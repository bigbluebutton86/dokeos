<?php
// including the widgets language file
$language_file = array ('widgets');

// include the global Dokeos file
include_once('../../inc/global.inc.php');

// load the specific widget settings
api_load_widget_settings();

//action handling
switch ($_POST['action']) {
	case 'get_widget_information' :
		content_get_information ();
		break;
	case 'get_widget_content' :
		content_get_content ();
		break;
	case 'addcontent' :
		content_display_form ();
		break;
	case 'savecontent' :
		content_save ( $_POST );
		break;
	case 'install' :
		content_install ();
		break;
	case 'addcomment' :
		comment_display_form ();
		break;
	case 'get_comment' :
		get_comment ( $_POST['content_id'] );
		break;
	case 'save_comment' :
		save_comment ( $_POST );
		break;
	case 'deletecontent' :
		delete_content ( $_POST['content_id'] );
		break;
	case 'editcontent' : 
		content_display_form ($_POST['content_id']);
		break;		
	case 'savecontentorder'	:
		if($_POST['widget'] == 'content'){
			save_content_order($_POST['content']);
	}
}
switch ($_GET ['action']) {
	case 'get_widget_information' :
		content_get_information ();
		break;
	case 'get_widget_content' :
		content_get_content ();
		break;
	case 'get_widget_title' :
		content_get_title ();
		break;
	case 'addcontent' :
		content_display_form ();
		break;
	case 'savecontent' :
		content_save ( $_GET );
		break;
	case 'install' :
		content_install ();
		break;
	case 'addcomment' :
		comment_display_form ();
		break;
	case 'get_comment' :
		get_comment ( $_GET['content_id'] );
		break;
	case 'save_comment' :
		save_comment ( $_GET );
		break;
	case 'deletecontent' :
		delete_content ( $_GET['content_id'] );
		break;
	case 'editcontent' : 
		content_display_form ($_GET['content_id']);
		break;		
}

/**
 * This function determines if the widget can be used inside a course, outside a course or both
 * 
 * @return array 
 * @version Dokeos 1.9
 * @since January 2010
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University, Belgium
 */
function content_get_scope(){
	return array('course', 'platform');
}


