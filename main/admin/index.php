<?php
/* For licensing terms, see /dokeos_license.txt */

/**
* @package dokeos.admin
*/

// Language files that should be included
$language_file=array('admin','tracking');

// resetting the course id
$cidReset=true;

// setting the help
$help_content = 'platformadministration';

// including the global Dokeos file
require_once '../inc/global.inc.php';

// including additional libraries
require_once api_get_path(LIBRARY_PATH).'security.lib.php';

// Section for the tabs
$this_section=SECTION_PLATFORM_ADMIN;

// Access restrictions
api_protect_admin_script(true);

$nameTools = get_lang('PlatformAdmin');

// setting breadcrumbs
//$interbreadcrumb[] = array('url' => 'index.php', 'name' => $nameTools);

// setting the name of the tool
$tool_name=get_lang('PlatformAdmin');

// Displaying the header
Display::display_header($nameTools);


if(api_is_platform_admin())
{
	if(is_dir(api_get_path(SYS_CODE_PATH).'install/') && is_readable(api_get_path(SYS_CODE_PATH).'install/index.php'))
	{
		Display::display_warning_message(get_lang('InstallDirAccessibleSecurityThreat'));
	}
	/*
	==============================================================================
			ACTION HANDLING
	==============================================================================
	*/
	if (!empty($_POST['Register']))
	{
		register_site();
		Display :: display_confirmation_message(get_lang('VersionCheckEnabled'));
	}

	/*
	==============================================================================
			MAIN SECTION
	==============================================================================
	*/
	$keyword_url = Security::remove_XSS((empty($_GET['keyword'])?'':$_GET['keyword']));
}

echo '<div id="content">';

if (api_is_platform_admin()) {
	?>
	<div class="admin_section section">
	<div class="admin_section_title sectiontitle"><a href="user_list.php"><?php echo Display::return_icon('pixel.gif', get_lang('HomePage'), array('class' => 'toolactionplaceholdericon toolactionadminusers')); ?> <?php echo api_ucfirst(get_lang('Users')); ?></a></div>
	<div class="admin_section_content sectioncontent">
	<form method="get" action="user_list.php">
			<input type="text" name="keyword" value="<?php echo $keyword_url; ?>"/>
			<button class="search" type="submit"> <?php echo get_lang('Search');?>
			</button>
			</form>
	<ul>
		<li><a href="user_list.php?search=advanced"><?php echo api_ucfirst(get_lang('AdvancedSearch')); ?></a></li>
		<li><a href="user_list.php">	<?php echo get_lang('UserList') ?></a></li>
		<li><a href="user_add.php">		<?php echo get_lang('AddUsers') ?></a></li>
		<?php 
		//if (api_get_setting('allow_social_tool')=='true') { ?>
		<!--<li><a href="group_add.php">	<?php echo get_lang('AddGroups') ?></a></li>
			<li><a href="group_list.php">	<?php echo get_lang('GroupList') ?></a></li>-->
		<?php
		//}
		if(isset($extAuthSource) && isset($extAuthSource['ldap']) && count($extAuthSource['ldap'])>0){
			?>
			<!-- dynamic ldap code -->
			  <li><a href="ldap_users_list.php"><?php echo get_lang('ImportLDAPUsersIntoPlatform');?></a></li>
			<!-- dynamic ldap code -->
			<?php
			}
		?>
		</ul>
	</div>
	</div>
<?php
}
else
{
	?>
	<div class="admin_section section">
	<div class="admin_section_title sectiontitle"><a href="user_list.php"><?php echo Display::return_icon('pixel.gif', get_lang('HomePage'), array('class' => 'toolactionplaceholdericon toolactionadminusers')); ?> <?php echo api_ucfirst(get_lang('Users')); ?></a></div>
	<div class="admin_section_content sectioncontent">
	<ul>
		<li><a href="user_list.php">	<?php echo get_lang('UserList') ?></a></li>
		<li><a href="../mySpace/user_add.php"><?php echo get_lang('AddUsers') ?></a></li>
		<li><a href="user_import.php">	<?php echo get_lang('ImportUserListXMLCSV') ?></a></li>
	</ul>
	</div>
	</div>
<?php
}


