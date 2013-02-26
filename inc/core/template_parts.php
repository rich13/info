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

	$p = explode("/", $page); // then loop through to get breadcrumbs

	if ($p[0] == ""){ array_shift($p); }

	foreach ($p as $n => $crumb) {

		if($n==0){ $crumblink = $infopath.$p[0]; }
		if($n==1){ $crumblink = $infopath.$p[0]."/".$p[1]; }
		if($n==2){ $crumblink = $infopath.$p[0]."/".$p[1]."/".$p[2]; }
		if($n==3){ $crumblink = $infopath.$p[0]."/".$p[1]."/".$p[2]."/".$p[3]; }

		if($crumb != ""){
			$trail .= "> [".ucfirst($crumb)."](".$crumblink.")";
			$titletrail .= " - ".ucfirst($crumb);
		}
	}

	$header = str_replace("%%info_pagetitle%%", $trail, $header);
	$start = str_replace("</title>", $titletrail."</title>", $start);


} else {
	$header = str_replace("%%info_pagetitle%%", "", $header);	
}

?>