<?php
// $Id: question_pool.php 20451 2009-05-10 12:02:22Z ivantcholakov $

/*
 ==============================================================================
 Dokeos - elearning and course management software

 Copyright (c) 2004-2009 Dokeos SPRL
 Copyright (c) 2003 Ghent University (UGent)
 Copyright (c) 2001 Universite catholique de Louvain (UCL)
 Copyright (c) various contributors

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
 * 	Question Pool
 * 	This script allows administrators to manage questions and add them into their exercises.
 * 	One question can be in several exercises
 * 	@package dokeos.exercise
 * 	@author Olivier Brouckaert
 * 	@version $Id: question_pool.php 20451 2009-05-10 12:02:22Z ivantcholakov $
 */
// name of the language file that needs to be included
$language_file = 'exercice';

include_once 'exercise.class.php';
include_once 'question.class.php';
include_once 'answer.class.php';
include_once '../inc/global.inc.php';
require_once '../newscorm/learnpath.class.php';

$this_section = SECTION_COURSES;

$is_allowedToEdit = api_is_allowed_to_edit();

$TBL_EXERCICE_QUESTION = Database::get_course_table(TABLE_QUIZ_TEST_QUESTION);
$TBL_EXERCICES = Database::get_course_table(TABLE_QUIZ_TEST);
$TBL_QUESTIONS = Database::get_course_table(TABLE_QUIZ_QUESTION);
$TBL_REPONSES = Database::get_course_table(TABLE_QUIZ_ANSWER);

$TBL_QUESTIONS_TEMPLATE = Database::get_main_table(TABLE_MAIN_QUIZ_QUESTION_TEMPLATES);
$TBL_REPONSES_TEMPLATE = Database::get_main_table(TABLE_MAIN_QUIZ_ANSWER_TEMPLATES);

// Variable
$learnpath_id = Security::remove_XSS($_GET['lp_id']);
// Lp object
if (isset($_SESSION['lpobject'])) {
 if ($debug > 0)
  error_log('New LP - SESSION[lpobject] is defined', 0);
 $oLP = unserialize($_SESSION['lpobject']);
 if (is_object($oLP)) {
  if ($debug > 0)
   error_log('New LP - oLP is object', 0);
  if ($myrefresh == 1 OR (empty($oLP->cc)) OR $oLP->cc != api_get_course_id()) {
   if ($debug > 0)
    error_log('New LP - Course has changed, discard lp object', 0);
   if ($myrefresh == 1) {
    $myrefresh_id = $oLP->get_id();
   }
   $oLP = null;
   api_session_unregister('oLP');
   api_session_unregister('lpobject');
  } else {
   $_SESSION['oLP'] = $oLP;
   $lp_found = true;
  }
 }
}

// we set the encoding of the lp
/*if (!empty($_SESSION['oLP']->encoding)) {
	$charset = $_SESSION['oLP']->encoding;
} else {
	$charset = api_get_system_encoding();
}
if (empty($charset)) {
	$charset = 'ISO-8859-1';
}*/

// Add the extra lp_id parameter to some links
$add_params_for_lp = '';
if (isset($_GET['lp_id'])) {
  $add_params_for_lp = "&lp_id=".$learnpath_id;
}

if(isset($_REQUEST['fromExercise']))
{
	$fromExercise = $_REQUEST['fromExercise'];
}

if (!empty($gradebook) && $gradebook == 'view') {
	$interbreadcrumb[] = array(
     'url' => '../gradebook/' . $_SESSION['gradebook_dest'],
     'name' => get_lang('Gradebook')
	);
}

$nameTools = get_lang('QuestionPool');

$interbreadcrumb[] = array("url" => "exercice.php", "name" => get_lang('Exercices'));

