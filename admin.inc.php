<?
/*
 *	Creates admin pages, performs updates, and creates the divs which the pages live in
 */
function admin($page) {
	if ($page == '')
		$page = "admin_start";
	
	//if there's an update, perform it
	global $update, $update_text;
	if (isset($update) && function_exists($update."_update")) {
		$update .= "_update";
		$update_text = '';
		$update();
	}
	
	$a_pages = 	array(	"admin_start" => array("Choose configuration page", ""),
						"admin_personal" => array("Change administrator's settings","Change my details"),
						"admin_look" => array("Colours and Appearance","Site Appearance"),
						"admin_moderate" => array("Show/Hide Comments","Moderate comments"),
						"admin_upload" => array("Upload new pictures","Upload photos"),
						"admin_modify" => array("Resize photos, rename directories and pictures","Modify photos"),
						"admin_delete" => array("Remove unwanted photos","Delete photos"),
						"admin_album_comments" => array("Edit album comments","Album comments"),
						"admin_change_password" => array("Change password","Change password"),
						"logout&admin=exit" => array("", "Logout")
				);
	
	echo "<div class=\"a_config_menu\">\n";
	echo "	<h3 class=\"a_config\">Admin Pages</h3>\n";
	echo "	<ul class=\"config_menu\">\n";
	foreach ($a_pages as $key=>$val) {
		if ($val[1] == "") continue;
		echo	"              <li><a href=\"index.php?action=admin&page=".$key."\">".$val[1]."</a></li>\n";
	}
	echo "              <li><a href=\"index.php\">Return to albums</a></li>\n";
	echo "	</ul>\n";
	echo "</div>\n";
	
	$my_page = $page."_page";
	
	echo "<div class=\"a_config\">\n";
	echo "<h3 class=\"a_config\">{$a_pages[$page][0]}</h3>\n";
	
	echo $update_text;
	if (function_exists($my_page)) {
		$my_page();
	} else {
		echo "<b>Warning</b> page '$page' does not exist\n";
	}
	echo "</div>\n";
}

function admin_start_page() {
	global $pg;
	echo 	"<div style=\"font-weight: bold; text-align: left;\"><p>Welcome {$pg['owner']['name']}</p>\n".
			"<p>Choose an option from the side menu</p>\n".
			"<p>Warning: 10 minutes of inactivity will automatically log you out</p>\n".
		 	"</div>\n";
}

function admin_personal_page() {
	$conv_on = array(1 => "ImageMagik Convert", 0 => "PHP GD2");
	$on_off = array(1 => "On", 0 => "Off");
	
	$vars = array(
				new pg_setting ("['owner']['name']", 			"text", 	"Administrator's name"),
				new pg_setting ("['admin']['email']", 			"text", 	"Administrator's e-mail"),
				new pg_setting ("['owner']['message']",			"textarea", "Front page message"),
				new pg_setting ("['admin']['use_convert']",		"radio", 	"Image resizing tool", $conv_on),
				new pg_setting ("['admin']['allow_comments']",	"radio", 	"Allow photo commenting", $on_off)
			);
	make_admin_config_table($vars);
}

function admin_change_password_page() {	
	$vars = array(
				new pg_setting ("[admin][user]", 		"text", 		"New username"),
				new pg_setting ("_new_password_1", 		"password", 	"New password"),
				new pg_setting ("_new_password_2",		"password", 	"Verify new password")
			);
	
	if (file_exists("user.inc.php")) {
		$vars[] = new pg_setting ("_old_username", 		"text", 		"Current username");
		$vars[] = new pg_setting ("_old_password", 		"password", 	"Current password");
	}
	make_admin_config_table($vars, "admin_new_password");
}

function admin_new_password_page() {
	global $pg_new_password_1, $pg_new_password_2, $pg_old_password, $pg_old_username;
	
	$l_pg = cleanup_keys($_POST['pg']);
	$new_username = $l_pg['admin']['user'];
	
	if ($new_username == '' || $pg_new_password_1 == '') {
		echo "<p>Please enter a new password!, please go back <a href=\"index.php?action=admin&page=admin_change_password\">here</a></p>";
	} elseif 
		($pg_new_password_1 != $pg_new_password_2) {
		echo "<p>New passwords do not match, please go back <a href=\"index.php?action=admin&page=admin_change_password\">here</a></p>";
	} else {
		if (!file_exists("user.inc.php") || authenticate($pg_old_username, $pg_old_password)) {
			$file_contents = "<?php\n".
							 "\$pg['admin']['user'] = '$new_username';\n".
							 "\$pg['admin']['password'] = '".md5($pg_new_password_1)."';\n".
							 "?>";
			if ($fh = @fopen("user.inc.php", "w")) {
				//write back modified contents
				fwrite($fh, $file_contents);
				fclose($fh);
				chmod("user.inc.php", 0660);
			} else {
				echo "File problem";
			}
			echo "<p>Please log in with new username / password</p>\n";
			admin_login();
		} else {
			echo "<p>Incorrect current username / password, please go back <a href=\"index.php?action=admin&page=admin_change_password\">here</a></p>\n";
		}
	}
}

