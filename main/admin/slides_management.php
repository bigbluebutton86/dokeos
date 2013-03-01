<?php
/* For licensing terms, see /dokeos_license.txt */

/**
* @package dokeos.admin
* @todo correct typo: language instead of langauge !!!!
* @todo do NOT use $_REQUEST but $_POST or $_GET
* @todo the update statement does not have a WHERE id = ... part so all the entries in the database will be updated. Is this the desired behaviour? 
* @todo why ob_start()... why output buffering? I don't think this is needed here
* @todo why include sortabletable.class.php there is no sortable table on this page. 
*/
// name of the language file that needs to be included
$language_file = array ('registration','admin');

// resetting the course id
$cidReset = true;

// setting the help
$help_content = 'platformadministrationslidesmanagement';

// including the global Dokeos file
require ('../inc/global.inc.php');

// including additional libraries
require_once (api_get_path(LIBRARY_PATH).'formvalidator/FormValidator.class.php');

// setting the section (for the tabs)
$this_section = SECTION_PLATFORM_ADMIN;

// Access restrictions
api_protect_admin_script(true);

$htmlHeadXtra[] ='<script type="text/javascript">
$(document).ready(function() { 
	$(function() {
		$("#contentLeft ul").sortable({ opacity: 0.6, cursor: "move", update: function() {
                    var order = $(this).sortable("serialize") + "&action=changeSlideOrder";                    
					var record = order.split("&");
                    var recordlen = record.length;
                    var disparr = new Array();
                    for (var i=0;i<(recordlen-1);i++) {
                        var recordval = record[i].split("=");
                        disparr[i] = recordval[1];			 
                    }					
                    $.ajax({
                    type: "GET",
                    url: "'.api_get_path(WEB_AJAX_PATH).'slides.ajax.php?action=changeSlideOrder&disporder="+disparr,
                    success: function(msg){}
		})			
		}
		});
	});

});
</script> ';

$tool_name = get_lang('AddSlides');

$slides_table = Database :: get_main_table(TABLE_MAIN_SLIDES);
$slides_management_table = Database :: get_main_table(TABLE_MAIN_SLIDES_MANAGEMENT);