if(api_is_platform_admin()) {
?>
	<div class="admin_section section">
	<div class="admin_section_title sectiontitle"><a href="course_list.php"><?php echo Display::return_icon('pixel.gif', get_lang('Courses'), array('class' => 'toolactionplaceholdericon toolactionadmincourses')); ?> <?php echo api_ucfirst(get_lang('Courses')); ?></a></div>
	<div class="admin_section_content sectioncontent">
		<form method="get" action="course_list.php">
		<input type="text" name="keyword" value="<?php echo $keyword_url; ?>"/>
		<button class="search" type="submit"> <?php echo get_lang('Search');?>
			</button>
		</form>
		<ul>
		<!--<li style="list-style-type:none"></li>-->
		<li>
		<a href="course_list.php?search=advanced"><?php echo api_ucfirst(get_lang('AdvancedSearch')); ?></a>

	</li>
	<li><a href="course_list.php"><?php echo get_lang('CourseList') ?></a></li>
	<li><a href="course_add.php"><?php echo get_lang('AddCourse') ?></a></li>
	<li><a href="course_enrolment.php"><?php echo get_lang('EnrolmentToCoursesAtRegistrationToPortal') ?></a></li>	
	<!--<li><a href="course_virtual.php"><?php //echo get_lang('AdminManageVirtualCourses') ?></a></li>-->
	<li><a href="course_category.php"><?php echo get_lang('AdminCategories'); ?></a></li>	
	<li><a href="course_user_import.php"><?php echo get_lang('ImportUsersToACourse'); ?></a></li>
	<?php if (api_get_setting('search_enabled')=='true') { ?>
	  <!--<li><a href="specific_fields.php"><?php echo get_lang('SpecificSearchFields'); ?></a></li></li>-->
	<?php } ?>
	<?php
		if(isset($extAuthSource) && isset($extAuthSource['ldap']) && count($extAuthSource['ldap'])>0){
		?>
		<!-- dynamic ldap code -->
		<li><a href="ldap_import_students.php"><?php echo get_lang('ImportLDAPUsersIntoCourse');?></a></li>
		<!-- dynamic ldap code -->
		<?php
		}
	?>

	</ul>
	</div>
	</div>


	<div class="admin_section section">
	<div class="admin_section_title sectiontitle"><a href="settings.php?category=Platform"><?php echo Display::return_icon('pixel.gif', get_lang('Platform'), array('class' => 'toolactionplaceholdericon toolactionhomepage')); ?> <?php echo api_ucfirst(get_lang('Platform')); ?></a></div>
	<div class="admin_section_content sectioncontent">
	 <ul>
	  <li><a href="settings.php?category=Platform"><?php echo get_lang('DokeosConfigSettings') ?></a></li>
	  <li><a href="special_exports.php"><?php echo get_lang('SpecialExports') ?></a></li>
	  <li><a href="system_announcements.php"><?php echo get_lang('SystemAnnouncements') ?></a></li>
	  <li><a href="languages.php"><?php echo get_lang('Languages'); ?></a></li>
	  <li><a href="configure_homepage.php"><?php echo get_lang('ConfigureHomePage'); ?></a></li>
	  <li><a href="configure_inscription.php"><?php echo get_lang('ConfigureInscription'); ?></a></li>
	  <li><a href="statistics/index.php"><?php echo get_lang('ToolName'); ?> </a></li>
	  <li><a href="agenda.php"><?php echo get_lang('GlobalAgenda'); ?> </a></li>
	  <?php
	  if (api_get_setting('show_emailtemplates')=='true') {
	  echo '<li><a href="emailtemplates.php">'.get_lang('Automaticemails').'</a></li>';
	  }
	  ?>
	  <?php if(!empty($phpMyAdminPath)) { ?>
	  <li><a href="<?php echo $phpMyAdminPath; ?>" target="_blank"><?php echo get_lang("AdminDatabases"); ?></a><br />(<?php echo get_lang("DBManagementOnlyForServerAdmin"); ?>)</li>
	  <?php } ?>
	  <?php
	  if(!empty($_configuration['multiple_access_urls']))
	  {
	    echo '<li><a href="access_urls.php">'.get_lang('ConfigureMultipleAccessURLs').'</a></li>';
	  }

	  /*if (api_get_setting('allow_reservation')=='true') {
		  	echo '<li><a href="../reservation/m_category.php">'.get_lang('BookingSystem').'</a></li>';
	  }*/

  	  if (api_get_setting('allow_terms_conditions')=='true') {
		  	echo '<li><a href="legal_add.php">'.get_lang('TermsAndConditions').'</a></li>';
	  }

	  ?>
	 </ul>
	</div>
	</div>

	<?php
}

