<?php //$id: $
/* For licensing terms, see /dokeos_license.txt */
/**
*
*	@package dokeos.exercise
* 	@author Julio Montoya Armas Added switchable fill in blank option added
* 	@version $Id: exercise_show.php 22256 2009-07-20 17:40:20Z ivantcholakov $
*
* 	@todo remove the debug code and use the general debug library
* 	@todo use the Database:: functions
* 	@todo small letters for table variables
*/

// name of the language file that needs to be included
$language_file=array('exercice','tracking','admin');

// including the global dokeos file
include('../inc/global.inc.php');
include('../inc/lib/course.lib.php');
// including additional libraries
include_once('exercise.class.php');
include_once('question.class.php'); //also defines answer type constants
include_once('answer.class.php');
include_once(api_get_path(LIBRARY_PATH).'formvalidator/FormValidator.class.php');
require_once(api_get_path(LIBRARY_PATH).'geometry.lib.php');
//$htmlHeadXtra[] = '<script src="'.api_get_path(WEB_LIBRARY_PATH).'javascript/jquery.js" type="text/javascript" language="javascript"></script>';
//$htmlHeadXtra[] = '<script src="'.api_get_path(WEB_LIBRARY_PATH).'javascript/jquery.corners.min.js" type="text/javascript"></script>';

if ( empty ( $origin ) ) {
    $origin = $_REQUEST['origin'];
}

if ($origin == 'learnpath')
	api_protect_course_script();
else 
	api_protect_course_script(true);

// Database table definitions
$TBL_EXERCICE_QUESTION 	= Database::get_course_table(TABLE_QUIZ_TEST_QUESTION);
$TBL_EXERCICES         	= Database::get_course_table(TABLE_QUIZ_TEST);
$TBL_QUESTIONS         	= Database::get_course_table(TABLE_QUIZ_QUESTION);
$TBL_REPONSES          	= Database::get_course_table(TABLE_QUIZ_ANSWER);
$main_user_table 		= Database :: get_main_table(TABLE_MAIN_USER);
$main_course_user_table = Database :: get_main_table(TABLE_MAIN_COURSE_USER);
$TBL_TRACK_EXERCICES	= Database::get_statistic_table(TABLE_STATISTIC_TRACK_E_EXERCICES);
$TBL_TRACK_ATTEMPT		= Database::get_statistic_table(TABLE_STATISTIC_TRACK_E_ATTEMPT);

$dsp_percent = false;
$debug=0;

if($debug>0) {
	echo str_repeat('&nbsp;',0).'Entered exercise_result.php'."<br />\n";var_dump($_POST);
}
// general parameters passed via POST/GET

if ( empty ( $learnpath_id ) ) {
    $learnpath_id       = $_REQUEST['learnpath_id'];
}
if ( empty ( $learnpath_item_id ) ) {
    $learnpath_item_id  = $_REQUEST['learnpath_item_id'];
}
if ( empty ( $formSent ) ) {
    $formSent= $_REQUEST['formSent'];
}
if ( empty ( $exerciseResult ) ) {
    $exerciseResult = $_SESSION['exerciseResult'];
}
if ( empty ( $questionId ) ) {
    $questionId = $_REQUEST['questionId'];
}
if ( empty ( $choice ) ) {
    $choice = $_REQUEST['choice'];
}
if ( empty ( $questionNum ) ) {
    $questionNum    = $_REQUEST['questionNum'];
}
if ( empty ( $nbrQuestions ) ) {
    $nbrQuestions   = $_REQUEST['nbrQuestions'];
}
if ( empty ( $questionList ) ) {
    $questionList = $_SESSION['questionList'];
}
if ( empty ( $objExercise ) ) {
    $objExercise = $_SESSION['objExercise'];
}
if ( empty ( $exeId ) ) {
    $exeId = $_REQUEST['id'];
}
if ( empty ( $exercise_id ) ) {
    $exercise_id = $_REQUEST['exerciseid'];
}

if ( empty ( $action ) ) {
    $action = $_GET['action'];
}

$current_user_id = api_get_user_id();
$current_user_id = "'".$current_user_id."'";
$current_attempt = $_SESSION['current_exercice_attempt'][$current_user_id];
$course_code = api_get_course_id();

//Unset session for clock time
unset($_SESSION['current_exercice_attempt'][$current_user_id]);
unset($_SESSION['expired_time'][$course_code][intval($_SESSION['id_session'])][$exercise_id][$learnpath_id]);
unset($_SESSION['end_expired_time'][$exercise_id][$learnpath_id]);


$is_allowedToEdit=api_is_allowed_to_edit() || $is_courseTutor;
$nameTools=get_lang('CorrectTest');

if (isset($_SESSION['gradebook'])){
	$gradebook=	$_SESSION['gradebook'];
}

if (!empty($gradebook) && $gradebook=='view') {	
	$interbreadcrumb[]= array (
			'url' => '../gradebook/'.$_SESSION['gradebook_dest'],
			'name' => get_lang('Gradebook')
		);
}

if($origin=='user_course') {
	$interbreadcrumb[] = array ("url" => "../user/user.php?cidReq=".Security::remove_XSS($_GET['course']), "name" => get_lang("Users"));
	$interbreadcrumb[] = array("url" => "../mySpace/myStudents.php?student=".Security::remove_XSS($_GET['student'])."&course=".$_course['id']."&details=true&origin=".Security::remove_XSS($_GET['origin']) , "name" => get_lang("DetailsStudentInCourse"));
} else if($origin=='tracking_course') {
	$interbreadcrumb[] = array ("url" => "../mySpace/index.php", "name" => get_lang('MySpace'));
 	$interbreadcrumb[] = array ("url" => "../mySpace/myStudents.php?student=".Security::remove_XSS($_GET['student']).'&details=true&origin='.$origin.'&course='.Security::remove_XSS($_GET['cidReq']), "name" => get_lang("DetailsStudentInCourse"));
} else if($origin=='student_progress') {
	$interbreadcrumb[] = array ("url" => "../auth/my_progress.php?id_session".Security::remove_XSS($_GET['id_session'])."&course=".$_cid, "name" => get_lang('MyProgress'));
	unset($_cid);
} else {
	$interbreadcrumb[]=array("url" => "exercice.php?gradebook=$gradebook","name" => get_lang('Exercices'));
	$this_section=SECTION_COURSES;
}

if ($origin != 'learnpath') {
	Display::display_tool_header($nameTools,"Exercise");
} else {
    // If the quiz is into modules then we must load jquery library
        $htmlHeadXtra[] = '<script type="text/javascript" src="'.api_get_path(WEB_LIBRARY_PATH).'javascript/jquery-1.4.2.min.js" language="javascript"></script>';
	Display::display_reduced_header();
}
$emailId   = $_REQUEST['email'];
$user_name = $_REQUEST['user'];
$test 	   = $_REQUEST['test'];
$dt	 	   = $_REQUEST['dt'];
$marks 	   = $_REQUEST['res'];
$id 	   = $_REQUEST['id'];

$sql_fb_type='SELECT feedback_type FROM '.$TBL_EXERCICES.' as exercises, '.$TBL_TRACK_EXERCICES.' as track_exercises WHERE exercises.id=track_exercises.exe_exo_id AND track_exercises.exe_id="'.Database::escape_string($id).'"';
$res_fb_type=Database::query($sql_fb_type,__FILE__,__LINE__);
$row_fb_type=Database::fetch_row($res_fb_type);
$feedback_type = $row_fb_type[0]; 

?>
<style type="text/css">
<!--
#comments {
	position:absolute;
	left:795px;
	top:0px;
	width:200px;
	height:75px;
	z-index:1;
}

-->
</style>
<script language="javascript">
function showfck(sid,marksid)
{
	document.getElementById(sid).style.display='block';
	document.getElementById(marksid).style.display='block';
	var comment = 'feedback_'+sid; 
	document.getElementById(comment).style.display='none';
}

function getFCK(vals,marksid)
{
  var f=document.getElementById('myform');

  var m_id = marksid.split(',');
  for(var i=0;i<m_id.length;i++){
  var oHidn = document.createElement("input");
			oHidn.type = "hidden";
			var selname = oHidn.name = "marks_"+m_id[i];
			var selid = document.forms['marksform_'+m_id[i]].marks.selectedIndex;
			oHidn.value = document.forms['marksform_'+m_id[i]].marks.options[selid].text;
			f.appendChild(oHidn);
	}

	var ids = vals.split(',');
	for(var k=0;k<ids.length;k++){
			var oHidden = document.createElement("input");
			oHidden.type = "hidden";
			oHidden.name = "comments_"+ids[k];
			oEditor = FCKeditorAPI.GetInstance(oHidden.name) ;
			oHidden.value = oEditor.GetXHTML(true);
			f.appendChild(oHidden);
	}
//f.submit();
}
</script>
<?php

/**
 * This function gets the comments of an exercise
 *
 * @param int $id
 * @param int $question_id
 * @return str the comment
 */
function get_comments($id,$question_id)
{
	global $TBL_TRACK_ATTEMPT;
	$sql = "SELECT teacher_comment FROM ".$TBL_TRACK_ATTEMPT." where exe_id='".Database::escape_string($id)."' and question_id = '".Database::escape_string($question_id)."' ORDER by question_id";
	$sqlres = api_sql_query($sql, __FILE__, __LINE__);
	$comm = Database::result($sqlres,0,"teacher_comment");
	return $comm;
}
/**
 * Display the answers to a multiple choice question
 *
 * @param integer Answer type
 * @param integer Student choice
 * @param string  Textual answer
 * @param string  Comment on answer
 * @param string  Correct answer comment
 * @param integer Exercise ID
 * @param integer Question ID
 * @param boolean Whether to show the answer comment or not 
 * @return void
 */
function display_unique_or_multiple_answer($answerType, $studentChoice, $answer, $answerComment, $answerCorrect, $id, $questionId, $ans)
{
	?>
	<tr>
	<td width="5%" align="center">
		<img src="../img/<?php echo ($answerType == UNIQUE_ANSWER)?'radio':'checkbox'; echo $studentChoice?'_on':'_off'; ?>.gif"
		border="0" alt="" />
	</td>
	<td width="5%" align="center">
		<img src="../img/<?php echo ($answerType == UNIQUE_ANSWER)?'radio':'checkbox'; echo $answerCorrect?'_on':'_off'; ?>.gif"
		border="0" alt=" " />
	</td>
	<td width="40%" style="border-bottom: 1px solid #4171B5;">
		<?php
		$answer=api_parse_tex($answer);
		echo $answer; 
		?>		
	</td>	
	<!--<td width="20%" style="border-bottom: 1px solid #4171B5;">
		<?php
		$answerComment=api_parse_tex($answerComment);
		if($studentChoice)
		{
			if(!$answerCorrect)
			{
				echo '<span style="font-weight: bold; color: #FF0000;">'.nl2br(make_clickable($answerComment)).'</span>';
			}
			else{
				echo '<span style="font-weight: bold; color: #008000;">'.nl2br(make_clickable($answerComment)).'</span>';
			}
		}
		else
		{
			echo '&nbsp;';
		} 
		?>
	</td>	
		<?php 
	    if ($ans==1) {
	        $comm = get_comments($id,$questionId);
		}
	    ?>    -->
	</tr>
	<?php
}

