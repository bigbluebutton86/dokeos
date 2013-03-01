<?php
/* For licensing terms, see /dokeos_license.txt */

/**
* @package dokeos.admin
*/


// name of the language file that needs to be included
$language_file = array ('registration','admin','exercice','work');

// resetting the course id
$cidReset = true;

// setting the help
$help_content = 'platformadministrationplatformnews';

// including the global Dokeos file
require ('../inc/global.inc.php');

// including additional libraries
require_once (api_get_path(LIBRARY_PATH).'formvalidator/FormValidator.class.php');
require_once (api_get_path(LIBRARY_PATH).'sessionmanager.lib.php');

// setting the section (for the tabs)
$this_section = SECTION_PLATFORM_ADMIN;

// Access restrictions
api_protect_admin_script(true);

// setting breadcrumbs
$interbreadcrumb[]=array("url" => "index.php","name" => get_lang('PlatformAdmin'));
$tool_name = get_lang('Emailtemplates');

// Displaying the header
Display::display_header();

echo '<div class="actions">';
if(isset($_GET['action']) && $_GET['action'] == 'edit'){
	echo '<a href="emailtemplates.php">'.Display::return_icon('pixel.gif', get_lang('Emailtemplates'), array('class' => 'toolactionplaceholdericon toolactionback')).get_lang('Emailtemplates').'</a>';
}
echo '</div>';