if(api_get_setting('show_catalogue')=='true' && api_get_setting('use_session_mode')=='true')
{
?>

	<div class="admin_section section">
		<div class="admin_section_title sectiontitle"><a href="catalogue_management.php"><?php echo Display::return_icon('pixel.gif', get_lang('Catalogue'), array('class' => 'toolactionplaceholdericon toolactionadmincatalogue')); ?> <?php echo get_lang('Catalogue') ?></a></div>
		<div class="admin_section_content sectioncontent">
 <ul>
  <li><a href="catalogue_management.php"><?php echo get_lang('Catalogue') ?></a></li>
  <li><a href="topic_list.php"><?php echo get_lang('Topics') ?></a></li>
  <li><a href="programme_list.php"><?php echo get_lang('Programmes') ?></a></li>
  <li><a href="session_list.php"><?php echo get_lang('Sessions') ?></a></li>
  <li><a href="session_add.php"><?php echo get_lang('AddSession') ?></a></li>  
				<?php	if(isset($extAuthSource) && isset($extAuthSource['ldap']) && count($extAuthSource['ldap'])>0){ ?>
		 <li><a href="ldap_import_students_to_session.php"><?php echo get_lang('ImportLDAPUsersIntoSession');?></a></li>
				<?php	} ?>  
  </ul>
	 	</div>
	</div>

<?php
}
else if(api_get_setting('use_session_mode')=='true' && api_get_setting('show_catalogue')!='true' )
{
?>

	<div class="admin_section section">
		<div class="admin_section_title sectiontitle"><a href="session_list.php"><?php echo Display::return_icon('pixel.gif', get_lang('Sessions'), array('class' => 'toolactionplaceholdericon toolactionadminsession')); ?> <?php echo get_lang('Sessions') ?></a></div>
		<div class="admin_section_content sectioncontent">
 <form method="POST" action="session_list.php">
	<input type="text" name="keyword_name" value="<?php echo $keyword_url; ?>"/>
				<button class="search" type="submit"> <?php echo get_lang('Search');?></button>
	</form>
 <ul>
  <li><a href="session_list.php?search=advanced"><?php echo api_ucfirst(get_lang('AdvancedSearch')); ?></a></li>
  <li><a href="session_list.php"><?php echo get_lang('ListSession'); ?></a></li>
  <li><a href="session_category_list.php"><?php echo get_lang('ListSessionCategory'); ?></a></li>
  <li><a href="session_add.php"><?php echo get_lang('AddSession'); ?></a></li>
 <?php	if(isset($extAuthSource) && isset($extAuthSource['ldap']) && count($extAuthSource['ldap'])>0){ ?>
 <li><a href="ldap_import_students_to_session.php"><?php echo get_lang('ImportLDAPUsersIntoSession');?></a></li>
 <?php	} ?>  
  </ul>
	 	</div>
	</div>

<?php
}
else if(api_is_platform_admin())
{
?>

<div class="admin_section section">
<div class="admin_section_title sectiontitle"><a href="class_list.php"><?php Display::return_icon('pixel.gif', get_lang('AdminClasses'), array('class' => 'toolactionplaceholdericon toolactionadminsession')); ?> <?php echo api_ucfirst(get_lang('AdminClasses')); ?></a></div>
<div class="admin_section_content sectioncontent">
<form method="get" action="class_list.php">

	<input type="text" name="keyword" value="<?php echo $keyword_url; ?>"/>
	<button class="search" type="submit"> <?php echo get_lang('Search');?></button>
	</form>
<ul>
<!--<li style="list-style-type:none"></li>-->
<li><a href="class_list.php"><?php echo get_lang('ClassList'); ?></a></li>
<li><a href="class_add.php"><?php echo get_lang('AddClasses'); ?></a></li>
<li><a href="class_import.php"><?php echo get_lang('ImportClassListCSV'); ?></a></li>
<li><a href="class_user_import.php"><?php echo get_lang('AddUsersToAClass'); ?> CSV</a></li>
<li><a href="subscribe_class2course.php"><?php echo get_lang('AddClassesToACourse'); ?></a></li>
</ul>
<br />
<br />
</div>
</div>
<?php
}

