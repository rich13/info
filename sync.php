<?
# - - - - - - - - - - - - -
# This is based on DropPHP sample
# ...part of http://fabi.me/en/php-projects/dropphp-dropbox-api-client/

date_default_timezone_set("Europe/London");

# - - - - - - - - - - - - -

require_once("inc/ext/DropPHP/DropboxClient.php");

# - - - - - - - - - - - - -

$sync_config_file = "inc/config/_config.ini";

if(!file_exists($sync_config_file)){ die("No config file"); }

$sync_config = parse_ini_file($sync_config_file);

# - - - - - - - - - - - - -
# stop everything if we've looked within the cache threshold

$cache_threshold = $sync_config["cache_threshold"];

$lockfile = ".sync.lock";
if(!file_exists($lockfile)){
	unlock($lockfile);
}

$now = time();
$last_update = filemtime($lockfile);
$age = $now - $last_update;

if($age < $cache_threshold){ // if time since last update is less than threshold
	die("*"); // too young to die? nope.
} else {
	unlock($lockfile); // reset and continue...
}

# - - - - - - - - - - - - -
# get remaining config

$sync_app_key = $sync_config["app_key"];
$sync_app_secret = $sync_config["app_secret"];
$sync_directory = $sync_config["db_directory"];

# - - - - - - - - - - - - -
# setup Dropbox

$dropbox = new DropboxClient(array(
	'app_key' => $sync_app_key, 
	'app_secret' => $sync_app_secret,
	'app_full_access' => false,
),'en');

# - - - - - - - - - - - - -
# first try to load existing access token

$access_token = load_token("access");
if(!empty($access_token)) {
	$dropbox->SetAccessToken($access_token);
} elseif(!empty($_GET['auth_callback'])) // are we coming from dropbox's auth page?
{
	// then load our previosly created request token
	$request_token = load_token($_GET['oauth_token']);
	if(empty($request_token)) die('Request token not found');
	
	// get & store access token, the request token is not needed anymore
	$access_token = $dropbox->GetAccessToken($request_token);	
	store_token($access_token, "access");
	delete_token($_GET['oauth_token']);
}

// checks if access token is required
if(!$dropbox->IsAuthorized()){

	// redirect user to dropbox auth page
	$return_url = "http://".$_SERVER['HTTP_HOST'].$_SERVER['SCRIPT_NAME']."?auth_callback=1";
	$auth_url = $dropbox->BuildAuthorizeUrl($return_url);
	$request_token = $dropbox->GetRequestToken();
	store_token($request_token, $request_token['t']);
	die("Authentication required. <a href='$auth_url'>Click here.</a>");
}

# - - - - - - - - - - - - -
# Get overall directory metadata

$db_meta = $dropbox->GetMetadata($sync_directory);
if(!$db_meta){ die("Nothing coming back from Dropbox..."); }

# - - - - - - - - - - - - -
# get the hash and store it

$hash = $db_meta->hash;
$dropbox_sync_hash = "content/remote/.dropbox_sync_hash";

if(!file_exists($dropbox_sync_hash)){
	file_put_contents($dropbox_sync_hash, $hash);
	$our_hash = -1;	
} else {
	$our_hash = file_get_contents($dropbox_sync_hash);
}

# - - - - - - - - - - - - -
# look to see if the hash has changed

if($hash == $our_hash && $_SERVER['QUERY_STRING'] != "override") {
	
	$output = "="; # no update needed

} else {

	if(islocked($lockfile)) { # is this process running already?

		die("!"); # abort! abort!
	
	} else { # there's an update
		
		lock($lockfile); # prevent clashes by locking

		file_put_contents($dropbox_sync_hash, $hash);

		$files = $dropbox->GetFiles($sync_directory, true);

	}
}

# - - - - - - - - - - - - -

if(!empty($files)){

	foreach ($files as $file) {
		
		$download = true;

		$filepath = str_replace($sync_directory, "", $file->path);
		
		$filecheck[] = $filepath;
		//echo $filepath."<br />";

		if($filepath[0] == "." || $filepath[0] == "-"){ $download = false; }

		$is_dir = $file->is_dir;
		$db_mod = strtotime($file->modified);
		
		$remote_filepath = "content/remote/".$filepath;
		
		if(file_exists($remote_filepath)){
			$remote_mod = filemtime($remote_filepath);
		} else {
			$remote_mod = 0;
		}

		if($is_dir){
			$download = false;
			@mkdir($remote_filepath);
		}

		if($remote_mod < $db_mod &&
			$download == true){
			
			$dropbox->DownloadFile($file, $remote_filepath);
			
			//if($debug){
				//$output .= "+ ".$filepath."<br />";
			//}

			$output = "+"; # report that we made changes
		}
	}

	$dir_iterator = new RecursiveDirectoryIterator("content/remote/");
	$dir_contents = new RecursiveIteratorIterator($dir_iterator, RecursiveIteratorIterator::CHILD_FIRST);

	foreach ($dir_contents as $filename) {
		$localfile = str_replace("content/remote/", "", $filename);
		if(!in_array($localfile, $filecheck)){
			if($localfile != ".dropbox_sync_hash" &&
				$localfile != "." &&
				$localfile != ".."){
				//echo "- ".$localfile."<br/>";
				$output = "-";
				
				$current = "content/remote/".$localfile;
				$trashed = "content/trash/".md5($localfile);

				@rename($current, $trashed);
				
			}
		}
	}

	unlock($lockfile);

}
echo $output;

# - - - - - - - - - - - - -
#
function rmdir_recursive($dir) {
    foreach(scandir($dir) as $file) {
        if ('.' === $file || '..' === $file) continue;
        if (is_dir("$dir/$file")) rmdir_recursive("$dir/$file");
        else unlink("$dir/$file");
    }
    rmdir($dir);
}

# - - - - - - - - - - - - -
#
function islocked($lockfile){
	if(@file_get_contents($lockfile) == "locked"){
		return true;		
	}
	return false;
}

# - - - - - - - - - - - - -
#
function unlock($lockfile){
	if(!file_put_contents($lockfile, "unlocked")){
		die("Problem: couldn't unlock");		
	}
}

# - - - - - - - - - - - - -
#
function lock($lockfile){
	if(!file_put_contents($lockfile, "locked")){
		die("Problem: couldn't lock");		
	}
}

# - - - - - - - - - - - - -
#
function store_token($token, $name){
	if(!file_put_contents("tokens/$name.token", serialize($token)))
		die("Problem: couldn't store token");
}

# - - - - - - - - - - - - -
#
function load_token($name){
	if(!file_exists("tokens/$name.token")) return null;
	return @unserialize(@file_get_contents("tokens/$name.token"));
}

# - - - - - - - - - - - - -
#
function delete_token($name){
	@unlink("tokens/$name.token");
}

