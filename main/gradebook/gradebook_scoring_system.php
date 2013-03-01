<?php

/* For licensing terms, see /dokeos_license.txt */

// name of the language file that needs to be included
$language_file = 'gradebook';

// including the global dokeos file
require_once ('../inc/global.inc.php');

// including additional libraries
require_once ('lib/be.inc.php');
require_once ('lib/gradebook_functions.inc.php');
require_once ('lib/fe/scoredisplayform.class.php');
require_once ('lib/scoredisplay.class.php');

// access restriction
api_block_anonymous_users();
//api_protect_admin_script();

$htmlHeadXtra[]= '
  <script language="JavaScript">
  function plusItem(item)
  {
		document.getElementById(item).style.display = "inline";
		document.getElementById("plus-"+item).style.display = "none";
   	 	document.getElementById("min-"+(item-1)).style.display = "none";
   	 	document.getElementById("min-"+(item)).style.display = "inline";
   	 	document.getElementById("plus-"+(item+1)).style.display = "inline";
	 	document.getElementById("txta-"+(item)).value = "100";
	 	document.getElementById("txta-"+(item-1)).value = "";
  }

  function minItem(item)
   {
    if (item != 1)
	{
     document.getElementById(item).style.display = "none";
	 document.getElementById("txta-"+item).value = "";
	 document.getElementById("txtb-"+item).value = "";
     document.getElementById("plus-"+item).style.display = "inline";
     document.getElementById("min-"+(item-1)).style.display = "inline";
	 document.getElementById("txta-"+(item-1)).value = "100";

	}
	if (item = 1)
	{
		document.getElementById("min-"+(item)).style.display = "none";
	}
  }
 </script>';

$interbreadcrumb[]= array (
	'url' => $_SESSION['gradebook_dest'],
	'name' => get_lang('Gradebook'
));
$displayscore= ScoreDisplay :: instance();
$customdisplays = $displayscore->get_custom_score_display_settings();
$nr_items =(count($customdisplays)!='0')?count($customdisplays):'1';

$scoreform= new ScoreDisplayForm('scoring_system_form',
							 api_get_self() . '?selectcat=' . $_GET['selectcat']
							 );
if ($scoreform->validate()) {
	$value_export='';
	$value_export=$scoreform->exportValues();
	$value_export=isset($value_export) ? $scoreform->exportValues(): '';
	$values= $value_export;

// create new array of custom display settings
// this loop also checks if all score ranges are unique

	$scoringdisplay= array ();
	$ranges_ok = true;
	$endscore= isset($values['endscore']) ? $values['endscore'] : null;
	$displaytext=isset($values['displaytext']) ? $values['displaytext'] : null;
	for ($counter= 1; $ranges_ok && $counter <= 20; $counter++) {
			$setting= array ();
			$setting['score']= $endscore[$counter];
			$setting['display']= $displaytext[$counter];
			if (!empty($setting['score'])) {
				foreach ($scoringdisplay as $passed_entry) {
					if ($passed_entry['score'] == $setting['score']) {
						$ranges_ok = false;
					}
				}
			$scoringdisplay[]= $setting;
			}
		}

	if (!$ranges_ok) {
		header('Location: ' . api_get_self() . '?nouniqueranges=&selectcat=' . Security::remove_XSS($_GET['selectcat']));
		exit;
	}


	// update color settings
	$val_enablescorecolor=isset($values['enablescorecolor']) ? $values['enablescorecolor'] : null;
	$displayscore->set_coloring_enabled(($val_enablescorecolor == '1') ? true : false);
	if ($displayscore->is_coloring_enabled()) {
		$displayscore->set_color_split_value($values['scorecolpercent']);
	}
	// update custom display settings
	$val_enablescore=isset($values['enablescore']) ? $values['enablescore'] : null;
	$val_includeupperlimit=isset($values['includeupperlimit']) ? $values['includeupperlimit'] : null;

	$displayscore->set_custom(($val_enablescore == '1') ? true : false);
	$displayscore->set_upperlimit_included(($val_includeupperlimit == '1') ? true : false);
	if ($displayscore->is_custom() && !empty($scoringdisplay)) {
		$displayscore->update_custom_score_display_settings($scoringdisplay);
	}
	header('Location: ' . api_get_self() . '?scoringupdated=&selectcat=' . Security::remove_XSS($_GET['selectcat']));
	exit;
}

Display::display_tool_header(get_lang('ScoreEdit'));

if (((isset($_GET['isStudentView']) && $_GET['isStudentView']=='false') || (isset($_GET['selectcat']) && ($_SESSION['studentview']=='teacherview')))) {
	if (isset ($_GET['scoringupdated'])) {
		Display :: display_confirmation_message(get_lang('ScoringUpdated'),false);
	}

if (isset ($_GET['nouniqueranges'])) {
	Display :: display_error_message(get_lang('NoUniqueScoreRanges'),false);
}
// Actions bar
echo '<div class="actions">';
echo '<a href="'.$_SESSION['gradebook_dest'].'?selectcat='.Security::remove_XSS($_GET['selectcat']).'&amp;'.api_get_cidreq().'">'.Display::return_icon('pixel.gif',get_lang('Gradebook'), array('class' => 'toolactionplaceholdericon toolactionback')).' '.get_lang('Gradebook').'</a>';
echo '</div>';

echo '<div id="content">';
$scoreform->display();
echo '</div>';
}
// Actions bar
echo '<div class="actions">';
echo '</div>';
Display :: display_footer();