if(api_is_platform_admin()){
?>
<div class="admin_section section">
<div class="admin_section_title sectiontitle"><a href="http://www.dokeos.com/forum/" target="_blank"><?php echo Display::return_icon('pixel.gif', 'Dokeos Community', array('class' => 'toolactionplaceholdericon toolactionadminsupport')); ?> Dokeos Community</a></div>
<div class="admin_section_content sectioncontent">
 <ul>
  <li><a href="http://www.dokeos.com/forum/" target="_blank"><?php echo get_lang('DokeosForum'); ?></a></li>
  <li><a href="http://www.dokeos.com/wiki/index.php/Main_Page" target="_blank"><?php echo get_lang('TechnicalWiki'); ?></a></li>
  <li><a href="http://www.dokeos.com/en/documentation.php" target="_blank"><?php echo get_lang('Documentation'); ?></a></li>
  <li><a href="http://www.dokeos.com/en/mind" target="_blank"><?php echo get_lang('DokeosMind'); ?></a></li>
  <li><a href="http://www.dokeos.com/DLTT/" target="_blank"><?php echo get_lang('TranslationTool'); ?></a></li>
  <li><a href="http://www.dokeos.com/en/community.php" target="_blank"><?php echo get_lang('WorldwideMap'); ?></a></li>
  <li><a href="http://www.dokeos.com/en/services/e-courses-production" target="_blank"><?php echo get_lang('AuthoringStudio'); ?></a></li>
  <li>
  <?php
  //try to display a maximum before we check the dokeos version and all that
  	//session_write_close(); //close session to avoid blocking concurrent access
	flush(); //send data to client as much as allowed by the web server
	//ob_flush();
	echo get_lang('VersionCheck').': '.get_lang('SiteRegistered');
  ?>
  </li>
  <!--<li><a href="configure_extensions.php?display=visio"><?php echo get_lang('Visioconf'); ?></a></li>
	  <li><a href="configure_extensions.php?display=ppt2lp"><?php echo get_lang('Ppt2lp'); ?></a></li>  
      <li><a href="configure_extensions.php?display=ephorus"><?php echo get_lang('EphorusPlagiarismPrevention'); ?></a></li> 
  ?>
  <li><a href="configure_extensions.php?display=search"><?php echo get_lang('SearchEngine'); ?></a></li>
  <li><a href="configure_extensions.php?display=serverstats"><?php echo get_lang('ServerStatistics'); ?></a></li>
  <li><a href="configure_extensions.php?display=bandwidthstats"><?php echo get_lang('BandWidthStatistics'); ?></a></li>-->
  </ul>
</div>
</div>
<?php
	/*if(isset($extAuthSource) && isset($extAuthSource['ldap']) && count($extAuthSource['ldap'])>0){
	?>
	<!-- dynamic ldap code -->
	<div class="admin_section">
	 <h4><?php Display::display_icon('members.gif', 'LDAP'); ?> LDAP</h4>
	 <ul>
	  <li><a href="ldap_users_list.php"><?php echo get_lang('ImportLDAPUsersIntoPlatform');?></a></li>
	  <!--<li><a href="ldap_import_students.php"><?php echo get_lang('ImportLDAPUsersIntoCourse');?></a></li>
	  <li><a href="ldap_import_students_to_session.php"><?php echo get_lang('ImportLDAPUsersIntoSession');?></a></li> -->
	  <li><a href="session_auto_import.php"><?php echo 'Table de correspondance des Sessions';?></a></li>

	  <!--li><a href="ldap_users_synchro.php"><?php echo get_lang('LDAPSynchroImportUsersAndStepsInSessions');?></a></li-->
	 </ul>
	</div>
	<!-- dynamic ldap code -->
	<?php
	}*/
?>
<!--<div class="admin_section section">
<div class="admin_section_title sectiontitle"><?php Display::display_icon('dokeos.gif', 'Dokeos'); ?> Dokeos.com</div>
<div class="admin_section_content sectioncontent">
 <ul>
  <li><a href="http://www.dokeos.com/" target="_blank"><?php echo get_lang('DokeosHomepage'); ?></a></li>
  <li><a href="http://www.dokeos.com/forum/" target="_blank"><?php echo get_lang('DokeosForum'); ?></a></li>
  <li><a href="http://www.dokeos.com/extensions/" target="_blank"><?php echo get_lang('DokeosExtensions'); ?></a></li>
  <li>-->
  <?php
  //try to display a maximum before we check the dokeos version and all that
  	//session_write_close(); //close session to avoid blocking concurrent access
	//flush(); //send data to client as much as allowed by the web server
	//ob_flush();
	//echo get_lang('VersionCheck').': '.version_check();
  ?>
 <!-- </li>
 </ul>
</div>
</div>-->

<div class="admin_section section">
<div class="admin_section_title sectiontitle"><a href="http://www.dokeos.com/en/node/32"><?php echo Display::return_icon('pixel.gif', get_lang('ConfigureExtensions'), array('class' => 'toolactionplaceholdericon toolactionadminconfext')); ?> <?php echo api_ucfirst(get_lang('ConfigureExtensions')); ?></a></div>
<div class="admin_section_content sectioncontent">
 <ul>
     <?php if(api_get_setting('service_ppt2lp', 'active') == 'true') { ?>
        <li><a href="configure_extensions.php?display=ppt2lp"><?php echo get_lang('Ppt2lp'); ?></a></li>
     <?php } ?>
     <?php if(api_get_setting('service_visio', 'active') == 'true') { ?>
        <li><a href="configure_extensions.php?display=visio"><?php echo get_lang('Visioconf'); ?></a></li>
     <?php } ?>
     <?php if(api_get_setting('search_enabled') == 'true') { ?>
        <li><a href="configure_extensions.php?display=search"><?php echo get_lang('SearchEngine'); ?></a></li>
     <?php } ?>
 <!-- <li><a href="configure_extensions.php?display=visio"><?php echo get_lang('Visioconf'); ?></a></li>
  <li><a href="configure_extensions.php?display=ephorus"><?php echo get_lang('EphorusPlagiarismPrevention'); ?></a></li>
  <li><a href="configure_extensions.php?display=search"><?php echo get_lang('SearchEngine'); ?></a></li>
  <li><a href="configure_extensions.php?display=serverstats"><?php echo get_lang('ServerStatistics'); ?></a></li>
  <li><a href="configure_extensions.php?display=bandwidthstats"><?php echo get_lang('BandWidthStatistics'); ?></a></li>
  <li><a href="http://www.dokeos.com/en/services/e-courses-production" target="_blank"><?php echo get_lang('CourseBuildingStudio'); ?></a></li>
  <li><a href="http://www.dokeos.com/en/node/777" target="_blank"><?php echo get_lang('ContentShop'); ?></a></li>
  <li><a href="http://www.dokeos.com/en/download.php" target="_blank"><?php echo get_lang('MedPro'); ?></a></li>
  <li><a href="http://www.dokeos.com/en/services/technical-assistance" target="_blank"><?php echo get_lang('TechnicalAssistance'); ?></a></li>
  <li><a href="configure_extensions.php?display=visio"><?php echo get_lang('Videoconferencing'); ?></a></li>
  <li><a href="configure_extensions.php?display=ppt2lp"><?php echo get_lang('OogieRapidLearning'); ?></a></li>-->
  <li><a href="http://www.dokeos.com/en/deployment/professional"><?php echo get_lang('DokeosProFeatures'); ?></a></li>
  <li><a href="http://www.dokeos.com/en/services/certified-training"><?php echo get_lang('TrainersTraining'); ?></a></li>  
  <li><a href="http://www.dokeos.com/en/services/hr-integration"><?php echo get_lang('HRIntegration'); ?></a></li>  
  <li><a href="http://www.dokeos.com/en/node/32"><?php echo get_lang('TechnicalAssistance'); ?></a></li>
  </ul>
</div>
</div>


<div class="clear">&nbsp;</div>
<?php
}

