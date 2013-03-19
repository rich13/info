<?
# - - - - - - - - - - - - -
# handle template replacements
# e.g. %%something%%


$config["info_link"] = '<a class="info_link" title="Info v'.$config["info_version"].'" href="http://info.northover.info">Info</a>';

# - - - - - - - - - - - - -

preg_match_all("/(\%\%(.*?)\%\%)/", $output, $matches, PREG_SET_ORDER);

if($matches){
	
	$variables = array();

	foreach ($matches as $variable) {
	    $variables[] = substr(substr($variable[1], 0, -2), 2);
	}

	foreach($variables as $var){
		if(!isset($config[$var])){
			echo("<p>Missing config item: ".$var."</p>");
		}
	    $rep = $config[$var];
	    $output = str_replace("%%". $var ."%%", $rep, $output);
	}
}
?>