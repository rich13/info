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

$base_config_file = "inc/config/_empty_config.ini";
$local_config_file = "inc/config/_config.ini";
$remote_config_file = $remote_path."_config.ini";

$base_config = parse_ini_file($base_config_file);

if(file_exists($local_config_file)){
	$local_config = parse_ini_file($local_config_file);
	$config = $local_config;
} else {
	$config = $base_config;
}

if(file_exists($remote_config_file)){
	$remote_config = parse_ini_file($remote_config_file);
	$config = array_merge($config, $remote_config);
}

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

if($infopath == "/"){
	$page = htmlspecialchars($_SERVER["REQUEST_URI"]);	
} else {
	$page = htmlspecialchars(str_replace($infopath, "", $_SERVER["REQUEST_URI"]));
}

# - - - - - - - - - - - - -
# process querystring

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

$config["page"] = $page;

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
# handle requests for special extensions

$mode = "normal";

if(strstr($page, ".info")){
	$mode = "info";
	$page = str_replace(".info", "", $page);
}

if(strstr($page, ".json")){
	$mode = "json";
	$page = str_replace(".json", "", $page);
}

$filepath = $content_path.$page.".md";

if(strstr($page, ".md")){
	$mode = "md";
	$filepath = $content_path.$page;
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
# standard /pages

$pages_list_link = '[&#8801;]('.$infopath.'pages?ptrt='.$page.' "All pages")';

if(strstr($page, "pages")){
	
	$pages = true;

	$pages_list_link = '[&#8722;]('.$infopath.$ptrt.' "Back to '.$ptrt.'")';

	$dir_iterator = new RecursiveDirectoryIterator($content_path); // TODO: add subdir
	$dir_contents = new RecursiveIteratorIterator($dir_iterator, RecursiveIteratorIterator::SELF_FIRST);

	foreach ($dir_contents as $filename) {
		$pagename = str_replace($content_path, "", $filename); // just filename
		$pagename = str_replace(".md", "", $pagename); // no file ending
		
		$pagelink = $pagename; // allow for different link and name
		$pagename = str_replace("-", " ", $pagename); // prettify filenames

		if($query){
			$pagelink = $pagelink."?q=".$query;
		}

		if(is_dir($filename)){ $pagename .= " ⇢"; } // directories end with /

		if( // do magic filter search...
			preg_match_all("/$query/i", file_get_contents($filename), $matches) &&

			// exclude unwanted files...
			$pagelink[0] != "." &&
			$pagelink[0] != "_" &&
			$pagelink[0] != "-" &&
			$pagename != "404" &&
			$pagename != "img/" &&
			!strstr($pagename, ".png") &&
			!strstr($pagename, ".txt") &&
			!strstr($pagename, "index") &&
			//!strstr($pagename, "/") &&
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

include("inc/core/template_parts.php");

# - - - - - - - - - - - - -

include("inc/plugins/tags.php");

# - - - - - - - - - - - - -

include("inc/core/output.php");
include("inc/core/template_vars.php");

# - - - - - - - - - - - - -

include("inc/plugins/images.php");
include("inc/plugins/handles.php");

# - - - - - - - - - - - - -
# cache

@file_put_contents($cachefile, $output);

# - - - - - - - - - - - - -

include("inc/plugins/json.php");

# - - - - - - - - - - - - -

include("inc/core/actions.php");

# - - - - - - - - - - - - -
# all done

echo $output;

# - - - - - - - - - - - - -
