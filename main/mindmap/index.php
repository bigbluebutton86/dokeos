<?php //$id: $
/* For licensing terms, see /dokeos_license.txt */
/**
==============================================================================
 * @desc The dropbox is a personal (peer to peer) file exchange module that allows
 * you to send documents to a certain (group of) users.
 *
 * @version 1.3
 *
 * @author Jan Bols <jan@ivpv.UGent.be>, main programmer, initial version
 * @author René Haentjens <rene.haentjens@UGent.be>, several contributions  (see RH)
 * @author Roan Embrechts, virtual course support
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University (see history version 1.3)
 *
 * @package dokeos.dropbox
 *
 * @todo complete refactoring. Currently there are about at least 3 sql queries needed for every individual dropbox document.
 *			first we find all the documents that were sent (resp. received) by the user
 *			then for every individual document the user(s)information who received (resp. sent) the document is searched
 *			then for every individual document the feedback is retrieved
 * @todo 	the implementation of the dropbox categories could (on the database level) have been done more elegantly by storing the category
 *			in the dropbox_person table because this table stores the relationship between the files (sent OR received) and the users
==============================================================================
 */

/**
==============================================================================
					HISTORY
==============================================================================
Version 1.1
------------
- dropbox_init1.inc.php: changed include statements to require statements. This way if a file is not found, it stops the execution of a script instead of continuing with warnings.
- dropbox_init1.inc.php: the include files "claro_init_global.inc.php" & "debug.lib.inc.php" are first checked for their existence before including them. If they don't exist, in the .../include dir, they get loaded from the .../inc dir. This change is necessary because the UCL changed the include dir to inc.
- dropbox_init1.inc.php: the databasetable name in the variable $dropbox_cnf["introTbl"] is chnged from "introduction" to "tool_intro"
- install.php: after submit, checks if the database uses accueil or tool_list as a tablename
- index.php: removed the behaviour of only the teachers that are allowed to delete entries
- index.php: added field "lastUploadDate" in table dropbox_file to store information about last update when resubmiting a file
- dropbox.inc.php: added $lang["lastUpdated"]
- index.php: entries in received list show when file was last updated if it is updated
- index.php: entries in sent list show when file was last resent if it was resent
- dropbox_submit.php: add a unique id to every uploaded file
- index.php: add POST-variable to the upload form with overwrite data when user decides to overwrite the previous sent file with new file
- dropbox_submit.php: add sanity checks on POST['overwrite'] data
- index.php: remove title field in upload form
- dropbox_submit.php: remove use of POST['title'] variable
- dropbox_init1.inc.php: added $dropbox_cnf["version"] variable
- dropbox_class.inc.php: add $this->lastUploadDate to Dropbox_work class
- dropbox.inc.php: added $lang['emptyTable']
- index.php: if the received or sent list is empty, a message is displayed
- dropbox_download.php: the $file var is set equal to the title-field of the filetable. So not constructed anymore by substracting the username from the filename
- index.php: add check to see if column lastUploadDate exists in filetable
- index.php: moved javascripts from dropbox_init2.inc.php to index.php
- index.php: when specifying an uploadfile in the form, a checkbox allowing the user to overwrite a previously sent file is shown when the specified file has the same name as a previously uploaded file of that user.
- index.php: assign all the metadata (author, description, date, recipient, sender) of an entry in a list to the class="dropbox_detail" and add css to html-header
- index.php: assign all dates of entries in list to the class="dropbox_date" and add CSS
- index.php: assign all persons in entries of list to the class="dropbox_person" and add CSS
- dropbox.inc.php: added $lang['dropbox_version'] to indicate the lates version. This must be equal to the $dropbox_cnf['version'] variable.
- dropbox_init1.inc.php: if the newest lang file isn't loaded by claro_init_global.inc.php from the .../lang dir it will be loaded locally from the .../plugin/dropbox/ dir. This way an administrator must not install the dropbox.inc.php in the .../lang/english dir, but he can leave it in the local .../plugin/dropbox/ dir. However if you want to present multiple language translations of the file you must still put the file in the /lang/ dir, because there is no language management system inside the .../plugin/dropbox dir.
- mime.inc.php: created this file. It contains an array $mimetype with all the mimetypes that are used by dropbox_download.php to give hinst to the browser during download about content
- dropbox_download.php: remove https specific headers because they're not necessary
- dropbox_download.php: use application/octet-stream as the default mime and inline as the default Content-Disposition
- dropbox.inc.php: add lang vars for "order by" action
- dropbox_class.inc.php: add methods orderSentWork, orderReceivedWork en _cmpWork and propery _orderBy to class Dropbox_person to take care of sorting
- index.php: add selectionlist to headers of sent/received lists to select "order by" and add code to keep selected value in sessionvar.
- index.php: moved part of a <a> hyperlink to previous line to remove the underlined space between symbol and title of a work entry in the sent/received list
- index.php: add filesize info in sent/received lists
- dropbox_submit.php: resubmit prevention only for GET action, because it gives some annoying behaviour in POST situation: white screen in IE6

Version 1.2
-----------
- adapted entire dropbox tool so it can be used as a default tool in Dokeos 1.5
- index.php: add event registration to log use of tool in stats tables
- index.php: upload form checks for correct user selection and file specification before uploading the script
- dropbox_init1.inc.php: added dropbox_cnf["allowOverwrite"] to allow or disallow overwriting of files
- index.php: author name textbox is automatically filled in
- mailing functionality (see RH comments in code)
- allowStudentToStudent and allowJustUpload options (id.)
- help in separate window (id.)

Version 1.3 (Patrick Cool)
--------------------------
- sortable table
- categories
- fixing a security hole
- tabs (which can be disabled: see $dropbox_cnf['sent_received_tabs'])
- same action on multiple documents ([zip]download, move, delete)
- consistency with the docuements tool (open/download file, icons of documents, ...)
- zip download of complete folder

Version 1.4 (Yannick Warnier)
-----------------------------
- removed all self-built database tables names
==============================================================================
 */