if(isset($_GET['action']) && $_GET['action'] == 'add'){
	//Create form
	$form = new FormValidator('add_slide', 'post', api_get_self().'?action=add');
	$form->addElement('header', '', $tool_name);
	
	$form->addElement('file', 'picture', get_lang('AddPicture'));
	$form->addElement('static','imagesize','',get_lang('DefaultImagesize'));
	$allowed_picture_types = array ('jpg', 'jpeg', 'png', 'gif');
	$form->addRule('picture', get_lang('OnlyImagesAllowed').' ('.implode(',', $allowed_picture_types).')', 'filetype', $allowed_picture_types);
	$form->addRule('picture', get_lang('ThisFieldIsRequired'), 'required');

	$form->addElement('text', 'title', get_lang('Title'),'class="focus" style="width:300px;"');
	$form->addElement('text', 'alttext', get_lang('AltText'),'class="focus" style="width:300px;"');
	$form->addElement('text', 'caption', get_lang('Caption'),'class="focus" style="width:300px;"');
	$form->addElement('text', 'link', get_lang('Link'),'class="focus" style="width:300px;"');
	$form->addElement('select_language', 'language', get_lang('Language'));
	$form->addElement('style_submit_button', 'submit', get_lang('Ok'), 'class="save"');

	// Validate form
	if( $form->validate()) {
		$slides = $form->exportValues();

		$title = $slides['title'];
		$alttext = $slides['alttext'];
		$caption = $slides['caption'];
		$link = $slides['link'];
		$language = $slides['language'];
		
		$updir = api_get_path(SYS_PATH). 'home/default_platform_document/'; //directory path to upload
		
		if(!empty($_FILES['picture']['tmp_name'])){
		$slide_pic = replace_dangerous_char($_FILES['picture']['name'], 'strict');
		
		@move_uploaded_file($_FILES['picture']['tmp_name'], $updir . $slide_pic);	
		
		$thumbWidth = "150";
		create_thumbnail($updir,$slide_pic,$thumbWidth);		
		 
		$sql_display_order = "SELECT max(display_order) AS max_order FROM $slides_table WHERE langauge = '".Database::escape_string($langauge)."'";
		$rs_display_order = Database::query($sql_display_order, __FILE__, __LINE__);
		while($row = Database::fetch_array($rs_display_order)){
			$display_order = $row['max_order'] + 1;
		}

		if(empty($display_order)){
			$display_order = 1;
		}

		$sql = "INSERT INTO " . $slides_table . " SET " .
							   "title				= '".Database::escape_string($slides['title'])."',
							   alternate_text		= '".Database::escape_string($slides['alttext'])."',
							   link					= '".Database::escape_string($slides['link'])."',
							   caption 				= '".Database::escape_string($slides['caption'])."',							   										  
							   image 				= '".Database::escape_string($slide_pic)."',										   
							   language				= '".Database::escape_string($slides['language'])."',	
							   display_order		= ".Database::escape_string($display_order);

		Database::query($sql, __FILE__, __LINE__);	  
		header('Location: slides_management.php?language='.Security::remove_XSS($slides['language']));
		exit ();
		}
	}
}
else if(isset($_GET['action']) && $_GET['action'] == 'edit'){

	$sql = "SELECT * FROM $slides_table WHERE id = ".Database::escape_string($_GET['id']);
	$res = Database::query($sql,__FILE__,__LINE__);
	while($row = Database::fetch_array($res)){
		$title = $row['title'];
		$alternate_text = $row['alternate_text'];
		$link = $row['link'];
		$caption = $row['caption'];
		$image = $row['image'];
		$language = $row['language'];
	}
	$img_path = api_get_path(WEB_PATH). 'home/default_platform_document/template_thumb/thumb_'.$image; //directory path to upload

	$form = new FormValidator('add_slide', 'post', api_get_self().'?action=edit&id='.$_GET['id']);
	$form->addElement('header', '', get_lang('EditSlide'));
	
	$form->addElement('file', 'picture', get_lang('UpdatePicture'));
	$form->addElement('static','imagesize','',get_lang('DefaultImagesize'));
	$form->addElement('static','thumbimage',get_lang('Preview'),'<img src="'.$img_path.'">');	
	$allowed_picture_types = array ('jpg', 'jpeg', 'png', 'gif');
	$form->addRule('picture', get_lang('OnlyImagesAllowed').' ('.implode(',', $allowed_picture_types).')', 'filetype', $allowed_picture_types);

	$form->addElement('text', 'title', get_lang('Title'),'class="focus" style="width:300px;"');
	$form->addElement('text', 'alttext', get_lang('AltText'),'class="focus" style="width:300px;"');
	$form->addElement('text', 'caption', get_lang('Caption'),'class="focus" style="width:300px;"');
	$form->addElement('text', 'link', get_lang('Link'),'class="focus" style="width:300px;"');
	$form->addElement('select_language', 'language', get_lang('Language'));
	$form->addElement('style_submit_button', 'submit', get_lang('Ok'), 'class="save"');

	$defaults['title'] = $title;
	$defaults['alttext'] = $alternate_text;
	$defaults['caption'] = $caption;
	$defaults['link'] = $link;
	$defaults['language'] = $language;
	$form->setDefaults($defaults);

	// Validate form
	if( $form->validate()) {
		$slides = $form->exportValues();

		$title 		= $slides['title'];
		$alttext 	= $slides['alttext'];
		$caption 	= $slides['caption'];
		$link 		= $slides['link'];
		$language 	= $slides['language'];
		
		$updir = api_get_path(SYS_PATH). 'home/default_platform_document/'; //directory path to upload
		
		if(!empty($_FILES['picture']['tmp_name'])){
			$slide_pic = replace_dangerous_char($_FILES['picture']['name'], 'strict');
		
			@move_uploaded_file($_FILES['picture']['tmp_name'], $updir . $slide_pic);	
		
			$thumbWidth = "150";
			create_thumbnail($updir,$slide_pic,$thumbWidth);
		}
		 
		$sql = "UPDATE " . $slides_table . " SET 
						title				= '".Database::escape_string($slides['title'])."',
						alternate_text		= '".Database::escape_string($slides['alttext'])."',
						link				= '".Database::escape_string($slides['link'])."',
						caption				= '".Database::escape_string($slides['caption'])."',";
		if(!empty($_FILES['picture']['tmp_name'])){
				$sql .=			"   image 	= '".Database::escape_string($slide_pic)."',";										   
		}
				$sql .=			" language	= '".Database::escape_string($slides['language'])."' WHERE id = ".$_GET['id'];

		Database::query($sql, __FILE__, __LINE__);	  
		header('Location: slides_management.php?language='.$slides['language']);
		exit ();
		}
}
else if(isset($_GET['action']) && $_GET['action'] == 'modify'){
	$sql = "SELECT * FROM $slides_management_table LIMIT 1";
	$rs = Database::query($sql, __FILE__, __LINE__);	
	while($row = Database::fetch_array($rs)){
		$show_slide = $row['show_slide'];
		$speed = $row['slide_speed'];
	}

	$form = new FormValidator('slide_timer', 'post', api_get_self().'?action=modify');
	$form->addElement('header', '', get_lang('SlideScenario'));
	$form->addElement('radio', 'show_slide', get_lang('ShowSlide'), get_lang('Yes'), 1);
	$form->addElement('radio', 'show_slide', '', get_lang('No'), 0);
	$slide_speed = range(0,30);
	$form->addElement('select', 'speed', get_lang('SlideSpeed'),$slide_speed);
	$form->addElement('style_submit_button', 'submit', get_lang('Ok'), 'class="save"');
	$defaults['speed'] = $speed;
	$defaults['show_slide']  = $show_slide;
	$form->setDefaults($defaults);

	// Validate form
	if( $form->validate()) {
		$slides = $form->exportValues();

		$sql = "UPDATE " . $slides_management_table . " SET 
														show_slide = ".Database::escape_string($slides['show_slide']).",
													    slide_speed	= ".Database::escape_string($slides['speed']);							  

		Database::query($sql, __FILE__, __LINE__);	  
		header('Location: slides_management.php');
		exit ();
	}	
}
else if(isset($_GET['action']) && $_GET['action'] == 'delete'){

	$sql = "SELECT language FROM $slides_table WHERE id = ".Database::escape_string(Security::remove_XSS($_GET['id']));
	$rs = Database::query($sql,__FILE__,__LINE__);
	while($row = Database::fetch_array($rs)){
		$langauge = $row['language'];
	}

	$sql = "DELETE FROM $slides_table WHERE id = ".Database::escape_string(Security::remove_XSS($_GET['id']));
	Database::query($sql,__FILE__,__LINE__);
	header('Location: slides_management.php?language='.$langauge);
	exit ();
}


