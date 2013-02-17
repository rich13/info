<?
# - - - - - - - - - - - - -
# final actions

if($action == "unlock"){
	file_put_contents(".sync.lock", "unlocked");	
	file_put_contents("content/remote/.dropbox_sync_hash", "");
	echo "sync unlocked";
}

if($action == "purgecache"){
	$files = glob($cache_path."*");
	foreach($files as $file){
  		if(is_file($file)){
  			unlink($file); // delete file
  		}
	}
	echo "cache purged";
}

?>