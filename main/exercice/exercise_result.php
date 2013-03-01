<?php
/*
==============================================================================
	Dokeos - elearning and course management software

	Copyright (c) 2008 Dokeos SPRL

	For a full list of contributors, see "credits.txt".
	The full license can be read in "license.txt".

	This program is free software; you can redistribute it and/or
	modify it under the terms of the GNU General Public License
	as published by the Free Software Foundation; either version 2
	of the License, or (at your option) any later version.

	See the GNU General Public License for more details.

	Contact address: Dokeos, rue du Corbeau, 108, B-1030 Brussels, Belgium
	Mail: info@dokeos.com
==============================================================================
*/
/**
*	Exercise result
*	This script gets informations from the script "exercise_submit.php",
*	through the session, and calculates the score of the student for
*	that exercise.
*	Then it shows the results on the screen.
*	@package dokeos.exercise
*	@author Olivier Brouckaert, main author
*	@author Roan Embrechts, some refactoring
* 	@author Julio Montoya Armas switchable fill in blank option added
* 	@version $Id: exercise_result.php 22201 2009-07-17 19:57:03Z cfasanando $
*
*	@todo	split more code up in functions, move functions to library?
*/
/*
==============================================================================
		INIT SECTION
==============================================================================
*/
require_once('exercise.class.php');
require_once('question.class.php');
require_once('answer.class.php');
if ($_GET['origin']=='learnpath') {
	require_once ('../newscorm/learnpath.class.php');
	require_once ('../newscorm/learnpathItem.class.php');
	require_once ('../newscorm/scorm.class.php');
	require_once ('../newscorm/scormItem.class.php');
	require_once ('../newscorm/aicc.class.php');
	require_once ('../newscorm/aiccItem.class.php');
}
global $_cid;
// name of the language file that needs to be included
$language_file=array('exercice','tracking');

require('../inc/global.inc.php');
$this_section=SECTION_COURSES;

/* ------------	ACCESS RIGHTS ------------ */
// notice for unauthorized people.
api_protect_course_script(true);

require_once(api_get_path(LIBRARY_PATH).'mail.lib.inc.php');
require_once(api_get_path(LIBRARY_PATH).'course.lib.php');
//$htmlHeadXtra[] = '<script src="'.api_get_path(WEB_LIBRARY_PATH).'javascript/jquery.js" type="text/javascript" language="javascript"></script>';
//$htmlHeadXtra[] = '<script src="'.api_get_path(WEB_LIBRARY_PATH).'javascript/jquery.corners.min.js" type="text/javascript"></script>';

// Database table definitions
$TBL_EXERCICE_QUESTION 	= Database::get_course_table(TABLE_QUIZ_TEST_QUESTION);
$TBL_EXERCICES         	= Database::get_course_table(TABLE_QUIZ_TEST);
$TBL_QUESTIONS         	= Database::get_course_table(TABLE_QUIZ_QUESTION);
$TBL_REPONSES          	= Database::get_course_table(TABLE_QUIZ_ANSWER);
$TBL_TRACK_EXERCICES	= Database::get_statistic_table(TABLE_STATISTIC_TRACK_E_EXERCICES);
$TBL_TRACK_ATTEMPT		= Database::get_statistic_table(TABLE_STATISTIC_TRACK_E_ATTEMPT);
$main_user_table 		= Database :: get_main_table(TABLE_MAIN_USER);
$main_course_user_table = Database :: get_main_table(TABLE_MAIN_COURSE_USER);
$table_ans 				= Database :: get_course_table(TABLE_QUIZ_ANSWER);

