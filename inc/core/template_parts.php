<?
# - - - - - - - - - - - - -
# template parts

$start = file_get_contents("inc/html/start.inc");
$end = file_get_contents("inc/html/end.inc");

$headerpath = "inc/md/_header.md";
$footerpath = "inc/md/_footer.md";

if(file_exists($remote_path."_header.md")){ $headerpath = $remote_path."_header.md"; }
if(file_exists($remote_path."_footer.md")){ $footerpath = $remote_path."_footer.md"; }

$header = file_get_contents($headerpath);
$footer = file_get_contents($footerpath);

$header = str_replace("%%pages_list_link%%", $pages_list_link, $header);

if($page != "index"){

	//$p = explode("/", $page); // then loop through to get breadcrumbs

	$crumb = str_replace("/index", "", $page);

	$header = str_replace("%%info_pagetitle%%", " > [".ucfirst($crumb)."](".$page.")", $header);
	$start = str_replace("</title>", " > ".ucfirst($crumb)."</title>", $start);	

} else {
	$header = str_replace("%%info_pagetitle%%", "", $header);	
}

?>