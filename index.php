<?
# - - - - - - - - - - - - -
# info
# - - - - - - - - - - - - -

date_default_timezone_set("Europe/London");

if(!file_exists(".htaccess")){ die("No .htaccess file"); }

$local_path = "content/local/";
$remote_path = "content/remote/";
$cache_path = "content/cache/";

if(!file_exists($local_path)){ die("No $local_path"); }

# - - - - - - - - - - - - -

$config_file = "inc/config/_config.ini";
if(!file_exists($config_file)){
	$config_file = "inc/config/_empty_config.ini";
}

$base_config = parse_ini_file($config_file);

$remote_config_file = $remote_path."/_config.ini";
$remote_config = array();
if(file_exists($remote_config_file)){
	$remote_config = parse_ini_file($remote_config_file);
}

$config = array_merge($base_config, $remote_config);

# - - - - - - - - - - - - -

$markdown_disabled = false;
$markdown_file = "inc/ext/markdown.php";

if(!file_exists($markdown_file)){
	echo "No Markdown";
	$markdown_disabled = true;
} else {
	include $markdown_file;
}

# - - - - - - - - - - - - -

$infopath = $config["info_path"];
$config["path_insert"] .= $infopath;

# - - - - - - - - - - - - -

$remote_enabled = $config["info_remote_enabled"];

if($remote_enabled && !file_exists($remote_path)){
	mkdir($remote_path);
}

# - - - - - - - - - - - - -

if(!file_exists($cache_path)){
	mkdir($cache_path);
}

# - - - - - - - - - - - - -
# remote/local

