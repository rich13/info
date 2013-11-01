<?
# - - - - - - - - - - - - -
# image path rewrites
# ensures that the image src attribute has the full path to the file...

$img_pattern = "/(\<img src=\"(.*?)\" \/\>)/";
preg_match_all($img_pattern, $output, $matches, PREG_SET_ORDER);

if($matches){
	$imgs = array();

	foreach ($matches as $m) {
	    $imgs[] = $m[2];
	}
	foreach($imgs as $img){
		if(!strstr($img, "http://")){
			$output = str_replace("<img src=\"".$img."\" />", "<img src=\"".$content_path.$img."\"/>", $output);
		}
	}
}
?>