function create_thumbnail($updir,$slide_pic,$thumbWidth){
	$filename = api_get_path(SYS_PATH). 'home/default_platform_document/template_thumb/';
	$img_name = $updir.$slide_pic;		

	$info = pathinfo($img_name);
	$ext=$info['extension'];

	if(!strcmp("png",$ext))
	$src_img=imagecreatefrompng($img_name);

	if(!strcmp("jpg",$ext))
	$src_img=imagecreatefromjpeg($img_name);	

	if(!strcmp("gif",$ext))
	$src_img=imagecreatefromgif($img_name);

	//gets the dimmensions of the image
	$width=imageSX($src_img);
	$height=imageSY($src_img);

	$new_width = $thumbWidth;
	$new_height = floor( $height * ( $thumbWidth / $width ) );

	// we create a new image with the new dimmensions
	$dst_img=ImageCreateTrueColor($new_width,$new_height);

	// resize the big image to the new created one
	imagecopyresampled($dst_img,$src_img,0,0,0,0,$new_width,$new_height,$width,$height); 

	// output the created image to the file. Now we will have the thumbnail into the file named by $filename
	if(!strcmp("png",$ext))
	imagepng($dst_img,$filename.'thumb_'.$slide_pic); 

	if(!strcmp("jpg",$ext))
	imagejpeg($dst_img,$filename.'thumb_'.$slide_pic); 

	if(!strcmp("gif",$ext))
	imagegif($dst_img,$filename.'thumb_'.$slide_pic);

	//destroys source and destination images. 
	imagedestroy($dst_img); 
	imagedestroy($src_img); 
}

Display :: display_header();

echo '<div class="actions">';
echo '<a href="configure_homepage.php">'.Display::return_icon('pixel.gif', get_lang('HomePage'), array('class' => 'toolactionplaceholdericon toolactionhomepage')).' ' . get_lang('HomePage') . '</a>';
echo '<a href="'.api_get_self().'?action=add">'.Display::return_icon('pixel.gif', get_lang('AddSlides'), array('class' => 'toolactionplaceholdericon toolallpages')).' ' . get_lang('AddSlides') . '</a>';
echo '<a href="'.api_get_self().'?action=modify">'.Display::return_icon('pixel.gif', get_lang('SlideScenario'), array('class' => 'toolactionplaceholdericon toolactionscenario')).' ' . get_lang('SlideScenario') . '</a>';
echo '<div class="float_r">';
echo api_display_language_form(true,'',true);
echo '</div>';
echo '</div>';

echo '<div id="content">';

