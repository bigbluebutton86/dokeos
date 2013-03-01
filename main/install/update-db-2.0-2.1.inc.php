<?php // $Id: $
/* See license terms in /dokeos_license.txt */
/**
==============================================================================
* Update the Dokeos database from an older version
* Notice : This script has to be included by index.php or update_courses.php
*
* @package dokeos.install
* @todo
* - conditional changing of tables. Currently we execute for example
* ALTER TABLE `$dbNameForm`.`cours` instructions without checking wether this is necessary.
* - reorganise code into functions
* @todo use database library
==============================================================================
*/


//load helper functions
require_once("install_upgrade.lib.php");
require_once('../inc/lib/image.lib.php');
$old_file_version = '2.0';
$new_file_version = '2.1';

$error_file = "../install/logs/upgrade-$old_file_version-$new_file_version.sql_errors";
$file_header = '-------------';
$file_header .= "Dokeos upgrade from version $old_file_version to version $new_file_version\n";
$file_header .= "Upgrade made on ".date('l jS \of F Y h:i:s A')."\n";
$file_header .= "-------------\n";

$f = fopen($error_file, 'w');
fwrite($f,$file_header);
fclose($f);

//remove memory and time limits as much as possible as this might be a long process...
if(function_exists('ini_set'))
{
	ini_set('memory_limit',-1);
	ini_set('max_execution_time',0);
}else{
	error_log('Update-db script: could not change memory and time limits',0);
}

/*
==============================================================================
		MAIN CODE
==============================================================================
*/

