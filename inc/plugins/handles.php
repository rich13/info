<?
# - - - - - - - - - - - - -
# twitter @handle rewrites

$handle_pattern = "/@(.*?)( |\.|,|\<)/";
preg_match_all($handle_pattern, $output, $matches, PREG_SET_ORDER);

if($matches){
	$handles = array();

	foreach ($matches as $m) {
	    $handles[] = $m[1];
	}
	foreach($handles as $handle){
	    $output = str_replace("@".$handle, '<a href="https://www.twitter.com/'.$handle.'">@'.$handle.'</a>', $output);
	}

}
?>