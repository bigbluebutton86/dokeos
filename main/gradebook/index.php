<?php // $Id: $
/*
==============================================================================
	Dokeos - elearning and course management software

    Copyright (c) 2008 Dokeos Latinoamerica SAC
	Copyright (c) 2006-2008 Dokeos SPRL
	Copyright (c) 2006 Ghent University (UGent)
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
$language_file= 'gradebook';
// $cidReset : This is the main difference with gradebook.php, here we say,
// basically, that we are inside a course, and many things depend from that
$cidReset= false;
$_in_course = true;
require_once '../inc/global.inc.php';
$course_code = api_get_course_id();
//make sure the destination for scripts is index.php instead of gradebook.php
$_SESSION['gradebook_dest'] = 'index.php';
$this_section = SECTION_MYGRADEBOOK;
require_once 'lib/be.inc.php';
require_once 'lib/scoredisplay.class.php';
require_once 'lib/gradebook_functions.inc.php';
require_once 'lib/fe/catform.class.php';
require_once 'lib/fe/evalform.class.php';
require_once 'lib/fe/linkform.class.php';
require_once 'lib/gradebook_data_generator.class.php';
require_once 'lib/fe/gradebooktable.class.php';
require_once 'lib/fe/displaygradebook.php';
require_once 'lib/fe/userform.class.php';
require_once api_get_path(LIBRARY_PATH).'ezpdf/class.ezpdf.php';
api_block_anonymous_users();
$htmlHeadXtra[]= '<script type="text/javascript">
function confirmation () {
	if (confirm("' . get_lang('DeleteAll') . '?"))
		{return true;}
	else
		{return false;}
}
</script>';

$tbl_forum_thread = Database :: get_course_table(TABLE_FORUM_THREAD);
$tbl_grade_links = Database :: get_main_table(TABLE_MAIN_GRADEBOOK_LINK);
$status=CourseManager::get_user_in_course_status(api_get_user_id(), api_get_course_id());
$filter_confirm_msg = true;
$filter_warning_msg = true;

///direct access to one evaluation
$cats = Category :: load(null, null, $course_code, null, null, $session_id, false); //already init
if (empty($cats))
{
	$cats = Category :: load(0, null, $course_code, null, null, $session_id, false);//first time
	$first_time=1;
}
$_GET['selectcat'] = $cats[0]->get_id();

if (isset($_GET['isStudentView'])) {
	if ( (isset($_GET['selectcat']) && $_GET['selectcat']>0) && (isset($_SESSION['studentview']) && $_SESSION['studentview']=='studentview') ) {
		$interbreadcrumb[]= array (
		'url' => 'index.php'.'?selectcat=0&amp;isStudentView='.$_GET['isStudentView'],
		'name' => get_lang('Gradebook')
		);
	}
}

if ( (isset($_GET['selectcat']) && $_GET['selectcat']>0) && (isset($_SESSION['studentview']) && $_SESSION['studentview']=='studentview') ) {
	Display::display_tool_header();

	//Introduction tool: student view
	Display::display_introduction_section(TOOL_GRADEBOOK, array('ToolbarSet' => 'AssessmentsIntroduction'));

	echo '<div id="content">';

	$category= Security::remove_XSS($_GET['selectcat']);
	$stud_id=api_get_user_id();
	$course_code=api_get_course_id();
	$session_id=api_get_session_id();
	$cats = Category :: load ($category, null, null, null, null, null, false);
	$allcat= $cats[0]->get_subcategories($stud_id, $course_code, $session_id);
	$alleval= $cats[0]->get_evaluations($stud_id);
	$alllink= $cats[0]->get_links($stud_id);
	$addparams=array();
	$gradebooktable= new GradebookTable($cats[0], $allcat, $alleval,$alllink, $addparams);
	$gradebooktable->display();
	
	echo '</div>';
	
	// bottom actions bar
	echo '<div class="actions">&nbsp;';
	echo '</div>';
	Display :: display_footer();
	exit;
} else {
	if ( !isset($_GET['selectcat']) && ($_SESSION['studentview']=='studentview') || (isset($_GET['isStudentView']) && $_GET['isStudentView']=='true') ) {
		//	if ( !isset($_GET['selectcat']) && ($_SESSION['studentview']=='studentview') && ($status<>1 && !api_is_platform_admin()) || (isset($_GET['isStudentView']) && $_GET['isStudentView']=='true' && $status<>1 && !api_is_platform_admin()) ) {
		Display::display_tool_header(get_lang('Gradebook'));
		//Introduction tool: student view
		Display::display_introduction_section(TOOL_GRADEBOOK, array('ToolbarSet' => 'AssessmentsIntroduction'));

		echo '<div id="content">';

		$stud_id=api_get_user_id();
		$course_code=api_get_course_id();
		$session_id=api_get_session_id();
		$addparams=array();
		$cats = Category :: load (0, null, null, null, null, null, false);
		$allcat= $cats[0]->get_subcategories($stud_id, $course_code, $session_id);
		$alleval= $cats[0]->get_evaluations($stud_id);
		$alllink= $cats[0]->get_links($stud_id);
		$gradebooktable= new GradebookTable($cats[0], $allcat, $alleval,$alllink, $addparams);
		$gradebooktable->display();
		
		echo '</div>';
		
		// bottom actions bar
		echo '<div class="actions">&nbsp;';
		echo '</div>';
		Display :: display_footer();
		exit;
	}
}


// --------------------------------------------------------------------------------
// -                                  ACTIONS                                     -
// --------------------------------------------------------------------------------
//this is called when there is no data for the course admin
if (isset ($_GET['createallcategories'])) {
	block_students();
	$coursecat= Category :: get_not_created_course_categories(api_get_user_id());
	if (!count($coursecat) == 0) {

		foreach ($coursecat as $row) {
			$cat= new Category();
			$cat->set_name($row[1]);
			$cat->set_course_code($row[0]);
			$cat->set_description(null);
			$cat->set_user_id(api_get_user_id());
			$cat->set_parent_id(0);
			$cat->set_weight(0);
			$cat->set_visible(1);
			$cat->add();
			unset ($cat);
		}
	}
	header('Location: '.$_SESSION['gradebook_dest'].'?addallcat=&selectcat=0');
	exit;
}
//show logs evaluations
if (isset ($_GET['visiblelog'])) {
	header('Location: ' . api_get_self().'/gradebook_showlog_eval.php');
	exit;
}
//move a category
if (isset ($_GET['movecat'])) {
	block_students();
	$cats= Category :: load($_GET['movecat']);
	if (!isset ($_GET['targetcat'])) {
		$move_form= new CatForm(CatForm :: TYPE_MOVE,
		$cats[0],
		'move_cat_form',
		null,
		api_get_self() . '?movecat=' . Security::remove_XSS($_GET['movecat'])
		. '&selectcat=' . Security::remove_XSS($_GET['selectcat']));
			if ($move_form->validate()) {
				header('Location: ' . api_get_self() . '?selectcat=' . Security::remove_XSS($_GET['selectcat'])
				 . '&movecat=' . Security::remove_XSS($_GET['movecat'])
				 . '&targetcat=' . $move_form->exportValue('move_cat'));
				 exit;
			}
		} else {
			$targetcat= Category :: load($_GET['targetcat']);
			$course_to_crsind = ($cats[0]->get_course_code() != null && $targetcat[0]->get_course_code() == null);

			if (!($course_to_crsind && !isset($_GET['confirm']))) {
				$cats[0]->move_to_cat($targetcat[0]);
				header('Location: ' . api_get_self() . '?categorymoved=&selectcat=' . Security::remove_XSS($_GET['selectcat']));
				exit;
				}
				unset ($targetcat);
		}
	unset ($cats);
}
//move an evaluation
if (isset ($_GET['moveeval'])) {
	block_students();
	$evals= Evaluation :: load($_GET['moveeval']);
	if (!isset ($_GET['targetcat'])) {

		$move_form= new EvalForm(EvalForm :: TYPE_MOVE,
		$evals[0],
		null,
		'move_eval_form',
		null,
		api_get_self() . '?moveeval=' . Security::remove_XSS($_GET['moveeval'])
		. '&selectcat=' . Security::remove_XSS($_GET['selectcat']));

		if ($move_form->validate()) {
			header('Location: ' .api_get_self() . '?selectcat=' . Security::remove_XSS($_GET['selectcat'])
			. '&moveeval=' . Security::remove_XSS($_GET['moveeval'])
			. '&targetcat=' . $move_form->exportValue('move_cat'));
			exit;
			}
		} else {
		$targetcat= Category :: load($_GET['targetcat']);
		$course_to_crsind = ($evals[0]->get_course_code() != null && $targetcat[0]->get_course_code() == null);

		if (!($course_to_crsind && !isset($_GET['confirm']))) {
			$evals[0]->move_to_cat($targetcat[0]);
			header('Location: ' . api_get_self() . '?evaluationmoved=&selectcat=' . Security::remove_XSS($_GET['selectcat']));
			exit;
		}
		unset ($targetcat);
	}
	unset ($evals);
}
//move a link
if (isset ($_GET['movelink'])) {
	block_students();
	$link= LinkFactory :: load($_GET['movelink']);
	$move_form= new LinkForm(LinkForm :: TYPE_MOVE, null, $link[0], 'move_link_form', null, api_get_self() . '?movelink=' . $_GET['movelink'] . '&selectcat=' . Security::remove_XSS($_GET['selectcat']));

	if ($move_form->validate()) {
		$targetcat= Category :: load($move_form->exportValue('move_cat'));
		$link[0]->move_to_cat($targetcat[0]);
		unset ($link);
		header('Location: ' . api_get_self(). '?linkmoved=&selectcat=' . Security::remove_XSS($_GET['selectcat']));
		exit;
	}
}
//parameters for categories
if (isset ($_GET['visiblecat'])) {
	block_students();

	if (isset ($_GET['set_visible'])) {
		$visibility_command= 1;
	} else {
		$visibility_command= 0;
	}
	$cats= Category :: load($_GET['visiblecat']);
	$cats[0]->set_visible($visibility_command);
	$cats[0]->save();
	$cats[0]->apply_visibility_to_children();
	unset ($cats);
	if ($visibility_command) {
		$confirmation_message = get_lang('ViMod');
		$filter_confirm_msg = false;
	} else {
		$confirmation_message = get_lang('InViMod');
		$filter_confirm_msg = false;
	}
}
if (isset ($_GET['deletecat'])) {
	block_students();
	$cats= Category :: load($_GET['deletecat']);
	//delete all categories,subcategories and results
	if ($cats[0] != null) {
		if ($cats[0]->get_id() != 0) {
			 // better don't try to delete the root...
			 $cats[0]->delete_all();
		}
	}
	$confirmation_message = get_lang('CategoryDeleted');
	$filter_confirm_msg = false;
}
//parameters for evaluations
if (isset ($_GET['visibleeval'])) {
	block_students();
	if (isset ($_GET['set_visible'])) {
		$visibility_command= 1;
	} else {
		$visibility_command= 0;
	}
	$eval= Evaluation :: load($_GET['visibleeval']);
	$eval[0]->set_visible($visibility_command);
	$eval[0]->save();
	unset ($eval);
	if ($visibility_command) {
		$confirmation_message = get_lang('ViMod');
		$filter_confirm_msg = false;
	} else {
		$confirmation_message = get_lang('InViMod');
		$filter_confirm_msg = false;
	}
}
if (isset ($_GET['deleteeval'])) {
	block_students();
	$eval= Evaluation :: load($_GET['deleteeval']);
	if ($eval[0] != null) {
		$eval[0]->delete_with_results();
	}
	$confirmation_message = get_lang('GradebookEvaluationDeleted');
	$filter_confirm_msg = false;
}
//parameters for links
if (isset ($_GET['visiblelink'])) {
	block_students();
	if (isset ($_GET['set_visible'])) {
		$visibility_command= 1;
	} else {
		$visibility_command= 0;
	}
	$link= LinkFactory :: load($_GET['visiblelink']);
	$link[0]->set_visible($visibility_command);
	$link[0]->save();
	unset ($link);
	if ($visibility_command) {
		$confirmation_message = get_lang('ViMod');
		$filter_confirm_msg = false;
	} else {
		$confirmation_message = get_lang('InViMod');
		$filter_confirm_msg = false;
	}
}

if (isset ($_GET['deletelink'])) {
	block_students();
	$get_delete_link=Security::remove_XSS($_GET['deletelink']);
	//fixing #5229
	if (!empty($get_delete_link)) {
		$link= LinkFactory :: load($get_delete_link);
		if ($link[0] != null) {
			$sql='UPDATE '.$tbl_forum_thread.' SET thread_qualify_max=0,thread_weight=0,thread_title_qualify="" WHERE thread_id=(SELECT ref_id FROM '.$tbl_grade_links.' where id='.$get_delete_link.');';
			Database::query($sql);
			$link[0]->delete();
		}
		unset ($link);
		$confirmation_message = get_lang('LinkDeleted');
		$filter_confirm_msg = false;
	}
}

if (!empty($course_to_crsind) && !isset($_GET['confirm'])) {
	block_students();

	if (!isset($_GET['movecat']) && !isset($_GET['moveeval'])) {
		die ('Error: movecat or moveeval not defined');
	}
	$button = '<form name="confirm"
					 method="post"
					 action="'.api_get_self() .'?confirm='
					.(isset($_GET['movecat']) ? '&movecat=' . Security::remove_XSS($_GET['movecat'])
					: '&moveeval=' . Security::remove_XSS($_GET['moveeval']) )
					.'&selectcat=' . Security::remove_XSS($_GET['selectcat'])
					.'&targetcat=' . Security::remove_XSS($_GET['targetcat']).'">
			   <input type="submit" value="'.get_lang('Ok').'">
			   </form>';
	$warning_message = get_lang('MoveWarning').'<br><br>'.$button;
	$filter_warning_msg = false;
}
//actions on the sortabletable
if (isset ($_POST['action'])) {
	block_students();
	$number_of_selected_items= count($_POST['id']);

	if ($number_of_selected_items == '0') {
		$warning_message = get_lang('NoItemsSelected');
		$filter_warning_msg = false;
	} else {
		switch ($_POST['action']) {
			case 'deleted' :
				$number_of_deleted_categories= 0;
				$number_of_deleted_evaluations= 0;
				$number_of_deleted_links= 0;
				foreach ($_POST['id'] as $indexstr) {
					if (substr($indexstr, 0, 4) == 'CATE') {
						$cats= Category :: load(substr($indexstr, 4));
						if ($cats[0] != null) {
							$cats[0]->delete_all();
						}
						$number_of_deleted_categories++;
					}
					if (substr($indexstr, 0, 4) == 'EVAL') {
						$eval= Evaluation :: load(substr($indexstr, 4));
						if ($eval[0] != null) {
							$eval[0]->delete_with_results();
						}

						$number_of_deleted_evaluations++;
					}
					if (substr($indexstr, 0, 4) == 'LINK') {
						//fixing #5229
						$id = substr($indexstr, 4);
						if (!empty($id)) {
							$link= LinkFactory :: load($id);
							if ($link[0] != null) {
								$link[0]->delete();
							}
							$number_of_deleted_links++;
						}
					}
				}
				$confirmation_message = get_lang('DeletedCategories') . ' : <b>' . $number_of_deleted_categories . '</b><br />' . get_lang('DeletedEvaluations') . ' : <b>' . $number_of_deleted_evaluations . '</b><br />' . get_lang('DeletedLinks') . ' : <b>' . $number_of_deleted_links . '</b><br /><br />' . get_lang('TotalItems') . ' : <b>' . $number_of_selected_items . '</b>';
				$filter_confirm_msg = false;
				break;
			case 'setvisible' :
				foreach ($_POST['id'] as $indexstr) {
					if (substr($indexstr, 0, 4) == 'CATE') {
						$cats= Category :: load(substr($indexstr, 4));
						$cats[0]->set_visible(1);
						$cats[0]->save();
						$cats[0]->apply_visibility_to_children();
					}
					if (substr($indexstr, 0, 4) == 'EVAL') {
						$eval= Evaluation :: load(substr($indexstr, 4));
						$eval[0]->set_visible(1);
						$eval[0]->save();
					}
					if (substr($indexstr, 0, 4) == 'LINK') {
						$link= LinkFactory :: load(substr($indexstr, 4));
						$link[0]->set_visible(1);
						$link[0]->save();
					}
				}
				$confirmation_message = get_lang('ItemsVisible');
				$filter_confirm_msg = false;
				break;
			case 'setinvisible' :
				foreach ($_POST['id'] as $indexstr) {
					if (substr($indexstr, 0, 4) == 'CATE') {
						$cats= Category :: load(substr($indexstr, 4));
						$cats[0]->set_visible(0);
						$cats[0]->save();
						$cats[0]->apply_visibility_to_children();
					}
					if (substr($indexstr, 0, 4) == 'EVAL') {
						$eval= Evaluation :: load(substr($indexstr, 4));
						$eval[0]->set_visible(0);
						$eval[0]->save();
					}
					if (substr($indexstr, 0, 4) == 'LINK') {
						$link= LinkFactory :: load(substr($indexstr, 4));
						$link[0]->set_visible(0);
						$link[0]->save();
					}
				}
				$confirmation_message = get_lang('ItemsInVisible');
				$filter_confirm_msg = false;
				break;
		}
	}
}

if (isset ($_POST['submit']) && isset ($_POST['keyword'])) {
	header('Location: ' . api_get_self() . '?selectcat=' . Security::remove_XSS($_GET['selectcat'])
	. '&search='.Security::remove_XSS($_POST['keyword']));
	exit;
}


// --------------------------------------------------------------------------------
// -                       DISPLAY HEADERS AND MESSAGES                           -
// --------------------------------------------------------------------------------

if (!isset($_GET['exportpdf']) and !isset($_GET['export_certificate'])) {
	if (isset ($_GET['studentoverview'])) {
		$interbreadcrumb[]= array (
			'url' => $_SESSION['gradebook_dest'].'?selectcat=' . Security::remove_XSS($_GET['selectcat']),
			'name' => get_lang('Gradebook')
		);
		Display::display_tool_header(get_lang('FlatView'));
	} elseif (isset ($_GET['search'])) {
		$interbreadcrumb[]= array (
			'url' => $_SESSION['gradebook_dest'].'?selectcat=' . Security::remove_XSS($_GET['selectcat']),
			'name' => get_lang('Gradebook')
		);
		Display::display_tool_header(get_lang('SearchResults'));
	} elseif(isset ($_GET['selectcat'])) {
		$interbreadcrumb[]= array (
			'url' => $_SESSION['gradebook_dest'],
			'name' => get_lang('Gradebook')
		);

		Display::display_tool_header('');
        // Display introduction section
        if (api_is_platform_admin() || api_is_allowed_to_create_course()  || $status==1) {
        /*
        -----------------------------------------------------------
            Introduction section (teacher edit)
        -----------------------------------------------------------
        */

            // Tool introduction
            Display::display_introduction_section(TOOL_GRADEBOOK, array('ToolbarSet' => 'AssessmentsIntroduction'));
        }
	} else {
		Display::display_tool_header(get_lang('Gradebook'));
	}
}

