<?php //$id: $
/* For licensing terms, see /dokeos_license.txt */
/**
==============================================================================
* This is the course creation library for Dokeos.
* It contains functions to create a course.
* Include/require it in your code to use its functionality.
*
* @package dokeos.library
* @todo clean up horrible structure, script is unwieldy, for example easier way to deal with
* different tool visibility settings: ALL_TOOLS_INVISIBLE, ALL_TOOLS_VISIBLE, CORE_TOOLS_VISIBLE...
==============================================================================
*/

include_once (api_get_path(LIBRARY_PATH).'database.lib.php');
require_once (api_get_path(LIBRARY_PATH).'mail.lib.inc.php');

/*
==============================================================================
		FUNCTIONS
==============================================================================
*/

/**
* Top-level function to create a course. Calls other functions to take care of
* the various parts of the course creation.
* @param	string	Course code requested (might be altered to match possible values)
* @param	string	Course title
* @param	string	Tutor name
* @param	integer	Course category code
* @param	string	Course language
* @param	integer Course admin ID
* @param	string	DB prefix
* @param	integer	Expiration delay in unix timestamp
* @return true if the course creation was succesful, false otherwise.
*/
function create_course($wanted_code, $title, $tutor_name, $category_code, $course_language, $course_admin_id, $db_prefix, $firstExpirationDelay)
{
	$keys = define_course_keys($wanted_code, "", $db_prefix);

	if(sizeof($keys))
	{
		$visual_code = $keys["currentCourseCode"];
		$code = $keys["currentCourseId"];
		$db_name = $keys["currentCourseDbName"];
		$directory = $keys["currentCourseRepository"];
		$expiration_date = time() + $firstExpirationDelay;

		prepare_course_repository($directory, $code);
		update_Db_course($db_name);
		fill_course_repository($directory);
		fill_Db_course($db_name, $directory, $course_language);
		register_course($code, $visual_code, $directory, $db_name, $tutor_name, $category_code, $title, $course_language, $course_admin_id, $expiration_date);

		return true;
	}
	else
		return false;
}

// TODO: Such a function might be useful in other places too. It might be moved in the CourseManager class.
// Also, the function might be upgraded for avoiding code duplications.
function generate_course_code($course_title, $encoding = null)
{
	if (empty($encoding)) {
		$encoding = api_get_system_encoding();
	}
	return substr(preg_replace('/[^A-Z0-9]/', '', strtoupper(api_transliterate($course_title, 'X', $encoding))), 0, 20);
}


/**
 *	Defines the four needed keys to create a course based on several parameters.
 *	@return array with the needed keys ["currentCourseCode"], ["currentCourseId"], ["currentCourseDbName"], ["currentCourseRepository"]
 *
 * @param	string    The code you want for this course
 * @param	string    Prefix added for ALL keys
 * @param   string    Prefix added for databases only
 * @param   string    Prefix added for paths only
 * @param   boolean   Add unique prefix
 * @param   boolean   Use code-independent keys
 * @todo	eliminate globals
 */
function define_course_keys($wantedCode, $prefix4all = "", $prefix4baseName = "", $prefix4path = "", $addUniquePrefix = false, $useCodeInDepedentKeys = true)
{
	global $prefixAntiNumber, $_configuration;
	$course_table = Database :: get_main_table(TABLE_MAIN_COURSE);
	$wantedCode = generate_course_code($wantedCode);
	$keysCourseCode = $wantedCode;
	if(!$useCodeInDepedentKeys)
	{
		$wantedCode = '';
	}

	if($addUniquePrefix)
	{
		$uniquePrefix = substr(md5(uniqid(rand())), 0, 10);
	}
	else
	{
		$uniquePrefix = '';
	}

	$keys = array ();

	$finalSuffix = array ('CourseId' => '', 'CourseDb' => '', 'CourseDir' => '');

	$limitNumbTry = 100;

	$keysAreUnique = false;

	$tryNewFSCId = $tryNewFSCDb = $tryNewFSCDir = 0;

	while (!$keysAreUnique)
	{
		$keysCourseId = $prefix4all.$uniquePrefix.$wantedCode.$finalSuffix['CourseId'];

		$keysCourseDbName = $prefix4baseName.$uniquePrefix.strtoupper($keysCourseId).$finalSuffix['CourseDb'];

		$keysCourseRepository = $prefix4path.$uniquePrefix.$wantedCode.$finalSuffix['CourseDir'];

		$keysAreUnique = true;

		// check if they are unique
		$query = "SELECT 1 FROM ".$course_table . " WHERE code='".$keysCourseId . "' LIMIT 0,1";
		$result = Database::query($query, __FILE__, __LINE__);

		if($keysCourseId == DEFAULT_COURSE || Database::num_rows($result))
		{
			$keysAreUnique = false;

			$tryNewFSCId ++;

			$finalSuffix['CourseId'] = substr(md5(uniqid(rand())), 0, 4);
		}

		if($_configuration['single_database'])
		{
			$query = "SHOW TABLES FROM `".$_configuration['main_database']."` LIKE '".$_configuration['table_prefix']."$keysCourseDbName".$_configuration['db_glue']."%'";
			$result = Database::query($query, __FILE__, __LINE__);
		}
		else
		{
			$query = "SHOW DATABASES LIKE '$keysCourseDbName'";
			$result = Database::query($query, __FILE__, __LINE__);
		}

		if(Database::num_rows($result))
		{
			$keysAreUnique = false;

			$tryNewFSCDb ++;

			$finalSuffix['CourseDb'] = substr('_'.md5(uniqid(rand())), 0, 4);
		}

		// @todo: use and api_get_path here instead of constructing it by yourself
		if(file_exists($_configuration['root_sys'].$_configuration['course_folder'].$keysCourseRepository))
		{
			$keysAreUnique = false;

			$tryNewFSCDir ++;

			$finalSuffix['CourseDir'] = substr(md5(uniqid(rand())), 0, 4);
		}

		if(($tryNewFSCId + $tryNewFSCDb + $tryNewFSCDir) > $limitNumbTry)
		{
			return $keys;
		}
	}

	// db name can't begin with a number
	if(!stristr("abcdefghijklmnopqrstuvwxyz", $keysCourseDbName[0]))
	{
		$keysCourseDbName = $prefixAntiNumber.$keysCourseDbName;
	}

	$keys["currentCourseCode"] = $keysCourseCode;
	$keys["currentCourseId"] = $keysCourseId;
	$keys["currentCourseDbName"] = $keysCourseDbName;
	$keys["currentCourseRepository"] = $keysCourseRepository;

	return $keys;
}

/**
 *
 *
 */
