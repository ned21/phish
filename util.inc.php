<?php
/* $Id$ */

function listDir($dir) {
	//global $dir because we may change it
	global $pg, $pic, $dir, $pic_index;
	
	$rFiles = array();
	$rDirs = array();
	$rMovies = array();
	
	if (is_dir($dir) || $dir == '')	{
		
		$start_dir = getcwd();
		chdir("$start_dir/$dir");
		
		//directory contents to $allFiles and $allDirs
		if ($handle = opendir("$start_dir/$dir")) {
			while($file = readdir($handle)) {
				if ($file[0] == "." || $file == "cache" || $file == "_phish")
					continue;
				if (validFormat($file)) {
					$rFiles[] = $file;
				} elseif (is_dir($file)) {
					$rDirs[] = $file;
				} elseif (validFilmFormat($file)) {
					$rMovies[] = $file;
				}
			}
			closedir($handle);
		}
		
		//sort photos alphabetically
		if (isset($rFiles))
			if ($pg['main']['sort_photo'] == 1) {
                        	$sFiles = array();
                        	foreach($rFiles as $val) {
                                	$key = filemtime($val).$val;
                                	$sFiles[$key] = $val;
                        	}
                        	ksort($sFiles);
                        	// Now copy sFiles back to rFiles using integer indices
                        	$i=0;
                        	foreach($sFiles as $val) {
                                	$rFiles[$i] = $val;
                                	$i = $i+1;
                        	}
			}
			else {
				sort($rFiles);
			}

		if (isset($rDirs)) {
			//optionally sort directories by file modification time
			switch ($pg['main']['sort']) {
				case 2: sort($rDirs);
						break;
				case 1:
					$sDirs = array();
					foreach($rDirs as $val) {
						$key = filemtime($val).$val;
						$sDirs[$key] = $val;
					}
					ksort($sDirs);
					$rDirs = $sDirs;
					break;
				case 0: //fallthru
				default: break; //do nothing, assume the filesystem passes back files in creation order
			}
		}
		
		chdir($start_dir);		
	}
	
	//set the index number of the current photo
	if (isset($pic_index) && (!isset($pic) || $pic == '')) {
		$pic = $rFiles[$pic_index];
	}
	
	// $pic_index = ($pic_index + sizeOf($rFiles)) % sizeOf($rFiles);
	if (!isset($pic_index) || $rFiles[$pic_index] != $pic)
		$pic_index = array_search($pic, $rFiles);
	
	return array($rFiles, $rDirs, $rMovies);
}

/*
 * Return just the photos of a directory
 */
function photoList($dir)
{
	$files = array();
	if ($dh = opendir($dir)) 
	{
		$root = getcwd();
		chdir($dir);
		//read out current dir contents
		while ($file = readdir($dh))
		{
			if (!is_dir($file) && validFormat($file))
			{
				$files[] = $file;
			}
		}
		chdir($root);
		closedir($dh);
	}
	return $files;
}

function validFormat($file) {
	global $pg;
	foreach ($pg['pic_formats'] as $val)
		if ($val == suffix($file))
			return true;
	return false;
}

function validFilmFormat($file) {
	global $pg;
	foreach ($pg['film_formats'] as $val)
		if ($val == suffix($file))
			return true;
	return false;
}

function get_dir_links($linkpart, $basedir) {
	global $pg;
	if ($basedir == '')
		$dirLinks = $pg['owner']['name'];
	else
		$dirLinks = $linkpart.'">'.$pg['owner']['name'].'</a>';
	$arr = explode('/', $basedir);
	for($i=0; $i<sizeOf($arr); $i++) {
		$cur = $arr[$i];
		if (strlen($cur) > 0) {
			if ($cur[0] == '/') {
				$cur = substr($cur, 1);
			}
			$allDir .= $cur.'/';
				$dirLinks .= ' - '.$linkpart.$allDir.'">'.$cur.'</a>';
		}
	}
	return $dirLinks;
}

//holds HTML for holding images in directory view
function image_holder($dir, $file, $text) {
	if (strlen($dir) > 0) $dir .= "/";
	$ret = "<img alt=\"$file\" src=\"cache/$dir/$file".".thumb\" />";
	if ($text != '')
		$ret .= "<br />".$text;
	return $ret;
}