/*
==============================================================================
		INIT SECTION
==============================================================================
*/
// the file that contains all the initialisation stuff (and includes all the configuration stuff)
require_once( "dropbox_init.inc.php");

// get the last time the user accessed the tool
if ($_SESSION[$_course['id']]['last_access'][TOOL_DROPBOX]=='') {
	$last_access=get_last_tool_access(TOOL_DROPBOX,$_course['code'],$_user['user_id']);
	$_SESSION[$_course['id']]['last_access'][TOOL_DROPBOX]=$last_access;
} else {
	$last_access=$_SESSION[$_course['id']]['last_access'][TOOL_DROPBOX];
}

// do the tracking
event_access_tool(TOOL_DROPBOX);

//this var is used to give a unique value to every page request. This is to prevent resubmiting data
$dropbox_unid = md5( uniqid( rand( ), true));

// Tool introduction
Display::display_introduction_section(TOOL_DROPBOX);

/*if (isset($_GET['action']) && $_GET['action']=="download")
{		
	$my_get_id=Security::remove_XSS($_GET['id']);
	event_download($my_get_id);	
	$course_name = explode("=",api_get_cidReq());
	$full_file_name = api_get_path(SYS_COURSE_PATH).$course_name[1].'/document/mindmaps/'.$_GET['id'];
	DocumentManager::file_send_for_download($full_file_name,true,$_GET['id']);
	exit;	
}*/

/*
-----------------------------------------------------------
	ACTIONS: add a dropbox file, add a dropbox category.
-----------------------------------------------------------
*/

if(isset($_REQUEST['dispaction']))
{

$dispaction 				= $_REQUEST['dispaction']; 
$updateRecordsArray 	= $_REQUEST['order'];

$tbl_documents = Database::get_course_table(TABLE_DOCUMENT);

if ($dispaction == "updateRecordsListings"){	

	$listingCounter = 1;
	$disp = explode(",",$updateRecordsArray);
	$cntdispid = sizeof($disp);
	for($i=0;$i<$cntdispid;$i++)	{
	
		$dispid = substr($disp[$i],8,strlen($disp[$i]));
		$query = "UPDATE $tbl_documents SET display_order = " . $listingCounter . " WHERE id = " . $dispid;
		$result = api_sql_query($query, __FILE__, __LINE__);		
		$listingCounter = $listingCounter + 1;
	}
	echo '<script type="text/javascript">window.location.href="index.php?'.api_get_cidReq().'&view='.Security::remove_XSS($_REQUEST['view']).'"</script>';
}
}