if (isset ($_GET['categorymoved'])) {
	Display :: display_confirmation_message(get_lang('CategoryMoved'),false);
}
if (isset ($_GET['evaluationmoved'])) {
	Display :: display_confirmation_message(get_lang('EvaluationMoved'),false);
}
if (isset ($_GET['linkmoved'])) {
	Display :: display_confirmation_message(get_lang('LinkMoved'),false);
}
if (isset ($_GET['addcat'])) {
	Display :: display_confirmation_message(get_lang('CategoryAdded'),false);
}
if (isset ($_GET['linkadded'])) {
	Display :: display_confirmation_message(get_lang('LinkAdded'),false);
}
if (isset ($_GET['addresult'])) {
	Display :: display_confirmation_message(get_lang('ResultAdded'),false);
}
if (isset ($_GET['editcat'])) {
	Display :: display_confirmation_message(get_lang('CategoryEdited'),false);
}
if (isset ($_GET['editeval'])) {
	Display :: display_confirmation_message(get_lang('EvaluationEdited'),false);
}
if (isset ($_GET['linkedited'])) {
	Display :: display_confirmation_message(get_lang('LinkEdited'),false);
}
if (isset ($_GET['nolinkitems'])){
	Display :: display_warning_message(get_lang('NoLinkItems'),false);
}
if (isset ($_GET['addallcat'])){
	Display :: display_normal_message(get_lang('AddAllCat'),false);
}
if (isset ($confirmation_message)){
	Display :: display_confirmation_message($confirmation_message,$filter_confirm_msg);
}
if (isset ($warning_message)){
	Display :: display_warning_message($warning_message,$filter_warning_msg);
}
if (isset ($move_form)){
	Display :: display_normal_message($move_form->toHtml(),false);
}