function admin_look_page() {
	
	$on_off = array( 1=>"On", 0=>"Off");
	$font_sizes = array ('6pt' => '6pt', '8pt' => '8pt', '10pt' => '10pt','12pt' => '12pt', '14pt' => '14pt', '16pt' => '16pt');
	$border_sizes = array ('0px' => '0px', '1px' => '1px', '2px' => '2px', '3px' => '3px', '4px' => '4px', '5px' => '5px', '6px' => '6px');
	$sorting = array( 0 => 'Time (created)', 1 => 'Time (modified)', 2 => 'Alphabetic');
	$main_width = array (50 => 50, 60 => 60, 70 => 70, 80 => 80, 90 => 90, 100 => 100);
	$plus_minus = array ("min" => 50, "max" => 350, "plus" => 10);
	$columns = array (1 => 1, 2 => 2, 3 => 3, 4 => 4, 5 => 5, 6 => 6, 7 => 7, 8 => 8, 9 => 9);
	$ss_times = array (0 => '0 (paused)', 2 => 2, 3 => 3, 4 => 4, 5 => 5, 6 => 6, 7 => 7, 8 => 8, 9 => 9, 10 => 10, 11 => 11, 12 => 12, 13 => 13, 14 => 14, 15 => 15);
	
	$vars = array(
	
			new pg_setting ("['css']['table.picTable']['border-color']","text", 	"Main border"),
			new pg_setting ("['css']['table.picTable']['border-width']","select", 	"Main border width", 					$border_sizes),
			new pg_setting ("['css']['table.picTable']['background']",	"text", 	"Main backing"),
			new pg_setting ("['css']['.thumb']['background']",			"text", 	"Thumbnail background"),
			new pg_setting ("['css']['.curThumb']['background']",		"text", 	"Selected thumbnail background"),
			new pg_setting ("['css']['.thumb']['border-width']",		"select", 	"Thumbnail border width", 				$border_sizes),
			new pg_setting ("['css']['.thumbdir']['background']",		"text", 	"Thumbnail directory background"),
			new pg_setting ("['css']['.thumbdir']['border-width']",		"select", 	"Thumbnail directory border width",		$border_sizes),
			new pg_setting ("['css']['body']['color']",					"text", 	"Text"),
			new pg_setting ("['css']['body']['font-size']", 			"select", 	"Text size", 							$font_sizes),
			new pg_setting ("['css']['a']['color']",					"text", 	"Link"),
			new pg_setting ("['css']['a:hover']['color']",				"text", 	"Mouse on a link"),
			
			new pg_setting ("['admin']['thumb_size']", 					"plus_minus", 	"Thumbnail size",					$plus_minus),
			new pg_setting ("['main']['columns']",						"select", 	"Columns per page", 					$columns),
			new pg_setting ("['admin']['main_width']", 					"select", 	"Main table width %",					$main_width),
			new pg_setting ("['main']['slideshow_time']", 				"select", 	"Default slideshow delay", 				$ss_times),

			new pg_setting ("['main']['dirnames']", 					"radio", 	"Show directory names", 				$on_off),
			new pg_setting ("['main']['dirphotos']",  					"radio", 	"Show directory photos", 				$on_off),
			new pg_setting ("['main']['filenames']", 					"radio", 	"Show thumbnail filenames", 			$on_off),
			new pg_setting ("['main']['dimensions']", 					"radio", 	"Show photo dimensions", 				$on_off),
			new pg_setting ("['admin']['comment_notify']", 				"radio", 	"Notify when new comment submitted", 	$on_off),
			new pg_setting ("['main']['side_photos']", 					"radio", 	"Show photo side links", 				$on_off),
			new pg_setting ("['main']['layout']",						"radio", 	"Photo page layout",					array('table'=>'Table', 'flat'=>'Flat (show all)')),
			new pg_setting ("['main']['photo_with_table']",				"radio", 	"Show table behind photo",				$on_off),
			new pg_setting ("['main']['list_with_photo']", 				"radio", 	"Photo listings when viewing photo",	$on_off),
			new pg_setting ("['main']['sort']", 						"select", 	"Sorting", 								$sorting),
			new pg_setting ("['admin']['header']",						"text", 	"Page header file"),
			new pg_setting ("['admin']['footer']",						"text", 	"Page footer file"),
			new pg_setting ("['css']['body']['background-image']",		"text", 	"Background image")
			);
	
	make_admin_config_table($vars);
}

