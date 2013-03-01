<?php
/* For licensing terms, see /dokeos_license.txt */
/**
==============================================================================
 * The INTRODUCTION MICRO MODULE is used to insert and edit
 * an introduction section on a Dokeos Module. It can be inserted on any
 * Dokeos Module, provided a connection to a course Database is already active.
 *
 * The introduction content are stored on a table called "introduction"
 * in the course Database. Each module introduction has an Id stored on
 * the table. It is this id that can make correspondance to a specific module.
 *
 * 'introduction' table description
 *   id : int
 *   intro_text :text
 *
 *
 * usage :
 *
 * $moduleId = XX // specifying the module Id
 * include(moduleIntro.inc.php);
*
*	@package dokeos.include
==============================================================================
*/

include_once(api_get_path(LIBRARY_PATH).'formvalidator/FormValidator.class.php');

/*
-----------------------------------------------------------
	Constants and variables
-----------------------------------------------------------
*/
$TBL_INTRODUCTION = Database::get_course_table(TABLE_TOOL_INTRO);
$intro_editAllowed = $is_allowed_to_edit;

global $charset,$course_home_visibility_type;
$intro_cmdEdit = (empty($_GET['intro_cmdEdit'])?'':$_GET['intro_cmdEdit']);
$intro_cmdUpdate = isset($_POST['intro_cmdUpdate'])?true:false;
$intro_cmdDel= (empty($_GET['intro_cmdDel'])?'':$_GET['intro_cmdDel']);
$intro_cmdAdd= (empty($_GET['intro_cmdAdd'])?'':$_GET['intro_cmdAdd']);

if (!empty ($GLOBALS["_cid"])) {
	$form = new FormValidator('introduction_text', 'post', api_get_self()."?".api_get_cidreq()."&course_scenario=1");
} else {
	$form = new FormValidator('introduction_text','post', api_get_self()."?course_scenario=1");
}
$renderer =& $form->defaultRenderer();

$toolbar_set = 'Introduction';
$width = '100%';
$height = '200';