function prepare_course_repository($courseRepository, $courseId)
{
	umask(0);
	$perm = api_get_setting('permissions_for_new_directories');
	$perm = octdec(!empty($perm)?$perm:'0770');
    $perm_file = api_get_setting('permissions_for_new_files');
    $perm_file = octdec(!empty($perm_file)?$perm_file:'0660');
	mkdir(api_get_path(SYS_COURSE_PATH).$courseRepository, $perm);
	mkdir(api_get_path(SYS_COURSE_PATH).$courseRepository . "/document", $perm);
	mkdir(api_get_path(SYS_COURSE_PATH).$courseRepository . "/document/images", $perm);
	mkdir(api_get_path(SYS_COURSE_PATH).$courseRepository . "/document/shared_folder/", $perm);
	mkdir(api_get_path(SYS_COURSE_PATH).$courseRepository . "/document/audio", $perm);
	mkdir(api_get_path(SYS_COURSE_PATH).$courseRepository . "/document/animations", $perm);
	mkdir(api_get_path(SYS_COURSE_PATH).$courseRepository . "/document/video", $perm);
	
	mkdir(api_get_path(SYS_COURSE_PATH).$courseRepository . "/document/mascot", $perm);	
	mkdir(api_get_path(SYS_COURSE_PATH).$courseRepository . "/document/mindmaps", 0777);//require 0777 because used by java socket
	mkdir(api_get_path(SYS_COURSE_PATH).$courseRepository . "/document/mindmaps/xmind", $perm);
	mkdir(api_get_path(SYS_COURSE_PATH).$courseRepository . "/document/photos", $perm);
	mkdir(api_get_path(SYS_COURSE_PATH).$courseRepository . "/document/podcasts", $perm);
	mkdir(api_get_path(SYS_COURSE_PATH).$courseRepository . "/document/screencasts", $perm);
	mkdir(api_get_path(SYS_COURSE_PATH).$courseRepository . "/document/themes", $perm);
	mkdir(api_get_path(SYS_COURSE_PATH).$courseRepository . "/document/themes/images", $perm);
	mkdir(api_get_path(SYS_COURSE_PATH).$courseRepository . "/document/themes/img", $perm);
	
	mkdir(api_get_path(SYS_COURSE_PATH).$courseRepository . "/dropbox", $perm);
	mkdir(api_get_path(SYS_COURSE_PATH).$courseRepository . "/group", $perm);
	mkdir(api_get_path(SYS_COURSE_PATH).$courseRepository . "/page", $perm);
	mkdir(api_get_path(SYS_COURSE_PATH).$courseRepository . "/scorm", $perm);
	mkdir(api_get_path(SYS_COURSE_PATH).$courseRepository . "/temp", $perm);
	mkdir(api_get_path(SYS_COURSE_PATH).$courseRepository . "/upload", $perm);
	mkdir(api_get_path(SYS_COURSE_PATH).$courseRepository . "/upload/forum", $perm);
	mkdir(api_get_path(SYS_COURSE_PATH).$courseRepository . "/upload/forum/images", $perm);
	mkdir(api_get_path(SYS_COURSE_PATH).$courseRepository . "/upload/test", $perm);
	mkdir(api_get_path(SYS_COURSE_PATH).$courseRepository . "/upload/blog", $perm);
	mkdir(api_get_path(SYS_COURSE_PATH).$courseRepository . "/upload/learning_path", $perm);
	mkdir(api_get_path(SYS_COURSE_PATH).$courseRepository . "/upload/learning_path/images", $perm);
	mkdir(api_get_path(SYS_COURSE_PATH).$courseRepository . "/upload/calendar", $perm);
	mkdir(api_get_path(SYS_COURSE_PATH).$courseRepository . "/upload/calendar/images", $perm);
	mkdir(api_get_path(SYS_COURSE_PATH).$courseRepository . "/work", $perm);
	mkdir(api_get_path(SYS_COURSE_PATH).$courseRepository . "/upload/announcements", $perm);
	mkdir(api_get_path(SYS_COURSE_PATH).$courseRepository . "/upload/announcements/images", $perm);

	//create .htaccess in dropbox
	$fp = fopen(api_get_path(SYS_COURSE_PATH).$courseRepository . "/dropbox/.htaccess", "w");
	fwrite($fp, "AuthName AllowLocalAccess
	               AuthType Basic

	               order deny,allow
	               deny from all

	               php_flag zlib.output_compression off");
	fclose($fp);

	// build index.php of course
	$fd = fopen(api_get_path(SYS_COURSE_PATH).$courseRepository . "/index.php", "w");

	// str_replace() removes \r that cause squares to appear at the end of each line
	$string = str_replace("\r", "", "<?" . "php
	\$cidReq = \"$courseId\";
	\$dbname = \"$courseId\";

	include(\"../../main/course_home/course_home.php\");
	?>");
	fwrite($fd,$string);
    $perm_file = api_get_setting('permissions_for_new_files');
    $perm_file = octdec(!empty($perm_file)?$perm_file:'0660');
    @chmod(api_get_path(SYS_COURSE_PATH).$courseRepository . '/index.php',$perm_file);
	$fd = fopen(api_get_path(SYS_COURSE_PATH).$courseRepository . '/group/index.html', 'w');
	$string = "<html></html>";
	fwrite($fd, "$string");
	return 0;
};

function update_Db_course($courseDbName)
{
	global $_configuration;

	if(!$_configuration['single_database'])
	{
		Database::query("CREATE DATABASE IF NOT EXISTS `" . $courseDbName . "`", __FILE__, __LINE__);
	}

	$courseDbName = $_configuration['table_prefix'].$courseDbName.$_configuration['db_glue'];

	$tbl_course_homepage 		= $courseDbName . 'tool';
	$TABLEINTROS 			= $courseDbName . 'tool_intro';

	// Group tool
	$TABLEGROUPS 			= $courseDbName . 'group_info';
	$TABLEGROUPCATEGORIES 		= $courseDbName . 'group_category';
	$TABLEGROUPUSER 		= $courseDbName . 'group_rel_user';
	$TABLEGROUPTUTOR 		= $courseDbName . 'group_rel_tutor';

	$TABLEITEMPROPERTY 		= $courseDbName . 'item_property';

	$TABLETOOLUSERINFOCONTENT 	= $courseDbName . 'userinfo_content';
	$TABLETOOLUSERINFODEF 		= $courseDbName . 'userinfo_def';

	$TABLETOOLCOURSEDESC		= $courseDbName . 'course_description';
	$TABLETOOLAGENDA 		= $courseDbName . 'calendar_event';
	$TABLETOOLAGENDAREPEAT		= $courseDbName . 'calendar_event_repeat';
	$TABLETOOLAGENDAREPEATNOT	= $courseDbName . 'calendar_event_repeat_not';
	$TABLETOOLAGENDAATTACHMENT	= $courseDbName . 'calendar_event_attachment';

	// Announcements
	$TABLETOOLANNOUNCEMENTS 		= $courseDbName . 'announcement';
	$TABLETOOLANNOUNCEMENTSATTACHMENT	= $courseDbName . 'announcement_attachment';

	// Resourcelinker
	$TABLEADDEDRESOURCES 		= $courseDbName . 'resource';

	// Student Publication
	$TABLETOOLWORKS 		= $courseDbName . 'student_publication';
	$TABLETOOLWORKSASS 		= $courseDbName . 'student_publication_assignment';

	// Document
	$TABLETOOLDOCUMENT 		= $courseDbName . 'document';

	// Forum
	$TABLETOOLFORUMCATEGORY 	= $courseDbName . 'forum_category';
	$TABLETOOLFORUM 		= $courseDbName . 'forum_forum';
	$TABLETOOLFORUMTHREAD 		= $courseDbName . 'forum_thread';
	$TABLETOOLFORUMPOST 		= $courseDbName . 'forum_post';
	$TABLETOOLFORUMMAILCUE 		= $courseDbName . 'forum_mailcue';
	$TABLETOOLFORUMATTACHMENT	= $courseDbName . 'forum_attachment';
	$TABLETOOLFORUMNOTIFICATION 	= $courseDbName . 'forum_notification';
	$TABLETOOLFORUMQUALIFY      	= $courseDbName . 'forum_thread_qualify';
	$TABLETOOLFORUMQUALIFYLOG	= $courseDbName . 'forum_thread_qualify_log';

	// Link
	$TABLETOOLLINK 			= $courseDbName . 'link';
	$TABLETOOLLINKCATEGORIES 	= $courseDbName . 'link_category';

	$TABLETOOLONLINECONNECTED 	= $courseDbName . 'online_connected';
	$TABLETOOLONLINELINK 		= $courseDbName . 'online_link';

	// Chat
	$TABLETOOLCHATCONNECTED 	= $courseDbName . 'chat_connected';

	// Quiz (a.k.a. exercises)
	$TABLEQUIZ 			= $courseDbName . 'quiz';
	$TABLEQUIZQUESTION 		= $courseDbName . 'quiz_rel_question';
	$TABLEQUIZQUESTIONLIST 		= $courseDbName . 'quiz_question';
	$TABLEQUIZANSWERSLIST 		= $courseDbName . 'quiz_answer';
	$TABLEQUIZSCENARIO 		= $courseDbName . 'quiz_scenario';
	$TABLEQUIZCATEGORY 		= $courseDbName . 'quiz_category';
	$TABLEQUIZTYPE 		= $courseDbName . 'quiz_type';

	// Dropbox
	$TABLETOOLDROPBOXPOST 		= $courseDbName . 'dropbox_post';
	$TABLETOOLDROPBOXFILE 		= $courseDbName . 'dropbox_file';
	$TABLETOOLDROPBOXPERSON 	= $courseDbName . 'dropbox_person';
	$TABLETOOLDROPBOXCATEGORY 	= $courseDbName . 'dropbox_category';
	$TABLETOOLDROPBOXFEEDBACK 	= $courseDbName . 'dropbox_feedback';

	// New Learning path
	$TABLELP			= $courseDbName . 'lp';
	$TABLELPITEM			= $courseDbName . 'lp_item';
	$TABLELPVIEW			= $courseDbName . 'lp_view';
	$TABLELPITEMVIEW		= $courseDbName . 'lp_item_view';
	$TABLELPIVINTERACTION		= $courseDbName . 'lp_iv_interaction';
	$TABLELPIVOBJECTIVE		= $courseDbName . 'lp_iv_objective';

	// Smartblogs
	$tbl_blogs			= $courseDbName . 'blog';
	$tbl_blogs_comments		= $courseDbName . 'blog_comment';
	$tbl_blogs_posts		= $courseDbName . 'blog_post';
	$tbl_blogs_rating		= $courseDbName . 'blog_rating';
	$tbl_blogs_rel_user		= $courseDbName . 'blog_rel_user';
	$tbl_blogs_tasks		= $courseDbName . 'blog_task';
	$tbl_blogs_tasks_rel_user	= $courseDbName . 'blog_task_rel_user';
	$tbl_blogs_attachment		= $courseDbName . 'blog_attachment';

	//Smartblogs permissions
	$tbl_permission_group		= $courseDbName . 'permission_group';
	$tbl_permission_user		= $courseDbName . 'permission_user';
	$tbl_permission_task		= $courseDbName . 'permission_task';

	//Smartblogs roles
	$tbl_role			= $courseDbName . 'role';
	$tbl_role_group			= $courseDbName . 'role_group';
	$tbl_role_permissions		= $courseDbName . 'role_permissions';
	$tbl_role_user			= $courseDbName . 'role_user';

	//Survey variables for course homepage;
	$TABLESURVEY 			= $courseDbName . 'survey';
	$TABLESURVEYQUESTION		= $courseDbName . 'survey_question';
	$TABLESURVEYQUESTIONOPTION	= $courseDbName . 'survey_question_option';
	$TABLESURVEYINVITATION		= $courseDbName . 'survey_invitation';
	$TABLESURVEYANSWER		= $courseDbName . 'survey_answer';
	$TABLESURVEYGROUP		= $courseDbName . 'survey_group';

	// Wiki
	$TABLETOOLWIKI 			= $courseDbName	. 'wiki';
	$TABLEWIKICONF			= $courseDbName	. 'wiki_conf';
	$TABLEWIKIDISCUSS		= $courseDbName . 'wiki_discuss';
	$TABLEWIKIMAILCUE		= $courseDbName . 'wiki_mailcue';

	// audiorecorder
	$TABLEAUDIORECORDER 		= $courseDbName.'audiorecorder';

	// Course settings
	$TABLESETTING 			= $courseDbName . 'course_setting';

	// Glossary
	$TBL_GLOSSARY   		= $courseDbName . 'glossary';

	// Notebook
	$TBL_NOTEBOOK   		= $courseDbName . 'notebook';
	/*
	-----------------------------------------------------------
		Announcement tool
	-----------------------------------------------------------
	*/
	$sql = "
		CREATE TABLE `".$TABLETOOLANNOUNCEMENTS . "` (
		id mediumint unsigned NOT NULL auto_increment,
		title text,
		content mediumtext,
		end_date date default NULL,
		display_order mediumint NOT NULL default 0,
		email_sent tinyint default 0,
		session_id smallint default 0,
		PRIMARY KEY (id)
		) ENGINE=MyISAM";
	Database::query($sql, __FILE__, __LINE__);
	$sql = "ALTER TABLE `".$TABLETOOLANNOUNCEMENTS . "` ADD INDEX ( session_id ) ";
	Database::query($sql, __FILE__, __LINE__);
	
	// Announcement Attachment
	$sql = "CREATE TABLE  `".$TABLETOOLANNOUNCEMENTSATTACHMENT."` (
			  id int NOT NULL auto_increment,
			  path varchar(255) NOT NULL,
			  comment text,
			  size int NOT NULL default 0,
			  announcement_id int NOT NULL,
			  filename varchar(255) NOT NULL,
			  PRIMARY KEY (id)
			)";
	Database::query($sql, __FILE__, __LINE__);

	/*
	-----------------------------------------------------------
		Resources
	-----------------------------------------------------------
	*/
	$sql = "
		CREATE TABLE `".$TABLEADDEDRESOURCES . "` (
		id int unsigned NOT NULL auto_increment,
		source_type varchar(50) default NULL,
		source_id int unsigned default NULL,
		resource_type varchar(50) default NULL,
		resource_id int unsigned default NULL,
		UNIQUE KEY id (id)
		) ENGINE=MyISAM";
	Database::query($sql, __FILE__, __LINE__);

	$sql = "
		CREATE TABLE `".$TABLETOOLUSERINFOCONTENT . "` (
		id int unsigned NOT NULL auto_increment,
		user_id int unsigned NOT NULL,
		definition_id int unsigned NOT NULL,
		editor_ip varchar(39) default NULL,
		edition_time datetime default NULL,
		content text NOT NULL,
		PRIMARY KEY (id),
		KEY user_id (user_id)
		) ENGINE=MyISAM";

	Database::query($sql, __FILE__, __LINE__);

	// Unused table. Temporarily ignored for tests.
	// Reused because of user/userInfo and user/userInfoLib scripts
	$sql = "
		CREATE TABLE `".$TABLETOOLUSERINFODEF . "` (
		id int unsigned NOT NULL auto_increment,
		title varchar(80) NOT NULL default '',
		comment text,
		line_count tinyint unsigned NOT NULL default 5,
		rank tinyint unsigned NOT NULL default 0,
		PRIMARY KEY (id)
		) ENGINE=MyISAM";

	Database::query($sql, __FILE__, __LINE__);

	/*
	-----------------------------------------------------------
		Forum tool
	-----------------------------------------------------------
	*/
	// Forum Category
	$sql = "
		CREATE TABLE `".$TABLETOOLFORUMCATEGORY . "` (
		 cat_id int NOT NULL auto_increment,
		 cat_title varchar(255) NOT NULL default '',
		 cat_comment text,
		 cat_order int NOT NULL default 0,
		 locked int NOT NULL default 0,
		 session_id smallint unsigned NOT NULL default 0,
		 PRIMARY KEY (cat_id)
		) ENGINE=MyISAM";

	Database::query($sql, __FILE__, __LINE__);
	$sql = "ALTER TABLE `".$TABLETOOLFORUMCATEGORY . "` ADD INDEX ( session_id ) ";
	Database::query($sql, __FILE__, __LINE__);

	// Forum
	$sql = "
		CREATE TABLE `".$TABLETOOLFORUM . "` (
		 forum_id int NOT NULL auto_increment,
		 forum_title varchar(255) NOT NULL default '',
		 forum_comment text,
		 forum_threads int default 0,
		 forum_posts int default 0,
		 forum_last_post int default 0,
		 forum_category int default NULL,
		 allow_anonymous int default NULL,
		 allow_edit int default NULL,
		 approval_direct_post varchar(20) default NULL,
		 allow_attachments int default NULL,
		 allow_new_threads int default NULL,
		 default_view varchar(20) default NULL,
		 forum_of_group varchar(20) default NULL,
		 forum_group_public_private varchar(20) default 'public',
		 forum_order int default NULL,
		 locked int NOT NULL default 0,
		 session_id int NOT NULL default 0,
		 forum_image varchar(255) NOT NULL default '',
		 PRIMARY KEY (forum_id)
		) ENGINE=MyISAM";

	Database::query($sql, __FILE__, __LINE__);
        
        
	// Forum Threads
	$sql = "
		CREATE TABLE `".$TABLETOOLFORUMTHREAD . "` (
		 thread_id int NOT NULL auto_increment,
		 thread_title varchar(255) default NULL,
		 forum_id int default NULL,
		 thread_replies int default 0,
		 thread_poster_id int default NULL,
		 thread_poster_name varchar(100) default '',
		 thread_views int default 0,
		 thread_last_post int default NULL,
		 thread_date datetime default '0000-00-00 00:00:00',
		 thread_sticky tinyint unsigned default 0,
		 locked int NOT NULL default 0,
  		 session_id int unsigned default NULL,
         thread_title_qualify varchar(255) default '',
         thread_qualify_max float(6,2) UNSIGNED NOT NULL default 0,
         thread_close_date datetime default '0000-00-00 00:00:00',
         thread_weight float(6,2) UNSIGNED NOT NULL default 0,
		 PRIMARY KEY (thread_id)
		) ENGINE=MyISAM";

	Database::query($sql, __FILE__, __LINE__);
	$sql = "ALTER TABLE `".$TABLETOOLFORUMTHREAD . "` ADD INDEX idx_forum_thread_forum_id (forum_id)";
	Database::query($sql, __FILE__, __LINE__);

	// Forum Posts
	$sql = "
		CREATE TABLE `".$TABLETOOLFORUMPOST . "` (
		 post_id int NOT NULL auto_increment,
		 post_title varchar(250) default NULL,
		 post_text text,
		 thread_id int default 0,
		 forum_id int default 0,
		 poster_id int default 0,
		 poster_name varchar(100) default '',
		 post_date datetime default '0000-00-00 00:00:00',
		 post_notification tinyint default 0,
		 post_parent_id int default 0,
		 visible tinyint default 1,
		 PRIMARY KEY (post_id),
		 KEY poster_id (poster_id),
		 KEY forum_id (forum_id)
		) ENGINE=MyISAM";

	Database::query($sql, __FILE__, __LINE__);
	$sql = "ALTER TABLE `".$TABLETOOLFORUMPOST . "` ADD INDEX idx_forum_post_thread_id (thread_id)";
	Database::query($sql, __FILE__, __LINE__);
	$sql = "ALTER TABLE `".$TABLETOOLFORUMPOST . "` ADD INDEX idx_forum_post_visible (visible)";
	Database::query($sql, __FILE__, __LINE__);

	// Forum Mailcue
	$sql = "
		CREATE TABLE `".$TABLETOOLFORUMMAILCUE . "` (
		 thread_id int default NULL,
		 user_id int default NULL,
		 post_id int default NULL
		) ENGINE=MyISAM";

	Database::query($sql, __FILE__, __LINE__);


	// Forum Attachment
	$sql = "CREATE TABLE  `".$TABLETOOLFORUMATTACHMENT."` (
			  id int NOT NULL auto_increment,
			  path varchar(255) NOT NULL,
			  comment text,
			  size int NOT NULL default 0,
			  post_id int NOT NULL,
			  filename varchar(255) NOT NULL,
			  PRIMARY KEY (id)
			)";
	Database::query($sql, __FILE__, __LINE__);

	// Forum notification
	$sql = "CREATE TABLE  `".$TABLETOOLFORUMNOTIFICATION."` (
			  user_id int,
			  forum_id int,
			  thread_id int,
			  post_id int,
			    KEY user_id (user_id),
  				KEY forum_id (forum_id)
			)";
	Database::query($sql, __FILE__, __LINE__);

	// Forum thread qualify :Add table forum_thread_qualify
	$sql = "CREATE TABLE  `".$TABLETOOLFORUMQUALIFY."` (
			id int unsigned PRIMARY KEY AUTO_INCREMENT,
			user_id int unsigned NOT NULL,
  			thread_id int NOT NULL,
  			qualify float(6,2) NOT NULL default 0,
 			qualify_user_id int  default NULL,
 			qualify_time datetime default '0000-00-00 00:00:00',
 			session_id int  default NULL
			)";
	Database::query($sql, __FILE__, __LINE__);
	$sql = "ALTER TABLE `".$TABLETOOLFORUMQUALIFY . "` ADD INDEX (user_id, thread_id)";
	Database::query($sql, __FILE__, __LINE__);

	//Forum thread qualify: Add table forum_thread_qualify_historical
	$sql = "CREATE TABLE  `".$TABLETOOLFORUMQUALIFYLOG."` (
			id int unsigned PRIMARY KEY AUTO_INCREMENT,
			user_id int unsigned NOT NULL,
  			thread_id int NOT NULL,
  			qualify float(6,2) NOT NULL default 0,
 			qualify_user_id int default NULL,
 			qualify_time datetime default '0000-00-00 00:00:00',
 			session_id int default NULL
			)";
	Database::query($sql, __FILE__, __LINE__);
	$sql = "ALTER TABLE `".$TABLETOOLFORUMQUALIFYLOG. "` ADD INDEX (user_id, thread_id)";
	Database::query($sql, __FILE__, __LINE__);
	/*
	-----------------------------------------------------------
		Exercise tool
	-----------------------------------------------------------
	*/
	// Exercise tool - Tests/exercises
	$sql = "
		CREATE TABLE `".$TABLEQUIZ . "` (
		id mediumint unsigned NOT NULL auto_increment,
		title varchar(200) NOT NULL,
		description text default NULL,
		sound varchar(50) default NULL,
		type tinyint unsigned NOT NULL default 1,
		random smallint(6) NOT NULL default 0,
		active tinyint NOT NULL default 0,
		results_disabled TINYINT UNSIGNED NOT NULL DEFAULT 0,
		access_condition TEXT DEFAULT NULL,
		max_attempt int NOT NULL default 0,
		start_time datetime NOT NULL default '0000-00-00 00:00:00',
		end_time datetime NOT NULL default '0000-00-00 00:00:00',
		feedback_type int NOT NULL default 0,
    	expired_time int NOT NULL default '0',
    	position smallint unsigned NOT NULL default 1, 
		session_id smallint default 0,
		PRIMARY KEY (id)
		)";
	Database::query($sql, __FILE__, __LINE__);
	$sql = "ALTER TABLE `".$TABLEQUIZ . "` ADD INDEX ( session_id ) ";
	Database::query($sql, __FILE__, __LINE__);

	// Exercise tool - questions
	$sql = "
		CREATE TABLE `".$TABLEQUIZQUESTIONLIST . "` (
		id mediumint unsigned NOT NULL auto_increment,
		question text NOT NULL,
		description text default NULL,
		ponderation float(6,2) NOT NULL default 0,
		position mediumint unsigned NOT NULL default 1,
		type tinyint unsigned NOT NULL default 2,
		picture varchar(50) default NULL,
		level int unsigned NOT NULL default 0,
		category varchar(255) NOT NULL default 0,	
		media_position varchar(50) NOT NULL default 'right',
		PRIMARY KEY (id)
		)";
	Database::query($sql, __FILE__, __LINE__);
	$sql = "ALTER TABLE `".$TABLEQUIZQUESTIONLIST . "` ADD INDEX (position)";
	Database::query($sql, __FILE__, __LINE__);

	// Exercise tool - answers
	$sql = "
		CREATE TABLE `".$TABLEQUIZANSWERSLIST . "` (
		id mediumint unsigned NOT NULL,
		question_id mediumint unsigned NOT NULL,
		answer text NOT NULL,
		correct mediumint unsigned default NULL,
		comment text default NULL,
		ponderation float(6,2) NOT NULL default 0,
		position mediumint unsigned NOT NULL default 1,
	    hotspot_coordinates text,
	    hotspot_type enum('square','circle','poly','delineation','oar') default NULL,
	    destination text NOT NULL,
		PRIMARY KEY (id, question_id)
		)";
	Database::query($sql, __FILE__, __LINE__);

	// Exercise tool - Test/question relations
	$sql = "
		CREATE TABLE `".$TABLEQUIZQUESTION . "` (
		question_id mediumint unsigned NOT NULL,
		exercice_id mediumint unsigned NOT NULL,
		question_order mediumint unsigned NOT NULL default 1,
		PRIMARY KEY (question_id,exercice_id)
		)";
	Database::query($sql, __FILE__, __LINE__);

	// Exercise tool - Scenarios
	$sql = "
		CREATE TABLE `".$TABLEQUIZSCENARIO . "` (
		id mediumint unsigned NOT NULL auto_increment,
		exercice_id mediumint unsigned NOT NULL,
		scenario_type int NOT NULL default 0,
		title varchar(200) NOT NULL,
		description text default NULL,
		sound varchar(50) default NULL,
		type tinyint unsigned NOT NULL default 1,
		random smallint(6) NOT NULL default 0,
		active tinyint NOT NULL default 0,
		results_disabled TINYINT UNSIGNED NOT NULL DEFAULT 0,
		access_condition TEXT DEFAULT NULL,
		max_attempt int NOT NULL default 0,
		start_time datetime NOT NULL default '0000-00-00 00:00:00',
		end_time datetime NOT NULL default '0000-00-00 00:00:00',
		feedback_type int NOT NULL default 0,
  expired_time int NOT NULL default '0',
		session_id smallint default 0,
		PRIMARY KEY (id)
		)";
	Database::query($sql, __FILE__, __LINE__);
	$sql = "ALTER TABLE `".$TABLEQUIZSCENARIO . "` ADD INDEX ( session_id ) ";
	Database::query($sql, __FILE__, __LINE__);
	$sql = "ALTER TABLE `".$TABLEQUIZSCENARIO . "` ADD INDEX ( exercice_id ) ";
	Database::query($sql, __FILE__, __LINE__);

	// Exercise tool - Quiz category
	$sql = "
		CREATE TABLE `".$TABLEQUIZCATEGORY . "` (
		id mediumint unsigned NOT NULL AUTO_INCREMENT,
		category_title varchar(255) NOT NULL,
		display_order mediumint unsigned NOT NULL default 0,
		session_id smallint default 0,
		PRIMARY KEY (id)		
		)";
	Database::query($sql, __FILE__, __LINE__);

	// Exercise tool - Quiz type
	$sql = "
		CREATE TABLE `".$TABLEQUIZTYPE . "` (
		id mediumint unsigned NOT NULL AUTO_INCREMENT,
		exercice_id mediumint unsigned NOT NULL,
		category_id mediumint unsigned NOT NULL,
		quiz_level varchar(50) DEFAULT NULL,
		number_of_question smallint default '0',
		scenario_type mediumint unsigned NOT NULL default '1',
		current_active mediumint unsigned NOT NULL default '0',
		session_id smallint default '0',
		PRIMARY KEY (id,exercice_id)	
		)";
	Database::query($sql, __FILE__, __LINE__);

	/*
	-----------------------------------------------------------
		Course description
	-----------------------------------------------------------
	*/
	$sql = "
		CREATE TABLE `".$TABLETOOLCOURSEDESC . "` (
		id TINYINT UNSIGNED NOT NULL auto_increment,
		title VARCHAR(255),
		content TEXT,
		session_id smallint default 0,
		description_type tinyint unsigned NOT NULL default 0,		
		UNIQUE (id)
		)";
	Database::query($sql, __FILE__, __LINE__);
	$sql = "ALTER TABLE `".$TABLETOOLCOURSEDESC . "` ADD INDEX ( session_id ) ";
	Database::query($sql, __FILE__, __LINE__);

	/*
	-----------------------------------------------------------
		Course homepage tool list
	-----------------------------------------------------------
	*/
	$sql = "
		CREATE TABLE `" . $tbl_course_homepage . "` (
		id int unsigned NOT NULL auto_increment,
		name varchar(100) NOT NULL,
		link varchar(255) NOT NULL,
		image varchar(100) default NULL,
		visibility tinyint unsigned default 0,
		admin varchar(200) default NULL,
		address varchar(120) default NULL,
		added_tool tinyint unsigned default 1,
		target enum('_self','_blank') NOT NULL default '_self',
		category enum('authoring','interaction','admin') NOT NULL default 'authoring',
		session_id smallint default 0,
		PRIMARY KEY (id)
		) ENGINE=MyISAM";
	Database::query($sql, __FILE__, __LINE__);
	$sql = "ALTER TABLE `".$tbl_course_homepage . "` ADD INDEX ( session_id ) ";
	Database::query($sql, __FILE__, __LINE__);

	/*
	-----------------------------------------------------------
		Agenda tool
	-----------------------------------------------------------
	*/
	$sql = "
		CREATE TABLE `".$TABLETOOLAGENDA . "` (
		id int unsigned NOT NULL auto_increment,
		title varchar(200) NOT NULL,
		content text,
		start_date datetime NOT NULL default '0000-00-00 00:00:00',
		end_date datetime NOT NULL default '0000-00-00 00:00:00',
    	parent_event_id INT NULL,
    	session_id int unsigned NOT NULL default 0,
		PRIMARY KEY (id)
		)";
	Database::query($sql, __FILE__, __LINE__);
	$sql = "ALTER TABLE `".$TABLETOOLAGENDA . "` ADD INDEX ( session_id ) ;";
	Database::query($sql, __FILE__, __LINE__);

	$sql = "
		CREATE TABLE `".$TABLETOOLAGENDAREPEAT. "` (
		cal_id INT DEFAULT 0 NOT NULL,
		cal_type VARCHAR(20),
		cal_end INT,
		cal_frequency INT DEFAULT 1,
		cal_days CHAR(7),
		PRIMARY KEY (cal_id)
		)";
	Database::query($sql,__FILE__,__LINE__);
	$sql = "
		CREATE TABLE `".$TABLETOOLAGENDAREPEATNOT."` (
		cal_id INT NOT NULL,
		cal_date INT NOT NULL,
		PRIMARY KEY ( cal_id, cal_date )
		)";
	Database::query($sql,__FILE__,__LINE__);


	// Agenda Attachment
	$sql = "CREATE TABLE  `".$TABLETOOLAGENDAATTACHMENT."` (
			  id int NOT NULL auto_increment,
			  path varchar(255) NOT NULL,
			  comment text,
			  size int NOT NULL default 0,
			  agenda_id int NOT NULL,
			  filename varchar(255) NOT NULL,
			  PRIMARY KEY (id)
			)";
	Database::query($sql, __FILE__, __LINE__);
	/*
	-----------------------------------------------------------
		Document tool
	-----------------------------------------------------------
	*/
	$sql = "
		CREATE TABLE `".$TABLETOOLDOCUMENT . "` (
			id int unsigned NOT NULL auto_increment,
			path varchar(255) NOT NULL default '',
			comment text,
			title varchar(255) default NULL,
			filetype set('file','folder') NOT NULL default 'file',
			size int NOT NULL default 0,
			display_order int NOT NULL default 0,
			readonly TINYINT UNSIGNED NOT NULL,
			session_id int UNSIGNED NOT NULL default 0,
			PRIMARY KEY (`id`)
		)";
	Database::query($sql, __FILE__, __LINE__);

	/*
	-----------------------------------------------------------
		Student publications
	-----------------------------------------------------------
	*/
	$sql = "
		CREATE TABLE `".$TABLETOOLWORKS . "` (
		id int unsigned NOT NULL auto_increment,
		url varchar(200) default NULL,
		title varchar(200) default NULL,
		description varchar(250) default NULL,
		author varchar(200) default NULL,
		active tinyint default NULL,
		accepted tinyint default 0,
		post_group_id int DEFAULT 0 NOT NULL,
		sent_date datetime NOT NULL default '0000-00-00 00:00:00',
		filetype set('file','folder') NOT NULL default 'file',
		has_properties int UNSIGNED NOT NULL DEFAULT 0,
		view_properties tinyint NULL,
		qualification float(6,2) UNSIGNED NOT NULL DEFAULT 0,
 		date_of_qualification datetime NOT NULL default '0000-00-00 00:00:00',
 		parent_id INT UNSIGNED NOT NULL DEFAULT 0,
		qualificator_id INT UNSIGNED NOT NULL DEFAULT 0,
		weight float(6,2) UNSIGNED NOT NULL default 0,
		remark text,
		session_id INT UNSIGNED NOT NULL default 0,
        PRIMARY KEY (id)
		)";
	Database::query($sql, __FILE__, __LINE__);

	$sql = "
        CREATE TABLE `".$TABLETOOLWORKSASS."` (
        id int NOT NULL auto_increment,
        expires_on datetime NOT NULL default '0000-00-00 00:00:00',
        ends_on datetime NOT NULL default '0000-00-00 00:00:00',
        add_to_calendar tinyint NOT NULL,
        enable_qualification tinyint NOT NULL,
        publication_id int NOT NULL,
        PRIMARY KEY  (id)" .
        ")";
	Database::query($sql, __FILE__, __LINE__);
	$sql = "ALTER TABLE `".$TABLETOOLWORKS . "` ADD INDEX ( session_id )" ;
	Database::query($sql, __FILE__, __LINE__);
	/*
	-----------------------------------------------------------
		Links tool
	-----------------------------------------------------------
	*/
	$sql = "
		CREATE TABLE `".$TABLETOOLLINK . "` (
		id int unsigned NOT NULL auto_increment,
		url TEXT NOT NULL,
		title varchar(150) default NULL,
		description text,
		category_id smallint unsigned default NULL,
		display_order smallint unsigned NOT NULL default 0,
		on_homepage enum('0','1') NOT NULL default '0',
		target char(10) default '_self',
		session_id smallint default 0,
		PRIMARY KEY (id)
		)";
	Database::query($sql, __FILE__, __LINE__);
	$sql = "ALTER TABLE `".$TABLETOOLLINK . "` ADD INDEX ( session_id ) ";
	Database::query($sql, __FILE__, __LINE__);

	$sql = "
		CREATE TABLE `".$TABLETOOLLINKCATEGORIES . "` (
		id smallint unsigned NOT NULL auto_increment,
		category_title varchar(255) NOT NULL,
		description text,
		display_order mediumint unsigned NOT NULL default 0,
		session_id smallint default 0,
		PRIMARY KEY (id)
		)";
	Database::query($sql, __FILE__, __LINE__);
	$sql = "ALTER TABLE `".$TABLETOOLLINKCATEGORIES . "` ADD INDEX ( session_id ) ";
	Database::query($sql, __FILE__, __LINE__);

/*
	-----------------------------------------------------------
		Wiki
	-----------------------------------------------------------
	*/

	$sql = "CREATE TABLE `".$TABLETOOLWIKI . "` (
		id int NOT NULL auto_increment,
		page_id int NOT NULL default 0,
		reflink varchar(255) NOT NULL default 'index',
		title varchar(255) NOT NULL,
		content mediumtext NOT NULL,
		user_id int NOT NULL default 0,
		group_id int DEFAULT NULL,
		dtime datetime NOT NULL default '0000-00-00 00:00:00',
		addlock int NOT NULL default 1,
		editlock int NOT NULL default 0,
		visibility int NOT NULL default 1,
		addlock_disc int NOT NULL default 1,
		visibility_disc int NOT NULL default 1,
		ratinglock_disc int NOT NULL default 1,
		assignment int NOT NULL default 0,
		comment text NOT NULL,
		progress text NOT NULL,
		score int NULL default 0,
		version int default NULL,
		is_editing int NOT NULL default 0,
		time_edit datetime NOT NULL default '0000-00-00 00:00:00',
		hits int default 0,
		linksto text NOT NULL,
		tag text NOT NULL,
		user_ip varchar(39) NOT NULL,
		session_id smallint default 0,
		PRIMARY KEY (id),
		KEY reflink (reflink),
		KEY group_id (group_id),
		KEY page_id (page_id)
		)";
	Database::query($sql, __FILE__, __LINE__);
	$sql = "ALTER TABLE `".$TABLETOOLWIKI . "` ADD INDEX ( session_id ) ";
	Database::query($sql, __FILE__, __LINE__);
        
       

	//
	$sql = "CREATE TABLE `".$TABLEWIKICONF . "` (
		page_id int NOT NULL default 0,
		task text NOT NULL,
		feedback1 text NOT NULL,
		feedback2 text NOT NULL,
		feedback3 text NOT NULL,
		fprogress1 varchar(3) NOT NULL,
		fprogress2 varchar(3) NOT NULL,
		fprogress3 varchar(3) NOT NULL,
		max_size int default NULL,
		max_text int default NULL,
		max_version int default NULL,
		startdate_assig datetime NOT NULL default '0000-00-00 00:00:00',
		enddate_assig datetime  NOT NULL default '0000-00-00 00:00:00',
		delayedsubmit int NOT NULL default 0,
		KEY page_id (page_id)
		)";
	Database::query($sql, __FILE__, __LINE__);

	//

	$sql = "CREATE TABLE `".$TABLEWIKIDISCUSS . "` (
		id int NOT NULL auto_increment,
		publication_id int NOT NULL default 0,
		userc_id int NOT NULL default 0,
		comment text NOT NULL,
		p_score varchar(255) default NULL,
		dtime datetime NOT NULL default '0000-00-00 00:00:00',
		PRIMARY KEY (id)
		)";
	Database::query($sql, __FILE__, __LINE__);

	//

	$sql = "CREATE TABLE `".$TABLEWIKIMAILCUE . "` (
		id int NOT NULL,
		user_id int NOT NULL,
		type text NOT NULL,
		group_id int DEFAULT NULL,
		KEY (id)
		)";
	Database::query($sql, __FILE__, __LINE__);



	/*
	-----------------------------------------------------------
		Online
	-----------------------------------------------------------
	*/
	$sql = "
		CREATE TABLE `".$TABLETOOLONLINECONNECTED . "` (
		user_id int unsigned NOT NULL,
		last_connection datetime NOT NULL default '0000-00-00 00:00:00',
		PRIMARY KEY (user_id)
		)";
	Database::query($sql, __FILE__, __LINE__);

	$sql = "
		CREATE TABLE `".$TABLETOOLONLINELINK . "` (
		id smallint unsigned NOT NULL auto_increment,
		name char(50) NOT NULL default '',
		url char(100) NOT NULL,
		PRIMARY KEY (id)
		)";
	Database::query($sql, __FILE__, __LINE__);

	$sql = "
		CREATE TABLE `".$TABLETOOLCHATCONNECTED . "` (
		user_id int unsigned NOT NULL default '0',
		last_connection datetime NOT NULL default '0000-00-00 00:00:00',
		session_id smallint NOT NULL default 0,
		to_group_id INT NOT NULL default 0		
		)";
	Database::query($sql, __FILE__, __LINE__);
	$sql = "ALTER TABLE `".$TABLETOOLCHATCONNECTED . "` ADD INDEX `char_connected_index`(user_id, session_id, to_group_id) ";
	Database::query($sql, __FILE__, __LINE__);
	/*
	-----------------------------------------------------------
		Groups tool
	-----------------------------------------------------------
	*/
	Database::query("CREATE TABLE `".$TABLEGROUPS . "` (
		id int unsigned NOT NULL auto_increment,
		name varchar(100) default NULL,
		category_id int unsigned NOT NULL default 0,
		description text,
		max_student smallint unsigned NOT NULL default 8,
		doc_state tinyint unsigned NOT NULL default 1,
		calendar_state tinyint unsigned NOT NULL default 0,
		work_state tinyint unsigned NOT NULL default 0,
		announcements_state tinyint unsigned NOT NULL default 0,
		forum_state tinyint unsigned NOT NULL default 0,
		wiki_state tinyint unsigned NOT NULL default 1,
		chat_state tinyint unsigned NOT NULL default 1,
		secret_directory varchar(255) default NULL,
		self_registration_allowed tinyint unsigned NOT NULL default '0',
		self_unregistration_allowed tinyint unsigned NOT NULL default '0',
		session_id smallint unsigned NOT NULL default 0,
		PRIMARY KEY (id)
		)", __FILE__, __LINE__);
	Database::query("ALTER TABLE `".$TABLEGROUPS . "` ADD INDEX ( session_id )", __FILE__,__LINE__);

	Database::query("CREATE TABLE `".$TABLEGROUPCATEGORIES . "` (
		id int unsigned NOT NULL auto_increment,
		title varchar(255) NOT NULL default '',
		description text NOT NULL,
		icon varchar(50) NOT NULL default '',
		doc_state tinyint unsigned NOT NULL default 1,
		calendar_state tinyint unsigned NOT NULL default 1,
		work_state tinyint unsigned NOT NULL default 1,
		announcements_state tinyint unsigned NOT NULL default 1,
		forum_state tinyint unsigned NOT NULL default 1,
		wiki_state tinyint unsigned NOT NULL default 1,
		chat_state tinyint unsigned NOT NULL default 1,
		group_state tinyint unsigned NOT NULL default 1,
		max_student smallint unsigned NOT NULL default 8,
		self_reg_allowed tinyint unsigned NOT NULL default 0,
		self_unreg_allowed tinyint unsigned NOT NULL default 0,
		groups_per_user smallint unsigned NOT NULL default 0,
		display_order smallint unsigned NOT NULL default 0,
		PRIMARY KEY (id)
		)", __FILE__, __LINE__);

	Database::query("CREATE TABLE `".$TABLEGROUPUSER . "` (
		id int unsigned NOT NULL auto_increment,
		user_id int unsigned NOT NULL,
		group_id int unsigned NOT NULL default 0,
		status int NOT NULL default 0,
		role char(50) NOT NULL,
		PRIMARY KEY (id)
		)", __FILE__, __LINE__);

	Database::query("CREATE TABLE `".$TABLEGROUPTUTOR . "` (
		id int NOT NULL auto_increment,
		user_id int NOT NULL,
		group_id int NOT NULL default 0,
		PRIMARY KEY (id)
		)", __FILE__, __LINE__);

	Database::query("CREATE TABLE `".$TABLEITEMPROPERTY . "` (
		tool varchar(100) NOT NULL default '',
		insert_user_id int unsigned NOT NULL default '0',
		insert_date datetime NOT NULL default '0000-00-00 00:00:00',
		lastedit_date datetime NOT NULL default '0000-00-00 00:00:00',
		ref int NOT NULL default '0',
		lastedit_type varchar(100) NOT NULL default '',
		lastedit_user_id int unsigned NOT NULL default '0',
		to_group_id int unsigned default NULL,
		to_user_id int unsigned default NULL,
		visibility tinyint NOT NULL default '1',
		start_visible datetime NOT NULL default '0000-00-00 00:00:00',
		end_visible datetime NOT NULL default '0000-00-00 00:00:00',
		id_session INT NOT NULL DEFAULT 0
		) ENGINE=MyISAM;", __FILE__, __LINE__);
	Database::query("ALTER TABLE `$TABLEITEMPROPERTY` ADD INDEX idx_item_property_toolref (tool,ref)", __FILE__, __LINE__);

	/*
	-----------------------------------------------------------
		Tool introductions
	-----------------------------------------------------------
	*/
	Database::query("
		CREATE TABLE `".$TABLEINTROS . "` (
		id varchar(50) NOT NULL,
		intro_text text NOT NULL,
		PRIMARY KEY (id))", __FILE__, __LINE__);

	/*
	-----------------------------------------------------------
		Dropbox tool
	-----------------------------------------------------------
	*/
	Database::query("
		CREATE TABLE `".$TABLETOOLDROPBOXFILE . "` (
		id int unsigned NOT NULL auto_increment,
		uploader_id int unsigned NOT NULL default 0,
		filename varchar(250) NOT NULL default '',
		filesize int unsigned NOT NULL,
		title varchar(250) default '',
		description varchar(250) default '',
		author varchar(250) default '',
		upload_date datetime NOT NULL default '0000-00-00 00:00:00',
		last_upload_date datetime NOT NULL default '0000-00-00 00:00:00',
		cat_id int NOT NULL default 0,
		session_id SMALLINT UNSIGNED NOT NULL,
                type int DEFAULT 0,
		PRIMARY KEY (id),
		UNIQUE KEY UN_filename (filename)
		)", __FILE__, __LINE__);

	Database::query("ALTER TABLE `$TABLETOOLDROPBOXFILE` ADD INDEX ( `session_id` )", __FILE__, __LINE__);

	Database::query("
		CREATE TABLE `".$TABLETOOLDROPBOXPOST . "` (
		file_id int unsigned NOT NULL,
		dest_user_id int unsigned NOT NULL default 0,
		feedback_date datetime NOT NULL default '0000-00-00 00:00:00',
		feedback text default '',
		cat_id int NOT NULL default 0,
		session_id SMALLINT UNSIGNED NOT NULL,
		PRIMARY KEY (file_id,dest_user_id)
		)", __FILE__, __LINE__);

	Database::query("ALTER TABLE `$TABLETOOLDROPBOXPOST` ADD INDEX ( `session_id` )", __FILE__, __LINE__);

	Database::query("
		CREATE TABLE `".$TABLETOOLDROPBOXPERSON . "` (
		file_id int unsigned NOT NULL,
		user_id int unsigned NOT NULL default 0,
		PRIMARY KEY (file_id,user_id)
		)", __FILE__, __LINE__);

	$sql = "CREATE TABLE `".$TABLETOOLDROPBOXCATEGORY."` (
  			cat_id int NOT NULL auto_increment,
			cat_name text NOT NULL,
  			received tinyint unsigned NOT NULL default 0,
  			sent tinyint unsigned NOT NULL default 0,
  			user_id int NOT NULL default 0,
  			session_id smallint NOT NULL default 0,		
  			PRIMARY KEY  (cat_id)
  			)";
	Database::query($sql, __FILE__, __LINE__);
	$sql = "ALTER TABLE `".$TABLETOOLDROPBOXCATEGORY . "` ADD INDEX ( session_id ) ";
	Database::query($sql, __FILE__, __LINE__);

	$sql = "CREATE TABLE `".$TABLETOOLDROPBOXFEEDBACK."` (
			  feedback_id int NOT NULL auto_increment,
			  file_id int NOT NULL default 0,
			  author_user_id int NOT NULL default 0,
			  feedback text NOT NULL,
			  feedback_date datetime NOT NULL default '0000-00-00 00:00:00',
			  PRIMARY KEY  (feedback_id),
			  KEY file_id (file_id),
			  KEY author_user_id (author_user_id)
  			)";
	Database::query($sql, __FILE__, __LINE__);

	/*
	-----------------------------------------------------------
		New learning path
	-----------------------------------------------------------
	*/
	$sql = "CREATE TABLE IF NOT EXISTS `$TABLELP` (" .
		"id				int	unsigned	primary key auto_increment," . //unique ID, generated by MySQL
		"lp_type		smallint	unsigned not null," .	//lp_types can be found in the main database's lp_type table
		"name			tinytext	not null," . //name is the text name of the learning path (e.g. Word 2000)
		"ref			tinytext	null," . //ref for SCORM elements is the SCORM ID in imsmanifest. For other learnpath types, just ignore
		"description	text	null,". //textual description
		"path 			text	not null," . //path, starting at the platforms root (so all paths should start with 'courses/...' for now)
		"force_commit  tinyint		unsigned not null default 0, " . //stores the default behaviour regarding SCORM information
		"default_view_mod char(32) not null default 'embedded'," .//stores the default view mode (embedded or fullscreen)
		"default_encoding char(32)	not null default 'UTF-8', " . //stores the encoding detected at learning path reading
		"display_order int		unsigned	not null default 0," . //order of learnpaths display in the learnpaths list - not really important
		"content_maker tinytext  not null default ''," . //the content make for this course (ENI, Articulate, ...)
		"content_local 	varchar(32)  not null default 'local'," . //content localisation ('local' or 'distant')
		"content_license	text not null default ''," . //content license
		"prevent_reinit tinyint		unsigned not null default 1," . //stores the default behaviour regarding items re-initialisation when viewed a second time after success
		"js_lib         tinytext    not null default ''," . //the JavaScript library to load for this lp
		"debug 			tinyint		unsigned not null default 0," . //stores the default behaviour regarding items re-initialisation when viewed a second time after success
		"theme 		varchar(255)    not null default '', " . //stores the theme of the LP
		"preview_image	varchar(255)    not null default '', " . //stores the theme of the LP
		"author 		varchar(255)    not null default '', " . //stores the theme of the LP
		"lp_interface 		int    not null default 0, " . //stores the default course interface of the LP
		"session_id  	int	unsigned not null  default 0".  //the session_id 
                ")";
	if(!Database::query($sql, __FILE__, __LINE__))
	{
		error_log($sql,0);
	}

	$sql = "CREATE TABLE IF NOT EXISTS `$TABLELPVIEW` (" .
		"id				int		unsigned	primary key auto_increment," . //unique ID from MySQL
		"lp_id			int		unsigned	not null," . //learnpath ID from 'lp'
		"user_id		int 	unsigned	not null," . //user ID from main.user
		"view_count		smallint unsigned	not null default 0," . //integer counting the amount of times this learning path has been attempted
		"last_item		int		unsigned	not null default 0," . //last item seen in this view
		"progress		int		unsigned	default 0 )"; //lp's progress for this user
	if(!Database::query($sql, __FILE__, __LINE__))
	{
		error_log($sql,0);
	}
	$sql = "ALTER TABLE `$TABLELPVIEW` ADD INDEX (lp_id) ";
	if(!Database::query($sql, __FILE__, __LINE__))
	{
		error_log($sql,0);
	}
	$sql = "ALTER TABLE `$TABLELPVIEW` ADD INDEX (user_id) ";
	if(!Database::query($sql, __FILE__, __LINE__))
	{
		error_log($sql,0);
	}

	$sql = "CREATE TABLE IF NOT EXISTS `$TABLELPITEM` (" .
		"id				int	unsigned	primary	key auto_increment," .	//unique ID from MySQL
		"lp_id			int unsigned	not null," .	//lp_id from 'lp'
		"item_type		char(32)	not null default 'dokeos_document'," . //can be dokeos_document, dokeos_chapter or scorm_asset, scorm_sco, scorm_chapter
		"ref			tinytext	not null default ''," . //the ID given to this item in the imsmanifest file
		"title			tinytext	not null," . //the title/name of this item (to display in the T.O.C.)
		"description	tinytext	not null default ''," . //the description of this item - deprecated
		"path			text		not null," . //the path to that item, starting at 'courses/...' level
		"min_score		float unsigned	not null default 0," . //min score allowed
		"max_score		float unsigned	not null default 100," . //max score allowed
		"mastery_score float unsigned null," . //minimum score to pass the test
		"parent_item_id		int unsigned	not null default 0," . //the item one level higher
		"previous_item_id	int unsigned	not null default 0," . //the item before this one in the sequential learning order (MySQL id)
		"next_item_id		int unsigned	not null default 0," . //the item after this one in the sequential learning order (MySQL id)
		"display_order		int unsigned	not null default 0," . //this is needed for ordering items under the same parent (previous_item_id doesn't give correct order after reordering)
		"prerequisite  text  null default null," . //prerequisites in AICC scripting language as defined in the SCORM norm (allow logical operators)
		"parameters  text  null," . //prerequisites in AICC scripting language as defined in the SCORM norm (allow logical operators)
		"launch_data 	text	not null default ''," . //data from imsmanifest <item>
		"max_time_allowed char(13) NULL default ''," . //data from imsmanifest <adlcp:maxtimeallowed>
        "terms TEXT NULL," . // contains the indexing tags (search engine)
        "search_did INT NULL,".// contains the internal search-engine id of this element
        "audio VARCHAR(250))"; // contains the audio file that goes with the learning path step
	if(!Database::query($sql, __FILE__, __LINE__))
	{
		error_log($sql,0);
	}
	$sql = "ALTER TABLE `$TABLELPITEM` ADD INDEX (lp_id)";
	if(!Database::query($sql, __FILE__, __LINE__))
	{
		error_log($sql,0);
	}

	$sql = "CREATE TABLE IF NOT EXISTS `$TABLELPITEMVIEW` (" .
		"id				bigint	unsigned	primary key auto_increment," . //unique ID
		"lp_item_id		int unsigned	not null," . //item ID (MySQL id)
		"lp_view_id		int unsigned 	not null," . // learning path view id (attempt)
		"view_count		int unsigned	not null default 0," . //how many times this item has been viewed in the current attempt (generally 0 or 1)
		"start_time		int unsigned	not null," . //when did the user open it?
		"total_time		int unsigned not null default 0," . //after how many seconds did he close it?
		"score			float unsigned not null default 0," . //score returned by SCORM or other techs
		"status			char(32) not null default 'not attempted'," . //status for this item (SCORM)
		"suspend_data	text null default ''," .
		"lesson_location text null default ''," .
		"core_exit		varchar(32) not null default 'none'," .
		"max_score		varchar(8) default ''" .
		")";
	if(!Database::query($sql, __FILE__, __LINE__))
	{
		error_log($sql,0);
	}
	$sql = "ALTER TABLE `$TABLELPITEMVIEW` ADD INDEX (lp_item_id) ";
	if(!Database::query($sql, __FILE__, __LINE__))
	{
		error_log($sql,0);
	}
	$sql = "ALTER TABLE `$TABLELPITEMVIEW` ADD INDEX (lp_view_id) ";
	if(!Database::query($sql, __FILE__, __LINE__))
	{
		error_log($sql,0);
	}

	$sql = "CREATE TABLE IF NOT EXISTS `$TABLELPIVINTERACTION`(" .
		"id				bigint	unsigned 		primary key auto_increment," .
		"order_id		smallint	unsigned	not null default 0,". //internal order (0->...) given by Dokeos
		"lp_iv_id		bigint	unsigned not null," . //identifier of the related sco_view
		"interaction_id	varchar(255) not null default ''," . //sco-specific, given by the sco
		"interaction_type	varchar(255) not null default ''," . //literal values, SCORM-specific (see p.63 of SCORM 1.2 RTE)
		"weighting			double not null default 0," .
		"completion_time	varchar(16) not null default ''," . //completion time for the interaction (timestamp in a day's time) - expected output format is scorm time
		"correct_responses	text not null default ''," . //actually a serialised array. See p.65 os SCORM 1.2 RTE)
		"student_response	text not null default ''," . //student response (format depends on type)
		"result			varchar(255) not null default ''," . //textual result
		"latency		varchar(16)	not null default ''" . //time necessary for completion of the interaction
		")";
	if(!Database::query($sql, __FILE__, __LINE__))
	{
		error_log($sql,0);
	}
	$sql = "ALTER TABLE `$TABLELPIVINTERACTION` ADD INDEX (lp_iv_id) ";
	if(!Database::query($sql, __FILE__, __LINE__))
	{
		error_log($sql,0);
	}

	$sql = "CREATE TABLE IF NOT EXISTS `$TABLELPIVOBJECTIVE`(" .
		"id				bigint	unsigned 		primary key auto_increment," .
		"lp_iv_id		bigint	unsigned not null," . //identifier of the related sco_view
		"order_id		smallint	unsigned	not null default 0,". //internal order (0->...) given by Dokeos
		"objective_id	varchar(255) not null default ''," . //sco-specific, given by the sco
		"score_raw		float unsigned not null default 0," . //score
		"score_max		float unsigned not null default 0," . //max score
		"score_min		float unsigned not null default 0," . //min score
		"status			char(32) not null default 'not attempted'" . //status, just as sco status
		")";
	if(!Database::query($sql, __FILE__, __LINE__))
	{
		error_log($sql,0);
	}
	$sql = "ALTER TABLE `$TABLELPIVOBJECTIVE` ADD INDEX (lp_iv_id) ";
	if(!Database::query($sql, __FILE__, __LINE__))
	{
		error_log($sql,0);
	}

	/*
	-----------------------------------------------------------
		Smart Blogs
	-----------------------------------------------------------
	*/
	$sql = "
		CREATE TABLE `" . $tbl_blogs . "` (
			blog_id smallint NOT NULL AUTO_INCREMENT ,
			blog_name varchar( 250 ) NOT NULL default '',
			blog_subtitle varchar( 250 ) default NULL ,
			date_creation datetime NOT NULL default '0000-00-00 00:00:00',
			visibility tinyint unsigned NOT NULL default 0,
			session_id smallint default 0,
			PRIMARY KEY ( blog_id )
		) ENGINE = MYISAM DEFAULT CHARSET = latin1 COMMENT = 'Table with blogs in this course';";

	if(!Database::query($sql, __FILE__, __LINE__))
	{
		error_log($sql, 0);
	}
	$sql = "ALTER TABLE `".$tbl_blogs . "` ADD INDEX ( session_id ) ";
	Database::query($sql, __FILE__, __LINE__);

	$sql = "
		CREATE TABLE `" . $tbl_blogs_comments . "` (
			comment_id int NOT NULL AUTO_INCREMENT ,
			title varchar( 250 ) NOT NULL default '',
			comment longtext NOT NULL ,
			author_id int NOT NULL default 0,
			date_creation datetime NOT NULL default '0000-00-00 00:00:00',
			blog_id mediumint NOT NULL default 0,
			post_id int NOT NULL default 0,
			task_id int default NULL ,
			parent_comment_id int NOT NULL default 0,
			PRIMARY KEY ( comment_id )
		) ENGINE = MYISAM DEFAULT CHARSET = latin1 COMMENT = 'Table with comments on posts in a blog';";

	if(!Database::query($sql, __FILE__, __LINE__))
	{
		error_log($sql, 0);
	}

	$sql = "
		CREATE TABLE `" . $tbl_blogs_posts . "` (
			post_id int NOT NULL AUTO_INCREMENT ,
			title varchar( 250 ) NOT NULL default '',
			full_text longtext NOT NULL ,
			date_creation datetime NOT NULL default '0000-00-00 00:00:00',
			blog_id mediumint NOT NULL default 0,
			author_id int NOT NULL default 0,
			PRIMARY KEY ( post_id )
		) ENGINE = MYISAM DEFAULT CHARSET = latin1 COMMENT = 'Table with posts / blog.';";

	if(!Database::query($sql, __FILE__, __LINE__))
	{
		error_log($sql, 0);
	}

	$sql = "
		CREATE TABLE `" . $tbl_blogs_rating . "` (
			rating_id int NOT NULL AUTO_INCREMENT ,
			blog_id int NOT NULL default 0,
			rating_type enum( 'post', 'comment' ) NOT NULL default 'post',
			item_id int NOT NULL default 0,
			user_id int NOT NULL default 0,
			rating mediumint NOT NULL default 0,
			PRIMARY KEY ( rating_id )
		) ENGINE = MYISAM DEFAULT CHARSET = latin1 COMMENT = 'Table with ratings for post/comments in a certain blog';";

	if(!Database::query($sql, __FILE__, __LINE__))
	{
		error_log($sql, 0);
	}

	$sql = "
		CREATE TABLE `" . $tbl_blogs_rel_user . "` (
			blog_id int NOT NULL default 0,
			user_id int NOT NULL default 0,
			PRIMARY KEY ( blog_id , user_id )
		) ENGINE = MYISAM DEFAULT CHARSET = latin1 COMMENT = 'Table representing users subscribed to a blog';";

	if(!Database::query($sql, __FILE__, __LINE__))
	{
		error_log($sql, 0);
	}

	$sql = "
		CREATE TABLE `" . $tbl_blogs_tasks . "` (
			task_id mediumint NOT NULL AUTO_INCREMENT ,
			blog_id mediumint NOT NULL default 0,
			title varchar( 250 ) NOT NULL default '',
			description text NOT NULL ,
			color varchar( 10 ) NOT NULL default '',
			system_task tinyint unsigned NOT NULL default 0,
			PRIMARY KEY ( task_id )
		) ENGINE = MYISAM DEFAULT CHARSET = latin1 COMMENT = 'Table with tasks for a blog';";

	if(!Database::query($sql, __FILE__, __LINE__))
	{
		error_log($sql, 0);
	}

	$sql = "
		CREATE TABLE `" . $tbl_blogs_tasks_rel_user . "` (
			blog_id mediumint NOT NULL default 0,
			user_id int NOT NULL default 0,
			task_id mediumint NOT NULL default 0,
			target_date date NOT NULL default '0000-00-00',
			PRIMARY KEY ( blog_id , user_id , task_id )
		) ENGINE = MYISAM DEFAULT CHARSET = latin1 COMMENT = 'Table with tasks assigned to a user in a blog';";

	if(!Database::query($sql, __FILE__, __LINE__))
	{
		error_log($sql, 0);
	}

	$sql ="CREATE TABLE  `" .$tbl_blogs_attachment."` (
		  id int unsigned NOT NULL auto_increment,
		  path varchar(255) NOT NULL COMMENT 'the real filename',
		  comment text,
		  size int NOT NULL default '0',
		  post_id int NOT NULL,
		  filename varchar(255) NOT NULL COMMENT 'the user s file name',
		  blog_id int NOT NULL,
		  comment_id int NOT NULL default '0',
  		PRIMARY KEY  (id)
		)";

	if(!Database::query($sql, __FILE__, __LINE__))
	{
		error_log($sql, 0);
	}




	$sql = "
		CREATE TABLE `" . $tbl_permission_group . "` (
			id int NOT NULL AUTO_INCREMENT ,
			group_id int NOT NULL default 0,
			tool varchar( 250 ) NOT NULL default '',
			action varchar( 250 ) NOT NULL default '',
			PRIMARY KEY (id)
		) ENGINE = MYISAM DEFAULT CHARSET = latin1;";

	if(!Database::query($sql, __FILE__, __LINE__))
	{
		error_log($sql, 0);
	}

	$sql = "
		CREATE TABLE `" . $tbl_permission_user . "` (
			id int NOT NULL AUTO_INCREMENT ,
			user_id int NOT NULL default 0,
			tool varchar( 250 ) NOT NULL default '',
			action varchar( 250 ) NOT NULL default '',
			PRIMARY KEY ( id )
		) ENGINE = MYISAM DEFAULT CHARSET = latin1;";

	if(!Database::query($sql, __FILE__, __LINE__))
	{
		error_log($sql, 0);
	}

	$sql = "
		CREATE TABLE `" . $tbl_permission_task . "` (
			id int NOT NULL AUTO_INCREMENT ,
			task_id int NOT NULL default 0,
			tool varchar( 250 ) NOT NULL default '',
			action varchar( 250 ) NOT NULL default '',
			PRIMARY KEY ( id )
		) ENGINE = MYISAM DEFAULT CHARSET = latin1;";

	if(!Database::query($sql, __FILE__, __LINE__))
	{
		error_log($sql, 0);
	}

	$sql = "
		CREATE TABLE `" . $tbl_role . "` (
			role_id int NOT NULL AUTO_INCREMENT ,
			role_name varchar( 250 ) NOT NULL default '',
			role_comment text,
			default_role tinyint default 0,
			PRIMARY KEY ( role_id )
		) ENGINE = MYISAM DEFAULT CHARSET = latin1;";

	if(!Database::query($sql, __FILE__, __LINE__))
	{
		error_log($sql, 0);
	}

	$sql = "
		CREATE TABLE `" . $tbl_role_group . "` (
			role_id int NOT NULL default 0,
			scope varchar( 20 ) NOT NULL default 'course',
			group_id int NOT NULL default 0
		) ENGINE = MYISAM DEFAULT CHARSET = latin1;";

	if(!Database::query($sql, __FILE__, __LINE__))
	{
		error_log($sql, 0);
	}

	$sql = "
		CREATE TABLE `" . $tbl_role_permissions . "` (
			role_id int NOT NULL default 0,
			tool varchar( 250 ) NOT NULL default '',
			action varchar( 50 ) NOT NULL default '',
			default_perm tinyint NOT NULL default 0
		) ENGINE = MYISAM DEFAULT CHARSET = latin1;";

	if(!Database::query($sql, __FILE__, __LINE__))
	{
		error_log($sql, 0);
	}

	$sql = "
		CREATE TABLE `" . $tbl_role_user . "` (
			role_id int NOT NULL default 0,
			scope varchar( 20 ) NOT NULL default 'course',
			user_id int NOT NULL default 0
		) ENGINE = MYISAM DEFAULT CHARSET = latin1;";

	if(!Database::query($sql, __FILE__, __LINE__))
	{
		error_log($sql, 0);
	}
	//end of Smartblogs

	/*
	-----------------------------------------------------------
		Course Config Settings
	-----------------------------------------------------------
	*/
	Database::query("
		CREATE TABLE `".$TABLESETTING . "` (
		id 			int unsigned NOT NULL auto_increment,
		variable 	varchar(255) NOT NULL default '',
		subkey		varchar(255) default NULL,
		type 		varchar(255) default NULL,
		category	varchar(255) default NULL,
		subcategory varchar(255) default NULL,
		value		varchar(255) NOT NULL default '',
		title 		varchar(255) NOT NULL default '',
		comment 	varchar(255) default NULL,
		subkeytext 	varchar(255) default NULL,
		PRIMARY KEY (id)
 		)", __FILE__, __LINE__);

	/*
	-----------------------------------------------------------
		Survey
	-----------------------------------------------------------
	*/
	$sql = "CREATE TABLE `".$TABLESURVEY."` (
			  survey_id int unsigned NOT NULL auto_increment,
			  code varchar(20) default NULL,
			  title text default NULL,
			  subtitle text default NULL,
			  author varchar(20) default NULL,
			  lang varchar(20) default NULL,
			  avail_from date default NULL,
			  avail_till date default NULL,
			  is_shared char(1) default '1',
			  template varchar(20) default NULL,
			  intro text,
			  surveythanks text,
			  creation_date datetime NOT NULL default '0000-00-00 00:00:00',
			  invited int NOT NULL,
			  answered int NOT NULL,
			  invite_mail text NOT NULL,
			  reminder_mail text NOT NULL,
			  mail_subject VARCHAR( 255 ) NOT NULL,
			  anonymous enum('0','1') NOT NULL default '0',
			  access_condition TEXT DEFAULT NULL,
			  shuffle bool NOT NULL default '0',
			  one_question_per_page bool NOT NULL default '0',
			  survey_version varchar(255) NOT NULL default '',
			  parent_id int unsigned NOT NULL,
			  survey_type int NOT NULL default 0,
			  show_form_profile int NOT NULL default 0,
			  form_fields TEXT NOT NULL,
			  session_id SMALLINT unsigned NOT NULL default 0,
                          PRIMARY KEY  (survey_id)
			)";

	$result = Database::query($sql,__FILE__,__LINE__) or die(mysql_error($sql));
	$sql = "ALTER TABLE `".$TABLESURVEY."` ADD INDEX ( session_id )";
	Database::query($sql,__FILE__,__LINE__);

	$sql = "CREATE TABLE `".$TABLESURVEYINVITATION."` (
			  survey_invitation_id int unsigned NOT NULL auto_increment,
			  survey_code varchar(20) NOT NULL,
			  user varchar(250) NOT NULL,
			  invitation_code varchar(250) NOT NULL,
			  invitation_date datetime NOT NULL,
			  reminder_date datetime NOT NULL,
			  answered int NOT NULL default 0,
			  session_id SMALLINT(5) UNSIGNED NOT NULL default 0,
			  PRIMARY KEY  (survey_invitation_id)
			)";
	$result = Database::query($sql, __FILE__, __LINE__) or die(mysql_error($sql));

	$sql = "CREATE TABLE `".$TABLESURVEYQUESTION."` (
			  question_id int unsigned NOT NULL auto_increment,
			  survey_id int unsigned NOT NULL,
			  survey_question text NOT NULL,
			  survey_question_comment text NOT NULL,
			  type varchar(250) NOT NULL,
			  display varchar(10) NOT NULL,
			  sort int NOT NULL,
			  shared_question_id int,
			  max_value int,
			  survey_group_pri int unsigned NOT NULL default '0',
			  survey_group_sec1 int unsigned NOT NULL default '0',
			  survey_group_sec2 int unsigned NOT NULL default '0',
			  PRIMARY KEY  (question_id)
			)";
	$result = Database::query($sql, __FILE__, __LINE__) or die(mysql_error($sql));

	$sql ="CREATE TABLE `".$TABLESURVEYQUESTIONOPTION."` (
	  question_option_id int unsigned NOT NULL auto_increment,
	  question_id int unsigned NOT NULL,
	  survey_id int unsigned NOT NULL,
	  option_text text NOT NULL,
	  sort int NOT NULL,
	  value int NOT NULL default '0',
	  PRIMARY KEY  (question_option_id)
	)";
	$result = Database::query($sql, __FILE__, __LINE__) or die(mysql_error($sql));

	$sql = "CREATE TABLE `".$TABLESURVEYANSWER."` (
			  answer_id int unsigned NOT NULL auto_increment,
			  survey_id int unsigned NOT NULL,
			  question_id int unsigned NOT NULL,
			  option_id TEXT NOT NULL,
			  value int unsigned NOT NULL,
			  user varchar(250) NOT NULL,
			  PRIMARY KEY  (answer_id)
			)";
	$result = Database::query($sql, __FILE__, __LINE__) or die(mysql_error($sql));

	$sql = "CREATE TABLE `".$TABLESURVEYGROUP."` (
			  id int unsigned NOT NULL auto_increment,
			  name varchar(20) NOT NULL,
			  description varchar(255) NOT NULL,
			  survey_id int unsigned NOT NULL,
			  PRIMARY KEY  (id)
			)";

	$result = Database::query($sql, __FILE__, __LINE__) or die(mysql_error($sql));

	// table glosary
	$sql = "CREATE TABLE `".$TBL_GLOSSARY."` (
			  glossary_id int unsigned NOT NULL auto_increment,
			  name varchar(255) NOT NULL,
			  description text not null,
			  display_order int,
			  session_id smallint default 0,
			  PRIMARY KEY  (glossary_id)
			)";
	$result = Database::query($sql, __FILE__, __LINE__) or die(mysql_error($sql));
	$sql = "ALTER TABLE `".$TBL_GLOSSARY . "` ADD INDEX ( session_id ) ";
	Database::query($sql, __FILE__, __LINE__);

	// table notebook
	$sql = "CREATE TABLE `".$TBL_NOTEBOOK."` (
			  notebook_id int unsigned NOT NULL auto_increment,
			  user_id int unsigned NOT NULL,
			  course varchar(40) not null,
			  session_id int NOT NULL default 0,
			  title varchar(255) NOT NULL,
			  description text NOT NULL,
			  creation_date datetime NOT NULL default '0000-00-00 00:00:00',
			  update_date datetime NOT NULL default '0000-00-00 00:00:00',
			  status int,
			  PRIMARY KEY  (notebook_id)
			)";
	$result = Database::query($sql, __FILE__, __LINE__) or die(mysql_error($sql));

	return 0;
}