function admin_moderate_page() 
{
	echo "<table style=\"width:100%;\">\n";
	mod_search("./cache");
	echo "</table>\n";
}

function admin_moderate_update()
{
	global $comment_file, $mod_comment, $mod_check;
	if (isset($comment_file)) 
	{
		if (file_exists(stripslashes($comment_file))) 
		{
			$file_contents = '';
			for ($i = 0; $i < sizeOf($mod_comment); $i++)
			{
				$file_contents .= (isset($mod_check[$i]) ? "" : "//").stripslashes($mod_comment[$i])."\n";
			}		
			if ($fh = fopen($comment_file, "w")) 
			{
				//remove the html special chars...
				$file_contents = str_replace(array("&gt;", "&lt;", "&quot;", "&amp;"), array(">", "<", "\"", "&"), $file_contents);
				//write back modified contents
				fwrite($fh, $file_contents);
				fclose($fh);
			}
		} else
			echo "$changed_comment_file not found\n";
	}
}

/* Find all comments and create the comment moderation page
 */
function mod_search($dir) 
{	
	global $page;
	
	if ($handle = opendir("$dir")) 
	{
		while (false !== ($file = readdir($handle))) 
		{
			if ($file !== "." && $file !== "..") 
			{
				//if a .nfo file
				if (substr($file, strrpos($file, ".")) == ".nfo" && $file != "album.nfo") 
				{
					//read_comments gets all the comments out the file
					if (count($com = read_comments("$dir/$file", true)) > 0)
					{
						echo "	<tr>";
						$temp = substr($file, strrpos($file, '/'), strrpos($file, '.'));
						$here = substr(substr($dir, 2) , 0, strrpos(substr($dir, 2), '/')).'/'.$temp;
						$pic = $dir.'/'.$temp.".thumb";

						echo "	<td style=\"width: 30%\">\n".
							 "    <div align=\"center\">\n".
							 "		<img src=\"$pic\" border=0 alt=\"$temp\" /></a>\n".
							 "    </div>\n".
							 "	</td>\n";

						echo "	<td>\n".
							 "	  <form method=\"post\" action=\"index.php\">\n".
							 "      <input type=\"hidden\" name=\"action\" value=\"admin\" />\n".
							 "      <input type=\"hidden\" name=\"page\" value=\"$page\" />\n".
							 "      <input type=\"hidden\" name=\"update\" value=\"admin_moderate\" />\n".
							 "      <input type=\"hidden\" name=\"comment_file\" value=\"$dir/$file\" />\n";
						$i = 0;
						foreach ($com as $comment)
						{
							if (substr($comment, 0, 2) == "//")
							{
								$active = "";
								$comment = substr($comment, 2);
							} 
							else
							{
								$active = "checked=\"checked\"";
							}
							echo "	<p>\n".
								 "		<input type=\"checkbox\" name=\"mod_check[$i]\" $active />\n".
								 "      <input type=\"text\" name=\"mod_comment[$i]\" value=\"".htmlspecialchars($comment)."\" size=\"50\" /><br />\n".
								 "	</p>\n";
							$i++;
						}
						
						echo "		<input type=\"submit\" name=\"submit\" value=\"Update\" /></form>\n";
						echo "	</td>\n";

						echo "	</tr>\n\n";
					}
				}
				if (is_dir($dir."/".$file)) 
				{
					mod_search($dir."/".$file);
				}
			}
		}
		closedir($handle);
	}
}

function unzip($dir, $file, $loc) {
	//unzip junking directories, overwriting without asking
	exec("unzip -jo $dir/$file -d $loc/");
}

