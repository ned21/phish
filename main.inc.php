<?php

function main($pic, $dir) {
	global $pg;
	//include custom page top here		
	if ($pg['admin']['header'] != '' && file_exists($pg['admin']['header']) && !isset($pic)) 		
		include $pg['admin']['header'];
	
	$total = sizeOf($pg['photos']) + sizeOf($pg['subDirs']) + sizeOf($pg['movies']);
	//gets the number of columns of thumbnails correct
	if ($pg['main']['columns'] > $total) {
		$maxCols = $total;
	} else {
		$maxCols = $pg['main']['columns'];
	}
	if ($maxCols == 0)
		$maxCols = 1;
		
	//the wonders of binary arithmetic
	$style = $pg['main']['list_with_photo'] + 2 * $pg['main']['photo_with_table'];
	
	switch ($style) {
		case 0: //pic without table, no photo listings
				if ($pic != '') {
					showPic($pic);
				} else {
					tableHead($dir, $maxCols);
					listout($dir, $maxCols);
					tableEnd();
				}
				break;
		case 1: //pic without table bg, photo listings at bottom
				if ($pic != '')
					showPic($pic);
				tableHead($dir, $maxCols);
				listout($dir, $maxCols);
				tableEnd();
				break;
		case 2: //pic with table bg, no photo listings
				tableHead($dir, $maxCols);
				if ($pic != '') {
					echo "	<tr>\n".
						 "		<td colspan=\"$maxCols\">\n";	
					showPic($pic);
					echo "		</td>\n".
						 "	</tr>\n";
				} else {
					listout($dir, $maxCols);
				}
				tableEnd();
				break;
		case 3:
		default: //pic with table bg, photo listings at bottom
				tableHead($dir, $maxCols);
				if ($pic != '') {
					echo "	<tr>\n".
						 "		<td colspan=\"$maxCols\">\n";	
					showPic($pic);
					echo "		</td>\n".
						 "	</tr>\n";
				}
				listout($dir, $maxCols);
				tableEnd();
				break;
	}
		
	tableEnd();
	
	if ($dir == '') 
	{
		echo 	"  <table style=\"width: 80%\" class=\"picTable\" cellspacing=\"5px\">\n".		
				"    <tr>\n".
				"      <td colspan=\"$maxCols\" class=\"head\">\n".
				"        <p>{$pg['owner']['name']}'s photo gallery</p>\n<p>{$pg['owner']['message']}</p>".
				"      </td>\n".
				"      <td>\n".
			 	"			<ul class=\"main_menu\">\n".
				"           	<li><a href=\"index.php?action=options\">Change my options</a></li>\n".
				"              	<li><a href=\"index.php?action=view_comments\">View all comments</a></li>\n".
				"              	<li><a href=\"index.php?action=admin\">Administrate site</a></li>\n".
				"              	<li><a href=\"index.php?action=about\">About phish</a></li>\n".
				"			</ul>\n".
				"      </td>\n".
				"    </tr>\n".
				"  </table>\n";
	}
	
	//include custom page foot here	
	if ($pg['admin']['footer'] != '' && file_exists($pg['admin']['footer'])) 		
		include $pg['admin']['footer'];
}

function tableHead($dir, $maxCols) {
	global $pg;
	
	if (!isset($pg['admin']['main_width'])) {
		$pg['admin']['main_width'] = 90;
	}
	
	//get the directory links out of the directory name
	$dirLinks = get_dir_links("<a href=\"index.php?dir=", $dir);
	
	echo "  <table style=\"width: {$pg['admin']['main_width']}%\" cellspacing=\"10px\" class=\"picTable\">\n".
		 "    <tr class=\"head\">\n".
		 "      <td>\n".
		 "        Gallery:\n".
		 "      </td>\n".
		 "      <td colspan=\"".($maxCols-1)."\">\n".
		 "        $dirLinks\n".
		 "      </td>\n".
		 "    </tr>\n";
}

function tableEnd() {	
	echo "  </table>\n";
}