function content_get_content() {
	global $_course;
	
	// adding the style of the content interface
	echo '<style type="text/css" media="screen, projection">
			/*<![CDATA[*/
			@import "'.api_get_path(WEB_PATH).'main/widgets/content/content.css";
			/*]]>*/
			</style>';	
	
	if (api_is_allowed_to_edit ()) {
		echo '<a href="' . api_get_path ( WEB_PATH ) . 'main/widgets/content/widgetfunctions.php?action=addcontent" title="' . get_lang ( 'AddContent' ) . '" class="dialoglink">' . Display::return_icon ( 'new_test.gif' ) . ' ' . get_lang ( 'AddContent' ) . '</a>';
		;
	}
	
	// displaying the images in the content
	echo '<script>
			$(function(){
				$(".content img").each(function(){
					var src = $(this).attr("src");
					// transform relative links into absolute links (inside the course)
					$(this).attr("src", src.replace("../../../../", "' . api_get_path ( WEB_PATH ) . '"));
					// transform relative links into absolute links (outside the course)
					$(this).attr("src", src.replace("../../", "' . api_get_path ( WEB_PATH ) . 'main/"));
				});	

				$(".content_number_of_comments a").live("click", function(){
					// the id of the content
					var id = $(this).attr("href");
					
					// debug
					// $("#content_"+id+" .content_list").html(id);
					
					$.get("' . api_get_path ( WEB_PATH ) . 'main/widgets/content/widgetfunctions.php", {action:"get_comment",content_id:id}, 
						function(comments) {
							$("#content_"+id+" .content_list").html(comments).slideDown("slow");
					});
					
					return false;
				});
				
				
				$(".content_add_comment a").live("click", function(){
					// the id of the content
					var id = $(this).attr("href");
									
					$.get("' . api_get_path ( WEB_PATH ) . 'main/widgets/content/widgetfunctions.php", {action:"addcomment",content_id:id}, 
						function(comments) {
							$("#content_"+id+" .content_list").html(comments).slideDown("slow");
					});
					
					return false;
				});		

				$(".deletecontent").live("click", function(){
					// the id of the content
					var id = $(this).attr("href");
									
					$.get("' . api_get_path ( WEB_PATH ) . 'main/widgets/content/widgetfunctions.php", {action:"deletecontent",content_id:id}, 
						function(comments) {
							$("#content_"+id).slideUp("slow", function(){
								$("#content_"+id).remove();	
							});
					});
					
					return false;
				});		
			})</script>';
	
	// database table definitions
	$table_documents = Database::get_course_table ( TABLE_DOCUMENT );
	if (!empty($_course) AND is_array($_course)){
		$table_content = Database::get_course_table ('content');
		$table_comment = Database::get_course_table ('comment');
	} else {
		$table_content = Database::get_main_table ('content');
		$table_comment = Database::get_main_table ('comment');
	}

	
	// getting the number of comments for every content item
	$sql = "SELECT count(id) as total, ref FROM $table_comment WHERE tool = 'widget_content' GROUP BY ref";
	$result = Database::query ( $sql, __FILE__, __LINE__ );
	while ( $row = Database::fetch_array ( $result, 'ASSOC' ) ) {
		$comment [$row ['ref']] = $row ['total'];
	}
	
	// getting all the content
	if (!empty($_course) AND is_array($_course)){
		$sql = "SELECT *, content.id as content_id, content.title as content_title 
					FROM $table_content content, $table_documents document 
					WHERE content.ref = document.id
					ORDER BY content.display_order DESC
					";
	} else {
		$sql = "SELECT *, content.id as content_id, content.title as content_title FROM $table_content ORDER BY content.display_order DESC";
	}
	$result = Database::query ( $sql, __FILE__, __LINE__ );
	echo '<script>';
	echo '$("div.contentwrapper").sortable({ 
			handle:	".contentmovehandle",
			update: function(){ 
				var parameters = "action=savecontentorder&widget=content&"+$(this).sortable("serialize"); 
				$.post("'.api_get_path(WEB_CODE_PATH).'widgets/content/widgetfunctions.php", parameters, function(theResponse){
					$("#debug").html(theResponse);
				});
				}
			});';	
	echo '</script>';
	echo '<div class="contentwrapper">';
	while ( $row = Database::fetch_array ( $result, 'ASSOC' ) ) {
		$content = '';
		echo '<div id="content_' . $row ['content_id'] . '" class="content" style="border: 1px solid #ddd; margin-bottom: 10px; padding: 5px;">';
		echo '<div class="content_title">' . $row ['content_title'];
		if (api_is_allowed_to_edit ()) {
			echo '<span class="contentactions">';
			Display::display_icon('draggable.png', get_lang('Move'), array('height' => '22px', 'class' => 'contentmovehandle'));
			echo '<a href="' . api_get_path ( WEB_PATH ) . 'main/widgets/content/widgetfunctions.php?action=editcontent&content_id=' . $row ['content_id'] . '" title="' . get_lang ( 'EditContent' ) . '" class="dialoglink">' . Display::return_icon ( 'edit.png' ) . '</a>';
			echo '<a href="' . $row ['content_id'] . '" title="' . get_lang ( 'DeleteContent' ) . '" class="deletecontent">' . Display::return_icon ( 'delete.png' ) . '</a>';
			echo '</span>';
		}		
		echo '</div>';
		echo '<div class="content_body">';
		if (!empty($_course) AND is_array($_course)){
			$content_file = api_get_path ( SYS_COURSE_PATH ) . $_course ['path'] . '/document/' . $row ['path'];
			$handle = fopen ( $content_file, "r" );
			$content = fread ( $handle, filesize ( $content_file ) );
			echo $content;
		} else {
			echo $row['content'];
		}
		echo '</div>';
		echo '<div class="content_comment">';
		if ($row ['comments_allowed']) {
			echo '<div class="content_number_of_comments"><a href="' . $row ['content_id'] . '"><span class="number">' . ( int ) $comment [$row ['content_id']] . '</span> ' . get_lang ( 'Comments' ) . '</a></div>';
			if (!api_is_anonymous()){
				echo '<div class="content_add_comment"><a href="' . $row ['content_id'] . '" title="' . get_lang ( 'AddContent' ) . '" class="">' . get_lang ( 'AddComment' ) . '</a></div>';
				//echo '<div class="content_add_comment"><a href="'.api_get_path(WEB_PATH).'main/widgets/content/widgetfunctions.php?action=addcomment&amp;content_id='.$row['content_id'].'" title="'.get_lang('AddContent').'" >'.get_lang('AddComment').'</a></div>';
			}
		}
		echo '<div class="content_list" style="display:none;"></div>';
		echo '</div>';
		echo '</div>';
	}
	echo '</div>';

}