function admin_upload_update()
{
	global $dir, $make_dir, $submit, $update_text;
		
	if ($submit == "Upload File(s)") {
		if (isset($_FILES)) {
			$update_text .= "<p>Photos uploaded: \n";
			foreach($_FILES['userfile']['name'] as $key=>$name) {
				if ($name != '') {
					move_uploaded_file($_FILES['userfile']['tmp_name'][$key], "./$dir/$name");
					if (suffix($name) == "zip") {
						//use this to unzip to temporary directory
						//chmod files correctly
						//move files back, then delete the directory
						$start_dir = getcwd();
						chdir($dir);
						$bar = "myPhishTmpDir";
						mkdir($bar, 0775);
						unzip('.', $name, $bar);
						if ($dh = opendir($bar)) {
							while ($baz = readdir($dh)) {
								if (!is_dir($baz)) {
									$update_text .= $baz."<br>";
									chmod("$bar/$baz", 0664);
									rename("$bar/$baz", "$baz");
								}
							}
							closedir($dh);
						}
						unlink($name);
						rmdir($bar);
						chdir($start_dir);
					} else
						chmod("./$dir/$name", 0664);
					$update_text .= "<br />".$name."\n";
					generate_thumb($dir, $name);
				}
			}
		}
	}
	if ($submit == "Create Directory") {
		if (isset($make_dir)) {
			$make_dir = trim(stripslashes($make_dir));
			while(strlen($make_dir) > 0 && $make_dir[0] == '/')
				$make_dir = substr($make_dir, 1);
			while(strlen($make_dir) > 0 && $make_dir[strlen($make_dir)-1] == '/')
				$make_dir = substr($make_dir, 0, -1);
			if (strlen($make_dir) <= 0 || $make_dir[0] == '.' || $make_dir == "cache" || strpos('/', $make_dir)) {
				$update_text .= "<p>Invalid directory name <b>\"$make_dir\"</b></p>\n";
			} else {
				if (!is_dir($make_dir)) {
					//check $make_dir for validity
					$start_dir = getcwd();
					chdir(getcwd()."/$dir");
					mkdir($make_dir, 0775);
					chdir($start_dir);
				} else
					$update_text .= "<p>Directory already exists</p>\n";
			}
		} else
			$update_text .= "<p>No directory name entered</p>\n";
	}
}

function admin_upload_page() 
{
	global $dir, $page;
	
	admin_dir_explore(	$dir, 
						$page, 
						'', 
						false,
						true,
						true,
						false);
							
	//main table, holds left & right
	echo 	"<table style=\"width: 100%\" cellspacing=\"6px\">\n".
			"  <tr>\n".
		 	"    <td>\n";
	//left table holds file selection boxes
	echo 	"<form enctype=\"multipart/form-data\" method=\"post\" action=\"index.php\">\n".
			"  <input type=\"hidden\" name=\"MAX_FILE_SIZE\" value=\"2000000\" />\n".
		 	"  <input type=\"hidden\" name=\"action\" value=\"admin\" />\n".
		 	"  <input type=\"hidden\" name=\"page\" value=\"$page\" />\n".
		 	"  <input type=\"hidden\" name=\"dir\" value=\"$dir\" />\n".
		 	"  <input type=\"hidden\" name=\"update\" value=\"$page\" />\n";
		 	
		 	
	echo 	"<table style=\"width: 50%\" cellspacing=\"6px\">\n".
			"  <tr>\n".
		 	"    <td>\n".
		 	"      <p>Photos(s) to Upload: (jpeg, png, gif)<br />or archives (zip)</p>\n".
		 	"    </td>\n";
	for ($i=0; $i<10; $i++) {	 
		echo "  <tr>\n".
			 "    <td>\n".
			 "      <input type=\"file\" name=\"userfile[]\" size=\"50\" />\n".
			 "    </td>\n".
			 "  </tr>\n";
	}
	echo 	"  <tr>\n".
			"    <td>\n".
			"      <input type=\"submit\" name=\"submit\" value=\"Upload File(s)\" />\n".
			"    </td>\n".
			"  </tr>\n".
		 	"</table>\n".
		 	"    </td>\n".
	//end of left table, start of right table for making new dirs
			"    <td>\n".
			"      </form>\n";
	echo 	"<form method=\"post\" action=\"index.php\">\n".
		 	"  <input type=\"hidden\" name=\"action\" value=\"admin\" />\n".
		 	"  <input type=\"hidden\" name=\"page\" value=\"$page\" />\n".
		 	"  <input type=\"hidden\" name=\"dir\" value=\"$dir\" />\n".
		 	"  <input type=\"hidden\" name=\"update\" value=\"$page\" />\n".
		 	"<table style=\"width: 50%\" cellspacing=\"6px\">\n".
			"  <tr>\n".
		 	"    <td>\n".
		 	"      <p>Directory name:</p>\n".
			"    </td>\n".
		 	"    <td>\n".
		 	"      <input type=\"text\" name=\"make_dir\" size=\"20\" maxlength=\"30\" />\n".
			"    </td>\n".
			"    <td>\n".
			"      <input type=\"submit\" name=\"submit\" value=\"Create Directory\" />\n".
			"    </td>\n".
			"  </tr>\n".
		 	"</table>\n".
			"      </form>\n".
	//end of right table
			"    </td>\n".
			"  </tr>\n".
			"</table>\n".
		 	"<p>It may take time to upload photos, please only press the upload button <b>once</b></p>\n".
		 	"<p>Upload lots of files by uploading a zip archive (up to 2MB at a time), if you want to do large numbers, upload via ftp.</p>\n";
}

