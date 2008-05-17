<?php
/*
 * Pretty Helpful Image Sorting Hierarchy
 *
 * Copyright (C) 2002 Kyle Maddison
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 *
 * Contact me at km329@srcf.ucam.org
 *
 * This program comes with ABSOLUTELY NO WARRANTY. It should not cause
 * any harm to anything when used properly. For details on how to use
 * it properly, write the docs.
 *
 * To install you need the files index.php, photoGal.cfg.php, main.css
 * (+ main.inc.php, util.inc.php, admin.inc.php says ned21)
 * Put them in a directory e.g. public_html/photo/ and you will be able
 * view photos in directories with /photo/ (including s-linked directories)
 * 
 * $Id$ 
 * 
 */
$start_time = getmicrotime();

if (isset($_GET['pic'])) {
   $pic = $_GET['pic'];
} elseif (isset($_POST['pic'])) {
   $pic = $_POST['pic'];
}

$dir = $_GET['dir'];
$page = $_GET['page'];
$comment = $_POST['comment'];

if (isset($_GET['action'])) {
   $action = $_GET['action'];
} elseif (isset($_POST['action'])) {
   $action = $_POST['action'];
}

if (isset($_GET['login_action'])) {
   $login_action = $_GET['login_action'];
} elseif (isset($_POST['login_action'])) {
   $login_action = $_POST['login_action'];
}

if (isset($_POST['username'])) {
   $username = $_POST['username'];
   $password = $_POST['password'];
}

if (isset($_COOKIE['login'])) {
   $login = $_COOKIE['login'];
}


//prevent illegal file names
//and clean up the filenames
$pic = cleanup($pic);
$dir = cleanup($dir);

include "util.inc.php";
include "photoGal.cfg.php";

if (!is_dir($dir) || $dir == '.') {
	$dir = '';
}
if ($pic != '') {
	if (isset($pic)) {
		//$foo = strrpos($pic, '/' );
		//if ($foo === false) {
			//ie no / in $pic, must be in root
		//	$dir = '';
		//} else {
			if (!file_exists(getcwd()."/$pic") || is_dir(getcwd()."/$pic"))
			{
				echo "foo";
				unset($pic);
				if (!is_dir(getcwd()."/$dir"))
					unset($dir);
			}
			else
			{
				$dir = dirname($pic);
				$pic = basename($pic);
			}
		//}
	}
}