// --------------------------------------------------------------------------------
// -                        LOAD DATA & DISPLAY TABLE                             -
// --------------------------------------------------------------------------------

$is_platform_admin= api_is_platform_admin();
$is_course_admin= api_is_allowed_to_create_course();

//load data for category, evaluation and links
if (empty ($_GET['selectcat'])) {
	$category= 0;
} else {
	$category= $_GET['selectcat'];
}
$simple_search_form='';
// search disabled in course context
/*
$simple_search_form= new UserForm(UserForm :: TYPE_SIMPLE_SEARCH, null, 'simple_search_form', null, api_get_self() . '?selectcat=' . Security::remove_XSS($_GET['selectcat']));
$values= $simple_search_form->exportValues();
$keyword = '';
if (isset($_GET['search']) && !empty($_GET['search']))
	$keyword = Security::remove_XSS($_GET['search']);
if ($simple_search_form->validate() && (empty($keyword)))
	$keyword = $values['keyword'];
*/

/* search disabled in course context
if (!empty($keyword))
{
	$cats= Category :: load($category);
	$allcat= array ();
	$alleval= Evaluation :: find_evaluations($keyword, $cats[0]->get_id());
	$alllink= LinkFactory :: find_links($keyword, $cats[0]->get_id());
}
else
*/