//removes slashes from array key values
function cleanup_keys($arr) {
	if (!is_array($arr))
		return $arr;

	$temp = array();
	foreach ($arr as $key=>$arr) {
		$key = trim(stripslashes($key), "'");
		$temp[$key] = cleanup_keys($arr);
	}
	return $temp;
}

function admin_config_update() {
	global $pg;
	
	if (isset($_POST['pg']['owner']['message'])) {

	}

	$set = cleanup_keys($_POST['pg']);
	
	$file_contents = '';
	if ($fh = @fopen("photoGal.cfg.php", "r")) {
		while ($buffer = fgets($fh, 4096)) {
		
			preg_match("/pg([^ =]*)[ =]/",$buffer,$regs);
			$key = $regs[1];
			if ($key == '') {
				$file_contents .= $buffer;
				continue;
			}
			preg_match_all("/\['([^']*)'\]/", $key, $keys);
			$keys = $keys[1];
			$new_val = $set;
			//pain in the arse way of accessing an array variable
			foreach($keys as $k) {
				$new_val = $new_val[$k];
			}
			if ($new_val == '') {
				$file_contents .= $buffer;
				continue;
			}
			
			if ($key == "['owner']['message']") {
				$new_val = str_replace(array("\n", "\r"), array("<br />", ""), $new_val);
				$new_val = stripslashes($new_val);
				$new_val = str_replace('"', '\"', $new_val);
			}
			
			if ($key == "['admin']['thumb_size']") {
				if ($pg['admin']['thumb_size'] != $new_val)
				  $set['admin']['thumb_size_updated'] = time();
			}
			if ($key == "['css']['body']['background-image']") {
				if (substr($new_val, 0, 4) != "url(")
					$new_val = "url(\\\"$new_val\\\")";
			}
			
			$comment = substr($buffer, strrpos($buffer, ';')+1);
			
			//success, update this value
			if (!is_numeric($new_val))
				$buffer = '$pg'.$key." = \"".trim($new_val)."\";";
			else
				$buffer = '$pg'.$key." = ".trim($new_val).";";
			
			$buffer .= $comment;	
			$file_contents .= $buffer;
		}
		fclose($fh);
		if ($fh = @fopen("photoGal.cfg.php", "w")) {
			//write back modified contents
			fwrite($fh, $file_contents);
			fclose($fh);
		}
	}
	//update the local environment with new config values
	include "photoGal.cfg.php";
}