if ( $_GET['view']=='received' OR $dropbox_cnf['sent_received_tabs']==false) {
	//echo '<h3>'.get_lang('ReceivedFiles').'</h3>';

	// This is for the categories
	if (isset($_GET['view_received_category']) AND $_GET['view_received_category']<>'') {
		$view_dropbox_category_received=Security::remove_XSS($_GET['view_received_category']);
	} else {
		$view_dropbox_category_received=0;
	}


	/* *** Menu Received *** */
	
	if (api_get_session_id()==0) {
		echo '<div class="actions">';
	/*	if ($view_dropbox_category_received<>0  && api_is_allowed_to_session_edit(false,true)) {
			echo get_lang('CurrentlySeeing').': <strong>'.$dropbox_categories[$view_dropbox_category_received]['cat_name'].'</strong> ';
			echo '<a href="'.api_get_self().'?'.api_get_cidreq().'&view_received_category=0&amp;view_sent_category='.Security::remove_XSS($_GET['view_sent_category']).'&amp;view='.Security::remove_XSS($_GET['view']).'">'.Display::return_icon('folder_up.gif',get_lang('Up')).' '.get_lang('Root')."</a>\n";
	        $movelist[0] = 'Root'; // move_received selectbox content
		} else {
		 //   echo '<a href="'.api_get_self().'?'.api_get_cidreq().'&action=addreceivedcategory&view='.Security::remove_XSS($_GET['view']).'">'.Display::return_icon('folder_new1.png',get_lang('AddNewCategory')).' '.get_lang('AddNewCategory').'</a>';
		}
		echo build_folder().'</td>';*/
		display_mindmap_tabs();
		echo '<a href="http://www.dokeos.com/mind/" target="_blank" style="float:right;">'.Display::return_icon('pixel.gif',get_lang('DownloadDokeosMind'),array('class'=>'mindmapplaceholdericon toolactiondownloadmind')).' '.get_lang('DownloadDokeosMind')."</a>&nbsp;\n";
		echo '</div>';
	} else {		
		 if (api_is_allowed_to_session_edit(false,true)) {
		 	echo '<div class="actions">';
		/*	if ($view_dropbox_category_received<>0  && api_is_allowed_to_session_edit(false,true)) {
				echo get_lang('CurrentlySeeing').': <strong>'.$dropbox_categories[$view_dropbox_category_received]['cat_name'].'</strong> ';
				echo '<a href="'.api_get_self().'?'.api_get_cidreq().'&view_received_category=0&amp;view_sent_category='.Security::remove_XSS($_GET['view_sent_category']).'&amp;view='.Security::remove_XSS($_GET['view']).'">'.Display::return_icon('folder_up.gif',get_lang('Up')).' '.get_lang('Root')."</a>\n";
		        $movelist[0] = 'Root'; // move_received selectbox content
			} else {
			//    echo '<a href="'.api_get_self().'?'.api_get_cidreq().'&action=addreceivedcategory&view='.Security::remove_XSS($_GET['view']).'">'.Display::return_icon('folder_new1.png',get_lang('AddNewCategory')).' '.get_lang('AddNewCategory').'</a>';
			}	
			echo build_folder().'</td>';*/
			echo '<a href="http://www.dokeos.com/mind/" target="_blank" style="float:right;">'.Display::return_icon('pixel.gif',get_lang('DownloadDokeosMind'),array('class'=>'mindmapplaceholdericon toolactiondownloadmind')).' '.get_lang('DownloadDokeosMind')."</a>&nbsp;\n";
			echo '</div>';
		 }
	}
}
if (!$_GET['view'] OR $_GET['view']=='sent' OR $dropbox_cnf['sent_received_tabs']==false) {
	//echo '<h3>'.get_lang('SentFiles').'</h3>';
	// This is for the categories
	if (isset($_GET['view_sent_category']) AND $_GET['view_sent_category']<>'') {
		$view_dropbox_category_sent=$_GET['view_sent_category'];
	} else {
		$view_dropbox_category_sent=0;
	}

	/* *** Menu Sent *** */
	if (api_get_session_id()==0) {
		echo '<div class="actions">';
		if($_GET['action'] == 'add' || $_GET['action'] == 'viewfeedback') {
			echo "<a href=\"index.php?".api_get_cidreq()."\">".Display::return_icon('pixel.gif', get_lang('Mindmap'), array('class' => 'toolactionplaceholdericon toolactionback')).get_lang('Mindmap').'</a>' . PHP_EOL;
		}
		if (empty($_GET['view_sent_category'])) {
			echo '<a href="'.api_get_self().'?'.api_get_cidreq().'&amp;view='.Security::remove_XSS($_GET['view']).'&amp;action=add&amp;curdirpath='.$_GET['curdirpath'].'">'.Display::return_icon('pixel.gif',get_lang('UploadMap'),array("class" => "mindmapplaceholdericon toolactionmindmap")).' '.get_lang('UploadMap')."</a>&nbsp;\n";
		}
		display_mindmap_tabs();
		echo '<a href="http://www.dokeos.com/mind/" target="_blank" style="float:right;">'.Display::return_icon('pixel.gif',get_lang('DownloadDokeosMind'),array("class" => "mindmapplaceholdericon toolactiondownloadmind")).' '.get_lang('DownloadDokeosMind').'</a>' . PHP_EOL;
		echo '</div>';
	} else {
		 if (api_is_allowed_to_session_edit(false,true)) {
		 	echo '<div class="actions">';
			if($_GET['action'] == 'add' || $_GET['action'] == 'viewfeedback')
			{
			echo "<a href=\"index.php?".api_get_cidreq()."\">".Display::return_icon('pixel.gif', get_lang('Mindmap'), array('class' => 'toolactionplaceholdericon toolactionback')).get_lang('Mindmap')."</a>&nbsp;\n";
			}
			if (empty($_GET['view_sent_category'])) {
				echo "<a href=\"".api_get_self()."?".api_get_cidreq()."&view=".Security::remove_XSS($_GET['view'])."&amp;action=add\">".Display::return_icon('pixel.gif',get_lang('UploadMap'),array('class'=>'mindmapplaceholdericon toolactionmindmap')).' '.get_lang('UploadMap')."</a>&nbsp;\n";
			}
		/*	echo build_folder();
			if ($view_dropbox_category_sent<>0) {
				echo get_lang('CurrentlySeeing').': <strong>'.$dropbox_categories[$view_dropbox_category_sent]['cat_name'].'</strong> ';
				echo '<a href="'.api_get_self().'?'.api_get_cidreq().'&view_received_category='.Security::remove_XSS($_GET['view_received_category']).'&amp;view_sent_category=0&amp;view='.Security::remove_XSS($_GET['view']).'">'.Display::return_icon('folder_up.gif',get_lang('Up')).' '.get_lang('Root')."</a>\n";
			} else {
				echo "<a href=\"".api_get_self()."?".api_get_cidreq()."&view=".Security::remove_XSS($_GET['view'])."&amp;action=addsentcategory\">".Display::return_icon('folder_new1.png',get_lang('NewFolder'))." ".get_lang('NewFolder')."</a>\n";
			}*/
			echo '<a href="http://www.dokeos.com/mind/" target="_blank" style="float:right;">'.Display::return_icon('pixel.gif',get_lang('DownloadDokeosMind'),array('class'=>'mindmapplaceholdericon toolactiondownloadmind')).' '.get_lang('DownloadDokeosMind')."</a>&nbsp;\n";
			echo '</div>'; 	
		 }
	}
	
}