function browse_folders($path, $files, $media)
{
	if($media=='images')
	{       
		$code_path = api_get_path(SYS_CODE_PATH)."default_course_document/images/";
	}
	if($media=='audio')
	{
		$code_path = api_get_path(SYS_CODE_PATH)."default_course_document/audio/";
	}
/*	if($media=='flash')
	{
		$code_path = api_get_path(SYS_CODE_PATH)."default_course_document/flash/";
	}*/
	if($media=='video')
	{
		$code_path = api_get_path(SYS_CODE_PATH)."default_course_document/video/";
	}
	if($media=='animations')
	{
		$code_path = api_get_path(SYS_CODE_PATH)."default_course_document/animations/";
	}
	if($media=='mascot')
	{
		$code_path = api_get_path(SYS_CODE_PATH)."default_course_document/mascot/";
	}
	if($media=='mindmaps')
	{
		$code_path = api_get_path(SYS_CODE_PATH)."default_course_document/mindmaps/";
	}
	if($media=='photos')
	{
		$code_path = api_get_path(SYS_CODE_PATH)."default_course_document/photos/";
	}
	if($media=='podcasts')
	{
		$code_path = api_get_path(SYS_CODE_PATH)."default_course_document/podcasts/";
	}
	if($media=='screencasts')
	{
		$code_path = api_get_path(SYS_CODE_PATH)."default_course_document/screencasts/";
	}
	if($media=='themes')
	{
		$code_path = api_get_path(SYS_CODE_PATH)."default_course_document/themes/";
	}
	if(is_dir($path))
	{
		$handle = opendir($path);
		while (false !== ($file = readdir($handle)))
		{
			if(is_dir($path.$file) && strpos($file,'.')!==0)
			{
				$files[]["dir"] = str_replace($code_path,"",$path.$file."/");
				$files = browse_folders($path.$file."/",$files,$media);
			}
			elseif(is_file($path.$file) && strpos($file,'.')!==0)
			{
		             $files[]["file"] = str_replace($code_path,"",$path.$file);  
			}
		}
	}
	return $files;
}