/**
 * Display the answers to a reasoning question
 *
 * @param integer Answer type
 * @param integer Student choice
 * @param string  Textual answer
 * @param string  Comment on answer
 * @param string  Correct answer comment
 * @param integer Exercise ID
 * @param integer Question ID
 * @param boolean Whether to show the answer comment or not 
 * @return void
 */
function display_reasoning_answer($answerType, $studentChoice, $answer, $answerComment, $answerCorrect, $id, $questionId, $ans)
{
	?>
	<tr>
	<td width="5%" align="center">
		<img src="../img/<?php echo ($answerType == UNIQUE_ANSWER)?'radio':'checkbox'; echo $studentChoice?'_on':'_off'; ?>.gif"
		border="0" alt="" />
	</td>
	<td width="5%" align="center">
		<img src="../img/<?php echo ($answerType == UNIQUE_ANSWER)?'radio':'checkbox'; echo $answerCorrect?'_on':'_off'; ?>.gif"
		border="0" alt=" " />
	</td>
	<td width="40%" style="border-bottom: 1px solid #4171B5;">
		<?php
		$answer=api_parse_tex($answer);
		echo $answer; 
		?>		
	</td>		
		<?php 
	    if ($ans==1) {
	        $comm = get_comments($id,$questionId);
		}
	    ?>  
	</tr>
	<?php
}
/**
 * Shows the answer to a fill-in-the-blanks question, as HTML
 * @param string    Answer text
 * @param int       Exercise ID
 * @param int       Question ID
 * @return void
 */
 
function display_fill_in_blanks_answer($answer,$id,$questionId)
{
	?>
		<tr>
		<td>
			<?php echo nl2br(Security::remove_XSS($answer,COURSEMANAGERLOWSECURITY)); ?>
		</td><?php
		if(!api_is_allowed_to_edit()) {?>
			<td>
			<?php
			$comm = get_comments($id,$questionId);
			?>
			</td>
			</tr>
	<?php }
}
/**
 * Shows the answer to a free-answer question, as HTML
 * @param string    Answer text
 * @param int       Exercise ID
 * @param int       Question ID
 * @return void
 */
function display_free_answer($answer,$id,$questionId) {
	?>
		<tr>
		<td>
			<?php echo nl2br(Security::remove_XSS($answer,COURSEMANAGERLOWSECURITY)); ?>
		</td> <?php if(!api_is_allowed_to_edit()) {?>
        <td>
        <?php        
        $comm = get_comments($id,$questionId);
        ?>
        </td> 
    <?php }?>
    </tr>
    <?php
}
/**
 * Displays the answer to a hotspot question
 *
 * @param int $answerId
 * @param string $answer
 * @param string $studentChoice
 * @param string $answerComment
 */
function display_hotspot_answer($answerId, $answer, $studentChoice, $correctComment) {
	//global $hotspot_colors;
	$hotspot_colors = array("", // $i starts from 1 on next loop (ugly fix)
            						"#4271B5",
									"#FE8E16",
									"#3B3B3B",
									"#BCD631",
									"#D63173",
									"#D7D7D7",
									"#90AFDD",
									"#AF8640",
									"#4F9242",
									"#F4EB24",
									"#ED2024",
									"#45C7F0",
									"#F7BDE2");
	?>
	<tr>
		<td width="100px" valign="top" align="left">
			<div style="width:100%;">
				<div style="height:11px; width:11px; background-color:<?php echo $hotspot_colors[$answerId]; ?>; display:inline; float:left; margin-top:3px;"></div>
				<div style="float:left; padding-left:5px;">
				<?php echo $answerId; ?>
				</div>
				<div><?php echo '&nbsp;'.$answer ?></div>
			</div>
		</td>
		<td width="50px" style="padding-right:15px" valign="top" align="left">
			<?php 
			$my_choice = ($studentChoice)?get_lang('Correct'):get_lang('Fault'); echo $my_choice; ?>
		</td>
		
		<td valign="top" align="left" >
			<?php			
			if($studentChoice) {
				echo '<span>'.nl2br(make_clickable($correctComment[0])).'</span>';							
			} else {
				echo '<span>'.nl2br(make_clickable($correctComment[1])).'</span>';				
			} 
			?>
		</td>	
	</tr>
	<?php
}

function display_hotspot_delineation_answer($answerId, $answer, $studentChoice, $answerComment)
{
	//global $hotspot_colors;
	$hotspot_colors = array("", // $i starts from 1 on next loop (ugly fix)
            						"#4271B5",
									"#FE8E16",
									"#3B3B3B",
									"#BCD631",
									"#D63173",
									"#D7D7D7",
									"#90AFDD",
									"#AF8640",
									"#4F9242",
									"#F4EB24",
									"#ED2024",
									"#45C7F0",
									"#F7BDE2");
	?>
		<tr>
				<td valign="top" align="left">
					<div style="width:100%;">
						<div style="height:11px; width:11px; background-color:<?php echo $hotspot_colors[$answerId]; ?>; float:left; margin:3px;"></div>
						<div><?php echo $answer ?></div>
					</div>
				</td>
				<td valign="top" align="left"></td>
			
		</tr>
	<?php
}

/*
==============================================================================
		MAIN CODE
==============================================================================
*/

if ($origin != 'learnpath') {    
        echo '<div class="actions">';        
        if (api_is_course_admin()) {
            echo '<a href="'.api_get_path(WEB_CODE_PATH).'exercice/admin.php?'.api_get_cidreq().'&exerciseId='.$exercise_id.'">' . Display::return_icon('pixel.gif', get_lang('GoBackToEx'), array('class' => 'toolactionplaceholdericon toolactionback')) . get_lang('GoBackToEx').'</a>';
            echo '<a href="'.api_get_path(WEB_CODE_PATH).'exercice/exercise_admin.php?scenario=yes&modifyExercise=yes&' . api_get_cidreq() . '&exerciseId='.$exercise_id.'">' . Display::return_icon('pixel.gif', get_lang('Scenario'), array('class' => 'toolactionplaceholdericon toolactionscenario')) . get_lang('Scenario') . '</a>';
        } else {
            echo '<a href="'.api_get_path(WEB_CODE_PATH).'exercice/exercice.php?'.api_get_cidreq().'">' . Display::return_icon('pixel.gif', get_lang('GoBackToEx'), array('class' => 'toolactionplaceholdericon toolactionback')) . get_lang('GoBackToEx').'</a>';
        }
	echo '</div>';
}
?>
 <div id="content">
<table width="100%" border="0" cellspacing="0" cellpadding="0">
  <tr>
    <td colspan="2">
<?php
$sql_test_name='SELECT title, description, results_disabled FROM '.$TBL_EXERCICES.' as exercises, '.$TBL_TRACK_EXERCICES.' as track_exercises WHERE exercises.id=track_exercises.exe_exo_id AND track_exercises.exe_id="'.Database::escape_string($id).'"';
$result=api_sql_query($sql_test_name);
$show_results = true;
// Avoiding the "Score 0/0" message  when the exe_id is not set 	
if (Database::num_rows($result)>0 && isset($id)) {
	$test=Database::result($result,0,0);		
	$exerciseTitle=api_parse_tex($test);
	$exerciseDescription=Database::result($result,0,1);
	
	// if the results_disabled of the Quiz is 1 when block the script	
	$result_disabled=Database::result($result,0,2);
	if (!(api_is_platform_admin() || api_is_course_admin()) ) {			
		if ($result_disabled==1) {			
			//api_not_allowed();
			$show_results = false;
			//Display::display_warning_message(get_lang('CantViewResults'));
			if ($origin!='learnpath') {
				echo '<div class="quiz_content_actions">'.get_lang('ThankYouForPassingTheTest').'<br /><br /><a href="exercice.php">'.(get_lang('BackToExercisesList')).'</a></div>';
			//	Display::display_warning_message(get_lang('ThankYouForPassingTheTest').'<br /><br /><a href="exercice.php">'.(get_lang('BackToExercisesList')).'</a>', false);						
				echo '</td>
				</tr>
				</table>';
			}	
		}
	}
	if ($show_results == true) {
		$user_restriction = $is_allowedToEdit ? '' :  "AND user_id=".intval($_user['user_id'])." ";
		$query = "SELECT attempts.question_id, answer  from ".$TBL_TRACK_ATTEMPT." as attempts  
						INNER JOIN ".$TBL_TRACK_EXERCICES." as stats_exercices ON stats_exercices.exe_id=attempts.exe_id 
						INNER JOIN ".$TBL_QUESTIONS." as questions ON questions.id=attempts.question_id    
				  WHERE attempts.exe_id='".Database::escape_string($id)."' $user_restriction
				  GROUP BY attempts.question_id"; 
					//GROUP BY questions.position, attempts.question_id";					
		$result =api_sql_query($query, __FILE__, __LINE__);
	}							
} else {
	Display::display_warning_message(get_lang('CantViewResults'));
	$show_results = false;
	echo '</td>
	</tr>
	</table>';
}		
if ($origin == 'learnpath' && !isset($_GET['fb_type']) ) {
	$show_results = false;
}
	
if ($show_results == true ) {			
	?>
	<!--<div style="padding:0px 0px 20px 30px;">-->
	<table width="100%" class="actions">
		<tr>
			<td style="font-weight:bold" width="10%"><div class="actions-message" align="right"><?php echo '&nbsp;'.get_lang('CourseTitle')?> : </div></td>
			<td><div class="actions-message" width="90%"><?php echo $_course['name'] ?></div></td>
		</tr>
		<tr>
			<td style="font-weight:bold" width="10%"><div class="actions-message" align="right"><?php echo '&nbsp;'.get_lang('User')?> : </div></td>
			<td><div class="actions-message" width="90%"><?php
			if (isset($_GET['cidReq'])) {
				$course_code=Security::remove_XSS($_GET['cidReq']);
			} else {
				$course_code=api_get_course_id();	
			}
			if (isset($_GET['student'])) {
				$user_id=Security::remove_XSS($_GET['student']);
			}else {
				$user_id=api_get_user_id();
			}

			$status_info=CourseManager::get_user_in_course_status($user_id,$course_code);
			if (STUDENT==$status_info) {
				$user_info=api_get_user_info($user_id); 
				echo $user_info['firstName'].' '.$user_info['lastName'];
			} elseif(COURSEMANAGER==$status_info && !isset($_GET['user'])) {
				$user_info=api_get_user_info($user_id); 
				echo $user_info['firstName'].' '.$user_info['lastName'];
			} else {
				echo $user_name;
			}

			?></div></td>
		</tr>
		<tr>
			<td style="font-weight:bold" width="10%" class="actions-message" align="right">
				<?php echo '&nbsp;'.get_lang("Exercise").' :'; ?>
			</td>
			<td width="90%">
			<?php echo $test; ?><br />
			<?php echo $exerciseDescription; ?>
			</td>
		</tr>
	</table>	 
	<br />
	</table>	
  	<?php
}
$i=$totalScore=$totalWeighting=0;