//directory listing
function listout($dir, $maxCols) {
	//grab the global preferences
	global 	$pg, $pic, $pic_index;
	
	//Add in album comment
	if (file_exists("cache/$dir/album.nfo")) {
		echo	"    <tr class=\"head\">\n".
				"      <td colspan=\"$maxCols\">\n";
		if ($fh = fopen("cache/$dir/album.nfo", r)) {
			fpassthru($fh);
		}
		echo	"      </td>\n".
		 		"    </tr>\n";
	}

	$width = round(100/($maxCols+1));
	$col = 0;
	
	if (isset($pg['subDirs'])) {
		foreach ($pg['subDirs'] as $file) {
			//allows $maxCols columns in the table
			if ($col >= $maxCols) {
				echo "    </tr>\n";
				$col = 0;
			}
			if ($col == 0)
				echo "    <tr>\n";

			$temp = stripslashes("$dir/$file");
			echo "      <td class=\"thumbdir\" style=\"width: $width%\">\n".
				 "        <a href=\"index.php?dir=$temp\">";
			if ($pg['main']['dirphotos']) {
				$img_text = randomImage($dir, $file);
				if ($img_text == $file)
					echo "<img src=\"_phish/book.png\" alt=\"folder\" style=\"border: none\" /><br />\n$file";
				else
					echo $img_text;
			} else {
				echo "<img src=\"_phish/book.png\" alt=\"folder\" style=\"border: none\" /><br />\n$file";
			}
			echo "</a>\n".
				 "      </td>\n";
			$col++;
		}
	}

	if (isset($pg['photos'])) {
		$num = 0;
		foreach ($pg['photos'] as $file) {
			if ($col >= $maxCols) {
				echo "    </tr>\n";
				$col = 0;
			}
			if ($col == 0)
				echo "    <tr>\n";
			
			if ($pg['main']['layout'] == 'flat') {
				echo "      <td colspan=\"$maxCols\" align=\"center\">\n";
				echo "	        <img src=\"$dir/$file\" alt=\"$dir/$file\" />\n";
				echo "      </td>\n";
			} else {
				//need to check here if thumbnail exists, otherwise create, and create link
				if ($file == $pic)
					echo "      <td class=\"curthumb\" style=\"width:$width%\">\n";
				else
					echo "      <td class=\"thumb\" style=\"width:$width%\">\n";	

				echo "        <a href=\"index.php?pic=$dir/$file&pic_index=$num\">";
				echo image_holder($dir, $file, ($pg['main']['show_file_names'] ? $file : ''));
				echo "</a>\n";

				if ($pg['main']['dimensions']) {
					$dims = getImageSize("./$dir/$file");
					echo 	"<br />\n".
							"<div class=\"small\">Size: $dims[0]x$dims[1]</div>\n";
				}
				echo "      </td>\n";
				$col++;
			}
			//create thumbnail in ./cache/$file.thumb
			generate_thumb($dir, $file);
			$num++;
		}
	}

	if (isset($pg['movies'])) {
		foreach ($pg['movies'] as $file) {
			if ($col >= $maxCols) {
				echo "    </tr>\n";
				$col = 0;
			}
			if ($col == 0)
				echo "    <tr>\n";

			echo "      <td class=\"thumb\" style=\"width:$width%\">\n";	
			echo "        <a href=\"$dir/$file\">";
			$url = "emb_img.php?action=embimage&img=film";
			echo "<img src=\"$url\" alt=\"$url\"/><br />\n".
				 "$file</a>\n";
			echo "      </td>\n";
			$col++;
		}
	}

	if (sizeOf($pg['photos']) > 0) {
		//if there are images in this directory, allow it to be tarred and slideshowed
		echo 	"    </tr>\n";
		echo	"    <tr class=\"head\">\n".
				"      <td>\n".
				"        ".sizeOf($pg['photos'])." photos\n".
				"      </td>\n".
				"      <td colspan=\"".($maxCols-1)."\">\n".
				"        <a href=\"index.php?action=slideshow&amp;dir=$dir\" style=\"margin: 0; padding-right: 7px; \">Slideshow</a>\n".
//				"        <a href=\"index.php?action=tar_dir&amp;dir=$dir\" style=\"margin: 0; padding-right: 4px; padding-left: 3px\">Download these photos</a>\n".
				"      </td>\n".
				"    </tr>\n";
	}
}

function get_ss_pic($ss_index, $dir) {
	//$ss_index is the CURRENT showing picture's index in the dir
	global $ss_index, $pg;
	
	if (!isset($ss_index))
		$ss_index = 0;
		
	//wrap around ends of slideshow
	if ($ss_index >= sizeOf($pg['photos']))
		$ss_index = 0;
	if ($ss_index == -1)
		$ss_index = sizeOf($pg['photos'])-1;
		
	return $pg['photos'][$ss_index];
}