function admin_modify_update()
{
	global $dir, $resizing, $custom_photo_size, $submit, $rotate_angle, $photo_size, $update_text;

	if ($custom_photo_size != '') {
		if (!is_numeric($custom_photo_size)) {
			$update_text .= "<p>Custom size must be an integer, resizing failed</p>\n";
			$resizing = 0;
		} else {
			if ($custom_photo_size < 200 || $custom_photo_size > 2048) {
				$update_text .= "<p>Custom size must be between 200 and 2048, resizing failed</p>\n";
				$resizing = 0;
			} else
				$photo_size = $custom_photo_size;
		}
	}
	
	if (isset($resizing) && $resizing == 1) {
		if (isset($_POST['ck_photo'])) 
		{
			foreach($_POST['ck_photo'] as $key=>$name) 
			{
				$images[] = $key;
			}
		}
		if (isset($images)) {
			foreach ($images as $val) {
				if (file_exists("./$dir/$val")) {
					if ($submit == "Resize Photo(s)") 
					{
						resize_photo ("./$dir/$val", "./$dir/$val", $photo_size);
						$update_text .= $val."<br />";
						flush();
					}
					if ($submit == "Rotate Photo(s)") 
					{
						/* MAKE A ROTATE FUNCTION THAT WORKS WITH GD
						*/
						exec("convert -rotate $rotate_angle \"./$dir/$val\" \"./$dir/$val\"");
						$update_text .= $val."<br />";
					}
					generate_thumb($dir, $val, true);
				}
				//change to public readable
				chmod ("./$dir/$val", 0664);
			}
			if ($submit == "Resize Photo(s)")
				$update_text .= "<p>Resize(s) complete</p>\n";
			if ($submit == "Rotate Photo(s)") 
			{
				$update_text .= 	"<p>Rotation(s) complete - thumbnails may not appear updated,".
						"click <a href=\"index.php?action=admin&page=$page&dir=$dir\">here</a> to refresh this page".
						" without resubmitting form data</p>\n";
			}
		} else {
			$update_text .= "<p>No images selected</p>\n";
		}
	}
}

function admin_modify_page() {
	global $dir, $page, $pg;
		
	$form =	"	<input type=\"hidden\" name=\"resizing\" value=\"1\" />\n".
			"	<input type=\"hidden\" name=\"update_conf\" value=\"1\" />\n".
			"<p>Photo size: \n".
			"<select name=\"photo_size\">".
			"	<option value=\"512\"".($pg['admin']['photo_size'] == 512 ? "selected=\"selected\"" : "").">512 x 384 (very small)</option>".
			"	<option value=\"640\"".($pg['admin']['photo_size'] == 640 ? "selected=\"selected\"" : "").">640 x 480 (small)</option>".
			"	<option value=\"800\"".($pg['admin']['photo_size'] == 800 ? "selected=\"selected\"" : "").">800 x 600 (normal)</option>".
			"	<option value=\"1024\"".($pg['admin']['photo_size'] == 1024 ? "selected=\"selected\"" : "").">1024 x 768 (large)</option>".
			"</select>".
			" Custom size: \n".
			"<input type=\"text\" name=\"custom_photo_size\" value=\"\" size=\"4\" maxlength=\"4\" /> (longer side only)\n".
			"<input type=\"submit\" name=\"submit\" value=\"Resize Photo(s)\" /></p>\n".
			"<p>Rotate: \n".
			"<select name=\"rotate_angle\">".
			"	<option value=\"90\">90 degrees clockwise</option>".
			"	<option value=\"-90\">90 degrees anti-clockwise</option>".
			"	<option value=\"180\">180 degrees</option>".
			"</select>".
			"<input type=\"submit\" name=\"submit\" value=\"Rotate Photo(s)\" /><br />\n".
			"<input type=\"button\" style=\"width: 120px\" value=\"Select All\" onClick=\"select_all(this)\" />";

	admin_dir_explore(	$dir, 
						$page, 
						$form, 
						true, 
						true, 
						false, 
						true);
			
	echo "<p>It may take time for the images to be resized.</p>\n";
}

function admin_delete_update()
{
	global $dir, $update_text, $deleting;
	
	if (isset($deleting) && $deleting == 1) {
		$start_dir = getcwd()."/$dir/";
		if (isset($_POST['ck_photo'])) {	
			$foo = $_POST['ck_photo'];
			foreach($foo as $key=>$name) {
				$images[] = $key;
			}
		}
		if (isset($images)) {
			$update_text .= "<b>Images deleted:</b>\n";
			foreach ($images as $val) {
				unlink($start_dir.$val);
				$update_text .= "<br />$val\n";
			}
		}
	}
}

function admin_delete_page() 
{
	global $dir, $update_text, $page;
	
	$form = "	<input type=\"hidden\" name=\"deleting\" value=\"1\" />\n".
			"	<input type=\"submit\" name=\"submit\" value=\"Delete Photo(s)\" /><br />\n".
			"	<input type=\"hidden\" name=\"udpdate\" value=\"admin_delete\" /><br />\n".
			"	<input type=\"button\" style=\"width: 120px\" value=\"Select All\" onClick=\"javascript:select_all(this)\" />\n";
	
	admin_dir_explore(	$dir, 
						$page, 
						$form, 
						true, 
						true, 
						true);
}