// start the content div
echo '<div id="content">';

// *** display the form for adding a new dropbox item. ***
if ($_GET['action']=="add") {
	if (api_get_session_id()!=0 && api_is_allowed_to_session_edit(false,true)==false) {		 
		api_not_allowed();
	}
	display_add_form();
}

if (isset($_POST['submitWork'])) {
	$check = Security::check_token();
	if ($check) {
		Display :: display_confirmation_message(store_add_dropbox());
		//include_once('dropbox_submit.php');
	}
}

if(isset($_GET['curdirpath']) && !empty($_GET['curdirpath']))
{
	$curdirpath = '/mindmaps/'.Security::remove_XSS($_GET['curdirpath']).'/';
}
else
{
	$curdirpath = '/mindmaps/';
}

$course_name = explode("=",api_get_cidReq());	
//$src_path = api_get_path(WEB_COURSE_PATH).$course_name[1].'/document/mindmaps/thumbs/';
$src_path = api_get_path(SYS_COURSE_PATH).$course_name[1].'/document'.$curdirpath;

$tbl_documents = Database::get_course_table(TABLE_DOCUMENT);
$propTable = Database::get_course_table(TABLE_ITEM_PROPERTY);

// *** display the form for adding a category ***
if ($_GET['action']=="addreceivedcategory" or $_GET['action']=="addsentcategory") {
	if (api_get_session_id()!=0 && api_is_allowed_to_session_edit(false,true)==false) {		 
		api_not_allowed();
	}
	display_addcategory_form($_POST['category_name'],'',$_GET['action']);
}

// *** editing a category: displaying the form ***
if ($_GET['action']=='editcategory' and isset($_GET['id'])) {
	if (api_get_session_id()!=0 && api_is_allowed_to_session_edit(false,true)==false) {		 
		api_not_allowed();
	}	
	if (!$_POST) {
		if (api_get_session_id()!=0 && api_is_allowed_to_session_edit(false,true)==false) {		 
			api_not_allowed();
		}
		display_addcategory_form('',$_GET['id'],'editcategory');
	}
}