if(!isset($_GET['action'])){
	if(isset($_GET['language'])){
		$language = $_GET['language'];
	}
	else {
		$language = api_get_interface_language();
	}


	$sql = "SELECT * FROM $slides_table WHERE language = '".Database::escape_string($language)."' ORDER BY display_order";
	$res = Database::query($sql,__FILE__,__LINE__);	
	$num_rows = Database::num_rows($res);
	if($num_rows == 0){
		//Adding 3 default images into database
		$sql = "INSERT INTO $slides_table(title,alternate_text,link,caption,image,language)
				VALUES('".Database::escape_string(get_lang('YourTitle1'))."',
					   '".Database::escape_string(get_lang('AltText1'))."',
					   '#',
					   '".Database::escape_string(get_lang('YourCaption1'))."',
					   'slide01.jpg',
					   '".Database::escape_string($language)."')";
		Database::query($sql,__FILE__,__LINE__);
		$sql = "INSERT INTO $slides_table(title,alternate_text,link,caption,image,language)
				VALUES('".Database::escape_string(get_lang('YourTitle2'))."',
					   '".Database::escape_string(get_lang('AltText2'))."',
					   '#',
					   '".Database::escape_string(get_lang('YourCaption2'))."',
					   'slide02.jpg',
					   '".Database::escape_string($language)."')";
		Database::query($sql,__FILE__,__LINE__);
		$sql = "INSERT INTO $slides_table(title,alternate_text,link,caption,image,language)
				VALUES('".Database::escape_string(get_lang('YourTitle3'))."',
					   '".Database::escape_string(get_lang('AltText3'))."',
					   '#',
					   '".Database::escape_string(get_lang('YourCaption3'))."',
					   'slide03.jpg',
					   '".Database::escape_string($language)."')";
		Database::query($sql,__FILE__,__LINE__);
	}
	$sql = "SELECT * FROM $slides_table WHERE language = '".Database::escape_string($language)."' ORDER BY display_order";
	$res = Database::query($sql,__FILE__,__LINE__);	
	
	echo '<table style="width:100%" id="slidelist" class="data_table data_table_exercise">';
	echo '<tr><td><div class="row"><div class="form_header">'.get_lang('SlidesList').'</div></div></td></tr>';
	echo '<tr>';
	echo '<th width="8%">'.get_lang('Move').'</th>';
	echo '<th width="35%">'.get_lang('Picture').'</th>';
	echo '<th width="15%">'.get_lang('Title').'</th>';
	echo '<th width="15%">'.get_lang('AltText').'</th>';
	echo '<th width="15%">'.get_lang('Caption').'</th>';
	echo '<th width="10%">'.get_lang('Language').'</th>';
	echo '<th width="5%">'.get_lang('Edit').'</th>';
	echo '<th width="5%">'.get_lang('Delete').'</th>';
	echo '</tr>';
	echo '</table>';

	echo '<div id="contentWrap"><div id="contentLeft"><ul id="categories" class="dragdrop nobullets  ui-sortable">';
	while($slide = Database::fetch_array($res)){

		if($i%2 == 0){
			$class = "row_odd";
		}
		else {
			$class = "row_even";
		}

		$thumbimg_dir = api_get_path(WEB_PATH). 'home/default_platform_document/template_thumb/';		
		$picture = "<div align='center'><img src='".$thumbimg_dir."thumb_".$slide['image']."'></div>";	
		$edit_link = '<center><a href="'.api_get_self().'?&action=edit&id='.$slide['id'].'">'.Display::return_icon('pixel.gif', get_lang('Edit'), array('class' => 'actionplaceholdericon actionedit')).'</a></center>';
		$delete_link = '<center><a href="'.api_get_self().'?&action=delete&id='.$slide['id'].'" onclick="javascript:if(!confirm(\''.get_lang('ConfirmYourChoice').'\')) return false;">'.Display::return_icon('pixel.gif', get_lang('Delete'), array('class' => 'actionplaceholdericon actiondelete')).'</a></center>';

	echo '<li id="recordsArray_'.$slide['id'].'" class="category" style="opacity: 1;">';
    echo '<div>';                
    echo '<table width="100%" class="data_table" border="0">	
		     <tr class="'.$class.'">
                    <td align="center" width="12%" style="cursor:pointer">'.Display::return_icon('pixel.gif', get_lang('Move'), array('class' => 'actionplaceholdericon actiondragdrop')).'</td>
					<td width="30%">'.$picture.'</td>
					<td width="12%">'.$slide['title'].'</td>
					<td width="14%">'.$slide['alternate_text'].'</td>
					<td width="14%">'.$slide['caption'].'</td>
					<td width="9%">'.$slide['language'].'</td>
					<td width="4%">'.$edit_link.'</td>
					<td width="5%">'.$delete_link.'</td>';
	echo '</tr></table>';
	echo '</div></li>';
	$i++;
	}
	echo '</ul></div></div>';    
}
else {
	$form->display();
}

echo '</div>';

echo '<div class="actions">';
echo '</div>';

// displaying the footer
Display :: display_footer();
?>