if($debug>0){echo "ExerciseResult: "; var_dump($exerciseResult); echo "QuestionList: ";var_dump($questionList);}

if ($show_results) {
	$questionList = array();
	$exerciseResult = array();
	$k=0;
	$counter=0;
	while ($row = Database::fetch_array($result)) {
		$questionList[] = $row['question_id'];
		$exerciseResult[] = $row['answer'];
	}
	// for each question
	foreach($questionList as $questionId) {
		$counter++;
		$k++;
		$choice=$exerciseResult[$questionId];
		// creates a temporary Question object
		$objQuestionTmp = Question::read($questionId);
		$questionName=$objQuestionTmp->selectTitle();
		$questionDescription=$objQuestionTmp->selectDescription();
		$questionWeighting=$objQuestionTmp->selectWeighting();
		$answerType=$objQuestionTmp->selectType();
		$quesId =$objQuestionTmp->selectId(); //added by priya saini
		$mediaPosition = $objQuestionTmp->selectMediaPosition();

		// destruction of the Question object
		unset($objQuestionTmp);

		if($answerType == UNIQUE_ANSWER || $answerType == MULTIPLE_ANSWER || $answerType == REASONING) {
			$colspan=2;
		}			
		if($answerType == MATCHING || $answerType == FREE_ANSWER) {
			$colspan=2;
		} else {
			$colspan=2;
		}
		?>	
		<div style="padding:0px 0px 20px 0px;"><div class="rounded" style="width: 100%; padding: 1px; background-color:#ccc;"><table class="rounded_inner" style="width: 100%; background-color:#fff;"><tr><td>
    	<div id="question_title" class="quiz_report_content">
    		<?php echo get_lang("Question").' '.($counter).' : '.$questionName; ?>
    	</div>	   
  <!--	<div id="question_description" class="scroll_feedback">
    		<?php echo $questionDescription; ?>
    	</div>-->

	 	<?php
		$s = '';
		if(!empty($questionDescription)){
			if($mediaPosition == 'top'){
			$s .= '<div align="center"><div class="quiz_content_actions" style="width:40%;">'.$questionDescription.'</div></div>';
			}
			elseif($mediaPosition == 'right'){
			$s .= '<div class="quiz_content_actions" style="width:40%;float:right">'.$questionDescription.'</div>';
			}
		}
		if ($answerType == MULTIPLE_ANSWER) {
			$choice=array();
			if($mediaPosition == 'top' || $mediaPosition == 'nomedia' || empty($questionDescription)){
			$s .= '<div class="quiz_content_actions" style="width:95%;float:left;">';
			}
			elseif($mediaPosition == 'right'){
			$s .= '<div class="quiz_content_actions" style="width:52%;float:left;height:auto;min-height:350px;">';
			}
			$s .= '<table width="100%" border="0" class="data_table"><tr class="row_odd"><td>'.get_lang("Choice").'</td><td>'.get_lang("ExpectedChoice").'</td><td>'.get_lang("Answer").'</td></tr>';

			?>
	<!--	<table width="100%" border="0" cellspacing="3" cellpadding="3" align="center" class="feedback_actions">
			<tr>
			<td>&nbsp;</td>
			</tr>
			<tr>
				<td><i><?php echo get_lang("Choice"); ?></i> </td>
				<td><i><?php echo get_lang("ExpectedChoice"); ?></i></td>
				<td><i><?php echo get_lang("Answer"); ?></i></td>
			    <td><i><?php echo get_lang("Comment"); ?></i></td>		
			</tr>
			<tr>
			<td>&nbsp;</td>
			</tr>-->
			<?php
			// construction of the Answer object
			$objAnswerTmp=new Answer($questionId);
			$nbrAnswers=$objAnswerTmp->selectNbrAnswers();
			$questionScore=0;
			$correctChoice = 'N';
			$answerWrong = 'N';
			for ($answerId=1;$answerId <= $nbrAnswers;$answerId++) {
				$answer=$objAnswerTmp->selectAnswer($answerId);
				$answerComment=$objAnswerTmp->selectComment($answerId);
				$correctComment[] =$objAnswerTmp->selectComment($answerId);
				$answerCorrect=$objAnswerTmp->isCorrect($answerId);
				$answerWeighting=$objAnswerTmp->selectWeighting($answerId);
				$queryans = "select * from ".$TBL_TRACK_ATTEMPT." where exe_id = '".Database::escape_string($id)."' and question_id= '".Database::escape_string($questionId)."'";
				$resultans = api_sql_query($queryans, __FILE__, __LINE__);
				while ($row = Database::fetch_array($resultans)) {
					$ind = $row['answer'];
					$choice[$ind] = 1;
				}
				$studentChoice=$choice[$answerId];
				if ($studentChoice) {
					$questionScore+=$answerWeighting;
					$totalScore+=$answerWeighting;
					if($studentChoice == $answerCorrect)
					{					
					$correctChoice = 'Y';
					$feedback_if_true = $objAnswerTmp->selectComment($answerId);
					}
					else
					{		
					$answerWrong = 'Y';
					$feedback_if_false = $objAnswerTmp->selectComment($answerId);
					}
				}
				
			/*	echo '<tr><td>';
				if ($answerId==1) {
						display_unique_or_multiple_answer($answerType, $studentChoice, $answer, $answerComment, $answerCorrect,$id,$questionId,$answerId);
				} else {
						display_unique_or_multiple_answer($answerType, $studentChoice, $answer, $answerComment, $answerCorrect,$id,$questionId,"");
				}
				echo '</td></tr>';*/

				if ($answerId==1) {
					$s .= display_unique_or_multiple_or_reasoning_answer($answerType, $studentChoice, $answer, $answerComment, $answerCorrect,$id,$questionId,$answerId);
				} else {
					$s .= display_unique_or_multiple_or_reasoning_answer($answerType, $studentChoice, $answer, $answerComment, $answerCorrect,$id,$questionId,"");
				}

				$i++;
		 	}
		/*	echo '<tr><td colspan="3">';
			if($correctChoice == 'Y' && $answerWrong == 'N')
			{
				echo '<br/><b>'.get_lang('Feedback').'  </b><br/><br/><span>'.nl2br(make_clickable($feedback_if_true)).'</span>';	
			}
			else
			{
				echo '<br/><b>'.get_lang('Feedback').'  </b><br/><br/><span>'.nl2br(make_clickable($feedback_if_false)).'</span>';
			}
			echo '</td></tr>';
		 	echo '</table>';*/

			$s .= '<tr><td colspan="3">&nbsp;</td></tr>';
			if($correctChoice == 'Y' && $answerWrong == 'N') {
				if (empty($feedback_if_true)) {
					$feedback_if_true = get_lang('NoTrainerComment');
				}
				$s .= '<tr><td colspan="3"><b>' . get_lang('Feedback') . '</b></td></tr><tr><td colspan="3">' . $feedback_if_true . '</td></tr>';
			} else {
				if (empty($feedback_if_false)) {
					$feedback_if_false = get_lang('NoTrainerComment');
				}
				$s .= '<tr><td colspan="3"><b>' . get_lang('Feedback') . '</b></td></tr><tr><td colspan="3">' . $feedback_if_false . '</td></tr>';
			}
					
			$s .= '</table></div>';
			echo $s;

		} elseif ($answerType == REASONING) {
			$choice=array();

			if($mediaPosition == 'top' || $mediaPosition == 'nomedia' || empty($questionDescription)){
			$s .= '<div class="quiz_content_actions" style="width:95%;float:left;">';
			}
			elseif($mediaPosition == 'right'){
			$s .= '<div class="quiz_content_actions" style="width:52%;float:left;">';
			}
			$s .= '<table width="100%" border="0" class="data_table"><tr class="row_odd"><td>'.get_lang("Choice").'</td><td>'.get_lang("ExpectedChoice").'</td><td>'.get_lang("Answer").'</td></tr>';
			?>
	<!--	<table width="100%" border="0" cellspacing="3" cellpadding="3" align="center" class="feedback_actions">
			<tr>
			<td>&nbsp;</td>
			</tr>
			<tr>
				<td><i><?php echo get_lang("Choice"); ?></i> </td>
				<td><i><?php echo get_lang("ExpectedChoice"); ?></i></td>
				<td><i><?php echo get_lang("Answer"); ?></i></td>				
			</tr>
			<tr>
			<td>&nbsp;</td>
			</tr>-->
			<?php
			// construction of the Answer object
			$objAnswerTmp=new Answer($questionId);
			$nbrAnswers=$objAnswerTmp->selectNbrAnswers();
			$questionScore=0;
			$correctChoice = 'Y';
			$noStudentChoice='N';
			$answerWrong = 'N';
			for ($answerId=1;$answerId <= $nbrAnswers;$answerId++) {				
				$answer=$objAnswerTmp->selectAnswer($answerId);
				$answerComment=$objAnswerTmp->selectComment($answerId);
				$correctComment[] =$objAnswerTmp->selectComment($answerId);
				$answerCorrect=$objAnswerTmp->isCorrect($answerId);
				$answerWeighting=$objAnswerTmp->selectWeighting($answerId);
				$queryans = "select * from ".$TBL_TRACK_ATTEMPT." where exe_id = '".Database::escape_string($id)."' and question_id= '".Database::escape_string($questionId)."'";
				$resultans = api_sql_query($queryans, __FILE__, __LINE__);
				while ($row = Database::fetch_array($resultans)) {
					$ind = $row['answer'];
					$choice[$ind] = 1;
				}
				$studentChoice=$choice[$answerId];
				
				if($answerCorrect)
			   {
				   $feedback_if_true = $objAnswerTmp->selectComment($answerId);
			   }
			   else
			   {
				   $feedback_if_false = $objAnswerTmp->selectComment($answerId);
			   }
				
				if($answerId == '2')
				{
					$wrongAnswerWeighting = $answerWeighting;
				}
				if($answerCorrect && $studentChoice == '1' && $correctChoice == 'Y')
				{				
					$correctChoice = 'Y';
					$noStudentChoice = 'Y';
				}
				elseif($answerCorrect && !$studentChoice)
				{				
					$correctChoice = 'N';
					$noStudentChoice = 'Y';	
					$answerWrong = 'Y';	
				}
				elseif(!$answerCorrect && $studentChoice == '1')
				{				
					$correctChoice = 'N';
					$noStudentChoice = 'Y';		
					$answerWrong = 'Y';	
				}	
			/*	echo '<tr><td>';
				if ($answerId==1) {
						display_reasoning_answer($answerType, $studentChoice, $answer, $answerComment, $answerCorrect,$id,$questionId,$answerId);
				} else {
						display_reasoning_answer($answerType, $studentChoice, $answer, $answerComment, $answerCorrect,$id,$questionId,"");
				}				
				echo '</td></tr>';*/

				if ($answerId==1) {
						$s .= display_unique_or_multiple_or_reasoning_answer($answerType, $studentChoice, $answer, $answerComment, $answerCorrect,$id,$questionId,$answerId);
				} else {
						$s .= display_unique_or_multiple_or_reasoning_answer($answerType, $studentChoice, $answer, $answerComment, $answerCorrect,$id,$questionId,"");
				}	
				$i++;
		 	}
			if ($answerType == REASONING  && $noStudentChoice == 'Y'){						
						if($correctChoice == 'Y')
						{						
						$questionScore += $questionWeighting;
						$totalScore += $questionWeighting;
						}
						else
						{						
						$questionScore += $wrongAnswerWeighting;
						$totalScore += $wrongAnswerWeighting;
						}
					}	
					
		/*	echo '<tr><td colspan="3">';
			if($correctChoice == 'Y' && $answerWrong == 'N')
			{
				echo '<b>'.get_lang('Feedback').' - '.get_lang('FeedbackReason').'  </b><span>'.nl2br(make_clickable($feedback_if_true)).'</span>';	
			}
			else
			{
				echo '<b>'.get_lang('Feedback').' - '.get_lang('FeedbackReason').'  </b><span>'.nl2br(make_clickable($feedback_if_false)).'</span>';
			}
			echo '</td></tr>';
		 	echo '</table>';*/

			$s .= '<tr><td colspan="3">&nbsp;</td></tr>';
			if ($correctChoice == 'Y' && $answerWrong == 'N') {
				if (empty($feedback_if_true)) {
					$feedback_if_true = get_lang('NoTrainerComment');
				}
				$s .= '<tr><td colspan="3"><b>' . get_lang('Feedback') . '</b></td></tr><tr><td colspan="3">' . $feedback_if_true . '</td></tr>';
			} else {
				if (empty($feedback_if_false)) {
					$feedback_if_false = get_lang('NoTrainerComment');
				}
				$s .= '<tr><td colspan="3"><b>' . get_lang('Feedback') . '</b></td></tr><tr><td colspan="3">' . $feedback_if_false . '</td></tr>';
			}
					
			$s .= '</table></div>';
			echo $s;

		}  elseif ($answerType == UNIQUE_ANSWER) {

			if($mediaPosition == 'top' || $mediaPosition == 'nomedia' || empty($questionDescription)){
			$s .= '<div class="quiz_content_actions" style="width:95%;float:left;">';
			}
			elseif($mediaPosition == 'right'){
			$s .= '<div class="quiz_content_actions" style="width:52%;float:left;height:auto;min-height:300px;">';
			}
			$s .= '<table width="100%" border="0" class="data_table"><tr class="row_odd"><td>'.get_lang("Choice").'</td><td>'.get_lang("ExpectedChoice").'</td><td>'.get_lang("Answer").'</td></tr>';
			?>
		<!--<table width="100%" border="0" cellspacing="3" cellpadding="3" align="center" class="feedback_actions">
				<tr>
				<td>&nbsp;</td>
				</tr>
				<tr>
					<td><i><?php echo get_lang("Choice"); ?></i> </td>
					<td><i><?php echo get_lang("ExpectedChoice"); ?></i></td>
					<td><i><?php echo get_lang("Answer"); ?></i></td>					
				</tr>
				<tr>
				<td>&nbsp;</td>
				</tr>-->
			<?php
			$objAnswerTmp=new Answer($questionId);
			$nbrAnswers=$objAnswerTmp->selectNbrAnswers();
			$questionScore=0;
			$correctChoice = 'N';
			$correctComment = array();
			for ($answerId=1;$answerId <= $nbrAnswers;$answerId++) {				
				$answer=$objAnswerTmp->selectAnswer($answerId);
				$answerComment=$objAnswerTmp->selectComment($answerId);
				$correctComment[] =$objAnswerTmp->selectComment($answerId);
				$answerCorrect=$objAnswerTmp->isCorrect($answerId);
				$answerWeighting=$objAnswerTmp->selectWeighting($answerId);
				$queryans = "select answer from ".$TBL_TRACK_ATTEMPT." where exe_id = '".Database::escape_string($id)."' and question_id= '".Database::escape_string($questionId)."'";
				$resultans = api_sql_query($queryans, __FILE__, __LINE__);
				$choice = Database::result($resultans,0,"answer");
				$studentChoice=($choice == $answerId)?1:0;
				if ($studentChoice) {
				  	$questionScore+=$answerWeighting;
					$totalScore+=$answerWeighting;
					if($studentChoice == $answerCorrect)
					{					
					$correctChoice = 'Y';
					$feedback_if_true = $objAnswerTmp->selectComment($answerId);
					}
					else
				   {
					   $feedback_if_false = $objAnswerTmp->selectComment($answerId);
				   }
				}
		/*		echo '<tr><td>';
				if ($answerId==1) {
					display_unique_or_multiple_answer($answerType, $studentChoice, $answer, $answerComment, $answerCorrect,$id,$questionId,$answerId);
				} else {
					display_unique_or_multiple_answer($answerType, $studentChoice, $answer, $answerComment, $answerCorrect,$id,$questionId,"");
				}						
				echo '</td></tr>';*/

				if ($answerId==1) {
					$s .= display_unique_or_multiple_or_reasoning_answer($answerType, $studentChoice, $answer, $answerComment, $answerCorrect,$id,$questionId,$answerId);
				} else {
					$s .= display_unique_or_multiple_or_reasoning_answer($answerType, $studentChoice, $answer, $answerComment, $answerCorrect,$id,$questionId,"");
				}
				$i++;
			}
	/*		echo '<tr><td colspan="3">';
			if($correctChoice == 'Y')
			{
				echo '<b>'.get_lang('Feedback').'  </b><span>'.nl2br(make_clickable($feedback_if_true)).'</span>';	
			}
			else
			{
				echo '<b>'.get_lang('Feedback').'  </b><span>'.nl2br(make_clickable($feedback_if_false)).'</span>';
			}
			echo '</td></tr>';
			echo '</table>';*/

			$s .= '<tr><td colspan="3">&nbsp;</td></tr>';
			if ($correctChoice == 'Y') {
				if (empty($feedback_if_true)) {
					$feedback_if_true = get_lang('NoTrainerComment');
				}
				$s .= '<tr><td colspan="3"><b>' . get_lang('Feedback') . '</b></td></tr><tr><td colspan="3">' . $feedback_if_true . '</td></tr>';
			} else {
				if (empty($feedback_if_false)) {
					$feedback_if_false = get_lang('NoTrainerComment');
				}
				$s .= '<tr><td colspan="3"><b>' . get_lang('Feedback') . '</b></td></tr><tr><td colspan="3">' . $feedback_if_false . '</td></tr>';
			}
					
			$s .= '</table></div>';
			echo $s;

		} elseif ($answerType == FILL_IN_BLANKS) {

			?>
	<!--	<table  border="0" cellspacing="3" cellpadding="3" align="center" class="feedback_actions" style="width:98%;">
			<tr>
			<td>&nbsp;</td>
			</tr>
			<tr>
			<td><i><?php echo get_lang("Answer"); ?></i> </td>
			</tr>
			<tr>
			<td>&nbsp;</td>
			</tr>-->
			<?php
			$objAnswerTmp=new Answer($questionId);
			$nbrAnswers=$objAnswerTmp->selectNbrAnswers();
			$questionScore=0;
			$feedback_data = unserialize($objAnswerTmp -> comment[1]);
		    $feedback_true = $feedback_data['comment[1]'];
		    $feedback_false = $feedback_data['comment[2]'];
			for ($answerId=1;$answerId <= $nbrAnswers;$answerId++) {
				$answer = $objAnswerTmp->selectAnswer($answerId);
				$answerComment = $objAnswerTmp->selectComment($answerId);
				$answerCorrect = $objAnswerTmp->isCorrect($answerId);
				$answerWeighting = $objAnswerTmp->selectWeighting($answerId);
				
			    // the question is encoded like this
			    // [A] B [C] D [E] F::10,10,10@1
			    // number 1 before the "@" means that is a switchable fill in blank question
			    // [A] B [C] D [E] F::10,10,10@ or  [A] B [C] D [E] F::10,10,10
			    // means that is a normal fill blank question

				$pre_array = explode('::', $answer);	
				
				// is switchable fill blank or not			
				$is_set_switchable = explode('@', $pre_array[1]);
				$switchable_answer_set=false;
				if ($is_set_switchable[1]==1) {
					$switchable_answer_set=true;
				}								
								
				$answer = $pre_array[0];
				
				// splits weightings that are joined with a comma
				$answerWeighting = explode(',',$is_set_switchable[0]);			
				//list($answer,$answerWeighting)=explode('::',$multiple[0]);				
				
				//$answerWeighting=explode(',',$answerWeighting);
				// we save the answer because it will be modified
			    $temp=$answer;
		
				// TeX parsing
				// 1. find everything between the [tex] and [/tex] tags
				$startlocations=api_strpos($temp,'[tex]');
				$endlocations=api_strpos($temp,'[/tex]');
				if ($startlocations !== false && $endlocations !== false) {
					$texstring=api_substr($temp,$startlocations,$endlocations-$startlocations+6);
					// 2. replace this by {texcode}
					$temp=str_replace($texstring,'{texcode}',$temp);
				}
				$j=0;
				// the loop will stop at the end of the text
				$i=0;
				$feedback_anscorrect = array();
				$feedback_usertag = array();
				$feedback_correcttag = array();
				//normal fill in blank
				if (!$switchable_answer_set) {
					while (1) {
						// quits the loop if there are no more blanks
						if (($pos = api_strpos($temp,'[')) === false) {
							// adds the end of the text
							$answer.=$temp;				
							// TeX parsing
							$texstring = api_parse_tex($texstring);
							break;
						}
					    $temp=api_substr($temp,$pos+1);
						// quits the loop if there are no more blanks
						if (($pos = api_strpos($temp,']')) === false) {
							break;
						}
						
						$queryfill = "select answer from ".$TBL_TRACK_ATTEMPT." where exe_id = '".Database::escape_string($id)."' and question_id= '".Database::escape_string($questionId)."'";
						$resfill = api_sql_query($queryfill, __FILE__, __LINE__);
						$str = Database::result($resfill,0,"answer");
						$str = str_replace("<br />","",$str);

						preg_match_all('#\[([^[]*)\]#', $str, $arr);
						$choice = $arr[1];
						$tmp=strrpos($choice[$j],' / ');
						$choice[$j]=substr($choice[$j],0,$tmp);
						$choice[$j]=trim($choice[$j]);
						$choice[$j]=stripslashes($choice[$j]);
						$feedback_usertag[] = $choice[$j];
						$feedback_correcttag[] = api_strtolower(api_substr($temp,0,$pos));

												
						// if the word entered by the student IS the same as the one defined by the professor
						if (trim(api_strtolower(api_substr($temp,0,$pos))) == trim(api_strtolower($choice[$j]))) {
							$feedback_anscorrect[] = "Y";
							// gives the related weighting to the student
							$questionScore+=$answerWeighting[$j];
							// increments total score
							$totalScore+=$answerWeighting[$j];
						}
						else
						{
							$feedback_anscorrect[] = "N";
						}
						// else if the word entered by the student IS NOT the same as the one defined by the professor
						$j++;
						$temp=api_substr($temp,$pos+1);
						$i=$i+1;
					}
					$answer = stripslashes($str);
					
				} else {	
					//multiple fill in blank	
					while (1) {
						// quits the loop if there are no more blanks
						if (($pos = api_strpos($temp,'[')) === false) {
							// adds the end of the text
							$answer.=$temp;
							// TeX parsing
							$texstring = api_parse_tex($texstring);
							//$answer=str_replace("{texcode}",$texstring,$answer);
							break;
						}
						// adds the piece of text that is before the blank and ended by [
						$real_text[]=api_substr($temp,0,$pos+1);
						$answer.=api_substr($temp,0,$pos+1);					
						$temp=api_substr($temp,$pos+1);

						// quits the loop if there are no more blanks
						if (($pos = api_strpos($temp,']')) === false) {
							// adds the end of the text
							//$answer.=$temp;
							break;
						}

						$queryfill = "SELECT answer FROM ".$TBL_TRACK_ATTEMPT." WHERE exe_id = '".Database::escape_string($id)."' and question_id= '".Database::escape_string($questionId)."'";
						$resfill = api_sql_query($queryfill, __FILE__, __LINE__);
						$str=Database::result($resfill,0,"answer");
						$str = str_replace("<br />","",$str);

						preg_match_all ('#\[([^[/]*)/#', $str, $arr);
						$choice = $arr[1];
						
						$choice[$j]=trim($choice[$j]);					
						$user_tags[]=api_strtolower($choice[$j]);
						$correct_tags[]=api_strtolower(api_substr($temp,0,$pos));	
						
						$j++;
						$temp=api_substr($temp,$pos+1);
						$i=$i+1;
					}
					$answer='';
					for ($i=0;$i<count($correct_tags);$i++) {		 							
						if (in_array($user_tags[$i],$correct_tags)) {
							// gives the related weighting to the student
							$questionScore+=$answerWeighting[$i];
							// increments total score
							$totalScore+=$answerWeighting[$i];
						}					
					}
					$answer = stripslashes($str);
					$answer = str_replace('rn', '', $answer);
				}
				//echo $questionScore."-".$totalScore;
			//	echo '<tr><td>';	
			//	display_fill_in_blanks_answer($answer,$id,$questionId);
			//	echo '<table align="center" width="100%"><tr><td colspan="3">' . $answer . '</td></tr>';
			//	echo '<table align="center" width="100%"><tr><td colspan="3"><div class="scroll_feedback">' . $answer . '</div></td></tr>';
			/*	echo '<div class="scroll_feedback">' . $answer . '</div>';
				echo '<table align="center" width="100%">';
				for($k=0;$k<sizeof($feedback_anscorrect);$k++)
				{
					echo '<tr><td>'.$feedback_usertag[$k].' / '.$feedback_correcttag[$k].'</td>';
					if($feedback_anscorrect[$k] == "Y")
					{
						echo '<td><img src="../img/Right32tr.png" style="vertical-align:middle;">&nbsp;'.get_lang('Right').'</td><td>'.$feedback_true.'</td></tr>';
					}
					else
					{
						echo '<td><img src="../img/Wrong32tr.png" style="vertical-align:middle;">&nbsp;'.get_lang('Wrong').'</td><td>'.$feedback_false.'</td></tr>';
					}				
				}
				echo '</table>';
				echo '</td></tr>';*/

				if($mediaPosition == 'top' || $mediaPosition == 'nomedia' || empty($questionDescription)){
				$s .= '<div class="quiz_content_actions" style="width:95%;float:left;">';
				}
				elseif($mediaPosition == 'right'){
				$s .= '<div class="quiz_content_actions" style="width:52%;float:left;height:auto;min-height:300px;">';
				}
				$s .= '<div class="scroll_feedback"><b>' . $answer . '</b></div>';
				$s .= '<table width="100%" border="0"><tr><td colspan="3"><b>'.get_lang('Feedaback').'</b></td></tr>';
				for ($k = 0; $k < sizeof($feedback_anscorrect); $k++) {
					$s .= '<tr><td>' . $feedback_usertag[$k] . ' / ' . $feedback_correcttag[$k] . '</td>';
					if ($feedback_anscorrect[$k] == "Y") {
						$s .= '<td><img src="../img/Right32tr.png" style="vertical-align:middle;">&nbsp;' . get_lang('Right') . '</td><td>' . $feedback_true . '</td></tr>';
					} else {
						$s .= '<td><img src="../img/Wrong32tr.png" style="vertical-align:middle;">&nbsp;' . get_lang('Wrong') . '</td><td>' . $feedback_false . '</td></tr>';
					}
				}
				$s .= '</table></div>';
				$i++;
			}
			echo $s;
		//	echo '</table>';
		} elseif ($answerType == FREE_ANSWER) {

			$answer = $str;		
			if($mediaPosition == 'top' || $mediaPosition == 'nomedia' || empty($questionDescription)){
			$s .= '<div class="quiz_content_actions" style="width:95%;float:left;">';
			}
			elseif($mediaPosition == 'right'){
			$s .= '<div class="quiz_content_actions" style="width:52%;float:left;height:auto;min-height:300px;">';
			}
			echo $s;
			?>
			<table border="0" cellspacing="3" cellpadding="3" align="center" class="feedback_actions" style="width:98%;">
			<tr>
			<td>&nbsp;</td>
			</tr>
			<tr>
			<td><i><?php echo get_lang("Answer"); ?></i> </td>
			</tr>
			<tr>
			<td>&nbsp;</td>
			</tr>

			<?php
			$objAnswerTmp = new Answer($questionId);
			$nbrAnswers = $objAnswerTmp->selectNbrAnswers();
			$questionScore = 0;
			$query = "select answer, marks from ".$TBL_TRACK_ATTEMPT." where exe_id = '".Database::escape_string($id)."' and question_id= '".Database::escape_string($questionId)."'";
			$resq = api_sql_query($query);
			$choice = Database::result($resq,0,"answer");
			$choice = stripslashes($choice);
			$choice = str_replace('rn', '', $choice);
			
			$questionScore = Database::result($resq,0,"marks");
			if ($questionScore==-1) {
				$totalScore+=0;
			} else {
				$totalScore+=$questionScore;
			}
			echo '<tr>
			<td valign="top">'.display_free_answer($choice, $id, $questionId).'</td>
			</tr>
			</table>';

		} elseif ($answerType == MATCHING) {

			$objAnswerTmp=new Answer($questionId);	
			$answerComment_true=$objAnswerTmp->selectComment(1);
			$answerComment_false=$objAnswerTmp->selectComment(2);
			$table_ans = Database :: get_course_table(TABLE_QUIZ_ANSWER);
			$TBL_TRACK_ATTEMPT= Database::get_statistic_table(TABLE_STATISTIC_TRACK_E_ATTEMPT);
			$answer_ok = 'N';
			$answer_wrong = 'N';
			$sql_select_answer = 'SELECT id, answer, correct, position FROM '.$table_ans.' WHERE question_id="'.Database::escape_string($questionId).'" AND correct<>0';		
			$sql_answer = 'SELECT position, answer FROM '.$table_ans.' WHERE question_id="'.Database::escape_string($questionId).'" AND correct=0';
			$res_answer = api_sql_query($sql_answer, __FILE__, __LINE__);
			// getting the real answer
			$real_list =array();		
			while ($real_answer = Database::fetch_array($res_answer)) {			
				$real_list[$real_answer['position']]= $real_answer['answer'];
			}	
			
			$res_answers = api_sql_query($sql_select_answer, __FILE__, __LINE__);
			
			echo '<table border="0" cellspacing="3" cellpadding="3" align="center" class="quiz_content_actions" style="width:98%;">';
			echo '<tr><td colspan="3">&nbsp;</td></tr>';
			echo '<tr>
					<td align="center" width="30%"><span style="font-style: italic;color:#4171B5;font-weight:bold;">'.get_lang("ElementList").'</span> </td>		
					<td align="center" width="35%"><span style="font-style: italic;color:#4171B5;font-weight:bold;">'.get_lang("YourAnswers").'</span></td>
					<td align="center" width="35%"><span style="font-style: italic;color:#4171B5;font-weight:bold;">'.get_lang("CorrectAnswers").'</span></td>
				  </tr>';
			echo '<tr><td colspan="3">&nbsp;</td></tr>';
			
			$questionScore=0;
			
			while ($a_answers = Database::fetch_array($res_answers)) {			
				$i_answer_id = $a_answers['id']; //3
				$s_answer_label = $a_answers['answer'];  // your dady - you mother			
				$i_answer_correct_answer = $a_answers['correct']; //1 - 2			
				$i_answer_position = $a_answers['position']; // 3 - 4
				
				$sql_user_answer = 
						'SELECT answers.answer 
						FROM '.$TBL_TRACK_ATTEMPT.' as track_e_attempt 
						INNER JOIN '.$table_ans.' as answers 
							ON answers.position = track_e_attempt.answer
							AND track_e_attempt.question_id=answers.question_id
						WHERE answers.correct = 0
						AND track_e_attempt.exe_id = "'.Database::escape_string($id).'"
						AND track_e_attempt.question_id = "'.Database::escape_string($questionId).'" 
						AND track_e_attempt.position="'.Database::escape_string($i_answer_position).'"';
				
				
				$res_user_answer = api_sql_query($sql_user_answer, __FILE__, __LINE__);
				if (Database::num_rows($res_user_answer)>0 ) {
					$s_user_answer = Database::result($res_user_answer,0,0); //  rich - good looking
				} else { 
					$s_user_answer = '';
				}
				
				//$s_correct_answer = $s_answer_label; // your ddady - your mother
				$s_correct_answer = $real_list[$i_answer_correct_answer];
				
				$i_answerWeighting=$objAnswerTmp->selectWeighting($i_answer_id);
				
				//if($s_user_answer == $s_correct_answer) // rich == your ddady?? wrong
				//echo $s_user_answer.' - '.$real_list[$i_answer_correct_answer];
				
				if ($s_user_answer == $real_list[$i_answer_correct_answer]) { // rich == your ddady?? wrong
					$questionScore+=$i_answerWeighting;
					$totalScore+=$i_answerWeighting;
					if($answer_wrong == 'N')
					{
						$answer_ok = 'Y';
					}
				} else {
					$s_user_answer = '<span style="color: #FF0000; text-decoration: line-through;">'.$s_user_answer.'</span>';
					$answer_wrong = 'Y';
				}	
				if($questionScore > 20)
				{
					$questionScore = round($questionScore);
				}
				echo '<tr>';
				echo '<td align="center"><div id="matchresult">'.$s_answer_label.'</div></td><td align="center" width="30%"><div id="matchresult">'.$s_user_answer.'</div></td><td align="center"><div id="matchresult"><b><span>'.$s_correct_answer.'</span></b></div></td>';		
				echo '</tr>';	
			}
			echo '<tr><td><b>'.get_lang('Feedback').'</b></td></tr><tr>';
			if($answer_ok == 'Y' && $answer_wrong == 'N')
			{
				echo '<td>'.$answerComment_true.'</td>';
			}
			else
			{
				echo '<td>'.$answerComment_false.'</td>';
			}
			echo '</tr></table>';
		} elseif ($answerType == HOT_SPOT) {
			$objAnswerTmp = new Answer($questionId);
			$nbrAnswers = $objAnswerTmp->selectNbrAnswers();
			$questionScore = 0;
			$correctComment = array();
			$answerOk = 'N';
			$answerWrong = 'N';

			$hotspot_colors = array("", // $i starts from 1 on next loop (ugly fix)
										"#4271B5",
										"#FE8E16",
										"#3B3B3B",
										"#BCD631",
										"#D63173",
										"#D7D7D7",
										"#90AFDD",
										"#AF8640",
										"#4F9242",
										"#F4EB24",
										"#ED2024",
										"#45C7F0",
										"#F7BDE2");

			$s .= '<table width="100%" border="0"><tr><td><div align="center"><object type="application/x-shockwave-flash" data="../plugin/hotspot/hotspot_solution.swf?modifyAnswers=' . Security::remove_XSS($questionId) . '&exe_id=' . $id . '&from_db=1" width="610" height="410">
				<param name="movie" value="../plugin/hotspot/hotspot_solution.swf?modifyAnswers=' . Security::remove_XSS($questionId) . '&exe_id=' . $id . '&from_db=1" />
			  </object></div></td><td width="40%" valign="top"><div class="quiz_content_actions" style="height:380px;"><div class="quiz_header">'.get_lang('Feedback').'</div><div align="center"><img src="../img/MouseHotspots64.png"></div><br/>';
			 
			 $s .= '<div><table width="90%" border="1" class="data_table">';
			 for ($answerId = 1; $answerId <= $nbrAnswers; $answerId++) {
				$answer = $objAnswerTmp->selectAnswer($answerId);
				$answerComment = $objAnswerTmp->selectComment($answerId);
				$correctComment[] = $objAnswerTmp->selectComment($answerId);
				$answerCorrect = $objAnswerTmp->isCorrect($answerId);
				if ($nbrAnswers == 1) {
					$correctComment = explode("~", $objAnswerTmp->selectComment($answerId));
				} else {
					if ($answerId == 1) {
						$correctComment[] = $objAnswerTmp->selectComment(1);
						$correctComment[] = $objAnswerTmp->selectComment(2);
					} else {
						$correctComment[] = $objAnswerTmp->selectComment($answerId);
					}
				}

				$TBL_TRACK_HOTSPOT = Database::get_statistic_table(TABLE_STATISTIC_TRACK_E_HOTSPOT);
				$query = "select hotspot_correct from " . $TBL_TRACK_HOTSPOT . " where hotspot_exe_id = '" . Database::escape_string($id) . "' and hotspot_question_id= '" . Database::escape_string($questionId) . "' AND hotspot_answer_id='" . Database::escape_string($answerId) . "'";
				$resq = api_sql_query($query);
				$choice = Database::result($resq, 0, "hotspot_correct");
				
				$queryfree = "select marks from ".$TBL_TRACK_ATTEMPT." where exe_id = '".Database::escape_string($id)."' and question_id= '".Database::escape_string($questionId)."'";
				$resfree = api_sql_query($queryfree, __FILE__, __LINE__);
				$questionScore= Database::result($resfree,0,"marks");
				$totalScore+=$questionScore;

				if ($choice) {
					$answerOk = 'Y';
					$img_choice = get_lang('Right');
				} else {
					$answerOk = 'N';
					$answerWrong = 'Y';
					$img_choice = get_lang('Wrong');
				}
				$s .= '<tr><td><div style="height:11px; width:11px; background-color:'.$hotspot_colors[$answerId].'; display:inline; float:left; margin-top:3px;"></div>&nbsp;'.$answerId.'</td><td>'.$answer.'</td><td>'.$img_choice.'</td></tr>';
			 }
			 $s .= '</table></div><br/><br/>';
			 if ($answerOk == 'Y' && $answerWrong == 'N') {
				 if ($nbrAnswers == 1){
					 $feedback = $correctComment[0]; 
				 }
				 else {
					 $feedback = $correctComment[1];  
				 }
			 }
			 else
			 {
				 if ($nbrAnswers == 1){
					 $feedback = $correctComment[1]; 
				 }
				 else {
					 $feedback = $correctComment[2];  
				 }				         
			 }
			 if(!empty($feedback)){
			 $s .= '<div align="center" class="quiz_feedback"><b>'.get_lang('Feedback').'</b> : '.$feedback.'</div>';		 
			 }
			 $s .= '</div></td></tr></table>';
			 echo $s;
		}
		else if($answerType == HOT_SPOT_DELINEATION) {
		$objAnswerTmp=new Answer($questionId);
		$nbrAnswers=$objAnswerTmp->selectNbrAnswers();
		//$nbrAnswers=1; // based in the code found in exercise_show.php
		$questionScore=0;		
		
		//based on exercise_submit modal
		/*  Hot spot delinetion parameters */		
		$choice=$exerciseResult[$questionid];
		$destination=array();
		$comment='';
		$next=1;
		$_SESSION['hotspot_coord']=array();
		$_SESSION['hotspot_dest']=array();
		$overlap_color=$missing_color=$excess_color=false;
		$organs_at_risk_hit=0;

		$final_answer = 0;
				for($answerId=1;$answerId <= $nbrAnswers;$answerId++) {
					
					$answer			=$objAnswerTmp->selectAnswer($answerId);
					$answerComment	=$objAnswerTmp->selectComment($answerId);
					$answerCorrect	=$objAnswerTmp->isCorrect($answerId);
					$answerWeighting=$objAnswerTmp->selectWeighting($answerId);
					
					//delineation						
					$answer_delineation_destination=$objAnswerTmp->selectDestination(1);
					$delineation_cord=$objAnswerTmp->selectHotspotCoordinates(1);					
					
					if ($answerId===1) {					
						$_SESSION['hotspot_coord'][1]=$delineation_cord;
						$_SESSION['hotspot_dest'][1]=$answer_delineation_destination;
					}	
										
					// getting the user answer 
					$TBL_TRACK_HOTSPOT = Database::get_statistic_table(TABLE_STATISTIC_TRACK_E_HOTSPOT);
					$query = "select hotspot_correct, hotspot_coordinate from ".$TBL_TRACK_HOTSPOT." where hotspot_exe_id = '".Database::escape_string($id)."' and hotspot_question_id= '".Database::escape_string($questionId)."' AND hotspot_answer_id='1'"; //by default we take 1 because it's a delineation 
					$resq=api_sql_query($query);
					$row = Database::fetch_array($resq,'ASSOC');
					$choice = $row['hotspot_correct'];
					$user_answer = $row['hotspot_coordinate'];	
					
					$queryfree = "select marks from ".$TBL_TRACK_ATTEMPT." where exe_id = '".Database::escape_string($id)."' and question_id= '".Database::escape_string($questionId)."'";
					$resfree = api_sql_query($queryfree, __FILE__, __LINE__);
					$questionScore= Database::result($resfree,0,"marks");
					$totalScore+=$questionScore;
							
					// THIS is very important otherwise the poly_compile will throw an error!!
					// round-up the coordinates
					$coords = explode('/',$user_answer);
					$user_array = '';
					foreach ($coords as $coord) {
					    list($x,$y) = explode(';',$coord);
					    $user_array .= round($x).';'.round($y).'/';
					}
					$user_array = substr($user_array,0,-1);									
							
					if ($next) {							                    
						//$tbl_track_e_hotspot = Database::get_statistic_table(TABLE_STATISTIC_TRACK_E_HOTSPOT);
								
					// Save into db
					/*	$sql = "INSERT INTO $tbl_track_e_hotspot (hotspot_user_id, hotspot_course_code, hotspot_exe_id, hotspot_question_id, hotspot_answer_id, hotspot_correct, hotspot_coordinate ) 
								VALUES ('".Database::escape_string($_user['user_id'])."', '".Database::escape_string($_course['id'])."', '".Database::escape_string($exeId)."', '".Database::escape_string($questionId)."', '".Database::escape_string($answerId)."', '".Database::escape_string($studentChoice)."', '".Database::escape_string($user_array)."')";							
						$result = api_sql_query($sql,__FILE__,__LINE__);*/						
						$user_answer = $user_array;
					
						// we compare only the delineation not the other points
						$answer_question	= $_SESSION['hotspot_coord'][1];	
						$answerDestination	= $_SESSION['hotspot_dest'][1];
						
						//calculating the area
                        $poly_user 			= convert_coordinates($user_answer,'/'); 
                        $poly_answer		= convert_coordinates($answer_question,'|');
                        $max_coord 			= array('x'=>600,'y'=>400);//poly_get_max($poly_user,$poly_answer);	                   
                        $poly_user_compiled = poly_compile($poly_user,$max_coord);	                             
                        $poly_answer_compiled = poly_compile($poly_answer,$max_coord);
                        $poly_results 		= poly_result($poly_answer_compiled,$poly_user_compiled,$max_coord);
                              
                        $overlap = $poly_results['both'];
                        $poly_answer_area = $poly_results['s1'];
                        $poly_user_area = $poly_results['s2'];
                        $missing = $poly_results['s1Only'];
                        $excess = $poly_results['s2Only'];
                        
                        //$overlap = round(polygons_overlap($poly_answer,$poly_user)); //this is an area in pixels
                        if ($dbg_local>0) { error_log(__LINE__.' - Polygons results are '.print_r($poly_results,1),0);}
                        if ($overlap < 1) {
                            //shortcut to avoid complicated calculations
                        	$final_overlap = 0;
                            $final_missing = 100;
                            $final_excess = 100;
                        } else {
                            // the final overlap is the percentage of the initial polygon that is overlapped by the user's polygon
                        	$final_overlap = round(((float)$overlap / (float)$poly_answer_area)*100);
                            if ($dbg_local>1) { error_log(__LINE__.' - Final overlap is '.$final_overlap,0);}
                            // the final missing area is the percentage of the initial polygon that is not overlapped by the user's polygon
                            $final_missing = 100 - $final_overlap;
                            if ($dbg_local>1) { error_log(__LINE__.' - Final missing is '.$final_missing,0);}
                            // the final excess area is the percentage of the initial polygon's size that is covered by the user's polygon outside of the initial polygon
                            $final_excess = round((((float)$poly_user_area-(float)$overlap)/(float)$poly_answer_area)*100);
                            if ($dbg_local>1) { error_log(__LINE__.' - Final excess is '.$final_excess,0);}
                        }
						
						//checking the destination parameters parsing the "@@"				
						$destination_items= explode('@@', $answerDestination);	                        
				        $threadhold_total = $destination_items[0];			            
				        $threadhold_items=explode(';',$threadhold_total);				        		            
			            $threadhold1 = $threadhold_items[0]; // overlap
			            $threadhold2 = $threadhold_items[1]; // excess
			            $threadhold3 = $threadhold_items[2];	 //missing          
						
						// if is delineation
						if ($answerId===1) {
							//setting colors
							if ($final_overlap>=$threadhold1) {	
								$overlap_color=true; //echo 'a';
							}
							//echo $excess.'-'.$threadhold2;
							if ($final_excess<=$threadhold2) {	
								$excess_color=true; //echo 'b';
							}
							//echo '--------'.$missing.'-'.$threadhold3;
							if ($final_missing<=$threadhold3) {	
								$missing_color=true; //echo 'c';
							}					
							
							// if pass
							if ($final_overlap>=$threadhold1 && $final_missing<=$threadhold3 && $final_excess<=$threadhold2) {																
								$next=1; //go to the oars	
								$result_comment=get_lang('Acceptable');	
								$final_answer = 1;	// do not update with  update_exercise_attempt
								$comment=$answerDestination=$objAnswerTmp->selectComment(1);
							} else {									
								$next=1; //Go to the oars. If $next =  0 we will show this message: "One (or more) area at risk has been hit" instead of the table resume with the results	
								$result_comment=get_lang('Unacceptable');									
								$comment=$answerDestination=$objAnswerTmp->selectComment(2);																
								$answerDestination=$objAnswerTmp->selectDestination(1);
								//checking the destination parameters parsing the "@@"	
								$destination_items= explode('@@', $answerDestination);
								/*
								$try_hotspot=$destination_items[1];
	            				$lp_hotspot=$destination_items[2];
	           					$select_question_hotspot=$destination_items[3];
	            				$url_hotspot=$destination_items[4]; */	 		            											
								 //echo 'show the feedback';
							}
						} elseif($answerId>1) {
                            if ($objAnswerTmp->selectHotspotType($answerId) == 'noerror') {
                                if ($dbg_local>0) { error_log(__LINE__.' - answerId is of type noerror',0);}
                            	//type no error shouldn't be treated
                                $next = 1;
                                continue;
                            }
                            if ($dbg_local>0) { error_log(__LINE__.' - answerId is >1 so we\'re probably in OAR',0);}
							//check the intersection between the oar and the user												
							//echo 'user';	print_r($x_user_list);		print_r($y_user_list);
							//echo 'official';print_r($x_list);print_r($y_list);												
							//$result = get_intersection_data($x_list,$y_list,$x_user_list,$y_user_list);
							$inter= $result['success'];

                            //$delineation_cord=$objAnswerTmp->selectHotspotCoordinates($answerId);
                            $delineation_cord=$objAnswerTmp->selectHotspotCoordinates($answerId);

                            $poly_answer 			= convert_coordinates($delineation_cord,'|');
                            $max_coord 				= poly_get_max($poly_user,$poly_answer);                            
                            $poly_answer_compiled 	= poly_compile($poly_answer,$max_coord); 
                            $overlap 				= poly_touch($poly_user_compiled, $poly_answer_compiled,$max_coord);
                                          				
                            if ($overlap == false) {
                            	//all good, no overlap
                                $next = 1;
                                continue;
                            } else {								
                                if ($dbg_local>0) { error_log(__LINE__.' - Overlap is '.$overlap.': OAR hit',0);}
                                $organs_at_risk_hit++;  
                                //show the feedback
                                $next=0;								
                                $comment=$answerDestination=$objAnswerTmp->selectComment($answerId);                                
                                $answerDestination=$objAnswerTmp->selectDestination($answerId);
                                                    
                                $destination_items= explode('@@', $answerDestination);
                                 /*
                                $try_hotspot=$destination_items[1];
                                $lp_hotspot=$destination_items[2];
                                $select_question_hotspot=$destination_items[3];
                                $url_hotspot=$destination_items[4];*/                                                                                 
                            }
						}
					}
					else
					{	// the first delineation feedback		
                        if ($dbg_local>0) { error_log(__LINE__.' first',0);}								
					}			
				} // end for				
						
		if ($overlap_color) {
			$overlap_color='green';
	    } else {
			$overlap_color='red';
	    }
	    
		if ($missing_color) {
			$missing_color='green';
	    } else {
			$missing_color='red';
	    }
		if ($excess_color) {
			$excess_color='green';
	    } else {
			$excess_color='red';
	    }
	    
	    
	    if (!is_numeric($final_overlap)) {
    	$final_overlap = 0;
	    }
	    
	    if (!is_numeric($final_missing)) {
	    	$final_missing = 0;
	    }
	    if (!is_numeric($final_excess)) {
	    	$final_excess = 0;
	    }
	    
	    if ($final_excess>100) {
	    	$final_excess = 100;
	    }

		if ($answerType!= HOT_SPOT_DELINEATION) {
			$item_list=explode('@@',$destination);
			//print_R($item_list);
			$try = $item_list[0];
			$lp = $item_list[1];
			$destinationid= $item_list[2];
			$url=$item_list[3];
			$table_resume='';
		} else {
			if ($next==0) {
				$try = $try_hotspot;
				$lp = $lp_hotspot;
				$destinationid= $select_question_hotspot;
				$url=$url_hotspot;
			} else {				
				//show if no error
				//echo 'no error';				
			//	$comment=$answerComment=$objAnswerTmp->selectComment($nbrAnswers);	
			//	$comment=$answerComment=$objAnswerTmp->selectComment(2);	
				$answerDestination=$objAnswerTmp->selectDestination($nbrAnswers);
			}
		} 

		echo '<table width="100%" border="0">';	
		echo '<tr><td><object type="application/x-shockwave-flash" data="../plugin/hotspot/hotspot_solution.swf?modifyAnswers='.$questionId.'&exe_id='.$id.'&from_db=1" width="610" height="410">
						<param name="movie" value="../plugin/hotspot/hotspot_solution.swf?modifyAnswers='.$questionId.'&exe_id='.$id.'&from_db=1" />
							
					</object></td>';
		echo '<td width="40%" valign="top"><div class="quiz_content_actions" style="height:380px;"><div class="quiz_header">'.get_lang('Feedback').'</div><p align="center"><img src="../img/mousepolygon64.png"></p><div><table width="100%" border="1" class="data_table"><tr class="row_odd"><td>&nbsp;</td><td>'.get_lang('Requirement').'</td><td>'.get_lang('YourContour').'</td></tr><tr class="row_even"><td align="right">'.get_lang('Overlap').'</td><td align="center">'.get_lang('Min').' '.$threadhold1.' %</td><td align="center"><div style="color:'.$overlap_color.'">'.(($final_overlap < 0)?0:intval($final_overlap)).'</div></td></tr><tr class="row_even"><td align="right">'.get_lang('Excess').'</td><td align="center">'.get_lang('Max').' '.$threadhold2.' %</td><td align="center"><div style="color:'.$excess_color.'">'.(($final_excess < 0)?0:intval($final_excess)).'</div></td></tr><tr class="row_even"><td align="right">'.get_lang('Missing').'</td><td align="center">'.get_lang('Max').' '.$threadhold3.' %</td><td align="center"><div style="color:'.$missing_color.'">'.(($final_missing < 0)?0:intval($final_missing)).'</div></td></tr>';

		if ($answerType == HOT_SPOT_DELINEATION) {			
			if ($organs_at_risk_hit>0) {
				$message= get_lang('ResultIs').' <b>'.$result_comment.'</b>';				
				$message.= '<p style="color:#DC0A0A;"><b>'.get_lang('OARHit').'</b></p>';
			} else {				
				$message = '<p>'.get_lang('ResultIs').' <b>'.$result_comment.'</b></p>';
			}
		
			echo '<tr><td colspan="3" align="center">'.$message.'</td></tr>';
			
			// by default we assume that the answer is ok but if the final answer after calculating the area in hotspot delineation =0 then update  
			if ($final_answer==0) {
				$sql = 'UPDATE '.$TBL_TRACK_ATTEMPT.' SET answer="", marks = 0 WHERE question_id = '.$questionId.' AND exe_id = '.$exeId;
				Database::query($sql, __FILE__, __LINE__);
			}
			
		} else {
			//echo '<p>'.$comment.'</p>';
			echo '<tr><td colspan="3">'.$comment.'</td></tr>';
		}
		
		echo '</table></div><br/><br/>';
		if(!empty($comment)){
		echo '<div align="center" class="quiz_feedback"><b>'.get_lang('Feedback').'</b> : '.$comment.'</div>';
		}
		echo '</div></td></tr>';
		
		echo '</table>';
	}

		echo '<table width="100%" border="0" cellspacing="3" cellpadding="0">';		
		if ($is_allowedToEdit) {
			echo '<tr><td>';							
			$name = "fckdiv".$questionId;
			$marksname = "marksName".$questionId;
			?>			
			<br />
			<a href="javascript://" onclick="showfck('<?php echo $name; ?>','<?php echo $marksname; ?>');">
			<?php 
			if ($answerType == FREE_ANSWER) {  
				echo get_lang('EditCommentsAndMarks'); 
			} else {
				if ($action=='edit') {
					echo Display::return_icon('pixel.gif',get_lang('EditIndividualComment'),array('class'=>'actionplaceholdericon actionedit')).get_lang('EditIndividualComment');
				} else {
					echo get_lang('AddComments');
				}
			}
			echo '</a><br /><div id="feedback_'.$name.'" style="width:100%">';
			$comnt = trim(get_comments($id,$questionId));
			if (empty($comnt)) {
				echo '<br />';
			} else {
				echo '<div id="question_feedback">'.$comnt.'</div><br />';			
			}
			echo '</div><div id="'.$name.'" style="display:none">';
			$arrid[] = $questionId;
	
			$feedback_form = new FormValidator('frmcomments'.$questionId,'post','');
			$feedback_form->addElement('html','<br>');
			$renderer =& $feedback_form->defaultRenderer();
			$renderer->setFormTemplate('<form{attributes}><div align="left">{content}</div></form>');
			$renderer->setElementTemplate('<div align="left">{element}</div>');
			$comnt = get_comments($id,$questionId);
			${user.$questionId}['comments_'.$questionId] = $comnt;
			$feedback_form->addElement('html_editor', 'comments_'.$questionId, null, null, array('ToolbarSet' => 'TestAnswerFeedback', 'Width' => '100%', 'Height' => '120'));
			$feedback_form->addElement('html','<br>');
			//$feedback_form->addElement('submit','submitQuestion',get_lang('Ok'));
			$feedback_form->setDefaults(${user.$questionId});							
			$feedback_form->display();			
			echo '</div>';
		} else {
			$comnt = get_comments($id,$questionId);
			echo '<tr><td><br />';
			if (!empty($comnt)) {
				echo '<b>'.get_lang('Feedback').'</b>';									
				echo '<div id="question_feedback">'.$comnt.'</div>';
			}
			echo '</td><td>';
		}
		if ($is_allowedToEdit) {
			if ($answerType == FREE_ANSWER) {
				$marksname = "marksName".$questionId;
				?>
				<div id="<?php echo $marksname; ?>" style="display:none">
				<form name="marksform_<?php echo $questionId; ?>" method="post" action="">
			    <?php
				$arrmarks[] = $questionId;
				echo get_lang("AssignMarks");				
				echo "&nbsp;<select name='marks' id='marks'>";
				for ($i=0;$i<=$questionWeighting;$i++) {
					echo '<option '.(($i==$questionScore)?"selected='selected'":'').'>'.$i.'</option>';
				}
				echo '</select>';
				echo '</form><br/ ></div>';
				if ($questionScore==-1) {
					$questionScore=0;
				  	echo '<br />'.get_lang('notCorrectedYet').'<br/><br/>';
				}
			} else {
				$arrmarks[] = $questionId;
				echo '<div id="'.$marksname.'" style="display:none"><form name="marksform_'.$questionId.'" method="post" action="">
					  <select name="marks" id="marks" style="display:none;"><option>'.$questionScore.'</option></select></form><br/ ></div>';
			}
		} else {
			if ($questionScore==-1) {
				 $questionScore=0;
			}
		}
		?>	
		</td>
		</tr>
		</table>
		
		<div id="question_score" class="sectiontitle">
		<?php
		$my_total_score  = round(float_format($questionScore,1));
		$my_total_weight = float_format($questionWeighting,1);			 
		echo get_lang('Score')." : $my_total_score/$my_total_weight"; 
		echo '</div>';
		echo '</td></tr></table></div></div>';
		unset($objAnswerTmp);
		$i++;
		$totalWeighting+=$questionWeighting;
	} // end of large foreach on questions
} //end of condition if $show_results