$editor_config = array('ToolbarSet' => $toolbar_set, 'Width' => $width, 'Height' => $height);
if (isset($_GET['display_template']) && $_GET['display_template'] == 1 && $tool == TOOL_COURSE_HOMEPAGE) {
  // 5 buttons for display the scenarios in the course home page
  if ($course_home_visibility_type === true) { // Tablet design - this has another HTML structure
  $html_buttons = '<div align="center ">
        <table class="gallery sectiontablet"><tbody>
        <tr><td><a href="'.  api_get_self().'?scenario=activity">
        <div class="width_tablet_scenario_button height_tablet_scenario_button border_tablet_button" >
			<div>'.Display::return_icon('pixel.gif',get_lang('Activities'), array('class' => 'toolscenarioactionplaceholdericon toolscenarioactionactivity')).'</div>
                        <div class="tablet_scenario_title">'.get_lang('Activities').'</div>
		</div></a></td>
        <td><a href="'.  api_get_self().'?scenario=social">
          <div class="width_tablet_scenario_button height_tablet_scenario_button border_tablet_button">
			<div class="">'.Display::return_icon('pixel.gif',get_lang('Social'), array('class' => 'toolscenarioactionplaceholdericon toolscenarioactionsocial')).'</div>
                        <div class="tablet_scenario_title">'.get_lang('Social').'</div>
		</div></a></td>
        <td><a href="'.  api_get_self().'?scenario=week">
          <div class="width_tablet_scenario_button height_tablet_scenario_button border_tablet_button" >
			<div class="">'.Display::return_icon('pixel.gif',get_lang('Weeks'), array('class' => 'toolscenarioactionplaceholdericon toolscenarioactionweek')).'</div>
                        <div class="tablet_scenario_title">'.get_lang('Weeks').'</div>
		</div></a></td>
        <td><a href="'.  api_get_self().'?scenario=corporate">
          <div class="width_tablet_scenario_button height_tablet_scenario_button border_tablet_button" >
			<div class="">'.Display::return_icon('pixel.gif',get_lang('Corporate'), array('class' => 'toolscenarioactionplaceholdericon toolscenarioactioncorporate')).'</div>
                        <div class="tablet_scenario_title">'.get_lang('Corporate').'</div>
		</div></a></td>
        <td><a href="'.  api_get_self().'?scenario=none">
          <div class="width_tablet_scenario_button height_tablet_scenario_button" >
			<div class="">'.Display::return_icon('pixel.gif',get_lang('NoScenario'), array('class' => 'toolscenarioactionplaceholdericon toolscenarioactionscenario')).'</div>
                        <div class="tablet_scenario_title">'.get_lang('NoScenario').'</div>
		</div></a></td>
        </tr></tbody>
        </table></div>';
  } else {
  $html_buttons = '<div align="center">
        <table class="gallery"><tbody>
        <tr><td><a href="'.  api_get_self().'?scenario=activity">
        <div class="section_scenario width_scenario_button" >
        <div class="sectiontitle">'.get_lang('Activities').'</div>
			<div class="">'.Display::return_icon('quiz_64.png',get_lang('Activities')).'</div>
		</div></a></td>
        <td><a href="'.  api_get_self().'?scenario=social">
          <div class="section_scenario width_scenario_button">
			<div class="sectiontitle">'.get_lang('Social').'</div>
			<div class="">'.Display::return_icon('social64.png',get_lang('Social')).'</div>
		</div></a></td>
        <td><a href="'.  api_get_self().'?scenario=week">
          <div class="section_scenario width_scenario_button" >
			<div class="sectiontitle">'.get_lang('Weeks').'</div>
			<div class="">'.Display::return_icon('weeks64.png',get_lang('Weeks')).'</div>
		</div></a></td>
        <td><a href="'.  api_get_self().'?scenario=corporate">
          <div class="section_scenario width_scenario_button" >
			<div class="sectiontitle">'.get_lang('Corporate').'</div>
			<div class="">'.Display::return_icon('corp64.png',get_lang('Corporate')).'</div>
		</div></a></td>
        <td><a href="'.  api_get_self().'?scenario=none">
          <div class="section_scenario width_scenario_button" >
			<div class="sectiontitle">'.get_lang('NoScenario').'</div>
			<div class="">'.Display::return_icon('noscenario64.png',get_lang('NoScenario')).'</div>
		</div></a></td>
        </tr></tbody>
        </table></div>';
  }
  $form->addElement('html',$html_buttons);
} else {
  $form->add_html_editor('intro_content', null, null, false, $editor_config);
  $form->addElement('style_submit_button', 'intro_cmdUpdate', get_lang('SaveIntroText'), 'class="save"');
}



/*=========================================================
  INTRODUCTION MICRO MODULE - COMMANDS SECTION (IF ALLOWED)
  ========================================================*/

if ($intro_editAllowed) {
	/* Replace command */

	if ( $intro_cmdUpdate ) {
		if ( $form->validate()) {

			$form_values = $form->exportValues();
			$intro_content = Security::remove_XSS(stripslashes(api_html_entity_decode($form_values['intro_content'])), COURSEMANAGERLOWSECURITY);
            if (empty($intro_content) ) {
              $intro_content = "&nbsp;";
            }
			if (! empty($intro_content) ) {
				$sql = "REPLACE $TBL_INTRODUCTION SET id='$moduleId',intro_text='".Database::escape_string($intro_content)."'";
				Database::query($sql,__FILE__,__LINE__);
				Display::display_confirmation_message(get_lang('IntroductionTextUpdated'),false);
			} else {
				$intro_cmdDel = true;	// got to the delete command
			}

		} else {
			$intro_cmdEdit = true;
		}
	}

	/* Delete Command */

	if ($intro_cmdDel) {
		Database::query("DELETE FROM $TBL_INTRODUCTION WHERE id='".$moduleId."'",__FILE__,__LINE__);
		Display::display_confirmation_message(get_lang('IntroductionTextDeleted'));
	}

}


/*===========================================
  INTRODUCTION MICRO MODULE - DISPLAY SECTION
  ===========================================*/

/* Retrieves the module introduction text, if exist */

$sql = "SELECT intro_text FROM $TBL_INTRODUCTION WHERE id='".$moduleId."'";
$intro_dbQuery = Database::query($sql,__FILE__,__LINE__);
$intro_dbResult = Database::fetch_array($intro_dbQuery);
if ($intro_cmdUpdate && empty($intro_content)) {
$intro_content = "&nbsp;";
} else {
$intro_content = $intro_dbResult['intro_text'];
}


