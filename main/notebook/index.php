<?php  //$id: $

/* For licensing terms, see /dokeos_license.txt */

/**
 * @package dokeos.glossary
 * @author Christian Fasanando, initial version
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University, Belgium, refactoring and tighter integration in Dokeos
 */

// name of the language file that needs to be included
$language_file = array('notebook');

// including the global dokeos file
require_once '../inc/global.inc.php';

// the section (tabs)
$this_section=SECTION_COURSES;

// notice for unauthorized people.
api_protect_course_script(true);

// including additional libraries
require_once api_get_path(LIBRARY_PATH).'formvalidator/FormValidator.class.php';

// additional javascript
$htmlHeadXtra[] = javascript_notebook();

// setting the tool constants
$tool = TOOL_NOTEBOOK;

// tracking
event_access_tool(TOOL_NOTEBOOK);

// tool name
if ( isset($_GET['action']) && $_GET['action'] == 'addnote')
{
	$tool = 'NoteAddNew';
	$interbreadcrumb[] = array ("url"=>"index.php", "name"=> get_lang('Notebook'));
}
if ( isset($_GET['action']) && $_GET['action'] == 'editnote')
{
	$tool = 'ModifyNote';
	$interbreadcrumb[] = array ("url"=>"index.php", "name"=> get_lang('Notebook'));
}

// displaying the header
Display::display_tool_header(get_lang(ucfirst($tool)));

// Tool introduction
Display::display_introduction_section(TOOL_NOTEBOOK);


if (!empty($_GET['isStudentView'])) {
		display_notes();
		exit;
	}

// Action handling: Adding a note
if (isset($_GET['action']) && $_GET['action'] == 'addnote') {
	if ($_GET['add'] == 'submit') {
	//	$note_title = $_POST['note_title'];
		$note_comment = $_POST['note_comment'];

		$tmp_comment = explode("#cont#",$note_comment);	
		// Clean html tags
		$note_title = $tmp_comment[1];
		if(strlen(strstr($note_title,'<img')) > 0)
		{
			$today = date('M d, h:i');
			$note_title = 'Notebook Image_'.$today;
		}
		else
		{
		$tmp_title = Security::remove_XSS(strip_tags($note_title));			
		$note_title =  substr($tmp_title,8,50);		
		}
     //   $note_title = strip_tags($note_title);
        $note_title = str_replace(array('\r\n','\n\r', '\n', '\r', '&nbsp;'), '', $note_title);
        $note_title = trim($note_title);

		save_note($note_title,$note_comment);
		echo '<script>window.location.href="'.api_get_self().'?'.api_get_cidReq().'"</script>';
	} else {
	  display_notes();
	}
}

// Action handling: Editing a note
else if (isset($_GET['action']) && $_GET['action'] == 'editnote' && is_numeric($_GET['notebook_id']))
{
	if($_GET['edit'] == 'submit')
	{		
	//	$note_title = $_POST['note_title'];
		$note_comment = $_POST['note_comment'];
		$notebook_id = $_POST['notebook_id'];
		$tmp_comment = explode("#cont#",$note_comment);		
		$note_title = $tmp_comment[1];
		if(strlen(strstr($note_title,'<img')) > 0)
		{
			$today = date('M d, h:i');
			$note_title = 'Notebook Image_'.$today;
		}
		else
		{
		$tmp_title = Security::remove_XSS(strip_tags($note_title));			
		$note_title =  substr($tmp_title,8,50);		
		}	
		update_note($notebook_id,$note_title,$note_comment);
		echo '<script>window.location.href="'.api_get_self().'?'.api_get_cidReq().'&start='.$_GET['start'].'"</script>';
	}
	else
	{
	display_notes();
	}
}

// Action handling: deleting a note
else if (isset($_GET['action']) && $_GET['action'] == 'deletenote' && is_numeric($_GET['notebook_id']))
{
	delete_note(Security::remove_XSS($_GET['notebook_id']));
	//display_notes();
	echo '<script>window.location.href="'.api_get_self().'?'.api_get_cidReq().'&start='.$_GET['start'].'"</script>';
}