// *** storing a new or edited category ***
if (isset($_POST['StoreCategory'])) {
	if (api_get_session_id()!=0 && api_is_allowed_to_session_edit(false,true)==false) {		 
		api_not_allowed();
	}	
	$return_information = store_addcategory();
	if( $return_information['type'] == 'confirmation')
	{
	//	Display :: display_confirmation_message($return_information['message']);
	}
	if( $return_information['type'] == 'error')
	{
		Display :: display_error_message(get_lang('FormHasErrorsPleaseComplete').'<br />'.$return_information['message']);
		display_addcategory_form($_POST['category_name'],$_POST['edit_id'],$_POST['action']);
	}

}

// *** Move a File ***
if (($_GET['action']=='movesent' OR $_GET['action']=='movereceived') AND isset($_GET['move_id'])) {
	if (api_get_session_id()!=0 && api_is_allowed_to_session_edit(false,true)==false) {		 
		api_not_allowed();
	}	
	display_move_form(str_replace('move','',$_GET['action']), $_GET['move_id'], get_dropbox_categories(str_replace('move','',$_GET['action'])));
}
if ($_POST['do_move']) {
	Display :: display_confirmation_message(store_move($_POST['id'], $_POST['move_target'], $_POST['part']));
}

// *** Delete a file ***
if (($_GET['action']=='deletereceivedfile' OR $_GET['action']=='deletesentfile') AND isset($_GET['id']) AND is_numeric($_GET['id'])) {
	if (api_get_session_id()!=0 && api_is_allowed_to_session_edit(false,true)==false) {		 
		api_not_allowed();
	}	
	$dropboxfile=new Dropbox_Person( $_user['user_id'], $is_courseAdmin, $is_courseTutor);
	if ($_GET['action']=='deletereceivedfile') {
		$dropboxfile->deleteReceivedWork($_GET['id']);
		$message=get_lang('ReceivedFileDeleted');
	}
	if ($_GET['action']=='deletesentfile') {
		$dropboxfile->deleteSentWork($_GET['id']);
		$message=get_lang('SentFileDeleted');
	}
//	Display :: display_confirmation_message($message);
}

// *** Delete a category ***
if (($_GET['action']=='deletereceivedcategory' OR $_GET['action']=='deletesentcategory') AND isset($_GET['id']) AND is_numeric($_GET['id'])) {
	if (api_get_session_id()!=0 && api_is_allowed_to_session_edit(false,true)==false) {		 
		api_not_allowed();
	}	
	$message=delete_category($_GET['action'], $_GET['id']);
//	Display :: display_confirmation_message($message);
}

// *** Do an action on multiple files ***
// only the download has is handled separately in dropbox_init_inc.php because this has to be done before the headers are sent
// (which also happens in dropbox_init.inc.php

if (!isset($_POST['feedback']) && (strstr($_POST['action'],'move_received') OR
        $_POST['action'] == 'delete_received' OR $_POST['action'] == 'download_received' OR
        $_POST['action'] == 'delete_sent' OR $_POST['action'] == 'download_sent'))
{
	$display_message=handle_multiple_actions();
	Display :: display_normal_message($display_message);
}

// *** Store Feedback ***
if ($_POST['feedback']) {	
	if (api_get_session_id()!=0 && api_is_allowed_to_session_edit(false,true)==false) {		 
		api_not_allowed();
	}
	$display_message = store_feedback();
	Display :: display_normal_message($display_message);
}


// *** Error Message ***
if (isset($_GET['error']) AND !empty($_GET['error'])) {
	Display :: display_normal_message(get_lang($_GET['error']));
}