function content_get_content_info($content_id=0){
	global $_course;
	
	// database table definitions
	$table_documents = Database::get_course_table ( TABLE_DOCUMENT );
	if (!empty($_course) AND is_array($_course)){
		$table_content = Database::get_course_table ('content');
		$table_comment = Database::get_course_table ('comment');
	} else {
		$table_content = Database::get_main_table ('content');
		$table_comment = Database::get_main_table ('comment');
	}

	// getting the information of the content table
	if ($content_id == 0){
		$sql = "SELECT * FROM $table_content ORDER BY display_order DESC";
	} else{
		$sql = "SELECT * FROM $table_content WHERE id = '".Database::escape_string($content_id)."' ORDER BY display_order DESC";
	}
	$result = Database::query ( $sql, __FILE__, __LINE__ );
	while ( $row = Database::fetch_array ( $result, 'ASSOC' ) ) {
		if (!empty($_course) AND is_array($_course)){
			// getting the information of the documents table
			$sql_doc = "SELECT * FROM $table_documents WHERE id = '".Database::escape_string($row['ref'])."'";
			$result_doc = Database::query ( $sql_doc, __FILE__, __LINE__ );
			$doc = Database::fetch_array ( $result_doc, 'ASSOC' );
		
			$content_file = api_get_path ( SYS_COURSE_PATH ) . $_course ['path'] . '/document/' . $doc['path'];
			$handle = fopen ( $content_file, "r" );
			$row['content'] = fread ( $handle, filesize ( $content_file ) );
		}
		$return = $row;
	}

	return $return; 
}