function slideshow($dir) {
	global $pg, $pic_index;
	if ($dir == '') {
		$tdir = $pg['owner']['name'];
	} else {
		$tdir = $dir;
	}
	echo "<form style=\"text-align: right; padding: 2px;\">\n";
	echo "	<div class=\"slideshow_menu\">\n".
		 "		<a href=\"index.php?dir=$dir\">".str_replace('/', ' - ', $tdir)."</a>\n".
		 "		<a href=\"javascript:slideshow_click_next()\">Next</a>\n".
		 "		<a href=\"javascript:slideshow_prev()\">Prev</a>\n";
	echo "		<input type=\"hidden\" name=\"ss_index\" value=\"$ss_index\" />\n".
		 "		<input type=\"hidden\" name=\"action\" value=\"slideshow\" />\n".
		 "		<input type=\"hidden\" name=\"dir\" value=\"$dir\" />\n".
		 "		<select id=\"ss_delay\" onchange=\"set_timeout(this.value)\">\n";
	echo "			<option value=\"0\">Pause</option>\n";
	for ($i=2; $i<15; $i+=1) {
		$checked = ($i == $pg['main']['slideshow_time'] ? "selected=\"selected\"" : "");
		echo "			<option value=\"$i\" $checked>$i"."s delay</option>\n";
	}
	echo "		</select><br />\n".
		 "	</div>\n";
	echo "</form>\n";
	echo "<p style=\"text-align: center;\"><img scr=\"\" id=\"slideshow\" /></p>\n";
}

function showPic($pic) {
	global $pg, $dir, $pic_index;
	
	$s = sizeOf($pg['photos']);
	$prev = ($pic_index + $s - 1) % $s;
	$next = ($pic_index + 1) % $s;
	
	$size_str = '';
	$width = $pg['main']['max_photo_size'];
	
	if (isset($width) && $width != 0)
	{
		//quick crude way to try to get the image to fit
		//to do it properly we should check that the new width/height fit within the bounding box
		$dims = getImageSize(getcwd()."/$dir/$pic");
		if ($dims[0] > $width)
		{
			$height = $dims[1]*$width/$dims[0];
			$size_str = "width: {$width}px; height: {$height}px;";
		}
		elseif ($dims[1] > 3*$width/4)
		{
			$new_width = (3*$width/4)*$dims[0]/$dims[1];
			$size_str = "width: {$new_width}px; height: ".(3*$width/4)."px;";
		}
	}
	
	echo "  <p style=\"text-align: center; width: 100%;\">\n".
		 "	<img src=\"{$pg['HTTP_root']}$dir/$pic\" alt=\"{$pg['HTTP_root']}$dir/$pic\" style=\"vertical-align: middle; $size_str\"/></p>\n";
	
	echo "	<p style=\"font-weight: bold; text-align: center; width: 100%;\">\n";
	
	$comments = read_comments(getcwd()."/cache/$dir/$pic.nfo");
	
	if (count($comments) != 0)
	foreach ($comments as $val) {
		echo "$val<br />";
	}
	
	if ($pg['main']['side_photos'])
	{
		//next & prev pictures
		echo "  <a href=\"index.php?dir=$dir&pic_index=$prev\">\n";
		$f_name = "{$pg['HTTP_root']}cache/$dir/{$pg['photos'][$prev]}.thumb";
		//optionally put left and right arrows here instead of thumbnails
		echo "	  <img src=\"$f_name\" alt=\"$f_name\" style=\"vertical-align: middle; margin-top: 10px; margin-right: 50px;\" />\n";
		echo "  </a>\n";

		echo "  <a href=\"index.php?dir=$dir&pic_index=$next\">\n";
		$f_name = "{$pg['HTTP_root']}cache/$dir/{$pg['photos'][$next]}.thumb";
		//optionally put left and right arrows here instead of thumbnails
		echo "	  <img src=\"$f_name\" alt=\"$f_name\" style=\"vertical-align: middle; margin-top: 10px; margin-left: 50px;\" />\n";
		echo "  </a>\n";
	}
	echo "  </p>\n";
	
	if ($pg['admin']['allow_comments']) {
		//insert comments & posting form here
		//opens comments file and prints each line
		echo "<form method=\"post\" action=\"index.php\" class=\"text_center\">\n";
		echo "  <p>\n";
		echo "  <input type=\"hidden\" name=\"pic\" value=\"$dir/$pic\" />\n";
		echo "  <input type=\"hidden\" name=\"action\" value=\"add_comment\" />\n";
		echo "  <input type=\"text\" name=\"comment\" size=\"70\" value=\"\"/>\n";
		echo "  <input type=\"submit\" value=\"Submit Caption\" />\n";
		echo "  </p>\n";
		echo "</form>\n";
	}
}

/*
 *  Gets a random image from a directory, and searches (efficiently) if neccessary into 2nd-level directories
 *  $basedir is our root directory (the directory we are currently browsing)
 *  $dir is the directory we want a thumbnail from
 */