/**
 * Displays either the text for the registration or the message that the installation is (not) up to date
 *
 * @return string html code
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
 * @version august 2006
 * @todo have a 6monthly re-registration
 */
function version_check()
{
	$tbl_settings = Database :: get_main_table(TABLE_MAIN_SETTINGS_CURRENT);
	$sql = 'SELECT selected_value FROM  '.$tbl_settings.' WHERE variable="registered" ';
	$result = Database::query($sql,__FILE__,__LINE__);
	$row=Database::fetch_array($result,'ASSOC');

	// The site has not been registered yet
	//if (api_get_setting('registered')=='false')

	$return = '';
	if ($row['selected_value']=='false')
	{
		$return .= '<form action="'.api_get_self().'" id="VersionCheck" name="VersionCheck" method="post">';
		$return .= get_lang('VersionCheckExplanation');
		$return .= '<input type="checkbox" name="donotlistcampus" value="1" id="checkbox" />'.get_lang('HideCampusFromPublicDokeosPlatformsList');
		$return .= '<button type="submit" class="save" name="Register" value="'.get_lang('EnableVersionCheck').'" id="register" />'.get_lang('EnableVersionCheck').'</button>';
		$return .= '</form>';
	}
	else
	{
		// The site has been registered already but is seriously out of date (registration date + 15552000 seconds)
		/*
		if ((api_get_setting('registered') + 15552000) > mktime())
		{
			$return = 'It has been a long time since about your campus has been updated on Dokeos.com';
			$return .= '<form action="'.api_get_self().'" id="VersionCheck" name="VersionCheck" method="post">';
			$return .= '<input type="submit" name="Register" value="Enable Version Check" id="register" />';
			$return .= '</form>';
		}
		else
		{
		*/
		$return = 'site registered. ';
		$return .= check_dokeos_version2();
		//}
	}
	return $return;
}