if (isset ($_GET['studentoverview'])) {
	$cats= Category :: load($category);
	$stud_id= (api_is_allowed_to_create_course() ? null : api_get_user_id());
	$allcat= array ();
	$alleval= $cats[0]->get_evaluations($stud_id, true);
	$alllink= $cats[0]->get_links($stud_id, true);
	if (isset ($_GET['exportpdf'])) {
		$datagen = new GradebookDataGenerator ($allcat,$alleval, $alllink);
		$header_names = array(get_lang('Name'),get_lang('Description'),get_lang('Weight'),get_lang('Date'),get_lang('Results'));
		$data_array = $datagen->get_data(GradebookDataGenerator :: GDG_SORT_NAME,0,null,true);
		$newarray = array();

		foreach ($data_array as $data) {
			$newarray[] = array_slice($data, 1);
		}
		$pdf= new Cezpdf();
		$pdf->selectFont(api_get_path(LIBRARY_PATH).'ezpdf/fonts/Courier.afm');
		$pdf->ezSetMargins(30, 30, 50, 30);
		$pdf->ezSetY(810);
		$pdf->ezText(get_lang('FlatView').' ('. date('j/n/Y g:i') .')',12,array('justification'=>'center'));
		$pdf->line(50,790,550,790);
		$pdf->line(50,40,550,40);
		$pdf->ezSetY(750);
		$pdf->ezTable($newarray,$header_names,'',array('showHeadings'=>1,'shaded'=>1,'showLines'=>1,'rowGap'=>3,'width'=> 500));
		$pdf->ezStream();
		exit;
		}
	} elseif (!empty($_GET['export_certificate'])) {
	$user_id = strval(intval($_GET['user']));
	if (!api_is_allowed_to_edit(true,true)) {
		$user_id = api_get_user_id();
	}

	$category = Category :: load($category); //hack replace $category = Category :: load ($_GET['cat_id']); to get de course name in certificates
	
	if ($category[0]->is_certificate_available($user_id)) {
		$user= get_user_info_from_id($user_id);
		$scoredisplay = ScoreDisplay :: instance();
		$scorecourse = $category[0]->calc_score($user_id);
		$scorecourse_display = (isset($scorecourse) ? $scoredisplay->display_score($scorecourse,SCORE_AVERAGE) : get_lang('NoResultsAvailable'));

		$cattotal = Category :: load($_GET['cat_id']);
		$scoretotal= $cattotal[0]->calc_score($user_id);
		$scoretotal_display = (isset($scoretotal) ? $scoredisplay->display_score($scoretotal,SCORE_PERCENT) : get_lang('NoResultsAvailable'));

		//prepare all necessary variables:
		$organization_name = api_get_setting('Institution');
		$portal_name = api_get_setting('siteName');
		$stud_fn = $user['firstname'];
		$stud_ln = $user['lastname'];
		$certif_text = sprintf(get_lang('CertificateWCertifiesStudentXFinishedCourseYWithGradeZ'),$organization_name,$stud_fn.' '.$stud_ln,$category[0]->get_name(),$scorecourse_display);
		$certif_text = str_replace("\\n","\n",$certif_text);
		$date = date('d/m/Y',time());

		$pdf= new Cezpdf('a4','landscape');
		$pdf->selectFont(api_get_path(LIBRARY_PATH).'ezpdf/fonts/Courier.afm');
		$pdf->ezSetMargins(30, 30, 50, 50);
		//line Y coordinates in landscape mode are upside down (500 is on top, 10 is on the bottom)
		$pdf->line(50,50,790,50);
		$pdf->line(50,550,790,550);
		$pdf->ezSetY(450);
		$pdf->ezImage(api_get_path(SYS_CODE_PATH).'img/dokeos_logo_certif.png',1,400,'','center','');
		$pdf->ezSetY(480);
		$pdf->ezText($certif_text,28,array('justification'=>'center'));
		//$pdf->ezSetY(750);
		$pdf->ezSetY(50);
		$pdf->ezText($date,18,array('justification'=>'center'));
		$pdf->ezSetY(580);
		$pdf->ezText($organization_name,22,array('justification'=>'left'));
		$pdf->ezSetY(580);
		$pdf->ezText($portal_name,22,array('justification'=>'right'));
		$pdf->ezStream();
	}
	exit;
} else { //in any other case (no search, no pdf), print the available gradebooks
    // Important note: loading a category will actually load the *contents* of
    // this category. This means that, to show the categories of a course,
    // we have to show the root category and show its subcategories that
    // are inside this course. This is done at the time of calling
    // $cats[0]->get_subcategories(), not at the time of doing Category::load()
    // $category comes from GET['selectcat']
    $course_code = api_get_course_id();
    $session_id = api_get_session_id();

    //if $category = 0 (which happens when GET['selectcat'] is undefined)
    // then Category::load() will create a new 'root' category with empty
    // course and session fields in memory (Category::create_root_category())
    if ($_in_course === true) {
        // When *inside* a course, we want to make sure there is one (and only
        // one) category for this course or for this session.
		
		//hack for delete a gradebook from inside course
		$clean_deletecat=Security::remove_XSS($_GET['deletecat']);
		if (!empty($clean_deletecat)) 
		{
			exit;
		}		
		//end hack
		
	    $cats = Category :: load(null, null, $course_code, null, null, $session_id, false);
        if (empty($cats)) {
            // There is no category for this course+session, so create one
            $cat= new Category();
            $course_code = api_get_course_id();
            $session_id = api_get_session_id();
            if (!empty($session_id)) {
            	$my_session_id=api_get_session_id();
                $s_name = api_get_session_name($my_session_id);
            	$cat->set_name($course_code.' - '.get_lang('Session').' '.$s_name);
                $cat->set_session_id($session_id);
            } else {
                $cat->set_name($course_code);
            }
            $cat->set_course_code($course_code);
            $cat->set_description(null);
            $cat->set_user_id(api_get_user_id());
            $cat->set_parent_id(0);
            $cat->set_weight(100);
            $cat->set_visible(1);// Gradebook must visible for student from Gradebook tab
            $can_edit = api_is_allowed_to_edit(true, true);
            if ($can_edit) {
                $cat->add();
            }
            unset ($cat);
        }
        unset($cats);
    }
    $cats = Category :: load ($category, null, null, null, null, null, false);

    //with this fix the teacher only can view 1 gradebook
	//$stud_id= (api_is_allowed_to_create_course() ? null : api_get_user_id());
    if (api_is_platform_admin()) {
            $stud_id= (api_is_allowed_to_create_course() ? null : api_get_user_id());
    } else {
    	$stud_id= api_get_user_id();
    }
	$allcat= $cats[0]->get_subcategories($stud_id, $course_code, $session_id);
	$alleval= $cats[0]->get_evaluations($stud_id);
	$alllink= $cats[0]->get_links($stud_id);
    //whether we found a category or not, we now have a category object with
    // empty or full subcats
}

