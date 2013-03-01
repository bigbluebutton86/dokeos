<?php
/* For licensing terms, see /dokeos_license.txt */

/**
  ==============================================================================
 * 	Exercise list: This script shows the list of exercises for administrators and students.
 * 	@package dokeos.exercise
  ==============================================================================
 */
// Language files that should be included
//$language_file = 'exercice';
$language_file = array('exercice','admin');

// setting the help
$help_content = 'exerciselist';

// including the global library
require_once '../inc/global.inc.php';

// including additional libraries
require_once '../gradebook/lib/be.inc.php';

// setting the tabs
$this_section = SECTION_COURSES;

// access control
api_protect_course_script(true);

$show = (isset($_GET['show']) && $_GET['show'] == 'result') ? 'result' : 'test'; // moved down to fix bug: http://www.dokeos.com/forum/viewtopic.php?p=18609#18609
// including additional libraries
require_once 'exercise.class.php';
require_once 'question.class.php';
require_once 'answer.class.php';
require_once api_get_path(LIBRARY_PATH) . 'fileManage.lib.php';
require_once api_get_path(LIBRARY_PATH) . 'fileUpload.lib.php';
require_once 'hotpotatoes.lib.php';
require_once api_get_path(LIBRARY_PATH) . 'document.lib.php';
require_once api_get_path(LIBRARY_PATH) . 'mail.lib.inc.php';
require_once api_get_path(LIBRARY_PATH) . 'usermanager.lib.php';

if (isset($_REQUEST['quizpopup']) && $_REQUEST['quizpopup'] == 1) {
  $htmlHeadXtra[] = '<script type="text/javascript" src="'.api_get_path(WEB_LIBRARY_PATH).'javascript/jquery-1.4.2.min.js" language="javascript"></script>';
  $htmlHeadXtra[] = '<script src="'.api_get_path(WEB_LIBRARY_PATH).'javascript/jquery.ui.all.js" type="text/javascript" language="javascript"></script>';
}
// Add the lp_id parameter to all links if the lp_id is defined in the uri
if (isset($_GET['lp_id']) && $_GET['lp_id'] > 0) {
  $lp_id = Security::remove_XSS($_GET['lp_id']);
 $htmlHeadXtra[] = '<script>
    $(document).ready(function (){
      $("a[href]").attr("href", function(index, href) {
          var param = "lp_id=' . $lp_id . '";
           var is_javascript_link = false;
           var info = href.split("javascript");

           if (info.length >= 2) {
             is_javascript_link = true;
           }
           if ($(this).attr("class") == "course_main_home_button" || $(this).attr("class") == "course_menu_button"  || $(this).attr("class") == "next_button"  || $(this).attr("class") == "prev_button" || is_javascript_link) {
             return href;
           } else {
             if (href.charAt(href.length - 1) === "?")
                 return href + param;
             else if (href.indexOf("?") > 0)
                 return href + "&" + param;
             else
                 return href + "?" + param;
           }
      });
    });
  </script>';
}
// variable initialisation
$is_allowedToEdit = api_is_allowed_to_edit();
$is_tutor = api_is_allowed_to_edit(true);
$is_tutor_course = api_is_course_tutor();
$tbl_course_rel_user = Database :: get_main_table(TABLE_MAIN_COURSE_USER);
$TBL_USER = Database :: get_main_table(TABLE_MAIN_USER);
$TBL_DOCUMENT = Database :: get_course_table(TABLE_DOCUMENT);
$TBL_ITEM_PROPERTY = Database :: get_course_table(TABLE_ITEM_PROPERTY);
$TBL_EXERCICE_ANSWER = Database :: get_course_table(TABLE_QUIZ_ANSWER);
$TBL_EXERCICE_QUESTION = Database :: get_course_table(TABLE_QUIZ_TEST_QUESTION);
$TBL_EXERCICES = Database :: get_course_table(TABLE_QUIZ_TEST);
$TBL_QUESTIONS = Database :: get_course_table(TABLE_QUIZ_QUESTION);
$TBL_TRACK_EXERCICES = Database :: get_statistic_table(TABLE_STATISTIC_TRACK_E_EXERCICES);
$TBL_TRACK_HOTPOTATOES = Database :: get_statistic_table(TABLE_STATISTIC_TRACK_E_HOTPOTATOES);
$TBL_TRACK_ATTEMPT = Database :: get_statistic_table(TABLE_STATISTIC_TRACK_E_ATTEMPT);
$TBL_TRACK_ATTEMPT_RECORDING = Database :: get_statistic_table(TABLE_STATISTIC_TRACK_E_ATTEMPT_RECORDING);
$TBL_LP_ITEM_VIEW = Database :: get_course_table(TABLE_LP_ITEM_VIEW);
$TBL_LP_ITEM = Database :: get_course_table(TABLE_LP_ITEM);
$TBL_LP_VIEW = Database :: get_course_table(TABLE_LP_VIEW);


// document path
$documentPath = api_get_path(SYS_COURSE_PATH) . $_course['path'] . "/document";
// picture path
$picturePath = $documentPath . '/images';
// audio path
$audioPath = $documentPath . '/audio';

// hotpotatoes
$uploadPath = DIR_HOTPOTATOES; //defined in main_api
$exercicePath = api_get_self();
$exfile = explode('/', $exercicePath);
$exfile = strtolower($exfile[sizeof($exfile) - 1]);
$exercicePath = substr($exercicePath, 0, strpos($exercicePath, $exfile));
$exercicePath = $exercicePath . "exercice.php";

// maximum number of exercises on a same page
$limitExPage = 50;

// Clear the exercise session
if (isset($_SESSION['objExercise'])) {
 api_session_unregister('objExercise');
}
if (isset($_SESSION['objQuestion'])) {
 api_session_unregister('objQuestion');
}
if (isset($_SESSION['objAnswer'])) {
 api_session_unregister('objAnswer');
}
if (isset($_SESSION['questionList'])) {
 api_session_unregister('questionList');
}
if (isset($_SESSION['exerciseResult'])) {
 api_session_unregister('exerciseResult');
}
if(isset($_POST['successcontent']))
{
	$success_mailcontent = $_POST['successcontent'];
}
if(isset($_POST['failurecontent']))
{
	$failure_mailcontent = $_POST['failurecontent'];
}
if(isset($_POST['quizstatus']))
{
	$quizstatus = $_POST['quizstatus'];
}
if(isset($_POST['notes']))
{
	$notes = $_POST['notes'];
}

//general POST/GET/SESSION/COOKIES parameters recovery
if (empty($origin)) {
 $origin = Security::remove_XSS($_REQUEST['origin']);
}
if (empty($choice)) {
 $choice = $_REQUEST['choice'];
}
if (empty($hpchoice)) {
 $hpchoice = $_REQUEST['hpchoice'];
}
if (empty($exerciseId)) {
 $exerciseId = Security::remove_XSS($_REQUEST['exerciseId']);
}
if (empty($file)) {
 $file = $_REQUEST['file'];
}

if (isset($_SESSION['fromTpl'])) {
 $_SESSION['fromTpl'] = '';
}
if (isset($_SESSION['editQn'])) {
 $_SESSION['editQn'] = '';
}
$learnpath_id = Security::remove_XSS($_REQUEST['learnpath_id']);
$learnpath_item_id = Security::remove_XSS($_REQUEST['learnpath_item_id']);
$page = Security::remove_XSS($_REQUEST['page']);

if ($origin == 'learnpath') {
 $show = 'result';
}

// redirect to exercise reporting page
if ($show == 'result') {
    header('Location: '.api_get_path(WEB_CODE_PATH).'exercice/exercice_reporting.php?'.api_get_cidreq());
    exit;
}