function generate_thumb($dir, $file, $force = false) 
{
	global $pg;
	$output_file = "cache/$dir/$file".".thumb";
	$input_file = "./$dir/$file";
	if (!file_exists($input_file))
	{
		//sanity check, prevents people from sending shell commands in filenames
		return;
	}
	//check if cache exists
	if (!file_exists("cache/$dir")) {
		if (!file_exists("cache"))
			mkdir ("cache", 0775);
	    $temp = getcwd();
		chdir("cache");
		$list = explode("/", $dir);
		foreach ($list as $d)
		{
			if ($d != '' && $d[0] != ".")
			{
				if (!file_exists($d))
					mkdir($d, 0775);
				chdir($d);
			}
		}
		chdir($temp);
	}
	//check if thumb exists //check if it is the latest version
	if (!file_exists($output_file)  or filemtime($input_file) > filemtime($output_file)
		or $pg['admin']['thumb_size_updated'] > filemtime($output_file)) {
		
		if (!$force && file_exists($output_file) && filesize($output_file) > 0)
		{
			//check the dimensions are actually wrong before resizing the photo
			$dims = getImageSize($output_file);
			if (max($dims[0], $dims[1]) == $pg['admin']['thumb_size'])
				return;
		}
		
		//generate a new thumb if there is no thumb
		//or the picture is newer than the current thumb
		//or if $pg['admin']['thumb_size_updated'] is newer than the thumb
		
		resize_photo ($input_file, $output_file, $pg['admin']['thumb_size']);
	}
}

function resize_photo ($in, $out, $long_side) {
	global $pg;
	
	if ($pg['admin']['use_convert']) {
		//using ImageMagik Convert
		$com_switches = "-size $long_side"."x"."$long_side";
		$com_switches .= ' "'.$in.'" -resize '.$long_side.'x'.$long_side;
		//$com_switches .= " -modulate 100,0"; //generates b&w thumbnails
		$com_switches .= ' +profile "*" "'.$out.'"';
		system("convert $com_switches &");
	} else {
		//using gd2 (generally slower than convert)
		$src_img = imagecreatefromjpeg($in); 
		$old_x=imageSX($src_img); 
		$old_y=imageSY($src_img); 
		if ($old_x > $old_y) { 
			$thumb_w=$long_side; 
			$thumb_h=$old_y*($long_side/$old_x); 
		} else {
			$thumb_w=$old_x*($long_side/$old_y); 
			$thumb_h=$long_side; 
		}
		$dst_img=ImageCreateTrueColor($thumb_w,$thumb_h); 
		imagecopyresampled($dst_img,$src_img,0,0,0,0,$thumb_w,$thumb_h,$old_x,$old_y);  
		imagejpeg($dst_img, $out);
		imagedestroy($dst_img);
	}
	//change to public readable
	@chmod($out, 0664);
}

//read comments out of the cache/$file.nfo file, by default ignoring //comments
function read_comments($file, $readHidden = false) 
{
	$ret = array();
	if ($fd = @fopen($file, "r")) 
	{
		while (!feof($fd)) 
		{
			$foo = fgets($fd, 1024);
			if (($readHidden || substr($foo, 0, 2) != "//") && trim($foo) != '') 
			{
				if ($foo[strlen($foo)-1] == "\n")
					$foo = substr($foo, 0, -1);
				$ret[] = $foo;
			}
		}
  		fclose ($fd);
  	}
  	return $ret;
}

function suffix($filename) {
   return strtolower(substr($filename,strrpos($filename,".") + 1));
}

//post a new comment
function new_comment($comment, $dir, $pic) {
	global $pg;
	if (!$pg['admin']['allow_comments']) 
		return;
// Silently ignore comments with http in to screw the link spammers
   if (preg_match("/http/", $comment))
      return;
	if (($comment = trim($comment)) != '') {
		$comment_file = "/cache/$dir/$pic.nfo";
		if ($fd = @fopen(getcwd().$comment_file, "a")) {
			fwrite($fd, strip_tags(stripslashes($comment), '<a><b><i><u><img>')."\n");
			fclose($fd);
		}
		@chmod($comment_file, 0660);
		if ($pg['admin']['comment_notify']) {
			$bar = substr($dir, strrpos($dir, '/')+1).$pic;
			$mail = "AUTO GENERATED E-MAIL by ".$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF'];
			$mail .= "\n\nNew comment at ".str_replace(" ", "%20", "\"{$pg['HTTP_root']}"."index.php?pic=$file\"")." added at ";
			$mail .= date("D M j G:i:s")."\n\nComment : \"$comment\"";
			$mail = stripslashes($mail);
			$params = 	"From: {$pg['owner']['name']}'s photo gallery <{$pg['admin']['email']}>\r\n";
			@mail("{$pg['owner']['name']} <{$pg['admin']['email']}>", "New Comment added for $bar", $mail, $params);
		}
	}
}

function show_all_comments() {
	global $comment_count, $pg;
	$comment_count = 0;
	$left = true;
	
	echo "<h2 style=\"text-align: center;\">All comments on pictures!</h2>\n";
	echo "<p style=\"text-align: center;\">(<a href=\"index.php\">return to the gallery</a>)</p>\n";
	echo "  <table style=\"margin-right: auto; margin-left: auto; width:90%\">\n";
	search("./cache");
	echo "  </table>\n";
	echo "<p style=\"text-align: center; font-weight: bold;\">$comment_count comments found</p>\n";
}