echo '<div id="content">';
$table_emailtemplate 	= Database::get_main_table(TABLE_MAIN_EMAILTEMPLATES);	
if(isset($_GET['action']) && $_GET['action'] == 'edit')
{	
	$sql = "SELECT * FROM $table_emailtemplate WHERE id = ".Security::remove_XSS($_GET['id']);
	$result = api_sql_query($sql, __FILE__, __LINE__);
	while($row = Database::fetch_array($result))
	{
		$title = $row['title'];
		$description = $row['description'];
		$content = $row['content'];
		$db_language = $row['language'];
		$temp = $db_language;
	}
	if($db_language == 'english')
	{
		$db_language = '2';
	}
	elseif($db_language == 'french')
	{
		$db_language = '3';
	}
	elseif($db_language == 'german')
	{
		$db_language = '4';
	}
	$statictext = get_lang('Dontedittext');
	$language_interface = $temp;
	$language = array('1'=>'All','2'=>'English','3'=>'French','4'=>'German');
	$action = "emailtemplates.php?action=submit";
	$form = new FormValidator('emailtemplates','post',$action);
	$form->addElement('header', '', $tool_name);
	$form->addElement('text', 'title', get_lang('Title'),'class="focus"');
	$form->addElement('static','', '','<div style="width:100%;">'.$statictext.'</div>');
	$form->addElement('select', 'language', get_lang('Language'),$language,'disabled');
	$form->addElement('textarea', 'content', get_lang('Content'),array('rows' => '20','cols' => '75'));
	$form->addElement('hidden','id',Security::remove_XSS($_GET['id']));
	$form->addElement('style_submit_button', 'submit', get_lang('Save'), 'class="save"');
	$defaults['title'] = $title;	
	if(empty($content))
	{		
		$langpath = api_get_path(SYS_CODE_PATH).'lang/';		
		foreach ($language_files as $index => $language_file) {			
		include $langpath.'english/'.$language_file.'.inc.php';
		$langfile = $langpath.$language_interface.'/'.$language_file.'.inc.php';
		if (file_exists($langfile)) {
			include $langfile;
		}
		}
		if($description == 'Userregistration'){			
			$content = get_lang('Dear')." {Name} ,\n\n";
			$content .= get_lang('YouAreReg')." {siteName} ".get_lang('WithTheFollowingSettings')."\n\n";
			$content .= get_lang('Username').": {username} \n";	
			$content .= get_lang('Pass')." :{password} \n\n";
			$content .= get_lang('Address')." {siteName} ".get_lang('Is')." - {url} \n\n";
			$content .= get_lang('Problem')."\n\n".get_lang('Formula').",\n";
			$content .= "{administratorSurname} \n";
			$content .= get_lang('Manager')."\n";
			$content .= "{administratorTelephone} \n";
			$content .= get_lang('Email')." : {emailAdministrator}";
		}
		if($description == 'Quizreport'){
			$content = get_lang('DearStudentEmailIntroduction')."\n\n";
			$content .= get_lang('AttemptVCC')."\n\n";
			$content .= get_lang('Question').": {ques_name} \n";	
			$content .= get_lang('Exercice')." :{test} \n\n";
			$content .= get_lang('ClickLinkToViewComment')." - {url} \n\n";
			$content .= get_lang('Regards')."\n\n";			
			$content .= "{administratorSurname} \n";
			$content .= get_lang('Manager')."\n";
			$content .= "{administratorTelephone} \n";
			$content .= get_lang('Email')." : {emailAdministrator}";
		}
		if($description == 'Quizsuccess'){
			$content = get_lang('DearStudentEmailIntroduction')."\n\n";
			$content .= get_lang('AttemptVCC')."\n\n";
			$content .= get_lang('Quizsuccess')."\n\n";
			$content .= get_lang('Question').": {ques_name} \n";	
			$content .= get_lang('Exercice')." :{test} \n\n";
			$content .= get_lang('ClickLinkToViewComment')." - {url} \n\n";
			$content .= get_lang('Notes')."\n\n";
			$content .= "{notes} \n\n";
			$content .= get_lang('Regards')."\n\n";			
			$content .= "{administratorSurname} \n";
			$content .= get_lang('Manager')."\n";
			$content .= "{administratorTelephone} \n";
			$content .= get_lang('Email')." : {emailAdministrator}";
		}
		if($description == 'Quizfailure'){
			$content = get_lang('DearStudentEmailIntroduction')."\n\n";
			$content .= get_lang('AttemptVCC')."\n\n";
			$content .= get_lang('Quizfailure')."\n\n";
			$content .= get_lang('Question').": {ques_name} \n";	
			$content .= get_lang('Exercice')." :{test} \n\n";
			$content .= get_lang('ClickLinkToViewComment')." - {url} \n\n";
			$content .= get_lang('Notes')."\n\n";	
			$content .= "{notes} \n\n";
			$content .= get_lang('Regards')."\n\n";			
			$content .= "{administratorSurname} \n";
			$content .= get_lang('Manager')."\n";
			$content .= "{administratorTelephone} \n";
			$content .= get_lang('Email')." : {emailAdministrator}";
		}
		if($description == 'Newassignment'){
			$content = get_lang('Dear')." {Name} ,\n\n";
			$content .= get_lang('CreatedNewAssignment').' : '." {courseName} ". "\n\n";
			$content .= "{assignmentName}" . "\n\n";
			$content .= "{assignmentDescription}" . "\n\n";
			$content .= get_lang('Deadline').' : '. "{assignmentDeadline}" . "\n\n";
			$content .= get_lang('UploadPaper').' : '. "{siteName}" . "\n\n";
			$content .= get_lang('Yours').', '. "\n\n";
			$content .= "{authorName}" ."\n";				
		}
		if($description == 'Submitwork'){
			$content = get_lang('Dear')." {authorName} ,\n\n";
			$content .=  "{studentName}". get_lang('PublishedPaper') . "\n\n";
			$content .= "{paperName} ". "\n\n";
			$content .= get_lang('For'). "{assignmentName} - {assignmentDescription}" .get_lang('In'). " {courseName}" . "\n\n";			
			$content .= get_lang('DeadlineWas').' : '. "{assignmentDeadline}" . "\n\n";
			$content .= get_lang('PaperSubmittedOn').' : '. "{assignmentSentDate}" . "\n\n";
			$content .= get_lang('CorrectComment').' : '. "{siteName}" . "\n\n";
			$content .= get_lang('Yours').', '. "\n\n";
			$content .= "{administratorSurname}" ."\n";				
		}
		if($description == 'Correctwork'){
			$content = get_lang('Dear')." {studentName} ,\n\n";
			$content .=  get_lang('CorrectedPaper') . "\n\n";
			$content .= "{paperName} ". "\n\n";
			$content .= get_lang('For'). "{assignmentName} - {assignmentDescription}" .get_lang('In'). "{courseName}" . "\n\n";			
			$content .= get_lang('DeadlineWas').' : '. "{assignmentDeadline}" . "\n\n";
			$content .= get_lang('PaperSubmittedOn').' : '. "{assignmentSentDate}" . "\n\n";
			$content .= get_lang('CheckMark').' : '. "{siteName}" . "\n\n";
			$content .= get_lang('Yours').', '. "\n\n";
			$content .= "{authorName}" ."\n";				
		}
	}
	
	$defaults['content'] = $content;
	$defaults['language'] = $db_language;
	$form->setDefaults($defaults);	
	$form->display();
}
elseif(isset($_GET['action']) && $_GET['action'] == 'submit')
{	
	$title = $_POST['title'];
	$content = $_POST['content'];
	$id = $_POST['id'];

	$sql = "UPDATE $table_emailtemplate SET title = '".Database::escape_string($title)."', content = '".Database::escape_string($content)."' WHERE id = ".$id;
	api_sql_query($sql, __FILE__, __LINE__);
	echo '<script>window.location.href = "emailtemplates.php"</script>';
}
else
{
echo '<script>
function change_language(){	
	var lang = document.emaillanguage.language.value;	
	document.location.href = "emailtemplates.php?lang="+lang;
}
</script>';
$platformLanguage = api_get_setting('platformLanguage');
if(isset($_REQUEST['lang'])){
$language_selected = Security::remove_XSS($_REQUEST['lang']);
}

$form = new FormValidator('emaillanguage');
$language = array('1'=>'All','2'=>'English','3'=>'French','4'=>'German');
$form->addElement('select', 'language', get_lang('Language'),$language,'onchange=change_language()');
$defaults['language'] = $language_selected;
$form->setDefaults($defaults);
$form->display();

if($language_selected == 1)
{
	$language_selected = "";
}
elseif($language_selected == 2)
{
	$language_selected = "english";
}
elseif($language_selected == 3)
{
	$language_selected = "french";
}
elseif($language_selected == 4)
{
	$language_selected = "german";
}

$sql = "SELECT * FROM $table_emailtemplate"; 
if(!empty($language_selected)){
	$sql .= " WHERE language = '".$language_selected."'";
}
$result = api_sql_query($sql, __FILE__, __LINE__);
$numrows = Database::num_rows($result);
if($numrows <> 0)
{
	$i=0;
	$j=1;

	echo '<table class="gallery">';

	while ($row = Database::fetch_array($result)) {
		if (!empty($row['image']))
			{
				$image = api_get_path(WEB_IMG_PATH).'/'.$row['image'];
			} 
		if(!$i%4)
		{
			echo '<tr>';
		}
		
		echo '<td>';	
		echo '	<div class="section">';
        
          // User templates are not translatable
		  echo '<div class="sectiontitle">'.$row['title'].'</div>
				<div class="sectioncontent"><img border="0" src="'.$image.'"></div>
				<div align="center"><a href="'.api_get_self().'?action=edit&id='.$row['id'].'">'.Display::return_icon('pixel.gif', get_lang("Edit"), array('class' => 'actionplaceholdericon actionedit')).'&nbsp;&nbsp;'.Display::return_icon('pixel.gif', get_lang("Delete"), array('class' => 'actionplaceholdericon actiondelete')).'</div>
			</div>';
		echo '</td>';
		if($j==4)
		{
			echo '</tr>';
			$j=0;
		}
		$i++;
		$j++;
	}
	echo '</table>';
}

}//End of else
echo '</div>';

// display the footer
Display::display_footer();
?>
