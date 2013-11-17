<?
# - - - - - - - - - - - - -
# standard /pages

$pages_list = "";

$pages_keyword = "contents";

$pages_list_link = '[&#8801;]('.$infopath.$pages_keyword.'?ptrt='.$page.' "All pages")';

if(strstr($page, $pages_keyword)){

	$pages = true;
	$pages_list_link = '[&#8722;]('.$infopath.$ptrt.' "Back to '.$ptrt.'")';

	$pages_subdir = explode("/".$pages_keyword, $page);
	
	//var_dump($pages_subdir);

	if($pages_subdir[0] != $pages_keyword){
		$pages_subdir_path = $pages_subdir[0]."/";
	} else {
		$pages_subdir_path = "";
	}

	$pages_path = $content_path.$pages_subdir_path;

	//if($query){
	//echo $pages_path;

	//	$dir_iterator = new RecursiveDirectoryIterator($pages_path); // TODO: add subdir
	//	$dir_contents = new RecursiveIteratorIterator($dir_iterator, RecursiveIteratorIterator::CHILD_FIRST);
	//} else {
		$dir_contents = glob($pages_path."*");
		sort($dir_contents);

		// move index to the top
		$key = array_search($pages_path."index.md", $dir_contents);
		unset($dir_contents[$key]);
		$dir_contents = array_values($dir_contents);
		array_unshift($dir_contents, $pages_path."index.md");
	//}

//echo("<pre>");
//print_r($dir_contents);
//echo("</pre>");

	foreach ($dir_contents as $n => $filename) {

		$pagename = str_replace($pages_path, "", $filename); // just filename
		$pagename = str_replace(".md", "", $pagename); // no file ending

		$pagelink = $pagename; // allow for different link and name
		$pagename = str_replace("-", " ", $pagename); // prettify filenames


		if(is_dir($filename)){
			$pagename .= " ⇢";
			$abspagelink = $pagelink."/".$pages_keyword."?ptrt=".$ptrt; // maintain PTRT
		} else {
			$abspagelink = str_replace($pages_keyword."/", "", $pagelink);
		
			if($query){
				$abspagelink .= "?q=".$query;	
			}
		}

		if( // do magic filter search...
			preg_match_all("/$query/i", @file_get_contents($filename), $matches) &&

			// exclude unwanted files...
			$pagelink[0] != "." &&
			$pagelink[0] != "_" &&
			$pagelink[0] != "-" &&
			$pagename != "." &&
			$pagename != ".." &&
			$pagename != "404" &&
			!strstr($pagename, "_img") &&
			!strstr($pagename, ".png") &&
			!strstr($pagename, ".txt") &&
			!strstr($pagename, "/") &&
			@filesize($filename) != 0){

   			$pages_list .= "- [".$pagename."](".$abspagelink.")\r";
		
		}		
	}
}

?>