/* Determines the correct display */

if ($intro_cmdEdit || $intro_cmdAdd) {
	$intro_dispDefault = false;
	$intro_dispForm = true;
	$intro_dispCommand = false;
} else {

	$intro_dispDefault = true;
	$intro_dispForm = false;

	if ($intro_editAllowed) {
		$intro_dispCommand = true;
	} else {
		$intro_dispCommand = false;
	}

}

/* Executes the display */

if ($intro_dispForm || isset($_GET['scenario'])) {
    if (isset($_GET['scenario']) && $_GET['scenario'] != 'none' && $tool == TOOL_COURSE_HOMEPAGE) {
          $image_arrow = Display::return_icon('media_playback_start_32.png', get_lang('Activity'),array('border' => '0', 'align' => 'absmiddle', 'vspace'=> '0','hspace'=> '0'));
        if ($_GET['scenario'] == 'activity') {
          $lang_var1 = get_lang('ActivityOne');
          $lang_var2 = get_lang('ActivityTwo');
          $lang_var3 = get_lang('ActivityThree');
          $lang_var4 = get_lang('ActivityFour');
          $lang_var5 = get_lang('ActivityFive');

          $image1 = Display::return_icon('quiz_64.png', $lang_var1,array('border' => '0', 'align' => 'absmiddle', 'vspace'=> '0','hspace'=> '0'));
          $image2 = Display::return_icon('applications_accessories_64.png', $lang_var2,array('border' => '0', 'align' => 'absmiddle', 'vspace'=> '0','hspace'=> '0'));
          $image3 = Display::return_icon('mouse_64.png', $lang_var3,array('border' => '0', 'align' => 'absmiddle', 'vspace'=> '0','hspace'=> '0'));
          $image4 = Display::return_icon('accessories-character-map.png', $lang_var4,array('border' => '0', 'align' => 'absmiddle', 'vspace'=> '0','hspace'=> '0'));
          $image5 = Display::return_icon('miscellaneous.png', $lang_var5,array('border' => '0', 'align' => 'absmiddle', 'vspace'=> '0','hspace'=> '0'));

        } elseif ($_GET['scenario'] == 'corporate') {
          $lang_var1 = get_lang('Corporate');
          $image1 = Display::return_icon('trainerleft.png', $lang_var1,array('border' => '0', 'align' => 'absmiddle', 'vspace'=> '0','hspace'=> '0'));
          $image2 = Display::return_icon('textright.png', $lang_var1,array('border' => '0', 'align' => 'absmiddle', 'vspace'=> '0','hspace'=> '0'));

        } elseif ($_GET['scenario'] == 'social') {
          $lang_var1 = get_lang('InteractionOne');
          $lang_var2 = get_lang('InteractionTwo');
          $lang_var3 = get_lang('InteractionThree');
          $lang_var4 = get_lang('InteractionFour');
          $lang_var5 = get_lang('InteractionFive');

          $image1 = Display::return_icon('group_blue.png', $lang_var1,array('border' => '0', 'align' => 'absmiddle', 'vspace'=> '0','hspace'=> '0'));
          $image2 = Display::return_icon('group_orange.png', $lang_var2,array('border' => '0', 'align' => 'absmiddle', 'vspace'=> '0','hspace'=> '0'));
          $image3 = Display::return_icon('presence_64.png', $lang_var3,array('border' => '0', 'align' => 'absmiddle', 'vspace'=> '0','hspace'=> '0'));
          $image4 = Display::return_icon('group_red.png', $lang_var4,array('border' => '0', 'align' => 'absmiddle', 'vspace'=> '0','hspace'=> '0'));
          $image5 = Display::return_icon('group_grey.png', $lang_var5,array('border' => '0', 'align' => 'absmiddle', 'vspace'=> '0','hspace'=> '0'));

        } elseif ($_GET['scenario'] == 'week') {
          $lang_var1 = get_lang('WeekOne');
          $lang_var2 = get_lang('WeekTwo');
          $lang_var3 = get_lang('WeekThree');
          $lang_var4 = get_lang('WeekFour');
          $lang_var5 = get_lang('WeekFive');

          $image1 = Display::return_icon('presence_64.png', $lang_var1,array('border' => '0', 'align' => 'absmiddle', 'vspace'=> '0','hspace'=> '0'));
          $image2 = Display::return_icon('media_podcasts.png', $lang_var2,array('border' => '0', 'align' => 'absmiddle', 'vspace'=> '0','hspace'=> '0'));
          $image3 = Display::return_icon('link_64.png', $lang_var3,array('border' => '0', 'align' => 'absmiddle', 'vspace'=> '0','hspace'=> '0'));
          $image4 = Display::return_icon('newpage.png', $lang_var4,array('border' => '0', 'align' => 'absmiddle', 'vspace'=> '0','hspace'=> '0'));
          $image5 = Display::return_icon('01time.png', $lang_var5,array('border' => '0', 'align' => 'absmiddle', 'vspace'=> '0','hspace'=> '0'));

        }
        if ($_GET['scenario'] != 'corporate') {
         $intro_content = '<div align="center"><table cellspacing="2" cellpadding="10" border="0" align="center" style="width: 800px; height: 130px;"><tbody>
              <tr>
                  <td style="text-align: center;">'.$image1.'</td>
                  <td style="text-align: center;">'.$image_arrow.'</td>
                  <td style="text-align: center;">'.$image2.'</td>
                  <td style="text-align: center;">'.$image_arrow.'</td>
                  <td style="text-align: center;">'.$image3.'</td>
                  <td style="text-align: center;">'.$image_arrow.'</td>
                  <td style="text-align: center;">'.$image4.'</td>
                  <td style="text-align: center;">'.$image_arrow.'</td>
                  <td style="text-align: center;">'.$image5.'</td>
              </tr>
              <tr>
                  <td style="text-align: center;">&nbsp;'.$lang_var1.'</td>
                  <td style="text-align: center;">&nbsp;</td>
                  <td style="text-align: center;">&nbsp;'.$lang_var2.'</td>
                  <td style="text-align: center;">&nbsp;</td>
                  <td style="text-align: center;">&nbsp;'.$lang_var3.'</td>
                  <td style="text-align: center;">&nbsp;</td>
                  <td style="text-align: center;">&nbsp;'.$lang_var4.'</td>
                  <td style="text-align: center;">&nbsp;</td>
                  <td style="text-align: center;">&nbsp;'.$lang_var5.'</td>
              </tr>
          </tbody>
        </table>
        </div>';
        } else {
          $intro_content = '<div align="center">
          <table width="420" cellspacing="0" cellpadding="0" border="0" align="center">
          <tbody>
              <tr>
                  <td width="356">'.$image1.'</td>
                  <td width="58">'.$image2.'</td>
              </tr>
          </tbody>
        </table>
      </div>';
        }
    } elseif (isset($_GET['scenario']) && $_GET['scenario'] == 'none' && $tool == TOOL_COURSE_HOMEPAGE) {
      $intro_content = "&nbsp;";
    }

    $default['intro_content'] = $intro_content;
	$form->setDefaults($default);

    // Actions bar for display the scenario icons
    if (((isset($_GET['display_template']) && $_GET['display_template'] == 0) || (isset($_GET['scenario']))) && $tool == TOOL_COURSE_HOMEPAGE) {
        $get_intro_cmdEdit = Security::remove_XSS($_GET['intro_cmdEdit']);
        //Display::return_icon('pixel.gif',get_lang('Weeks'), array('class' => 'toolscenarioactionplaceholdericon toolscenarioactionweek')
        echo '<div class="actions">';
        echo '<a href="'. api_get_self().'?intro_cmdEdit='.$get_intro_cmdEdit.'&amp;display_template=0&amp;scenario=activity">'.Display::return_icon('pixel.gif',get_lang('Activities'), array('class' => 'toolactionplaceholdericon toolactionsactivity')).get_lang('Activities').'</a>';
        echo '<a href="'. api_get_self().'?intro_cmdEdit='.$get_intro_cmdEdit.'&amp;display_template=0&amp;scenario=social">'.Display::return_icon('pixel.gif',get_lang('Social'), array('class' => 'toolactionplaceholdericon toolactionssocial')).get_lang('Social').'</a>';
        echo '<a href="'. api_get_self().'?intro_cmdEdit='.$get_intro_cmdEdit.'&amp;display_template=0&amp;scenario=week">'.Display::return_icon('pixel.gif',get_lang('Weeks'), array('class' => 'toolactionplaceholdericon toolactionsweek')).get_lang('Weeks').'</a>';
        echo '<a href="'. api_get_self().'?intro_cmdEdit='.$get_intro_cmdEdit.'&amp;display_template=0&amp;scenario=corporate">'.Display::return_icon('pixel.gif',get_lang('Corporate'), array('class' => 'toolactionplaceholdericon toolactionscorporate')).get_lang('Corporate').'</a>';
        echo '<a href="'. api_get_self().'?intro_cmdEdit='.$get_intro_cmdEdit.'&amp;display_template=0&amp;scenario=none">'.Display::return_icon('pixel.gif',get_lang('NoScenario'), array('class' => 'toolactionplaceholdericon toolactionsscenario')).get_lang('NoScenario').'</a>';
        echo '</div>';
    }
    // Display course intro
	echo '<div id="courseintro">';
	$form->display();
	echo '</div>';
}