function search($dir) {
	if ($handle = opendir("$dir")) {
		while (false !== ($file = readdir($handle))) {
			if ($file !== "." && $file !== "..") {
				//if a .nfo file
				if (substr($file, strrpos($file, ".")) == ".nfo" && $file != "album.nfo") {
					if (count($comments = read_comments("$dir/$file")) > 0)
					{
						//found comment file, find picture it refers to
						global $left, $comment_count, $pg;
						$comment_count += count($comments);
						
						$filename = basename($file, ".nfo");
						$mydir = substr($dir, strpos($dir, 'cache/') + strlen('cache/'));
						$pic = $mydir."/$filename";
						generate_thumb($mydir, $filename);
								
						//new table version
						echo "<tr>\n";
						echo "	<td style=\"width: ".($pg['admin']['thumb_size'])."px\">\n";
						if ($left) {
							echo "	<a href=\"index.php?pic=$pic\">\n";
							//echo "	<img src=\"$pic\" alt=\"$temp\" style=\"vertical-align: middle;\" /></a>\n";
							echo image_holder($mydir, $filename, '');
						}
						echo "	</td>\n";
						
						echo "	<td style=\"text-align: ".($left ? "left" : "right")."; vertical-align: middle; padding-right: 20px;\">\n";
						echo "	<ul>\n";
						foreach ($comments as $val) {
							echo "		<li>$val<li />\n";
						}
						echo "	</ul>\n";
						echo "	</td>\n";
						
						echo "	<td style=\"width: ".($pg['admin']['thumb_size'])."px\">\n";
						if (!$left) {
							echo "	<a href=\"index.php?pic=$pic\">\n";
							echo image_holder($mydir, $filename, '');
						}
						echo "	</td>\n";
						echo "</tr>\n";						
						$left = !$left;
					}
				}
				if (is_dir("$dir/$file")) {
					search("$dir/$file");
				}
			}
		}
		closedir($handle);
	}
}

/*
 *	Safely gets allowed user cookies that modify $pg variables
 */
function global_user_prefs() 
{
	global $pg, $fake_cookies;
	
	//not strictly necessary as users can override any $pg['main'][] without harm
	$allowed = array("columns", "layout", "max_photo_size", "dirphotos");
	
	if (isset($fake_cookies))
	{
		//we are getting values from the user who has just set a cookie
		foreach ($allowed as $name)
		{
			if (isset($fake_cookies[$name]))
				$pg['main'][$name] = $fake_cookies[$name];
		}
	}
	else
	{
		//we are getting values from a cookie
		foreach ($allowed as $name)
		{
			if (isset($_COOKIE[$name]))
				$pg['main'][$name] = $_COOKIE[$name];
		}
	}
}

function set_user_cookie() 
{
	global $pg, $fake_cookies;
	
	$fake_cookies = array();
	
	foreach ($_POST['new'] as $key=>$val) 
	{
		//set new cookie, will not timeout
		//users can set any cookies they want... we just won't look at them
		setcookie($key,$val, time()+30000000, "", $_SERVER['HTTP_HOST']);
		//do this for immediate change
		$fake_cookies[$key] = $val;
	}
}

function tar_dir ($dir) {
	if (strpos($_SERVER['SERVER_SOFTWARE'], "Win32")) {
		return;
	}
	$dir = str_replace("*", "", $dir); //so you can't escape out
	$cwd = getcwd();
	@chdir($dir);
	$filename = substr($dir, strrpos($dir, "/")+1).".zip";

	/*exec('tar -cf /dev/null --totals *.jpg *.png *.gif *.JPG 2>&1', $array);
	if (isset($array))
	foreach($array as $line) {
		if (strlen($line) > 20 && substr($line, 0, 19) == "Total bytes written") {
			$arr = explode(' ', $line);
			if (is_numeric($arr[3]))
				$file_size_est = $arr[3];
			break;
		}
	}*/
	
    header("Content-Type: application/x-zip"); 
	//if (isset($file_size_est))
	//	header("Content-Length: $file_size_est");
	header("Content-Disposition: attachment; filename=$filename\n");
	
	passthru('zip -0 - *.jpg *.JPG &');
	@chdir($cwd);
	exit();
}

function old_tar_dir ($dir) {
	if (strpos($_SERVER['SERVER_SOFTWARE'], "Win32")) {
		return;
	}
	$dir = str_replace("*", "", $dir); //so you can't escape out
	$cwd = getcwd();
	@chdir($dir);
	$filename = substr($dir, strrpos($dir, "/")+1).".tar";

	exec('tar -cf /dev/null --totals *.jpg *.png *.gif *.JPG 2>&1', $array);
	if (isset($array))
	foreach($array as $line) {
		if (strlen($line) > 20 && substr($line, 0, 19) == "Total bytes written") {
			$arr = explode(' ', $line);
			if (is_numeric($arr[3]))
				$file_size_est = $arr[3];
			break;
		}
	}
	
    header("Content-Type: application/x-tar"); 
	if (isset($file_size_est))
		header("Content-Length: $file_size_est");
	header("Content-Disposition: attachment; filename=$filename\n");
	
	passthru('tar -c *.jpg *.png *.gif *.JPG &');
	@chdir($cwd);
	exit();
}
?>