function content_display_form($content_id=0) {
	require_once (api_get_path ( LIBRARY_PATH ) . 'formvalidator/FormValidator.class.php');
	
	// we have to do this without ajax because the current fckeditor implementation in Dokeos does not support ajaxsubmit (too many changes)
	$form = new FormValidator ( 'addcontent', 'post', api_get_path ( WEB_PATH ) . 'main/widgets/content/savecontent.php' );
	
	// settting the form elements
	$form->addElement ( 'header', '', get_lang ( 'AddContent' ) );
	$form->addElement ( 'hidden', 'action', get_lang ( 'Action' ) );
	$form->addElement ( 'hidden', 'content_id', get_lang ( 'ContentId' ));
	$form->addElement ( 'text', 'content_title', get_lang ( 'Title' ), 'class="input_titles"' );
	$form->addElement ( 'html_editor', 'content_comment', get_lang ( 'Content' ), null, array ('ToolbarSet' => 'Forum', 'Width' => '98%', 'Height' => '200' ) );
	$group [] = & HTML_QuickForm::createElement ( 'radio', 'content_comments_allowed', null, get_lang ( 'Yes' ), 1 );
	$group [] = & HTML_QuickForm::createElement ( 'radio', 'content_comments_allowed', null, get_lang ( 'No' ), 0 );
	$form->addGroup ( $group, 'content_comments_allowed', get_lang ( 'AreCommentsAllowed' ), '&nbsp;' );
	$form->addElement ( 'style_submit_button', 'submit_add_content', get_lang ( 'SaveContent' ), 'class="add"' );
	
	// setting the rules
	$form->addRule ( 'content_title', '<div class="required">' . get_lang ( 'ThisFieldIsRequired' ), 'required' );
	
	// default values
	$defaults ['action'] = 'savecontent';
	$defaults ['content_id'] = $content_id;
	$defaults ['content_comments_allowed'] = '0';	
	
	// getting the information
	if ($content_id <> 0){
		$content_info = content_get_content_info($content_id);
		$defaults['content_title'] = $content_info['title'];
		$defaults['content_comment'] = $content_info['content'];
		$defaults['content_comments_allowed'] = $content_info['comments_allowed'];  
	}

	$form->setDefaults ( $defaults );
	
	// The validation or display
	if ($form->validate ()) {
		$check = Security::check_token ( 'post' );
		if ($check) {
			$values = $form->exportValues ();
		}
		Security::clear_token ();
	} else {
		$token = Security::get_token ();
		$form->addElement ( 'hidden', 'sec_token' );
		$form->setConstants ( array ('sec_token' => $token ) );
		$form->display ();
	}
	
	//echo '<script>';
	//echo '$(function(){ $("textarea").fck({path: "'.api_get_path(WEB_PATH).'main/inc/lib/fckeditor/"}); });';
	// we already add the save button and a feedback message to the button pane (but do not display it yet)
	/*
	echo '$(".ui-dialog-buttonpane").prepend("<button class=\"ui-state-default ui-corner-all\" type=\"button\" name=\"SaveContent\" id=\"SaveContent\" style=\"display:none;\">'.get_lang('SaveContent').'</button>");';
	echo '$(".ui-dialog-buttonpane").append("<div class=\"ui-widget\" style=\"width: 75%\">',
												'<div class=\"ui-corner-all dialogfeedback ui-state-highlight\" name=\"dialogfeedback\" id=\"dialogfeedback\" style=\"display:none; line-height:1.4em; font-size: 100%; margin:5px 5px 3px 0px; padding:0.2em 0.6em 0.3em;\">',
													get_lang('ABC'),
												'</div>',
											'</div>");';
	*/
	//echo '$(".ui-dialog-buttonpane").hide(); ';
	
	// displaying the save button when something in the form is changed and hiding the OK button
	/*
    echo '$("#addcontent").live("click", function() {
			// hiding the OK button
			$(".ui-dialog-buttonpane button").hide(); 
			// showing the SaveSettings button
			$("#SaveContent").show(); // attr("style","display:block;");
    	});';	
    */
	
	// saving the navigation item	
	/*
    echo '$("#SaveContent").live("click", function() {
    		// changing the button to indicate that we are saving it
			$("#SaveContent").html("'.get_lang('SavingDotted').'");
			// the actual saving
			var options = { 
		    	success:    function() { 
		    		alert("Thank you for your comment!"); 
		    		// display a feedback message in the dialog for 5 seconds, then remove it
        			$(".dialogfeedback").html("'.get_lang('NewContentIsSaved').'").show();
        			// hide it again
					$(".dialogfeedback").animate({ 
						opacity: 1
				  	}, 5000).animate({ 
						opacity: 0
				  	}, 1500);
				  	// we set the text of the button again to SaveSettings
				  	$("#SaveNavigation").html("'.get_lang('SaveNewNavigationItem').'");
					// we show all the buttons again (the OK button was hidden)
					$(".ui-dialog-buttonpane button").show();
					// but we hide the save button again after successfully saving the widget settings
				  	$("#SaveContent").hide();
					
    			}
			};
			$("#addcontent").ajaxSubmit(options);
			
    	});';
    	*/
	//echo '</script>';
}