if ($intro_dispDefault && !isset($_GET['scenario'])) {
	//$intro_content = make_clickable($intro_content); // make url in text clickable
	$intro_content = text_filter($intro_content); // parse [tex] codes
	if (!empty($intro_content))	{
		echo '<div id="courseintroduction"><div class="scroll_feedback">';
		echo $intro_content;
		echo '</div></div>';
	}
}

if ($intro_dispCommand  && !isset($_GET['scenario'])) {

	if ( empty($intro_content) ) {

		//displays "Add intro" Commands
		echo "<div id=\"courseintro\">\n";
		if (!empty ($GLOBALS["_cid"])) {
          // Add param for display the templates(4 buttons)
          $add_param_display_template = "";
          if ($tool == TOOL_COURSE_HOMEPAGE) {
            $add_param_display_template = "&amp;display_template=1";
          }
          echo "<a href=\"".api_get_self()."?".api_get_cidreq()."&amp;intro_cmdAdd=1&amp;course_scenario=1".$add_param_display_template."\">\n".get_lang('AddIntro')."</a>\n";
		} else {
			echo "<a href=\"".api_get_self()."?intro_cmdAdd=1&amp;course_scenario=1\">\n".get_lang('AddIntro')."</a>\n";
		}
		echo "\n</div>";

	} else {
        $content_without_space = str_replace('&nbsp;','',$intro_content);
        $add_param_display_template = "";
        if ($tool == TOOL_COURSE_HOMEPAGE) {
           $add_param_display_template = '&amp;display_template=0';
           if (strlen($content_without_space) == 0) {
              $add_param_display_template = '&amp;display_template=1';
           }
        }

		// displays "edit intro && delete intro" Commands
		echo "<div id=\"courseintro_icons\">\n";
		if (!empty ($GLOBALS["_cid"])) {
			echo "<a href=\"".api_get_self()."?".api_get_cidreq()."&amp;intro_cmdDel=1&amp;course_scenario=1\" onclick=\"javascript:if(!confirm('".addslashes(api_htmlentities(get_lang('ConfirmYourChoice'),ENT_QUOTES,$charset))."')) return false;\">".Display::return_icon('pixel.gif', get_lang('Delete'), array('class' => 'actionplaceholdericon actiondelete')).'</a>' . PHP_EOL;
			echo "<a href=\"".api_get_self()."?".api_get_cidreq()."&amp;intro_cmdEdit=1&amp;course_scenario=1".$add_param_display_template."\">".Display::return_icon('pixel.gif', get_lang('Modify'), array('class' => 'actionplaceholdericon actionedit')).'</a>' . PHP_EOL;
		} else {
			echo "<a href=\"".api_get_self()."?intro_cmdDel=1&amp;course_scenario=1\" onclick=\"javascript:if(!confirm('".addslashes(api_htmlentities(get_lang('ConfirmYourChoice'),ENT_QUOTES,$charset))."')) return false;\">" . Display::return_icon('pixel.gif', get_lang('Delete'), array('class' => 'actionplaceholdericon actiondelete')) . '</a>' . PHP_EOL;
			echo "<a href=\"".api_get_self()."?intro_cmdEdit=1&amp;course_scenario=1\">".Display::return_icon('pixel.gif', get_lang('Edit'), array('class' => 'actionplaceholdericon actionedit')).'</a>' . PHP_EOL;
		}
		echo "</div>";

	}

}
?>