/*if ($origin!='learnpath' || ($origin == 'learnpath' && isset($_GET['fb_type']))) {
	//$query = "update ".$TBL_TRACK_EXERCICES." set exe_result = $totalScore where exe_id = '$id'";
	//api_sql_query($query,__FILE__,__LINE__);
	if ($show_results) {
		echo '<div style="padding:0px 0px 20px 0px;"><div id="question_score" class="actions" style="width:98%;font-weight:bold;">'.get_lang('YourTotalScore')." ";
		if($dsp_percent == true) {
			$my_result = number_format(($totalScore/$totalWeighting)*100,1,'.','');
			$my_result = float_format($my_result,1);
			echo $my_result."%";
		} else {
			$my_total_score  = float_format($totalScore,1);
			$my_total_weight = float_format($totalWeighting,1);			
			echo $my_total_score."/".$my_total_weight;
		}
		echo '</div></div>';		
	}
}*/

if (is_array($arrid) && is_array($arrmarks)) {
	$strids = implode(",",$arrid);
	$marksid = implode(",",$arrmarks);
}

echo '<script>
$(document).ready(function() {
	$("input[name=quizstatus]").change(function() {  
   showmailcontent();
});
function showmailcontent() {
	var quizstatus = $("input[name=quizstatus]:checked").val();	
	if(quizstatus == "success")
	{
	$("#successmailcontent").show();
	$("#failuremailcontent").hide();
	}
	else
	{
	$("#failuremailcontent").show();
	$("#successmailcontent").hide();
	}
}
});