function sort_pictures($files,$type)
{
	$pictures=array();
	foreach($files as $key => $value){
		if($value[$type]!=""){
			$pictures[][$type]=$value[$type];
		}
	}
	return $pictures;
}

/**
*	Fills the course repository with some
*	example content.
*	@version	 1.2
*/
function fill_course_repository($courseRepository)
{
	$old_umask = umask(0);
	$sys_course_path = api_get_path(SYS_COURSE_PATH);
	$web_code_path = api_get_path(WEB_CODE_PATH);

	$default_document_array=array();

	if(api_get_setting('example_material_course_creation')<>'false')
	{
		$img_code_path = api_get_path(SYS_CODE_PATH)."default_course_document/images/";
		$audio_code_path = api_get_path(SYS_CODE_PATH)."default_course_document/audio/";
		$video_code_path = api_get_path(SYS_CODE_PATH)."default_course_document/video/";
		$animations_code_path = api_get_path(SYS_CODE_PATH)."default_course_document/animations/";
		$mascot_code_path = api_get_path(SYS_CODE_PATH)."default_course_document/mascot/";
		$mindmaps_code_path = api_get_path(SYS_CODE_PATH)."default_course_document/mindmaps/";
		$photos_code_path = api_get_path(SYS_CODE_PATH)."default_course_document/photos/";
		$podcasts_code_path = api_get_path(SYS_CODE_PATH)."default_course_document/podcasts/";
		$screencasts_code_path = api_get_path(SYS_CODE_PATH)."default_course_document/screencasts/";
		$themes_code_path = api_get_path(SYS_CODE_PATH)."default_course_document/themes/";
		$course_documents_folder_images=$sys_course_path.$courseRepository.'/document/images/';
		$course_documents_folder_audio=$sys_course_path.$courseRepository.'/document/audio/';
		$course_documents_folder_video=$sys_course_path.$courseRepository.'/document/video/';
		$course_documents_folder_animations=$sys_course_path.$courseRepository.'/document/animations/';
		$course_documents_folder_mascot=$sys_course_path.$courseRepository.'/document/mascot/';
		$course_documents_folder_mindmaps=$sys_course_path.$courseRepository.'/document/mindmaps/';
		$course_documents_folder_photos=$sys_course_path.$courseRepository.'/document/photos/';
		$course_documents_folder_podcasts=$sys_course_path.$courseRepository.'/document/podcasts/';
		$course_documents_folder_screencasts=$sys_course_path.$courseRepository.'/document/screencasts/';
		$course_documents_folder_themes=$sys_course_path.$courseRepository.'/document/themes/';

		/*
		 * Images
		 */            
	   	$files=array();
		$files=browse_folders($img_code_path,$files,'images');
		$pictures_array = sort_pictures($files,"dir");
		$pictures_array = array_merge($pictures_array,sort_pictures($files,"file"));
		$perm = api_get_setting('permissions_for_new_directories');
		$perm = octdec(!empty($perm)?$perm:'0770');
		$perm_file = api_get_setting('permissions_for_new_files');
		$perm_file = octdec(!empty($perm_file)?$perm_file:'0660');
                //Hotspot templates should not included as images in the document table
                $hotspot_images = array();
                $hotspot_images[] = 'quiz-27.jpg';
                $hotspot_images[] = 'quiz-28.jpg';
                $hotspot_images[] = 'quiz-29.jpg';
                $hotspot_images[] = 'quiz-30.jpg';
		if(!is_dir($course_documents_folder_images))
		{
			mkdir($course_documents_folder_images,$perm);
		}

		$handle = opendir($img_code_path);

		foreach($pictures_array as $key => $value)
		{
			if($value["dir"]!="")
			{
				mkdir($course_documents_folder_images.$value["dir"],$perm);
			}
			if($value["file"]!="")
			{
				copy($img_code_path.$value["file"],$course_documents_folder_images.$value["file"]);
				chmod($course_documents_folder_images.$value["file"],$perm_file);
                                if (in_array($value["file"],$hotspot_images)) {
                                    unset($pictures_array[$key]);
                                }
                                
			}
		}

		$default_document_array['images']=$pictures_array;

		/*
		 * Themes
		 */
	   	$files=array();

		$files=browse_folders($themes_code_path,$files,'themes');

		$themes_array = sort_pictures($files,"dir");
		$themes_array = array_merge($themes_array,sort_pictures($files,"file"));

		$perm = api_get_setting('permissions_for_new_directories');
		$perm = octdec(!empty($perm)?$perm:'0770');
		$perm_file = api_get_setting('permissions_for_new_files');
		$perm_file = octdec(!empty($perm_file)?$perm_file:'0660');
		if(!is_dir($course_documents_folder_themes))
		{
			mkdir($course_documents_folder_themes,$perm);
		}

		$handle = opendir($themes_code_path);
	
		foreach($themes_array as $key => $value)
		{
			if($value["dir"]!="")
			{
				mkdir($course_documents_folder_themes.$value["dir"],$perm);
			}
			if($value["file"]!="")
			{
				copy($themes_code_path.$value["file"],$course_documents_folder_themes.$value["file"]);
				chmod($course_documents_folder_themes.$value["file"],$perm_file);
			}
		}
		
		//images thumbnails fix
		
		$path_thumb=mkdir($course_documents_folder_themes.'images',$perm);				
		$handle = opendir($themes_code_path.'images/');		
		
		while (false !== ($file = readdir($handle))) 
		{
			if (is_file($themes_code_path.'images/'.$file))
			{
		        copy($themes_code_path.'images/'.$file,$course_documents_folder_themes.'images/'.$file);
				chmod($course_documents_folder_themes.'images/'.$file,$perm_file);
			}		
		}	
		
		//img thumbnails fix
		
		$path_thumb=mkdir($course_documents_folder_themes.'img',$perm);				
		$handle = opendir($themes_code_path.'img/');		
		
		while (false !== ($file = readdir($handle))) 
		{
			if (is_file($themes_code_path.'img/'.$file))
			{
		        copy($themes_code_path.'img/'.$file,$course_documents_folder_themes.'img/'.$file);
				chmod($course_documents_folder_themes.'img/'.$file,$perm_file);
			}		
		}	

		$default_document_array['themes']=$themes_array;

		/*
		 * Audio
		 */
		$files=array();

		$files=browse_folders($audio_code_path,$files,'audio');

		$audio_array = sort_pictures($files,"dir");
		$audio_array = array_merge($audio_array,sort_pictures($files,"file"));

		if(!is_dir($course_documents_folder_audio))
		{
			mkdir($course_documents_folder_audio,$perm);
		}

		$handle = opendir($audio_code_path);

		foreach($audio_array as $key => $value){

			if($value["dir"]!=""){
				mkdir($course_documents_folder_audio.$value["dir"],$perm);
			}
			if($value["file"]!=""){
				copy($audio_code_path.$value["file"],$course_documents_folder_audio.$value["file"]);
				chmod($course_documents_folder_audio.$value["file"],$perm_file);
			}

		}
		$default_document_array['audio']=$audio_array;

		/*
		 * Animations
		 */
		$files=array();

		$files=browse_folders($animations_code_path,$files,'animations');

		$animations_array = sort_pictures($files,"dir");
		$animations_array = array_merge($animations_array,sort_pictures($files,"file"));

		if(!is_dir($course_documents_folder_animations))
		{
			mkdir($course_documents_folder_animations,$perm);
		}

		$handle = opendir($animations_code_path);

		foreach($animations_array as $key => $value){

			if($value["dir"]!=""){
				mkdir($course_documents_folder_animations.$value["dir"],$perm);
			}
			if($value["file"]!=""){
				copy($animations_code_path.$value["file"],$course_documents_folder_animations.$value["file"]);
				chmod($course_documents_folder_animations.$value["file"],$perm_file);
			}

		}
		$default_document_array['animations']=$animations_array;

		/*
		 * Mascot
		 */
		$files=array();

		$files=browse_folders($mascot_code_path,$files,'mascot');

		$mascot_array = sort_pictures($files,"dir");
		$mascot_array = array_merge($mascot_array,sort_pictures($files,"file"));

		if(!is_dir($course_documents_folder_mascot))
		{
			mkdir($course_documents_folder_mascot,$perm);
		}

		$handle = opendir($mascot_code_path);

		foreach($mascot_array as $key => $value){

			if($value["dir"]!=""){
				mkdir($course_documents_folder_mascot.$value["dir"],$perm);
			}
			if($value["file"]!=""){
				copy($mascot_code_path.$value["file"],$course_documents_folder_mascot.$value["file"]);
				chmod($course_documents_folder_mascot.$value["file"],$perm_file);
			}

		}
		$default_document_array['mascot']=$mascot_array;

		/*
		 * photos
		 */
		$files=array();

		$files=browse_folders($photos_code_path,$files,'photos');

		$photos_array = sort_pictures($files,"dir");
		$photos_array = array_merge($photos_array,sort_pictures($files,"file"));

		if(!is_dir($course_documents_folder_photos))
		{
			mkdir($course_documents_folder_photos,$perm);
		}

		$handle = opendir($photos_code_path);

		foreach($photos_array as $key => $value){

			if($value["dir"]!=""){
				mkdir($course_documents_folder_photos.$value["dir"],$perm);
			}
			if($value["file"]!=""){
				copy($photos_code_path.$value["file"],$course_documents_folder_photos.$value["file"]);
				chmod($course_documents_folder_photos.$value["file"],$perm_file);
			}

		}
		$default_document_array['photos']=$photos_array;

		/*
		 * podcasts
		 */
		$files=array();

		$files=browse_folders($podcasts_code_path,$files,'podcasts');

		$podcasts_array = sort_pictures($files,"dir");
		$podcasts_array = array_merge($podcasts_array,sort_pictures($files,"file"));

		if(!is_dir($course_documents_folder_podcasts))
		{
			mkdir($course_documents_folder_podcasts,$perm);
		}

		$handle = opendir($podcasts_code_path);

		foreach($podcasts_array as $key => $value){

			if($value["dir"]!=""){
				mkdir($course_documents_folder_podcasts.$value["dir"],$perm);
			}
			if($value["file"]!=""){
				copy($podcasts_code_path.$value["file"],$course_documents_folder_podcasts.$value["file"]);
				chmod($course_documents_folder_podcasts.$value["file"],$perm_file);
			}

		}
		$default_document_array['podcasts']=$podcasts_array;

		/*
		 * Screencasts
		 */
		$files=array();

		$files=browse_folders($screencasts_code_path,$files,'screencasts');

		$screencasts_array = sort_pictures($files,"dir");
		$screencasts_array = array_merge($screencasts_array,sort_pictures($files,"file"));

		if(!is_dir($course_documents_folder_screencasts))
		{
			mkdir($course_documents_folder_screencasts,$perm);
		}

		$handle = opendir($screencasts_code_path);

		foreach($screencasts_array as $key => $value){

			if($value["dir"]!=""){
				mkdir($course_documents_folder_screencasts.$value["dir"],$perm);
			}
			if($value["file"]!=""){
				copy($screencasts_code_path.$value["file"],$course_documents_folder_screencasts.$value["file"]);
				chmod($course_documents_folder_screencasts.$value["file"],$perm_file);
			}

		}
		$default_document_array['screencasts']=$screencasts_array;		

		/*
		 * Mindmaps
		 */
		$files=array();

		$files=browse_folders($mindmaps_code_path,$files,'mindmaps');

		$mindmaps_array = sort_pictures($files,"dir");
		$mindmaps_array = array_merge($mindmaps_array,sort_pictures($files,"file"));

		if(!is_dir($course_documents_folder_mindmaps))
		{
			mkdir($course_documents_folder_mindmaps,$perm);
		}

		$handle = opendir($mindmaps_code_path);

		foreach($mindmaps_array as $key => $value){

			if($value["dir"]!=""){
				@mkdir($course_documents_folder_mindmaps.$value["dir"],$perm);
			}
			if($value["file"]!=""){
				copy($mindmaps_code_path.$value["file"],$course_documents_folder_mindmaps.$value["file"]);
				chmod($course_documents_folder_mindmaps.$value["file"],$perm_file);
			}

		}
		$default_document_array['mindmaps']=$mindmaps_array;

		/*
		 * Video
		 */
		$files=array();

		$files=browse_folders($video_code_path,$files,'video');

		$video_array = sort_pictures($files,"dir");
		$video_array = array_merge($video_array,sort_pictures($files,"file"));

		if(!is_dir($course_documents_folder_video))
		{
			mkdir($course_documents_folder_video,$perm);
		}

		$handle = opendir($video_code_path);

		foreach($video_array as $key => $value){

			if($value["dir"]!=""){
				@mkdir($course_documents_folder_video.$value["dir"],$perm);
			}
			if($value["file"]!=""){
				copy($video_code_path.$value["file"],$course_documents_folder_video.$value["file"]);
				chmod($course_documents_folder_video.$value["file"],$perm_file);
			}

		}
		$default_document_array['video']=$video_array;

	}
	umask($old_umask);
	return $default_document_array;
}

/**
 * Function to convert a string from the Dokeos language files to a string ready
 * to insert into the database.
 * @author Bart Mollet (bart.mollet@hogent.be)
 * @param string $string The string to convert
 * @return string The string converted to insert into the database
 */
