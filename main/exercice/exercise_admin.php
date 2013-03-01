<?php

/* For licensing terms, see /dokeos_license.txt */

/**
==============================================================================
* 	Exercise Administration
*	@package dokeos.exercise
==============================================================================
*/

define('DOKEOS_EXERCISE', true);

// Language files that should be included
$language_file = array('exercice','admin');
// setting the help
$help_content = 'exerciselist';

// including the global library
require_once '../inc/global.inc.php';

// including additional libraries
include('exercise.class.php');
include('question.class.php');
include('answer.class.php');
include('exercise.lib.php');
include_once(api_get_path(LIBRARY_PATH).'formvalidator/FormValidator.class.php');
require_once '../newscorm/learnpath.class.php';
require_once '../newscorm/learnpathItem.class.php';
// setting the tabs
$this_section=SECTION_COURSES;

if(!api_is_allowed_to_edit()) {
	api_not_allowed(true);
}

// Add additional javascript, css
$htmlHeadXtra[] = '<script type="text/javascript" src="'.api_get_path(WEB_LIBRARY_PATH).'javascript/jquery-1.4.2.min.js" language="javascript"></script>';
$htmlHeadXtra[] = '<style> .media { display:none;}</style>';		// to hide the exercise description 	
$htmlHeadXtra[] = '<script>
		function advanced_parameters() {
			if(document.getElementById(\'options\').style.display == \'none\') {
				document.getElementById(\'options\').style.display = \'block\';
				document.getElementById(\'img_plus_and_minus\').innerHTML=\'&nbsp;<img style="vertical-align:middle;" src="../img/div_show.gif" alt="" />&nbsp;'.get_lang('AdvancedParameters').'\';

			} else {

				document.getElementById(\'options\').style.display = \'none\';
				document.getElementById(\'img_plus_and_minus\').innerHTML=\'&nbsp;<img style="vertical-align:middle;" src="../img/div_hide.gif" alt="" />&nbsp;'.get_lang('AdvancedParameters').'\';
			}
		}


		function FCKeditor_OnComplete( editorInstance )
			{
			   if (document.getElementById ( \'HiddenFCK\' + editorInstance.Name )) {
			      HideFCKEditorByInstanceName (editorInstance.Name);
			   }
			}

			function HideFCKEditorByInstanceName ( editorInstanceName ) {
			   if (document.getElementById ( \'HiddenFCK\' + editorInstanceName ).className == "HideFCKEditor" ) {
			      document.getElementById ( \'HiddenFCK\' + editorInstanceName ).className = "media";
			      }
			}
		function show_media() {
			var my_display = document.getElementById(\'HiddenFCKexerciseDescription\').style.display;
				if(my_display== \'none\' || my_display == \'\') {
				document.getElementById(\'HiddenFCKexerciseDescription\').style.display = \'block\';
				document.getElementById(\'media_icon\').innerHTML=\'&nbsp;<img src="../img/looknfeelna.png" alt="" />&nbsp;'.get_lang('ExerciseDescription').'\';
			} else {
				document.getElementById(\'HiddenFCKexerciseDescription\').style.display = \'none\';
				document.getElementById(\'media_icon\').innerHTML=\'&nbsp;<img src="../img/looknfeel.png" alt="" />&nbsp;'.get_lang('ExerciseDescription').'\';

			}
		}

		function timelimit() {
			if(document.getElementById(\'options2\').style.display == \'none\') {
				document.getElementById(\'options2\').style.display = \'block\';
			} else {
				document.getElementById(\'options2\').style.display = \'none\';
			}
		}

		function feedbackselection()
		{
			var index = document.exercise_admin.exerciseFeedbackType.selectedIndex;

			if (index == \'1\') {
				document.exercise_admin.exerciseType[1].checked=true;
				document.exercise_admin.exerciseType[0].disabled=true;

			} else {
				document.exercise_admin.exerciseType[0].disabled=false;
			}
		}

		function option_time_expired()
	    {
		    if(document.getElementById(\'timercontrol\').style.display == \'none\')
		    {
		      document.getElementById(\'timercontrol\').style.display = \'block\';
		    } else {
		      document.getElementById(\'timercontrol\').style.display = \'none\';
		    }
	    }

     	function check_per_page_one()
     	{
     		if (document.getElementById(\'divtimecontrol\').style.display==\'none\')
     		{
     			document.getElementById(\'divtimecontrol\').style.display=\'block\';
     			document.getElementById(\'divtimecontrol\').display=block;
     			document.getElementById(\'timecontrol\').display=none;
     		}
		}

		function check_per_page_all()
     	{
			if (document.getElementById(\'divtimecontrol\').style.display==\'block\')
			{
				document.getElementById(\'divtimecontrol\').style.display=\'none\';
				document.getElementById(\'enabletimercontroltotalminutes\').value=\'\';
			}
		}
		</script>';

$htmlHeadXtra[] = '<script>
  $(document).ready(function (){
    $(".save").attr("style","float:right;margin-right:350px");
  });
</script>';

$htmlHeadXtra[] = '<script>
  $(document).ready(function (){
    $("div.label").attr("style","width: 100%;text-align:left;padding-bottom:2px;");
    $("div.row").attr("style","width: 100%;");
    $("div.formw").attr("style","width: 100%;");
  });
</script>';

$htmlHeadXtra[] = '<script>
  $(document).ready(function (){
	   $("#addButton_1").click(function () {		   
		   var counter = $("input[name=\'counter_1\']").attr("value");		   
		   var quizcategory = $("input[name=\'quizcategory_1\']").attr("value");
		   var quizcategory_id = $("input[name=\'quizcategory_id_1\']").attr("value");
	       var quiz_level = $("input[name=\'quiz_level_1\']").attr("value");
		   var quiz_level_id = $("input[name=\'quiz_level_id_1\']").attr("value");
		   var quizCategoryArray = quizcategory.split(",");	
	       var quizCategoryIdArray = quizcategory_id.split(",");
	       var quizlevelArray = quiz_level.split(",");
		   var quizlevelIdArray = quiz_level_id.split(",");
	       counter = (counter*1) + 1;		   
		   var newTextBoxDiv = $(document.createElement("div")).attr("id", "TextBoxDiv"+counter+"_1");		   
		   var str = "<br/><table width=\"100%\" border=\"0\" cellspacing=\"2\"><tr><td width=\"45%\">";
	       str = str + "<select name=\"quizcategory_"+counter+"_1\">";
	       var str1 = "";		   
		   if(quizCategoryArray.length == 1)
		   {
			   str1 = str1 + "<option value=\"0\">Select</option>";
		   }
		   for(var i=0;i<quizCategoryArray.length;i++)
		   {
			   str1 = str1 + "<option value="+quizCategoryIdArray[i]+">"+quizCategoryArray[i]+"</option>";
		   }
		   str = str + str1;
		   str = str + "</select></td><td width=\"25%\">";
		   str = str + "<select name=\"quizlevel_"+counter+"_1\">";
		   var str2 = "";		   
		   for(var i=0;i<quizlevelArray.length;i++)
		   {
			   str2 = str2 + "<option value="+quizlevelIdArray[i]+">"+quizlevelArray[i]+"</option>";
		   }
		   str = str + str2;
		   str = str + "</select></td><td width=\"30%\">";
		   str = str + "<select name=\"numberofquestion_"+counter+"_1\" size=\"1\">";
		   var str3 = "";
		   str3 = str3 + "<option value=\"0\">Select</option>";
		   for(var i=1;i<=10;i++)
		   {
			   str3 = str3 + "<option value="+i+">"+i+"</option>";
		   }
		   str = str + str3;
		   str = str + "</select></td></tr></table>";		   
		   newTextBoxDiv.html(str);
		   $("input[name=counter_1]").val(counter);	
		   newTextBoxDiv.appendTo("#TextBoxesGroup_1");
	   });

	   $("#addButton_2").click(function () {			   
		   var counter = $("input[name=\'counter_2\']").attr("value");		   
		   var quizcategory = $("input[name=\'quizcategory_2\']").attr("value");
		   var quizcategory_id = $("input[name=\'quizcategory_id_2\']").attr("value");
	       var quiz_level = $("input[name=\'quiz_level_2\']").attr("value");
		   var quiz_level_id = $("input[name=\'quiz_level_id_2\']").attr("value");
		   var quizCategoryArray = quizcategory.split(",");	
	       var quizCategoryIdArray = quizcategory_id.split(",");
	       var quizlevelArray = quiz_level.split(",");
		   var quizlevelIdArray = quiz_level_id.split(",");
	       counter = (counter*1) + 1;		   
		   var newTextBoxDiv = $(document.createElement("div")).attr("id", "TextBoxDiv"+counter+"_2");		   
		   var str = "<br/><table width=\"100%\" border=\"0\" cellspacing=\"2\"><tr><td width=\"45%\">";
	       str = str + "<select name=\"quizcategory_"+counter+"_2\">";
	       var str1 = "";		   
		   if(quizCategoryArray.length == 1)
		   {
			   str1 = str1 + "<option value=\"0\">Select</option>";
		   }
		   for(var i=0;i<quizCategoryArray.length;i++)
		   {
			   str1 = str1 + "<option value="+quizCategoryIdArray[i]+">"+quizCategoryArray[i]+"</option>";
		   }
		   str = str + str1;
		   str = str + "</select></td><td width=\"25%\">";
		   str = str + "<select name=\"quizlevel_"+counter+"_2\">";
		   var str2 = "";
		   for(var i=0;i<quizlevelArray.length;i++)
		   {
			   str2 = str2 + "<option value="+quizlevelIdArray[i]+">"+quizlevelArray[i]+"</option>";
		   }
		   str = str + str2;
		   str = str + "</select></td><td width=\"30%\">";
		   str = str + "<select name=\"numberofquestion_"+counter+"_2\" size=\"1\">";
		   var str3 = "";
		   str3 = str3 + "<option value=\"0\">Select</option>";
		   for(var i=1;i<=10;i++)
		   {
			   str3 = str3 + "<option value="+i+">"+i+"</option>";
		   }
		   str = str + str3;
		   str = str + "</select></td></tr></table>";		   
		   newTextBoxDiv.html(str);
		   $("input[name=counter_2]").val(counter);	
		   newTextBoxDiv.appendTo("#TextBoxesGroup_2");
	   });

	   $("#removeButton_1").click(function () {			   
		   var counter = $("input[name=\'counter_1\']").attr("value");		   
		   $("#TextBoxDiv" + counter+"_1").remove();
		   counter--;	
		   $("input[name=counter_1]").val(counter);	
	   });

	    $("#removeButton_2").click(function () {			   
		   var counter = $("input[name=\'counter_2\']").attr("value");		   
		   $("#TextBoxDiv" + counter+"_2").remove();
		   counter--;	
		   $("input[name=counter_2]").val(counter);	
	   });
  });
</script>';

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

// Add the extra lp_id parameter to some links
$add_params_for_lp = '';
if (isset($_GET['lp_id'])) {
  $add_params_for_lp = "&lp_id=".$learnpath_id;
}
/*********************
 * INIT EXERCISE
 *********************/

// Scenario 1
$objExercise = new Exercise(1);
/*if (isset($_REQUEST['scenario'])) {
	// Scenario 2
	$objExercise_s2 = new Exercise(2);
}*/

/*********************
 * INIT FORM
 *********************/
if(isset($_GET['exerciseId'])) {
 // Scenario 1
	$form = new FormValidator('exercise_admin1', 'post', api_get_self().'?exerciseId='.Security::remove_XSS($_GET['exerciseId']).'&'.api_get_cidreq(), null, array('style' => 'width: 100%; border: 0px'));
	$objExercise -> read (intval($_GET['exerciseId']));
	$form -> addElement ('hidden','edit','true');

 /*if (isset($_REQUEST['scenario'])) {
   // Scenario 2
	  $form_s2 = new FormValidator('exercise_admin2', 'post', api_get_self().'?exerciseId='.Security::remove_XSS($_GET['exerciseId']).'&'.api_get_cidreq(), null, array('style' => 'width: 100%; border: 0px'));
	  $objExercise_s2 -> read (intval($_GET['exerciseId']));
	  $form_s2 -> addElement ('hidden','edit','true');
 }*/

} else {
  $add_params_for_lp = '';
  if (isset($_GET['lp_id'])) {
    $add_params_for_lp = "&lp_id=".Security::remove_XSS($_GET['lp_id']);
  }
  // Scenario 1
  $form = new FormValidator('exercise_admin1', null,  api_get_self().'?'.  api_get_cidreq().$add_params_for_lp, null, array('style' => 'width: 100%; border: 0px'));
  $form -> addElement ('hidden','edit','false');

/* if (isset($_REQUEST['scenario'])) {
    // Add Scenario 2
    $form_s2 = new FormValidator('exercise_admin2', null, api_get_self().'?'.  api_get_cidreq().$add_params_for_lp, null, array('style' => 'width: 100%; border: 0px'));
    $form_s2 -> addElement ('hidden','edit','false');
  }*/
}

// Scenario 1
$objExercise -> createForm($form);
// Add Scenario 2
/*if (isset($_REQUEST['scenario'])) {
  $objExercise_s2 -> createForm($form_s2);
}*/


/*********************
 * VALIDATE FORM
 *********************/
// Validation for the scenario feature.
$no_validate = false;
if (isset($_REQUEST['scenario'])) {
/* if ($form_s2 -> validate()) {
    if ($form_s2 -> validate()) {
      $objExercise_s2 -> processCreation($form_s2);
    }

    if ($form_s2 -> getSubmitValue('edit') == 'true') {
      if(isset($_SESSION['fromlp'])) {
       header('Location:exercice.php?message=ExerciseEdited&'.api_get_cidreq().'&fromlp='.$_SESSION['fromlp']);
      } else {
       header('Location:exercice.php?message=ExerciseEdited&'.api_get_cidreq());
      }
    } else {
      $my_quiz_id = $objExercise_s2->id;
      if(isset($_SESSION['fromlp'])) {
       header('Location:admin.php?message=ExerciseAdded&exerciseId='.$my_quiz_id.'&fromlp='.$_SESSION['fromlp']);
      } else {
       header('Location:admin.php?message=ExerciseAdded&exerciseId='.$my_quiz_id);
      }
    }
  } else {
    $no_validate = true;
  }*/
}

if ($form -> validate()) {
  if ($form -> validate()) {
    $objExercise -> processCreation($form);
  }

  if ($form -> getSubmitValue('edit') == 'true') {
    if(isset($_SESSION['fromlp'])) {
      header('Location:exercice.php?message=ExerciseEdited&'.api_get_cidreq().'&fromlp='.$_SESSION['fromlp']);
    } else {
      header('Location:exercice.php?message=ExerciseEdited&'.api_get_cidreq());
    }
   } else {
     $my_quiz_id = $objExercise->id;
     /*if(isset($_SESSION['fromlp'])) {
        header('Location:admin.php?message=ExerciseAdded&exerciseId='.$my_quiz_id.'&fromlp='.$_SESSION['fromlp']);
      } else {*/
        header('Location:admin.php?'.api_get_cidreq().'&message=ExerciseAdded&exerciseId='.$my_quiz_id.$add_params_for_lp);
      //}
    }
 } else {
  $no_validate = true;
 }


 if ($no_validate === true) {
	/*********************
	 * DISPLAY FORM
	 *********************/
	if (isset($_SESSION['gradebook'])) {
		$gradebook=	$_SESSION['gradebook'];
	}

	if (!empty($gradebook) && $gradebook=='view') {
		$interbreadcrumb[]= array (
				'url' => '../gradebook/'.$_SESSION['gradebook_dest'],
				'name' => get_lang('Gradebook')
			);
	}
	
	$nameTools=get_lang('ExerciseManagement');
	$interbreadcrumb[] = array ("url"=>"exercice.php", "name"=> get_lang('Exercices'));
	
//	Display::display_header($nameTools,'Exercise');
	Display :: display_tool_header();
	
	if ($objExercise->feedbacktype==1 || $objExercise_s2->feedbacktype==1) {
		Display::display_normal_message(get_lang("DirectFeedbackCantModifyTypeQuestion"));
	}
	
	if(api_get_setting('search_enabled')=='true' && !extension_loaded('xapian')) {
		echo '<div class="confirmation-message">'.get_lang('SearchXapianModuleNotInstaled').'</div>';
	}
	?>
	
	<div class="actions">
  <?php
   if (isset($_GET['lp_id']) && $_GET['lp_id'] > 0) {
     
    //$lp_id = Security::remove_XSS($_GET['lp_id']);
    // The lp_id parameter will be added by javascript
     $return = "";
     $return.= '<a href="../newscorm/lp_controller.php?' . api_get_cidreq() . '">' . Display::return_icon('pixel.gif', get_lang("Author"), array('class' => 'toolactionplaceholdericon toolactionauthor')).get_lang("Author") . '</a>';
     $return.= '<a href="../newscorm/lp_controller.php?' . api_get_cidreq() . '&action=add_item&type=step">' . Display::return_icon('pixel.gif', get_lang("Content"), array('class' => 'toolactionplaceholdericon toolactionauthorcontent')).get_lang("Content") . '</a>';
	 $return.= '<a href="../newscorm/lp_controller.php?' . api_get_cidreq() . '&gradebook=&action=view&lp_id='.$_GET['lp_id'].'">' . Display::return_icon('pixel.gif', get_lang("ViewRight"), array('class' => 'toolactionplaceholdericon toolactionauthorpreview')).get_lang("ViewRight") . '</a>';
     echo $return;
   }
   if (!isset($_GET['lp_id'])) {
  ?>
		<a href="exercice.php?<?php echo api_get_cidreq()?>"><?php echo Display::return_icon('pixel.gif', get_lang('List'), array('class' => 'toolactionplaceholdericon toolactionback')) . get_lang('List')?></a>
  <?php
   }
  ?>		
  <?php if (!isset($_GET['lp_id'])) {?>
		<a href="exercise_admin.php?<?php echo api_get_cidreq()?>"><?php echo Display::return_icon('pixel.gif', get_lang('NewEx'), array('class' => 'toolactionplaceholdericon toolactionnewquiz')) . get_lang('NewEx')?></a>	
<!--	<a href="upload_exercise.php?<?php echo api_get_cidreq()?>"><?php echo Display::return_icon('pixel.gif', get_lang('UploadQuiz'), array('class' => 'toolactionplaceholdericon toolactionexportcourse')) . get_lang('UploadQuiz')?></a>-->
  <?php } ?>
  <?php
   if (isset($_GET['exerciseId']) && $_GET['exerciseId'] > 0) {
  ?>
  <a href="admin.php?<?php echo  api_get_cidreq() . '&exerciseId='.Security::remove_XSS($_GET['exerciseId']); ?>"><?php echo Display::return_icon('pixel.gif', get_lang('Questions'), array('class' => 'toolactionplaceholdericon toolactionquestion')) . get_lang('Questions'); ?></a>
  <a href="exercise_admin.php?<?php echo 'scenario=yes&modifyExercise=yes&' . api_get_cidreq() . '&exerciseId='.Security::remove_XSS($_GET['exerciseId']); ?>"><?php echo Display::return_icon('pixel.gif', get_lang('Scenario'), array('class' => 'toolactionplaceholdericon toolactionscenario')). get_lang('Scenario'); ?></a>
  <a href="exercice_submit.php?<?php echo api_get_cidreq() . '&exerciseId='.Security::remove_XSS($_GET['exerciseId']); ?>"><?php echo Display::return_icon('pixel.gif', get_lang('ViewRight'), array('class' => 'toolactionplaceholdericon toolactionsearch')) . get_lang('ViewRight'); ?></a> 
		<?php }
  ?>
	</div>

<?php
	// start the content div
	echo '<div id="content_with_secondary_actions">';

  if (isset($_GET['modifyExercise'])) {
   $widht = '46%';
 //  $height = '550px';
   $height = '600px';
   $widht_right = $widht;
   $container_class = "";
   $sub_container_class = "quiz_scenario_squarebox";
  } else {
   $container_class = "";
   $sub_container_class = "";
/*   $widht = '500px';
   $widht_right = '';*/
   $widht = '370px';
   $widht_right = '400px';
   $height = '320px';
  }
?>
<!--<div id ="exercise_admin_container" class="<?php echo $container_class; ?>" style="height: <?php echo $height; ?>">-->
<div id ="exercise_admin_container" class="<?php echo $container_class; ?>">
<table cellpadding="5" width="100%"><tr><td width="100%" valign="top">
<div id="exercise_admin_left_container" class="<?php echo $sub_container_class; ?>" >
		<?php $form -> display (); ?>
	</div></td>
<!--<td width="50%">
	<div id="exercise_admin_right_container" class="<?php echo $sub_container_class; ?>">
		<?php
   /*if (!isset($_GET['scenario'])) {
     Display::display_icon('instructor-faq.png', get_lang('Teacher'));
   } elseif (isset($_GET['scenario']) && isset($_GET['modifyExercise'])) {
     $form_s2 -> display ();
   }*/
  ?>
	</div></td>-->
	</tr></table></div>

	<?php
	// close the content div
	echo '</div>';
	?>

	<div class="actions">
 <?php
	if(api_get_setting('show_quizcategory') == 'true'){
	echo '<a href="exercise_category.php?<?php echo api_get_cidreq()?>&action=add_category">'.Display :: return_icon("category_22.png", get_lang("Categories")) . get_lang("Categories").'</a>';
	}
   if (isset($_GET['lp_id']) && $_GET['lp_id'] > 0) {
     $return = '';
     // The lp_id parameter will be added by Javascript
//     $return.= '<a href="../newscorm/lp_controller.php?' . api_get_cidreq() . '&action=build">' . Display::return_icon('build.png', get_lang('Build')).get_lang("Build") . '</a>';
 //    $return.= '<a href="../newscorm/lp_controller.php?' . api_get_cidreq() . '&gradebook=&action=admin_view">' . Display::return_icon('organize.png', get_lang('Organize')).get_lang("Organize") . '</a>';
     //$return.= '<a href="../newscorm/lp_controller.php?' . api_get_cidreq() . '&gradebook=&action=view">' . Display::return_icon('view.png', get_lang('ViewRight')).get_lang("ViewRight") . '</a>';
     echo $return;
   } else {
  ?>
	<!--	<a href="<?php echo api_add_url_param($_SERVER['REQUEST_URI'], 'show=result')?>"><?php echo Display :: return_icon('reporting22.png', get_lang('Tracking')) . get_lang('Tracking')?></a>-->
	<?php
  }
  ?>
  <?php if (!isset($_GET['lp_id'])) {?>		
		<a href="upload_exercise.php?<?php echo api_get_cidreq()?>"><?php echo Display::return_icon('pixel.gif', get_lang('UploadQuiz'), array('class' => 'actionplaceholdericon actionuploadquiz')) . get_lang('UploadQuiz')?></a>
  <?php } ?>
	</div>
	<div style="clear:both"></div>
 <?php
}

// display footer
Display::display_footer();
?>