if ($_GET['action']!="add") {
// getting all the categories in the dropbox for the given user
$dropbox_categories=get_dropbox_categories();
// creating the arrays with the categories for the received files and for the sent files
foreach ($dropbox_categories as $category) {
	if ($category['received']=='1') {
		$dropbox_received_category[]=$category;
	}
	if ($category['sent']=='1') {
		$dropbox_sent_category[]=$category;
	}
}

// ACTIONS
if($_GET['action'] == 'viewfeedback')
{
	global $dropbox_cnf;
		
	// getting the information of the document
	$query = "SELECT path FROM $tbl_documents WHERE id=".Database::escape_string(Security::Remove_XSS($_GET['id']));
	$result = api_sql_query($query, __FILE__, __LINE__);
	while ($row = Database :: fetch_array($result)) 
	{
		$image_path = $row['path'];
	}	
	$img_path = api_get_path(SYS_COURSE_PATH).$course_name[1].'/document'.$image_path;
	list($width,$height) = getimagesize($img_path);
	
	$image =  api_get_path(WEB_COURSE_PATH).$course_name[1].'/document'.$image_path;
	
	if($width > 900)
	{
		$target_width = 850;		
		$target_height = $height;
		$new_sizes = api_resize_image($img_path, $target_width, $target_height);
		$new_width = $new_sizes['width'];
		$new_height = $new_sizes['height'];
	}
	else
	{
		$new_width = $width;
		$new_height = $height;
	}
	echo '<div class="rounded center border"><img src="'.$image.'" width="'.$new_width.'" height="'.$new_height.'"></div>';

	// displaying the feedback messages
	$sql = "SELECT author_user_id, DATE_FORMAT(feedback_date,'%Y-%m-%d') AS feedback_date, feedback  FROM ".$dropbox_cnf['tbl_feedback']." WHERE file_id=".Database::escape_string(Security::Remove_XSS($_GET['id']));
	$result = api_sql_query($sql, __FILE__, __LINE__);
	$num_rows = Database::num_rows($result);
	if($num_rows <> 0)
	{
		echo '<div class="rounded border margintop padding">';
		while ($row = Database :: fetch_array($result)) 
		{			
			$output = '';		
			$output .=  display_user_link($row['author_user_id']);		
			$output .= '&nbsp;&nbsp;'.$row['feedback_date'].'<br />';
			$output .= '<div class="paddingtop">'.nl2br($row['feedback']).'</div><hr size="1" color="#ccc" noshade/>';
			echo '<tr><td colspan="2">'.$output.'</td></tr>';
			$i++;
		}
		echo '</div>';
	}
	echo '<form name="form_tablename" method="POST" action="'.api_get_self().'?'.api_get_cidReq().'&action=viewfeedback&id='.$_GET['id'].'">'.get_lang('AddNewComment').'<br /><textarea name="feedback" rows="3" cols="100"></textarea><button type="submit" class="save" name="store_feedback" value="'.get_lang('ok').' onclick="document.form_tablename.attributes.action.value = document.location;"">'.get_lang('SaveComment').'</button></form>';
}
else
{

/*
-----------------------------------------------------------
	RECEIVED FILES
-----------------------------------------------------------
*/
if ($_GET['view']=='received' OR $dropbox_cnf['sent_received_tabs']==false) {
	//echo '<h3>'.get_lang('ReceivedFiles').'</h3>';

	// This is for the categories
	$return = '';			
	
	$sql = "SELECT * FROM $tbl_documents doc,$propTable prop WHERE doc.id = prop.ref AND prop.tool = '".TOOL_DOCUMENT."' AND doc.filetype = 'file' AND doc.path LIKE '".$curdirpath."%' AND doc.path NOT LIKE '".$curdirpath."%/%' AND prop.visibility = 1 AND prop.to_user_id = ".$_user['user_id'] ." AND prop.insert_user_id <> ".$_user['user_id'];	
	
	$result = api_sql_query($sql, __FILE__, __LINE__);
	$arrLP = array ();	
	while ($row = Database :: fetch_array($result)) {					
		$arrLP[] = array (
			'id' => $row['id'],				
			'title' => $row['title']				
		);
	}						
		
	$return .= '<table style="width: 100%; background-color:#fff;"><tr><td colspan="2"><div id="GalleryContainer">';

	for ($i = 0; $i < count($arrLP); $i++) {				
			$id = $arrLP[$i]['id'];			
			$title = $arrLP[$i]['title'];			
			$tmptitle = pathinfo($title, PATHINFO_EXTENSION);
			if($tmptitle == 'png' || $tmptitle == 'gif' || $tmptitle == 'jpg' || $tmptitle == 'jpeg')
			{
				$return .= '<div class="imageBox" id="imageBox'.$id.'">
				<div class="imageBox_theImage"><a  href="'.api_get_self().'?'.api_get_cidreq().'&view_received_category='.Security::remove_XSS($_GET['view_received_category']).'&amp;view_sent_category='.Security::remove_XSS($_GET['view_sent_category']).'&amp;view='.Security::remove_XSS($_GET['view']).'&amp;action=viewfeedback&amp;id='.$id.'"><table height="100%"><tr><td><img src="thumb.php?file='.$src_path.$title.'&size=240"></td></tr></table></a></div><div><a href="'.api_get_self().'?'.api_get_cidreq().'&view_received_category='.Security::remove_XSS($_GET['view_received_category']).'&amp;view_sent_category='.Security::remove_XSS($_GET['view_sent_category']).'&amp;view='.Security::remove_XSS($_GET['view']).'&amp;action=viewfeedback&amp;id='.$id.'">'.Display::return_icon('pixel.gif','',array('class'=>'mindmapplaceholdericon toolactioncomments','style'=>'margin-left:185px;width:25px;height:25px;')).'</a><a href="dropbox_download.php?'.api_get_cidreq().'&id='.$id.'&amp;action=download">'.Display::return_icon('pixel.gif','',array('class'=>'actionplaceholdericon forcedownload','style'=>'padding-left:10px;')).'<a href="'.api_get_self().'?'.api_get_cidreq().'&view_received_category='.Security::remove_XSS($_GET['view_received_category']).'&amp;view_sent_category='.Security::remove_XSS($_GET['view_sent_category']).'&amp;view='.Security::remove_XSS($_GET['view']).'&amp;action=deletereceivedfile&amp;id='.$id.'" onclick="return confirmation(\''.$title.'\');">'.Display::return_icon('pixel.gif','',array('class'=>'actionplaceholdericon actiondelete','style'=>'padding-left:10px;')).'</a></div></div>';
			}
		}		
		$return .= '</div>
		<div id="insertionMarker">
		<img src="../img/marker_top.gif">
		<img src="../img/marker_middle.gif" id="insertionMarkerLine">
		<img src="../img/marker_bottom.gif">
		</div>
		<div id="dragDropContent">
		</div><div id="debug" style="clear:both">
		</div>';

		$return .= '</td></tr></table>';

		echo $return;
}


/*
-----------------------------------------------------------
	SENT FILES
-----------------------------------------------------------
*/
if (!$_GET['view'] OR $_GET['view']=='sent' OR $dropbox_cnf['sent_received_tabs']==false) {
	//echo '<h3>'.get_lang('SentFiles').'</h3>';		
	
		$return = '';			
		$arrLP = array ();	

		$sql1 = "(SELECT distinct(title) AS title,id,display_order FROM $tbl_documents doc,$propTable prop WHERE doc.id = prop.ref AND prop.tool = '".TOOL_DOCUMENT."' AND doc.filetype = 'file' AND doc.path LIKE '".$curdirpath."%' AND doc.path NOT LIKE '".$curdirpath."%/%' AND prop.visibility = 1 AND prop.to_user_id IS NULL)";
		$sql2 = "(SELECT distinct(title) AS title,id,display_order FROM $tbl_documents doc,$propTable prop WHERE doc.id = prop.ref AND prop.tool = '".TOOL_DOCUMENT."' AND doc.filetype = 'file' AND doc.path LIKE '".$curdirpath."%' AND doc.path NOT LIKE '".$curdirpath."%/%' AND prop.visibility = 1  AND prop.insert_user_id = ".$_user['user_id'] . ")";
		$sql = $sql1." UNION ".$sql2." ORDER BY display_order";
		
		$result = api_sql_query($sql, __FILE__, __LINE__);		
		while ($row = Database :: fetch_array($result)) {					
			$arrLP[] = array (
				'id' => $row['id'],				
				'title' => $row['title']				
			);
		}						
		$return .= '<table style="width: 100%; background-color:#fff;"><tr><td colspan="2"><div id="GalleryContainer">';
		for ($i = 0; $i < count($arrLP); $i++) {				
			$id = $arrLP[$i]['id'];			
			$title = $arrLP[$i]['title'];			
			$tmptitle = pathinfo($title, PATHINFO_EXTENSION);
			if($tmptitle == 'png' || $tmptitle == 'gif' || $tmptitle == 'jpg' || $tmptitle == 'jpeg')
			{

                $file_id = Security::remove_XSS($id);
                $allowed_to_download = check_if_user_is_allowed_to_download_file($file_id, api_get_user_id());
				$return .= '<div class="imageBox" id="imageBox'.$id.'">';
                
				$return .= '<div class="imageBox_theImage" style="text-align:center;">
                    <a  href="'.api_get_self().'?'.api_get_cidreq().'&view_received_category='.Security::remove_XSS($_GET['view_received_category']).'&amp;view_sent_category='.Security::remove_XSS($_GET['view_sent_category']).'&amp;view='.Security::remove_XSS($_GET['view']).'&amp;action=viewfeedback&amp;id='.$id.'">
                    <table height="100%">
                    <tr><td>
                    <img src="thumb.php?file='.$src_path.$title.'&size=240" />
                    </td></tr>
                    </table>
                    </a>
                    </
                    div>';
                
				$return .= '<a href="'.api_get_self().'?'.api_get_cidreq().'&view_received_category='.Security::remove_XSS($_GET['view_received_category']).'&amp;view_sent_category='.Security::remove_XSS($_GET['view_sent_category']).'&amp;view='.Security::remove_XSS($_GET['view']).'&amp;action=viewfeedback&amp;id='.$id.'">
                      '.Display::return_icon('pixel.gif',get_lang('Comments'),array("class" => "mindmapplaceholdericon toolactioncomments","style"=>"margin-left:185px;width:25px;height:25px;")).'</a>';
                      if ($allowed_to_download === true || api_is_allowed_to_edit()) {
                        $return .= '<a href="dropbox_download.php?'.api_get_cidreq().'&id='.$id.'&amp;action=download">
                        '.Display::return_icon('pixel.gif',get_lang('Edit'),array("class" => "actionplaceholdericon forcedownload","style"=>"margin-left:10px;")).'</a>';
                        
                        $return .= '<a href="'.api_get_self().'?'.api_get_cidreq().'&view_received_category='.Security::remove_XSS($_GET['view_received_category']).'&amp;view_sent_category='.Security::remove_XSS($_GET['view_sent_category']).'&amp;view='.Security::remove_XSS($_GET['view']).'&amp;action=deletereceivedfile&amp;id='.$id.'" onclick="return confirmation(\''.$title.'\');">
                         '.Display::return_icon('pixel.gif',get_lang('Delete'),array("class" => "actionplaceholdericon actiondelete","style"=>"margin-left:10px")).'</a>';
                      }
                      $return .= '</div>
                   </div>';
            }
		}		
		$return .= '</div>
		<div id="insertionMarker">
		<img src="../img/marker_top.gif">
		<img src="../img/marker_middle.gif" id="insertionMarkerLine">
		<img src="../img/marker_bottom.gif">
		</div>
		<div id="dragDropContent">
		</div><div id="debug" style="clear:both">
		</div>';

		$return .= '</td></tr></table>';
		echo $return;
  }
}

}// else end by breetha for feedback