// add params to the future links (in the table shown)
$addparams = array ('selectcat' => $cats[0]->get_id());
/*
if (isset($_GET['search'])) {
	$addparams['search'] = $keyword;
}
*/
if (isset ($_GET['studentoverview'])) {
	$addparams['studentoverview'] = '';
}
//$addparams['cidReq']='';
if (isset($_GET['cidReq']) && $_GET['cidReq']!='') {
	$addparams['cidReq']=Security::remove_XSS($_GET['cidReq']);
} else {
	$addparams['cidReq']='';
}
$gradebooktable= new GradebookTable($cats[0], $allcat, $alleval,$alllink, $addparams);
$no_qualification = false;
if (( count($allcat) == 0) && ( count($alleval) == 0 ) && ( count($alllink) == 0 )) {
    $no_qualification = true;
    if ((($is_course_admin) && (!isset ($_GET['selectcat']))) && api_is_course_tutor()) {
	   Display :: display_normal_message(get_lang('GradebookWelcomeMessage') . '<br /><br /><form name="createcat" method="post" action="' . api_get_self() . '?createallcategories=1"><input type="submit" value="' . get_lang('CreateAllCat') . '"></form>',false);
    }
}
//here we are in a sub category
if ($category != '0') {
	$cat=new Category();
	//$dblib=new Database();

	$category_id=Security::remove_XSS($_GET['selectcat']);
	$course_id=Database::get_course_by_category($category_id);
	$show_message=$cat->show_message_resource_delete($course_id);
	if ($show_message=='') {
		
		//hack for inside courses menu cat	
		if (api_is_allowed_to_edit()) {

			$op_cat_weight= '<strong>'.get_lang('Weight').'</strong>'.': '.((intval($cats[0]->get_weight())>0) ? $cats[0]->get_weight() : 0);			
			$opt_cat_cert_min= '<strong>'.get_lang('CertificateMinScore').'</strong>'.': '.(intval($cats[0]->get_certificate_min_score()>0) ? $cats[0]->get_certificate_min_score() : 0);
			$opt_cat_descrip= '<strong>'.get_lang('GradebookDescriptionLog').'</strong>'.': '.(($cats[0]->get_description() == "" || is_null($cats[0]->get_description())) ? get_lang('None') : $cats[0]->get_description());					

			$visibility_icon= ($cats[0]->is_visible() == 0) ? 'invisible' : 'visible';
			$visibility_command= ($cats[0]->is_visible() == 0) ? 'set_visible' : 'set_invisible';
		}
		else
		{
			// generating the total score for a course
			$stud_id= api_get_user_id();
			$cats_course = Category :: load ($id, null, null, null, null, null, false);
			$alleval_course= $cats_course[0]->get_evaluations($stud_id,true);
			$alllink_course= $cats_course[0]->get_links($stud_id,true);
			$evals_links = array_merge($alleval_course, $alllink_course);
			$item_value=0;
			$item_total=0;
			for ($count=0; $count < count($evals_links); $count++) {
						$item = $evals_links[$count];
						$score = $item->calc_score($stud_id);

						$score_denom=($score[1]==0) ? 1 : $score[1];
						$item_value+=$score[0]/$score_denom*$item->get_weight();
						$item_total+=$item->get_weight();
			}
			$item_value = number_format($item_value, 2, '.', ' ');
			$cattotal = Category :: load($id);
			$scoretotal= $cattotal[0]->calc_score(api_get_user_id());
			$scoretotal_display = (isset($scoretotal)? round($scoretotal[0],2).'/'.round($scoretotal[1],2).'('.round(($scoretotal[0] / $scoretotal[1]) * 100,2) . ' %)': '-');
						
			//show certificate
			$certificate_min_score=$cats[0]->get_certificate_min_score();
			if (isset($certificate_min_score) && (int)$item_value >= (int)$certificate_min_score) {
				$certificates = '<a href="'.api_get_path(WEB_CODE_PATH) .'gradebook/'.$_SESSION['gradebook_dest'].'?export_certificate=yes&cat_id='.$cats[0]->get_course_code().'"><img src="'.api_get_path(WEB_CODE_PATH) . 'img/dokeos.gif" />'.get_lang('Certificates').'</a>&nbsp;'.get_lang('langTotal').': '.$scoretotal_display;
			
			echo '<div class="actions" align="right">';
			 echo $certificates;
			echo '</div>';
			
			}
		} //end hack
		
		DisplayGradebook :: display_header_gradebook($cats[0], 0, $category_id, $is_course_admin, $is_platform_admin, $simple_search_form, false, true, false);
	}

} else {
//this is the root category
	//DisplayGradebook :: display_header_gradebook($cats[0], 0, 0, $is_course_admin, $is_platform_admin, $simple_search_form, false, false);
}