// Action handling: changing the view (sorting order)
else if ($_GET['action'] == 'changeview' AND in_array($_GET['view'],array('creation_date','update_date', 'title')))
{
	switch ($_GET['view'])
	{
		case 'creation_date':
			if (!$_GET['direction'] OR $_GET['direction'] == 'ASC')
			{
				Display::display_confirmation_message(get_lang('NotesSortedByCreationDateAsc'));
			}
			else
			{
				Display::display_confirmation_message(get_lang('NotesSortedByCreationDateDESC'));
			}
			break;
		case 'update_date':
			if (!$_GET['direction'] OR $_GET['direction'] == 'ASC')
			{
				Display::display_confirmation_message(get_lang('NotesSortedByUpdateDateAsc'));
			}
			else
			{
				Display::display_confirmation_message(get_lang('NotesSortedByUpdateDateDESC'));
			}
			break;
		case 'title':
			if (!$_GET['direction'] OR $_GET['direction'] == 'ASC')
			{
				Display::display_confirmation_message(get_lang('NotesSortedByTitleAsc'));
			}
			else
			{
				Display::display_confirmation_message(get_lang('NotesSortedByTitleDESC'));
			}
			break;
	}
	$_SESSION['notebook_view'] = $_GET['view'];
	display_notes();
} else {
	display_notes();
}

// secondary actions
echo '<div class="actions"> </div>';

// footer
Display::display_footer();

/**
 * a little bit of javascript to display a prettier warning when deleting a note
 *
 * @return unknown
 *
 * @author Patrick Cool <patrick.cool@ugent.be>, Ghent University, Belgium
 * @version januari 2009, dokeos 1.8.6
 */