</script>';

echo '<div style="padding:0px 0px 20px 0px;">';
if ($is_allowedToEdit) {
	if (in_array($origin, array('tracking_course','user_course'))) {
		echo ' <form name="myform" id="myform" action="exercice.php?'.  api_get_cidreq().'&comments=update&exeid='.$id.'&test='.urlencode($test).'&emailid='.$emailId.'&origin='.$origin.'&student='.Security::remove_XSS($_GET['student']).'&details=true&course='.Security::remove_XSS($_GET['cidReq']);
		if(isset($_REQUEST['quizpopup']))
		{
			//echo "&quizpopup=1";
		}
		echo '" method="post">';
		echo ' <input type = "hidden" name="totalWeighting" value="'.$totalWeighting.'">';
		if (isset($_GET['myid']) && isset($_GET['my_lp_id']) && isset($_GET['student'])) {			
			?>
			<input type = "hidden" name="lp_item_id" value="<?php echo Security::remove_XSS($_GET['myid']); ?>">
			<input type = "hidden" name="lp_item_view_id" value="<?php echo Security::remove_XSS($_GET['my_lp_id']); ?>">
			<input type = "hidden" name="student_id" value="<?php echo Security::remove_XSS($_GET['student']);?>">
			<input type = "hidden" name="total_score" value="<?php echo $totalScore; ?>">
			<input type = "hidden" name="total_time" value="<?php echo Security::remove_XSS($_GET['total_time']);?>">			
			<input type = "hidden" name="my_exe_exo_id" value="<?php echo Security::remove_XSS($_GET['my_exe_exo_id']); ?>">		
			<?php		 
		}
	} else {
		echo ' <form name="myform" id="myform" action="exercice.php?'.  api_get_cidreq().'&comments=update&exeid='.$id.'&test='.$test.'&emailid='.$emailId.'&totalWeighting='.$totalWeighting;
		if(isset($_REQUEST['quizpopup']))
		{
			//echo "&quizpopup=1";
		}	
		echo '" method="post">';
	}					
	if ($origin!='learnpath' && $origin!='student_progress') {

		$success_content = getMailContent('success');
		$failure_content = getMailContent('failure');

		?>					
		<script>
			function showcontent(){			
			document.getElementById('mailcontent').style.display = 'block';
			document.getElementById('hidecontent').style.display = 'block';
			document.getElementById('viewcontent').style.display = 'none';
		}
		function hidecontent(){			
			document.getElementById('mailcontent').style.display = 'none';
			document.getElementById('hidecontent').style.display = 'none';
			document.getElementById('viewcontent').style.display = 'block';
		}
		</script>
		<?php
			if($_REQUEST['action'] == 'qualify'){			
		?>
		<div><table><tr><td valign="top"><?php echo get_lang('Notes'); ?> : </td><td><textarea name="notes" rows="5" cols="75"></textarea></td></tr></table></div>
		<div><table><tr><td><label><input type="radio" name="quizstatus" value="success" /><?php echo get_lang('Showsuccesscontent'); ?></label></td><td><label><input type="radio" name="quizstatus" value="failure" /><?php echo get_lang('Showfailurecontent'); ?></label></td></tr></table></div><br/>	
	<!--<div id="viewcontent"><a href="#mailcontent" onclick="showcontent()"><?php echo get_lang('Showemailcontent'); ?></a></div>
		<div id="hidecontent" style="display:none;"><a href="#mailcontent" onclick="hidecontent()"><?php echo get_lang('Hideemailcontent'); ?></a></div>-->		
		<div id="successmailcontent" style="display:none;"><?php echo get_lang('Dontedittext'); ?><br/><textarea name="successcontent" rows="10" cols="75"><?php echo $success_content; ?></textarea></div>
		<div id="failuremailcontent" style="display:none;"><?php echo get_lang('Dontedittext'); ?><br/><textarea name="failurecontent" rows="10" cols="75"><?php echo $failure_content; ?></textarea></div>
		<button type="submit" class="save" value="<?php echo get_lang('Ok'); ?>" onclick="getFCK('<?php echo $strids; ?>','<?php echo $marksid; ?>');"><?php echo get_lang('FinishTest'); ?></button>
		<?php
		}
		?>
		</form>
		<?php 
	}
}  		

