<?
/*phish Configuration file*/

$pg['owner']['name'] = "Your Name";
$pg['owner']['message'] = "Your messages here.";

$pg['pic_formats'] = array('jpg', 'jpeg');
$pg['film_formats'] = array('avi', 'mpg');
//$pg['HTTP_root'] = "http://".$_SERVER['HTTP_HOST'].substr($_SERVER['PHP_SELF'], 0, strrpos($_SERVER['PHP_SELF'], '/')+1);

$pg['main']['columns'] = 4; //default columns per page
$pg['main']['pic_limit'] = 15;
$pg['main']['dirnames'] = 1; //show directory names
$pg['main']['dirphotos'] = 1; //show directory photos
$pg['main']['filenames'] = 0; //show photo names
$pg['main']['dimensions'] = 1; //show photo dimensions
$pg['main']['side_photos'] = 1;
$pg['main']['sort'] = 1; //directory sorted by creation time (else alphabetical)
$pg['main']['layout'] = "table";
$pg['main']['list_with_photo'] = 1;
$pg['main']['photo_with_table'] = 1;
$pg['main']['slideshow_time'] = 2;

//custom CSS
$pg['css']['table.picTable']['border-color'] = "#070e4c";
$pg['css']['table.picTable']['background'] = "#fff7b2";
$pg['css']['table.picTable']['border-width'] = "3px";
$pg['css']['.thumb']['background'] = "#fff";
$pg['css']['.thumb']['border-width'] = "1px";
$pg['css']['.thumbdir']['background'] = "#d95";
$pg['css']['.thumbdir']['border-width'] = "2px";
$pg['css']['.curThumb']['background'] = "#ddd";
$pg['css']['a']['color'] = "#007";
$pg['css']['a:hover']['color'] = "#a00";
$pg['css']['body']['font-size'] = "12pt";
$pg['css']['body']['color'] = "#000000";
$pg['css']['body']['background-image'] = "";
//admin
$pg['admin']['email'] = "";
$pg['admin']['comment_notify'] = 1; //e-mail notification to admin on new comment added
$pg['admin']['photo_size'] = 1024; //photo size when resized
$pg['admin']['use_convert'] = 1;
$pg['admin']['allow_comments'] = 1;
$pg['admin']['header'] = ''; //page header file
$pg['admin']['footer'] = "foot.inc"; //page footer file
$pg['admin']['thumb_size'] = 150; //gallery thumbnail size
$pg['admin']['thumb_size_updated'] = 1082136013; //unix timestamp of when $pg['admin']['thumb_size'] was updated
$pg['admin']['main_width'] = 100;

/*
Pretty Helpful Image Sorting Hierarchy
*/
?>