function javascript_notebook()
{
	return "<script type=\"text/javascript\">
			function confirmation (name)
			{
				if (confirm(\" ". get_lang("NoteConfirmDelete") ." \"+ name + \" ?\"))
					{return true;}
				else
					{return false;}
			}
			</script>";
}

/**
 * This functions stores the note in the database
 *
 * @param array $values
 *
 * @author Christian Fasanando <christian.fasanando@dokeos.com>
 * @author Patrick Cool <patrick.cool@ugent.be>, Ghent University, Belgium
 * @version januari 2009, dokeos 1.8.6
 */
function save_note($note_title,$note_comment) {
	// Database table definition
	$t_notebook = Database :: get_course_table(TABLE_NOTEBOOK);
	
	$sql = "INSERT INTO $t_notebook (user_id, course, session_id, title, description, creation_date,update_date,status)
			VALUES(
				'".Database::escape_string(api_get_user_id())."',
				'".Database::escape_string(api_get_course_id())."',
				'".Database::escape_string($_SESSION['id_session'])."',
				'".Database::escape_string(Security::remove_XSS($note_title))."',
				'".Database::escape_string(Security::remove_XSS(stripslashes(api_html_entity_decode($note_comment)),COURSEMANAGERLOWSECURITY))."',
				'".Database::escape_string(date('Y-m-d H:i:s'))."',
				'".Database::escape_string(date('Y-m-d H:i:s'))."',
				'0')";
	$result = Database::query($sql, __FILE__, __LINE__);
	$id = Database::insert_id();
	if ($id > 0) {
		//insert into item_property
		api_item_property_update(api_get_course_info(), TOOL_NOTEBOOK, $id, 'NotebookAdded', api_get_user_id());
	}

	// display the feedback message
	//Display::display_confirmation_message(get_lang('NoteAdded'));
}

function get_note_information($notebook_id) {
	// Database table definition
	$t_notebook = Database :: get_course_table(TABLE_NOTEBOOK);

	$sql = "SELECT 	notebook_id 		AS notebook_id,
					title				AS note_title,
					description 		AS note_comment,
			   		session_id			AS session_id
			   FROM $t_notebook
			   WHERE notebook_id = '".Database::escape_string($notebook_id)."' ";
	$result = Database::query($sql, __FILE__, __LINE__);
	return Database::fetch_array($result);
}

/**
 * This functions updates the note in the database
 *
 * @param array $values
 *
 * @author Christian Fasanando <christian.fasanando@dokeos.com>
 * @author Patrick Cool <patrick.cool@ugent.be>, Ghent University, Belgium
 * @version januari 2009, dokeos 1.8.6
 */
function update_note($notebook_id,$note_title,$note_comment) {
	// Database table definition
	$t_notebook = Database :: get_course_table(TABLE_NOTEBOOK);

	$sql = "UPDATE $t_notebook SET
				user_id = '".Database::escape_string(api_get_user_id())."',
				course = '".Database::escape_string(api_get_course_id())."',
				session_id = '".Database::escape_string($_SESSION['id_session'])."',
				title = '".Database::escape_string(Security::remove_XSS($note_title))."',
				description = '".Database::escape_string(Security::remove_XSS(stripslashes(api_html_entity_decode($note_comment)),COURSEMANAGERLOWSECURITY))."',
				update_date = '".Database::escape_string(date('Y-m-d H:i:s'))."'
			WHERE notebook_id = '".Database::escape_string($notebook_id)."'";
	$result = Database::query($sql, __FILE__, __LINE__);

	//update item_property (update)
	api_item_property_update(api_get_course_info(), TOOL_NOTEBOOK, Database::escape_string($values['notebook_id']), 'NotebookUpdated', api_get_user_id());

	// display the feedback message
	//Display::display_confirmation_message(get_lang('NoteUpdated'));
}

function delete_note($notebook_id) {
	// Database table definition
	$t_notebook = Database :: get_course_table(TABLE_NOTEBOOK);

	$sql = "DELETE FROM $t_notebook WHERE notebook_id='".Database::escape_string($notebook_id)."' AND user_id = '".Database::escape_string(api_get_user_id())."'";
	$result = Database::query($sql, __FILE__, __LINE__);

	//update item_property (delete)
	api_item_property_update(api_get_course_info(), TOOL_NOTEBOOK, Database::escape_string($notebook_id), 'delete', api_get_user_id());

	//Display::display_confirmation_message(get_lang('NoteDeleted'));
}

function display_notes() {

	global $_user;
	global $_course;
	
	setlocale (LC_TIME, $_course['language']);
	$today = ucfirst(strftime ('%B %d, %I:%M'));

	echo '<style>
	form {		
		border:0px;
	}
	</style>';
	 $styles = '<style>
	div.row div.label{
		width: 10%;
	}
	div.row div.formw{
		width: 98%;
	}
	</style>
	';
 echo $styles;
 echo '<div class="actions"><a href="'.api_get_self().'?'.api_get_cidReq().'&action=addnote">'.Display::return_icon('pixel.gif', get_lang('NewNote'), array('class' => 'toolactionplaceholdericon tooladdnewnote')).get_lang('NewNote').'</a></div>';
	echo '<div id="content">';
	echo '	<table width="100%" class="data_table"><tr><td width="20%" valign="top">';
	echo '		<div style="height:400px;width:200px;overflow:auto;">';
	echo '			<table class="data_table" width="100%">';
	$t_notebook = Database :: get_course_table(TABLE_NOTEBOOK);	
	$sql = "SELECT notebook_id,title,date_format(creation_date,'%b %d') AS notesdate FROM $t_notebook WHERE user_id = '".Database::escape_string(api_get_user_id())."'";
	$result = Database::query($sql, __FILE__, __LINE__);
	$numrows = Database::num_rows($result);
	if(isset($_GET['start'])) {
	  $from = Security::remove_XSS($_GET['start']);
	} else {
	  $from = 0;
	}
	$inc_cnt = 8;
	$number_of_items = $from + $inc_cnt;

	if($from <> 0){
	  $previous = $from - $inc_cnt;
	}
	if($number_of_items < $numrows){
	  $next = $from + $inc_cnt;
	}	
	
	$i = 0;
	$sql = "SELECT notebook_id,title,date_format(creation_date,'%b %d') AS notesdate FROM $t_notebook WHERE user_id = '".Database::escape_string(api_get_user_id())."' ORDER BY creation_date desc LIMIT $from, $inc_cnt";
	$result = Database::query($sql, __FILE__, __LINE__);	
	while ($row = Database::fetch_array($result)) {
		
		$title = $row['title'];
		$title = Security::remove_XSS($title);
		$titlelen = strlen($title);
		if($titlelen > 25) {
		  $title = substr($title,0,50).'...';
		}

		if($i%2 == 0) {
			$notes_trclass = "class=\"row_odd\"";
		} else {
			$notes_trclass = "class=\"row_even\"";
		}

		if(isset($_GET['notebook_id'])) {
			$current_notebookid = $_GET['notebook_id'];
		}
		if ($row['notebook_id'] == $current_notebookid) {
			$trclass = "class=\"row_odd\"";
		} else {
			$trclass = "";
		}	
		
		$i++;
	
		echo '<tr '.$notes_trclass.'><td><table width="100%"><tr '.$trclass.'><td width="75%"><a style="color:#000;font-size:12px;" href="'.api_get_self().'?action=editnote&amp;notebook_id='.$row['notebook_id'].'&start='.$from.'">'.$title.'</td><td align="right" valign="top" style="color:#999999;">'.$row['notesdate'].'</a></td></tr></table></td></tr>';
	}
    if ($numrows == 0) {
      echo get_lang('YouHaveNotPersonalNotesHere');
    }
	echo '</table></div>';
	if($numrows > $inc_cnt)
	{
		echo '<table width="100%"><tr><td width="50%" align="left">';
		if($from != 0){
		  echo '<a href="'.api_get_self().'?'.api_get_cidReq().'&start='.$previous.'"><img src="../img/go-left.png"></a>';
		}
		echo '</td><td width="50%" align="right">';
		if($numrows > $number_of_items) {
		  echo '<a href="'.api_get_self().'?'.api_get_cidReq().'&start='.$next.'"><img src="../img/go-right.png"></a>';
		}
		echo '</td></tr></table>';
	}	
	echo '</td><td width="80%" align="right">';

	if (isset($_GET['action']) && $_GET['action'] == 'editnote') {
	if (!empty($_GET['isStudentView'])) {
		display_notes();// Why recursive function?
		exit;
	}

	// initiate the object
	$form = new FormValidator('note','post', api_get_self().'?action='.Security::remove_XSS($_GET['action']).'&edit=submit&start='.$from.'&notebook_id='.Security::remove_XSS($_GET['notebook_id']));	
	//$renderer = & $form->defaultRenderer();
	$form->addElement('hidden', 'notebook_id');
	//$renderer->setElementTemplate('<div>{element}</div>', 'note_comment');
	$form->add_html_editor('note_comment','', false, false, api_is_allowed_to_edit()
		? array('ToolbarSet' => 'Notebook', 'Width' => '100%', 'Height' => '330')
		: array('ToolbarSet' => 'NotebookStudent', 'Width' => '100%', 'Height' => '330', 'UserStatus' => 'student'));
	$form->addElement('hidden', 'note_title');
	
	$form->addElement('html','<div align="left" style="padding-left:10px;"><a href="'.api_get_self().'?action=deletenote&amp;notebook_id='.Security::remove_XSS($_GET['notebook_id']).'&start='.$from.'">'.Display::return_icon('pixel.gif', get_lang('Delete'), array('class' => 'actionplaceholdericon actiondelete')).'&nbsp;&nbsp;'.get_lang('Delete').'</a></div>');
	$form->addElement('style_submit_button', 'SubmitNote', get_lang('Validate'), 'class="save"');
	// setting the defaults
	$defaults = get_note_information(Security::remove_XSS($_GET['notebook_id']));
	$form->setDefaults($defaults);

	$token = Security::get_token();
	$form->addElement('hidden','sec_token');
	$form->setConstants(array('sec_token' => $token));
	$form->display();	

	} else {
	$form = new FormValidator('note','post', api_get_self().'?action=addnote&add=submit');
	//$renderer = & $form->defaultRenderer();
	//$renderer->setElementTemplate('<div>{element}</div>', 'note_comment');
	$form->add_html_editor('note_comment','', false, false, api_is_allowed_to_edit()
		? array('ToolbarSet' => 'Notebook', 'Width' => '100%', 'Height' => '330')
		: array('ToolbarSet' => 'NotebookStudent', 'Width' => '100%', 'Height' => '330', 'UserStatus' => 'student'));
	$form->addElement('style_submit_button', 'SubmitNote', get_lang('Validate'), 'class="save"');
//	$today = date('M d, h:i');
	$default_note = '<div><table width="99%" height="250px" cellspacing="5" cellpadding="0">
            <tbody>
                <tr valign="top" align="left">                                       
                    <td valign="top" style="padding-left:10px;">
                    <p><span style="color:#E05400;font-size:16px;">'.get_lang('Today').'</span><span style="padding-left:50%;color:#E05400;font-size:16px;">'.$today.'</span></p>
                    <div id="#cont#"><br/>'.get_lang('Typeyournoteshere').'</div>                                    
                    </td>
                </tr>
            </tbody>
        </table>';
	$defaults['note_comment'] = $default_note;
	$form->setDefaults($defaults);
	$form->display();	
	//echo '</td></tr></table></div>';
	}
	echo '</table></div>';

	//return $return;
}
?>