function admin_album_comments_update()
{
	global $dir, $album_comment, $submit;
	if ($submit != "Remove comment" && isset($album_comment)) {
		if (!file_exists("cache/$dir")) {
			mkdir("cache/$dir", 0775);
		}
		if ($fh = @fopen("cache/$dir/album.nfo", "w")) { 
			//need to change this such that it can create cache if necessary
			//remove the html special chars...
			$album_comment = stripslashes(str_replace(array("&gt;", "&lt;", "&quot;", "&amp;"), array(">", "<", "\"", "&"), $album_comment));
			//write back modified contents
			fwrite($fh, $album_comment);
			fclose($fh);
		}
	} else {
		if ($submit == "Remove comment") {
			@unlink("cache/$dir/album.nfo");
		}
	}
}

function admin_album_comments_page() {
	global $dir, $page;
	
	if (@file_exists("cache/$dir/album.nfo")) {
		if ($fh = @fopen("cache/$dir/album.nfo", r)) {
			$comment = fread($fh, filesize("cache/$dir/album.nfo"));
			echo "Album comment: <b>$comment</b>\n";
		}
	} else {
		echo "This album is currently uncommented";
	}
		
	$form = "	<input type=\"text\" name=\"album_comment\" value=\"".htmlspecialchars("$comment")."\" size=\"100\" /><br />\n".
			(isset($comment) ? 	"<input type=\"submit\" name=\"submit\" value=\"Edit comment\" /><br />\n".
								"<input type=\"submit\" name=\"submit\" value=\"Remove comment\" /><br />\n"
							 :	"<input type=\"submit\" name=\"submit\" value=\"Add comment\" /><br />\n");
								
	admin_dir_explore($dir, $page, $form, false, true);
}

/*
 *	Prints a navigable view of $dir for the modify, upload, delete, album comment pages
 */
function admin_dir_explore(	$dir, 
							$page, 
							$form_buttons, 
							$checkboxes = true, 
							$show_photos = false, 
							$show_movies = false, 
							$dimensions = false)
{
	global $pg;
	
	$linkpart = "<a href=\"index.php?action=admin&page=$page&dir=";
	$dirLinks = get_dir_links($linkpart, $dir);
	echo "<p>Current directory: <b>".$dirLinks."</b></p>\n";
	
	echo "<form action=\"index.php\" method=\"post\">\n".
		 "	<input type=\"hidden\" name=\"action\" value=\"admin\" />\n".
		 "	<input type=\"hidden\" name=\"page\" value=\"$page\" />\n".
		 "	<input type=\"hidden\" name=\"update\" value=\"$page\" />\n".
		 "	<input type=\"hidden\" name=\"dir\" value=\"$dir\" />\n";
	
	list ($photos, $dirs, $movies) = listDir($dir);
	
	echo "<table class=\"a_explore\" cellspacing=\"10px\">\n";

	$col = 0;
	$max_cols = $pg['main']['columns'] - 1;
	if ($max_cols == 0)
		$max_cols = 1;
	$width = round(100 / $max_cols);
	
	if (isset($dirs)) 
	{
		foreach ($dirs as $file) 
		{
			if ($col >= $max_cols) 
			{
				echo "    </tr>\n";
				$col = 0;
			}
			if ($col == 0)
				echo "    <tr>\n";

			$temp = stripslashes("$dir/$file");
			echo "      <td style=\"width: $width%\">\n".
				 "        <a href=\"index.php?action=admin&page=$page&dir=$temp\">".
				 "$file</a>\n".
				 "      </td>\n";
			$col++;
		}
	}
	
	if ($show_photos && isset($photos))
	{
		foreach ($photos as $file) 
		{
			if ($col >= $max_cols) 
			{
				echo "    </tr>\n";
				$col = 0;
			}
			if ($col == 0)
				echo "    <tr>\n";
			
			echo "      <td style=\"width: $width%\">\n";	

			echo image_holder($dir, $file, '').
				 "        <br />\n";
			if ($checkboxes)
			{
							echo "		  <input type=\"checkbox\" name=\"ck_photo[".$file."]\" />\n<br />\n";
			}
			echo $file;
				
			if ($dimensions) {
				$dims = getImageSize("./$dir/$file");
				echo 	"<br />\n".
						"Size: $dims[0]x$dims[1]</div>\n";
			}
			echo "      </td>\n";
			$col++;
			//create thumbnail in ./cache/$file.thumb
			generate_thumb($dir, $file);
		}
	}
	
	if ($show_movies && isset($movies)) 
	{
		foreach ($movies as $file) 
		{
			if ($col >= $max_cols) 
			{
				echo "    </tr>\n";
				$col = 0;
			}
			if ($col == 0)
				echo "    <tr>\n";

			echo "      <td style=\"width:$width%\">\n";
			$url = "emb_img.php?action=embimage&img=film";
			echo "<img src=\"$url\" alt=\"$url\"/><br />\n";
			if ($checkboxes)
			{
							echo "		  <input type=\"checkbox\" name=\"ck_photo[".$file."]\" />\n<br />\n";
			}
			echo "$file</a>\n";
			echo "      </td>\n";
			$col++;
		}
	}	
	echo "	</tr>\n".
		 "</table>\n";
		 
	echo $form_buttons;
	echo "</form>\n";
}

