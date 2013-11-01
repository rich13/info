<?
# - - - - - - - - - - - - -
# result highlights

$handle_pattern = "/".$query."(?!([^<]+)?>)/i";
preg_match_all($handle_pattern, $output, $matches, PREG_SET_ORDER);

if($matches){

	//var_dump($matches);
	//die();

	$highlights = array();

	foreach ($matches as $m) {
	    $highlights[] = $m[0];
	}

	//var_dump($highlights);
	//die();

	foreach($highlights as $highlight){
	    $output = str_replace($highlight, '<span class="hi">'.$highlight.'</span>', $output);
	}

}
?>