switch ($action) {
	//need to be done before any html content sent
	case "tar_dir":			tar_dir($dir);
							break;
	case "set_user_prefs":	set_user_cookie();
							break;
	case "add_comment":		new_comment($comment, $dir, $pic);
							header("Location: http://".$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF']."?pic=$dir/$pic");
							//script exits here
							break;							
}

if ($action == "admin") {
	$auth = login();
	
	if ($auth && $admin == "exit") {
		logout();
		$action = "";
		$auth = false;
		main_start();
	}
	if (isset($update) && $update == 1) {
		include "admin.inc.php";
		update_config();
	}
} else {
	main_start();
}


echo    "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.1//EN\" \"http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd\">\n".
		"<html>\n".
		"<head>\n".
		"	<meta name=\"keywords\" content=\"photos, html, xhtml, php, gallery\" />\n".
		"	<meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\" />\n".
		"	<script src=\"script.js\"></script>\n".
		"	<link rel=\"stylesheet\" type=\"text/css\" href=\"main.css\" />\n";

//auto exit admin after 10 minutes of inactivity
if ($action == "admin") {
	echo	"	<meta http-equiv=\"Refresh\" content=\"610; url=index.php\" />\n";
}

//slideshow		
if ($action == "slideshow") {
	echo	"	<style type=\"text/css\">\n".
			"   <!--".
			"	body {\n".
			"   	background: #000;\n".
			"   }\n".
			"   -->\n".
			"   </style>\n";
	echo	"<script type=\"text/javascript\"><!--\n".
			"var my_images = new Array();\n";
	for ($i=0; $i<sizeOf($pg['photos']); $i++) {
		echo "my_images[$i] = '".addslashes($pg['photos'][$i])."';\n";
	}
	echo 	"var max_image = $i;\n";
	echo	"--></script>\n";
} else {
	if ($action == '') {
		//only if we are browsing
		//top links for opera and other new browsers		
		echo	"    <link rel=\"start\" href=\"index.php\" />\n".
				"    <link rel=\"author\" href=\"index.php?action=about\" />\n";
		if ($pic != '')
		{
			echo "    <link rel=\"next\" href=\"index.php?dir=$dir&pic_index=".($pic_index+1)."\" />\n".
				 "    <link rel=\"previous\" href=\"index.php?dir=$dir&pic_index=".($pic_index-1)."\" />\n";
			echo "    <link rel=\"up\" href=\"index.php?dir=$dir\" />\n";
		}
		elseif ($dir != '')
			//echo "    <link rel=\"up\" href=\"index.php?dir=".substr($dir, 0, strrpos($dir, '/')+1)."\" />\n";
			echo "    <link rel=\"up\" href=\"index.php?dir=".dirname($dir)."\" />\n";
	}
	if ($action != 'admin') {
		//custom styles overriding css defaults from photoGal.cfg.php			
		echo "	<style type=\"text/css\">\n";
		foreach ($pg['css'] as $style=>$keys) {
			if ($style[0] == ".") //special to make thumbnail css work
				$style = "table.picTable tr td$style";
			echo "	$style {\n";
			foreach ($keys as $key=>$val)
				echo "		$key: $val;\n";
			echo "	}\n";
		}
		echo "	</style>\n";
	}
}
echo	"	<title>{$pg[owner][name]}'s photo gallery - $dir</title>\n".
		"</head>\n";
if ($action == 'slideshow')
	echo "<body onload=\"slideshow_start('".addslashes($dir)."', ".$pg['main']['slideshow_time'].");\">\n";
else
	echo "<body>\n";

switch ($action) {
	case "admin" :			include_once "admin.inc.php";
							if (!$auth) {
								admin_login();
							} else {
								admin($page);
							}
							break;
	case "colour": 			if (!isset($light))
								$light = 1;
							include "colour.php";	
							colourTable($light, $colour);
							break;
	case "view_comments":	show_all_comments();
							break;
	case "options":			global_user_prefs();
							options();
							break;
	case "about":			about_page();
							break;
	case "slideshow":		slideshow($dir);
							break;
	default:				global_user_prefs();
							main($pic, $dir);
							break;
}
echo "<!--Script executed in ".round(getmicrotime() - $start_time, 4)." seconds-->\n";
echo "</body>\n";
echo "</html>\n";
echo "<!-- ";
echo "\nCOOKIE\n";
show_array($_COOKIE);
echo "\nGET\n";
show_array($_GET);
echo "\nPOST\n";
show_array($_POST);
echo " -->\n";
# $action = $_GET['action'];

function getmicrotime()
{ 
	list($usec, $sec) = explode(" ",microtime()); 
	return ((float)$usec + (float)$sec); 
}
   
function main_start() {
	include "main.inc.php";
	global $pg;
	list ($pg['photos'], $pg['subDirs'], $pg['movies']) = listDir($dir);
}

function cleanup($string) {
	$string = str_replace("..", "", $string);
	$string = stripslashes($string);
	$string = trim($string, '/');
	return $string;
}

function admin_login() { ?>
	<h3 class="a_config">Please enter administrator username and password</h3>
	<form action="index.php" method="post" class="text_center" style="padding: 0; margin-top: 300px;">
		<input type="hidden" name="action" value="admin" />
		<input type="hidden" name="login_action" value="enter" />
		<table class="a_config" style="width: 50%">
			<tr>
				<td class="blue">
					Username
				</td>
				<td>
					<input type="text" size="30" name="username" value="" />
				</td>
			</tr>
			<tr>
				<td class="blue">
					Password
				</td>
				<td>
					<input type="password" size="30" name="password" value="" />
				</td>
			</tr>
		</table>
		<input type="submit" value="Login" />
	</form> <?
}

function login() {
	global $login, $username, $password, $admin;
	$auth = false;
	
	if (!file_exists("user.inc.php")) {
		global $page;
		if ($page != "admin_new_password")
			$page = "admin_change_password";
		return true;
	}
	$valid = false;
	if (isset($login["user"]) && isset($login["pass"])) {
		//using cookie
		$user = $login["user"];
		$pass = $login["pass"];
		$auth = authenticate_md5($user, $pass);
		if ($auth && $admin != "exit") {
			refresh_cookies($user, $pass);
			$valid = true;
		} else { //incorrect cookie information
			$valid = false;
		}
	}
	if (!$valid) {
		//cookie not set, tyring to log in
		if (isset($username) && isset($password)) {
			if ($auth = authenticate($username, $password)) {
				refresh_cookies($username, md5($password));
			} else {
				return $auth;
			}
		} else { //not set, must be trying to get to login screen
		}
	}
	unset ($pg['admin']['user']);
	unset ($pg['admin']['password']);
	return $auth;
}

function authenticate($usr, $pass) {
	return authenticate_md5($usr, md5($pass));
}

function authenticate_md5($usr, $pass) {
	include "user.inc.php";
	$auth = ($pg['admin']['user'] == $usr && $pg['admin']['password'] == $pass);
	return $auth;
}

function refresh_cookies($username, $password) {
	setcookie("login[user]",$username, time() + 600, "", $_SERVER['HTTP_HOST']);
	setcookie("login[pass]",$password, time() + 600, "", $_SERVER['HTTP_HOST']);
}

function logout() {
	setcookie("login[user]","", time() - 36000, "", $_SERVER['HTTP_HOST']);
	setcookie("login[pass]","", time() - 36000, "", $_SERVER['HTTP_HOST']);
}


function show_array($array) {
    foreach ($array as $value) {
        if (is_array($value)) {
            show_array($value);
        } else {
            echo $value . "<br>";
        }
    }
} 


?>
