<?
# - - - - - - - - - - - - -
# hiding tags
# takes tags in the form:
# "++word++"
# and converts to comments which are found in searches, but not seen when rendered

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

	$hidden_tag_list .= "\n\n<!-- ".$tag_list."-->\n\n";
}
?>