/**
 * This setting changes the registration status for the campus
 *
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
 * @version August 2006
 *
 * @todo the $_settings should be reloaded here. => write api function for this and use this in global.inc.php also.
 */
function register_site()
{
	// Database Table Definitions
	$tbl_settings = Database :: get_main_table(TABLE_MAIN_SETTINGS_CURRENT);

	// the SQL statment
	$sql = "UPDATE $tbl_settings SET selected_value='true' WHERE variable='registered'";
	$result = Database::query($sql,__FILE__,__LINE__);

	//
	if ($_POST['donotlistcampus'])
	{
		$sql = "UPDATE $tbl_settings SET selected_value='true' WHERE variable='donotlistcampus'";
		$result = Database::query($sql,__FILE__,__LINE__);
	}

	// reload the settings
}

/**
* Check if the current installation is up to date
* The code is borrowed from phpBB and slighlty modified
* @author The phpBB Group <support@phpbb.com> (the code)
* @author Patrick Cool <patrick.cool@UGent.be>, Ghent University (the modifications)
* @copyright (C) 2001 The phpBB Group
* @return language string with some layout (color)
*/
function check_dokeos_version2()
{
	global $_configuration;
	$dokeos_version = trim($_configuration['dokeos_version']); // the dokeos version of your installation

	if (ini_get('allow_url_fopen')==1)
	{
		// the number of courses
		$sql="SELECT count(code) FROM ".Database::get_main_table(TABLE_MAIN_COURSE);
		$result=Database::query($sql,__FILE__,__LINE__);
		$row = Database::fetch_array($result);
		$number_of_courses = $row[0];

		// the number of users
		$sql="SELECT count(user_id) FROM ".Database::get_main_table(TABLE_MAIN_USER);
		$result=Database::query($sql,__FILE__,__LINE__);
		$row = Database::fetch_array($result);
		$number_of_users = $row[0];

		$script = 'version.pro.php';
		$version_url= 'http://www.dokeos.com/'.$script.'?url='.urlencode(api_get_path(WEB_PATH)).'&campus='.urlencode(api_get_setting('siteName')).'&contact='.urlencode(api_get_setting('emailAdministrator')).'&version='.urlencode($dokeos_version).'&numberofcourses='.urlencode($number_of_courses).'&numberofusers='.urlencode($number_of_users).'&donotlistcampus='.api_get_setting('donotlistcampus').'&organisation='.urlencode(api_get_setting('Institution')).'&adminname='.urlencode(api_get_setting('administratorName').' '.api_get_setting('administratorSurname'));
		$handle=@fopen($version_url,'r');
		$version_info=trim(@fread($handle, 1024));

		if ($dokeos_version<>$version_info)
		{
			$output='<br /><span style="color:red">' . get_lang('YourVersionNotUpToDate') . '. '.get_lang('LatestVersionIs').' <b>Dokeos '.$version_info.'</b>. '.get_lang('YourVersionIs').' <b>Dokeos '.$dokeos_version. '</b>. '.str_replace('http://www.dokeos.com','<a href="http://www.dokeos.com">http://www.dokeos.com</a>',get_lang('PleaseVisitDokeos')).'</span>';
		}
		else
		{
			$output = '<br /><span style="color:green">'.get_lang('VersionUpToDate').': Dokeos '.$version_info.'</span>';
		}
	}
	else
	{
		$output = '<span style="color:red">' . get_lang('AllowurlfopenIsSetToOff') . '</span>';
	}
	return $output;
}