function content_save($formelements) {
	global $_course, $_user;
	
	// access restriction
	if (!api_is_allowed_to_edit ()) {
		return false;
	}
	// database table definitions
	$table_documents = Database::get_course_table ( TABLE_DOCUMENT );
	// database table definitions
	$table_documents = Database::get_course_table ( TABLE_DOCUMENT );
	if (!empty($_course) AND is_array($_course)){
		$table_content = Database::get_course_table ('content');
		$table_comment = Database::get_course_table ('comment');
	} else {
		$table_content = Database::get_main_table ('content');
		$table_comment = Database::get_main_table ('comment');
	}
	
	// including the library for fileuploads (for the create_unexisting_directory() function)
	require_once (api_get_path ( LIBRARY_PATH ) . 'fileUpload.lib.php');
	
	// create the widget_content folder if it does not exist yet
	if (! is_dir ( api_get_path ( SYS_COURSE_PATH ) . $_course ['path'] . '/document/widget_content/' )) {
		// create the folder
		$created_dir = create_unexisting_directory ( $_course, $_user ['user_id'], 0, null, api_get_path ( SYS_COURSE_PATH ) . $_course ['path'] . '/document', '/widget_content', 'widget_content' );
		
		// update it so that it is invisible by default
		$sql = "SELECT * FROM $table_documents WHERE path = '/widget_content'";
		$result = Database::query ( $sql, __FILE__, __LINE__ );
		$row = Database::fetch_array ( $result );
		api_item_property_update ( $_course, TOOL_DOCUMENT, $row ['id'], 'invisible', $_user['user_id'] );
	}
	
	// create a unique name for the document
	$filename = date ( 'Y-m-d-H-i-s-' ) . str_replace ( ' ', '', strip_tags ( trim ( $formelements['content_title'] ) ) );
	
	// the filepath
	$filepath = api_get_path ( 'SYS_COURSE_PATH' ) . $_course['path'] . '/document/widget_content/';
	
	// the extension
	$extension = 'html';
	
	// create the document
	if ($new_content_file = @fopen($filepath . $filename . '.' . $extension, 'w' )) {
		// write the content to the file
		fputs ( $new_content_file, $formelements ['content_comment'] );
		fclose ( $new_content_file );
		
		// change the permissions of the file
		$files_perm = api_get_setting ( 'permissions_for_new_files' );
		$files_perm = octdec ( ! empty ( $files_perm ) ? $files_perm : '0770' );
		chmod ( $filepath . $filename . '.' . $extension, $files_perm );
		
		// get the filesize of the file
		$file_size = filesize ( $filepath . $filename . '.' . $extension );
		
		// store it in the documents table
		

		$document_id = add_document ( $_course, '/widget_content/' . $filename . '.' . $extension, 'file', $file_size, $filename, null, 0 );
		
		// store it in item_property
		api_item_property_update ( $_course, TOOL_DOCUMENT, $document_id, 'DocumentAdded', $_user['user_id'], $to_group_id, null, null, null, $current_session_id );
	}
	
	// get the max display_order
	$sql = "SELECT MAX(display_order) AS max FROM $table_content";
	$result = Database::query ( $sql, __FILE__, __LINE__ );
	$row = Database::fetch_array ( $result, 'ASSOC' );
	$max = $row['max'];
	
	// save or update it in the content table
	if ($formelements['content_id'] == 0 or empty($formelements['content_id'])){
		$sql = "INSERT INTO $table_content(title, content, comments_allowed, ref, display_order, submitdate) VALUES (
						'" . Database::escape_string ( $formelements ['content_title'] ) . "', 
						'" . Database::escape_string ( $formelements ['content_comment'] ) . "', 
						'" . Database::escape_string ( $formelements ['content_comments_allowed'] ['content_comments_allowed'] ) . "', 
						'" . Database::escape_string ( $document_id ) . "',
						'" . Database::escape_string ( $max + 1 ) . "',
						NOW())";
	} else {
		$sql = "UPDATE $table_content SET 
						title = '" . Database::escape_string ( $formelements['content_title'] ) . "', 
						content = '" . Database::escape_string ( $formelements['content_comment'] ) . "', 
						comments_allowed = '" . Database::escape_string ( $formelements['content_comments_allowed']['content_comments_allowed'] ) . "', 
						ref = '" . Database::escape_string ( $document_id ) . "',
						submitdate = NOW()
				 WHERE id= '".Database::escape_string($formelements['content_id'])."'";
	};
	$result = Database::query ( $sql, __FILE__, __LINE__ );
	
	return true;
}

