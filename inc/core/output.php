<?
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

?>