if ($origin=='student_progress') {?>				
	<button type="button" class="back" onclick="window.back();" value="<?php echo get_lang('Back'); ?>" ><?php echo get_lang('Back');?></button>										
<?php 
} else if($origin=='myprogress') {
?>
	<button type="button" class="save" onclick="top.location.href='../auth/my_progress.php?course=<?php echo api_get_course_id()?>'" value="<?php echo get_lang('Finish'); ?>" ><?php echo get_lang('Finish');?></button>				
<?php 
}
echo '</div>';
echo '</div>';

if ($origin!='learnpath' || ($origin == 'learnpath' && isset($_GET['fb_type']))) {
	//$query = "update ".$TBL_TRACK_EXERCICES." set exe_result = $totalScore where exe_id = '$id'";
	//api_sql_query($query,__FILE__,__LINE__);
	if ($show_results) {
		echo '<div id="question_score" class="actions" style="font-weight:bold;">'.get_lang('YourTotalScore')." ";
		if($dsp_percent == true) {
			$my_result = number_format(($totalScore/$totalWeighting)*100,1,'.','');
			$my_result = float_format($my_result,1);
			echo $my_result."%";
		} else {
			$my_total_score  = round($totalScore);
			$my_total_weight = float_format($totalWeighting,1);
			echo $my_total_score."/".$my_total_weight;
		}
		echo '</div>';
	}
}