function content_get_title($param1, $original_title=false) {
	$config_title = api_get_setting('content', 'title');
	if (!empty($config_title) AND $original_title==false){
		return $config_title;
	} else {
		return get_lang('WidgetContentTitle');
	}
}

function content_get_information() {
	echo get_lang('ContentInformation');
}

function content_settings_form() {

}

function content_install() {
	global $_course;

	// database table definitions
	if (!empty($_course) AND is_array($_course)){
		$table_content = Database::get_course_table ('content');
		$table_comment = Database::get_course_table ('comment');
	} else {
		$table_content = Database::get_main_table ('content');
		$table_comment = Database::get_main_table ('comment');
	}
	
	$sql = 'CREATE TABLE IF NOT EXISTS ' . $table_content . ' (
			  id int(11) NOT NULL auto_increment,
			  title varchar(250) NOT NULL,
			  content TEXT NOT NULL,
			  comments_allowed int(11) NOT NULL,
			  ref int(11),
			  display_order int(11),
			  submitdate datetime,
			  PRIMARY KEY  (id)
			)';
	$res = Database::query ( $sql, __FILE__, __LINE__ );
	
	$sql = 'CREATE TABLE IF NOT EXISTS ' . $table_comment . ' (
			  id int(11) NOT NULL auto_increment,
			  tool varchar(250) NOT NULL,
			  ref int(11),
			  title varchar(250) NOT NULL,
			  content TEXT NOT NULL,
			  submitdate datetime,
			  user_id int(11),
			  PRIMARY KEY  (id)
			)';
	$res = Database::query ( $sql, __FILE__, __LINE__ );
}