if (api_is_platform_admin() || api_is_allowed_to_create_course()  || $status==1) {
	if ( (isset ($_GET['selectcat']) && $_GET['selectcat']<>0) ) {
	//
	} else {
			if ( ((isset ($_GET['selectcat']) && $_GET['selectcat']==0) || ((isset($_GET['cidReq']) && $_GET['cidReq']!==''))) || isset($_GET['isStudentView']) && $_GET['isStudentView']=='false') {
				$cats = Category :: load(null, null, $course_code, null, null, $session_id, false);
				if(!$first_time=1)
				{
					DisplayGradebook :: display_reduce_header_gradebook($cats[0],$is_course_admin, $is_platform_admin, $simple_search_form, false, false);
				}
		}
	}
}

echo '<div id="content">';

if($first_time==1 && api_is_allowed_to_edit())
{
	echo '<meta http-equiv="refresh" content="0;url='.api_get_self().'?cidReq='.$course_code.'" />';
}
else
{
	$gradebooktable->display();
}

echo '</div>';

echo '<div class="actions">&nbsp;';
/*if ($show_message===false && api_is_platform_admin() || api_is_allowed_to_create_course()  || $status==1) {
echo  '<a href="gradebook_scoring_system.php?cidReq='.$course_code.'&selectcat=' . $cats[0]->get_id() .'">'.Display::return_icon('settings_22.png', get_lang('ScoreEdit')).' ' . get_lang('ScoreEdit') . '</a>';
echo '<a href="gradebook_edit_all.php?cidReq='.$course_code.'&selectcat=' . $cats[0]->get_id() . '">'.Display::return_icon('balance.png', get_lang('EditAllWeights')).' ' . get_lang('EditAllWeights') . '</a>';
}*/
echo '</div>';

Display :: display_footer();