if ($origin != 'learnpath') {
	//we are not in learnpath tool	
	Display::display_footer();	
} else {
	
	if (!isset($_GET['fb_type'])) {
		$lp_mode =  $_SESSION['lp_mode'];
		$url = '../newscorm/lp_controller.php?cidReq='.api_get_course_id().'&action=view&lp_id='.$learnpath_id.'&lp_item_id='.$learnpath_item_id.'&exeId='.$exeId.'&fb_type='.$feedback_type;
		$href = ($lp_mode == 'fullscreen')?' window.opener.location.href="'.$url.'" ':' top.location.href="'.$url.'" ';	 
		echo '<script language="javascript" type="text/javascript">'.$href.'</script>'."\n";
	
		//record the results in the learning path, using the SCORM interface (API)
		echo '<script language="javascript" type="text/javascript">window.parent.API.void_save_asset('.$totalScore.','.$totalWeighting.');</script>'."\n";
		echo '</body></html>';
	} else {
		Display::display_normal_message(get_lang('ExerciseFinished'));
	}
}

function getMailContent($quizresult)
{
	global $language_interface;
	if($quizresult == 'success')
	{
		$description = "Quizsuccess";
	}
	elseif($quizresult == 'failure')
	{
		$description = "Quizfailure";
	}
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
		if($quizresult == 'success'){
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
	return $content;
}

function display_unique_or_multiple_or_reasoning_answer($answerType, $studentChoice, $answer, $answerComment, $answerCorrect, $id, $questionId, $ans)
{
	if($answerType == UNIQUE_ANSWER){
		$img = 'radio';
	}
	else {
		$img = 'checkbox';
	}
	if($studentChoice){
		$your_choice = $img.'_on'.'.gif';
	}
	else {
		$your_choice = $img.'_off'.'.gif';
	}

	if($answerCorrect){
		$expected_choice = $img.'_on'.'.gif';
	}
	else {
		$expected_choice = $img.'_off'.'.gif';
	}

	$s .= '
	<tr>
	<td width="5%" align="center">
		<img src="../img/'.$your_choice.'"
		border="0" alt="" />
	</td>
	<td width="5%" align="center">
		<img src="../img/'.$expected_choice.'"
		border="0" alt=" " />
	</td>
	<td width="40%" style="border-bottom: 1px solid #4171B5;">'.api_parse_tex($answer).'	
	</td>		
	</tr>';
	return $s;
}

//destroying the session 
api_session_unregister('questionList');
unset ($questionList);

api_session_unregister('exerciseResult');
unset ($exerciseResult);