echo '<script>
function changedir()
{	
	var foldername = document.createdir.mindmapfolder.value;
	window.location.href = "'.api_get_self().'?'.api_get_cidReq().'&view=sent&curdirpath="+foldername;
}
</script>';

// close the content div
echo '</div>';

// Actions bar
echo '<div class="actions">';
echo '</div>';

// Display the footer
Display::display_footer();

/**
 * This functions display the inbox and outbox links
 */ 
function display_mindmap_tabs(){
	global $dropbox_cnf; 

	if ($dropbox_cnf['sent_received_tabs']) {
	?>
	<div id="tabbed_menu">
		<ul id="tabbed_menu_tabs">
			<li><a href="index.php?<?php echo api_get_cidreq();?>&view=sent" <?php if (!$_GET['view'] OR $_GET['view']=='sent'){echo 'class="active"';}?>><?php echo Display::return_icon(('pixel.gif'),get_lang('MapsOut'),array("class" => "mindmapplaceholdericon toolactionup")) .get_lang('MapsOut'); ?></a></li>
			<li><a href="index.php?<?php echo api_get_cidreq();?>&view=received" <?php if ($_GET['view']=='received'){echo 'class="active"';}?> ><?php echo Display::return_icon(('pixel.gif'),get_lang('MapsIn'),array("class" => "mindmapplaceholdericon toolactiondown32")) .get_lang('MapsIn'); ?></a></li>
		</ul>
	</div>
	<?php
	}
}