if($remote_enabled){
	$config["flags"] = "remote";
} else {
	$config["flags"] = "local";
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
# process the page request

$page = htmlspecialchars(str_replace($infopath, "", $_SERVER["REQUEST_URI"]));

$exploded_page_ptrt = explode("?ptrt=", $page);
$page = $exploded_page_ptrt[0];
$ptrt = preg_quote($exploded_page_ptrt[1], '/');

$exploded_page_query = explode("?q=", $page);
$page = $exploded_page_query[0];
$query = preg_quote($exploded_page_query[1], '/');

$exploded_page_action = explode("?a=", $page);
$page = $exploded_page_action[0];
$action = preg_quote($exploded_page_action[1], '/');

# - - - - - - - - - - - - -
# allow for /

if($page == ""){ $page = "index"; }

if(is_dir($content_path.$page)){ 
	$page = $page."/index";
}

# - - - - - - - - - - - - -
# get page from cache if there...

$cachefile = $cache_path.md5($page).".html";
$now = time();
$last_update = @filemtime($cachefile);
$age = $now - $last_update;

if(file_exists($cachefile) && ($age < $config["cache_threshold"])){
	die(file_get_contents($cachefile));
}

# - - - - - - - - - - - - -
# set $filepath and...
# handle requests for .md files

if(strstr($page, ".md")){
	$filepath = $content_path.$page;
} else {
	$filepath = $content_path.$page.".md";
}

# - - - - - - - - - - - - -
# handle 404s

if(!file_exists($filepath)){

	if(strstr($filepath, "/index")){
		die("we should show directory listing");
	}

	$filepath = "inc/md/404.md";
	if(!file_exists($filepath)){ die("Sorry, the 404 404'ed."); }
}

# - - - - - - - - - - - - -
# /pages

$pages_list_link = '[&#8801;]('.$infopath.'pages?ptrt='.$page.' "All pages")';

if($page == "pages" || $page == "pages.md"){
	
	$pages = true;

	$pages_list_link = '[&#8722;]('.$infopath.$ptrt.' "Back to '.$ptrt.'")';

	$dir_iterator = new RecursiveDirectoryIterator($content_path);
	$dir_contents = new RecursiveIteratorIterator($dir_iterator, RecursiveIteratorIterator::SELF_FIRST);

	foreach ($dir_contents as $filename) {
		$pagename = str_replace($content_path, "", $filename); // just filename
		$pagename = str_replace(".md", "", $pagename); // no file ending
		
		$pagelink = $pagename; // allow for different link and name

		if($query){
			$pagelink = $pagelink."?q=".$query;
		}

		if(is_dir($filename)){ $pagename .= "/"; } // directories end with /

		if( // do magic filter search...
			preg_match_all("/$query/i", file_get_contents($filename), $matches) &&

			// exclude unwanted files...
			$pagename[0] != "." &&
			$pagename[0] != "_" &&
			$pagename != "index" &&
			$pagename != "404" &&
			$pagename != "img/" &&
			!strstr($pagename, ".png") &&
			!strstr($pagename, ".txt") &&
			!strstr($pagename, "index") &&
			!strstr($pagename, "/.") &&
			filesize($filename) != 0){

			//echo "<pre>";
			//var_dump($matches);
			//echo "</pre>";
			//die();

   				$pages_list .= "- [".$pagename."](".$pagelink.")\r";
		
		}
	}
}

# - - - - - - - - - - - - -
# extra css

if(file_exists($content_path."_css.css")){
	$config["extra_css"] = '<link href="'.$infopath.$content_path.'_css.css" rel="stylesheet" type="text/css" media="all" />';
} else {
	$config["extra_css"] = "";
}

# - - - - - - - - - - - - -
# set $content

if($pages){

	$content = file_get_contents("inc/html/search.inc");

	if($pages_list){

		if($query){
			$content .= "## Pages containing \"".$query."\"\n";
		}

		$content .= $pages_list;
		
	} else {
		$content .= "Nothing to see here.";
	}

} else {

	$content = file_get_contents($filepath);	

}

# - - - - - - - - - - - - -
# template parts

$start = file_get_contents("inc/html/start.inc");
$end = file_get_contents("inc/html/end.inc");
$header = file_get_contents("inc/md/_header.md");
$footer = file_get_contents("inc/md/_footer.md");

$header = str_replace("%%pages_list_link%%", $pages_list_link, $header);

if($page != "index"){

	//$p = explode("/", $page); // then loop through to get breadcrumbs

	$crumb = str_replace("index", "", $page);

	$header = str_replace("%%info_pagetitle%%", " > [".ucfirst($crumb)."](".$page.")", $header);
	$start = str_replace("</title>", " > ".ucfirst($crumb)."</title>", $start);	

} else {
	$header = str_replace("%%info_pagetitle%%", "", $header);	
}

# - - - - - - - - - - - - -
# hiding tags

$tag_pattern = "/(\+\+(.*?)\+\+)/";
preg_match_all($tag_pattern, $content, $matches, PREG_SET_ORDER);

if($matches){
	
	$tags = array();

	foreach ($matches as $tag) {
	    $tags[] = substr(substr($tag[1], 0, -2), 2);
	}

	foreach($tags as $tag){
	    $tag_list .= $tag." ";
	    $content = str_replace("++". $tag ."++", "", $content);
	}

	//$hidden_tag_list .= "\n\n<!-- ".$tag_list."-->\n\n";
}

# - - - - - - - - - - - - -
# create $output

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
	$output .= $hidden_tag_list;
}

$output .= $end;

# - - - - - - - - - - - - -
# handle template replacements
# e.g. %%something%%

$config["info_link"] = '<a class="info_link" title="Info v'.$config["info_version"].'" href="http://richard.northover.info/info">Info</a>';

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
# image path rewrites

$img_pattern = "/(\<img src=\"(.*?)\" \/\>)/";
preg_match_all($img_pattern, $output, $matches, PREG_SET_ORDER);

if($matches){
	$imgs = array();

	foreach ($matches as $imgs) {
	    $imgs[] = $matches[2];
	}
	foreach($imgs as $img){
	    $output = str_replace("<img src=\"".$img."\" />", "<img src=\"".$content_path.$img."\" />", $output);
	}

}

# - - - - - - - - - - - - -
# cache
file_put_contents($cachefile, $output);

# - - - - - - - - - - - - -
# all done

echo $output;