function comment_display_form() {
	// access restriction
	/*
	if (!api_is_allowed_to_edit ()) {
		return false;
	}
	*/
	require_once (api_get_path ( LIBRARY_PATH ) . 'formvalidator/FormValidator.class.php');
	echo '<style>
		div.row div.formw{
			width: 210px; 					
		} 		
		</style>';
	
	// we have to do this without ajax because the current fckeditor implementation in Dokeos does not support ajaxsubmit (too many changes)
	$form = new FormValidator ( 'addcomment', 'post', api_get_path ( WEB_PATH ) . 'main/widgets/content/widgetfunctions.php' );
	
	// settting the form elements
	$form->addElement ( 'header', '', get_lang ( 'AddComment' ) );
	$form->addElement ( 'hidden', 'action', get_lang ( 'Action' ) );
	$form->addElement ( 'hidden', 'content_id', get_lang ( 'ContentId' ) );
	$form->addElement ( 'text', 'comment_title', get_lang ( 'Title' ), array ('size' => 30 ) );
	$form->addElement ( 'textarea', 'comment_text', get_lang ( 'Comment' ), array ('rows' => 5, 'cols' => 30 ) );
	$form->addElement ( 'style_submit_button', 'submit_add_comment', get_lang ( 'SaveComment' ), 'class="add submit_add_comment"' );
	
	// setting the rules
	$form->addRule ( 'comment_title', '<div class="required">' . get_lang ( 'ThisFieldIsRequired' ), 'required' );
	
	// default values
	$defaults ['action'] = 'save_comment';
	$defaults ['content_id'] = $_GET ['content_id'];
	$form->setDefaults ( $defaults );
	
	$form->display ();
	
	echo '<script>';
	echo '			var options = { 
		    	success:    function() { 
		    		// display feedback message
		    		$("#addcomment").before("<div class=\"confirmation-message\">comment opgeslagen</div>");
		    		
		    		// remove the feedback message after x seconds
		    		$(".confirmation-message").animate({	opacity: 1 }, 2500).animate({ opacity: 0,height: "toggle" }, 500);
		    		
		    		$("#addcomment").remove();
    			}
			};';
	echo '$(".submit_add_comment").live("click", function(){
				// the id of the content
				var content_id = $(this).parents(".content").attr("id")
				content_id = content_id.replace("content_","");
				
				// the current number of content items
				number_of_comments = $("#content_"+content_id+" .content_number_of_comments span").html();
				number_of_comments = parseInt(number_of_comments) + 1;
				$("#content_"+content_id+" .content_number_of_comments span").html(number_of_comments);
				
				//submitting the form
	 			$("#addcomment").ajaxForm(options);

	    	});';
	
	/* this is old code that used the dialog functionality to display a form for commenting
	// we already add the save button and a feedback message to the button pane (but do not display it yet)
	echo '$(".ui-dialog-buttonpane").prepend("<button class=\"ui-state-default ui-corner-all\" type=\"button\" name=\"Savecomment\" id=\"Savecomment\" style=\"display:none;\">'.get_lang('SaveComment').'</button>");';
	echo '$(".ui-dialog-buttonpane").append("<div class=\"ui-widget\" style=\"width: 75%\">',
												'<div class=\"ui-corner-all dialogfeedback ui-state-highlight\" name=\"dialogfeedback\" id=\"dialogfeedback\" style=\"display:none; line-height:1.4em; font-size: 100%; margin:5px 5px 3px 0px; padding:0.2em 0.6em 0.3em;\">',
													get_lang('ABC'),
												'</div>',
											'</div>");';
	
	// displaying the save button when something in the form is changed and hiding the OK button
    echo '$("#addcomment").live("click", function() {
			// hiding the OK button
			$(".ui-dialog-buttonpane button").hide(); 
			// showing the Savecomment button
			$("#Savecomment").show(); // attr("style","display:block;");
    	});';	
	
   
	// saving the navigation item	

    echo '$("#Savecomment").live("click", function() {
    		// changing the button to indicate that we are saving it
			$("#Savecomment").html("'.get_lang('SavingDotted').'");
			// the actual saving
			var options = { 
		    	success:    function() { 
		    		alert("Thank you for your comment!"); 
		    		// display a feedback message in the dialog for 5 seconds, then remove it
        			$(".dialogfeedback").html("'.get_lang('NewContentIsSaved').'").show();
        			// hide it again
					$(".dialogfeedback").animate({ 
						opacity: 1
				  	}, 5000).animate({ 
						opacity: 0
				  	}, 1500);
				  	// we set the text of the button again to SaveSettings
				  	$("#SaveNavigation").html("'.get_lang('SaveNewNavigationItem').'");
					// we show all the buttons again (the OK button was hidden)
					$(".ui-dialog-buttonpane button").show();
					// but we hide the save button again after successfully saving the widget settings
				  	$("#Savecomment").hide();
					
    			}
			};
			$("#addcomment").ajaxSubmit(options);
			
    	});';
    	*/
	echo '</script>';
}