//check if we come from index.php or update_courses.php - otherwise display error msg
if (defined('DOKEOS_INSTALL') || defined('DOKEOS_COURSE_UPDATE'))
{
	//check if the current Dokeos install is elligible for update
	if (!file_exists('../inc/conf/configuration.php'))
	{
		echo '<b>'.get_lang('Error').' !</b> Dokeos '.implode('|', $updateFromVersion).' '.get_lang('HasNotBeenFound').'.<br><br>
								'.get_lang('PleasGoBackToStep1').'.
							    <p><button type="submit" class="back" name="step1" value="&lt; '.get_lang('Back').'">'.get_lang('Back').'</button></p>
							    </td></tr></table></form></body></html>';

		exit ();
	}

	//get_config_param() comes from install_functions.inc.php and
	//actually gets the param from
	$_configuration['db_glue'] = get_config_param('dbGlu');

	if ($singleDbForm)
	{
		$_configuration['table_prefix'] = get_config_param('courseTablePrefix');
		$_configuration['main_database'] = get_config_param('mainDbName');
		$_configuration['db_prefix'] = get_config_param('dbNamePrefix');
	}

	$dbScormForm = eregi_replace('[^a-z0-9_-]', '', $dbScormForm);

	if (! empty ($dbPrefixForm) && !ereg('^'.$dbPrefixForm, $dbScormForm))
	{
		$dbScormForm = $dbPrefixForm.$dbScormForm;
	}

	if (empty ($dbScormForm) || $dbScormForm == 'mysql' || $dbScormForm == $dbPrefixForm)
	{
		$dbScormForm = $dbPrefixForm.'scorm';
	}
	$res = @mysql_connect($dbHostForm, $dbUsernameForm, $dbPassForm);

	//if error on connection to the database, show error and exit
	if ($res === false)
	{
		//$no = mysql_errno();
		//$msg = mysql_error();

		//echo '<hr>['.$no.'] - '.$msg.'<hr>';
		echo					get_lang('DBServerDoesntWorkOrLoginPassIsWrong').'.<br /><br />' .
				'				'.get_lang('PleaseCheckTheseValues').' :<br /><br />
							    <b>'.get_lang('DBHost').'</b> : '.$dbHostForm.'<br />
								<b>'.get_lang('DBLogin').'</b> : '.$dbUsernameForm.'<br />
								<b>'.get_lang('DBPassword').'</b> : '.$dbPassForm.'<br /><br />
								'.get_lang('PleaseGoBackToStep').' '. (defined('DOKEOS_INSTALL') ? '3' : '1').'.
							    <p><button type="submit" class="back" name="step'. (defined('DOKEOS_INSTALL') ? '3' : '1').'" value="&lt; '.get_lang('Back').'">'.get_lang('Back').'</button></p>
							    </td></tr></table></form></body></html>';

		exit ();
	}

	// The Dokeos system has not been designed to use special SQL modes that were introduced since MySQL 5
	@mysql_query("set session sql_mode='';");

	$dblistres = mysql_list_dbs();
	$dblist = array();
	while ($row = mysql_fetch_object($dblistres)) {
    	$dblist[] = $row->Database;
	}
	/*
	-----------------------------------------------------------
		Normal upgrade procedure:
		start by updating main, statistic, user databases
	-----------------------------------------------------------
	*/
	//if this script has been included by index.php, not update_courses.php, so
	// that we want to change the main databases as well...
	$only_test = false;
	$log = 0;
	if (defined('DOKEOS_INSTALL'))
	{
		if ($singleDbForm)
		{
			$dbStatsForm = $dbNameForm;
			$dbScormForm = $dbNameForm;
			$dbUserForm = $dbNameForm;
		}
		/**
		 * Update the databases "pre" migration
		 */
		include ("../lang/english/create_course.inc.php");

		if ($languageForm != 'english')
		{
			//languageForm has been escaped in index.php
			include ("../lang/$languageForm/create_course.inc.php");
		}

		//get the main queries list (m_q_list)
		$m_q_list = get_sql_file_contents('migrate-db-'.$old_file_version.'-'.$new_file_version.'-pre.sql','main');
		if(count($m_q_list)>0)
		{
			//now use the $m_q_list
			/**
			 * We connect to the right DB first to make sure we can use the queries
			 * without a database name
			 */
			if(strlen($dbNameForm)>40){
				error_log('Database name '.$dbNameForm.' is too long, skipping',0);
			}elseif(!in_array($dbNameForm,$dblist)){
				error_log('Database '.$dbNameForm.' was not found, skipping',0);
			}else{
				mysql_select_db($dbNameForm);
				foreach($m_q_list as $query){
		          if ( strlen(trim($query)) != 0 ) {
		            if($only_test){
		              error_log("mysql_query($dbNameForm,$query)",0);
		            }else{
		              $res = mysql_query($query);
		              if (mysql_errno()) {
		                    //write_error($error_file,'MysqlError : '.mysql_errno().' : '.mysql_error());
		                    //write_error($error_file,"DB : $dbNameForm | Request : $query\n"); 
		              }
		              if($log)
		              {
		                error_log("In $dbNameForm, executed: $query",0);
		              }
		            }
		          }
				}
			}
		}
				
				
			
		
		//get the stats queries list (s_q_list)
		$s_q_list = get_sql_file_contents('migrate-db-'.$old_file_version.'-'.$new_file_version.'-pre.sql','stats');

		if(count($s_q_list)>0)
		{
			//now use the $s_q_list
			/**
			 * We connect to the right DB first to make sure we can use the queries
			 * without a database name
			 */
			if(strlen($dbStatsForm)>40){
				error_log('Database name '.$dbStatsForm.' is too long, skipping',0);
			}elseif(!in_array($dbStatsForm,$dblist)){
				error_log('Database '.$dbStatsForm.' was not found, skipping',0);
			}else{
				mysql_select_db($dbStatsForm);
        foreach($s_q_list as $query){
          if ( strlen(trim($query)) != 0) {
            if($only_test){
              error_log("mysql_query($dbStatsForm,$query)",0);
            }else{
              $res = mysql_query($query);
              if (mysql_errno()) {
                    //write_error($error_file,'MysqlError : '.mysql_errno().' : '.mysql_error());
                    //write_error($error_file,"DB : $dbStatsForm | Request : $query\n"); 
              }
              if($log)
              {
                error_log("In $dbStatsForm, executed: $query",0);
              }
            }
          }
        }
			}
		}
		//get the user queries list (u_q_list)
		$u_q_list = get_sql_file_contents('migrate-db-'.$old_file_version.'-'.$new_file_version.'-pre.sql','user');
		if(count($u_q_list)>0)
		{
			//now use the $u_q_list
			/**
			 * We connect to the right DB first to make sure we can use the queries
			 * without a database name
			 */
			if(strlen($dbUserForm)>40){
				error_log('Database name '.$dbUserForm.' is too long, skipping',0);
			}elseif(!in_array($dbUserForm,$dblist)){
				error_log('Database '.$dbUserForm.' was not found, skipping',0);
			}else{
				mysql_select_db($dbUserForm);
        foreach($u_q_list as $query){
          if ( strlen(trim($query)) == false ) {
            if($only_test){
              error_log("mysql_query($dbUserForm,$query)",0);
              error_log("In $dbUserForm, executed: $query",0);
            }else{
              $res = mysql_query($query);
              if (mysql_errno()) {
                    //write_error($error_file,'MysqlError : '.mysql_errno().' : '.mysql_error());
                    //write_error($error_file,"DB : $dbUserForm | Request : $query\n"); 
              }
            }
          }
        }
			}
		}
		//the SCORM database doesn't need a change in the pre-migrate part - ignore
	}


	/*
	-----------------------------------------------------------
		Update the Dokeos course databases
		this part can be accessed in two ways:
		- from the normal upgrade process
		- from the script update_courses.php,
		which is used to upgrade more than MAX_COURSE_TRANSFER courses

		Every time this script is accessed, only
		MAX_COURSE_TRANSFER courses are upgraded.
	-----------------------------------------------------------
	*/

	$prefix = '';
	if ($singleDbForm)
	{
		$prefix =  get_config_param ('table_prefix');
	}

	//get the courses databases queries list (c_q_list)
	$c_q_list = get_sql_file_contents('migrate-db-'.$old_file_version.'-'.$new_file_version.'-pre.sql','course');

	if(count($c_q_list)>0)
	{
		//get the courses list
		if(strlen($dbNameForm)>40)
		{
			error_log('Database name '.$dbNameForm.' is too long, skipping',0);
		}
		elseif(!in_array($dbNameForm,$dblist))
		{
			error_log('Database '.$dbNameForm.' was not found, skipping',0);
		}
		else
		{
			mysql_select_db($dbNameForm);

                       // Add email templates if does not exists
                       $check_template = Database::query("SELECT id FROM email_template WHERE description='Userregistration' AND language='english'");
                       if (Database::num_rows($check_template) == 0) {
                           Database::query("INSERT INTO email_template VALUES(1, 'User Registration', 'Userregistration', 'emailtemplate.png', 'english', '');");
                       }
                       $check_template = Database::query("SELECT id FROM email_template WHERE description='Quizreport' AND language='english'");
                       if (Database::num_rows($check_template) == 0) {
                           Database::query("INSERT INTO email_template VALUES(2, 'Quiz Report', 'Quizreport', 'emailtemplate.png', 'english', '');");
                       }
                       $check_template = Database::query("SELECT id FROM email_template WHERE description='Userregistration' AND language='french'");
                       if (Database::num_rows($check_template) == 0) {
                           Database::query("INSERT INTO email_template VALUES(3, 'Utilisateurs inscrire', 'Userregistration', 'emailtemplate.png', 'french' ,'');");
                       }
                       $check_template = Database::query("SELECT id FROM email_template WHERE description='Quizreport' AND language='french'");
                       if (Database::num_rows($check_template) == 0) {
                           Database::query("INSERT INTO email_template VALUES(4, 'Quiz suivi', 'Quizreport', 'emailtemplate.png', 'french' ,'');");
                       }
                       $check_template = Database::query("SELECT id FROM email_template WHERE description='Userregistration' AND language='german'");
                       if (Database::num_rows($check_template) == 0) {
                           Database::query("INSERT INTO email_template VALUES(5, 'Nutzer registrieren', 'Userregistration', 'emailtemplate.png', 'german' ,'');");
                       }
                       $check_template = Database::query("SELECT id FROM email_template WHERE description='Quizreport' AND language='german'");
                       if (Database::num_rows($check_template) == 0) {
                           Database::query("INSERT INTO email_template VALUES(6, 'Test statistik', 'Quizreport', 'emailtemplate.png', 'german' ,'');");
                       }
                       $check_template = Database::query("SELECT id FROM email_template WHERE description='Quizsuccess' AND language='english'");
                       if (Database::num_rows($check_template) == 0) {
                           Database::query("INSERT INTO email_template VALUES(7, 'Quiz Success Report', 'Quizsuccess', 'emailtemplate.png', 'english' ,'');");
                       }
                       $check_template = Database::query("SELECT id FROM email_template WHERE description='Quizfailure' AND language='english'");
                       if (Database::num_rows($check_template) == 0) {
                           Database::query("INSERT INTO email_template VALUES(8, 'Quiz Failure Report', 'Quizfailure', 'emailtemplate.png', 'english' ,'');");
                       }
                       $check_template = Database::query("SELECT id FROM email_template WHERE description='Quizsuccess' AND language='french'");
                       if (Database::num_rows($check_template) == 0) {
                           Database::query("INSERT INTO email_template VALUES(9, 'Rapport de reussite Quiz', 'Quizsuccess', 'emailtemplate.png', 'french' ,'');");
                       }
                       $check_template = Database::query("SELECT id FROM email_template WHERE description='Quizfailure' AND language='french'");
                       if (Database::num_rows($check_template) == 0) {
                           Database::query("INSERT INTO email_template VALUES(10, 'Rapport non Quiz', 'Quizfailure', 'emailtemplate.png', 'french' ,'');");
                       }
                       $check_template = Database::query("SELECT id FROM email_template WHERE description='Quizsuccess' AND language='german'");
                       if (Database::num_rows($check_template) == 0) {
                           Database::query("INSERT INTO email_template VALUES(11, 'Quiz Erfolgsbericht', 'Quizsuccess', 'emailtemplate.png', 'german' ,'');");
                       }
                       $check_template = Database::query("SELECT id FROM email_template WHERE description='Quizfailure' AND language='german'");
                       if (Database::num_rows($check_template) == 0) {
                           Database::query("INSERT INTO email_template VALUES(12, 'Quiz Fehler Bericht', 'Quizfailure', 'emailtemplate.png', 'german' ,'');");
                       }
                       $check_template = Database::query("SELECT id FROM email_template WHERE description='Newassignment' AND language='english'");
                       if (Database::num_rows($check_template) == 0) {
                           Database::query("INSERT INTO email_template VALUES(13, 'New Assignment', 'Newassignment', 'emailtemplate.png', 'english', 'Dear {Name} ,<br/><br/>\r\n\r\nCreated New Assignment :  {courseName} <br/>\r\n\r\n{assignmentName} <br/>\r\n\r\n{assignmentDescription} <br/><br/>\r\n\r\nDeadline : {assignmentDeadline} <br/>\r\n\r\nUpload your paper on : {siteName} <br/>\r\n\r\nYours, <br/><br/>\r\n\r\n{authorName} <br/>\r\n');");
                       }
                       $check_template = Database::query("SELECT id FROM email_template WHERE description='Submitwork' AND language='english'");
                       if (Database::num_rows($check_template) == 0) {
                           Database::query("INSERT INTO email_template VALUES(14, 'Submit Work', 'Submitwork', 'emailtemplate.png', 'english', 'Dear {authorName} ,<br/><br/>\r\n\r\n{studentName} has published a paper named <br/>\r\n\r\n{paperName} <br/>\r\n\r\nfor the {assignmentName} - {assignmentDescription}in the course {courseName} <br/> <br/>\r\n\r\nDeadline was : {assignmentDeadline}\r\n<br/>\r\nThe paper was submitted on : {assignmentSentDate} <br/>\r\n\r\nYou can mark, comment and correct this paper on  : {siteName} <br/>\r\n\r\nYours, <br/><br/>\r\n\r\n{administratorSurname} <br/>\r\n');");
                       }
                       $check_template = Database::query("SELECT id FROM email_template WHERE description='Correctwork' AND language='english'");
                       if (Database::num_rows($check_template) == 0) {
                           Database::query("INSERT INTO email_template VALUES(15, 'Correct Work', 'Correctwork', 'emailtemplate.png', 'english', 'Dear {studentName} ,<br/><br/>\r\n\r\nI have corrected your Paper <br/>\r\n\r\n{paperName}  <br/>\r\n\r\nfor the {assignmentName} - {assignmentDescription}in the course{courseName} <br/><br/>\r\n\r\nDeadline was : {assignmentDeadline} <br/>\r\n\r\nThe paper was submitted on : {assignmentSentDate} <br/>\r\n\r\nCheck your mark and /or corrections on : {siteName} <br/>\r\n\r\nYours, <br/><br/>\r\n\r\n{authorName} <br/>\r\n');");
                       }
                       $check_template = Database::query("SELECT id FROM email_template WHERE description='EmailsInCaseOfChequePayment' AND language='french'");
                       if (Database::num_rows($check_template) == 0) {
                           Database::query("INSERT INTO email_template VALUES(16, 'Inscription par chèque', 'EmailsInCaseOfChequePayment', 'emailtemplate.png', 'french', 'Cher (ére) {firstName} {lastName} ,<br/><br/>\r\n\r\nVous êtes inscrit(e) au programme '{Programme}' sur {siteName} {Institution}<br/>\r\n\r\nNOM D''UTILISATEUR : {username}\r\nMOT DE PASSE : {password}<br/><br/>\r\n\r\nComme vous avez payé par chèque, votre compte sera activé dès que votre paiement sera enregistré par nos services. <br/>\r\n\r\n{siteName} vous offre une expérience e-learning authentique avec la possibilité de progresser pas à pas sous la supervision d''un tuteur. Pour en savoir plus : {url}\r\n\r\nMerci de faire confiance à : {Institution}.\r\n\r\nCordialement,\r\n\r\n{siteName}\r\n{administratorSurname}');");
                       }
                       $check_template = Database::query("SELECT id FROM email_template WHERE description='EmailsInCaseOfChequePayment' AND language='english'");
                       if (Database::num_rows($check_template) == 0) {
                           Database::query("INSERT INTO email_template VALUES(17, 'User registration with cheque payment', 'EmailsInCaseOfChequePayment', 'emailtemplate.png', 'english', 'Dear {firstName} {lastName} ,<br/><br/>\n\nYou are registered to the {Programme} Programme on {siteName} {Institution}<br/>\n\nLOGIN : {username}\nPASSWORD : {password}<br/><br/>\n\nAs you paid by cheque, your account will be activated once we validate your payment. <br/>\n\n{siteName} offers you a true e-learning experience with the posibilty to progress step by step in your learning process under the supervision of a tutor that is dedicated to your support. For more details : {url}\n\nThank you for trusting {Institution}.\n\nYours,\n\n{siteName}\n{administratorSurname}');");
                       }
                       $check_template = Database::query("SELECT id FROM email_template WHERE description='UserRegistrationToSession' AND language='french'");
                       if (Database::num_rows($check_template) == 0) {
                           Database::query("INSERT INTO email_template VALUES(18, 'Inscription à une session', 'UserRegistrationToSession', 'emailtemplate.png', 'french', 'Cher(ère) {administratorname} ,<br/><br/>\r\n\r\nL''étudiant {firstName} {lastName} ,<br/><br/>\r\n\r\na été inscrit au programme '{Programme}' sur{siteName} {Institution}<br/>\r\n\r\nNOM D''UTILISATEUR : {username}\r\n\r\nVous pouvez maintenant vérifier si cet étudiant a un tuteur dans chacun de ses cours en allant à {sessionList}\r\n\r\n\r\nCordialement,\r\n\r\n{siteName}\r\n{administratorSurname}');");
                       }
                       $check_template = Database::query("SELECT id FROM email_template WHERE description='NewGroup' AND language='english'");
                       if (Database::num_rows($check_template) == 0) {
                           Database::query("INSERT INTO email_template VALUES(19, 'New Group', 'NewGroup', 'emailtemplate.png', 'english', 'Dear {adminName} ,<br/><br/>\r\n\r\nNew Group created automatically to give space to new user <br/><br/>\r\n\r\nGroup : {groupName} <br/><br/>\r\n\r\nSeats :  {maxStudent} <br/><br/>\r\n\r\nIn course : {courseName} <br/><br/>\r\n\r\nYours, <br/><br/>\r\n\r\n{authorName} <br/><br/>\r\n');");
                       }
                       $check_template = Database::query("SELECT id FROM email_template WHERE description='Newassignment' AND language='french'");
                       if (Database::num_rows($check_template) == 0) {
                           Database::query("INSERT INTO email_template VALUES(20, 'Nouveau devoir', 'Newassignment', 'emailtemplate.png', 'french', 'Cher(ère) {Name} ,<br/><br/>\r\n\r\nUn nouveau devoir a été créé dans le cours :  {courseName} <br/>\r\n\r\n{assignmentName} <br/>\r\n\r\n{assignmentDescription} <br/><br/>\r\n\r\nEchéance : {assignmentDeadline} <br/>\r\n\r\nRemettez votre travail sur : {siteName} <br/>\r\n\r\nCordialement,, <br/><br/>\r\n\r\n{authorName} <br/>\r\n');");
                       }

                       $check_template = Database::query("SELECT id FROM email_template WHERE description='Submitwork' AND language='french'");
                       if (Database::num_rows($check_template) == 0) {
                           Database::query("INSERT INTO email_template VALUES(21, 'Travail publié', 'Submitwork', 'emailtemplate.png', 'french', 'Cher(ère) {authorName} ,<br/><br/>\r\n\r\n{studentName} a publié un travail intitulé <br/>\r\n\r\n{paperName} <br/>\r\n\r\npour le devoir {assignmentName} - {assignmentDescription} dans le cours  {courseName} <br/> <br/>\r\n\r\nL''échéance était : {assignmentDeadline}\r\n<br/>\r\nLe travail a été remis le : {assignmentSentDate} <br/>\r\n\r\nVous pouvez noter, commenter et corriger ce travail sur : {siteName} <br/>\r\n\r\nCordialement, <br/><br/>\r\n\r\n{administratorSurname} <br/>\r\n');");
                       }
                       $check_template = Database::query("SELECT id FROM email_template WHERE description='Correctwork' AND language='french'");
                       if (Database::num_rows($check_template) == 0) {
                           Database::query("INSERT INTO email_template VALUES(22, 'Travail corrigé', 'Correctwork', 'emailtemplate.png', 'french', 'Cher(ère) {studentName} ,<br/><br/>\r\n\r\nJ''ai corrigé votre travail :<br/>\r\n\r\n{paperName}  <br/>\r\n\r\npour le devoir {assignmentName} - {assignmentDescription} dans le cours {courseName} <br/><br/>\r\n\r\nL''échéance était : {assignmentDeadline} <br/>\r\n\r\nLe travail a été remis le : {assignmentSentDate} <br/>\r\n\r\nConsultez vos points et/ou remarques et/ou correction sur : {siteName} <br/>\r\n\r\nCordialement,, <br/><br/>\r\n\r\n{authorName} <br/>\r\n');");
                       }
                       $check_template = Database::query("SELECT id FROM email_template WHERE description='NewGroup' AND language='french'");
                       if (Database::num_rows($check_template) == 0) {
                           Database::query("INSERT INTO email_template VALUES(23, 'Nouveau groupe', 'NewGroup', 'emailtemplate.png', 'french', 'Cher(ère) {adminName} ,<br/><br/>\r\n\r\nUn nouvau groupe a été créé automatiuement pour accueillir de nouveaux étudiants.<br/><br/>\r\n\r\nGroupe : {groupName} <br/><br/>\r\n\r\nPlaces :  {maxStudent} <br/><br/>\r\n\r\nDans le cours : {courseName} <br/><br/>\r\n\r\nCordialement, <br/><br/>\r\n\r\n{authorName} <br/><br/>\r\n');");
                       }
                       $check_template = Database::query("SELECT id FROM email_template WHERE description='EmailsRegistrationInCaseCreditCardOrInstallment' AND language='french'");
                       if (Database::num_rows($check_template) == 0) {
                           Database::query("INSERT INTO email_template VALUES(24, 'Inscription carte ou 3 fois', 'EmailsRegistrationInCaseCreditCardOrInstallment', 'emailtemplate.png', 'french', 'Cher(ère) {firstName} {lastName},\r\n\r\nVous êtes inscrit(e) au programme '{Programme}' sur le portail {siteName}\r\n\r\nNOM D''UTILISATEUR : {username} \r\nMOT DE PASSE : {password} \r\n\r\nEn cas de problème, veuillez nous contacter.\r\n\r\nCordialement,\r\n\r\nL''équipe DILA\r\n29, quai Voltaire 75007 Paris\r\nTéléphone : 01.40.15.70.00\r\n');");
                       }
                       $check_template = Database::query("SELECT id FROM email_template WHERE description='EmailsRegistrationInCaseCreditCardOrInstallment' AND language='english'");
                       if (Database::num_rows($check_template) == 0) {
                           Database::query("INSERT INTO email_template VALUES(25, 'User Registration with credit card or 3 installment payment', 'EmailsRegistrationInCaseCreditCardOrInstallment', 'emailtemplate.png', 'english', 'Dear {firstName} {lastName},\r\n\r\nYou are registered to the {Programme} Programme on {siteName} {Institution} portal {InstitutionUrl}.\r\nLOGIN : {username}\r\nPASSWORD : {password}\r\n\r\n{siteName} offers you a true e-learning experience with the posibilty to progress step by step in your learning process under the supervision of a tutor that is dedicated to your support. For more details : {detailsUrl}.\r\n\r\nThank you for trusting {Institution}.\r\n\r\nYours,\r\n\r\n{siteName}\r\n\r\n{administratorSurname}');");
                       }
                        
                        
			$res = mysql_query("SELECT code,db_name,directory,course_language FROM course WHERE target_course_code IS NULL ORDER BY code");

			if($res===false){die('Error while querying the courses list in update_db.inc.php');}

			if(mysql_num_rows($res)>0)
			{
				$i=0;
                $list = array();
				//while( ($i < MAX_COURSE_TRANSFER) && ($row = mysql_fetch_array($res)))
				while($row = mysql_fetch_array($res))
				{
					$list[] = $row;
					$i++;
				}
				foreach($list as $row_course)
				{
					//now use the $c_q_list
					/**
					 * We connect to the right DB first to make sure we can use the queries
					 * without a database name
					 */
					if (!$singleDbForm) //otherwise just use the main one
					{
						mysql_select_db($row_course['db_name']);
					}

					foreach($c_q_list as $query)
          {
            if ( strlen(trim($query)) != 0 ) {
              if ($singleDbForm) //otherwise just use the main one
              {
                $query = preg_replace('/^(UPDATE|ALTER TABLE|CREATE TABLE|DROP TABLE|INSERT INTO|DELETE FROM)\s+(\w*)(.*)$/',"$1 $prefix{$row_course['db_name']}_$2$3",$query);
              }

              if($only_test)
              {
                error_log("mysql_query(".$row_course['db_name'].",$query)",0);
              }
              else
              {
                $res = mysql_query($query);
                if (mysql_errno()) {
                    //write_error($error_file,'MysqlError : '.mysql_errno().' : '.mysql_error());
                    //write_error($error_file,"DB : ".$row_course['db_name']." | "."Request : $query\n"); 
                }
                if($log)
                {
                  error_log("In ".$row_course['db_name'].", executed: $query",0);
                }
              }

            }					
          }
					
              // give a negative weighting to old multiple answers
              $table_questions = 'quiz_question';
              $table_answers = 'quiz_answer';
              $sql = 'SELECT * FROM '.$table_questions.' WHERE type = 2';
              $rsQuestions = Database::query($sql, __FILE__, __LINE__);
              while ($question = Database::fetch_array($rsQuestions)) {
                    $sql = 'SELECT max(ponderation) FROM '.$table_answers.' WHERE question_id = '.$question['id'];
                    $rsMax = Database::query($sql, __FILE__, __LINE__);
                    $max = Database::result($rsMax, 0, 0);
                    $sql = 'UPDATE '.$table_answers.' 
                                    SET ponderation = '.(-$max).' 
                                    WHERE question_id = '.$question['id'].'
                                    AND correct = 0'; // weighting ?
                    Database::query($sql, __FILE__, __LINE__);
              }
                            
              // create folders for documents in courses
              $new_folders = array('animations', 'certificates', 'mascot', 'mindmaps', 'photos', 'podcasts', 'screencasts', 'themes', 'css');
              $course_path = api_get_path(SYS_COURSE_PATH).$row_course['directory'].'/document/';              
              foreach ($new_folders as $folder) {
                  if (!is_dir($course_path.$folder)) {
                      if (mkdir($course_path.$folder)) {
                          // insert information in document table
                          $check = Database::query("SELECT id FROM document WHERE path = '/".$folder."'");
                          if (Database::num_rows($check) == 0) {
                              Database::query("INSERT INTO document SET path = '/".$folder."', title = '".ucfirst($folder)."', filetype = 'folder';");                          
                              $doc_id = Database::insert_id();
                              $course_info['dbName'] = '';
                              api_item_property_update($course_info, TOOL_DOCUMENT, $doc_id, 'FolderCreated', 1);
                          }
                      }
                  }                  
              }
              
              // copy templates.css inside course document css folder
              $css_name = api_get_setting('stylesheets');
              if (!file_exists($course_path.'css/templates.css')) {
                  if(file_exists(api_get_path(SYS_PATH).'main/css/'.$css_name.'/templates.css')) {
                    $template_content = str_replace('../../img/', api_get_path(REL_CODE_PATH).'img/', file_get_contents(api_get_path(SYS_PATH).'main/css/'.$css_name.'/templates.css'));
                    $template_content = str_replace('images/', api_get_path(REL_CODE_PATH).'css/'.$css_name.'/images/', $template_content);            
                    file_put_contents($course_path.'css/templates.css', $template_content);
                  }
              }
              
              // we add dropbox tool if doesn't exist in the table
              $check_tool = Database::query("SELECT id FROM tool WHERE name='dropbox'");
              if (Database::num_rows($check_tool) == 0) {
                  Database::query("INSERT INTO tool SET 
                                    name='dropbox', 
                                    link='dropbox/index.php', 
                                    image='dropbox.png', 
                                    visibility='1', 
                                    admin='0', 
                                    address='squaregrey.gif',
                                    added_tool = '0',
                                    target = '_self',
                                    category = 'interaction';
                                 ");
              }
              
              
										
   				}
			}
		}
	}
}
else
{
	echo 'You are not allowed here !';
}
