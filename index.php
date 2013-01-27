<?
# - - - - - - - - - - - - -
# info
# - - - - - - - - - - - - -

$local_path = "content/local/";
$remote_path = "content/remote/";

if(!file_exists($local_path)){ die("No $local_path"); }

# - - - - - - - - - - - - -

$config_file = "inc/_config.ini";
$things_file = "inc/_things.ini";

# - - - - - - - - - - - - -

$markdown_file = "inc/ext/markdown.php";
$markdown_disabled = false;

# - - - - - - - - - - - - -

if(!file_exists(".htaccess")){ die("No .htaccess file: things will break"); }
if(!file_exists($config_file)){ die("No default config file"); }
if(!file_exists($things_file)){ die("No default things file"); }

if(!file_exists($markdown_file)){
	echo "No Markdown";
	$markdown_disabled = true;
} else {
	include $markdown_file;
}

# - - - - - - - - - - - - -

$config = parse_ini_file($config_file);
$things = parse_ini_file($things_file);

# - - - - - - - - - - - - -

$remote_enabled = $config["info_remote_enabled"];

if( $remote_enabled && !file_exists($remote_path)){
	mkdir($remote_path);
 }

# - - - - - - - - - - - - -
# remote overrides local

if($remote_enabled){

	if(file_exists($remote_path."_config.ini")){ $config_file = $remote_path."_config.ini"; }
	if(file_exists($remote_path."_things.ini")){ $things_file = $remote_path."_things.ini"; }

	$config = parse_ini_file($config_file);
	$things = parse_ini_file($things_file);

	$config["flags"] .= "remote";

} else {
	$config["flags"] .= "local";
}

# - - - - - - - - - - - - -

if(sizeof(glob($remote_path."*.md")) != 0 && $remote_enabled){
	$content_path = $remote_path;
} elseif(sizeof(glob($local_path."*.md")) != 0){
	$content_path = $local_path;
} else {
	die("No content files");
}

# - - - - - - - - - - - - -

$infopath = $config["info_path"];
$infotitle = $config["info_title"];

# - - - - - - - - - - - - -
# extra css

if(file_exists($content_path."_css.css")){
	$config["extra_css"] = '<link href="'.$infopath.$content_path.'_css.css" rel="stylesheet" type="text/css" media="all" />';
} else {
	$config["extra_css"] = "";
}

# - - - - - - - - - - - - -
# inventory check

foreach ($things as $name => $thing) {
	if(!file_exists($thing)){
		die("Problem: can't find ".$thing);
	} else {
		${$name} = file_get_contents($thing);
	}
}

# - - - - - - - - - - - - -
# process the page request

$page = htmlspecialchars(str_replace($infopath, "", $_SERVER["REQUEST_URI"]));

$exploded_page_ptrt = explode("?ptrt=", $page);
$page = $exploded_page_ptrt[0];
$ptrt = $exploded_page_ptrt[1];

# - - - - - - - - - - - - -

if($page == ""){ $page = "index"; }
if(is_dir($content_path.$page)){ 
	$page = $page."/index";
}

# - - - - - - - - - - - - -
# handle requests for .md files

if(strstr($page, ".md")){
	$filepath = $content_path.$page;
} else {
	$filepath = $content_path.$page.".md";
}

# - - - - - - - - - - - - -
# handle 404s

if(!file_exists($filepath)){
	$filepath = $content_path."404.md";
	if(!file_exists($filepath)){ die("Sorry, the 404 404'ed."); }
}

# - - - - - - - - - - - - -

$pages_list_link = '[&equiv;]('.$infopath.'pages?ptrt='.$page.' "All pages")';

# - - - - - - - - - - - - -

if($page == "pages" || $page == "pages.md"){
	
	$pages = true;

	$pages_list_link = '[&minus;]('.$infopath.$ptrt.' "Back to '.$ptrt.'")';

	$dir_iterator = new RecursiveDirectoryIterator($content_path);
	$iterator = new RecursiveIteratorIterator($dir_iterator, RecursiveIteratorIterator::SELF_FIRST);

	foreach ($iterator as $filename) {
		$pagename = str_replace($content_path, "", $filename);
		$pagename = str_replace(".md", "", $pagename);
		
		//if(is_dir($filename)){ $pagename .= "/"; }

		if( $pagename[0] != "." &&
			$pagename[0] != "_" &&
			$pagename != "index" &&
			$pagename != "404" &&
			!is_dir($filename) &&
			!strstr($pagename, "/.") &&
			filesize($filename) != 0){

   				$pages_list .= "- [".$pagename."](".$pagename.")\r";
		
		}
	}
}

# - - - - - - - - - - - - -

if($pages){

	if($pages_list){
		$content = $pages_list;
	} else {
		$content = "Nothing to see here.";
	}

} else {

	$content = file_get_contents($filepath);	

}

# - - - - - - - - - - - - -

$start = file_get_contents($things["start"]);
$end = file_get_contents($things["end"]);

$header = str_replace("%%pages_list_link%%", $pages_list_link, $header);

# - - - - - - - - - - - - -

$output = $start;

if(strstr($page, ".md") || $markdown_disabled){

	if(!$markdown_disabled){
		$config["md_switch"] = '<a href="'.$infopath.str_replace(".md", "", $page).'" title="View Markup">⇡</a>';
	}
	$output .= "<pre>";
	$output .= $header."\n\n";
	$output .= htmlspecialchars($content);
	$output .= "\n\n".$footer;
	$output .= "</pre>";

} else {

	if(!$markdown_disabled){
		$config["md_switch"] = '<a href="'.$infopath.$page.'.md" title="View Markdown">⇣</a>';
	}

	$output .= markdown($header);
	$output .= markdown($content);
	$output .= markdown($footer);

}

$output .= $end;

# - - - - - - - - - - - - -

preg_match_all("/(\%\%(.*?)\%\%)/", $output, $matches, PREG_SET_ORDER);
if($matches){
	
	$variables = array();

	foreach ($matches as $variable) {
	    $variables[] = substr(substr($variable[1], 0, -2), 2);
	 }

	foreach($variables as $var){
		if(!isset($config[$var])){
			die("Missing config item: ".$var);
		}
	    $rep = $config[$var];
	    $output = str_replace("%%". $var ."%%", $rep, $output);
	}
}

# - - - - - - - - - - - - -

echo $output;