function get_comment($content_id) {
	global $_course;

	// database table definitions
	if (!empty($_course) AND is_array($_course)){
		$table_comment = Database::get_course_table ('comment');
	} else {
		$table_comment = Database::get_main_table ('comment');
	}
	$table_user 	= Database::get_main_table ( TABLE_MAIN_USER );
	
	$sql = "SELECT comment.*, user.firstname, user.lastname, user.email, user.user_id, user.picture_uri
				FROM $table_comment comment, $table_user user 
				WHERE comment.tool='widget_content' 
				AND comment.ref = '" . Database::escape_string ( $content_id ) . "' 
				AND comment.user_id = user.user_id 
				ORDER BY comment.id ASC";
	$result = Database::query ( $sql, __FILE__, __LINE__ );
	while ( $row = Database::fetch_array ( $result, 'ASSOC' ) ) {
		echo '<div class="comment">';
		//echo '<div class="userimage">'.$row['picture_uri'].'</div>';
		echo '<div class="comment_author">'.$row['firstname'].' '.$row['lastname'].' - '.$row['firstname'].' ('.$row['submitdate'].')</div>';
		echo '<div class="comment_title">' . $row ['title'] . '</div>';
		echo '<div class="comment_text">' . $row ['content'] . '</div>';
		echo '</div>';
	}
}

function save_comment($values) {
	global $_course;

	// database table definitions
	if (!empty($_course) AND is_array($_course)){
		$table_comment = Database::get_course_table ('comment');
	} else {
		$table_comment = Database::get_main_table ('comment');
	}
	
	if (!empty($values['comment_title']) AND !empty($values['comment_text'])){
		$sql = "INSERT INTO $table_comment (tool, ref, title, content, user_id) VALUES ('widget_content', '" . Database::escape_string ( $values ['content_id'] ) . "', '" . Database::escape_string ( $values ['comment_title'] ) . "','" . Database::escape_string ( $values ['comment_text'] ) . "','".Database::escape_string(api_get_user_id())."')";
		$result = Database::query ( $sql, __FILE__, __LINE__ );
	} else{
		return false;		
	}
}

function delete_content($content_id) {
	global $_course;
	// access restriction
	if (!api_is_allowed_to_edit ()) {
		return false;
	}

	// database table definitions
	$table_document = Database::get_course_table ( 'document' );
	if (!empty($_course) AND is_array($_course)){
		$table_content = Database::get_course_table ('content');
		$table_comment = Database::get_course_table ('comment');
	} else {
		$table_content = Database::get_main_table ('content');
		$table_comment = Database::get_main_table ('comment');
	}	
	
	// deleting the comments
	$sql = "DELETE FROM $table_comment WHERE tool = 'widget_content' AND ref = '" . Database::escape_string ( $content_id ) . "'";
	$result = Database::query ( $sql, __FILE__, __LINE__ );
	
	// selecting all the information of the content (to delete the document)
	if (!empty($_course) AND is_array($_course)){
		$sql = "SELECT * FROM $table_content content, $table_document document 
					WHERE content.id = '" . Database::escape_string ( $content_id ) . "'
					AND content.ref = document.id";
		$result = Database::query ( $sql, __FILE__, __LINE__ );
		$content_info = Database::fetch_array ( $result, 'ASSOC' );
	}
	
	// deleting the content
	$sql = "DELETE FROM $table_content WHERE id = '" . Database::escape_string ( $content_id ) . "'";
	$result = Database::query ( $sql, __FILE__, __LINE__ );
	
	// deleting the source document
	if (!empty($_course) AND is_array($_course)){
		include_once (api_get_path ( LIBRARY_PATH ) . 'document.lib.php');
		DocumentManager::delete_document ( $_course, $content_info ['path'], api_get_path(SYS_COURSE_PATH).$_course['path']."/document");
	}
}

function save_content_order($content){
	global $_course;

	// access restriction
	if (!api_is_allowed_to_edit ()) {
		return false;
	}

	// database table definitions
	if (!empty($_course) AND is_array($_course)){
		$table_content = Database::get_course_table ('content');
	} else {
		$table_content = Database::get_main_table ('content');
	}

	$max = count($content);

	$counter = $max;
	foreach ($content as $key=>$contentid){
		$sql = "UPDATE $table_content SET display_order = '".Database::escape_string($counter)."' WHERE id = '".Database::escape_string($contentid)."'";
		$result = Database::query ( $sql, __FILE__, __LINE__ );
		$counter--;
	}
}
?>
