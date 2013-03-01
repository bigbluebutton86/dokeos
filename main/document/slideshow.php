<?php

/* For licensing terms, see /dokeos_license.txt */

/**
==============================================================================
*	@package dokeos.document
==============================================================================
*/

// Language files that should be included
$language_file = array('document', 'slideshow');

// setting the help
$help_content = 'documentslideshow';

// including the global Dokeos file
require '../inc/global.inc.php';

// including additional libraries
require_once 'slideshow.inc.php';
require_once api_get_path(LIBRARY_PATH) . 'document.lib.php';

global $_user;

// variable initialisation
$noPHP_SELF = true;
$path = Security::remove_XSS($_GET['curdirpath']);
$pathurl = urlencode($path);
// Get slide ID
$slide_id = Security::remove_XSS($_GET['slide_id']);
// Get sys course path
$sys_course_path = api_get_path(SYS_COURSE_PATH);

// Database table definitions
$tbl_documents = Database::get_course_table(TABLE_DOCUMENT);
$propTable = Database::get_course_table(TABLE_ITEM_PROPERTY);

// setting the breadcrumbs
$interbreadcrumb[] = array ("url" => Security::remove_XSS('document.php?curdirpath='.$pathurl), "name" => get_lang('Documents'));

$htmlHeadXtra[] =
"<script type=\"text/javascript\">
function confirmation (name) {
	if (confirm(\" ". get_lang("AreYouSureToDelete") ." \"+ name + \" ?\"))
		{return true;}
	else
		{return false;}
}
</script>";

// Show hide images of gallery
if (isset($_GET['set_invisible']) || isset($_GET['set_visible'])) {
  if ($_GET['set_invisible']) {
   $update_id = Security::remove_XSS($_GET['set_invisible']);
			$visibility_command = 'invisible';
  } else {
   $update_id = Security::remove_XSS($_GET['set_visible']);
			$visibility_command = 'visible';
  }
 api_item_property_update($_course, TOOL_DOCUMENT, $update_id, $visibility_command, api_get_user_id());
}

// Displaying the header
Display :: display_tool_header(get_lang('Documents'));

// Check if folder exists, admin with the course copy tool sometimes copy only files and no folders
$count_if_folder_exists = DocumentManager::check_if_folder_exists($path);
// loading the slides from the session
if(((isset($_GET['slide_id'])) && $count_if_folder_exists > 0) || $path=='/') {
    // These paths will be used in the sql query
    if ($path=='/') {
       $like_path = $path."%";
       $not_like_path = $path."%/%";
    } else {
       $like_path = $path."/%";
       $not_like_path = $path."/%/%"; 
    }
 // Sql query for get the results
 if (api_is_allowed_to_edit ()) { // Teacher
	  $sql = "SELECT * FROM $tbl_documents doc,$propTable prop WHERE doc.id = prop.ref AND prop.tool = '".TOOL_DOCUMENT."' AND doc.filetype = 'file' AND doc.path LIKE '".$like_path."' AND doc.path NOT LIKE '".$not_like_path."' AND prop.lastedit_type !='DocumentDeleted'";
 } else { // Student
	  $sql = "SELECT * FROM $tbl_documents doc,$propTable prop WHERE doc.id = prop.ref AND prop.tool = '".TOOL_DOCUMENT."' AND doc.filetype = 'file' AND doc.path LIKE '".$like_path."' AND doc.path NOT LIKE '".$not_like_path."' AND prop.visibility = 1 AND prop.lastedit_type !='DocumentDeleted'";
 }

    // Sql query for get mindmap items
    if ($path == '/mindmaps') {
            $path = $path.'/';
            $sql1 = "(SELECT distinct(title) AS title,path,size,id,display_order FROM $tbl_documents doc,$propTable prop WHERE doc.id = prop.ref AND prop.tool = '".TOOL_DOCUMENT."' AND doc.filetype = 'file' AND doc.path LIKE '".$like_path."' AND doc.path NOT LIKE '".$not_like_path."' AND prop.visibility = 1 AND prop.to_user_id IS NULL)";
            $sql2 = "(SELECT distinct(title) AS title,path,size,id,display_order FROM $tbl_documents doc,$propTable prop WHERE doc.id = prop.ref AND prop.tool = '".TOOL_DOCUMENT."' AND doc.filetype = 'file' AND doc.path LIKE '".$like_path."' AND doc.path NOT LIKE '".$not_like_path."' AND prop.visibility = 1  AND prop.insert_user_id = ".$_user['user_id'] . ")";
            $sql = $sql1." UNION ".$sql2." ORDER BY display_order";
    }

    $result = api_sql_query($sql,__FILE__,__LINE__);
    $image_files_only = array();
    while($row = Database::fetch_array($result)) {		
            // Only show files of type image(jpg,bmp,gif,png,jpeg)
            $allowed_image_types = array('jpg','bmp','jpeg','gif','png','tif');
            // Get last 3 characters
            $ext = substr(strrchr($row['path'],'.'),1);
            $ext = strtolower($ext);
            // Only allowed images will be shown in the slider
            if (in_array($ext, $allowed_image_types)) {
            $image_files_only[] = array('path' => $row['path'],'size'=> $row['size'], 'file' => $row['title'], 'id' => $row['id']);// Add path + size
            $index_image_files_only[] = $row['id'];
                if ($row['id'] == intval($_GET['slide_id'])){
                    $current_slide_path = $row['path'];
                }
           }
    }
}

// calculating the current slide, next slide, previous slide and the number of slides
if ($slide_id <> "all") {
	if ($slide_id) {
		$slide = $slide_id;
	} else {
		$slide = 0;
	}

 if ($slide_id == '') {
  $previous_slide = '';
  $slide_id = $index_image_files_only[0];
  $next_slide = $index_image_files_only[1];
  $display_counter = count($index_image_files_only);
 }

 for ($i = 0; $i < count($index_image_files_only); $i++ ) {
  if ($slide_id == $index_image_files_only[$i]) {
   $previous_slide = $index_image_files_only[$i-1];
   $next_slide =  $index_image_files_only[$i+1];
   $display_counter = ($i +1);
  }
 }
	//$previous_slide = $slide -1;
	//$next_slide = $slide +1;
} // if ($slide_id<>"all")
$total_slides = count($image_files_only);

$query = "SELECT * FROM $tbl_documents doc,$propTable prop WHERE doc.id = prop.ref AND prop.tool = '".TOOL_DOCUMENT."' AND doc.filetype = 'folder' AND doc.path ='".urldecode($pathurl)."' AND prop.lastedit_type !='DocumentDeleted'";
$result = api_sql_query($query,__FILE__,__LINE__);
$row = Database::fetch_array($result);

$visibility_icon = ($row['visibility']==0)?'closedeye_tr':'dokeoseyeopen22';
$visibility_command = ($row['visibility']==0)?'set_visible':'set_invisible';
$visibility_title = ($row['visibility']==0)?'UnPublished':'Published';
?>

<?php
// Actions
echo '<div class="actions">';
echo '<a href="document.php?'.api_get_cidReq().'">'.Display::return_icon('pixel.gif', get_lang('Documents'), array('class' => 'toolactionplaceholdericon toolactionback')).' '.get_lang('Documents').'</a>';
echo '<a href="mediabox.php?'.api_get_cidreq().'&curdirpath='.$pathurl.'">'.Display::return_icon('pixel.gif', get_lang('Mediabox'), array('class' => 'toolactionplaceholdericon toolactionmediabox')).' '.get_lang('Mediabox').'</a>';
if(api_is_allowed_to_edit()) {
	echo '<a href="document.php?'.api_get_cidreq().'&action=exit_slideshow&curdirpath='.$pathurl.'">'.Display::return_icon('pixel.gif', get_lang('ListView'), array('class' => 'toolactionplaceholdericon toolactionlist')).' '.get_lang('ListView').'</a>';
}

// The image gallery is allowed for the students
if ($slide_id <> "all") {
	echo '<a href="slideshow.php?'.api_get_cidreq().'&slide_id=all&curdirpath='.$pathurl.'">'.Display::return_icon('pixel.gif', get_lang('Gallery'), array('class' => 'toolactionplaceholdericon toolactiongallery')).' '.get_lang('Gallery').'</a>';
}

if (api_is_allowed_to_edit()) {
	echo '<a href="upload.php?'.api_get_cidReq().'&path='.$pathurl.'">'.Display::return_icon('pixel.gif', get_lang('UplUpload'), array('class' => 'toolactionplaceholdericon toolactionupload')).' '.get_lang('UplUpload').'</a>';
}
echo '</div>';

// Feedback messages
if (isset($_GET['msg']) ) {		
	if($_GET['msg'] == 'DEL') {
		Display::display_confirmation_message(get_lang('DocDeleted'));
	} elseif($_GET['msg'] == 'ERR') {				
		Display::display_error_message(get_lang('DocDeleteError'));
	} elseif($_GET['msg'] == 'ViMod') {				
		Display::display_confirmation_message(get_lang("ViMod"));
	} elseif($_GET['msg'] == 'ViModProb') {				
		Display::display_error_message(get_lang("ViModProb"));
	}	
}

// Mediabox folders
$mediabox_folders = array('images','mascot','photos','animations','mindmaps');
$real_folder_name = substr($path,1);
// start the content div
echo '<div id="content">';
// Display message if folder doesn't exists
if ($count_if_folder_exists == 0 && in_array($real_folder_name, $mediabox_folders)) {
	$message_exists = get_lang('DoesNotExistsTheFolder').' : <strong>'.$real_folder_name.'</strong>';
	echo '<div class="confirmation-message rounded">'.$message_exists.'</div>';
} else {
// display the tool title
//api_display_tool_title(get_lang('TemplateGallery'));

// =======================================================================
//				TREATING THE POST DATA FROM SLIDESHOW OPTIONS
// =======================================================================
// if we come from slideshowoptions.php we sessionize (new word !!! ;-) the options
if (isset ($_POST['Submit'])) {
	// we come from slideshowoptions.php
	$_SESSION["image_resizing"] = Security::remove_XSS($_POST['radio_resizing']);
	if ($_POST['radio_resizing'] == "resizing" && $_POST['width'] != '' && $_POST['height'] != '') {
		//echo "resizing";
		$_SESSION["image_resizing_width"] = Security::remove_XSS($_POST['width']);
		$_SESSION["image_resizing_height"] = Security::remove_XSS($_POST['height']);
	} else {
		//echo "unsetting the session heighte and width";
		$_SESSION["image_resizing_width"] = null;
		$_SESSION["image_resizing_height"] = null;
	}
} // if ($submit)


// The target height and width depends if we choose resizing or no resizing
if ($_SESSION["image_resizing"] == "resizing") {
	$target_width = $_SESSION["image_resizing_width"];
	$target_height = $_SESSION["image_resizing_height"];
} else {
	$image_width = $source_width;
	$image_height = $source_height;
}
// =======================================================================
//						THUMBNAIL VIEW
// =======================================================================
// this is for viewing all the images in the slideshow as thumbnails.
$image_tag = array ();
if ($slide_id == "all") {
	$thumbnail_width = 200;
	$thumbnail_height = 150;
	$row_items = 4;
        $count_index = 0;
	if (is_array($image_files_only)) {
		foreach ($image_files_only as $index => $one_image_file) {
			$image = $sys_course_path.$_course['path']."/document".$one_image_file['path'];
			if (file_exists($image)) {
				list($twidth,$theight) = getimagesize($image);
				
				if($twidth > 200 || $theight > 150) {
				  $image_height_width = resize_image($image, $thumbnail_width, $thumbnail_height, 1);
				
				  $image_height = $image_height_width[0];
				  $image_width = $image_height_width[1];	
				} else {
				  $image_height = $theight;
				  $image_width = $twidth;
				}

				if ($path and $path !== "/") {
					$doc_url = $one_image_file['path'];
				} else {
					$doc_url = $path.$one_image_file['path'];
				}
				$image_tag[] = "<img src='download.php?doc_url=".$doc_url."' border='0' width='".$image_width."' height='".$image_height."' title='".$one_image_file['file']."'>";
                                $image_index_tag[] = $one_image_file['id'];
			}
                                $count_index++;
		} // foreach ($image_files_only as $one_image_file)
	}
} // if ($slide_id=="all")
// creating the table
$html_table='';
$i = 0;
$count_image=count($image_tag);
$number_image=4;
$number_iteration=ceil($count_image/$number_image);

$p=0;
for ($k=0;$k<$number_iteration;$k++) {
	//echo '<tr height="'.$thumbnail_height.'">';
	for ($i=0;$i<$number_image;$i++) {
	//	echo '<div class="big_button four_buttons rounded grey_border float_l" style="clear:none; height:170px; width:220px; padding:0px;margin:0 5px 5px 5px;">';
		echo '<div class="mediabig_button four_buttons rounded grey_border float_l" style="clear:none; height:170px; width:220px; padding:0px;margin:0 5px 5px 5px;">';
		if (!is_null($image_tag[$p])) {
//			echo '<div class="sectionttitle"></div>';				
			echo '<div class="sectioncontent" style="padding: 10px;">';
			echo '<a href="slideshow.php?'.api_get_cidreq().'&slide_id='.$image_index_tag[$p].'&curdirpath='.$pathurl.' ">'.$image_tag[$p].'</a>';
			echo '</div>';
		}
		$p++;
		echo '</div>';
	}
}
echo '<div class="clear">&nbsp;</div>';
// =======================================================================
//						ONE AT A TIME VIEW
// =======================================================================
// this is for viewing all the images in the slideshow one at a time.
if ($slide_id !== "all") {
	$image = $sys_course_path.$_course['path']."/document".$current_slide_path;
	if (file_exists($image)) {

		if (!isset($_REQUEST['linkfile'])) {
		  $sql = "SELECT * FROM $tbl_documents doc,$propTable prop WHERE doc.id = prop.ref AND prop.tool = '".TOOL_DOCUMENT."' AND doc.filetype = 'file' AND doc.id='".Database::escape_string(Security::remove_XSS($slide_id))."' AND prop.lastedit_type !='DocumentDeleted'";
		} else {
		  $sql = "SELECT * FROM $tbl_documents doc,$propTable prop WHERE doc.id = prop.ref AND prop.tool = '".TOOL_DOCUMENT."' AND doc.filetype = 'file' AND doc.path='".Database::escape_string(Security::remove_XSS($_REQUEST['linkfile']))."' AND prop.lastedit_type !='DocumentDeleted'";
		}
		
		$result = api_sql_query($sql,__FILE__,__LINE__);
		$row = Database::fetch_array($result);

		$title = $row['title'];
		$title_without_extension = str_ireplace(array('.jpg','.gif','.bmp','.png','.jpeg'),array('','','','',''),$title);
		$visibility_icon = ($row['visibility']==0)?'closedeye_tr':'dokeoseyeopen22';
		$visibility_command = ($row['visibility']==0)?'set_visible':'set_invisible';

		$image = $sys_course_path.$_course['path']."/document".$row['path'];

		$image_height_width = resize_image($image, $target_width, $target_height);

		$image_height = $image_height_width[0];
		$image_width = $image_height_width[1];

		if ($_SESSION["image_resizing"] == "resizing") {
			$height_width_tags = 'width="'.$image_width.'" height="'.$image_height.'"';
		}

		list($width, $height) = getimagesize($image);

		// showing the comment of the image, Patrick Cool, 8 april 2005
		// this is done really quickly and should be cleaned up a little bit using the API functions
		

	/*	echo '<div class="section">';
			echo '<br/><div class="sectiontitle overflow_h" style="width:35%">';*/
			echo '<div>';
			echo '<div style="width:50%;padding-left:230px;">';
			if ($previous_slide > 0) {
				echo '<div class="float_l sectiontitleleft"><a href="slideshow.php?'.api_get_cidreq().'&slide_id='.$previous_slide.'&amp;curdirpath='.$pathurl.'">';
					echo '<img style="vertical-align:middle; margin:10px 0;" src="'.api_get_path(WEB_IMG_PATH).'previousbig.png" alt="">';
				echo '</a></div>';
			}
			else
			{
				echo '<div class="float_l sectiontitleleft">';
					echo '<img style="vertical-align:middle; margin:10px 0;" src="'.api_get_path(WEB_IMG_PATH).'previousbig.png" alt="">';
				echo '</div>';
			}
			if ($slide_id <> 'all') {
				echo '<div class="float_l sectiontitlecenter" style="padding:15px 0;">&nbsp;&nbsp;&nbsp;'.$title_without_extension.'&nbsp;<br/>&nbsp; '.$display_counter.' '.get_lang('Of').' '.$total_slides.'</div>';
			}	
			// next slide
			if ($next_slide !='' && $slide_id <> "all") {
				echo "<div class='float_l sectiontitleright'><a href='slideshow.php?".api_get_cidreq()."&slide_id=".$next_slide."&curdirpath=$pathurl'>";
					echo '<img style="vertical-align:middle; margin:10px 0;" src="'.api_get_path(WEB_IMG_PATH).'nextbig.png" alt="">';
				echo '</a></div>';
			} else {
				echo "<div class='float_l sectiontitleright'>";
					echo '<img style="vertical-align:middle; margin:5px 0;" src="'.api_get_path(WEB_IMG_PATH).'nextbig.png" alt="">';
				echo '</div>';
			}
			
		echo '	</div>';

                if ($width > 930) {
                    $style = 'width:930px;';
                }
		echo '	<div class="sectioncontent" style="overflow: hidden; text-align:center;">';
		echo "<a href='download.php?doc_url=".$row['path']."' target='_blank' ><img src='download.php?doc_url=".$row['path']."' alt='".$title."' border='0'".$height_width_tags." style='margin:10px 0;".$style."' /></a>";
		echo '	</div>';

		echo '<div class="sectionfooter" style="text-align:center;">';	
			echo '<div style="margin:0 10px 10px 0">'.$title_without_extension." - ".get_lang('Size').': '.$width.'px x '.$height.'px</div>';
			if(api_is_allowed_to_edit()) {	
				$url_path = $path;			
				$forcedownload_link='document.php?action=download&id='.$row['path'];
				$img_style = "margin:0 5px 20px 5px;";
			// delete
				echo '<a style="'.$img_style.'" href="document.php?'.api_get_cidreq().'&curdirpath='.$pathurl.'&delete='.urlencode($row['path']).'&slide_id='.$slide_id.'" onclick="return confirmation(\''.basename($path).'\');">'.Display::return_icon('pixel.gif','',array('class'=>'actionplaceholdericon actiondelete')).'</a>';
			// visible or not
				echo '<a style="'.$img_style.'" href="slideshow.php?'.api_get_cidreq().'&curdirpath='.$pathurl.'&amp;'.$visibility_command.'='.$row['id'].'&slide_id='.$slide_id.'"><img src="../img/'.$visibility_icon.'.png" border="0" title="'.get_lang('Visible').'" alt="" /></a>';
			// download
				echo '<a style="'.$img_style.'" href="'.$forcedownload_link.'">'.Display::return_icon('pixel.gif','',array('class'=>'actionplaceholdericon forcedownload','style'=>'vertical-align:top;','alt'=>'')).'</a>';
				
			}
		echo '	</div>';
		echo '</div>';
		
	} else {
		Display::display_warning_message(get_lang('FileNotFound'));
	}
} // if ($slide_id!=="all")
}
// close the content div
echo '</div>';

 // bottom actions bar
echo '<div class="actions">';
echo '</div>';
// display footer
Display :: display_footer();
?>
