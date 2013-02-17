<?
# - - - - - - - - - - - - -
# json mode

if($mode == "json"){
	
	$output_array = array(
		"path" 		=> $infopath.$page,
		"content"   => $output
		);
	$output = json_encode($output_array);

}
?>