function lang2db($string)
{
	$string = str_replace("\\'", "'", $string);
	$string = Database::escape_string($string);
	return $string;
}
/**
*	Fills the course database with some required content and example content.
*	@version 1.2
*/
function fill_Db_course($courseDbName, $courseRepository, $language,$default_document_array)
{
	global $_configuration, $clarolineRepositoryWeb, $_user;

	$courseDbName 		= $_configuration['table_prefix'].$courseDbName.$_configuration['db_glue'];

	$tbl_course_homepage 	= $courseDbName . "tool";
	$TABLEINTROS 		= $courseDbName . "tool_intro";

	$TABLEGROUPS 		= $courseDbName . "group_info";
	$TABLEGROUPCATEGORIES 	= $courseDbName . "group_category";
	$TABLEGROUPUSER 	= $courseDbName . "group_rel_user";

	$TABLEITEMPROPERTY 	= $courseDbName . "item_property";

	$TABLETOOLCOURSEDESC 	= $courseDbName . "course_description";
	$TABLETOOLAGENDA 	= $courseDbName . "calendar_event";
	$TABLETOOLANNOUNCEMENTS = $courseDbName . "announcement";
	$TABLEADDEDRESOURCES 	= $courseDbName . "resource";
	$TABLETOOLWORKS 	= $courseDbName . "student_publication";
	$TABLETOOLWORKSUSER 	= $courseDbName . "stud_pub_rel_user";
	$TABLETOOLDOCUMENT 	= $courseDbName . "document";
	$TABLETOOLWIKI 		= $courseDbName . "wiki";

	$TABLETOOLLINK 		= $courseDbName . "link";

	$TABLEQUIZ 		= $courseDbName . "quiz";
	$TABLEQUIZQUESTION 	= $courseDbName . "quiz_rel_question";
	$TABLEQUIZQUESTIONLIST 	= $courseDbName . "quiz_question";
	$TABLEQUIZANSWERSLIST 	= $courseDbName . "quiz_answer";
	$TABLEQUIZSCENARIO 	= $courseDbName . "quiz_scenario";
	$TABLESETTING 		= $courseDbName . "course_setting";

	$TABLEFORUMCATEGORIES 	= $courseDbName . "forum_category";
	$TABLEFORUMS 		= $courseDbName . "forum_forum";
	$TABLEFORUMTHREADS 	= $courseDbName . "forum_thread";
	$TABLEFORUMPOSTS 	= $courseDbName . "forum_post";


	$nom = $_user['lastName'];
	$prenom = $_user['firstName'];

	include (api_get_path(SYS_CODE_PATH) . "lang/english/create_course.inc.php");
	$file_to_include = "lang/".$language . "/create_course.inc.php";
	if (file_exists($file_to_include))
		include (api_get_path(SYS_CODE_PATH) . $file_to_include);

	mysql_select_db("$courseDbName");

	/*
	==============================================================================
			All course tables are created.
			Next sections of the script:
			- insert links to all course tools so they can be accessed on the course homepage
			- fill the tool tables with examples
	==============================================================================
	*/

	$visible4all = 1;
	$visible4AdminOfCourse = 0;
	$visible4AdminOfClaroline = 2;

	/*
	-----------------------------------------------------------
		Course homepage tools
	-----------------------------------------------------------
	*/
	Database::query("INSERT INTO `" . $tbl_course_homepage . "` VALUES (NULL, '" . TOOL_OOGIE . "','upload/upload_ppt.php','oogie.png','1','0','squaregrey.gif','NO','_self','authoring','0')", __FILE__, __LINE__);
	Database::query("INSERT INTO `" . $tbl_course_homepage . "` VALUES (NULL, '" . TOOL_DOCUMENT . "','document/document.php','folder_document.png','".string2binary(api_get_setting('course_create_active_tools', 'documents')) . "','0','squaregrey.gif','NO','_self','authoring','0')", __FILE__, __LINE__);
	Database::query("INSERT INTO `" . $tbl_course_homepage . "` VALUES (NULL, '" . TOOL_COURSE_DESCRIPTION . "','course_description/','info.png','".string2binary(api_get_setting('course_create_active_tools', 'course_description')) . "','0','squaregrey.gif','NO','_self','authoring','0')", __FILE__, __LINE__);
	Database::query("INSERT INTO `" . $tbl_course_homepage . "` VALUES (NULL, '" . TOOL_AUTHOR . "','newscorm/lp_controller.php','author.png','1','0','squaregrey.gif','NO','_self','authoring','0')", __FILE__, __LINE__);
	Database::query("INSERT INTO `" . $tbl_course_homepage . "` VALUES (NULL, '" . TOOL_LEARNPATH . "','newscorm/lp_controller.php?action=course','scorm.png','".string2binary(api_get_setting('course_create_active_tools', 'learning_path')) . "','0','squaregrey.gif','NO','_self','authoring','0')", __FILE__, __LINE__);
	Database::query("INSERT INTO `" . $tbl_course_homepage . "` VALUES (NULL, '" . TOOL_LINK . "','link/link.php','links.png','".string2binary(api_get_setting('course_create_active_tools', 'links')) . "','0','squaregrey.gif','NO','_self','authoring','0')", __FILE__, __LINE__);
	Database::query("INSERT INTO `" . $tbl_course_homepage . "` VALUES (NULL, '" . TOOL_QUIZ . "','exercice/exercice.php','quiz.png','".string2binary(api_get_setting('course_create_active_tools', 'quiz')) . "','0','squaregrey.gif','NO','_self','authoring','0')", __FILE__, __LINE__);
	Database::query("INSERT INTO `" . $tbl_course_homepage . "` VALUES (NULL, '" . TOOL_MEDIABOX . "','document/mediabox.php','podcast.png','1','0','squaregrey.gif','NO','_self','authoring','0')", __FILE__, __LINE__);
	Database::query("INSERT INTO `" . $tbl_course_homepage . "` VALUES (NULL, '" . TOOL_GLOSSARY."','glossary/index.php','glossary.png','".string2binary(api_get_setting('course_create_active_tools', 'glossary')). "','0','squaregrey.gif','NO','_self','authoring','0')", __FILE__, __LINE__);
	Database::query("INSERT INTO `" . $tbl_course_homepage . "` VALUES (NULL, '" . TOOL_CALENDAR_EVENT . "','calendar/agenda.php','agenda.png','".string2binary(api_get_setting('course_create_active_tools', 'agenda')) . "','0','squaregrey.gif','NO','_self','interaction','0')", __FILE__, __LINE__);
	Database::query("INSERT INTO `" . $tbl_course_homepage . "` VALUES (NULL, '" . TOOL_CHAT . "','chat/chat.php','chat.png','".string2binary(api_get_setting('course_create_active_tools', 'chat')) . "','0','squaregrey.gif','NO','_self','interaction','0')", __FILE__, __LINE__);
	Database::query("INSERT INTO `" . $tbl_course_homepage . "` VALUES (NULL, '" . TOOL_NOTEBOOK."','notebook/index.php','notebook.png','".string2binary(api_get_setting('course_create_active_tools', 'notebook'))."','0','squaregrey.gif','NO','_self','interaction','0')", __FILE__, __LINE__);
	Database::query("INSERT INTO `" . $tbl_course_homepage . "` VALUES (NULL, '" . TOOL_USER . "','user/user.php','members.png','".string2binary(api_get_setting('course_create_active_tools', 'users')) . "','0','squaregrey.gif','NO','_self','interaction','0')", __FILE__, __LINE__);
	Database::query("INSERT INTO `" . $tbl_course_homepage . "` VALUES (NULL, '" . TOOL_FORUM . "','forum/index.php','forum.png','".string2binary(api_get_setting('course_create_active_tools', 'forums')) . "','0','squaregrey.gif','NO','_self','interaction','0')", __FILE__, __LINE__);
	Database::query("INSERT INTO `" . $tbl_course_homepage . "` VALUES (NULL, '" . TOOL_GROUP . "','group/group.php','group.png','".string2binary(api_get_setting('course_create_active_tools', 'groups')) . "','0','squaregrey.gif','NO','_self','interaction','0')", __FILE__, __LINE__);
	Database::query("INSERT INTO `" . $tbl_course_homepage . "` VALUES (NULL, '" . TOOL_STUDENTPUBLICATION . "','work/work.php','works.png','".string2binary(api_get_setting('course_create_active_tools', 'student_publications')) . "','0','squaregrey.gif','NO','_self','interaction','0')", __FILE__, __LINE__);
	Database::query("INSERT INTO `" . $tbl_course_homepage . "` VALUES (NULL, '" . TOOL_WIKI ."','wiki/index.php','wiki.png','".string2binary(api_get_setting('course_create_active_tools', 'wiki')) . "','0','squaregrey.gif','NO','_self','interaction','0')", __FILE__, __LINE__);	
	Database::query("INSERT INTO `" . $tbl_course_homepage . "` VALUES (NULL, '" . TOOL_ANNOUNCEMENT . "','announcements/announcements.php','valves.png','".string2binary(api_get_setting('course_create_active_tools', 'announcements')) . "','0','squaregrey.gif','NO','_self','authoring','0')", __FILE__, __LINE__);
	Database::query("INSERT INTO `" . $tbl_course_homepage . "` VALUES (NULL, '" . TOOL_SURVEY."','survey/survey_list.php','survey.png','".string2binary(api_get_setting('course_create_active_tools', 'survey')) . "','0','squaregrey.gif','NO','_self','interaction','0')", __FILE__, __LINE__);
	Database::query("INSERT INTO `" . $tbl_course_homepage . "` VALUES (NULL, '" . TOOL_MINDMAP . "','mindmap/index.php','mindmap.png','1','0','squaregrey.gif','NO','_self','interaction','0')", __FILE__, __LINE__);
        Database::query("INSERT INTO `" . $tbl_course_homepage . "` VALUES (NULL, '" .TOOL_DROPBOX."','dropbox/index.php','dropbox.gif','".string2binary(api_get_setting('course_create_active_tools', 'dropbox')) . "','0','squaregrey.gif','NO','_self','interaction','0')", __FILE__, __LINE__);
	// Smartblogs (Kevin Van Den Haute :: kevin@develop-it.be)
	$sql = "INSERT INTO `" . $tbl_course_homepage . "` VALUES (NULL,'" . TOOL_BLOGS . "','blog/blog_admin.php','blog_admin.png','" . string2binary(api_get_setting('course_create_active_tools', 'blogs')) . "','1','squaregrey.gif','NO','_self','admin','0')";
	Database::query($sql, __FILE__, __LINE__);
	// end of Smartblogs
	/*
	-----------------------------------------------------------
		Course homepage tools for course admin only
	-----------------------------------------------------------
	*/
	Database::query("INSERT INTO `" . $tbl_course_homepage . "` VALUES (NULL,'".TOOL_COPY_COURSE_CONTENT."','coursecopy/copy_course.php','copy.png','$visible4AdminOfCourse','1','','NO','_self', 'admin','0')", __FILE__, __LINE__);
	Database::query("INSERT INTO `" . $tbl_course_homepage . "` VALUES (NULL,'".TOOL_COURSE_MAINTENANCE."','course_info/maintenance.php','backup.png','$visible4AdminOfCourse','1','','NO','_self', 'admin','0')", __FILE__, __LINE__);
	Database::query("INSERT INTO `" . $tbl_course_homepage . "` VALUES (NULL, '" . TOOL_TRACKING . "','tracking/courseLog.php','report_32.png','$visible4AdminOfCourse','1','', 'NO','_self','admin','0')", __FILE__, __LINE__);
	Database::query("INSERT INTO `" . $tbl_course_homepage . "` VALUES (NULL, '" . TOOL_COURSE_SETTING . "','course_info/infocours.php','reference.png','$visible4AdminOfCourse','1','', 'NO','_self','admin','0')", __FILE__, __LINE__);	
	Database::query("INSERT INTO `" . $tbl_course_homepage . "` VALUES (NULL, '" . TOOL_GRADEBOOK."','gradebook/index.php','gradebook.png','".string2binary(api_get_setting('course_create_active_tools', 'gradebook')). "','1','','NO','_self','admin','0')", __FILE__, __LINE__);	
	
	if(api_get_setting('service_visio','active')=='true')
	{
		//$mycheck = api_get_setting('service_visio','visio_host');
		//if(!empty($mycheck))
		//{
		Database::query("INSERT INTO `" . $tbl_course_homepage . "` VALUES (NULL, '" . TOOL_VISIO_CONFERENCE . "','videoconference/virtual_meeting.php','visio_meeting.gif','1','0','squaregrey.gif','NO','_self','interaction','0')", __FILE__, __LINE__);
		Database::query("INSERT INTO `" . $tbl_course_homepage . "` VALUES (NULL, '" . TOOL_VISIO_CLASSROOM . "','videoconference/virtual_classroom.php','visio.gif','1','0','squaregrey.gif','NO','_self','authoring','0')", __FILE__, __LINE__);
		//}
	}

    if (api_get_setting('search_enabled')=='true') {
        Database::query("INSERT INTO `" . $tbl_course_homepage . "` VALUES (NULL, '" . TOOL_SEARCH. "','search/','page-zoom.png','".string2binary(api_get_setting('course_create_active_tools', 'enable_search')) . "','0','search.png','NO','_self','authoring','0')", __FILE__, __LINE__);
    }

	/*
	-----------------------------------------------------------
		course_setting table (courseinfo tool)
	-----------------------------------------------------------
	*/
	Database::query("INSERT INTO `".$TABLESETTING . "`(variable,value,category) VALUES ('email_alert_manager_on_new_doc',0,'work')", __FILE__, __LINE__);
	Database::query("INSERT INTO `".$TABLESETTING . "`(variable,value,category) VALUES ('email_alert_on_new_doc_dropbox',0,'dropbox')", __FILE__, __LINE__);
	Database::query("INSERT INTO `".$TABLESETTING . "`(variable,value,category) VALUES ('allow_user_edit_agenda',0,'agenda')", __FILE__, __LINE__);
	Database::query("INSERT INTO `".$TABLESETTING . "`(variable,value,category) VALUES ('allow_user_edit_announcement',0,'announcement')", __FILE__, __LINE__);
	Database::query("INSERT INTO `".$TABLESETTING . "`(variable,value,category) VALUES ('email_alert_manager_on_new_quiz',0,'quiz')", __FILE__, __LINE__);
	Database::query("INSERT INTO `".$TABLESETTING . "`(variable,value,category) VALUES ('allow_user_image_forum',1,'forum')", __FILE__, __LINE__);
	Database::query("INSERT INTO `".$TABLESETTING . "`(variable,value,category) VALUES ('course_theme','','theme')", __FILE__, __LINE__);
	Database::query("INSERT INTO `".$TABLESETTING . "`(variable,value,category) VALUES ('allow_learning_path_theme','1','theme')", __FILE__, __LINE__);
	Database::query("INSERT INTO `".$TABLESETTING . "`(variable,value,category) VALUES ('allow_open_chat_window',1,'chat')", __FILE__, __LINE__);
	Database::query("INSERT INTO `".$TABLESETTING . "`(variable,value,category) VALUES ('email_alert_to_teacher_on_new_user_in_course',0,'registration')", __FILE__, __LINE__);
	/*
	-----------------------------------------------------------
		Course homepage tools for platform admin only
	-----------------------------------------------------------
	*/


	/*
	-----------------------------------------------------------
		Group tool
	-----------------------------------------------------------
	*/
	Database::query("INSERT INTO `".$TABLEGROUPCATEGORIES . "` ( title,	description, icon, doc_state, calendar_state, work_state, announcements_state, forum_state, wiki_state, chat_state, group_state, max_student, self_reg_allowed, self_unreg_allowed, groups_per_user) 
											VALUES ('".lang2db(get_lang('GroupScenarioCollaboration')) . "','".lang2db(get_lang('GroupScenarioCollaborationDescription')) . "', 'collaboration.png','1', '1', '1', '1', '1', '1', '1', '1','5','1','1','0');", __FILE__, __LINE__);
	Database::query("INSERT INTO `".$TABLEGROUPCATEGORIES . "` ( title,	description, icon, doc_state, calendar_state, work_state, announcements_state, forum_state, wiki_state, chat_state, group_state, max_student, self_reg_allowed, self_unreg_allowed, groups_per_user) 
											VALUES ('".lang2db(get_lang('GroupScenarioCompetition')) . "','".lang2db(get_lang('GroupScenarioCompetitionDescription')) . "', 'competition.png', '0', '0', '0', '0', '0', '0', '0', '0','5','1','1','0');", __FILE__, __LINE__);


	/*
	-----------------------------------------------------------
		Example Material
	-----------------------------------------------------------
	*/
	global $language_interface;
	// Example material in the same language
	$language_interface_tmp=$language_interface;
	$language_interface=$language;

	if(api_get_setting('example_material_course_creation')<>'false')
	{

		/*
		-----------------------------------------------------------
			Documents
		-----------------------------------------------------------
		*/
		Database::query("INSERT INTO `".$TABLETOOLDOCUMENT . "`(path,title,filetype,size) VALUES ('/images','".get_lang('Images')."','folder','0')", __FILE__, __LINE__);
		$example_doc_id = Database :: insert_id();
		Database::query("INSERT INTO `".$TABLEITEMPROPERTY . "` (tool,insert_user_id,insert_date,lastedit_date,ref,lastedit_type,lastedit_user_id,to_group_id,to_user_id,visibility) VALUES ('document',1,NOW(),NOW(),$example_doc_id,'DocumentAdded',1,0,NULL,0)", __FILE__, __LINE__);

	        Database::query("INSERT INTO `".$TABLETOOLDOCUMENT . "`(path,title,filetype,size) VALUES ('/shared_folder','".get_lang('SharedDocumentsDirectory')."','folder','0')", __FILE__, __LINE__);
	        $example_doc_id = Database :: insert_id();
	        Database::query("INSERT INTO `".$TABLEITEMPROPERTY . "` (tool,insert_user_id,insert_date,lastedit_date,ref,lastedit_type,lastedit_user_id,to_group_id,to_user_id,visibility) VALUES ('document',1,NOW(),NOW(),$example_doc_id,'DocumentAdded',1,0,NULL,0)", __FILE__, __LINE__);

		Database::query("INSERT INTO `".$TABLETOOLDOCUMENT . "`(path,title,filetype,size) VALUES ('/audio','".get_lang('Audio')."','folder','0')", __FILE__, __LINE__);
		$example_doc_id = Database :: insert_id();
		Database::query("INSERT INTO `".$TABLEITEMPROPERTY . "` (tool,insert_user_id,insert_date,lastedit_date,ref,lastedit_type,lastedit_user_id,to_group_id,to_user_id,visibility) VALUES ('document',1,NOW(),NOW(),$example_doc_id,'DocumentAdded',1,0,NULL,0)", __FILE__, __LINE__);

		Database::query("INSERT INTO `".$TABLETOOLDOCUMENT . "`(path,title,filetype,size) VALUES ('/video','".get_lang('Video')."','folder','0')", __FILE__, __LINE__);
		$example_doc_id = Database :: insert_id();
		Database::query("INSERT INTO `".$TABLEITEMPROPERTY . "` (tool,insert_user_id,insert_date,lastedit_date,ref,lastedit_type,lastedit_user_id,to_group_id,to_user_id,visibility) VALUES ('document',1,NOW(),NOW(),$example_doc_id,'DocumentAdded',1,0,NULL,0)", __FILE__, __LINE__);

		api_sql_query("INSERT INTO `".$TABLETOOLDOCUMENT . "`(path,title,filetype,size) VALUES ('/animations','".get_lang('Animations')."','folder','0')", __FILE__, __LINE__);
		$example_doc_id = Database :: get_last_insert_id();
		api_sql_query("INSERT INTO `".$TABLEITEMPROPERTY . "` (tool,insert_user_id,insert_date,lastedit_date,ref,lastedit_type,lastedit_user_id,to_group_id,to_user_id,visibility) VALUES ('document',1,NOW(),NOW(),$example_doc_id,'DocumentAdded',1,0,NULL,0)", __FILE__, __LINE__);

		api_sql_query("INSERT INTO `".$TABLETOOLDOCUMENT . "`(path,title,filetype,size) VALUES ('/mascot','".get_lang('Mascot')."','folder','0')", __FILE__, __LINE__);
		$example_doc_id = Database :: get_last_insert_id();
		api_sql_query("INSERT INTO `".$TABLEITEMPROPERTY . "` (tool,insert_user_id,insert_date,lastedit_date,ref,lastedit_type,lastedit_user_id,to_group_id,to_user_id,visibility) VALUES ('document',1,NOW(),NOW(),$example_doc_id,'DocumentAdded',1,0,NULL,0)", __FILE__, __LINE__);		

		api_sql_query("INSERT INTO `".$TABLETOOLDOCUMENT . "`(path,title,filetype,size) VALUES ('/photos','".get_lang('Photos')."','folder','0')", __FILE__, __LINE__);
		$example_doc_id = Database :: get_last_insert_id();
		api_sql_query("INSERT INTO `".$TABLEITEMPROPERTY . "` (tool,insert_user_id,insert_date,lastedit_date,ref,lastedit_type,lastedit_user_id,to_group_id,to_user_id,visibility) VALUES ('document',1,NOW(),NOW(),$example_doc_id,'DocumentAdded',1,0,NULL,0)", __FILE__, __LINE__);

		api_sql_query("INSERT INTO `".$TABLETOOLDOCUMENT . "`(path,title,filetype,size) VALUES ('/podcasts','".get_lang('Podcasts')."','folder','0')", __FILE__, __LINE__);
		$example_doc_id = Database :: get_last_insert_id();
		api_sql_query("INSERT INTO `".$TABLEITEMPROPERTY . "` (tool,insert_user_id,insert_date,lastedit_date,ref,lastedit_type,lastedit_user_id,to_group_id,to_user_id,visibility) VALUES ('document',1,NOW(),NOW(),$example_doc_id,'DocumentAdded',1,0,NULL,0)", __FILE__, __LINE__);

		api_sql_query("INSERT INTO `".$TABLETOOLDOCUMENT . "`(path,title,filetype,size) VALUES ('/screencasts','".get_lang('Screencasts')."','folder','0')", __FILE__, __LINE__);
		$example_doc_id = Database :: get_last_insert_id();
		api_sql_query("INSERT INTO `".$TABLEITEMPROPERTY . "` (tool,insert_user_id,insert_date,lastedit_date,ref,lastedit_type,lastedit_user_id,to_group_id,to_user_id,visibility) VALUES ('document',1,NOW(),NOW(),$example_doc_id,'DocumentAdded',1,0,NULL,0)", __FILE__, __LINE__);

		api_sql_query("INSERT INTO `".$TABLETOOLDOCUMENT . "`(path,title,filetype,size) VALUES ('/themes','".get_lang('Themes')."','folder','0')", __FILE__, __LINE__);
		$example_doc_id = Database :: get_last_insert_id();
		api_sql_query("INSERT INTO `".$TABLEITEMPROPERTY . "` (tool,insert_user_id,insert_date,lastedit_date,ref,lastedit_type,lastedit_user_id,to_group_id,to_user_id,visibility) VALUES ('document',1,NOW(),NOW(),$example_doc_id,'DocumentAdded',1,0,NULL,0)", __FILE__, __LINE__);

		Database::query("INSERT INTO `".$TABLETOOLDOCUMENT . "`(path,title,filetype,size) VALUES ('/chat_files','chat_files','folder','0')", __FILE__, __LINE__);
		$example_doc_id = Database :: insert_id();
		Database::query("INSERT INTO `".$TABLEITEMPROPERTY . "` (tool,insert_user_id,insert_date,lastedit_date,ref,lastedit_type,lastedit_user_id,to_group_id,to_user_id,visibility) VALUES ('document',1,NOW(),NOW(),$example_doc_id,'DocumentAdded',1,0,NULL,0)", __FILE__, __LINE__);
		
		Database::query("INSERT INTO `".$TABLETOOLDOCUMENT . "`(path,title,filetype,size) VALUES ('/mindmaps','mindmaps','folder','0')", __FILE__, __LINE__);
		$example_doc_id = Database :: insert_id();
		Database::query("INSERT INTO `".$TABLEITEMPROPERTY . "` (tool,insert_user_id,insert_date,lastedit_date,ref,lastedit_type,lastedit_user_id,to_group_id,to_user_id,visibility) VALUES ('document',1,NOW(),NOW(),$example_doc_id,'DocumentAdded',1,0,NULL,0)", __FILE__, __LINE__);
		

		//FILL THE COURSE DOCUMENT WITH DEFAULT COURSE PICTURES
		$sys_course_path = api_get_path(SYS_COURSE_PATH);
		$display_order = 1;

		foreach($default_document_array as $media_type=>$array_media)
		{
			if($media_type=='images')
			{
				$path_documents='/images/';
				$course_documents_folder=$sys_course_path.$courseRepository.'/document/images/';
			}
			if($media_type=='audio')
			{
				$path_documents='/audio/';
				$course_documents_folder=$sys_course_path.$courseRepository.'/document/audio/';
			}
			if($media_type=='video')
			{
				$path_documents='/video/';
				$course_documents_folder=$sys_course_path.$courseRepository.'/document/video/';
			}
			if($media_type=='animations')
			{
				$path_documents='/animations/';
				$course_documents_folder=$sys_course_path.$courseRepository.'/document/animations/';
			}
			if($media_type=='mascot')
			{
				$path_documents='/mascot/';
				$course_documents_folder=$sys_course_path.$courseRepository.'/document/mascot/';
			}
			if($media_type=='mindmaps')
			{
				$path_documents='/mindmaps/';
				$course_documents_folder=$sys_course_path.$courseRepository.'/document/mindmaps/';
			}
			if($media_type=='photos')
			{
				$path_documents='/photos/';
				$course_documents_folder=$sys_course_path.$courseRepository.'/document/photos/';
			}
			if($media_type=='podcasts')
			{
				$path_documents='/podcasts/';
				$course_documents_folder=$sys_course_path.$courseRepository.'/document/podcasts/';
			}
			if($media_type=='screencasts')
			{
				$path_documents='/screencasts/';
				$course_documents_folder=$sys_course_path.$courseRepository.'/document/screencasts/';
			}
			if($media_type=='themes')
			{
				$path_documents='/themes/';
				$course_documents_folder=$sys_course_path.$courseRepository.'/document/themes/';
			}
			foreach($array_media as $key => $value)
			{
				if($value["dir"]!="")
				{
					$folder_path=substr($value["dir"],0,strlen($value["dir"])-1);
					$temp=explode("/",$folder_path);
					Database::query("INSERT INTO `".$TABLETOOLDOCUMENT . "`(path,title,filetype,size) VALUES ('$path_documents".$folder_path."','".$temp[count($temp)-1]."','folder','0')", __FILE__, __LINE__);
					$image_id = Database :: insert_id();
					Database::query("INSERT INTO `".$TABLEITEMPROPERTY . "` (tool,insert_user_id,insert_date,lastedit_date,ref,lastedit_type,lastedit_user_id,to_group_id,to_user_id,visibility) VALUES ('document',1,NOW(),NOW(),$image_id,'DocumentAdded',1,0,NULL,0)", __FILE__, __LINE__);
				}

				if($value["file"]!="")
				{
					$temp=explode("/",$value["file"]);
					$file_size=filesize($course_documents_folder.$value["file"]);
					if($path_documents == '/mindmaps/' && pathinfo($value["file"],PATHINFO_EXTENSION) != 'xmind')
					{					
					Database::query("INSERT INTO `".$TABLETOOLDOCUMENT . "`(path,title,filetype,size,display_order) VALUES ('$path_documents".$value["file"]."','".$temp[count($temp)-1]."','file','$file_size',$display_order)", __FILE__, __LINE__);
					$display_order = $display_order + 1;
					}
					else
					{
			        Database::query("INSERT INTO `".$TABLETOOLDOCUMENT . "`(path,title,filetype,size) VALUES ('$path_documents".$value["file"]."','".$temp[count($temp)-1]."','file','$file_size')", __FILE__, __LINE__);
					}			       
					$image_id = Database :: insert_id();
					Database::query("INSERT INTO `".$TABLEITEMPROPERTY . "` (tool,insert_user_id,insert_date,lastedit_date,ref,lastedit_type,lastedit_user_id,to_group_id,to_user_id,visibility) VALUES ('document',1,NOW(),NOW(),$image_id,'DocumentAdded',1,0,NULL,1)", __FILE__, __LINE__);
				}
			}
		}

		/*
		-----------------------------------------------------------
			Agenda tool
		-----------------------------------------------------------
		*/
		//Database::query("INSERT INTO `".$TABLETOOLAGENDA . "` VALUES ( NULL, '".lang2db(get_lang('AgendaCreationTitle')) . "', '".lang2db(get_lang('AgendaCreationContenu')) . "', now(), now(), NULL, 0)", __FILE__, __LINE__);
		//we need to add the item properties too!
		/*$insert_id = Database :: insert_id();
		$sql = "INSERT INTO `".$TABLEITEMPROPERTY . "` (tool,insert_user_id,insert_date,lastedit_date,ref,lastedit_type,lastedit_user_id,to_group_id,to_user_id,visibility) VALUES ('" . TOOL_CALENDAR_EVENT . "',1,NOW(),NOW(),$insert_id,'AgendaAdded',1,0,NULL,1)";
		Database::query($sql, __FILE__, __LINE__);*/

		/*
		-----------------------------------------------------------
			Links tool
		-----------------------------------------------------------
		*/
		$add_google_link_sql = "	INSERT INTO `".$TABLETOOLLINK . "` (url, title, description, category_id, display_order, on_homepage, target)
							VALUES ('http://www.google.com','Google','".lang2db(get_lang('Google')) . "','0','0','0','_self')";
		Database::query($add_google_link_sql, __FILE__, __LINE__);
		//we need to add the item properties too!
		$insert_id = Database :: insert_id();
		$sql = "INSERT INTO `".$TABLEITEMPROPERTY . "` (tool,insert_user_id,insert_date,lastedit_date,ref,lastedit_type,lastedit_user_id,to_group_id,to_user_id,visibility) VALUES ('" . TOOL_LINK . "',1,NOW(),NOW(),$insert_id,'LinkAdded',1,0,NULL,1)";
		Database::query($sql, __FILE__, __LINE__);

		$add_wikipedia_link_sql = "	INSERT INTO `".$TABLETOOLLINK . "` (url, title, description, category_id, display_order, on_homepage, target)
							VALUES ('http://www.wikipedia.org','Wikipedia','".lang2db(get_lang('Wikipedia')) . "','0','1','0','_self')";
		Database::query($add_wikipedia_link_sql, __FILE__, __LINE__);
		//we need to add the item properties too!
		$insert_id = Database :: insert_id();
		$sql = "INSERT INTO `".$TABLEITEMPROPERTY . "` (tool,insert_user_id,insert_date,lastedit_date,ref,lastedit_type,lastedit_user_id,to_group_id,to_user_id,visibility) VALUES ('" . TOOL_LINK . "',1,NOW(),NOW(),$insert_id,'LinkAdded',1,0,NULL,1)";
		Database::query($sql, __FILE__, __LINE__);

		/*
		-----------------------------------------------------------
			Annoucement tool
		-----------------------------------------------------------
		*/
		$sql = "INSERT INTO `".$TABLETOOLANNOUNCEMENTS . "` (title,content,end_date,display_order,email_sent) VALUES ('".lang2db(get_lang('AnnouncementExampleTitle')) . "', '".lang2db(get_lang('AnnouncementEx')) . "', NOW(), '1','0')";
		Database::query($sql, __FILE__, __LINE__);
		//we need to add the item properties too!
		$insert_id = Database :: insert_id();
		$sql = "INSERT INTO `".$TABLEITEMPROPERTY . "` (tool,insert_user_id,insert_date,lastedit_date,ref,lastedit_type,lastedit_user_id,to_group_id,to_user_id,visibility) VALUES ('" . TOOL_ANNOUNCEMENT . "',1,NOW(),NOW(),$insert_id,'AnnouncementAdded',1,0,NULL,1)";
		Database::query($sql, __FILE__, __LINE__);

		/*
		-----------------------------------------------------------
			Introduction text
		-----------------------------------------------------------
		*/

		$intro_text='&nbsp;';
		Database::query("INSERT INTO `".$TABLEINTROS . "` VALUES ('" . TOOL_COURSE_HOMEPAGE . "','".$intro_text. "')", __FILE__, __LINE__);
		Database::query("INSERT INTO `".$TABLEINTROS . "` VALUES ('" . TOOL_STUDENTPUBLICATION . "','".lang2db(get_lang('IntroductionTwo')) . "')", __FILE__, __LINE__);

		//wiki intro
		$intro_wiki='<table width="100%" border="0" cellpadding="0" cellspacing="0"><tr><td valign="top" align="left">'.lang2db(get_lang('IntroductionWiki')).'</td></tr></table>';
		Database::query("INSERT INTO `".$TABLEINTROS . "` VALUES ('" . TOOL_WIKI . "','".$intro_wiki. "')",__FILE__,__LINE__);

		if($language_interface != 'french'){
			$language_interface = "english";
		}

		/*
		-----------------------------------------------------------
			Exercise tool
		-----------------------------------------------------------
		*/
		$html=addslashes('<table width=\"100%\" border=\"0\" cellpadding=\"0\" cellspacing=\"0\"><tr><td width=\"110\" valign=\"top\" align=\"left\"><img src=\"'.api_get_path(WEB_CODE_PATH).'default_course_document/images/mr_dokeos/thinking.jpg\"></td><td valign=\"top\" align=\"left\">'.lang2db(get_lang('Antique')).'</td></tr></table>');
		Database::query('INSERT INTO `'.$TABLEQUIZ . '` (id, title, description, type, random, active, results_disabled ) VALUES ("1","'.lang2db(get_lang('DefaultExercise')) . '", "'.$html.'", "1", "0", "-1", "0")', __FILE__, __LINE__);	

		Database::query('INSERT INTO `'.$TABLEQUIZSCENARIO . '` (exercice_id, scenario_type, title, description, type, random, active, results_disabled ) VALUES ("1", "1", "'.lang2db(get_lang('DefaultExercise')) . '", "'.$html.'", "1", "0", "-1", "0")', __FILE__, __LINE__);
		Database::query('INSERT INTO `'.$TABLEQUIZSCENARIO . '` (exercice_id, scenario_type, title, description, type, random, active, results_disabled ) VALUES ("1", "2", "'.lang2db(get_lang('DefaultExercise')) . '", "'.$html.'", "1", "0", "-1", "0")', __FILE__, __LINE__);

		$html_img1 = "<table cellspacing=\"2\" cellpadding=\"0\" width=\"98%\" height=\"100%\" style=\"font-family: Comic Sans MS; font-size: 16px;\"><tbody>
        <tr><td align=\"center\" height=\"323px\"><img border=\"0\" align=\"absmiddle\" width=\"350\" vspace=\"0\" hspace=\"0\" height=\"328\" alt=\"Price_elasticity_of_demand2.png\" src=\"".api_get_path('WEB_COURSE_PATH').$courseRepository.'/document/images/diagrams/templates/Price_elasticity_of_demand2.png'."\" /></td></tr></tbody></table>";

		$html_img2 = "<table cellspacing=\"2\" cellpadding=\"0\" width=\"98%\" height=\"100%\" style=\"font-family: Comic Sans MS; font-size: 16px;\"><tbody>
        <tr><td align=\"center\" height=\"323px\"><img border=\"0\" align=\"absmiddle\" width=\"310\" vspace=\"0\" hspace=\"0\" height=\"310\" alt=\"Cross_elasticity_of_demand_complements.png\" src=\"".api_get_path('WEB_COURSE_PATH').$courseRepository.'/document/images/diagrams/templates/Cross_elasticity_of_demand_complements.png'."\" /></td>
        </tr></tbody></table>";		
		
		$html_img3 = "<table height=\"100%\" width=\"98%\" cellspacing=\"2\" cellpadding=\"0\" style=\"font-family: Comic Sans MS; font-size: 16px;\">\r\n    <tbody>\r\n        <tr>\r\n            <td height=\"323px\" align=\"center\"><img  src=\"".api_get_path('WEB_COURSE_PATH').$courseRepository.'/document/images/diagrams/templates/heartArrows4Numbers300.png'."\"  /></td>\r\n        </tr>\r\n    </tbody>\r\n</table>";	

		$html_img4 = "<table height=\"100%\" width=\"98%\" cellspacing=\"2\" cellpadding=\"0\" style=\"font-family: Comic Sans MS; font-size: 16px;\">\r\n    <tbody>\r\n        <tr>\r\n            <td height=\"323px\" align=\"center\"><img height=\"310px\" src=\"../img/instructor-faq.png\"  /></td>\r\n        </tr>\r\n    </tbody>\r\n</table>";	

		$html_img5 = "<p style=\"text-align: center;\">".lang2db(get_lang('html_img5_text'))."</p><p style=\"text-align: center;\"></p><p style=\"text-align: center;\"><img border=\"0\" align=\"absmiddle\" width=\"200\" vspace=\"0\" hspace=\"0\" height=\"133\" alt=\"Cornell_dormitories2.jpg\" src=\"".api_get_path('WEB_COURSE_PATH').$courseRepository.'/document/images/diagrams/templates/Cornell_dormitories2.jpg'."\" /></p>";
		$html_ans_6a = "<p><img border=\"0\" align=\"absmiddle\" width=\"250\" vspace=\"0\" hspace=\"0\" height=\"63\" alt=\"HPAnswer1_1.png\" src=\"".api_get_path('WEB_COURSE_PATH').$courseRepository.'/document/images/diagrams/templates/HPAnswer1_1.png'."\" /></p>";
		$html_ans_6b = "<p><img border=\"0\" align=\"absmiddle\" width=\"250\" vspace=\"0\" hspace=\"0\" height=\"63\" alt=\"HPAnswer2.png\" src=\"".api_get_path('WEB_COURSE_PATH').$courseRepository.'/document/images/diagrams/templates/HPAnswer2.png'."\" /></p>";
		$html_ans_6c = "<p><img border=\"0\" align=\"absmiddle\" width=\"250\" vspace=\"0\" hspace=\"0\" height=\"63\" alt=\"HPAnswer3_1.png\" src=\"".api_get_path('WEB_COURSE_PATH').$courseRepository.'/document/images/diagrams/templates/HPAnswer3_1.png'."\" /></p>";
		$html_ans_6d = "<p><img border=\"0\" align=\"absmiddle\" width=\"250\" vspace=\"0\" hspace=\"0\" height=\"63\" alt=\"HPAnswer4_3.png\" src=\"".api_get_path('WEB_COURSE_PATH').$courseRepository.'/document/images/diagrams/templates/HPAnswer4_3.png'."\" /></p>";
		$html_img6 = "<table cellspacing=\"2\" cellpadding=\"0\" width=\"98%\" height=\"100%\" style=\"font-family: Comic Sans MS; font-size: 16px;\">
        <tbody><tr><td align=\"center\" height=\"323px\"><img border=\"0\" align=\"absmiddle\" width=\"377\" vspace=\"0\" hspace=\"0\" height=\"300\" alt=\"HPQuestion_1.png\" src=\"".api_get_path('WEB_COURSE_PATH').$courseRepository.'/document/images/diagrams/templates/HPQuestion_1.png'."\" /></td></tr></tbody></table>";

		$html_img6_feedback_true = "<p><img border=\"0\" align=\"absmiddle\" width=\"376\" vspace=\"0\" hspace=\"0\" height=\"300\" alt=\"HPfeedback_1.png\" src=\"".api_get_path('WEB_COURSE_PATH').$courseRepository.'/document/images/diagrams/templates/HPfeedback_1.png'."\" />".lang2db(get_lang('html_img6_feedback_text'))."</p>";

		$html_img7 = "<table cellspacing=\"2\" cellpadding=\"0\ width=\"98%\" height=\100%\" style=\"font-family: Comic Sans MS; font-size: 16px;\">
       <tbody><tr><td align=\"center\" height=\"323px\"><p><img height=\"310px\" alt=\"\" src=\"../img/instructor-faq.png\" /></p>
            <p><embed width=\"300\" height=\"20\" flashvars=\"file=".api_get_path('WEB_COURSE_PATH').$courseRepository."/document/audio/EconomicCensus.mp3&amp;autostart=false\" allowscriptaccess=\"always\" allowfullscreen=\"false\" src=\"/main/inc/lib/mediaplayer/player.swf\" bgcolor=\"#FFFFFF\" pluginspage=\"http://www.macromedia.com/go/getflashplayer\" type=\"application/x-shockwave-flash\"></embed></p></td></tr></tbody></table>";

		$html_img8 = "<table width='98%' height='100%' cellspacing='2' cellpadding='0' style='font-family: Comic Sans MS; font-size: 16px;'>
                                <tbody>
                                    <tr>
                                        <td height='323px' align='center'>
                                        <div style='text-align: center;' id='player986311-parent'>
                                        <div style='border-style: none; height: 240px; width: 375px; overflow: hidden; background-color: rgb(220, 220, 220);' id='test'>
                                        <div style='display: none; visibility: hidden; width: 0px; height: 0px; overflow: hidden;' id='player986311-config'>url=/courses/".$courseRepository."/document/video/flv/OpenofficeSlideshow.flv width=375 height=240 loop=1 play=true downloadable=false fullscreen=true</div>
                                        <div class='thePlayer' id='player986311'><script src='".api_get_path(WEB_CODE_PATH)."inc/lib/fckeditor/editor/plugins/videoPlayer/jwplayer.min.js' type='text/javascript'></script>
                                        <div id='player986311-parent2'>Loading the player ...</div>
                                        <script type='text/javascript'>jwplayer('player986311-parent2').setup({flashplayer: '".api_get_path(WEB_CODE_PATH)."inc/lib/fckeditor/editor/plugins/videoPlayer/player.swf',autostart: 'true',repeat: 'always',file: '/courses/".$courseRepository."/document/video/flv/OpenofficeSlideshow.flv',height: 240,width: 375,skin: '".api_get_path(WEB_CODE_PATH)."inc/lib/fckeditor/editor/plugins/videoPlayer/skins/facebook.zip'});</script></div>
                                        </div>
                                        </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>";

		$html_img9 = "<table width='98%' height='100%' cellspacing='2' cellpadding='0' style='font-family: Comic Sans MS; font-size: 16px;'>
                                <tbody>
                                    <tr>
                                        <td height='323px' align='center'>
                                            <div style='text-align: center;' id='player939756-parent'>
                                                <div style='border-style: none; height: 300px; width: 350px; overflow: hidden; background-color: rgb(220, 220, 220);' id='test'>
                                                        <div style='display: none; visibility: hidden; width: 0px; height: 0px; overflow: hidden;' id='player939756-config'>url=/courses/".$courseRepository."/document/animations/SpinEchoSequence.swf width=350 height=300 loop=1 play=true</div>
                                                        <embed width='350' vspace='' hspace='' height='300' align='' play='true' loop='true' bgcolor='' scale='showall' quality='high' src='/courses/".$courseRepository."/document/animations/SpinEchoSequence.swf' type='application/x-shockwave-flash' pluginspage='http://www.macromedia.com/shockwave/download/index.cgi?P1_Prod_Version=ShockwaveFlash' controller='true'></embed>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>";

		$html_img10 = "<div style='text-align: center;' id='player833553-parent'>
                                    <div style='border-style: none; height: 250px; width: 375px; overflow: hidden; background-color: rgb(220, 220, 220);' id='test'>
                                        <div style='display: none; visibility: hidden; width: 0px; height: 0px; overflow: hidden;' id='player833553-config'>url=/courses/".$courseRepository."/document/video/flv/Bloedstolling.flv width=375 height=250 loop=1 play=true downloadable=false fullscreen=true</div>
                                        <div class='thePlayer' id='player833553'>
                                                <script src='".api_get_path(WEB_CODE_PATH)."inc/lib/fckeditor/editor/plugins/videoPlayer/jwplayer.min.js' type='text/javascript'></script>
                                                <div id='player833553-parent2'>Loading the player ...</div>
                                                <script type='text/javascript'>jwplayer('player833553-parent2').setup({flashplayer: '".api_get_path(WEB_CODE_PATH)."inc/lib/fckeditor/editor/plugins/videoPlayer/player.swf',autostart: 'true',repeat: 'always',file: '/courses/".$courseRepository."/document/video/flv/Bloedstolling.flv',height: 250,width: 375,skin: '".api_get_path(WEB_CODE_PATH)."inc/lib/fckeditor/editor/plugins/videoPlayer/skins/facebook.zip'});</script>
                                        </div>
                                    </div>
                              </div>";

		$html_img11 = "<table cellspacing=\"2\" cellpadding=\"0\" width=\"98%\" height=\"100%\" style=\"font-family: Comic Sans MS; font-size: 16px;\">
		<tbody><tr><td align=\"center\" height=\"323px\"><img border=\"0\" align=\"absmiddle\" width=\"380\" vspace=\"0\" hspace=\"0\" height=\"143\" alt=\"sleeping_1.png\" src=\"".api_get_path('WEB_COURSE_PATH').$courseRepository.'/document/images/diagrams/templates/sleeping_1.png'."\" /></td></tr></tbody></table>";

		$html_img12 = "<table cellspacing=\"2\" cellpadding=\"0\" width=\"98%\" height=\"100%\" style=\"font-family: Comic Sans MS; font-size: 16px;\"><tbody>
        <tr><td align=\"center\" height=\"323px\"><img border=\"0\" align=\"absmiddle\" width=\"380\" vspace=\"0\" hspace=\"0\" height=\"239\" alt=\"Solar_sys.jpg\" src=\"".api_get_path('WEB_COURSE_PATH').$courseRepository.'/document/images/diagrams/templates/Solar_sys.jpg'."\" /></td></tr></tbody></table>";

		$html_img13 = "<table height=\"100%\" width=\"98%\" cellspacing=\"2\" cellpadding=\"0\" style=\"font-family: Comic Sans MS; font-size: 16px;\"><tbody>
        <tr><td height=\"323px\" align=\"center\"><img hspace=\"0\" height=\"345\" width=\"350\" vspace=\"0\" border=\"0\" align=\"absmiddle\" alt=\"Traffic_lights.jpg\" src=\"".api_get_path('WEB_COURSE_PATH').$courseRepository.'/document/images/diagrams/templates/Traffic_lights.jpg'."\" /></td></tr></tbody></table>";

		$html_ans_13a = "<p><img hspace=\"0\" height=\"100\" width=\"100\" vspace=\"0\" border=\"0\" align=\"absmiddle\" alt=\"truck.jpg\" src=\"".api_get_path('WEB_COURSE_PATH').$courseRepository.'/document/images/diagrams/templates/truck.jpg'."\" /></p>";
		$html_ans_13b = "<p><img hspace=\"0\" height=\"100\" width=\"100\" vspace=\"0\" border=\"0\" align=\"absmiddle\" alt=\"railroad.jpg\" src=\"".api_get_path('WEB_COURSE_PATH').$courseRepository.'/document/images/diagrams/templates/railroad.jpg'."\" /></p>";
		$html_ans_13c = "<p><img hspace=\"0\" height=\"100\" width=\"100\" vspace=\"0\" border=\"0\" align=\"absmiddle\" alt=\"deer.jpg\" src=\"".api_get_path('WEB_COURSE_PATH').$courseRepository.'/document/images/diagrams/templates/deer.jpg'."\" /></p>";
		$html_ans_13d = "<p><img hspace=\"0\" height=\"100\" width=\"100\" vspace=\"0\" border=\"0\" align=\"absmiddle\" alt=\"pedestrian.jpg\" src=\"".api_get_path('WEB_COURSE_PATH').$courseRepository.'/document/images/diagrams/templates/pedestrian.jpg'."\" /></p>";

		$html_img14 = "<table cellspacing=\"2\" cellpadding=\"0\" width=\"98%\" height=\"100%\" style=\"font-family: Comic Sans MS; font-size: 16px;\"><tbody>
        <tr><td align=\"center\" height=\"323px\"><img border=\"0\" align=\"absmiddle\" width=\"300\" vspace=\"0\" hspace=\"0\" height=\"227\" alt=\"ViolentCrimeAmerica.png\" src=\"".api_get_path('WEB_COURSE_PATH').$courseRepository.'/document/images/diagrams/templates/ViolentCrimeAmerica.png'."\" /></td></tr></tbody></table>";

		$html_img15 = "<table cellspacing=\"2\" cellpadding=\"0\" width=\"98%\" height=\"100%\" style=\"font-family: Comic Sans MS; font-size: 16px;\"><tbody>
        <tr><td align=\"center\" height=\"323px\"><img alt=\"\" src=\"../img/KnockOnWood.png\" /></td></tr></tbody></table>";

		$html_ans_15 = "<table cellspacing=\"0\" cellpadding=\"10\" border=\"1\" align=\"center\" width=\"420\"><tbody><tr><td style=\"text-align: center;\"><strong>Treatment</strong></td><td style=\"text-align: center;\"><strong>Y</strong> or<strong> N</strong></td><td><p><strong>1</strong> = on day 1</p><p><strong>0</strong> = none</p><p><strong>D</strong> = discharge day</p></td></tr><tr><td style=\"text-align: center;\"><strong>Malaria </strong></td>
        <td style=\"text-align: center;\">[<u>Y</u>]&nbsp;&nbsp;</td><td style=\"text-align: center;\">&nbsp;[<u>1</u>]&nbsp;&nbsp;</td></tr><tr><td style=\"text-align: center;\"><strong>Polio </strong></td><td style=\"text-align: center;\">[<u>Y</u>]&nbsp;&nbsp;</td><td style=\"text-align: center;\">[<u> D</u>]&nbsp;&nbsp;</td></tr><tr><td style=\"text-align: center;\"><strong>Pneumococcus vaccin </strong></td><td style=\"text-align: center;\">[<u>N</u>]&nbsp;&nbsp;</td><td style=\"text-align: center;\">[<u>0</u>]&nbsp;&nbsp;</td></tr></tbody></table><p>&nbsp;</p>::10,10,10,10,10,10@";

		$comment_15 = "a:2:{s:10:\"comment[1]\";s:0:\"\";s:10:\"comment[2]\";s:0:\"\";}";
		$comment_16 = "a:2:{s:10:\"comment[1]\";s:0:\"\";s:10:\"comment[2]\";s:0:\"\";}";
		$comment_17 = "a:2:{s:10:\"comment[1]\";s:0:\"\";s:10:\"comment[2]\";s:0:\"\";}";
		$comment_18 = "a:2:{s:10:\"comment[1]\";s:0:\"\";s:10:\"comment[2]\";s:0:\"\";}";
		$comment_19 = "a:2:{s:10:\"comment[1]\";s:0:\"\";s:10:\"comment[2]\";s:0:\"\";}";

		$html_img16 = "<table cellspacing=\"2\" cellpadding=\"0\" width=\"98%\" height=\"100%\" style=\"font-family: Comic Sans MS; font-size: 16px;\"><tbody>
        <tr><td align=\"center\" height=\"323px\"><img border=\"0\" align=\"absmiddle\" width=\"270\" vspace=\"0\" hspace=\"0\" height=\"320\" alt=\"balance_scale_redone.jpg\" src=\"".api_get_path('WEB_COURSE_PATH').$courseRepository.'/document/images/diagrams/templates/balance_scale_redone.jpg'."\" /></td></tr></tbody></table>";

		$html_ans_16 = "<table cellspacing=\"0\" cellpadding=\"10\" border=\"1\" align=\"center\" width=\"380\"><tbody><tr><td bgcolor=\"#f5f5f5\" style=\"text-align: right;\"><strong>Patient</strong></td><td bgcolor=\"#f5f5f5\" style=\"text-align: center;\"><strong>Laura</strong></td><td bgcolor=\"#f5f5f5\" style=\"text-align: center;\"><strong>Bill</strong></td></tr><tr><td style=\"text-align: right;\">Age</td><td style=\"text-align: center;\">38</td><td style=\"text-align: center;\">44</td></tr><tr><td style=\"text-align: right;\">Height</td><td style=\"text-align: center;\">1.72 m</td><td style=\"text-align: center;\">1.88 m</td>   </tr><tr><td style=\"text-align: right;\">Weight</td><td style=\"text-align: center;\">65 kg</td><td style=\"text-align: center;\">[<u>103</u>] kg</td></tr><tr><td style=\"text-align: right;\">Blood Pressure</td><td style=\"text-align: center;\">120/75</td><td style=\"text-align: center;\">11/65</td></tr> <tr><td style=\"vertical-align: top; text-align: right;\">BMI</td><td style=\"vertical-align: top; text-align: center;\">[<u>22</u>]&nbsp;&nbsp;</td><td style=\"text-align: center;\">&nbsp;29</td></tr></tbody></table>::10,10@";

		$html_ans_17 = "<table cellspacing=\"0\" cellpadding=\"10\" border=\"1\" align=\"center\" width=\"420\"><tbody><tr><td>&nbsp;</td><td style=\"text-align: center;\"><strong>H</strong></td><td style=\"text-align: center;\"><strong>W</strong></td><td style=\"text-align: center;\"><strong>M</strong></td><td style=\"text-align: center;\"><strong>O</strong></td><td style=\"text-align: center;\"><strong>NS<br /></strong></td></tr><tr><td style=\"text-align: center;\"><strong>Laura</strong></td><td style=\"text-align: center;\">89</td><td style=\"text-align: center;\">12.3</td><td style=\"text-align: center;\">140</td><td style=\"text-align: center;\">Y</td><td style=\"text-align: center;\">[<u>SAM</u>]&nbsp;&nbsp;</td></tr><tr><td style=\"text-align: center;\"><strong>John</strong></td><td style=\"text-align: center;\">73.5</td><td style=\"text-align: center;\">6.3</td><td style=\"text-align: center;\">124</td><td style=\"text-align: center;\">N</td><td style=\"text-align: center;\">[<u>SAM</u>]&nbsp;&nbsp;</td></tr><tr><td style=\"text-align: center;\"><strong>Anna</strong></td><td style=\"text-align: center;\">94.5</td><td style=\"text-align: center;\">10</td><td style=\"text-align: center;\">108</td><td style=\"text-align: center;\">N</td><td style=\"text-align: center;\">[<u>SAM</u>]&nbsp;&nbsp;</td></tr><tr><td style=\"text-align: center;\"><strong>Bill</strong></td><td style=\"text-align: center;\">120</td><td style=\"text-align: center;\">13.8</td><td style=\"text-align: center;\">112</td><td style=\"text-align: center;\">N</td><td style=\"text-align: center;\">[<u>SAM</u>]&nbsp;&nbsp;</td></tr><tr><td style=\"text-align: center;\"><strong>Peter</strong></td><td style=\"text-align: center;\">67</td><td style=\"text-align: center;\">7.4</td><td style=\"text-align: center;\">130</td><td style=\"text-align: center;\">N</td><td style=\"text-align: center;\">[<u>N</u>]&nbsp;&nbsp;</td></tr></tbody></table><p>H = Height in cm, W = Weight in kg, M = Muac in mm, O = Oedema present Yes/No</p>::10,10,10,10,10@";

		$html_ans_18 = "<p>".lang2db(get_lang('html_ans_18_text'))."<sqdf></sqdf></p>::10,10,10@";

		$html_img18 = "<table cellspacing=\"2\" cellpadding=\"0\" style=\"font-family: Comic Sans MS; font-size: 16px; width: 375px; height: 277px;\"><tbody>
        <tr><td align=\"center\" height=\"323px\"><img border=\"0\" align=\"absmiddle\" width=\"254\" vspace=\"0\" hspace=\"0\" height=\"200\" alt=\"SpeechMike.png\" src=\"".api_get_path('WEB_COURSE_PATH').$courseRepository.'/document/mascot/SpeechMike.png'."\" /><embed width=\"300\" height=\"20\" type=\"application/x-shockwave-flash\" pluginspage=\"http://www.macromedia.com/go/getflashplayer\" bgcolor=\"#FFFFFF\" src=\"/main/inc/lib/mediaplayer/player.swf\" allowfullscreen=\"false\" allowscriptaccess=\"always\" flashvars=\"file=".api_get_path('WEB_COURSE_PATH').$courseRepository."/document/audio/EconCensus64.mp3&amp;autostart=false\"></embed></td></tr></tbody></table>";

		$html_ans_19 = "<table cellspacing=\"0\" cellpadding=\"10\" border=\"1\" align=\"center\" width=\"420\"><tbody><tr><td>&nbsp;</td><td style=\"text-align: center;\">[<u>M</u>]&nbsp;&nbsp;</td><td>&nbsp;</td><td style=\"vertical-align: top;\">&nbsp;</td><td style=\"vertical-align: top;\">&nbsp;</td><td style=\"vertical-align: top;\">&nbsp;</td><td style=\"vertical-align: top;\">&nbsp;</td><td style=\"vertical-align: top;\">&nbsp;</td><td style=\"vertical-align: top;\">&nbsp;</td></tr><tr><td style=\"text-align: center;\">[<u>V</u>]&nbsp;&nbsp;</td><td style=\"text-align: center;\">[<u>I</u>]&nbsp;&nbsp;</td><td style=\"text-align: center;\">[<u>S</u>]&nbsp;&nbsp;</td><td style=\"vertical-align: top; text-align: center;\">[<u>I</u>]&nbsp;&nbsp;</td><td style=\"vertical-align: top; text-align: center;\">[<u>O</u>]&nbsp;&nbsp;</td><td style=\"vertical-align: top; text-align: center;\">[<u>N</u>]&nbsp;&nbsp;</td>
       <td style=\"vertical-align: top;\">&nbsp;</td><td style=\"vertical-align: top;\">&nbsp;</td><td style=\"vertical-align: top;\">&nbsp;</td></tr><tr><td style=\"vertical-align: top;\">&nbsp;</td><td style=\"vertical-align: top; text-align: center;\">[<u>S</u>]&nbsp;&nbsp;</td><td style=\"vertical-align: top;\">&nbsp;</td><td style=\"vertical-align: top;\">&nbsp;</td><td style=\"vertical-align: top;\">&nbsp;</td><td style=\"vertical-align: top;\">&nbsp;</td>
       <td style=\"vertical-align: top;\">&nbsp;</td><td style=\"vertical-align: top;\">&nbsp;</td><td style=\"vertical-align: top;\">&nbsp;</td></tr>
        <tr><td style=\"vertical-align: top;\">&nbsp;</td><td style=\"vertical-align: top; text-align: center;\">[<u>S</u>]&nbsp;&nbsp;</td><td style=\"vertical-align: top; text-align: center;\">[<u>T</u>]&nbsp;&nbsp;</td><td style=\"vertical-align: top; text-align: center;\">[<u>R</u>]&nbsp;&nbsp;</td><td style=\"vertical-align: top; text-align: center;\">[<u>A</u>]&nbsp;&nbsp;</td><td style=\"vertical-align: top;\">[<u>T</u>]&nbsp;&nbsp;</td><td style=\"vertical-align: top; text-align: center;\">[<u>E</u>]&nbsp;&nbsp;</td><td style=\"vertical-align: top; text-align: center;\">[<u>G</u>]&nbsp;&nbsp;</td>
        <td style=\"vertical-align: top; text-align: center;\">[<u>Y</u>]&nbsp;&nbsp;</td></tr><tr><td style=\"vertical-align: top;\">&nbsp;</td><td style=\"vertical-align: top; text-align: center;\">[<u>I</u>]&nbsp;&nbsp;</td><td style=\"vertical-align: top;\">&nbsp;</td><td style=\"vertical-align: top;\">&nbsp;</td><td style=\"vertical-align: top;\">&nbsp;</td><td style=\"vertical-align: top;\">&nbsp;</td><td style=\"vertical-align: top;\">&nbsp;</td><td style=\"vertical-align: top;\">&nbsp;</td><td style=\"vertical-align: top;\">&nbsp;</td></tr><tr><td style=\"vertical-align: top; text-align: center;\">[<u>P</u>]&nbsp;&nbsp;</td><td style=\"vertical-align: top;\">[<u>O</u>]&nbsp;&nbsp;</td><td style=\"vertical-align: top; text-align: center;\">[<u>L</u>]&nbsp;&nbsp;</td><td style=\"vertical-align: top; text-align: center;\">[<u>I</u>]&nbsp;&nbsp;</td><td style=\"vertical-align: top; text-align: center;\">[<u>C</u>]&nbsp;&nbsp;</td><td style=\"vertical-align: top; text-align: center;\">[<u>Y</u>]&nbsp;&nbsp;</td><td style=\"vertical-align: top;\">&nbsp;</td><td style=\"vertical-align: top;\">&nbsp;</td><td style=\"vertical-align: top;\">&nbsp;</td></tr><tr><td style=\"vertical-align: top;\">&nbsp;</td><td style=\"vertical-align: top; text-align: center;\">[<u>N</u>]&nbsp;&nbsp;</td><td style=\"vertical-align: top;\">&nbsp;</td><td style=\"vertical-align: top;\">&nbsp;</td><td style=\"vertical-align: top;\">&nbsp;</td><td style=\"vertical-align: top;\">&nbsp;</td><td style=\"vertical-align: top;\">&nbsp;</td><td style=\"vertical-align: top;\">&nbsp;</td><td style=\"vertical-align: top;\">&nbsp;</td></tr></tbody></table><p>&nbsp;</p>::10,10,10,10,10,10,10,10,10,10,10,10,10,10,10,10,10,10,10,10,10,10,10,10,10@";

		$html_img19 = "<p>&nbsp;</p><p>1 Vertical&nbsp; : In the B-bath Company, it is to make soap<br />1 Horizontal : Intended direction <br />2 Horizontal : provides a guideline to managers decision making<br />3 Horizontal contains rules</p><p style=\"text-align: center;\">&nbsp;</p><p style=\"text-align: center;\"><img border=\"0\" align=\"absmiddle\" width=\"239\" vspace=\"0\" hspace=\"0\" height=\"150\" alt=\"240business_meeting.jpg\" src=\"".api_get_path('WEB_COURSE_PATH').$courseRepository.'/document/images/diagrams/templates/240business_meeting.jpg'."\" /></p>";

		$html_img20 = "<table height=\"100%\" width=\"98%\" cellspacing=\"2\" cellpadding=\"0\" style=\"font-family: Comic Sans MS; font-size: 16px;\"><tbody>
        <tr><td height=\"323px\" align=\"center\"><img hspace=\"0\" height=\"205\" width=\"350\" vspace=\"0\" border=\"0\" align=\"absmiddle\" alt=\"6Hats_1.png\" src=\"".api_get_path('WEB_COURSE_PATH').$courseRepository.'/document/images/diagrams/templates/6Hats_1.png'."\" /></td></tr></tbody></table>";	
		
		$html_img21 = "<table cellspacing=\"2\" cellpadding=\"0\" width=\"98%\" height=\"100%\" style=\"font-family: Comic Sans MS; font-size: 16px;\"><tbody><tr>  <td align=\"center\" height=\"323px\"><img alt=\"\" src=\"../img/instructor-idea.jpg\" /></td></tr></tbody></table>";

		$html_img22 = "<table cellspacing=\"2\" cellpadding=\"0\" width=\"98%\" height=\"100%\" style=\"font-family: Comic Sans MS; font-size: 16px;\"><tbody><tr>  <td align=\"center\" height=\"323px\"><img border=\"0\" align=\"absmiddle\" width=\"380\" vspace=\"0\" hspace=\"0\" height=\"309\" alt=\"Board2_1.png\" src=\"".api_get_path('WEB_COURSE_PATH').$courseRepository.'/document/images/diagrams/templates/Board2_1.png'."\" /></td></tr></tbody></table>";

		Database::query("INSERT INTO  `".$TABLEQUIZQUESTIONLIST . "` (`id`, `question`, `description`, `ponderation`, `position`, `type`, `picture`, `level`) VALUES
		(1, '".lang2db(get_lang('DefaultQuizQuestion1')) ."', '".Database::escape_string($html_img1)."', 20.00, 1, 1, '', 1),
		(2, '".lang2db(get_lang('DefaultQuizQuestion2')) ."', '".Database::escape_string($html_img2)."', 20.00, 2, 1, '', 1),
		(3, '".lang2db(get_lang('DefaultQuizQuestion3')) ."', '".Database::escape_string($html_img3)."', 20.00, 3, 1, '', 1),
		(4, '".lang2db(get_lang('DefaultQuizQuestion4')) ."', '".Database::escape_string($html_img4)."', 20.00, 4, 1, '', 1),
		(5, '".lang2db(get_lang('DefaultQuizQuestion5')) ."', '".Database::escape_string($html_img5)."', 20.00, 5, 1, '', 1),
		(6, '".lang2db(get_lang('DefaultQuizQuestion6')) ."', '".Database::escape_string($html_img6)."', 20.00, 6, 1, '', 1),
		(7, '".lang2db(get_lang('DefaultQuizQuestion7')) ."', '".Database::escape_string($html_img7)."', 20.00, 7, 1, '', 1),
		(8, '".lang2db(get_lang('DefaultQuizQuestion8')) ."', '".Database::escape_string($html_img8)."', 20.00, 8, 1, '', 1),
		(9, '".lang2db(get_lang('DefaultQuizQuestion9')) ."', '".Database::escape_string($html_img9)."', 20.00, 9, 1, '', 1),
		(10, '".lang2db(get_lang('DefaultQuizQuestion10')) ."', '".Database::escape_string($html_img10)."', 20.00, 10, 1, '', 1),
		(11, '".lang2db(get_lang('DefaultQuizQuestion11')) ."', '".Database::escape_string($html_img11)."', 20.00, 11, 2, '', 1),	
		(12, '".lang2db(get_lang('DefaultQuizQuestion12')) ."', '".Database::escape_string($html_img12)."', 20.00, 12, 8, '', 1),	
		(13, '".lang2db(get_lang('DefaultQuizQuestion13')) ."', '".Database::escape_string($html_img13)."', 20.00, 13, 2, '', 1),	
		(14, '".lang2db(get_lang('DefaultQuizQuestion14')) ."', '".Database::escape_string($html_img14)."', 20.00, 14, 8, '', 1),
		(15, '".lang2db(get_lang('DefaultQuizQuestion15')) ."', '".Database::escape_string($html_img15)."', 60.00, 15, 3, '', 1),
		(16, '".lang2db(get_lang('DefaultQuizQuestion16')) ."', '".Database::escape_string($html_img16)."', 20.00, 16, 3, '', 1),
		(17, '".lang2db(get_lang('DefaultQuizQuestion17')) ."', '".Database::escape_string($html_img15)."', 50.00, 17, 3, '', 1),
		(18, '".lang2db(get_lang('DefaultQuizQuestion18')) ."', '".Database::escape_string($html_img18)."', 30.00, 18, 3, '', 1),
		(19, '".lang2db(get_lang('DefaultQuizQuestion19')) ."', '".Database::escape_string($html_img19)."', 250.00, 19, 3, '', 1),
		(20, '".lang2db(get_lang('DefaultQuizQuestion20')) ."', '".Database::escape_string($html_img20)."', 20.00, 20, 5, '', 1),
		(21, '".lang2db(get_lang('DefaultQuizQuestion21')) ."', '".Database::escape_string($html_img21)."', 20.00, 21, 5, '', 1),
		(22, '".lang2db(get_lang('DefaultQuizQuestion22')) ."', '".Database::escape_string($html_img22)."', 20.00, 22, 5, '', 1),
		(23, '".lang2db(get_lang('DefaultQuestion18')) ."', '', 20.00, 23, 4, '', 1),
		(24, '".lang2db(get_lang('DefaultQuestion19')) ."', '', 20.00, 24, 4, '', 1),
		(25, '".lang2db(get_lang('DefaultQuizQuestion24')) ."', '', 20.00, 25, 4, '', 1),
		(26, '".lang2db(get_lang('DefaultQuizQuestion25')) ."', '', 20.00, 26, 4, '', 1),
		(27, '".lang2db(get_lang('DefaultQuizQuestion27')) ."', '', 40.00, 27, 6, 'quiz-27.jpg', 1),
		(28, '".lang2db(get_lang('DefaultQuizQuestion28')) ."', '', 30.00, 28, 6, 'quiz-28.jpg', 1),
		(29, '".lang2db(get_lang('DefaultQuizQuestion29')) ."', '', 30.00, 29, 6, 'quiz-29.jpg', 1),
		(30, '".lang2db(get_lang('DefaultQuizQuestion30')) ."', '', 30.00, 30, 6, 'quiz-30.jpg', 1)", __FILE__, __LINE__);

		Database::query("INSERT INTO `".$TABLEQUIZANSWERSLIST . "` (`id`, `question_id`, `answer`, `correct`, `comment`, `ponderation`, `position`, `hotspot_coordinates`, `hotspot_type`, `destination`) VALUES
		(1, 1, '".lang2db(get_lang('QuizAnswer_1a')) ."', 0, '".lang2db(get_lang('Feedback_qn1_true')) ."', 0.00, 1, '', '', '0@@0@@0@@0'),
		(2, 1, '".lang2db(get_lang('QuizAnswer_1b')) ."', 1, '".lang2db(get_lang('Feedback_qn1_true')) ."', 20.00, 2, '', '', '0@@0@@0@@0'),		
		(4, 2, '".lang2db(get_lang('QuizAnswer_2d')) ."', 0, '".lang2db(get_lang('Feedback_qn2_true')) ."', 0.00, 4, '', '', '0@@0@@0@@0'),
		(3, 2, '".lang2db(get_lang('QuizAnswer_2c')) ."', 0, '".lang2db(get_lang('Feedback_qn2_true')) ."', 0.00, 3, '', '', '0@@0@@0@@0'),
		(2, 2, '".lang2db(get_lang('QuizAnswer_2b')) ."', 1, '".lang2db(get_lang('Feedback_qn2_true')) ."', 20.00, 2, '', '', '0@@0@@0@@0'),
		(1, 2, '".lang2db(get_lang('QuizAnswer_2a')) ."', 0, '".lang2db(get_lang('Feedback_qn2_true')) ."', 0.00, 1, '', '', '0@@0@@0@@0'),
		(4, 3, '".lang2db(get_lang('QuizAnswer_3d')) ."', 0, '', 0.00, 4, '', '', '0@@0@@0@@0'),
		(3, 3, '".lang2db(get_lang('QuizAnswer_3c')) ."', 0, '', 0.00, 3, '', '', '0@@0@@0@@0'),
		(2, 3, '".lang2db(get_lang('QuizAnswer_3b')) ."', 1, '', 20.00, 2, '', '', '0@@0@@0@@0'),
		(1, 3, '".lang2db(get_lang('QuizAnswer_3a')) ."', 0, '', 0.00, 1, '', '', '0@@0@@0@@0'),
		(1, 4, '".lang2db(get_lang('QuizAnswer_4a')) ."', 0, '".lang2db(get_lang('Feedback_qn4_true')) ."', 0.00, 1, '', '', '0@@0@@0@@0'),
		(2, 4, '".lang2db(get_lang('QuizAnswer_4b')) ."', 0, '".lang2db(get_lang('Feedback_qn4_true')) ."', 0.00, 2, '', '', '0@@0@@0@@0'),
		(3, 4, '".lang2db(get_lang('QuizAnswer_4c')) ."', 0, '".lang2db(get_lang('Feedback_qn4_true')) ."', 0.00, 3, '', '', '0@@0@@0@@0'),
		(4, 4, '".lang2db(get_lang('QuizAnswer_4d')) ."', 1, '".lang2db(get_lang('Feedback_qn4_true')) ."', 20.00, 4, '', '', '0@@0@@0@@0'),
		(1, 5, '".lang2db(get_lang('QuizAnswer_5a')) ."', 0, '".lang2db(get_lang('Feedback_qn5_true')) ."', 0.00, 1, '', '', '0@@0@@0@@0'),
		(2, 5, '".lang2db(get_lang('QuizAnswer_5b')) ."', 0, '".lang2db(get_lang('Feedback_qn5_true')) ."', 0.00, 2, '', '', '0@@0@@0@@0'),
		(3, 5, '".lang2db(get_lang('QuizAnswer_5c')) ."', 0, '".lang2db(get_lang('Feedback_qn5_true')) ."', 0.00, 3, '', '', '0@@0@@0@@0'),
		(4, 5, '".lang2db(get_lang('QuizAnswer_5d')) ."', 1, '".lang2db(get_lang('Feedback_qn5_true')) ."', 20.00, 4, '', '', '0@@0@@0@@0'),
		(1, 6, '".$html_ans_6a."', 0, '".$html_img6_feedback_true."', 0.00, 1, '', '', '0@@0@@0@@0'),
		(2, 6, '".$html_ans_6b."', 0, '".$html_img6_feedback_true."', 0.00, 2, '', '', '0@@0@@0@@0'),
		(3, 6, '".$html_ans_6c."', 1, '".$html_img6_feedback_true."', 20.00, 3, '', '', '0@@0@@0@@0'),
		(4, 6, '".$html_ans_6d."', 0, '".$html_img6_feedback_true."', 0.00, 4, '', '', '0@@0@@0@@0'),
		(4, 7, '".lang2db(get_lang('QuizAnswer_7a')) ."', 1, '".lang2db(get_lang('Feedback_qn7_true')) ."', 20.00, 4, '', '', ''),
		(3, 7, '".lang2db(get_lang('QuizAnswer_7b')) ."', 0, '".lang2db(get_lang('Feedback_qn7_true')) ."', 0.00, 3, '', '', ''),
		(2, 7, '".lang2db(get_lang('QuizAnswer_7c')) ."', 0, '".lang2db(get_lang('Feedback_qn7_true')) ."', 0.00, 2, '', '', ''),
		(1, 7, '".lang2db(get_lang('QuizAnswer_7d')) ."', 0, '".lang2db(get_lang('Feedback_qn7_true')) ."', 0.00, 1, '', '', ''),
		(1, 8, '".lang2db(get_lang('QuizAnswer_8a')) ."', 0, '', 0.00, 1, '', '', ''),
		(2, 8, '".lang2db(get_lang('QuizAnswer_8b')) ."', 0, '', 0.00, 2, '', '', ''),
		(3, 8, '".lang2db(get_lang('QuizAnswer_8c')) ."', 0, '', 0.00, 3, '', '', ''),
		(4, 8, '".lang2db(get_lang('QuizAnswer_8d')) ."', 1, '', 20.00, 4, '', '', ''),
		(1, 9, '".lang2db(get_lang('QuizAnswer_9a')) ."', 0, '".lang2db(get_lang('Feedback_qn9_true')) ."', 0.00, 1, '', '', ''),
		(2, 9, '".lang2db(get_lang('QuizAnswer_9b')) ."', 0, '".lang2db(get_lang('Feedback_qn9_true')) ."', 0.00, 2, '', '', ''),
		(3, 9, '".lang2db(get_lang('QuizAnswer_9c')) ."', 0, '".lang2db(get_lang('Feedback_qn9_true')) ."', 0.00, 3, '', '', ''),
		(4, 9, '".lang2db(get_lang('QuizAnswer_9d')) ."', 1, '".lang2db(get_lang('Feedback_qn9_true')) ."', 20.00, 4, '', '', ''),
		(1, 10, '".lang2db(get_lang('QuizAnswer_10a')) ."', 1, '".lang2db(get_lang('Feedback_qn10_true')) ."', 20.00, 1, '', '', ''),
		(2, 10, '".lang2db(get_lang('QuizAnswer_10b')) ."', 0, '".lang2db(get_lang('Feedback_qn10_true')) ."', 0.00, 2, '', '', ''),
		(3, 10, '".lang2db(get_lang('QuizAnswer_10c')) ."', 0, '".lang2db(get_lang('Feedback_qn10_true')) ."', 0.00, 3, '', '', ''),
		(4, 10, '".lang2db(get_lang('QuizAnswer_10d')) ."', 0, '".lang2db(get_lang('Feedback_qn10_true')) ."', 0.00, 4, '', '', ''),
		(1, 11, '".lang2db(get_lang('QuizAnswer_11a')) ."', 1, '".lang2db(get_lang('Feedback_qn8_true')) ."', 10.00, 1, '', '', ''),
		(2, 11, '".lang2db(get_lang('QuizAnswer_11b')) ."', 1, '".lang2db(get_lang('Feedback_qn8_true')) ."', 10.00, 2, '', '', ''),
		(3, 11, '".lang2db(get_lang('QuizAnswer_11c')) ."', 0, '".lang2db(get_lang('Feedback_qn8_true')) ."', 0.00, 3, '', '', ''),
		(4, 11, '".lang2db(get_lang('QuizAnswer_11d')) ."', 1, '".lang2db(get_lang('Feedback_qn8_true')) ."', 10.00, 4, '', '', ''),
		(1, 12, '".lang2db(get_lang('QuizAnswer_12a')) ."', 1, '".lang2db(get_lang('Feedback_qn12_true')) ."', 10.00, 1, '', '', ''),
		(2, 12, '".lang2db(get_lang('QuizAnswer_12b')) ."', 0, '".lang2db(get_lang('Feedback_qn12_true')) ."', 0.00, 2, '', '', ''),
		(3, 12, '".lang2db(get_lang('QuizAnswer_12c')) ."', 0, '".lang2db(get_lang('Feedback_qn12_true')) ."', 0.00, 3, '', '', ''),
		(4, 12, '".lang2db(get_lang('QuizAnswer_12d')) ."', 1, '".lang2db(get_lang('Feedback_qn12_true')) ."', 10.00, 4, '', '', ''),
		(1, 13, '".$html_ans_13a."', 1, '".lang2db(get_lang('Feedback_qn13_true'))."', 10.00, 1, '', '', '0@@0@@0@@0'),
		(2, 13, '".$html_ans_13b."', 0, '".lang2db(get_lang('Feedback_qn13_true'))."', 0.00, 2, '', '', '0@@0@@0@@0'),
		(3, 13, '".$html_ans_13c."', 0, '".lang2db(get_lang('Feedback_qn13_true'))."', 0.00, 3, '', '', '0@@0@@0@@0'),
		(4, 13, '".$html_ans_13d."', 1, '".lang2db(get_lang('Feedback_qn13_true'))."', 10.00, 4, '', '', '0@@0@@0@@0'),
		(1, 14, '".lang2db(get_lang('QuizAnswer_14a')) ."', 1, '', 10.00, 1, '', '', ''),
		(2, 14, '".lang2db(get_lang('QuizAnswer_14b')) ."', 0, '', 0.00, 2, '', '', ''),
		(3, 14, '".lang2db(get_lang('QuizAnswer_14c')) ."', 0, '', 0.00, 3, '', '', ''),
		(4, 14, '".lang2db(get_lang('QuizAnswer_14d')) ."', 1, '', 10.00, 4, '', '', ''),
		(1, 15, '".$html_ans_15."', 1, '".$comment_15."', 0.00, 0, '', '', ''),
		(1, 16, '".$html_ans_16."', 1, '".$comment_16."', 0.00, 0, '', '', ''),
		(1, 17, '".$html_ans_17."', 1, '".$comment_17."', 0.00, 0, '', '', ''),
		(1, 18, '".$html_ans_18."', 1, '".$comment_18."', 0.00, 0, '', '', ''),
		(1, 19, '".$html_ans_19."', 1, '".$comment_19."', 0.00, 0, '', '', ''),
		(1, 23, '<p>".lang2db(get_lang('langAnswer18_a'))." <img hspace=\"0\" height=\"64\" width=\"64\" vspace=\"0\" border=\"0\" align=\"absmiddle\" src=\"".api_get_path('WEB_COURSE_PATH').$courseRepository.'/document/images/diagrams/templates/ColumbiaRiverTr64.png'."\" alt=\"ColumbiaRiverTr64.png\" /></p>', 0, '".lang2db(get_lang('Feedback_qn18_true'))."', 0.00, 1, '', '', ''),
		(2, 23, '<p>".lang2db(get_lang('langAnswer18_b'))." <img hspace=\"0\" height=\"64\" width=\"64\" vspace=\"0\" border=\"0\" align=\"absmiddle\" src=\"".api_get_path('WEB_COURSE_PATH').$courseRepository.'/document/images/diagrams/templates/RioGrandeTr64.png'."\" alt=\"RioGrandeTr64.png\" /></p>', 0, '".lang2db(get_lang('Feedback_qn18_false'))."', 0.00, 2, '', '', ''),
		(3, 23, '<p>".lang2db(get_lang('langAnswer18_c'))." <img hspace=\"0\" height=\"64\" width=\"64\" vspace=\"0\" border=\"0\" align=\"absmiddle\" src=\"".api_get_path('WEB_COURSE_PATH').$courseRepository.'/document/images/diagrams/templates/TenesseeRiverTr64.png'."\" alt=\"TenesseeRiverTr64.png\" /></p>', 0, '', 0.00, 3, '', '', ''),
		(4, 23, '<p>".lang2db(get_lang('langAnswer18_d'))."&nbsp; <img hspace=\"0\" height=\"64\" width=\"64\" vspace=\"0\" border=\"0\" align=\"absmiddle\" src=\"".api_get_path('WEB_COURSE_PATH').$courseRepository.'/document/images/diagrams/templates/ArkansasRiverTr64.png'."\" alt=\"ArkansasRiverTr64.png\" /></p>', 0, '', 0.00, 4, '', '', ''),
		(5, 23, '<p>".lang2db(get_lang('langAnswer18_e'))." <img hspace=\"0\" height=\"64\" width=\"68\" vspace=\"0\" border=\"0\" align=\"absmiddle\" src=\"".api_get_path('WEB_COURSE_PATH').$courseRepository.'/document/images/diagrams/templates/New_Mexico2tr64.png'."\" alt=\"New_Mexico2tr64.png\" /></p>', 1, '', 5.00, 5, '', '', ''),
		(6, 23, '<p>".lang2db(get_lang('langAnswer18_f'))." <img hspace=\"0\" height=\"64\" width=\"64\" vspace=\"0\" border=\"0\" align=\"absmiddle\" src=\"".api_get_path('WEB_COURSE_PATH').$courseRepository.'/document/images/diagrams/templates/AlabampaMapOutlineBlue2Tr64.png'."\" alt=\"AlabampaMapOutlineBlue2Tr64.png\" /></p>', 1, '', 5.00, 6, '', '', ''),
		(7, 23, '<p>".lang2db(get_lang('langAnswer18_g'))."&nbsp; <img hspace=\"0\" height=\"64\" width=\"64\" vspace=\"0\" border=\"0\" align=\"absmiddle\" src=\"".api_get_path('WEB_COURSE_PATH').$courseRepository.'/document/images/diagrams/templates/OklahomaMapOutline3Tr64.png'."\" alt=\"OklahomaMapOutline3Tr64.png\" /></p>', 1, '', 5.00, 7, '', '', ''),
		(8, 23, '<p>".lang2db(get_lang('langAnswer18_h'))." <img hspace=\"0\" height=\"64\" width=\"64\" vspace=\"0\" border=\"0\" align=\"absmiddle\" src=\"".api_get_path('WEB_COURSE_PATH').$courseRepository.'/document/images/diagrams/templates/WashingtonStateMapOutline2tr64.png'."\" alt=\"WashingtonStateMapOutline2tr64.png\" /></p>', 1, '', 5.00, 8, '', '', ''),
		(1, 24, '<p><img hspace=\"0\" height=\"64\" width=\"64\" vspace=\"0\" border=\"0\" align=\"absmiddle\" src=\"".api_get_path('WEB_COURSE_PATH').$courseRepository.'/document/images/diagrams/templates/medical15.png'."\" alt=\"medical15.png\" />&nbsp; ".lang2db(get_lang('langAnswer19_a'))."</p>', 0, '', 0.00, 1, '', '', ''),
		(2, 24, '<p><img hspace=\"0\" height=\"64\" width=\"64\" vspace=\"0\" border=\"0\" align=\"absmiddle\" src=\"".api_get_path('WEB_COURSE_PATH').$courseRepository.'/document/images/diagrams/templates/medic25.png'."\" alt=\"medic25.png\" />&nbsp; ".lang2db(get_lang('langAnswer19_b'))."</p>', 0, '', 0.00, 2, '', '', ''),
		(3, 24, '<p><img hspace=\"0\" height=\"64\" width=\"64\" vspace=\"0\" border=\"0\" align=\"absmiddle\" src=\"".api_get_path('WEB_COURSE_PATH').$courseRepository.'/document/images/diagrams/templates/medicalhandpointing.png'."\" alt=\"medicalhandpointing.png\" /> ".lang2db(get_lang('langAnswer19_c'))."</p>', 0, '', 0.00, 3, '', '', ''),
		(4, 24, '', 0, '', 0.00, 4, '', '', ''),
		(5, 24, '<p><img style=\"text-align: center;\" hspace=\"0\" height=\"64\" width=\"64\" vspace=\"0\" border=\"0\" align=\"absmiddle\" src=\"".api_get_path('WEB_COURSE_PATH').$courseRepository.'/document/images/icons/logic/01.png'."\" alt=\"01.png\" /></p>', 3, '', 6.67, 5, '', '', ''),
		(6, 24, '<p><img style=\"text-align: center;\" hspace=\"0\" height=\"64\" width=\"64\" vspace=\"0\" border=\"0\" align=\"absmiddle\" src=\"".api_get_path('WEB_COURSE_PATH').$courseRepository.'/document/images/icons/logic/02.png'."\" alt=\"02.png\" /></p>', 2, '', 6.67, 6, '', '', ''),
		(7, 24, '<p><img style=\"text-align: center;\" hspace=\"0\" height=\"64\" width=\"64\" vspace=\"0\" border=\"0\" align=\"absmiddle\" src=\"".api_get_path('WEB_COURSE_PATH').$courseRepository.'/document/images/icons/logic/03.png'."\" alt=\"03.png\" /></p>', 1, '', 6.67, 7, '', '', ''),	
		(1, 25, '<p style=\"text-align: center;\"><img hspace=\"0\" height=\"37\" width=\"31\" vspace=\"0\" border=\"0\" align=\"absmiddle\" src=\"".api_get_path('WEB_COURSE_PATH').$courseRepository.'/document/images/diagrams/templates/A.png'."\" alt=\"A.png\" /></p>', 0, '".lang2db(get_lang('Feedback_qn24_true'))."', 0.00, 1, '', '', ''),
		(2, 25, '<p style=\"text-align: center;\"><img hspace=\"0\" height=\"37\" width=\"37\" vspace=\"0\" border=\"0\" align=\"absmiddle\" src=\"".api_get_path('WEB_COURSE_PATH').$courseRepository.'/document/images/diagrams/templates/B_1.png'."\" alt=\"B_1.png\" /></p>', 0, '".lang2db(get_lang('Feedback_qn24_true'))."', 0.00, 2, '', '', ''),
		(3, 25, '<p style=\"text-align: center;\"><img hspace=\"0\" height=\"36\" width=\"199\" vspace=\"0\" border=\"0\" align=\"absmiddle\" src=\"".api_get_path('WEB_COURSE_PATH').$courseRepository.'/document/images/diagrams/templates/_AorB_andnonA.png'."\" alt=\"_AorB_andnonA.png\" /></p>', 0, '', 0.00, 3, '', '', ''),
		(4, 25, '<p style=\"text-align: center;\"><img hspace=\"0\" height=\"37\" width=\"145\" vspace=\"0\" border=\"0\" align=\"absmiddle\" src=\"".api_get_path('WEB_COURSE_PATH').$courseRepository.'/document/images/diagrams/templates/AandnonA.png'."\" alt=\"AandnonA.png\" /></p>', 0, '', 0.00, 4, '', '', ''),
		(5, 25, '<p style=\"text-align: center;\"><img hspace=\"0\" height=\"37\" width=\"111\" vspace=\"0\" border=\"0\" align=\"absmiddle\" src=\"".api_get_path('WEB_COURSE_PATH').$courseRepository.'/document/images/diagrams/templates/AorB.png'."\" alt=\"AorB.png\" /></p>', 0, '', 0.00, 5, '', '', ''),
		(6, 25, '<p style=\"text-align: center;\"><img hspace=\"0\" height=\"64\" width=\"64\" vspace=\"0\" border=\"0\" align=\"absmiddle\" src=\"".api_get_path('WEB_COURSE_PATH').$courseRepository.'/document/images/icons/logic/01.png'."\" alt=\"01.png\" /></p>', 4, '', 4.00, 6, '', '', ''),
		(7, 25, '<p style=\"text-align: center;\"><img hspace=\"0\" height=\"64\" width=\"64\" vspace=\"0\" border=\"0\" align=\"absmiddle\" src=\"".api_get_path('WEB_COURSE_PATH').$courseRepository.'/document/images/icons/logic/02.png'."\" alt=\"02.png\" /></p>', 1, '', 4.00, 7, '', '', ''),
		(8, 25, '<p style=\"text-align: center;\"><img hspace=\"0\" height=\"64\" width=\"64\" vspace=\"0\" border=\"0\" align=\"absmiddle\" src=\"".api_get_path('WEB_COURSE_PATH').$courseRepository.'/document/images/icons/logic/03.png'."\" alt=\"03.png\" /></p>', 5, '', 4.00, 8, '', '', ''),
		(9, 25, '<p style=\"text-align: center;\"><img hspace=\"0\" height=\"64\" width=\"64\" vspace=\"0\" border=\"0\" align=\"absmiddle\" src=\"".api_get_path('WEB_COURSE_PATH').$courseRepository.'/document/images/icons/logic/04.png'."\" alt=\"04.png\" /></p>', 3, '', 4.00, 9, '', '', ''),
		(10, 25, '<p style=\"text-align: center;\"><img hspace=\"0\" height=\"64\" width=\"64\" vspace=\"0\" border=\"0\" align=\"absmiddle\" src=\"".api_get_path('WEB_COURSE_PATH').$courseRepository.'/document/images/icons/logic/05.png'."\" alt=\"05.png\" /></p>', 2, '', 4.00, 10, '', '', ''),		
		(1, 26, '<p><img hspace=\"0\" height=\"100\" width=\"50\" vspace=\"0\" border=\"0\" align=\"absmiddle\" alt=\"Compression.jpeg\" src=\"".api_get_path('WEB_COURSE_PATH').$courseRepository.'/document/images/diagrams/templates/Compression.jpeg'."\" /></p>', 0, '".lang2db(get_lang('Feedback_qn25_true'))."', 0.00, 1, '', '', ''),
		(2, 26, '<p><img hspace=\"0\" height=\"100\" width=\"50\" vspace=\"0\" border=\"0\" align=\"absmiddle\" alt=\"Emission.jpeg\" src=\"".api_get_path('WEB_COURSE_PATH').$courseRepository.'/document/images/diagrams/templates/Emission.jpeg'."\" /></p>', 0, '".lang2db(get_lang('Feedback_qn25_true'))."', 0.00, 2, '', '', ''),
		(3, 26, '<p><img hspace=\"0\" height=\"100\" width=\"50\" vspace=\"0\" border=\"0\" align=\"absmiddle\" alt=\"Ignition.jpeg\" src=\"".api_get_path('WEB_COURSE_PATH').$courseRepository.'/document/images/diagrams/templates/Ignition.jpeg'."\" /></p>', 0, '', 0.00, 3, '', '', ''),
		(4, 26, '<p><img hspace=\"0\" height=\"100\" width=\"50\" vspace=\"0\" border=\"0\" align=\"absmiddle\" alt=\"Induction.jpeg\" src=\"".api_get_path('WEB_COURSE_PATH').$courseRepository.'/document/images/diagrams/templates/Induction.jpeg'."\" /></p>', 0, '', 0.00, 4, '', '', ''),
		(5, 26, '".lang2db(get_lang('langQuizAnswer_25a'))."', 3, '', 5.00, 5, '', '', ''),
		(6, 26, '".lang2db(get_lang('langQuizAnswer_25b'))."', 1, '', 5.00, 6, '', '', ''),
		(7, 26, '".lang2db(get_lang('langQuizAnswer_25c'))."', 4, '', 5.00, 7, '', '', ''),
		(8, 26, '".lang2db(get_lang('langQuizAnswer_25d'))."', 2, '', 5.00, 8, '', '', ''),
		(1, 27, '".lang2db(get_lang('langQuizAnswer_27a'))."', 0, '', 10.00, 1, '42;166|32|38', 'square', ''),
		(2, 27, '".lang2db(get_lang('langQuizAnswer_27b'))."', 0, '', 10.00, 2, '122;283|75|120', 'circle', ''),
		(3, 27, '".lang2db(get_lang('langQuizAnswer_27c'))."', 0, '', 10.00, 3, '116;45|13|55', 'square', ''),
		(4, 27, '".lang2db(get_lang('langQuizAnswer_27d'))."', 0, '', 10.00, 4, '116;152|50|90', 'square', ''),
		(1, 28, '".lang2db(get_lang('langQuizAnswer_28a'))."', 0, '', 10.00, 1, '114;221|27|28', 'square', ''),
		(2, 28, '".lang2db(get_lang('langQuizAnswer_28b'))."', 0, '', 10.00, 2, '164;53|39|18', 'square', ''),
		(3, 28, '".lang2db(get_lang('langQuizAnswer_28c'))."', 0, '', 10.00, 3, '158;87|48|26', 'square', ''),	
		(1, 29, '".lang2db(get_lang('langQuizAnswer_29a'))."', 0, '', 10.00, 1, '203;17|23|30', 'square', ''),	
		(2, 29, '".lang2db(get_lang('langQuizAnswer_29b'))."', 0, '', 10.00, 2, '133;294|59|20', 'square', ''),	
		(3, 29, '".lang2db(get_lang('langQuizAnswer_29c'))."', 0, '', 10.00, 3, '306;184|93|22', 'square', ''),	
		(1, 30, '".lang2db(get_lang('langQuizAnswer_30a'))."', 0, '', 10.00, 1, '37;31|8|13', 'square', ''),	
		(2, 30, '".lang2db(get_lang('langQuizAnswer_30b'))."', 0, '', 10.00, 2, '52;71|9|14', 'square', ''),	
		(3, 30, '".lang2db(get_lang('langQuizAnswer_30c'))."', 0, '', 10.00, 3, '22;98|11|14', 'square', '')",
		__FILE__, __LINE__);

		Database::query("INSERT INTO `".$TABLEQUIZQUESTION . "` (question_id, exercice_id, question_order) VALUES (1, 1, 1),
		(2, 1, 2),
		(3, 1, 3),
		(4, 1, 4),
		(5, 1, 5),
		(6, 1, 6),
		(7, 1, 7),
		(8, 1, 8),
		(9, 1, 9),
		(10, 1, 10),
		(11, 1, 11),
		(12, 1, 12),
		(13, 1, 13),
		(14, 1, 14),
		(15, 1, 15),
		(16, 1, 16),
		(17, 1, 17),
		(18, 1, 18),
		(19, 1, 19),
		(20, 1, 20),
		(21, 1, 21),
		(22, 1, 22),
		(23, 1, 23),
		(24, 1, 24),
		(25, 1, 25),
		(26, 1, 26),
		(27, 1, 27),
		(28, 1, 28),
		(29, 1, 29),
		(30, 1, 30)", __FILE__, __LINE__);	

		/*
		-----------------------------------------------------------
			Forum tool
		-----------------------------------------------------------
		*/
		Database::query("INSERT INTO `$TABLEFORUMCATEGORIES` VALUES (1,'".lang2db(get_lang('ExampleForumCategory'))."', '', 1, 0, 0)", __FILE__, __LINE__);
		$insert_id = Database :: insert_id();
		Database::query("INSERT INTO `".$TABLEITEMPROPERTY . "` (tool,insert_user_id,insert_date,lastedit_date,ref,lastedit_type,lastedit_user_id,to_group_id,to_user_id,visibility) VALUES ('forum_category',1,NOW(),NOW(),$insert_id,'ForumCategoryAdded',1,0,NULL,1)", __FILE__, __LINE__);

		Database::query("INSERT INTO `$TABLEFORUMS` (forum_title, forum_comment, forum_threads,forum_posts,forum_last_post,forum_category, allow_anonymous, allow_edit,allow_attachments, allow_new_threads,default_view,forum_of_group,forum_group_public_private, forum_order,locked,session_id ) VALUES ('".lang2db(get_lang('ExampleForum'))."', '', 0, 0, 0, 1, 0, 1, '0', 1, 'flat','0', 'public', 1, 0,0)", __FILE__, __LINE__);
		$insert_id = Database :: insert_id();
		Database::query("INSERT INTO `".$TABLEITEMPROPERTY . "` (tool,insert_user_id,insert_date,lastedit_date,ref,lastedit_type,lastedit_user_id,to_group_id,to_user_id,visibility) VALUES ('" . TOOL_FORUM . "',1,NOW(),NOW(),$insert_id,'ForumAdded',1,0,NULL,1)", __FILE__, __LINE__);

		Database::query("INSERT INTO `$TABLEFORUMTHREADS` (thread_id, thread_title, forum_id, thread_replies, thread_poster_id, thread_poster_name, thread_views, thread_last_post, thread_date, locked, thread_qualify_max) VALUES (1, '".lang2db(get_lang('ExampleThread'))."', 1, 0, 1, '', 0, 1, NOW(), 0, 10)", __FILE__, __LINE__);
		$insert_id = Database :: insert_id();
		Database::query("INSERT INTO `".$TABLEITEMPROPERTY . "` (tool,insert_user_id,insert_date,lastedit_date,ref,lastedit_type,lastedit_user_id,to_group_id,to_user_id,visibility) VALUES ('forum_thread',1,NOW(),NOW(),$insert_id,'ForumThreadAdded',1,0,NULL,1)", __FILE__, __LINE__);

		Database::query("INSERT INTO `$TABLEFORUMPOSTS` VALUES (1, '".lang2db(get_lang('ExampleThread'))."', '".lang2db(get_lang('ExampleThreadContent'))."', 1, 1, 1, '', NOW(), 0, 0, 1)", __FILE__, __LINE__);

	}

	$language_interface=$language_interface_tmp;

	return 0;
};

/**
 * function string2binary converts the string "true" or "false" to the boolean true false (0 or 1)
 * This is used for the Dokeos Config Settings as these store true or false as string
 * and the api_get_setting('course_create_active_tools') should be 0 or 1 (used for
 * the visibility of the tool)
 * @param string	$variable
 * @author Patrick Cool, patrick.cool@ugent.be
 */
function string2binary($variable)
{
	if($variable == "true")
	{
		return true;
	}
	if($variable == "false")
	{
		return false;
	}
}

/**
 * function register_course to create a record in the course table of the main database
 * @param string	$courseId
 * @param string	$courseCode
 * @param string	$courseRepository
 * @param string	$courseDbName
 * @param string	$tutor_name
 * @param string	$category
 * @param string	$title			complete name of course
 * @param string	$course_language		lang for this course
 * @param string	$uid				uid of owner
 * @param integer	Expiration date in unix time representation
 * @param array		Optional array of teachers' user ID
 * @return	int		0
 */
function register_course($courseSysCode, $courseScreenCode, $courseRepository, $courseDbName, $titular, $category, $title, $course_language, $uidCreator, $expiration_date = "", $teachers=array())
{
	global $defaultVisibilityForANewCourse, $error_msg;
	$TABLECOURSE = Database :: get_main_table(TABLE_MAIN_COURSE);
	$TABLECOURSUSER = Database :: get_main_table(TABLE_MAIN_COURSE_USER);

	$TABLEANNOUNCEMENTS = Database :: get_course_table(TABLE_ANNOUNCEMENT,$courseDbName);

	$okForRegisterCourse = true;

	// Check if I have all
	if (empty($courseSysCode)) {
		$error_msg[] = "courseSysCode is missing";
		$okForRegisterCourse = false;
	}
	if (empty($courseScreenCode)) {
		$error_msg[] = "courseScreenCode is missing";
		$okForRegisterCourse = false;
	}
	if (empty($courseDbName)) {
		$error_msg[] = "courseDbName is missing";
		$okForRegisterCourse = false;
	}
	if (empty($courseRepository)) {
		$error_msg[] = "courseRepository is missing";
		$okForRegisterCourse = false;
	}
	if (empty($titular)) {
		$error_msg[] = "titular is missing";
		$okForRegisterCourse = false;
	}
	if (empty($title)) {
		$error_msg[] = "title is missing";
		$okForRegisterCourse = false;
	}
	if (empty($course_language)) {
		$error_msg[] = "language is missing";
		$okForRegisterCourse = false;
	}

	if (empty($expiration_date)) {
		$expiration_date = "NULL";
	} else {
		$expiration_date = "FROM_UNIXTIME(".$expiration_date . ")";
	}
	if ($okForRegisterCourse) {
		//$titular=addslashes($titular);
		// here we must add 2 fields
		$sql = "INSERT INTO ".$TABLECOURSE . " SET
					code = '".Database :: escape_string($courseSysCode) . "',
					db_name = '".Database :: escape_string($courseDbName) . "',
					directory = '".Database :: escape_string($courseRepository) . "',
					course_language = '".Database :: escape_string($course_language) . "',
					title = '".Database :: escape_string($title) . "',
					description = '".lang2db(get_lang('CourseDescription')) . "',
					category_code = '".Database :: escape_string($category) . "',
					visibility = '1',
					show_score = '',
					disk_quota = '".api_get_setting('default_document_quotum') . "',
					creation_date = now(),
					expiration_date = ".$expiration_date . ",
					last_edit = now(),
					last_visit = NULL,
					tutor_name = '".Database :: escape_string($titular) . "',
					visual_code = '".Database :: escape_string($courseScreenCode) . "'";

		Database::query($sql, __FILE__, __LINE__);

		$sort = api_max_sort_value('0', api_get_user_id());

		require_once (api_get_path(LIBRARY_PATH).'course.lib.php');
		$i_course_sort = CourseManager :: userCourseSort($uidCreator,$courseSysCode);

		$sql = "INSERT INTO ".$TABLECOURSUSER . " SET
					course_code = '".addslashes($courseSysCode) . "',
					user_id = '".Database::escape_string($uidCreator) . "',
					status = '1',
					role = '".lang2db(get_lang('Professor')) . "',
					tutor_id='1',
					sort='". ($i_course_sort) . "',
					user_course_cat='0'";
		Database::query($sql, __FILE__, __LINE__);

		if (count($teachers)>0) {
			foreach ($teachers as $key) {
				$sql = "INSERT INTO ".$TABLECOURSUSER . " SET
					course_code = '".Database::escape_string($courseSysCode) . "',
					user_id = '".Database::escape_string($key) . "',
					status = '1',
					role = '',
					tutor_id='0',
					sort='". ($sort +1) . "',
					user_course_cat='0'";
				Database::query($sql, __FILE__, __LINE__);
			}
		}
		//adding the course to an URL
		global $_configuration;
		require_once (api_get_path(LIBRARY_PATH).'urlmanager.lib.php');
		if ($_configuration['multiple_access_urls']==true) {
			$url_id=1;
			if (api_get_current_access_url_id()!=-1) {
				$url_id=api_get_current_access_url_id();
			}
			UrlManager::add_course_to_url($courseSysCode,$url_id);
		} else {
			UrlManager::add_course_to_url($courseSysCode,1);
		}

		// add event to system log
		$time = time();
		$user_id = api_get_user_id();
		event_system(LOG_COURSE_CREATE, LOG_COURSE_CODE, $courseSysCode, $time, $user_id, $courseSysCode);

		$send_mail_to_admin = api_get_setting('send_email_to_admin_when_create_course');
		
		//@todo improve code to send to all current portal admins
		if ($send_mail_to_admin=='true'){
			$siteName=api_get_setting('siteName');
			$recipient_email = api_get_setting('emailAdministrator');
			$recipient_name = api_get_person_name(api_get_setting('administratorName'), api_get_setting('administratorSurname'));
			$urlsite = api_get_path(WEB_PATH);
			$iname = api_get_setting('Institution');
			$subject=get_lang('NewCourseCreatedIn').' '.$siteName.' - '.$iname;
			$message =  get_lang('Dear').' '.$recipient_name.",\n\n".get_lang('MessageOfNewCourseToAdmin').' '.$siteName.' - '.$iname."\n";
			$message .= get_lang('CourseName').' '.$title."\n";
			$message .= get_lang('Category').' '.$category."\n";
			$message .= get_lang('Tutor').' '.$titular."\n";
			$message .= get_lang('Language').' '.$course_language;
			
			api_mail($recipient_name, $recipient_email, $subject, $message,$siteName,$recipient_email);	
		}

	}
	return 0;
}

/**
*	WARNING: this function always returns true.
*/
function checkArchive($pathToArchive) {
	return TRUE;
}

/**
 * Extract properties of the files from a ZIP package, write them to disk and
 * return them as an array.
 * @param	string	Absolute path to the ZIP file
 * @param	bool	Whether the ZIP file is compressed (not implemented). Defaults to TRUE.
 * @return	array	List of files properties from the ZIP package
 */
function readPropertiesInArchive($archive, $isCompressed = TRUE) {
	include (api_get_path(LIBRARY_PATH) . "pclzip/pclzip.lib.php");
	printVar(dirname($archive), "Zip : ");
	$uid = api_get_user_id();
	/*
	string tempnam ( string dir, string prefix)
	tempnam() creates a unique temporary file in the dir directory. If the
	directory doesn't existm tempnam() will generate a filename in the system's
	temporary directory.
	Before PHP 4.0.6, the behaviour of tempnam() depended of the underlying OS.
	Under Windows, the "TMP" environment variable replaces the dir parameter;
	under Linux, the "TMPDIR" environment variable has priority, while for the
	OSes based on system V R4, the dir parameter will always be used if the
	directory which it represents exists. Consult your documentation for more
	details.
	tempnam() returns the temporary filename, or the string NULL upon failure.
	*/
	$zipFile = new pclZip($archive);
	$tmpDirName = dirname($archive) . "/tmp".$uid.uniqid($uid);
	if (mkpath($tmpDirName)) {
		$unzippingSate = $zipFile->extract($tmpDirName);
	} else {
		die("mkpath failed");
	}
	$pathToArchiveIni = dirname($tmpDirName) . "/archive.ini";
	//	echo $pathToArchiveIni;
	$courseProperties = parse_ini_file($pathToArchiveIni);
	rmdir($tmpDirName);
	return $courseProperties;
}
