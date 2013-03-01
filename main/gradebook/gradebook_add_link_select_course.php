<?php

/* For licensing terms, see /dokeos_license.txt */

// name of the language file that needs to be included
$language_file = 'gradebook';

// including the global dokeos file
require_once ('../inc/global.inc.php');

// including additional libraries
require_once ('lib/be.inc.php');
require_once ('lib/gradebook_functions.inc.php');
require_once ('lib/fe/catform.class.php');

// access restriction
api_block_anonymous_users();
block_students();

// additional javascript
$htmlHeadXtra[] = '<script>
  $(document).ready(function (){
    $("div.label").attr("style","width: 100%;text-align:left");
    $("div.row").attr("style","width: 100%;");
    $("div.formw").attr("style","width: 100%;");
  });
</script>';
$catadd = new Category();
$catadd->set_user_id($_user['user_id']);
$catadd->set_parent_id($_GET['selectcat']);
$catcourse = Category :: load ($_GET['selectcat']);
//$catadd->set_course_code($catcourse[0]->get_course_code());
$form = new CatForm(CatForm :: TYPE_SELECT_COURSE, $catadd, 'add_cat_form', null, api_get_self().'?selectcat=' . Security::remove_XSS($_GET['selectcat']));

if ($form->validate()) {
	$values = $form->exportValues();
	$cat = new Category();
	$cat->set_course_code($values['select_course']);
	$cat->set_name($values['name']);
	header('location: gradebook_add_link.php?selectcat=' .Security::remove_XSS($_GET['selectcat']).'&course_code='.Security::remove_XSS($values['select_course']));
	exit;
}

$interbreadcrumb[] = array (
	'url' => $_SESSION['gradebook_dest'].'?selectcat='.Security::remove_XSS($_GET['selectcat']),
	'name' => get_lang('Gradebook'
));
Display::display_tool_header(get_lang('NewCategory'));
$form->display();
Display :: display_footer();