//temp values to move to admin settings
$dsp_percent = false; //false to display total score as absolute values
//debug param. 0: no display - 1: debug display
$debug=0;
if($debug>0){echo str_repeat('&nbsp;',0).'Entered exercise_result.php'."<br />\n";var_dump($_POST);}
// general parameters passed via POST/GET
if ( empty ( $origin ) ) {
     $origin = Security::remove_XSS($_REQUEST['origin']);
}
if ( empty ( $learnpath_id ) ) {
     $learnpath_id       = Security::remove_XSS($_REQUEST['learnpath_id']);
}
if ( empty ( $learnpath_item_id ) ) {
     $learnpath_item_id  = Security::remove_XSS($_REQUEST['learnpath_item_id']);
}
if ( empty ( $formSent ) ) {
    $formSent       = $_REQUEST['formSent'];
}
if ( empty ( $exerciseResult ) ) {
     $exerciseResult = $_SESSION['exerciseResult'];
}
if ( empty ( $exerciseResultCoordinates ) ) {
     $exerciseResultCoordinates = $_SESSION['exerciseResultCoordinates'];
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
if ( empty ( $exerciseType ) ) {
    $exerciseType = $_REQUEST['exerciseType'];
}

$course_code = api_get_course_id();
if(isset($_SESSION['expired_time'][$course_code][intval($_SESSION['id_session'])][$objExercise->id][$learnpath_id]))
{
	unset($_SESSION['expired_time'][$course_code][intval($_SESSION['id_session'])][$objExercise->id][$learnpath_id]);
}

$_configuration['live_exercise_tracking'] = false;
if($_configuration['live_exercise_tracking']) define('ENABLED_LIVE_EXERCISE_TRACKING',1);

if($_configuration['live_exercise_tracking'] == true && $exerciseType == 1){
	$_configuration['live_exercise_tracking'] = false;
}

// set admin name as person who sends the results e-mail (lacks policy about whom should really send the results)
$main_user_table = Database :: get_main_table(TABLE_MAIN_USER);
$main_admin_table = Database :: get_main_table(TABLE_MAIN_ADMIN);
$courseName = $_SESSION['_course']['name'];
$query = "SELECT user_id FROM $main_admin_table LIMIT 1"; //get all admins from admin table
$admin_id = Database::result(api_sql_query($query),0,"user_id");
$uinfo = api_get_user_info($admin_id);
$from = $uinfo['mail'];
$from_name = $uinfo['firstname'].' '.$uinfo['lastname'];
$str = $_SERVER['REQUEST_URI'];
$url = api_get_path(WEB_CODE_PATH).'exercice/exercice.php?'.api_get_cidreq().'&show=result';

 // if the above variables are empty or incorrect, we don't have any result to show, so stop the script
if(!is_array($exerciseResult) || !is_array($questionList) || !is_object($objExercise)) {

	header('Location: exercice.php');
	exit();
}

$sql_fb_type='SELECT feedback_type FROM '.$TBL_EXERCICES.' WHERE id ="'.Database::escape_string($objExercise->selectId()).'"';

$res_fb_type=Database::query($sql_fb_type,__FILE__,__LINE__);
$row_fb_type=Database::fetch_row($res_fb_type);
$feedback_type = $row_fb_type[0];

// define basic exercise info to print on screen
$exerciseTitle=$objExercise->selectTitle();
$exerciseDescription=$objExercise->selectDescription();

$gradebook = '';
if (isset($_SESSION['gradebook'])){
	$gradebook=	$_SESSION['gradebook'];
}

if (!empty($gradebook) && $gradebook=='view') {	
	$interbreadcrumb[]= array (
			'url' => '../gradebook/'.$_SESSION['gradebook_dest'],
			'name' => get_lang('Gradebook')
		);
}

$nameTools=get_lang('Exercice');

$interbreadcrumb[]=array("url" => "exercice.php?gradebook=$gradebook","name" => get_lang('Exercices'));
$htmlHeadXtra[] = "<script type=\"text/javascript\" src=\"../plugin/hotspot/JavaScriptFlashGateway.js\"></script>
					<script src=\"../plugin/hotspot/hotspot.js\" type=\"text/javascript\"></script>
					<script language=\"JavaScript\" type=\"text/javascript\">
					<!--
					// -----------------------------------------------------------------------------
					// Globals
					// Major version of Flash required
					var requiredMajorVersion = 7;
					// Minor version of Flash required
					var requiredMinorVersion = 0;
					// Minor version of Flash required
					var requiredRevision = 0;
					// the version of javascript supported
					var jsVersion = 1.0;
					// -----------------------------------------------------------------------------
					// -->
					</script>
					<script language=\"VBScript\" type=\"text/vbscript\">
					<!-- // Visual basic helper required to detect Flash Player ActiveX control version information
					Function VBGetSwfVer(i)
					  on error resume next
					  Dim swControl, swVersion
					  swVersion = 0

					  set swControl = CreateObject(\"ShockwaveFlash.ShockwaveFlash.\" + CStr(i))
					  if (IsObject(swControl)) then
					    swVersion = swControl.GetVariable(\"\$version\")
					  end if
					  VBGetSwfVer = swVersion
					End Function
					// -->
					</script>

					<script language=\"JavaScript1.1\" type=\"text/javascript\">
					<!-- // Detect Client Browser type
					var isIE  = (navigator.appVersion.indexOf(\"MSIE\") != -1) ? true : false;
					var isWin = (navigator.appVersion.toLowerCase().indexOf(\"win\") != -1) ? true : false;
					var isOpera = (navigator.userAgent.indexOf(\"Opera\") != -1) ? true : false;
					jsVersion = 1.1;
					// JavaScript helper required to detect Flash Player PlugIn version information
					function JSGetSwfVer(i){
						// NS/Opera version >= 3 check for Flash plugin in plugin array
						if (navigator.plugins != null && navigator.plugins.length > 0) {
							if (navigator.plugins[\"Shockwave Flash 2.0\"] || navigator.plugins[\"Shockwave Flash\"]) {
								var swVer2 = navigator.plugins[\"Shockwave Flash 2.0\"] ? \" 2.0\" : \"\";
					      		var flashDescription = navigator.plugins[\"Shockwave Flash\" + swVer2].description;
								descArray = flashDescription.split(\" \");
								tempArrayMajor = descArray[2].split(\".\");
								versionMajor = tempArrayMajor[0];
								versionMinor = tempArrayMajor[1];
								if ( descArray[3] != \"\" ) {
									tempArrayMinor = descArray[3].split(\"r\");
								} else {
									tempArrayMinor = descArray[4].split(\"r\");
								}
					      		versionRevision = tempArrayMinor[1] > 0 ? tempArrayMinor[1] : 0;
					            flashVer = versionMajor + \".\" + versionMinor + \".\" + versionRevision;
					      	} else {
								flashVer = -1;
							}
						}
						// MSN/WebTV 2.6 supports Flash 4
						else if (navigator.userAgent.toLowerCase().indexOf(\"webtv/2.6\") != -1) flashVer = 4;
						// WebTV 2.5 supports Flash 3
						else if (navigator.userAgent.toLowerCase().indexOf(\"webtv/2.5\") != -1) flashVer = 3;
						// older WebTV supports Flash 2
						else if (navigator.userAgent.toLowerCase().indexOf(\"webtv\") != -1) flashVer = 2;
						// Can't detect in all other cases
						else {

							flashVer = -1;
						}
						return flashVer;
					}
					// When called with reqMajorVer, reqMinorVer, reqRevision returns true if that version or greater is available
					function DetectFlashVer(reqMajorVer, reqMinorVer, reqRevision)
					{
					 	reqVer = parseFloat(reqMajorVer + \".\" + reqRevision);
					   	// loop backwards through the versions until we find the newest version
						for (i=25;i>0;i--) {
							if (isIE && isWin && !isOpera) {
								versionStr = VBGetSwfVer(i);
							} else {
								versionStr = JSGetSwfVer(i);
							}
							if (versionStr == -1 ) {
								return false;
							} else if (versionStr != 0) {
								if(isIE && isWin && !isOpera) {
									tempArray         = versionStr.split(\" \");
									tempString        = tempArray[1];
									versionArray      = tempString .split(\",\");
								} else {
									versionArray      = versionStr.split(\".\");
								}
								versionMajor      = versionArray[0];
								versionMinor      = versionArray[1];
								versionRevision   = versionArray[2];

								versionString     = versionMajor + \".\" + versionRevision;   // 7.0r24 == 7.24
								versionNum        = parseFloat(versionString);
					        	// is the major.revision >= requested major.revision AND the minor version >= requested minor
								if ( (versionMajor > reqMajorVer) && (versionNum >= reqVer) ) {
									return true;
								} else {
									return ((versionNum >= reqVer && versionMinor >= reqMinorVer) ? true : false );
								}
							}
						}
					}
					// -->
					</script>";

if ($origin != 'learnpath') {
	//so we are not in learnpath tool
	Display::display_tool_header($nameTools,"Exercise");
} else {

	if (empty($charset)) {
		$charset = 'ISO-8859-15';
	}
	header('Content-Type: text/html; charset='. $charset);

	@$document_language = Database::get_language_isocode($language_interface);
	if(empty($document_language))
	{
	  //if there was no valid iso-code, use the english one
	  $document_language = 'en';
	}

	/*
	 * HTML HEADER
	 */

?>
<!DOCTYPE html
     PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
     "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?php echo $document_language; ?>" lang="<?php echo $document_language; ?>">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $charset; ?>" />
</head>

<body>
<link rel="stylesheet" type="text/css" href="<?php echo api_get_path(WEB_CODE_PATH).'css/'.api_get_setting('stylesheets').'/default.css'; ?>" />
<?php
}


if ($objExercise->results_disabled) {
	ob_start();
}

/*
FUNCTIONS
*/


function display_unique_or_multiple_answer($answerType, $studentChoice, $answer, $answerComment, $answerCorrect)
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
	<td width="45%" style="border-bottom: 1px solid #4171B5;">
		<?php		
		$answer=api_parse_tex($answer);
		echo $answer; 
		?>
	</td>
	<!--<td width="45%" style="border-bottom: 1px solid #4171B5;">
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
	</td>-->
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
	<td width="45%" style="border-bottom: 1px solid #4171B5;">
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

function display_fill_in_blanks_answer($answer,$correctChoice,$answerWrong,$feedback_true,$feedback_false)
{
	?>
		<tr>
		<td>
			<?php echo Security::remove_XSS($answer,COURSEMANAGERLOWSECURITY); ?>
		</td>
		</tr>
		<tr>
		<td>
			<?php echo get_lang('Feedback'); ?>
		</td>
		</tr>
		<tr>		
			<?php 
			if($correctChoice == 'Y' && $answerWrong == 'N')
			{
				echo '<td>'.$feedback_true;
			}
			else
			{
				echo '<td>'.$feedback_false;
			}
			?>
		</td>
		</tr>
	<?php
}

function display_free_answer($answer)
{
	?>
		<tr>
		<td width="55%">
			<?php echo nl2br(Security::remove_XSS($answer,COURSEMANAGERLOWSECURITY)); ?>
		</td>
   <td width="45%">
    <?php echo get_lang('notCorrectedYet');?>

   </td>
		</tr>
	<?php
}

function display_hotspot_answer($answerId, $answer, $studentChoice, $answerComment)
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

			$s .= '<tr>';
			$s .= '<td valign="top">';
			$s .= '	<div style="height:11px; width:11px; background-color:'.$hotspot_colors[$answerId].'; display:inline; float:left; margin-top:3px;"></div>
						<div style="float:left; padding-left:5px;">'.$answerId.'</div>
							<div style="float:left; padding-left:5px;">
								<div style="display:inline; float:left; width:80px;">'.$answer.'</div>
							</div>';
			$s .= '</td>
				   <td valign="top">';
			$my_choice = ($studentChoice)?get_lang('Correct'):get_lang('Fault');
			$s .= $my_choice;
			$s .= '</td>
				   </tr>';
	?>
	
		<!--<tr>
				<td valign="top">
				<div style="height:11px; width:11px; background-color:<?php echo $hotspot_colors[$answerId]; ?>; display:inline; float:left; margin-top:3px;"></div>
				<div style="float:left; padding-left:5px;">
				<?php echo $answerId; ?>
				</div>
					<div style="float:left; padding-left:5px;">
						
						<div style="display:inline; float:left; width:80px;"><?php echo $answer ?></div>
						
					</div>
				</td>
				<td valign="top">
					<?php $my_choice = ($studentChoice)?get_lang('Correct'):get_lang('Fault'); echo $my_choice; ?>
				</td>
				<td valign="top">				
					<?php
					if ($studentChoice) {
						echo '<span>';							
					} else {
						echo '<span>';												
					}					
					echo $answerComment;
					echo '</span>';				 
					?>
				</td>
		</tr>-->
		
	<?php
		return $s;
}

/*
DISPLAY AND MAIN PROCESS
*/

// I'm in a preview mode as course admin. Display the action menu.
if ($origin != 'learnpath') {
    echo '<div class="actions">';
    if (api_is_course_admin()) {
        echo '<a href="exercice.php?'.api_get_cidreq().'&exerciseId='.$objExercise->id.'">'.Display::return_icon('pixel.gif', get_lang('GoBackToEx'),array('class'=>'toolactionplaceholdericon toolactionback')).get_lang('GoBackToEx'). '</a>';
	echo '<a href="exercise_admin.php?scenario=yes&modifyExercise=yes&' . api_get_cidreq() . '&exerciseId='.$objExercise->id.'">' . Display :: return_icon('pixel.gif', get_lang('Scenario'),array('class'=>'toolactionplaceholdericon toolactionscenario')) . get_lang('Scenario') . '</a>';
    } else {
        echo '<a href="'.api_get_path(WEB_CODE_PATH).'exercice/exercice.php?'.api_get_cidreq().'">' . Display::return_icon('pixel.gif', get_lang('GoBackToEx'), array('class' => 'toolactionplaceholdericon toolactionback')) . get_lang('GoBackToEx').'</a>';
    }
    echo '</div>';			                
}

echo '<div id="content">';
echo '<div class="actions"><table width="100%">';
$exerciseTitle=api_parse_tex($exerciseTitle);
$user_id=api_get_user_id();
$course_code = api_get_course_id();
$status_info=CourseManager::get_user_in_course_status($user_id,$course_code);
			if (STUDENT==$status_info) {
				$user_info=api_get_user_info($user_id); 
				$user_name =  $user_info['firstName'].' '.$user_info['lastName'];
			} elseif(COURSEMANAGER==$status_info && !isset($_GET['user'])) {
				$user_info=api_get_user_info($user_id); 
				$user_name =  $user_info['firstName'].' '.$user_info['lastName'];
			} else {
				echo $user_name;
			}

//show exercise title
?>
	<?php if($origin != 'learnpath') {
		echo '<tr><td width="10%" align="right"><b>'.get_lang('CourseTitle').'</b> :</td><td>'.api_get_course_id().'</td></tr>';
		echo '<tr><td width="10%" align="right"><b>'.get_lang('User').'</b> :</td><td>'.$user_name.'</td></tr>';
		echo '<tr><td width="10%" align="right"><b>'.get_lang('Exercise').'</b> :</td><td>'.$exerciseTitle.'<br/>'.$exerciseDescription.'</td></tr>';
	} ?>
	</table></div><br/>
	<form method="get" action="exercice.php">
	<input type="hidden" name="origin" value="<?php echo $origin; ?>" />
    <input type="hidden" name="learnpath_id" value="<?php echo $learnpath_id; ?>" />
    <input type="hidden" name="learnpath_item_id" value="<?php echo $learnpath_item_id; ?>" />

<?php

$i=$totalScore=$totalWeighting=0;
if($debug>0){echo "ExerciseResult: "; var_dump($exerciseResult); echo "QuestionList: ";var_dump($questionList);}

if ($_configuration['tracking_enabled']) {
	// Create an empty exercise  
	$exeId= create_event_exercice($objExercise->selectId());
}	 
$counter=0;

// Loop over all question to show results for each of them, one by one
foreach ($questionList as $questionId) {
	$counter++;
	// gets the student choice for this question
	$choice=$exerciseResult[$questionId];
	// creates a temporary Question object
	$objQuestionTmp = Question :: read($questionId);
	// initialize question information
	$questionName=$objQuestionTmp->selectTitle();
	$questionDescription=$objQuestionTmp->selectDescription();
	$questionWeighting=$objQuestionTmp->selectWeighting();
	$answerType=$objQuestionTmp->selectType();
	$quesId =$objQuestionTmp->selectId(); //added by priya saini

	// destruction of the Question object
	unset($objQuestionTmp);

	// decide how many columns we want to use to show the results of each type
	if($answerType == UNIQUE_ANSWER || $answerType == MULTIPLE_ANSWER || $answerType == REASONING) {
		$colspan=4;
	} elseif($answerType == MATCHING || $answerType == FREE_ANSWER) {
		$colspan=2;
	} elseif($answerType == HOT_SPOT || $answerType == HOT_SPOT_ORDER) {
		$colspan=4;
		$rowspan=$nbrAnswers+1;
	} else {
		$colspan=1;
	}
	// show titles
	if ($origin != 'learnpath') {?>
		<div style="padding:0px 0px 20px 0px;"><div class="rounded" style="width: 100%; padding: 1px; background-color:#ccc;"><table class="rounded_inner" style="width: 100%; background-color:#fff;"><tr><td>
		<div id="question_title" class="quiz_content_actions" style="font-weight:bold;margin: 0px 10px 10px 10px;padding: 5px 15px;top:-10px;position: relative;border: 1px solid #ED9438;">
    		<?php echo get_lang("Question").' '.($counter).' : '.$questionName; ?>
    	</div>	   
    	<div id="question_description" class="scroll_feedback">
    		<?php echo $questionDescription; ?>
    	</div>
	<!--	<tr bgcolor="#E6E6E6">
		<td colspan="<?php echo $colspan; ?>">
			<?php echo get_lang("Question").' '.($counter).' : '.$questionName; ?>
		</td>
		</tr>
		<tr>
		<td colspan="<?php echo $colspan; ?>">				
			<i><?php echo $questionDescription; ?></i>				
		</td>
		</tr>-->
		<table width="100%" border="0" cellpadding="3" cellspacing="2" class="feedback_actions">
		<?php
		if ($answerType == UNIQUE_ANSWER || $answerType == MULTIPLE_ANSWER) {
			?>			
				<tr>
				<td width="5%" valign="top" align="center" nowrap="nowrap">
					<i><?php echo get_lang("Choice"); ?></i>
				</td>
				<td width="5%"  align="center" nowrap="nowrap">
					<i><?php echo get_lang("ExpectedChoice"); ?></i>
				</td>
				<td width="45%" valign="top">
					<i><?php echo get_lang("Answer"); ?></i>
				</td>
			<!--<td width="45%" valign="top">
					<i><?php echo get_lang("Comment"); ?></i>
				</td>-->
				</tr>
			<?php
		} elseif ($answerType == REASONING) {
			?>
				<tr>
				<td width="5%" valign="top" align="center" nowrap="nowrap">
					<i><?php echo get_lang("Choice"); ?></i>
				</td>
				<td width="5%"  align="center" nowrap="nowrap">
					<i><?php echo get_lang("ExpectedChoice"); ?></i>
				</td>
				<td width="45%" valign="top">
					<i><?php echo get_lang("Answer"); ?></i>
				</td>				
				</tr>
			<?php
		} elseif ($answerType == FILL_IN_BLANKS) {
			?>
				<tr>
				<td>
					<i><?php echo get_lang("Answer"); ?></i>
				</td>
				</tr>
			<?php
		} elseif ($answerType == FREE_ANSWER) {
			?>
				<tr>
				<td width="55%">
					<i><?php echo get_lang("Answer"); ?></i>
				</td>
				<td width="45%" valign="top">
					<i><?php echo get_lang("Comment"); ?></i>
				</td>
				</tr>
			<?php
		} elseif ($answerType == HOT_SPOT) {
			?>
				<tr>
					<td valign="top" colspan="2">
						<table width="552" border="1" bordercolor="#A4A4A4" style="border-collapse: collapse;">
						<!--	<tr>
								<td width="152" valign="top">
									<i><?php echo get_lang("CorrectAnswer"); ?></i><br /><br />
								</td>
						
								<td width="100" valign="top">
									<i><?php echo get_lang('HotspotHit'); ?></i><br /><br />
								</td>
								<td width="300" valign="top">
									<i><?php echo get_lang("Comment"); ?></i><br /><br />
								</td>
							</tr>-->
			<?php
		} else { //matching type
			?>
				<tr>
				<td width="50%">
					<i><?php echo get_lang("ElementList"); ?></i>
				</td>
				<td width="50%">
					<i><?php echo get_lang("CorrespondsTo"); ?></i>
				</td>
				</tr>
			<?php
		}
	} 

	// construction of the Answer object
	$objAnswerTmp=new Answer($questionId);
	$nbrAnswers=$objAnswerTmp->selectNbrAnswers();
	$questionScore=0;
	$correctChoice='Y';
	$noStudentChoice='N';
	$correctComment = array();
	if ($answerType == FREE_ANSWER) {
		$nbrAnswers = 1;
	}
	if ($answerType == FILL_IN_BLANKS) {
	$feedback_data = unserialize($objAnswerTmp -> comment[1]);
	$feedback_true = $feedback_data['comment[1]'];
	$feedback_false = $feedback_data['comment[2]'];
	}
	$correctChoice_unique = 'N';
	$correctChoice_multiple = 'N';
	$correctChoice_reasoning = 'N';
	$correctChoice = 'N';
	$answerWrong = 'N';
	// We're inside *one* question. Go through each possible answer for this question
	for ($answerId=1;$answerId <= $nbrAnswers;$answerId++) {
		
		//select answer of *position*=$answerId
		$answer=$objAnswerTmp->selectAnswer($answerId);
		$answerComment=$objAnswerTmp->selectComment($answerId);
		$correctComment[] =$objAnswerTmp->selectComment($answerId);
		$answerCorrect=$objAnswerTmp->isCorrect($answerId);
		$answerWeighting=$objAnswerTmp->selectWeighting($answerId);
		
		switch ($answerType) {
			// for unique answer
			case UNIQUE_ANSWER :
					// if the student choice is equal to the answer ID
					// then give him the corresponding score
					// (maybe a negative score, positive score or 0)
					// Positive score should only be given when we are going over the right answer
					$studentChoice=($choice == $answerId)?1:0;
					if($studentChoice) {
					  	$questionScore+=$answerWeighting;
						$totalScore+=$answerWeighting;
						if($studentChoice == $answerCorrect)
						{								
						$correctChoice_unique = 'Y';
						}
					}					
					break;
			// for multiple answers
			case MULTIPLE_ANSWER :
					$studentChoice=$choice[$answerId];
					if($studentChoice) {
						$questionScore+=$answerWeighting;
						$totalScore+=$answerWeighting;
						if($studentChoice == $answerCorrect)
						{					
						$correctChoice_multiple = 'Y';
						$feedback_if_true = $objAnswerTmp->selectComment($answerId);
						}
						else
						{		
						$answerWrong = 'Y';
						$feedback_if_false = $objAnswerTmp->selectComment($answerId);
						}	
					}
					break;
			case REASONING :
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
					if($answerCorrect && $studentChoice == '1' && $correctChoice_reasoning == 'N')
					{					
						$correctChoice_reasoning = 'Y';
						$noStudentChoice = 'Y';
					}
					elseif($answerCorrect && !$studentChoice)
					{					
						$correctChoice_reasoning = 'N';
						$noStudentChoice = 'Y';
						$answerWrong = 'Y';	
						break;
					}
					elseif(!$answerCorrect && $studentChoice == '1')
					{					
						$correctChoice_reasoning = 'N';
						$noStudentChoice = 'Y';
						$answerWrong = 'Y';	
						break;
					}					
					break;
			// for fill in the blanks
			case FILL_IN_BLANKS :					
		    		// the question is encoded like this
				    // [A] B [C] D [E] F::10,10,10@1
				    // number 1 before the "@" means that is a switchable fill in blank question
				    // [A] B [C] D [E] F::10,10,10@ or  [A] B [C] D [E] F::10,10,10
				    // means that is a normal fill blank question			

					// first we explode the "::"
					$pre_array = explode('::', $answer);	

					// is switchable fill blank or not
                    $last = count($pre_array)-1;		
					$is_set_switchable = explode('@', $pre_array[$last]);
					
					$switchable_answer_set=false;
					if (isset($is_set_switchable[1]) && $is_set_switchable[1]==1)
					{
						$switchable_answer_set=true;
					}								
					
                    $answer = '';
                    for ($k=0; $k<$last; $k++)
                    {
					  $answer .= $pre_array[$k];
                    }
					
					// splits weightings that are joined with a comma
					$answerWeighting = explode(',',$is_set_switchable[0]);				

					// we save the answer because it will be modified
					$temp=$answer;

					// TeX parsing
					// 1. find everything between the [tex] and [/tex] tags
					$startlocations=api_strpos($temp,'[tex]');
					$endlocations=api_strpos($temp,'[/tex]');

					if($startlocations !== false && $endlocations !== false)
					{
						$texstring=api_substr($temp,$startlocations,$endlocations-$startlocations+6);
						// 2. replace this by {texcode}
						$temp=str_replace($texstring,'{texcode}',$temp);
					}

					$answer='';
					$j=0;
					
                    //initialise answer tags
					$user_tags=array();
					$correct_tags=array();
					$real_text=array();
					// the loop will stop at the end of the text
					while(1)
					{
						// quits the loop if there are no more blanks (detect '[')
						if(($pos = api_strpos($temp,'[')) === false)
						{
							// adds the end of the textsolution
							$answer=$temp;
							// TeX parsing - replacement of texcode tags
							$texstring = api_parse_tex($texstring);
							$answer=str_replace("{texcode}",$texstring,$answer);
                            $real_text[] = $answer;
							break; //no more "blanks", quit the loop
						}
						// adds the piece of text that is before the blank 
                        //and ends with '[' into a general storage array
                        $real_text[]=api_substr($temp,0,$pos+1);
						$answer.=api_substr($temp,0,$pos+1);
						//take the string remaining (after the last "[" we found)
						$temp=api_substr($temp,$pos+1);
						// quit the loop if there are no more blanks, and update $pos to the position of next ']'
						if(($pos = api_strpos($temp,']')) === false)
						{
							// adds the end of the text
							$answer.=$temp;
							break;
						}
						$choice[$j]=trim($choice[$j]);
						$user_tags[]=api_strtolower($choice[$j]);
						//put the contents of the [] answer tag into correct_tags[]
                        $correct_tags[]=api_strtolower(api_substr($temp,0,$pos));
						$j++;
						$temp=api_substr($temp,$pos+1);
                        //$answer .= ']';
					}
														
					$answer='';			
					$real_correct_tags = $correct_tags;							
					$chosen_list=array();
					
					for($i=0;$i<count($real_correct_tags);$i++) {
						if ($i==0)
						{
							$answer.=$real_text[0];
						}
						
						if (!$switchable_answer_set)
						{						
							if ($correct_tags[$i]==$user_tags[$i])
							{
								// gives the related weighting to the student
								$questionScore+=$answerWeighting[$i]; 
								// increments total score
								$totalScore+=$answerWeighting[$i];
								// adds the word in green at the end of the string
								$answer.=$correct_tags[$i]; 
								if($answerWrong == 'N'){
									$correctChoice = 'Y';
								}
							}
							// else if the word entered by the student IS NOT the same as the one defined by the professor											
							elseif(!empty($user_tags[$i]))
							{
								$answerWrong = 'Y';
								// adds the word in red at the end of the string, and strikes it
								$answer.='<font color="red"><s>'.$user_tags[$i].'</s></font>'; 
							}
							else
							{
								$answerWrong = 'Y';
								// adds a tabulation if no word has been typed by the student
								$answer.='&nbsp;&nbsp;&nbsp;';
							}												
						} else { 	
							// switchable fill in the blanks
							if (in_array($user_tags[$i],$correct_tags)) {
								$chosen_list[]=$user_tags[$i];													
								$correct_tags=array_diff($correct_tags,$chosen_list);
												
								// gives the related weighting to the student												
								$questionScore+=$answerWeighting[$i];
								// increments total score
								$totalScore+=$answerWeighting[$i];
								// adds the word in green at the end of the string
								$answer.=$user_tags[$i];
								if($answerWrong == 'N'){
									$correctChoice = 'Y';
								}
							}													// else if the word entered by the student IS NOT the same as the one defined by the professor											
							elseif(!empty($user_tags[$i]))
							{
								$answerWrong = 'Y';
								// adds the word in red at the end of the string, and strikes it
								$answer.='<font color="red"><s>'.$user_tags[$i].'</s></font>'; 
							}
							else
							{
								$answerWrong = 'Y';
								// adds a tabulation if no word has been typed by the student
								$answer.='&nbsp;&nbsp;&nbsp;';
							}												
						}
						// adds the correct word, followed by ] to close the blank
						$answer.=' / <font color="green"><b>'.$real_correct_tags[$i].'</b></font>]';
						if ( isset( $real_text[$i+1] ) ) {
                            $answer.=$real_text[$i+1];
                        }
					}
					break;
			// for free answer
			case FREE_ANSWER :
					$studentChoice=$choice;

					if($studentChoice)
					{
						//Score is at -1 because the question has'nt been corected
					  	$questionScore=-1;
						$totalScore+=0;
					}


					break;
			// for matching
			case MATCHING :
					if($answerCorrect)
					{
						if($answerCorrect == $choice[$answerId])
						{
							$questionScore+=$answerWeighting;
							$totalScore+=$answerWeighting;
							$choice[$answerId]=$matching[$choice[$answerId]];
						}
						elseif(!$choice[$answerId])
						{
							$choice[$answerId]='&nbsp;&nbsp;&nbsp;';
						}
						else
						{
						//	$choice[$answerId]='<font color="red"><s>'.$matching[$choice[$answerId]].'</s></font>';
							$choice[$answerId]=$matching[$choice[$answerId]];
						}
					}
					else
					{
						$matching[$answerId]=$answer;
					}
					if($questionScore == $questionWeighting)
					{
						$correctChoice = 'Y';
					}
					break;
			// for hotspot with no order
			case HOT_SPOT :			
					$studentChoice=$choice[$answerId];
					if ($studentChoice) { //the answer was right
						$questionScore+=$answerWeighting;
						$totalScore+=$answerWeighting;
						$correctChoice = 'Y';
					}
					break;
			// for hotspot with fixed order
			case HOT_SPOT_ORDER :
					$studentChoice=$choice['order'][$answerId];

					if($studentChoice == $answerId)
					{
						$questionScore+=$answerWeighting;
						$totalScore+=$answerWeighting;
						$studentChoice = true;
						if($answerWrong == 'N'){
						$correctChoice = 'Y';
						}
					}
					else
					{
						$answerWrong = 'Y';
						$studentChoice = false;
					}

					break;
		} // end switch Answertype
		
		//display answers (if not matching type, or if the answer is correct)
		if ($answerType != MATCHING || $answerCorrect) {
			if ($answerType == UNIQUE_ANSWER || $answerType == MULTIPLE_ANSWER) {	
				if ($origin!='learnpath') {
					display_unique_or_multiple_answer($answerType, $studentChoice, $answer, $answerComment, $answerCorrect);					
				}
			} elseif ($answerType == REASONING) {	
				if ($origin!='learnpath') {					
					display_reasoning_answer($answerType, $studentChoice, $answer, $answerComment, $answerCorrect);					
				}
			} elseif($answerType == FILL_IN_BLANKS) {
				if ($origin!='learnpath') {
					display_fill_in_blanks_answer($answer);
				}
			} elseif($answerType == FREE_ANSWER) {
				// to store the details of open questions in an array to be used in mail

				$arrques[] = $questionName;
				$arrans[]  = $choice;
				$firstName =   $_SESSION['_user']['firstName'];
				$lastName =   $_SESSION['_user']['lastName'];
				$mail =  $_SESSION['_user']['mail'];
				$coursecode =  $_SESSION['_course']['id'];
				$to = '';
				$teachers = array();
				if(api_get_setting('use_session_mode')=='true' && !empty($_SESSION['id_session']))
				{
					$teachers = CourseManager::get_coach_list_from_course_code($coursecode,$_SESSION['id_session']);
				}
				else
				{
					$teachers = CourseManager::get_teacher_list_from_course_code($coursecode);
				}
				$num = count($teachers);
				if($num>1)
				{
					$to = array();
					foreach($teachers as $teacher)
					{
						$to[] = $teacher['email'];
					}
				}elseif($num>0){
					foreach($teachers as $teacher)
					{
						$to = $teacher['email'];
					}
				}else{
					//this is a problem (it means that there is no admin for this course)
				}
				if($origin != 'learnpath') {
					display_free_answer($choice);
				}
				
			}
			elseif($answerType == HOT_SPOT)
			{					
				if ($origin != 'learnpath') {
					if($studentChoice){
					$answerOk = 'Y';
					}
					else {
					$answerOk = 'N';
					$answerWrong = 'Y';
					}
					$s .= display_hotspot_answer($answerId, $answer, $studentChoice, $answerComment);
				}
			}
			elseif($answerType == HOT_SPOT_ORDER)
			{
				display_hotspot_order_answer($answerId, $answer, $studentChoice, $answerComment);
			}
			else
			{
				if ($origin != 'learnpath') {
				?>
					<tr>
					<td width="50%">
						<?php
						$answer=api_parse_tex($answer);
						echo $answer; ?>
					</td>
					<td width="50%">
						<?php echo $choice[$answerId]; ?> / <font color="green"><b>
						<?php
						$matching[$answerCorrect]=api_parse_tex($matching[$answerCorrect]);
						echo $matching[$answerCorrect]; ?></b></font>
					</td>
					</tr>
				<?php
				}
			}
		}
	} // end for that loops over all answers of the current question

	if ($answerType == REASONING && $noStudentChoice == 'Y'){						
					if($correctChoice_reasoning == 'Y')				
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

	// if answer is hotspot. To the difference of exercise_show.php, we use the results from the session (from_db=0)
	// TODO Change this, because it is wrong to show the user some results that haven't been stored in the database yet
	if ($answerType == HOT_SPOT || $answerType == HOT_SPOT_ORDER) {
			// We made an extra table for the answers
			if($origin != 'learnpath') {
			echo "</table></td></tr>";	//echo Security::remove_XSS($questionId);
			?>
		
			<tr>
				<table width="100%" border="0"><tr>
				<td colspan="2">					
					<object type="application/x-shockwave-flash" data="../plugin/hotspot/hotspot_solution.swf?modifyAnswers=<?php echo Security::remove_XSS($questionId); ?>&exe_id=&from_db=0" width="610" height="410">
						<param name="movie" value="../plugin/hotspot/hotspot_solution.swf?modifyAnswers=<?php echo Security::remove_XSS($questionId); ?>&exe_id=&from_db=0" />
					</object>
				</td>
				<td colspan="2" width="40%" valign="top"><div class="quiz_content_actions" style="height:380px;"><div class="quiz_header"><?php echo get_lang('Feedback'); ?></div><div align="center"><img src="../img/MouseHotspots64.png"></div><br/>
				<table class="data_table" width="100%" border="1">
				<?php echo $s;?>
				</table><br/>
				<div align="center">
				<?php 
				if($answerOk == 'Y' && $answerWrong == 'N'){
					if($nbrAnswers == 1){
						$correctComment = explode("~", $objAnswerTmp->selectComment(1));
						$feedback = $correctComment[0];
					}
					else {
						$feedback = $objAnswerTmp->selectComment(1);
					}
				}
				else {
					if($nbrAnswers == 1){
						$correctComment = explode("~", $objAnswerTmp->selectComment(1));
						$feedback = $correctComment[1];
					}
					else {
						$feedback = $objAnswerTmp->selectComment(2);
					}				
				}
				if(!empty($feedback)){
				echo get_lang('Feedback').' : '.$feedback ;
				}
				?>
				</div>
				</div>
				</td>
				</tr></table>
			</tr>
			<?php 
			} 
		}
	?>
	<?php if($origin != 'learnpath') { 
		echo '<tr><td colspan="3">';
		if($answerType == MATCHING)
		{
					if($questionScore == $questionWeighting)
					{
						echo '<b>'.get_lang('Feedback').' </b><br /><span>'.nl2br(make_clickable($correctComment[0])).'</span><br />';	
					}
					else
					{
						echo '<b>'.get_lang('Feedback').' </b><br /><span>'.nl2br(make_clickable($correctComment[1])).'</span><br />';
					}
		}
		elseif($answerType != FILL_IN_BLANKS && $answerType != FREE_ANSWER)
		{
			
					if($answerType == UNIQUE_ANSWER)
					{							
						if($correctChoice_unique == 'Y')
						{
							echo '<b>'.get_lang('Feedback').' </b><br /><span>'.nl2br(make_clickable($correctComment[0])).'</span><br />';	
						}
						else
						{
							echo '<b>'.get_lang('Feedback').' </b><br /><span>'.nl2br(make_clickable($correctComment[1])).'</span><br />';
						}
					}
					if($answerType == MULTIPLE_ANSWER)
					{						
						if($correctChoice_multiple == 'Y' && $answerWrong == 'N')
						{
							echo '<b>'.get_lang('Feedback').'  </b><span>'.nl2br(make_clickable($feedback_if_true)).'</span>';	
						}
						else
						{
							echo '<b>'.get_lang('Feedback').'  </b><span>'.nl2br(make_clickable($feedback_if_false)).'</span>';
						}
					}
					if($answerType == REASONING)
					{							
						if($correctChoice_reasoning == 'Y' && $answerWrong == 'N')
						{
							echo '<b>'.get_lang('Feedback').' - '.get_lang('FeedbackReason').'  </b><span>'.nl2br(make_clickable($feedback_if_true)).'</span>';	
						}
						else
						{
							echo '<b>'.get_lang('Feedback').' - '.get_lang('FeedbackReason').'  </b><span>'.nl2br(make_clickable($feedback_if_false)).'</span>';
						}
					}	
		}
					echo '</td></tr>';
	?>		
		<tr>
		<td colspan="<?php echo $colspan; ?>" align="left"><br /><div class="sectiontitle">
			<b>
			<?php
			if($questionScore==-1){ 
				echo get_lang('Score').": 0 /".float_format($questionWeighting);
			} else {					
				echo get_lang('Score').": ".round(float_format($questionScore,1))."/".float_format($questionWeighting,1);
			}
			?></b>
		</div></td>
		</tr>
		</table>
		<?php } ?>
	<?php
	// destruction of Answer
	unset($objAnswerTmp);

	$i++;

	$totalWeighting+=$questionWeighting;
	//added by priya saini
	if($_configuration['tracking_enabled']) {
		if(empty($choice)){
			$choice = 0;
		}
		if ($answerType==MULTIPLE_ANSWER ) {				
			if ($choice != 0) {					
				$reply = array_keys($choice);																									
				for ($i=0;$i<sizeof($reply);$i++) {
					$ans = $reply[$i];
					exercise_attempt($questionScore,$ans,$quesId,$exeId,$i);
				}
			} else {
				exercise_attempt($questionScore, 0 ,$quesId,$exeId,0);
			}
		}  elseif ($answerType == REASONING) {			
			if ($choice != 0) {
				$reply = array_keys($choice);

				for ($i = 0; $i < sizeof($reply); $i++) {
					$ans = $reply[$i];			

					exercise_attempt($questionScore, $ans, $quesId, $exeId, $i);
				}
			} else {			
				exercise_attempt($questionScore, 0, $quesId, $exeId, 0);
			}
		} elseif ($answerType==MATCHING) {
			$j=sizeof($matching)+1;
			$sql1 = "SELECT * FROM $table_ans WHERE question_id ='".Database::escape_string($questionId)."' AND ponderation = 0.00";
			$res1 = api_sql_query($sql1, __FILE__, __LINE__);
			$numrows = Database::num_rows($res1);			
				for ($i=0;$i<sizeof($choice);$i++) {
				$val = $choice[$j];
				
				if (preg_match_all ('#<font color="red"><s>([0-9a-z ]*)</s></font>#', $val, $arr1))
				{
					$val = $arr1[1][0];
				}
				
			//	$val=strip_tags($val);
				$sql = "SELECT position from $table_ans where question_id='".Database::escape_string($questionId)."' and answer LIKE  '".Database::escape_string($val)."' AND correct=0";				
				$res = api_sql_query($sql, __FILE__, __LINE__);
				if (Database::num_rows($res)>0) {					
					$answer = Database::result($res,0,"position");					
				} else {					
					$answer = 0;					
				}	
				
				exercise_attempt($questionScore,$answer,$quesId,$exeId,$j);
				if($numrows < sizeof($matching))
				{
					$j--;
				}
				else
				{
					$j++;
				}
			}
		}
		elseif ($answerType==FREE_ANSWER) {
			$answer = $choice;
			exercise_attempt($questionScore,$answer,$quesId,$exeId,0);
		}
		elseif ($answerType==UNIQUE_ANSWER) {
			// exercise_attempt($questionScore,$answer,$quesId,$exeId,0);
			// In fact, we are not storing the results by answer ID, but by *position*, which is stored in $choice
			exercise_attempt($questionScore,$choice,$quesId,$exeId,0);
		} elseif ($answerType == HOT_SPOT) {
			exercise_attempt($questionScore, $answer, $quesId, $exeId, 0);
			if (is_array($exerciseResultCoordinates[$quesId])) {
				foreach($exerciseResultCoordinates[$quesId] as $idx => $val) {
					exercise_attempt_hotspot($exeId,$quesId,$idx,$choice[$idx],$val);
				}
			}
		} else {
			exercise_attempt($questionScore,$answer,$quesId,$exeId,0);
		}
	}
	echo '</td></tr></table></div></div>';
} // end huge foreach() block that loops over all questions
?>

	</form>

	<br /><br />
<?php 
if ($origin == 'learnpath') {	
	//Display::display_normal_message(get_lang('ExerciseFinished'));
	$lp_mode =  $_SESSION['lp_mode'];	
	$url = '../newscorm/lp_controller.php?cidReq='.api_get_course_id().'&action=view&lp_id='.$learnpath_id.'&lp_item_id='.$learnpath_item_id.'&exeId='.$exeId.'&fb_type='.$feedback_type;
	$href = ($lp_mode == 'fullscreen')?' window.opener.location.href="'.$url.'" ':' top.location.href="'.$url.'" ';	 
	echo '<script language="javascript" type="text/javascript">'.$href.'</script>'."\n";
}	
/*
==============================================================================
		Tracking of results
==============================================================================
*/

if ($_configuration['tracking_enabled']) {
	//	Updates the empty exercise  
	$safe_lp_id = $learnpath_id==''?0:(int)$learnpath_id;
	$safe_lp_item_id = $learnpath_item_id==''?0:(int)$learnpath_item_id;
	$quizDuration = (!empty($_SESSION['quizStartTime']) ? time() - $_SESSION['quizStartTime'] : 0);
	update_event_exercice($exeId, $objExercise->selectId(),$totalScore,$totalWeighting,api_get_session_id(),$safe_lp_id,$safe_lp_item_id,$quizDuration);
}

if($objExercise->results_disabled) {
	ob_end_clean();
	if ($origin != 'learnpath') {
		echo '<div class="quiz_content_actions">'.get_lang('ExerciseFinished').'<br /><br /><a href="exercice.php" />'.get_lang('Back').'</a></div>';
	//	Display :: display_normal_message(get_lang('ExerciseFinished').'<br /><a href="exercice.php" />'.get_lang('Back').'</a>',false);
	} else {
		echo '<div class="quiz_content_actions">'.get_lang('ExerciseFinished').'<br /><br />'.'</div>';
	//	Display :: display_normal_message(get_lang('ExerciseFinished').'<br /><br />',false);
	}
}

if ($origin != 'learnpath') {
	//we are not in learnpath tool
	if(!$objExercise->results_disabled){
	echo '</div>';
	}

 echo '<div class="actions">';
 if($origin != 'learnpath' && !$objExercise->results_disabled) { 
	 ?>
	<div>	
		<b>
		<?php echo get_lang('YourTotalScore')." ";
		if ($dsp_percent == true) {
		  echo number_format(($totalScore/$totalWeighting)*100,1,'.','')."%";
		} else {
		  echo round(float_format($totalScore,1))."/".float_format($totalWeighting,1);
		}
		?>
		</b>		
		<!--<button type="submit" class="save"><?php echo get_lang('Finish');?></button>-->			
	</div>
<?php } 
 	echo '</div>';
	Display::display_footer();
} else {
	//record the results in the learning path, using the SCORM interface (API)
	echo '<script language="javascript" type="text/javascript">window.parent.API.void_save_asset('.$totalScore.','.$totalWeighting.');</script>'."\n";
	echo '</body></html>';
}

if(count($arrques)>0) {
	$mycharset = api_get_setting('platform_charset');
	$msg = '<html><head>
		<link rel="stylesheet" href="'.api_get_path(WEB_CODE_PATH).'css/'.api_get_setting('stylesheets').'/default.css" type="text/css">
		<meta content="text/html; charset='.$mycharset.'" http-equiv="content-type">';
	$msg .= '</head>
	<body><br />
	<p>'.get_lang('OpenQuestionsAttempted').' : 
	</p>
	<p>'.get_lang('AttemptDetails').' : ><br />
	</p>
	<table width="730" height="136" border="0" cellpadding="3" cellspacing="3">
						<tr>
	    <td width="229" valign="top"><h2>&nbsp;&nbsp;'.get_lang('CourseName').'</h2></td>
	    <td width="469" valign="top"><h2>#course#</h2></td>
	  </tr>
	  <tr>
	    <td width="229" valign="top" class="outerframe">&nbsp;&nbsp;'.get_lang('TestAttempted').'</span></td>
	    <td width="469" valign="top" class="outerframe">#exercise#</td>
	  </tr>
	  <tr>
	    <td valign="top">&nbsp;&nbsp;<span class="style10">'.get_lang('StudentName').'</span></td>
	    <td valign="top" >#firstName# #lastName#</td>
	  </tr>
	  <tr>
	    <td valign="top" >&nbsp;&nbsp;'.get_lang('StudentEmail').' </td>
	    <td valign="top"> #mail#</td>
	</tr></table>
	<p><br />'.get_lang('OpenQuestionsAttemptedAre').' :</p>
	 <table width="730" height="136" border="0" cellpadding="3" cellspacing="3">';
	for($i=0;$i<sizeof($arrques);$i++) {
		  $msg.='
			<tr>
		    <td width="220" valign="top" bgcolor="#E5EDF8">&nbsp;&nbsp;<span class="style10">'.get_lang('Question').'</span></td>
		    <td width="473" valign="top" bgcolor="#F3F3F3"><span class="style16"> #questionName#</span></td>
		  	</tr>
		  	<tr>
		    <td width="220" valign="top" bgcolor="#E5EDF8">&nbsp;&nbsp;<span class="style10">'.get_lang('Answer').' </span></td>
		    <td valign="top" bgcolor="#F3F3F3"><span class="style16"> #answer#</span></td>
		  	</tr>';
		
			$msg1= str_replace("#exercise#",$exerciseTitle,$msg);
			$msg= str_replace("#firstName#",$firstName,$msg1);
			$msg1= str_replace("#lastName#",$lastName,$msg);
			$msg= str_replace("#mail#",$mail,$msg1);
			$msg1= str_replace("#questionName#",$arrques[$i],$msg);
			$msg= str_replace("#answer#",$arrans[$i],$msg1);
			$msg1= str_replace("#i#",$i,$msg);
			$msg= str_replace("#course#",$courseName,$msg1);
	}
		$msg.='</table><br>
	 	<span class="style16">'.get_lang('ClickToCommentAndGiveFeedback').',<br />
	<a href="#url#">#url#</a></span></body></html>';
	
		$msg1= str_replace("#url#",$url,$msg);
		$mail_content = $msg1;
		$student_name = $_SESSION['_user']['firstName'].' '.$_SESSION['_user']['lastName'];
		$subject = get_lang('OpenQuestionsAttempted');
		
		$from = api_get_setting('noreply_email_address');
		if($from == '') {
			if(isset($_SESSION['id_session']) && $_SESSION['id_session'] != ''){
				$sql = 'SELECT user.email,user.lastname,user.firstname FROM '.TABLE_MAIN_SESSION.' as session, '.TABLE_MAIN_USER.' as user 
						WHERE session.id_coach = user.user_id
						AND session.id = "'.Database::escape_string($_SESSION['id_session']).'"
						';
				$result=api_sql_query($sql,__FILE__,__LINE__);
				$from = Database::result($result,0,'email');
				$from_name = Database::result($result,0,'firstname').' '.Database::result($result,0,'lastname');
			} else {
				$array = explode(' ',$_SESSION['_course']['titular']);
				$firstname = $array[1];
				$lastname = $array[0];
				$sql = 'SELECT email,lastname,firstname FROM '.TABLE_MAIN_USER.'
						WHERE firstname = "'.Database::escape_string($firstname).'"
						AND lastname = "'.Database::escape_string($lastname).'"
				';
				$result=api_sql_query($sql,__FILE__,__LINE__);
				$from = Database::result($result,0,'email');
				$from_name = Database::result($result,0,'firstname').' '.Database::result($result,0,'lastname');
			}
		}	
	api_mail_html($student_name, $to, $subject, $mail_content, $from_name, $from, array('encoding'=>$mycharset,'charset'=>$mycharset));
}

?>