function build_folder($curdirpath)
{	
		$tbl_documents = Database::get_course_table(TABLE_DOCUMENT);
		$propTable = Database::get_course_table(TABLE_ITEM_PROPERTY);
		$folders = array();
		$sql = "SELECT * FROM $tbl_documents doc,$propTable prop WHERE doc.id = prop.ref AND prop.tool = '".TOOL_DOCUMENT."' AND doc.filetype = 'folder' AND doc.path LIKE '/mindmaps%' AND prop.visibility = 1";	
		$result = api_sql_query($sql, __FILE__, __LINE__);
		$numrows = Database :: num_rows($result);
		if($numrows <> 0)
		{
			while ($row = Database :: fetch_array($result)) 	
				{
					$folders[] = $row['title'];
				}
		}		
		$return = '<form style="display:inline;" name="createdir" method="post"><select name="mindmapfolder" onchange="javascript:changedir()">';
		$return .= '<option value=""';
		if(empty($curdirpath))
		{
			$return .= 'selected';
		}
		elseif($curdirpath == 'Home')
		{
			$return .= 'selected';
		}
		$return .= '>Home</option>';
		foreach ($folders as $folder)
		{
			$return .= '<option value="'.$folder.'"';
			if($curdirpath == $folder)
			{
				$return .= 'selected';
			}
			$return .= '>'.$folder.'</option>';
		}
		$return .= '</select></form></td>';

		return $return;
}