// if admin of course
if ($is_allowedToEdit) {
	Display::display_tool_header($nameTools, 'Exercise');

	 $exercice_id = Security::remove_XSS($_REQUEST['fromExercise']);
	 
	// Main buttons
	 echo '<div class="actions">';
	 if (isset($_GET['lp_id']) && $_GET['lp_id'] > 0) {
     
    //$lp_id = Security::remove_XSS($_GET['lp_id']);
    // The lp_id parameter will be added by javascript
     $return = "";
     $return.= '<a href="../newscorm/lp_controller.php?' . api_get_cidreq() . '">' . Display::return_icon('pixel.gif', get_lang("Author"), array('class' => 'toolactionplaceholdericon toolactionauthor')).get_lang("Author") . '</a>';
     $return.= '<a href="../newscorm/lp_controller.php?' . api_get_cidreq() . '&action=add_item&type=step">' . Display::return_icon('pixel.gif', get_lang("Content"), array('class' => 'toolactionplaceholdericon toolactionauthorcontent')).get_lang("Content") . '</a>';
     echo $return;
   }
   else
	{
	 echo '<a href="exercice.php?' . api_get_cidreq() . '">' . Display::return_icon('pixel.gif', get_lang('List'), array('class' => 'toolactionplaceholdericon toolactionback')). get_lang('List') . '</a>';
	}
	 echo '<a href="exercise_admin.php?' . api_get_cidreq() . '">' . Display::return_icon('pixel.gif', get_lang('NewEx'), array('class' => 'toolactionplaceholdericon toolactionnewquiz')) . get_lang('NewEx') . '</a>';
	 echo '<a href="admin.php?' . api_get_cidreq() . '&exerciseId=' . $exercice_id . '">' . Display::return_icon('pixel.gif', get_lang('Questions'), array('class' => 'toolactionplaceholdericon toolactionquestion')) . get_lang('Questions') . '</a>';
	echo '</div>';
	?>

<div id="content">
<style>
	.quiztpl_actions {
	background-color:#fff;
	/* gradient background: Mozilla, Chrome/Safari, MSIE */
	background:-moz-linear-gradient(center top , #eaeaea, #FFFFFF);
	background: -webkit-gradient(linear,left top, left bottom, from(#eaeaea), to(#ffffff));
	filter: progid:DXImageTransform.Microsoft.Gradient(StartColorStr="#eaeaea", EndColorStr="#ffffff", GradientType=0);
	/* rounded corners */
	border:1px solid #b8b8b6;
	border-radius:5px;
	-moz-border-radius: 5px;
	-webkit-border-radius: 5px;	
	margin-bottom: 5px;
	margin-top: 5px;
	padding: 10px;
	overflow:hidden;
	vertical-align:middle;
}
</style>
<?php

echo '<table><tr>
<td><div class="quiztpl_actions"><table><tr><td><a href="admin.php?'.api_get_cidreq().'&fromTpl=1&editQuestion=1&fromExercise='.$fromExercise.$add_params_for_lp.'">'.Display::return_icon('pixel.gif', get_lang('Truefalse'), array('class' => 'quiztemplateplaceholdericon quiztpl_01true_false')).'</a></td></tr><tr><td><a href="admin.php?'.api_get_cidreq().'&fromTpl=1&editQuestion=2&fromExercise='.$fromExercise.$add_params_for_lp.'">'.Display::return_icon('pixel.gif', get_lang('Multiplechoice'), array('class' => 'quiztemplateplaceholdericon quiztpl_02multiple_choice')).'</a></td></tr><tr><td><a href="admin.php?'.api_get_cidreq().'&fromTpl=1&editQuestion=3&fromExercise='.$fromExercise.$add_params_for_lp.'">'.Display::return_icon('pixel.gif', get_lang('Multiplechoicesequence'), array('class' => 'quiztemplateplaceholdericon quiztpl_03multiple_choice_sequence')).'</a></td></tr><tr><td><a href="admin.php?'.api_get_cidreq().'&fromTpl=1&editQuestion=4&fromExercise='.$fromExercise.$add_params_for_lp.'">'.Display::return_icon('pixel.gif', get_lang('Justifiedmultiplechoice'), array('class' => 'quiztemplateplaceholdericon quiztpl_04true_false_justified')).'</a></td></tr><tr><td><a href="admin.php?'.api_get_cidreq().'&fromTpl=1&editQuestion=5&fromExercise='.$fromExercise.$add_params_for_lp.'">'.Display::return_icon('pixel.gif', get_lang('Noneoftheabove'), array('class' => 'quiztemplateplaceholdericon quiztpl_05none_of_the_above')).'</a></td></tr></table></div></td>
<td><div class="quiztpl_actions"><table><tr><td><a href="admin.php?'.api_get_cidreq().'&fromTpl=1&editQuestion=6&fromExercise='.$fromExercise.$add_params_for_lp.'">'.Display::return_icon('pixel.gif', get_lang('Multiplechoiceimage'), array('class' => 'quiztemplateplaceholdericon quiztpl_06mc_image')).'</td></tr><tr><td><a href="admin.php?'.api_get_cidreq().'&fromTpl=1&editQuestion=7&fromExercise='.$fromExercise.$add_params_for_lp.'">'.Display::return_icon('pixel.gif', get_lang('Multiplechoicesound'), array('class' => 'quiztemplateplaceholdericon quiztpl_07mc_audio')).'</a></td></tr><tr><td><a href="admin.php?'.api_get_cidreq().'&fromTpl=1&editQuestion=8&fromExercise='.$fromExercise.$add_params_for_lp.'">'.Display::return_icon('pixel.gif', get_lang('Multiplechoicescreencast'), array('class' => 'quiztemplateplaceholdericon quiztpl_08mc_screencast')).'</a></td></tr><tr><td><a href="admin.php?'.api_get_cidreq().'&fromTpl=1&editQuestion=9&fromExercise='.$fromExercise.$add_params_for_lp.'">'.Display::return_icon('pixel.gif', get_lang('Multiplechoiceflash'), array('class' => 'quiztemplateplaceholdericon quiztpl_09mc_flash')).'</a></td></tr><tr><td><a href="admin.php?'.api_get_cidreq().'&fromTpl=1&editQuestion=10&fromExercise='.$fromExercise.$add_params_for_lp.'">'.Display::return_icon('pixel.gif', get_lang('Multiplechoicevideo'), array('class' => 'quiztemplateplaceholdericon quiztpl_10mc_video')).'</a></td></tr></table></div></td>
<td valign="top"><div class="quiztpl_actions"><table><tr><td><a href="admin.php?'.api_get_cidreq().'&fromTpl=1&editQuestion=11&fromExercise='.$fromExercise.$add_params_for_lp.'">'.Display::return_icon('pixel.gif', get_lang('Multipleinclusion'), array('class' => 'quiztemplateplaceholdericon quiztpl_11ma_identify')).'</a></td></tr><tr><td><a href="admin.php?'.api_get_cidreq().'&fromTpl=1&editQuestion=12&fromExercise='.$fromExercise.$add_params_for_lp.'">'.Display::return_icon('pixel.gif', get_lang('Multipleexclusion'), array('class' => 'quiztemplateplaceholdericon quiztpl_12ma_remove')).'</a></td></tr><tr><td><a href="admin.php?'.api_get_cidreq().'&fromTpl=1&editQuestion=13&fromExercise='.$fromExercise.$add_params_for_lp.'">'.Display::return_icon('pixel.gif', get_lang('Multipleanswerimage'), array('class' => 'quiztemplateplaceholdericon quiztpl_13ma_identify_image')).'</a></td></tr><tr><td><a href="admin.php?'.api_get_cidreq().'&fromTpl=1&editQuestion=14&fromExercise='.$fromExercise.$add_params_for_lp.'">'.Display::return_icon('pixel.gif', get_lang('Allitemsneeded'), array('class' => 'quiztemplateplaceholdericon quiztpl_14reasoning')).'</a></td></tr><tr><td class="quiz_tpl_table">&nbsp;</td></tr></table></div></td>
<td valign="top"><div class="quiztpl_actions"><table><tr><td><a href="admin.php?'.api_get_cidreq().'&fromTpl=1&editQuestion=15&fromExercise='.$fromExercise.$add_params_for_lp.'">'.Display::return_icon('pixel.gif', get_lang('Fillinaword'), array('class' => 'quiztemplateplaceholdericon quiztpl_15fill_blank_text')).'</a></td></tr><tr><td><a href="admin.php?'.api_get_cidreq().'&fromTpl=1&editQuestion=16&fromExercise='.$fromExercise.$add_params_for_lp.'">'.Display::return_icon('pixel.gif', get_lang('Calculatedanswer'), array('class' => 'quiztemplateplaceholdericon quiztpl_16fill_math')).'</a></td></tr><tr><td><a href="admin.php?'.api_get_cidreq().'&fromTpl=1&editQuestion=17&fromExercise='.$fromExercise.$add_params_for_lp.'">'.Display::return_icon('pixel.gif', get_lang('Itemtable'), array('class' => 'quiztemplateplaceholdericon quiztpl_17fill_table')).'</a></td></tr><tr><td><a href="admin.php?'.api_get_cidreq().'&fromTpl=1&editQuestion=18&fromExercise='.$fromExercise.$add_params_for_lp.'">'.Display::return_icon('pixel.gif', get_lang('Listeningcomprehension'), array('class' => 'quiztemplateplaceholdericon quiztpl_18listening_comprehension')).'</a></td></tr><tr><td style="height:81px;"><a href="admin.php?'.api_get_cidreq().'&fromTpl=1&editQuestion=19&fromExercise='.$fromExercise.$add_params_for_lp.'">'.Display::return_icon('pixel.gif', get_lang('Crosswords'), array('class' => 'quiztemplateplaceholdericon quiztpl_18listening_crosswords')).'</a></td></tr></table></div></td>
<td valign="top"><div class="quiztpl_actions"><table><tr><td><a href="admin.php?'.api_get_cidreq().'&fromTpl=1&editQuestion=20&fromExercise='.$fromExercise.$add_params_for_lp.'">'.Display::return_icon('pixel.gif', get_lang('Openquestion'), array('class' => 'quiztemplateplaceholdericon quiztpl_19open_question')).'</a></td></tr><tr><td><a href="admin.php?'.api_get_cidreq().'&fromTpl=1&editQuestion=21&fromExercise='.$fromExercise.$add_params_for_lp.'">'.Display::return_icon('pixel.gif', get_lang('Multiplechoicejustified'), array('class' => 'quiztemplateplaceholdericon quiztpl_20bopen_justify_mc')).'</a></td></tr><tr><td><a href="admin.php?'.api_get_cidreq().'&fromTpl=1&editQuestion=22&fromExercise='.$fromExercise.$add_params_for_lp.'">'.Display::return_icon('pixel.gif', get_lang('Commentmap'), array('class' => 'quiztemplateplaceholdericon quiztpl_20open_map')).'</a></td></tr><tr><td class="quiz_tpl_table">&nbsp;</td></tr><tr><td class="quiz_tpl_table">&nbsp;</td></tr></table></div></td>
<td valign="top"><div class="quiztpl_actions"><table><tr><td><a href="admin.php?'.api_get_cidreq().'&fromTpl=1&editQuestion=23&fromExercise='.$fromExercise.$add_params_for_lp.'&answerType=4">'.Display::return_icon('pixel.gif', get_lang('Wordsmatching'), array('class' => 'quiztemplateplaceholdericon quiztpl_21matching')).'</a></td></tr><tr><td><a href="admin.php?'.api_get_cidreq().'&fromTpl=1&editQuestion=24&fromExercise='.$fromExercise.$add_params_for_lp.'&answerType=4">'.Display::return_icon('pixel.gif', get_lang('Makerightsequence'), array('class' => 'quiztemplateplaceholdericon quiztpl_22ordering')).'</a></td></tr><tr><td><a href="admin.php?'.api_get_cidreq().'&fromTpl=1&editQuestion=25&fromExercise='.$fromExercise.$add_params_for_lp.'&answerType=4">'.Display::return_icon('pixel.gif', get_lang('Logicevidence'), array('class' => 'quiztemplateplaceholdericon quiztpl_23bmatch_assemble_proof')).'</a></td></tr><tr><td><a href="admin.php?'.api_get_cidreq().'&fromTpl=1&editQuestion=26&fromExercise='.$fromExercise.$add_params_for_lp.'&answerType=4">'.Display::return_icon('pixel.gif', get_lang('Imagesmatching'), array('class' => 'quiztemplateplaceholdericon quiztpl_23match_image')).'</a></td></tr><tr><td class="quiz_tpl_table">&nbsp;</td></tr></table></div></td>
<td valign="top"><div class="quiztpl_actions"><table><tr><td><a href="admin.php?'.api_get_cidreq().'&fromTpl=1&editQuestion=27&fromExercise='.$fromExercise.$add_params_for_lp.'&answerType=6">'.Display::return_icon('pixel.gif', get_lang('Imagezone'), array('class' => 'quiztemplateplaceholdericon quiztpl_24hotspots')).'</a></td></tr><tr><td><a href="admin.php?'.api_get_cidreq().'&fromTpl=1&editQuestion=28&fromExercise='.$fromExercise.$add_params_for_lp.'&answerType=6">'.Display::return_icon('pixel.gif', get_lang('Sequencediagram'), array('class' => 'quiztemplateplaceholdericon quiztpl_25hotspots_organigram')).'</a></td></tr><tr><td><a href="admin.php?'.api_get_cidreq().'&fromTpl=1&editQuestion=29&fromExercise='.$fromExercise.$add_params_for_lp.'&answerType=6">'.Display::return_icon('pixel.gif', get_lang('Sequencescreenshot'), array('class' => 'quiztemplateplaceholdericon quiztpl_26hotspots_screen')).'</a></td></tr><tr><td><a href="admin.php?'.api_get_cidreq().'&fromTpl=1&editQuestion=30&fromExercise='.$fromExercise.$add_params_for_lp.'&answerType=6">'.Display::return_icon('pixel.gif', get_lang('Datatable'), array('class' => 'quiztemplateplaceholdericon quiztpl_27hotspots_table')).'</a></td></tr><tr><td class="quiz_tpl_table">&nbsp;</td></tr></table></div></td>
</tr></table>';
	
         } else {
          // if not admin of course
          api_not_allowed(true);
         }
 ?>

 </div>

<?php
  if (api_is_allowed_to_edit ()) {
	  $organize_lang_var = api_convert_encoding(get_lang('Organize'), $charset, api_get_system_encoding());	  
 ?>
          <div class="actions">
		  <?php
		  if (isset($_GET['lp_id']) && $_GET['lp_id'] > 0) {
     $return = '';
     // The lp_id parameter will be added by Javascript
  //   $return.= '<a href="../newscorm/lp_controller.php?' . api_get_cidreq() . '">' . Display::return_icon('build.png', get_lang('Build')).get_lang("Build") . '</a>';
     $return.= '<a href="../newscorm/lp_controller.php?' . api_get_cidreq() . '&gradebook=&action=admin_view">' . Display::return_icon('organize.png', $organize_lang_var).$organize_lang_var . '</a>';
     $return.= '<a href="../newscorm/lp_controller.php?' . api_get_cidreq() . '&gradebook=&action=view">' . Display::return_icon('view.png', get_lang('ViewRight')).get_lang("ViewRight") . '</a>';
     echo $return;
   } else {
        if (isset($_GET['exerciseId'])) {
            $quiz_id = Security::remove_XSS($_GET['exerciseId']);
        } elseif (isset($_GET['fromExercise'])) {
            $quiz_id = Security::remove_XSS($_GET['fromExercise']);
        }
	   ?>
           <a href="<?php echo 'exercice.php?show=result&' . api_get_cidreq(); ?>"><?php echo Display::return_icon('pixel.gif', get_lang('Tracking'), array('class' => 'actionplaceholdericon actiontracking')) . get_lang('Tracking') ?></a>
           <a href="<?php echo 'question_pool.php?fromExercise=' . $quiz_id . '&' . api_get_cidreq(); ?>"><?php echo Display::return_icon('pixel.gif', get_lang('QuizQuestionsPool'), array('class' => 'actionplaceholdericon actionquestionpool')) . get_lang('QuizQuestionsPool') ?></a>
		   <?php
   }
	   ?>
          </div>
<?php
  }
  Display::display_footer();
?>