if ($_GET['delete'] == 'delete' && ($is_allowedToEdit || api_is_coach()) && !empty($_GET['did']) && $_GET['did'] == strval(intval($_GET['did']))) {
 $sql = 'DELETE FROM ' . Database :: get_statistic_table(TABLE_STATISTIC_TRACK_E_EXERCICES) . ' WHERE exe_id = ' . Database::escape_string(Security::Remove_XSS($_GET['did'])); //_GET[did] filtered by entry condition
 api_sql_query($sql, __FILE__, __LINE__);
 $filter = Security::remove_XSS($_GET['filter']);
 header('Location: exercice.php?cidReq=' . Security::remove_XSS($_GET['cidReq']) . '&show=result&filter=' . $filter . '');
 exit;
}

if ($_REQUEST['comments'] == 'update' && ($is_allowedToEdit || $is_tutor) && $_GET['exeid'] == strval(intval($_GET['exeid']))) {
 $id = $_GET['exeid']; //filtered by post-condition
 $emailid = Security::remove_XSS($_GET['emailid']);
 $test = Security::remove_XSS($_GET['test']);
 $from = $_SESSION['_user']['mail'];
 $from_name = $_SESSION['_user']['firstName'] . " " . $_SESSION['_user']['lastName'];
 $url = api_get_path(WEB_CODE_PATH) . 'exercice/exercice.php?' . api_get_cidreq() . '&show=result';
 $TBL_RECORDING = Database :: get_statistic_table('track_e_attempt_recording');
 $total_weighting = Security::remove_XSS($_REQUEST['totalWeighting']);

 $my_post_info = array();
 $post_content_id = array();
 $comments_exist = false;
 foreach ($_POST as $key_index => $key_value) {
  $my_post_info = explode('_', $key_index);
  $post_content_id[] = $my_post_info[1];
  if ($my_post_info[0] == 'comments') {
   $comments_exist = true;
  }
 }

 $loop_in_track = ($comments_exist === true) ? (count($_POST) / 2) : count($_POST);
 $array_content_id_exe = array();
 if ($comments_exist === true) {
  $array_content_id_exe = array_slice($post_content_id, $loop_in_track);
 } else {
  $array_content_id_exe = $post_content_id;
 }

 for ($i = 0; $i < $loop_in_track; $i++) {

  $my_marks = $_POST['marks_' . $array_content_id_exe[$i]];
  $contain_comments = $_POST['comments_' . $array_content_id_exe[$i]];

  if (isset($contain_comments)) {
   $my_comments = $_POST['comments_' . $array_content_id_exe[$i]];
  } else {
   $my_comments = '';
  }
  $my_questionid = $array_content_id_exe[$i];
  $sql = "SELECT question from $TBL_QUESTIONS WHERE id = '" . Database::escape_string($my_questionid) . "'";
  $result = api_sql_query($sql, __FILE__, __LINE__);
  $ques_name = Database::result($result, 0, "question");

  $query = "UPDATE $TBL_TRACK_ATTEMPT SET marks = '" . Database::escape_string($my_marks) . "',teacher_comment = '" . Database::escape_string($my_comments) . "'
					  WHERE question_id = '" . Database::escape_string($my_questionid) . "'
					  AND exe_id='" . Database::escape_string($id) . "'";
  api_sql_query($query, __FILE__, __LINE__);


  $qry = 'SELECT sum(marks) as tot
					FROM ' . $TBL_TRACK_ATTEMPT . ' WHERE exe_id = ' . Database::escape_string(intval($id)) . '
					GROUP BY question_id';

  $res = api_sql_query($qry, __FILE__, __LINE__);
  $tot = Database::result($res, 0, 'tot');
  //updating also the total weight
  $totquery = "UPDATE $TBL_TRACK_EXERCICES SET exe_result = '" . Database::escape_string($tot) . "', exe_weighting = '" . Database::escape_string($total_weighting) . "'
						 WHERE exe_Id='" . Database::escape_string($id) . "'";
  api_sql_query($totquery, __FILE__, __LINE__);
  $recording_changes = "INSERT INTO " . $TBL_RECORDING . "
							(exe_id, question_id, marks, insert_date, author, teacher_comment)
						VALUES (
							'" . Database::escape_string($id) . "',
							'" . Database::escape_string($my_questionid) . "',
							'" . Database::escape_string($my_marks) . "',
							'" . date('Y-m-d H:i:s') . "',
							'" . api_get_user_id() . "',
							'" . Database::escape_string($my_comments) . "')";
  api_sql_query($recording_changes, __FILE__, __LINE__);
 }
 $post_content_id = array();
 $array_content_id_exe = array();
 /* foreach ($_POST as $key => $v) {
   $keyexp = explode('_', $key);

   $id = $id;
   $v = $v;
   $my_questionid = $keyexp[1];

   if ($keyexp[0] == "marks") {
   $sql = "SELECT question from $TBL_QUESTIONS WHERE id = '".Database::escape_string($my_questionid)."'";
   $result = api_sql_query($sql, __FILE__, __LINE__);
   $ques_name = Database :: result($result, 0, "question");

   $query = "UPDATE $TBL_TRACK_ATTEMPT SET marks = '" . $v . "'
   WHERE question_id = '" . Database::escape_string($my_questionid) . "'
   AND exe_id='" . Database::escape_string($id) . "'";
   api_sql_query($query, __FILE__, __LINE__);

   $qry = 'SELECT sum(marks) as tot
   FROM ' . $TBL_TRACK_ATTEMPT . ' WHERE exe_id = ' . Database::escape_string(intval($id)) . '
   GROUP BY question_id';

   $res = api_sql_query($qry, __FILE__, __LINE__);
   $tot = Database :: result($res, 0, 'tot');
   //updating also the total weight
   $totquery = "UPDATE $TBL_TRACK_EXERCICES SET exe_result = '" . Database :: escape_string($tot) . "', exe_weighting = '" . Database :: escape_string($total_weighting) . "'
   WHERE exe_Id='" . Database :: escape_string($id) . "'";

   api_sql_query($totquery, __FILE__, __LINE__);

   $recording_changes = "INSERT INTO " . $TBL_RECORDING . "(exe_id, question_id, marks, insert_date, author)
   VALUES (
   '" . Database::escape_string($id). "',
   '" . Database::escape_string($my_questionid) . "',
   '" . Database::escape_string($v) ."',
   '" . date('Y-m-d H:i:s') . "',
   '" . api_get_user_id() . "')";
   api_sql_query($recording_changes, __FILE__, __LINE__);
   } else {
   $query = "UPDATE $TBL_TRACK_ATTEMPT SET teacher_comment = '" . Database::escape_string($v) . "'
   WHERE question_id = '" . Database::escape_string($my_questionid) . "'
   AND exe_id = '" . Database::escape_string($id) . "')";
   api_sql_query($query, __FILE__, __LINE__);

   $recording_changes = "INSERT INTO " . $TBL_RECORDING . " (exe_id, question_id, teacher_comment, insert_date, author)
   VALUES (
   '" . Database::escape_string($id) ."',
   '" . Database::escape_string($my_questionid) . "',
   '" . Database::escape_string($v) . "',
   '" . date('Y-m-d H:i:s') . "',
   '" . api_get_user_id() . "')";
   api_sql_query($recording_changes, __FILE__, __LINE__);
   }
   } */

 $qry = 'SELECT DISTINCT question_id, marks
			FROM ' . $TBL_TRACK_ATTEMPT . ' as attempts 
			INNER JOIN '.$TBL_QUESTIONS.' as questions
				ON questions.id = attempts.question_id
			where exe_id = ' . Database::escape_string(intval($id)) . '
			GROUP BY question_id';

 $res = api_sql_query($qry, __FILE__, __LINE__);
 $tot = 0;
 while ($row = Database :: fetch_array($res, 'ASSOC')) {
  $tot += $row['marks'];
 }

 $totquery = "UPDATE $TBL_TRACK_EXERCICES SET exe_result = '" . Database :: escape_string($tot) . "' WHERE exe_Id='" . Database :: escape_string($id) . "'";

 //search items
 if (isset($_POST['my_exe_exo_id']) && isset($_POST['student_id'])) {
  $sql_lp = 'SELECT li.id as lp_item_id,li.lp_id,li.item_type,li.path,liv.id AS lp_view_id,liv.user_id,max(liv.view_count) AS view_count FROM ' . $TBL_LP_ITEM . ' li
		INNER JOIN ' . $TBL_LP_VIEW . ' liv ON li.lp_id=liv.lp_id WHERE li.path="' . Database::escape_string(Security::remove_XSS($_POST['my_exe_exo_id'])) . '" AND li.item_type="quiz" AND user_id="' . Database::escape_string(Security::remove_XSS($_POST['student_id'])) . '" ';
  $rs_lp = Database::query($sql_lp, __FILE__, __LINE__);
  if (!($rs_lp === false)) {
   $row_lp = Database::fetch_array($rs_lp);
   //update score in learnig path
   $sql_lp_view = 'UPDATE ' . $TBL_LP_ITEM_VIEW . ' liv SET score ="' . Database::escape_string($tot) . '" WHERE liv.lp_item_id="' . Database::escape_string((int) $row_lp['lp_item_id']) . '" AND liv.lp_view_id="' . Database::escape_string((int) $row_lp['lp_view_id']) . '" AND liv.view_count="' . Database::escape_string((int) $row_lp['view_count']) . '" ;';
   $rs_lp_view = Database::query($sql_lp_view, __FILE__, __LINE__);
  }
 }
 Database::query($totquery, __FILE__, __LINE__);

global $language_interface;
$subject = get_lang('ExamSheetVCC');
if($quizstatus == 'success')
{
	$description = "Quizsuccess";
	$mailcontent = $successcontent;
}
else
{
	$description = "Quizfailure";
	$mailcontent = $failurecontent;
}

if(empty($mailcontent)){
$table_emailtemplate 	= Database::get_main_table(TABLE_MAIN_EMAILTEMPLATES);
$sql = "SELECT * FROM $table_emailtemplate WHERE description = '".$description."' AND language= '".$language_interface."'";
$result = api_sql_query($sql, __FILE__, __LINE__);
while($row = Database::fetch_array($result))
{				
	$content = $row['content'];
}
if(empty($content))
{
	$content = get_lang('DearStudentEmailIntroduction')."\n\n";
	$content .= get_lang('AttemptVCC')."\n\n";
	if($quizstatus == 'success'){
	$content .= get_lang('Quizsuccess')."\n\n";
	}
	else {
	$content .= get_lang('Quizfailure')."\n\n";
	}
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
}
else {
	$content = $mailcontent;
}
/* $htmlmessage = '<html>' .
         '<head>' .
         '</head>' .
         '<body>' .
         '<div>' .
         '  <p>' . get_lang('DearStudentEmailIntroduction') . '</p>' .
         '  <p> ' . get_lang('AttemptVCC') . ' </p>' .
         '  <table width="417">' .
         '    <tr>' .
         '      <td width="229" valign="top" bgcolor="E5EDF8">&nbsp;&nbsp;<span>' . get_lang('Question') . '</span></td>' .
         '      <td width="469" valign="top" bgcolor="#F3F3F3"><span>#ques_name#</span></td>' .
         '    </tr>' .
         '    <tr>' .
         '      <td width="229" valign="top" bgcolor="E5EDF8">&nbsp;&nbsp;<span>' . get_lang('Exercice') . '</span></td>' .
         '       <td width="469" valign="top" bgcolor="#F3F3F3"><span>#test#</span></td>' .
         '    </tr>' .
         '  </table>' .
         '  <p>' . get_lang('ClickLinkToViewComment') . ' <a href="#url#">#url#</a><br />' .
         '    <br />' .
         '  ' . get_lang('Regards') . ' </p>' .
         '  </div>' .
         '  </body>' .
         '  </html>';
 $message = '<p>' . sprintf(get_lang('AttemptVCCLong'), Security::remove_XSS($test)) . ' <A href="#url#">#url#</A></p><br />';
 $mess = str_replace("#test#", Security::remove_XSS($test), $message);
 //$message= str_replace("#ques_name#",$ques_name,$mess);
 $message = str_replace("#url#", $url, $mess);
 $mess = $message;*/
 $content = str_replace("{ques_name}", $ques_name, $content);
 $content = str_replace("{test}", Security::remove_XSS($test), $content);
 $content = str_replace("{notes}", Security::remove_XSS($notes), $content);
 $content = str_replace("{url}", '<a href="'.$url.'">'.$url.'</a>', $content);
 $content = str_replace('{administratorSurname}',api_get_person_name(api_get_setting('administratorName'), api_get_setting('administratorSurname')), $content); 
 $content = str_replace('{administratorTelephone}',api_get_setting('administratorTelephone'), $content); 
 $content = str_replace('{emailAdministrator}',api_get_setting('emailAdministrator'), $content);
 $mess = $content;
 $headers = " MIME-Version: 1.0 \r\n";
 $headers .= "User-Agent: Dokeos/2.0";
 $headers .= "Content-Transfer-Encoding: 7bit";
 $headers .= 'From: ' . $from_name . ' <' . $from . '>' . "\r\n";
 $headers = "From:$from_name\r\nReply-to: $to\r\nContent-type: text/html; charset=" . ($charset ? $charset : 'ISO-8859-15');
 //mail($emailid, $subject, $mess,$headers);

 api_mail_html($emailid, $emailid, $subject, $mess, $from_name, $from);
 if (in_array($origin, array(
             'tracking_course',
             'user_course'
         ))) {
  if (isset($_POST['lp_item_id']) && isset($_POST['lp_item_view_id']) && isset($_POST['student_id']) && isset($_POST['total_score']) && isset($_POST['total_time']) && isset($_POST['totalWeighting'])) {
   $lp_item_id = $_POST['lp_item_id'];
   $lp_item_view_id = $_POST['lp_item_view_id'];
   $student_id = $_POST['student_id'];
   $totalWeighting = $_POST['totalWeighting'];

   if ($lp_item_id == strval(intval($lp_item_id)) && $lp_item_view_id == strval(intval($lp_item_view_id)) && $student_id == strval(intval($student_id))) {
    $score = $_POST['total_score'];
    $total_time = $_POST['total_time'];
    // get max view_count from lp_item_view
    /* $sql = "SELECT MAX(view_count) FROM $TBL_LP_ITEM_VIEW WHERE lp_item_id = '" . Database::escape_string((int) $lp_item_view_id) . "'
      AND lp_view_id = (SELECT id from $TBL_LP_VIEW  WHERE user_id = '" . Database::escape_string((int) $student_id) . "' and lp_id='" . Database::escape_string((int) $lp_item_id) . "')";
      $res_max_view_count = api_sql_query($sql, __FILE__, __LINE__);
      $row_max_view_count = Database :: fetch_row($res_max_view_count);
      $max_view_count = (int) $row_max_view_count[0];

      // update score and total_time from last attempt when you qualify the exercise in Learning path detail
      $sql_update_score = "UPDATE $TBL_LP_ITEM_VIEW SET score = '" . Database::escape_string((float) $score) . "',total_time = '" . Database::escape_string((int) $total_time) . "' WHERE lp_item_id = '" . Database::escape_string((int) $lp_item_view_id) . "'
      AND lp_view_id = (SELECT id from $TBL_LP_VIEW  WHERE user_id = '" . Database::escape_string((int) $student_id) . "' and lp_id='" . Database::escape_string((int) $lp_item_id) . "') AND view_count = '".Database::escape_string($max_view_count)."'";
      api_sql_query($sql_update_score, __FILE__, __LINE__);

      // update score and total_time from last attempt when you qualify the exercise in Learning path detail
      $sql_update_score = "UPDATE $TBL_LP_ITEM_VIEW SET score = '" . (float) $score . "',total_time = '" . Database::escape_string((int) $total_time) . "' WHERE lp_item_id = '" . Database::escape_string((int) $lp_item_view_id) . "'
      AND lp_view_id = (SELECT id from $TBL_LP_VIEW  WHERE user_id = '" . Database::escape_string((int) $student_id) . "' and lp_id='" . Database::escape_string((int) $lp_item_id) . "') AND view_count = '".Database::escape_string($max_view_count)."'";
      api_sql_query($sql_update_score, __FILE__, __LINE__); */

    // update max_score from a exercise in lp
    $sql_update_max_score = "UPDATE $TBL_LP_ITEM SET max_score = '" . Database::escape_string((float) $totalWeighting) . "'  WHERE id = '" . Database::escape_string((int) $lp_item_view_id) . "'";

    api_sql_query($sql_update_max_score, __FILE__, __LINE__);
   }
  }
  if ($origin == 'tracking_course' && !empty($_POST['lp_item_id'])) {
   //Redirect to the course detail in lp
   header('location: ../mySpace/lp_tracking.php?course=' . Security :: remove_XSS($_GET['course']) . '&origin=' . $origin . '&lp_id=' . Security :: remove_XSS($_POST['lp_item_id']) . '&student_id=' . Security :: remove_XSS($_GET['student']));
  } else {
   //Redirect to the reporting
   header('location: ../mySpace/myStudents.php?origin=' . $origin . '&student=' . Security :: remove_XSS($_GET['student']) . '&details=true&course=' . Security :: remove_XSS($_GET['course']));
  }
 } 
 header('Location: '.api_get_path(WEB_CODE_PATH).'exercice/exercice.php?'.api_get_cidreq().'&show=result');
 exit;
}

if (!empty($_GET['gradebook']) && $_GET['gradebook'] == 'view') {
 $_SESSION['gradebook'] = Security::remove_XSS($_GET['gradebook']);
 $gradebook = $_SESSION['gradebook'];
} elseif (empty($_GET['gradebook'])) {
 unset($_SESSION['gradebook']);
 $gradebook = '';
}

if (!empty($gradebook) && $gradebook == 'view') {
 $interbreadcrumb[] = array(
     'url' => '../gradebook/' . $_SESSION['gradebook_dest'],
     'name' => get_lang('Gradebook')
 );
}

if ($show != 'result') {
 $nameTools = get_lang('Exercices');
} 

// need functions of statsutils lib to display previous exercices scores
include_once (api_get_path(LIBRARY_PATH) . 'statsUtils.lib.inc.php');

if ($is_allowedToEdit && !empty($choice) && $choice == 'exportqti2') {
 require_once ('export/qti2/qti2_export.php');
 $export = export_exercise($exerciseId, true);

 require_once (api_get_path(LIBRARY_PATH) . 'pclzip/pclzip.lib.php');
 $archive_path = api_get_path(SYS_ARCHIVE_PATH);
 $temp_dir_short = uniqid();
 $temp_zip_dir = $archive_path . "/" . $temp_dir_short;
 if (!is_dir($temp_zip_dir))
  mkdir($temp_zip_dir);
 $temp_zip_file = $temp_zip_dir . "/" . md5(time()) . ".zip";
 $temp_xml_file = $temp_zip_dir . "/qti2export_" . $exerciseId . '.xml';
 file_put_contents($temp_xml_file, $export);
 $zip_folder = new PclZip($temp_zip_file);
 $zip_folder->add($temp_xml_file, PCLZIP_OPT_REMOVE_ALL_PATH);
 $name = 'qti2_export_' . $exerciseId . '.zip';

 //DocumentManager::string_send_for_download($export,true,'qti2export_'.$exerciseId.'.xml');
 DocumentManager :: file_send_for_download($temp_zip_file, true, $name);
 unlink($temp_zip_file);
 unlink($temp_xml_file);
 rmdir($temp_zip_dir);
 exit (); //otherwise following clicks may become buggy
}
if (!empty($_POST['export_user_fields'])) {
 switch ($_POST['export_user_fields']) {
  case 'export_user_fields' :
   $_SESSION['export_user_fields'] = true;
   break;
  case 'do_not_export_user_fields' :
  default :
   $_SESSION['export_user_fields'] = false;
   break;
 }
}
if (!empty($_POST['export_report']) && $_POST['export_report'] == 'export_report') {
 if (api_is_platform_admin() || api_is_course_admin() || api_is_course_tutor() || api_is_course_coach()) {
  $user_id = null;
  if (empty($_SESSION['export_user_fields']))
   $_SESSION['export_user_fields'] = false;
  if (!$is_allowedToEdit and !$is_tutor) {
   $user_id = api_get_user_id();
  }
  require_once ('exercise_result.class.php');
  switch ($_POST['export_format']) {
   case 'xls' :
    $export = new ExerciseResult();
    $export->exportCompleteReportXLS($documentPath, $user_id, $_SESSION['export_user_fields']);
    exit;
    break;
   case 'csv' :
   default :
    $export = new ExerciseResult();
    $export->exportCompleteReportCSV($documentPath, $user_id, $_SESSION['export_user_fields']);
    exit;
    break;
  }
 } else {
  api_not_allowed(true);
 }
}

if (api_is_allowed_to_edit ()) {
 $htmlHeadXtra[] = '<link rel="stylesheet" href="' . api_get_path(WEB_LIBRARY_PATH) . 'javascript/thickbox.css" type="text/css" media="screen" />';
 $htmlHeadXtra[] = '<script type="text/javascript" src="' . api_get_path(WEB_LIBRARY_PATH) . 'javascript/thickbox.js"></script>';
 $htmlHeadXtra[] ='<script type="text/javascript">
$(document).ready(function(){ 

	$(function() {
		$("#contentLeft ul").sortable({ opacity: 0.6, cursor: "move", cancel: ".nodrag", update: function() {
			var order = $(this).sortable("serialize") + "&action=updateQuiz";			
			var record = order.split("&");
			var recordlen = record.length;
			var disparr = new Array();
			for(var i=0;i<(recordlen-1);i++)
			{
			 var recordval = record[i].split("=");
			 disparr[i] = recordval[1];			 
			}
			$.ajax({
			type: "GET",
			url: "exercise.ajax.php?'.api_get_cidReq().'&action=updateQuiz&disporder="+disparr,
			success: function(msg){
                             document.location="exercice.php?' . api_get_cidreq() . '";
                        }
		})			
		}
		});
	});

});
</script> ';
}

if ($origin != 'learnpath') {
 //so we are not in learnpath tool
 if (!isset($_REQUEST['quizpopup'])) {
//  Display :: display_header($nameTools, "Exercise");
	Display :: display_tool_header();
 } else {
  Display::display_reduced_header($nameTools, 'Exercise');
 }
 if (isset($_GET['message'])) {
  if (in_array($_REQUEST['message'], array('ExerciseEdited'))) {
   Display :: display_confirmation_message(get_lang($_GET['message']));
  }
 }
} else {
 echo '<link rel="stylesheet" type="text/css" href="' . api_get_path(WEB_CODE_PATH) . 'css/default.css"/>';
}

// tracking
event_access_tool(TOOL_QUIZ);

// Tool introduction
Display :: display_introduction_section(TOOL_QUIZ);

// selects $limitExPage exercises at the same time
$from = $page * $limitExPage;
$sql = "SELECT count(id) FROM $TBL_EXERCICES";
$res = api_sql_query($sql, __FILE__, __LINE__);
list ($nbrexerc) = Database :: fetch_array($res);

HotPotGCt($documentPath, 1, $_user['user_id']);
$tbl_grade_link = Database :: get_main_table(TABLE_MAIN_GRADEBOOK_LINK);
// only for administrator

if ($is_allowedToEdit) {

 if (!empty($choice)) {
  // construction of Exercise

  $objExerciseTmp = new Exercise();

  if($choice == "multipledelete")
  {
	  $del_quizid = array();
	  $quizid = $_REQUEST['quizid'];
	  $del_quizid = explode(",",$quizid);
	  
	  for($i=0;$i<sizeof($del_quizid);$i++)
	  {		  
		  if ($objExerciseTmp->read($del_quizid[$i])) {
			  $objExerciseTmp->delete();
			  $sql = 'SELECT gl.id FROM ' . $tbl_grade_link . ' gl WHERE gl.type="1" AND gl.ref_id="' . Database::escape_string($exerciseId) . '";';
			 $result = api_sql_query($sql, __FILE__, __LINE__);
			 $row = Database :: fetch_array($result, 'ASSOC');

			 $link = LinkFactory :: load($row['id']);
			 if ($link[0] != null) {
			  $link[0]->delete();
			 }			 
		  }
	  }
  }

  if ($objExerciseTmp->read($exerciseId)) {
   switch ($choice) {
    case 'delete' : // deletes an exercise
     $objExerciseTmp->delete();

     //delete link of exercise of gradebook tool
     $sql = 'SELECT gl.id FROM ' . $tbl_grade_link . ' gl WHERE gl.type="1" AND gl.ref_id="' . Database::escape_string($exerciseId) . '";';
     $result = api_sql_query($sql, __FILE__, __LINE__);
     $row = Database :: fetch_array($result, 'ASSOC');

     $link = LinkFactory :: load($row['id']);
     if ($link[0] != null) {
      $link[0]->delete();
     }
     Display :: display_confirmation_message(get_lang('ExerciseDeleted'));
     break;
    case 'enable' : // enables an exercise
     $objExerciseTmp->enable();
     $objExerciseTmp->save();
     // "WHAT'S NEW" notification: update table item_property (previously last_tooledit)
     Display :: display_confirmation_message(get_lang('VisibilityChanged'));

     break;
    case 'disable' : // disables an exercise
     $objExerciseTmp->disable();
     $objExerciseTmp->save();
     Display :: display_confirmation_message(get_lang('VisibilityChanged'));
     break;
    case 'disable_results' : //disable the results for the learners
     $objExerciseTmp->disable_results();
     $objExerciseTmp->save();
     Display :: display_confirmation_message(get_lang('ResultsDisabled'));
     break;
    case 'enable_results' : //disable the results for the learners
     $objExerciseTmp->enable_results();
     $objExerciseTmp->save();
     Display :: display_confirmation_message(get_lang('ResultsEnabled'));
     break;
    case 'integrate_quiz_in_lp':
     if (Security::check_token() && !empty($_POST['integrate_lp_id'])) { // if the form to integrate quiz in lp has been submitted
      Security::clear_token();
      $lp_id = intval($_POST['integrate_lp_id']);
      $quiz_title = $objExerciseTmp->selectTitle();
      require_once('../newscorm/learnpath.class.php');
      $o_lp = new learnpath(api_get_course_id(), $lp_id, api_get_user_id());
      $previous = 0;
      if (count($o_lp->items) > 0) {
       $previous = $o_lp->get_last();
      }
      $o_lp->add_item(0, $previous, 'quiz', $exerciseId, $quiz_title, '');
      Display :: display_confirmation_message(get_lang('QuizIntegratedInCourse'));
     }
     break;
   }
  }

  // destruction of Exercise
  unset($objExerciseTmp);
 }

 if (!empty($hpchoice)) {
  switch ($hpchoice) {
   case 'delete' : // deletes an exercise
    $imgparams = array();
    $imgcount = 0;
    GetImgParams($file, $documentPath, $imgparams, $imgcount);
    $fld = GetFolderName($file);
    for ($i = 0; $i < $imgcount; $i++) {
     my_delete($documentPath . $uploadPath . "/" . $fld . "/" . $imgparams[$i]);
     update_db_info("delete", $uploadPath . "/" . $fld . "/" . $imgparams[$i]);
    }

    if (my_delete($documentPath . $file)) {
     update_db_info("delete", $file);
    }
    my_delete($documentPath . $uploadPath . "/" . $fld . "/");
    break;
   case 'enable' : // enables an exercise
    $newVisibilityStatus = "1"; //"visible"
    $query = "SELECT id FROM $TBL_DOCUMENT WHERE path='" . Database :: escape_string($file) . "'";
    $res = api_sql_query($query, __FILE__, __LINE__);
    $row = Database :: fetch_array($res, 'ASSOC');
    api_item_property_update($_course, TOOL_DOCUMENT, $row['id'], 'visible', $_user['user_id']);
    //$dialogBox = get_lang('ViMod');

    break;
   case 'disable' : // disables an exercise
    $newVisibilityStatus = "0"; //"invisible"
    $query = "SELECT id FROM $TBL_DOCUMENT WHERE path='" . Database :: escape_string($file) . "'";
    $res = api_sql_query($query, __FILE__, __LINE__);
    $row = Database :: fetch_array($res, 'ASSOC');
    api_item_property_update($_course, TOOL_DOCUMENT, $row['id'], 'invisible', $_user['user_id']);
    //$dialogBox = get_lang('ViMod');
    break;
   default :
    break;
  }
 }

 if ($show == 'test') {
  $sql = "SELECT id,title,type,active,description, results_disabled FROM $TBL_EXERCICES WHERE active<>'-1' ORDER BY title LIMIT " . (int) $from . "," . (int) ($limitExPage + 1);
  $result = api_sql_query($sql, __FILE__, __LINE__);
 }
} elseif ($show == 'test') { // only for students //fin
 $sql = "SELECT id,title,type,description, results_disabled FROM $TBL_EXERCICES WHERE active='1' ORDER BY title LIMIT " . (int) $from . "," . (int) ($limitExPage + 1);
 $result = api_sql_query($sql, __FILE__, __LINE__);
}

// the actions
echo '<div class="actions">';

//echo '<a href="' . api_add_url_param($_SERVER['REQUEST_URI'], 'show=test') . '">' . Display :: return_icon('quiz.png', get_lang('BackToExercisesList')) . get_lang('BackToExercisesList') . '</a>';

// display the next and previous link if needed
$from = $page * $limitExPage;
$sql = "SELECT count(id) FROM $TBL_EXERCICES";
$res = api_sql_query($sql, __FILE__, __LINE__);
list ($nbrexerc) = Database :: fetch_array($res);
HotPotGCt($documentPath, 1, $_user['user_id']);
// only for administrator
if ($is_allowedToEdit) {
 if ($show == 'test') {
  $sql = "SELECT id,title,type,active,description, results_disabled FROM $TBL_EXERCICES q WHERE active<>'-1' ORDER BY position LIMIT " . (int) $from . "," . (int) ($limitExPage + 1);
  $result = api_sql_query($sql, __FILE__, __LINE__);
 }
} elseif ($show == 'test') { // only for students
 $sql = "SELECT id,title,type,description, results_disabled FROM $TBL_EXERCICES WHERE active='1' ORDER BY position LIMIT " . (int) $from . "," . (int) ($limitExPage + 1);
 $result = api_sql_query($sql, __FILE__, __LINE__);
}

if ($show == 'test') {
 $nbrExercises = Database :: num_rows($result);

 //get HotPotatoes files (active and inactive)
 $res = api_sql_query("SELECT *
						FROM $TBL_DOCUMENT
						WHERE
						path LIKE '" . Database :: escape_string($uploadPath) . "/%/%'", __FILE__, __LINE__);
 $nbrTests = Database :: num_rows($res);
 $res = api_sql_query("SELECT *
						FROM $TBL_DOCUMENT d, $TBL_ITEM_PROPERTY ip
						WHERE  d.id = ip.ref
						AND ip.tool = '" . TOOL_DOCUMENT . "'
						AND d.path LIKE '" . Database :: escape_string($uploadPath) . "/%/%'
						AND ip.visibility='1'", __FILE__, __LINE__);
 $nbrActiveTests = Database :: num_rows($res);

 if ($is_allowedToEdit) {
  //if user is allowed to edit, also show hidden HP tests
  $nbrHpTests = $nbrTests;
 } else {
  $nbrHpTests = $nbrActiveTests;
 }
 $nbrNextTests = $nbrexerc - $nbrHpTests - (($page * $limitExPage));

 echo '<span style="float:right">';
 //show pages navigation link for previous page
 if ($page) {
  echo "<a href=\"" . api_get_self() . "?" . api_get_cidreq() . "&amp;page=" . ($page - 1) . "\">" . Display :: return_icon('prev.png') . get_lang("PreviousPage") . "</a> | ";
 } elseif ($nbrExercises + $nbrNextTests > $limitExPage) {
  echo Display :: return_icon('prev.png') . get_lang('PreviousPage') . " | ";
 }

 //show pages navigation link for previous page
 if ($nbrExercises + $nbrNextTests > $limitExPage) {
  echo "<a href=\"" . api_get_self() . "?" . api_get_cidreq() . "&amp;page=" . ($page + 1) . "\">" . get_lang("NextPage") . Display :: return_icon('next.png') . "</a>";
 } elseif ($page) {
  echo get_lang("NextPage") . Display :: return_icon('next.png');
 }
 echo '</span>';
}

if (($is_allowedToEdit) and ($origin != 'learnpath')) {
 if ($_GET['show'] != 'result') {
  echo '<a href="exercice.php?' . api_get_cidreq() . '">' . Display::return_icon('pixel.gif', get_lang('Move'), array('class' => 'toolactionplaceholdericon toolactionlist')) . get_lang('List') . '</a>';
  echo '<a href="exercise_admin.php?' . api_get_cidreq() . '">' . Display::return_icon('pixel.gif', get_lang('NewEx'), array('class' => 'toolactionplaceholdericon toolactionnewquiz'))  . get_lang('NewEx') . '</a>';  
/*if (!isset($_GET['lp_id'])) {
   echo '<a href="upload_exercise.php?' . api_get_cidreq() . '">' . Display::return_icon('pixel.gif', get_lang('UploadQuiz'), array('class' => 'toolactionplaceholdericon toolactionexportcourse')) . get_lang('UploadQuiz') . '</a>';
  }*/
 }
} else {
 //the student view
 if ($show == 'result') {
  
 } else {
  echo '<a href="' . api_add_url_param($_SERVER['REQUEST_URI'], 'show=result') . '">' . Display::return_icon('pixel.gif', get_lang("Results"), array('class' => 'toolactionplaceholdericon toolactionquizscores')) . get_lang('Results') . '</a>';
 }
}

echo '<script>
function quiz_delete()
{	
	var len = document.quiz_list.chk_quiz.length;
	var quizid = new Array();
	if(isNaN(len))
	{
		quizid = document.quiz_list.chk_quiz.value;
	}
	else
	{
		for(var i=0;i<len;i++)
		{
			if(document.quiz_list.chk_quiz[i].checked)
			{
				quizid[i] = document.quiz_list.chk_quiz[i].value;			
			}
		}	
	}
window.location.href = "'.api_get_self().'?'.api_get_cidReq().'&choice=multipledelete&quizid="+quizid;
}
</script>';

echo '</div>'; // closing the actions div
// start the content div
if ($_GET['show'] == 'result') {
 $content_id = 'content';
} else {
 $content_id = 'content_with_secondary_actions';
}
echo '<div id="content">';

// showing the list of quizes
if ($show == 'test') {
?>
<form name="quiz_list" method="POST">
 <table id="table_quiz_list" class="data_table data_table_exercise" style="width:100%">
<?php
// table headers for teachers
 if (($is_allowedToEdit) and ($origin != 'learnpath')) {
?>
   <tr class="row_odd nodrop nodrag">
    <th width="8%"><?php echo get_lang('Move') ?></th>
    <th width="8%"><?php echo get_lang('Delete'); ?></th>
    <th width="3%">&nbsp;</th>
    <th width="8%"><?php echo get_lang('Modify') ?></th>
    <th align="left" width="40%"><?php echo get_lang('ExerciseName'); ?></th>
    <th width="9%"><?php echo get_lang('Questions') ?></th>
    <th width="9%"><?php echo get_lang('Visible'); ?></th>
    <th width="9%"><?php echo get_lang('Tracking'); ?></th>
    <th width="5%"><?php echo get_lang('Course'); ?></th>
   </tr>
<?php
 } else {
// table headers for students
?> 
 	<tr>
   <th colspan="2"><?php echo get_lang('ExerciseName'); ?></th>
   <th style="width:200px;"><?php echo get_lang('State'); ?></th>
  </tr>
<?php
 }
 echo "</table>";
echo '<div id="contentWrap"><div id="contentLeft"><ul class="dragdrop nobullets ">';
 // show message if no HP test to show
 if (!($nbrExercises + $nbrHpTests)) {
?>
  <table width="100%"><tr>
   <td <?php echo ($is_allowedToEdit ? 'colspan="9"' : 'colspan="5"'); ?> align="center"><?php echo get_lang("NoEx"); ?></td>
  </tr></table>
<?php
 }
 $i = 1;
 // while list exercises
 if ($origin != 'learnpath') {     
     
  //avoid sending empty parameters
  $myorigin = (empty($origin) ? '' : '&origin=' . $origin);
  $mylpid = (empty($learnpath_id) ? '' : '&learnpath_id=' . $learnpath_id);
  $mylpitemid = (empty($learnpath_item_id) ? '' : '&learnpath_item_id=' . $learnpath_item_id);
  while ($row = Database :: fetch_array($result)) {
   echo '<tr><td>';
   echo '<li id="recordsArray_' . $row['id'] . '" class="category">';
   echo '<div>';
   echo '<table class="data_table" width="100%">';
   if ($i % 2 == 0)
    $s_class = "row_odd";
   else
    $s_class = "row_even";
   // prof only
   if ($is_allowedToEdit) {
//    echo '<tr class="' . $s_class . '" id="quiz_row_' . $row['id'] . '">' . "\n";
	 echo '<tr class="' . $s_class . '">' . "\n";
?>
    <td width="8%" align="center" class="dragHandle" style="cursor:pointer"><?php echo Display::return_icon('pixel.gif', get_lang('Move'), array('class' => 'actionplaceholdericon actionsdraganddrop')); ?></td>
    <td class="nodrag" width="8%" valign="left" align="center"><input type="checkbox" name="chk_quiz" value="<?php echo $row['id']; ?>"></td>
    <td class="nodrag" width="3%" valign="left" align="center"><?php echo ($i + ($page * $limitExPage)) . '.'; ?></td>
    <td class="nodrag" width="8%" align="center"><a href="admin.php?exerciseId=<?php echo $row['id']; ?>&<?php echo api_get_cidreq() ?>"><?php echo Display::return_icon('pixel.gif', get_lang('Edit'), array('class' => 'actionplaceholdericon actionedit'));  ?></a></td>
 <?php $row['title'] = api_parse_tex($row['title']); ?>
    <td class="nodrag"  width="40%"><a href="exercice_submit.php?<?php echo api_get_cidreq() . $myorigin . $mylpid . $mylpitemid; ?>&amp;exerciseId=<?php echo $row['id']; ?>" <?php if (!$row['active'])
     echo 'class="invisible"'; ?>><?php echo $row['title']; ?></a></td>
    <td class="nodrag" width="9%" align="center"><?php
    $TBL_QUIZ_TYPE = Database::get_course_table(TABLE_QUIZ_TYPE);
	$sql_scenario = "SELECT count(*) FROM $TBL_QUIZ_TYPE WHERE exercice_id = ".$row['id']."  AND current_active = 1";
	$rs_scenario = Database::query($sql_scenario, __FILE__, __LINE__);
    $quiz_category = Database::result($rs_scenario, 0);
	if($quiz_category == 0)
	{
    $sql_quiz_question = "SELECT count(*) as count FROM " . $TBL_EXERCICE_QUESTION . " WHERE exercice_id='" . $row['id'] . "'";
    $rs_quiz_question = Database::query($sql_quiz_question, __FILE__, __LINE__);
    $number_questions = Database::result($rs_quiz_question, 0);
	}
	else
	{
			$sql1 = "SELECT category_id,quiz_level,number_of_question,scenario_type FROM $TBL_QUIZ_TYPE WHERE exercice_id = ".$row['id']. " AND current_active = 1";
			$result1 = Database::query($sql1, __FILE__, __LINE__);
			$total_questions = 0;
			while($row1 = Database::fetch_array($result1))
			{				
				$sql_in = "SELECT DISTINCT(question.id) AS id FROM $TBL_EXERCICES quiz, $TBL_QUESTIONS question, $TBL_EXERCICE_QUESTION rel_question, $TBL_QUIZ_TYPE quiz_type WHERE quiz.id=rel_question.exercice_id AND rel_question.question_id = question.id AND quiz.id = quiz_type.exercice_id AND rel_question.exercice_id = quiz_type.exercice_id AND question.level = ".$row1['quiz_level']." AND question.category = ".$row1['category_id']." ORDER BY rel_question.question_order LIMIT ".$row1['number_of_question'];				
				$result_in = Database::query($sql_in, __FILE__, __LINE__);
				$nbrQuestions = Database::num_rows($result_in);
				$total_questions = $total_questions + $nbrQuestions;
			}
			$number_questions = $total_questions;
	}
    echo $number_questions;
 ?></td>
<!--<td  width="50" align="center">
     <a href="exercice.php?choice=delete&exerciseId=<?php echo $row['id']; ?>&<?php echo api_get_cidreq() ?>" onclick="javascript:if(!confirm('<?php echo addslashes(api_htmlentities(get_lang('AreYouSureToDelete'), ENT_QUOTES, $charset));
    echo " " . $row['title'];
    echo "?"; ?>')) return false;"><?php Display::display_icon('delete.png', get_lang('Delete')) ?></a>&nbsp;
    </td>-->
    <td class="nodrag" width="9%" align="center">
<?php
    //if active
    if ($row['active']) {
?>
      <a href="exercice.php?choice=disable&page=<?php echo $page; ?>&exerciseId=<?php echo $row['id']; ?>&<?php echo api_get_cidreq() ?>"><?php echo Display::return_icon('pixel.gif', get_lang('Deactivate'), array('class' => 'actionplaceholdericon actionvisible')); ?></a>&nbsp;
<?php
    } else {
     // else if not active
?>
     <a href="exercice.php?choice=enable&page=<?php echo $page; ?>&exerciseId=<?php echo $row['id']; ?>&<?php echo api_get_cidreq() ?>"><?php echo Display::return_icon('pixel.gif', get_lang('Activate'), array('class' => 'actionplaceholdericon actionvisible invisible')); ?></a>&nbsp;
  <?php
    }
  ?>
   </td>
   <td class="nodrag" width="9%"  align="center">
    <a href="tracking/by_questions.php?exerciceId=<?php echo $row['id']; ?>&<?php echo api_get_cidreq() ?>" title="<?php echo get_lang('ExercisesTracking') ?>"><?php echo Display::return_icon('pixel.gif', get_lang('ExercisesTracking'), array('class' => 'actionplaceholdericon actiontracking')); ?></a>
   </td>
   <td class="nodrag" width="9%" align="center">
    <a class="thickbox" href="integrate_quiz_in_course.php?quizId=<?php echo $row['id']; ?>&<?php echo api_get_cidreq() ?>&height=180&width=500" title="<?php echo get_lang('IntegrateQuizInCourse') ?>"><?php echo Display::return_icon('pixel.gif', get_lang('IntegrateQuizInCourse'), array('class' => 'actionplaceholdericon actionviewmodule')); ?></a>
   </td>
  </tr>
  <?php
   } else { // student only
    if ($i % 2 == 0)
     $s_class = "row_odd";
    else
     $s_class = "row_even";
  ?>
  <tr class="<?php echo $s_class; ?>">
   <td  style="width:40px;"><?php echo ($i + ($page * $limitExPage)) . '.'; ?></td>
   <td  style="width:40px;"><?php echo Display::return_icon('pixel.gif', '', array('class' => 'actionplaceholdericon actionstudentviewquiz'));?></td>
<?php $row['title'] = api_parse_tex($row['title']); ?>
   <td align="left"><a href="exercice_submit.php?<?php echo api_get_cidreq() . $myorigin . $mylpid . $myllpitemid; ?>&exerciseId=<?php echo $row['id']; ?>"><?php echo $row['title']; ?></a></td>
   <td style="width:200px;" align="center"><?php
    $eid = $row['id'];
    $uid = api_get_user_id();
    //this query might be improved later on by ordering by the new "tms" field rather than by exe_id
    $qry = "SELECT * FROM $TBL_TRACK_EXERCICES
						WHERE exe_exo_id = '" . Database :: escape_string($eid) . "' and exe_user_id = '" . Database :: escape_string($uid) . "' AND exe_cours_id = '" . api_get_course_id() . "' AND status <>'incomplete' AND orig_lp_id = 0 AND orig_lp_item_id = 0 AND session_id =  '" . api_get_session_id() . "'
						ORDER BY exe_id DESC";
    $qryres = api_sql_query($qry);
    $num = Database :: num_rows($qryres);

    //hide the results
    $my_result_disabled = $row['results_disabled'];
    if ($my_result_disabled == 0) {
     if ($num > 0) {
      $row = Database :: fetch_array($qryres);
      $percentage = 0;
      if ($row['exe_weighting'] != 0) {
       $percentage = ($row['exe_result'] / $row['exe_weighting']) * 100;
      }
      echo get_lang('Attempted') . ' (' . get_lang('Score') . ': ';
      printf("%1.2f\n", $percentage);
      echo " %)";
     } else {
      echo get_lang('NotAttempted');
     }
    } else {
     echo get_lang('CantShowResults');
    }
?></td>
  </tr>
  <?php
   }
   // skips the last exercise, that is only used to know if we have or not to create a link "Next page"
   if ($i == $limitExPage) {
    break;
   }
   echo '</table></div></li></td></tr>';
   $i++;
  } // end while()
  
  echo '</form>';

  $ind = $i;
  if (($from + $limitExPage - 1) > $nbrexerc) {
   if ($from > $nbrexerc) {
    $from = $from - $nbrexerc;
    $to = $limitExPage;
   } else {
    $to = $limitExPage - ($nbrexerc - $from);
    $from = 0;
   }
  } else {
   $to = $limitExPage;
  }

  if ($is_allowedToEdit) {
   $sql = "SELECT d.path as path, d.comment as comment, ip.visibility as visibility
							FROM $TBL_DOCUMENT d, $TBL_ITEM_PROPERTY ip
										WHERE   d.id = ip.ref AND ip.tool = '" . TOOL_DOCUMENT . "' AND
										 (d.path LIKE '%htm%')
										AND   d.path  LIKE '" . Database :: escape_string($uploadPath) . "/%/%' LIMIT " . (int) $from . "," . (int) $to; // only .htm or .html files listed
  } else {
   $sql = "SELECT d.path as path, d.comment as comment, ip.visibility as visibility
							FROM $TBL_DOCUMENT d, $TBL_ITEM_PROPERTY ip
											WHERE d.id = ip.ref AND ip.tool = '" . TOOL_DOCUMENT . "' AND
											 (d.path LIKE '%htm%')
											AND   d.path  LIKE '" . Database :: escape_string($uploadPath) . "/%/%' AND ip.visibility='1' LIMIT " . (int) $from . "," . (int) $to;
  }

  $result = api_sql_query($sql, __FILE__, __LINE__);
  echo '<table>';
  while ($row = Database :: fetch_array($result, 'ASSOC')) {
   $attribute['path'][] = $row['path'];
   $attribute['visibility'][] = $row['visibility'];
   $attribute['comment'][] = $row['comment'];
  }
  $nbrActiveTests = 0;
  if (is_array($attribute['path'])) {
   while (list ($key, $path) = each($attribute['path'])) {
    list ($a, $vis) = each($attribute['visibility']);
    if (strcmp($vis, "1") == 0) {
     $active = 1;
    } else {
     $active = 0;
    }
    echo "<tr>\n";

    $title = GetQuizName($path, $documentPath);
    if ($title == '') {
     $title = basename($path);
    }
    // prof only
    if ($is_allowedToEdit) {
     /*      * ********* */
  ?>

     <tr>
      <td><img src="../img/jqz.gif" alt="HotPotatoes" /></td>
      <td><?php echo ($ind + ($page * $limitExPage)) . '.'; ?></td>
      <td><a href="showinframes.php?file=<?php echo $path ?>&cid=<?php echo $_course['official_code']; ?>&uid=<?php echo $_user['user_id']; ?>" <?php if (!$active)
      echo 'class="invisible"'; ?>><?php echo $title ?></a></td>
      <td></td>
      <td><a href="adminhp.php?hotpotatoesName=<?php echo $path; ?>"> <img src="../img/edit_link.png" border="0" alt="<?php echo api_htmlentities(get_lang('Modify'), ENT_QUOTES, $charset); ?>" /></a>
       <img src="../img/wizard_gray_small.gif" border="0" title="<?php echo api_htmlentities(get_lang('Edit'), ENT_QUOTES, $charset); ?>" alt="<?php echo api_htmlentities(get_lang('Edit'), ENT_QUOTES, $charset); ?>" />
       <a href="<?php echo $exercicePath; ?>?hpchoice=delete&amp;file=<?php echo $path; ?>" onclick="javascript:if(!confirm('<?php echo addslashes(api_htmlentities(get_lang('AreYouSure'), ENT_QUOTES, $charset) . $title . "?"); ?>')) return false;"><img src="../img/delete.png" border="0" alt="<?php echo api_htmlentities(get_lang('Delete'), ENT_QUOTES, $charset); ?>" /></a>
<?php
     // if active
     if ($active) {
      $nbrActiveTests = $nbrActiveTests + 1;
?>
        <a href="<?php echo $exercicePath; ?>?hpchoice=disable&amp;page=<?php echo $page; ?>&amp;file=<?php echo $path; ?>"><img src="../img/visible.gif" border="0" alt="<?php echo api_htmlentities(get_lang('Deactivate'), ENT_QUOTES, $charset); ?>" /></a>
<?php
     } else { // else if not active
?>
        <a href="<?php echo $exercicePath; ?>?hpchoice=enable&amp;page=<?php echo $page; ?>&amp;file=<?php echo $path; ?>"><img src="../img/invisible.gif" border="0" alt="<?php echo api_htmlentities(get_lang('Activate'), ENT_QUOTES, $charset); ?>" /></a>
<?php
     }
     echo '<img src="../img/lp_quiz_na.gif" border="0" alt="" />';
     /*      * ************* */
?></td>
<?php
    } else { // student only
     if ($active == 1) {
      $nbrActiveTests = $nbrActiveTests + 1;
?>
    <tr>

            <td><?php echo ($ind + ($page * $limitExPage)) . '.'; ?><!--<img src="../img/jqz.jpg" alt="HotPotatoes" />--></td>
     <td>&nbsp;</td>
     <td><a href="showinframes.php?<?php echo api_get_cidreq() . "&amp;file=" . $path . "&amp;cid=" . $_course['official_code'] . "&amp;uid=" . $_user['user_id'] . '"';
      if (!$active)
       echo 'class="invisible"'; ?>"><?php echo $title; ?></a></td>
     <td>&nbsp;</td><td>&nbsp;</td>
    </tr>
  <?php
     }
    }
  ?>
   <?php
    if ($ind == $limitExPage) {
     break;
    }
    if ($is_allowedToEdit) {
     $ind++;
    } else {
     if ($active == 1) {
      $ind++;
     }
    }
   }
  }
 } //end if ($origin != 'learnpath') {
   ?>
 </table>
 </ul></div></div>

<?php
echo '<div style="padding:5px 5px 5px 0px;">';
if(api_is_allowed_to_edit() && $nbrExercises <> 0)
	 {
		echo '<button class="cancel" type="button" name="submit_save" id="submit_save"  style="float:left;" onclick="quiz_delete()">'.get_lang('Delete').'</button>';
	 }
echo '</div>';

// close the content div
 echo '</div>';
?>
<div class="actions">
<?php
  if (is_allowed_to_edit()) {
	if(api_get_setting('show_quizcategory') == 'true'){
	echo '<a href="exercise_category.php?' . api_get_cidreq() . '">' . Display :: return_icon('category_22.png', get_lang('Categories')) . get_lang('Categories') . '</a>';
	}	
	if (!isset($_GET['lp_id'])) {
	echo '<a href="upload_exercise.php?' . api_get_cidreq() . '">' . Display::return_icon('pixel.gif', get_lang('UploadQuiz'), array('class' => 'actionplaceholdericon actionuploadquiz')) . get_lang('UploadQuiz') . '</a>';
	}
?>
 <a href="exercice.php?<?php echo api_get_cidreq(); ?>&show=result"><?php echo Display::return_icon('pixel.gif', get_lang('Tracking'), array('class' => 'actionplaceholdericon actiontracking')) . get_lang('Tracking') ?></a>
 	<!--><a href="<?php //echo api_add_url_param($_SERVER['REQUEST_URI'], 'show=export')?>"><?php //echo Display :: return_icon('down32.png', get_lang('Export')) . get_lang('Export')?></a><!-->
<?php
  }
?>
</div>

<?php
}

// display footer
 Display :: display_footer();
 ?>