function make_admin_config_table ($vars, $page = '') {
	global $pg;
	
	if ($page == '')
		global $page;
	
	echo 	"<form method=\"post\" action=\"index.php\">\n".
			"	<input type=\"hidden\" name=\"action\" value=\"admin\" />\n".
			"	<input type=\"hidden\" name=\"page\" value=\"$page\" />\n".
			"	<input type=\"hidden\" name=\"update\" value=\"admin_config\" />\n";
			
	conf_table_start();
	
	foreach ($vars as $row) {
		
		preg_match_all("/\['([^']*)'\]/", $row->name, $keys);
		$keys = $keys[1];
		$value = $pg;
		foreach($keys as $k) {
			$value = $value[$k];
		}
		if (is_array($value))
			$value = "";
		
		$right = "";
		
		switch ($row->type) {
			case "password":
			case "text":
				$value = htmlentities($value);
				$right = "<input type=\"{$row->type}\" name=\"pg".$row->name."\" value=\"$value\" size=\"20\" />";
				break;
			case "textarea":
				$value = str_replace("<br />", "\n", $value);
				$right = "<textarea cols=\"50\" rows=\"6\" name=\"pg".$row->name."\">$value</textarea>";
				break;
			case "select":
				$right .= "<select name=\"pg".$row->name."\">\n";
				foreach ($row->possibles as $val=>$label) {
					$checked = ($val == $value ? 'selected="selected"' : '');
					$right .= "<option value=\"".$val."\" $checked />$label</option>\n";
				}
				$right .= "</select>";
				break;
			case "radio":
				foreach ($row->possibles as $val=>$label) {
					$checked = ($val == $value ? 'checked="checked"' : '');
					$right .= "<input type=\"radio\" name=\"pg".$row->name."\" value=\"".$val."\" $checked /> $label \n";
				}
				break;
			case "plus_minus":
				$right .= "<input type=\"button\" style=\"width: 20px\" value=\"-\" onclick=\"plus('".addslashes($row->name)."', -{$row->possibles['plus']}, {$row->possibles['min']}, {$row->possibles['max']})\" />\n";
				$right .= "<input type=\"text\" size=\"4\" name=\"pg".$row->name."\" value=\"$value\" id=\"".$row->name."\" readonly=\"readonly\" />\n";
				$right .= "<input type=\"button\" style=\"width: 20px\" value=\"+\" onclick=\"plus('".addslashes($row->name)."', {$row->possibles['plus']}, {$row->possibles['min']}, {$row->possibles['max']})\" />\n";
				break;
		}
		conf_table_row($row->text, $right);
	}
	
	conf_table_end();
	echo	"	<input type=\"submit\" value=\"Set Changes\" />\n".
			"</form>";
}

//used for constructing the configuration tables
class pg_setting {
	var $name;	//variable name
	var $value;	//variable value
	var $type;	//input type (text, textare, checkbox, select)
	var $text;	//text to display next to input control
	var $possibles; //multiple options for selects and radios
	
	function pg_setting ($k = "", $t = "", $m = "", $p = array(), $v = "") {
		$this->name = $k;
		$this->value = $v;
		$this->type = $t;
		$this->text = $m;
		$this->possibles = $p;
	}
}

function conf_table_start() {
	echo "  <table class=\"a_config\">\n";
}

function conf_table_end() {
	echo "	</table>\n";
}

function conf_table_row($left, $right) {
	echo	"    <tr>\n".
			"      <td class=\"blue\">\n".
			"        $left\n".
			"      </td>\n".
			"      <td>\n".
			"        $right\n".
			"      </td>\n".
			"    </tr>\n";
}
?>