function randomImage($basedir, $dir)
{
	$start = getcwd();
	global $pg;
	if ($dh = opendir(getcwd()."/$basedir/$dir")) 
	{
		chdir(getcwd()."/$basedir/$dir");
		//read out current dir contents
		while ($file = readdir($dh))
		{
			if (validFormat($file))
			{
				$fileList[] = $file;
			}
			elseif (is_dir($file) && $file[0] != '.')
			{
				$dirList[] = $file;
			}
		}
		chdir($start);
		closedir($dh);
		if (!empty($fileList))
		{
			$picnum = rand(0, sizeof($fileList) - 1);
			if (strlen($basedir) > 0) $tmp = "/";
			generate_thumb("./$basedir/$dir", $fileList[$picnum]);
			return image_holder("$basedir$tmp$dir", $fileList[$picnum], ($pg['main']['dirnames'] ? $dir : ''));
		}
		else
		{
			//try a random 2nd level directory
			while ($max = sizeOf($dirList))
			{
				$n = rand(0,$max-1);
				$fileList = photoList(getcwd()."/$basedir/$dir/".$dirList[$n]);
				if (!empty($fileList))
				{
					//if there are photos here, return a thumbnail
					$picnum = rand(0, sizeof($fileList) - 1);
					generate_thumb("./$basedir/$dir/".$dirList[$n], $fileList[$picnum]);
					return image_holder("./$basedir/$dir/".$dirList[$n], $fileList[$picnum], ($pg['main']['dirnames'] ? $dir : ''));
				} 
				else 
				{
					//else discard the directory and search the rest
					$dirList[$n] = $dirList[$max-1];
					unset($dirList[$max-1]);
					if ($max == 0)
					{
						return $dir;
					}
				}
			}
		}
	}
	return $dir;
}

function options() {
	global $dir, $pg;
	
	echo "<div class=\"a_config\" style=\"left: 20%; width: 60%\">\n";
	
	echo 	"<form action=\"index.php\" method=\"post\">\n".
			"<input type=\"hidden\" name=\"dir\" value=\"$dir\" />\n".
			"<input type=\"hidden\" name=\"action\" value=\"set_user_prefs\" />\n".
			"<h3 class=\"a_config\">Options (for this computer only)</h3>\n".
			"	<p>".
			"        Number of maximum columns per page: \n".
			"        <select name=\"new[columns]\">";
	for($n=2; $n<10; $n++) {
		echo "	      <option value=\"$n\"".($pg['main']['columns'] == $n ? "selected=\"selected\"" : "").">$n</option>\n";
	}
	echo 	"        </select>\n".	
			"	</p>".
			"	<p>".
			"       Maximum viewable photo size: \n".
			"        <select name=\"new[max_photo_size]\">";
	$sizes = array ( 512, 640, 800, 1024, 1280 );
	
	foreach ($sizes as $width)
	{
		echo "	      <option value=\"$width\"".($pg['main']['max_photo_size'] == $width ? "selected=\"selected\"" : "").">$width x ".(3*$width/4)."</option>\n";
	}
	echo 	"	      <option value=\"0\"".(!isset($pg['main']['max_photo_size']) || $pg['main']['max_photo_size'] == 0 ? "selected=\"selected\"" : "").">No restriction</option>\n";
	echo 	"        </select>\n".
			"	</p>".
			"	<p>".
			"        Photo page style: \n";
	echo 	"      <input type=\"radio\" name=\"new[layout]\" value=\"table\" ".($pg['main']['layout'] == "table" ? "checked=\"checked\"" : "")." /> Table \n".
			"      <input type=\"radio\" name=\"new[layout]\" value=\"flat\" ".($pg['main']['layout'] == "flat" ? "checked=\"checked\"" : "")." /> Flat (show all) \n".
			"	</p>";
	echo	"	<p>\n".
			"		 Show thumbnail photo for each directory: \n".
		 	"      <input type=\"radio\" name=\"new[dirphotos]\" value=\"1\" ".($pg['main']['dirphotos'] == 1 ? "checked=\"checked\"" : "")." /> Yes \n".
			"      <input type=\"radio\" name=\"new[dirphotos]\" value=\"0\" ".($pg['main']['dirphotos'] == 0 ? "checked=\"checked\"" : "")." /> No \n".
			"	</p>\n";
			
	echo	"<input type=\"submit\" value=\"Set options\" />\n".
			"</form>\n";
	echo "</div>\n";
}

function about_page() {
	echo 	"  <table style=\"width: 80%\" class=\"picTable\" cellspacing=\"10px\">\n".
			"    <tr class=\"head\">\n".
			"      <td>\n".
			"        <p>Pretty Helpful Image Sorting Hierarchy</p>\n".
			"        <p>System.. yees.. good it is, written by Kyle Maddison it is.. make your complaints, nags, ".
			"personal issues out to km329 at cam dot ac dot uk in a large self addressed envelope. No exchanges or refunds. &copy;2003 Kyle Maddison</p>\n".
			"        <p>This program is released under the GNU General Public License - it can be modified and redistributed under these conditions.<p>\n";
			"      </td>\n".
			"    </tr>\n".
			"  </table>\n";
}

?>