/**
* Check if the current installation is up to date
* The code is borrowed from phpBB and slighlty modified
* @author The phpBB Group <support@phpbb.com> (the code)
* @author Patrick Cool <patrick.cool@UGent.be>, Ghent University (the modifications)
* @copyright (C) 2001 The phpBB Group
* @return language string with some layout (color)
* @deprecated 	For some reason this code adds a 9 in front and a 0 at the end of what normally gets displayed by
				the http://www.dokeos.com/version.php page (instead of version.txt) . That's why I chose to use fopen which requires however
				that allow_url_open is set to true
*/
function check_dokeos_version()
{
	global $_configuration; // the dokeos version of your installation
	$dokeos_version = $_configuration['dokeos_version'];

	if ($fsock = @fsockopen('www.dokeos.com', 80, $errno, $errstr))
	{
		@fputs($fsock, "GET /version.php HTTP/1.1\r\n");
		@fputs($fsock, "HOST: www.dokeos.com\r\n");
		@fputs($fsock, "Connection: close\r\n\r\n");

		$get_info = false;
		while (!@feof($fsock))
		{
			if ($get_info)
			{
				$version_info .= @fread($fsock, 1024);
			}
			else
			{
				if (@fgets($fsock, 1024) == "\r\n")
				{
					$get_info = true;
				}
			}
		}
		@fclose($fsock);

		if (trim($dokeos_version)<>trim($version_info))
		{
			$output='<span style="color:red">' . get_lang('YourVersionNotUpToDate') . '. '.get_lang('LatestVersionIs').' <b>Dokeos '.$version_info.'</b>. '.get_lang('YourVersionIs').' <b>Dokeos '.$dokeos_version. '</b>. '.str_replace('http://www.dokeos.com','<a href="http://www.dokeos.com">http://www.dokeos.com</a>',get_lang('PleaseVisitDokeos')).'</span>';
		}
		else
		{
			$output = '<span style="color:green">'.get_lang('VersionUpToDate').': Dokeos '.$version_info.'</span>';
		}
	}
	else
	{
		if ($errstr)
		{
			$output = '<span style="color:red">' . get_lang('ConnectSocketError') . ': '. $errstr . '</span>';
		}
		else
		{
			$output = '<span>' . get_lang('SocketFunctionsDisabled') . '</span>';
		}
	}
	return $output;
}

echo '</div>';

// display the footer
Display::display_footer();
?>
