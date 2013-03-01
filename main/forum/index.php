<?php // $Id: document.php 16494 2008-10-10 22:07:36Z yannoo $

/*
==============================================================================
	Dokeos - elearning and course management software

	Copyright (c) 2004-2008 Dokeos SPRL
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
*	These files are a complete rework of the forum. The database structure is
*	based on phpBB but all the code is rewritten. A lot of new functionalities
*	are added:
* 	- forum categories and forums can be sorted up or down, locked or made invisible
*	- consistent and integrated forum administration
* 	- forum options: 	are students allowed to edit their post?
* 						moderation of posts (approval)
* 						reply only forums (students cannot create new threads)
* 						multiple forums per group
*	- sticky messages
* 	- new view option: nested view
* 	- quoting a message
*
*	@Author Patrick Cool <patrick.cool@UGent.be>, Ghent University
*	@Copyright Ghent University
*	@Copyright Patrick Cool
*
* 	@package dokeos.forum
*/

// name of the language file that needs to be included
$language_file = 'forum';

// including the global dokeos file
require '../inc/global.inc.php';
require_once api_get_path(LIBRARY_PATH).'forummanager.lib.php';
require_once api_get_path(LIBRARY_PATH).'searchengine.lib.php';
//$htmlHeadXtra[] = '<script src="'.api_get_path(WEB_LIBRARY_PATH).'javascript/jquery-1.4.2.min.js" type="text/javascript" language="javascript"></script>';
$htmlHeadXtra[] = '<script src="'.api_get_path(WEB_LIBRARY_PATH).'javascript/jquery.ui.all.js" type="text/javascript" language="javascript"></script>';
$htmlHeadXtra[] = '<script type="text/javascript" language="javascript">
	$(document).ready(function(){ $(\'.hide-me\').slideUp() });
	function hidecontent(content){ $(content).slideToggle(\'normal\'); }
	</script>';
$htmlHeadXtra[] = '<script type="text/javascript" language="javascript">

		function advanced_parameters() {
			if(document.getElementById(\'options\').style.display == \'none\') {
					document.getElementById(\'options\').style.display = \'block\';
					document.getElementById(\'plus_minus\').innerHTML=\'&nbsp;'.Display::return_icon('div_hide.gif',get_lang('Hide'),array('style'=>'vertical-align:middle')).'&nbsp;'.get_lang('AdvancedParameters').'\';
			} else {
					document.getElementById(\'options\').style.display = \'none\';
					document.getElementById(\'plus_minus\').innerHTML=\'&nbsp;'.Display::return_icon('div_show.gif',get_lang('Show'),array('style'=>'vertical-align:middle')).'&nbsp;'.get_lang('AdvancedParameters').'\';
			}
		}
	</script>';
// the section (tabs)
$this_section=SECTION_COURSES;

// notice for unauthorized people.
api_protect_course_script(true);

// including additional library scripts
require_once(api_get_path(LIBRARY_PATH).'/text.lib.php');
require_once (api_get_path(LIBRARY_PATH).'formvalidator/FormValidator.class.php');
require_once (api_get_path(LIBRARY_PATH).'groupmanager.lib.php');
require_once '../newscorm/learnpath.class.php';
require_once '../newscorm/learnpathItem.class.php';
$nameTools=get_lang('Forums');

/*
-----------------------------------------------------------
	Including necessary files
-----------------------------------------------------------
*/
require 'forumconfig.inc.php';
require_once 'forumfunction.inc.php';


// gradebook stuff
if (!empty($_GET['gradebook']) && $_GET['gradebook']=='view' ) {
	$_SESSION['gradebook']=Security::remove_XSS($_GET['gradebook']);
	$gradebook=	$_SESSION['gradebook'];
} elseif (empty($_GET['gradebook'])) {
	unset($_SESSION['gradebook']);
	$gradebook=	'';
}

if (!empty($gradebook) && $gradebook=='view') {
	$interbreadcrumb[] = array (
		'url' => '../gradebook/' . $_SESSION['gradebook_dest'],
		'name' => get_lang('Gradebook')
	);
}


$search_forum=isset($_GET['search']) ? Security::remove_XSS($_GET['search']) : '';

// breadcrumbs
$interbreadcrumb[]=array("url" => "index.php?gradebook=$gradebook&search=".$search_forum,"name" => $nameTools);


if (isset($_GET['action']) && $_GET['action']=='add' ) {

	switch ($_GET['content']) {
		case 'forum':			
			$interbreadcrumb[] = array (	"url"	=> api_get_self().'?'.api_get_cidreq().'&gradebook='.$gradebook.'&action=add&amp;content=forum','name' => get_lang('AddForum'));
			break;
		case 'forumcategory':	
			$interbreadcrumb[] = array (	"url"	=> api_get_self().'?'.api_get_cidreq().'&gradebook='.$gradebook.'&action=add&amp;content=forumcategory','name'	=> get_lang('AddForumCategory'));
			break;
		default: 
			break;
	}
}

//Display :: display_tool_header('');
Display::display_tool_header();

// api_display_tool_title($nameTools);
//echo '<link href="forumstyles.css" rel="stylesheet" type="text/css" />';

// Tool introduction
Display::display_introduction_section(TOOL_FORUM);

$form_count=0;


/*
------------------------------------------------------------------------------------------------------
	ACTIONS
------------------------------------------------------------------------------------------------------
*/
$get_actions=isset($_GET['action']) ? $_GET['action'] : '';

// notification
if (isset($_GET['action']) && $_GET['action'] == 'notify' AND isset($_GET['content']) AND isset($_GET['id'])) {
	if (api_get_session_id()!=0 && api_is_allowed_to_session_edit(false,true)==false) {		 
		api_not_allowed();
	}
	$return_message = set_notification($_GET['content'],$_GET['id']);
	Display :: display_confirmation_message($return_message,false);
}

	// getting all the information that is new for this user
	get_whats_new();
	$whatsnew_post_info = array();
	$whatsnew_post_info = $_SESSION['whatsnew_post_info'];

	/*
	-----------------------------------------------------------
	  			TRACKING
	-----------------------------------------------------------
	*/
	event_access_tool(TOOL_FORUM);


	/*
	------------------------------------------------------------------------------------------------------
		ACTION LINKS
	------------------------------------------------------------------------------------------------------
	*/
	$session_id=isset($_SESSION['id_session']) ? $_SESSION['id_session'] : false;
	$forum_categories_list=get_forum_categories();
	echo '<div class="actions">';
	echo '<span>'.search_link().'</span>';
	if (api_is_allowed_to_edit(false,true)) {
		echo '<a href="'.api_get_self().'?'.api_get_cidreq().'&gradebook='.$gradebook.'&action=add&amp;content=forumcategory"> '.Display::return_icon('pixel.gif',get_lang('AddForumCategory'),array('class' => 'toolactionplaceholdericon toolactionnewforumcategory')).get_lang('AddForumCategory').'</a>';
		if (is_array($forum_categories_list) and !empty($forum_categories_list)) {
			echo '<a href="'.api_get_self().'?'.api_get_cidreq().'&gradebook='.$gradebook.'&action=add&amp;content=forum'.$add_params_for_lp.'"> '.Display::return_icon('pixel.gif',get_lang('AddForum'),array('class' => 'toolactionplaceholdericon toolactionnewforum')).get_lang('AddForum').'</a>';
		}
		if (isset($_GET['lp_id']) && $_GET['lp_id'] > 0) {
		  echo '<a href="../newscorm/lp_controller.php?' . api_get_cidreq() . '&action=add_item&type=step&lp_id='.$_GET['lp_id'].'">' . Display::return_icon('pixel.gif', get_lang('Content'),array('class'=>'toolactionplaceholdericon toolactionauthorcontent')).get_lang("Content") . '</a>';
		  echo '<a href="../newscorm/lp_controller.php?' . api_get_cidreq() . '&action=admin_view&lp_id='.$_GET['lp_id'].'">' . Display::return_icon('pixel.gif', get_lang('Scenario'),array('class'=>'toolactionplaceholdericon toolactionauthorscenario')).get_lang("Scenario") . '</a>';
		  }
	}
	echo '</div>';

	// start the content div
	echo '<div id="content">';

	// displaying the forms, saving the forms, ...
	if (api_is_allowed_to_edit(false,true)) {
		handle_forum_and_forumcategories();
	}

	/*
	------------------------------------------------------------------------------------------------------
		RETRIEVING ALL THE FORUM CATEGORIES AND FORUMS
	------------------------------------------------------------------------------------------------------
	note: we do this here just after het handling of the actions to be sure that we already incorporate the
	latest changes
	*/
	// Step 1: We store all the forum categories in an array $forum_categories
	$forum_categories=array();
	$forum_categories_list=get_forum_categories();

	// step 2: we find all the forums (only the visible ones if it is a student)
	$forum_list=array();
	$forum_list=get_forums();

	/*
	------------------------------------------------------------------------------------------------------
		RETRIEVING ALL GROUPS AND THOSE OF THE USER
	------------------------------------------------------------------------------------------------------
	*/
	// the groups of the user
	$groups_of_user=array();
	$groups_of_user=GroupManager::get_group_ids($_course['dbName'], $_user['user_id']);
	// all groups in the course (and sorting them as the id of the group = the key of the array
	if (!api_is_anonymous()) {
		$all_groups=GroupManager::get_group_list();
		if(is_array($all_groups)) {
			foreach ($all_groups as $group) {
				$all_groups[$group['id']]=$group;
			}
		}
	}

	/*
	------------------------------------------------------------------------------------------------------
		CLEAN GROUP ID FOR AJAXFILEMANAGER
	------------------------------------------------------------------------------------------------------
	*/
	if(isset($_SESSION['_gid']))
	{
		unset($_SESSION['_gid']);
	}

	/*
	------------------------------------------------------------------------------------------------------
		Display Forum Categories and the Forums in it
	------------------------------------------------------------------------------------------------------
	*/
    if (api_is_allowed_to_edit ()) {
	echo '<script type="text/javascript">
		$(document).ready(function(){ 	
			var category_before = "";
			var category_after = "";

			// make the categories sortable	
			$("#categories li div div ul").sortable({
				connectWith: "#categories li div div ul",
                handle: $(".move"),
                cancel: ".nodrag",
                start: function(event, ui) {
					parentElement = ui.item.parent();
					category_before = getCategoryId(parentElement);
				},
				receive: function(event, ui) {
					parentElement = ui.item.parent();
					//Check if the parent ul has a category
					//We will compare the id of the parent category and the category id after moved
					category_after = getCategoryId(parentElement);
					itemId = getItemId(ui.item);

					var order = $(this).sortable("serialize") + "&action=updateRecordsListings";
					var record = order.split("&");
					var recordlen = record.length;
					var disparr = new Array();
					for(var i=0;i<(recordlen-1);i++){
						var recordval = record[i].split("=");
						disparr[i] = recordval[1];
					}
					//if the category has changed we store it in the database
					if(category_before != category_after){
						$.ajax({
							url: "index.php?action=changeForumCategory&itemId="+itemId+"&categoryId="+category_after,
							success: function(){
								//we update the order of all links of the new category
								window.location.href = "index.php?action=updateRecordsListings&disporder="+disparr;
								return;
						      }
				      		});
                      return false;
					}
				},
				stop: function(event, ui) {
                    var order = $(this).sortable("serialize") + "&action=updateRecordsListings";
					var record = order.split("&");
					var recordlen = record.length;
					var disparr = new Array();
					for(var i=0;i<(recordlen-1);i++){
						var recordval = record[i].split("=");
						disparr[i] = recordval[1];
					}
					//Update the order in the category
					window.location.href = "index.php?action=updateRecordsListings&disporder="+disparr;
					return;

				}
			});


	//Allow th change the categories order
	$("#categories").sortable({
        connectWith: "#categories",
        cancel: ".nodrag",
        handle: $(".move1"),
        update: function(event, ui) {
        var order = $(this).sortable("serialize") + "&action=updateRecordsListings"; 			
		var record = order.split("&");
		var recordlen = record.length;			
		var disparr = new Array();
		for(var i=0;i<(recordlen-1);i++) {				
			var recordval = record[i].split("=");
			disparr[i] = recordval[1];
		}
		//update the order of all categories
		window.location.href = "index.php?action=updateRecordsListings&type=categories&disporder="+disparr;
	}
	});

});

function getCategoryId(parentElement){
	if(parentElement.is("[class*=\'category\']")){
		var classList =$(parentElement).attr("class").split(/\s+/);
		var category_id;
		for(var i= 0; i < classList.length; i++){
			var index = classList[i].indexOf("category_");
		    if (index != -1) {
		    	return classList[i].substr(9,classList[i].length);
		    }
		}
		return;
	}
}
function getItemId(item){
	var arrayTemp = $(item).attr("id").split("_");
	return arrayTemp[1];
}

</script>';
    }

// Drag&drop settings
$parent_draggable = "parent_no_draggable";
$draggable = "";
if (api_is_allowed_to_edit(null, true)) {
 $parent_draggable = "parent_draggable ";
 $draggable = " draggable";
}
$action = $_GET['action']; 
$disporder = $_GET['disporder'];

if($action == "changeForumCategory"){
	$itemId = Security::remove_XSS($_GET["itemId"]);
	$categoryId = Security::remove_XSS($_GET["categoryId"]);
	$displayorder = $_GET["disporder"];
	$sql = "UPDATE $table_forums SET forum_category=".$categoryId." WHERE forum_id = ".Database::escape_string($itemId);
	$res = Database::query($sql,__FILE__,__LINE__);
	exit;
}

if ($action == "updateRecordsListings"){
	$disparr = explode(",",$disporder);
	if(isset($_GET['type']) && $_GET['type']=='categories'){
		$table_name = $table_categories;
		$table_field = "cat_order";
		$table_id = "cat_id";
	}
	else{
		$table_name = $table_forums;
		$table_field = "forum_order";
		$table_id = "forum_id";
	}
	$len = sizeof($disparr);
	$listingCounter = 1;
	for($i=0;$i<sizeof($disparr);$i++)
	{	
		$sql = "UPDATE $table_name SET $table_field=".$listingCounter." WHERE $table_id = ".$disparr[$i];
		$res = Database::query($sql,__FILE__,__LINE__);
		$listingCounter = $listingCounter + 1;	
	}
	echo '<script>window.location.href = "index.php?'.api_get_cidReq().'";</script>';
}

	echo '<div id="contentWrap"><div id="contentLeft"><ul class="dragdrop" id="categories">';
	// Step 3: we display the forum_categories first
	if(is_array($forum_categories_list)) {
		foreach ($forum_categories_list as $forum_category_key => $forum_category) {
			
			
			//validacion when belongs to a session
			$session_img = api_get_session_image($forum_category['session_id'], $_user['status']);
		
			if((!isset($_SESSION['id_session']) || $_SESSION['id_session']==0) && !empty($forum_category['session_name'])) {
				$session_displayed = ' ('.$forum_category['session_name'].')';
			} else {
				$session_displayed = '';
			}
                        echo '<li id="recordsArray_'.$forum_category['cat_id'].'" class="category">' .
					'<div class="'.$parent_draggable.$draggable.' rounded move1">';
                        // group forum title
                        echo '<div style="float:left">';
                        echo '<a href="viewforumcategory.php?'.api_get_cidreq().'&forumcategory='.prepare4display($forum_category['cat_id']).'" '.class_visible_invisible(prepare4display($forum_category['visibility'])).'>'.Display::return_icon('pixel.gif',$forum_category['cat_title'],array('class' => 'actionplaceholdericon actionfolder')).'&nbsp;'.prepare4display($forum_category['cat_title']).$session_displayed.'</a>'. $session_img ;
                        if ($forum_category['cat_comment']<>'' AND trim($forum_category['cat_comment'])<>'&nbsp;') {  
                            echo ' : <span class="forum_description">'.prepare4display($forum_category['cat_comment']).'</span>';
                        }
                        echo '</div>';
                        echo '<div style="float:right">';
                        // actions icons for each group forum
			if (api_is_allowed_to_edit(false,true) && !($forum_category['session_id']==0 && intval($session_id)!=0)) {
                        echo "<a class='action' href=\"".api_get_self()."?".api_get_cidreq()."&gradebook=$gradebook&action=edit&amp;content=forumcategory&amp;id=".prepare4display($forum_category['cat_id'])."\">".Display::return_icon('pixel.gif',get_lang('Edit'),array('class' => 'actionplaceholdericon actionedit'))."</a>";
                        echo '&nbsp;&nbsp;&nbsp;';
                        echo "<a class='action' href=\"".api_get_self()."?".api_get_cidreq()."&gradebook=$gradebook&action=delete&amp;content=forumcategory&amp;id=".prepare4display($forum_category['cat_id'])."\" onclick=\"javascript:if(!confirm('".addslashes(api_htmlentities(get_lang("DeleteForumCategory"),ENT_QUOTES,$charset))."')) return false;\">".Display::return_icon('pixel.gif',get_lang('Delete'),array('class' => 'actionplaceholdericon actiondelete'))."</a>";
                        echo '&nbsp;&nbsp;&nbsp;';
                        display_visible_invisible_icon('forumcategory', prepare4display($forum_category['cat_id']), prepare4display($forum_category['visibility']));
                        display_lock_unlock_icon('forumcategory',prepare4display($forum_category['cat_id']), prepare4display($forum_category['locked']));
			}
				
		$i=1;
		
    // ending directory forum div

    echo '</div>';
    echo '</div>';
		
    // style column titles only if !empty forum
	$column_titles_already_showed = false;

	//	echo '<tr><td colspan="7">';
		echo '<div id="contentWrap"><div id="contentLeft"><ul style="" class="dragdrop category_'.$forum_category['cat_id'].'">';
		$state = false;
                foreach ($forum_list as $key=>$forum) {
                    
				// Here we clean the whatnew_post_info array a little bit because to display the icon we
				// test if $whatsnew_post_info[$forum['forum_id']] is empty or not.
				if (!empty($whatsnew_post_info)) {
					if (is_array(isset($whatsnew_post_info[$forum['forum_id']])?$whatsnew_post_info[$forum['forum_id']]:null)) {
						foreach ($whatsnew_post_info[$forum['forum_id']] as $key_thread_id => $new_post_array) {
							if (empty($whatsnew_post_info[$forum['forum_id']][$key_thread_id]))	{
								unset($whatsnew_post_info[$forum['forum_id']][$key_thread_id]);
								unset($_SESSION['whatsnew_post_info'][$forum['forum_id']][$key_thread_id]);
							}
						}
					}
				}
			// note: this can be speeded up if we transform the $forum_list to an array that uses the forum_category as the key.
				if (prepare4display($forum['forum_category'])==prepare4display($forum_category['cat_id'])) {
					
                                        // the forum has to be showed if
					// 1.v it is a not a group forum (teacher and student)
					// 2.v it is a group forum and it is public (teacher and student)
					// 3. it is a group forum and it is private (always for teachers only if the user is member of the forum
					// if the forum is private and it is a group forum and the user is not a member of the group forum then it cannot be displayed
					//if (!($forum['forum_group_public_private']=='private' AND !is_null($forum['forum_of_group']) AND !in_array($forum['forum_of_group'], $groups_of_user)))
					//{
					$show_forum=false;

					// SHOULD WE SHOW THIS PARTICULAR FORUM
					// you are teacher => show forum

					if (api_is_allowed_to_edit(false,true)) {
						//echo 'teacher';
						$show_forum=true;
					} else {// you are not a teacher
						//echo 'student';
						// it is not a group forum => show forum (invisible forums are already left out see get_forums function)
						if ($forum['forum_of_group']=='0') {
							//echo '-gewoon forum';
							$show_forum=true;
						} else {
							// it is a group forum
							//echo '-groepsforum';
							// it is a group forum but it is public => show
							if ($forum['forum_group_public_private']=='public') {
								$show_forum=true;
								//echo '-publiek';
							} else if ($forum['forum_group_public_private']=='private') {
								// it is a group forum and it is private
								//echo '-prive';
								// it is a group forum and it is private but the user is member of the group
								if (in_array($forum['forum_of_group'],$groups_of_user)) {
									//echo '-is lid';
									$show_forum=true;
								} else {
									//echo '-is GEEN lid';
									$show_forum=false;
								}
							} else {
								$show_forum=false;
							}

						}
					}
				
				if($show_forum && !$column_titles_already_showed){
					echo show_columns_titles();
					$column_titles_already_showed = true;					
				}
				$state = true;
				echo '<li id="recordsArray_'.$forum['forum_id'].'" class="move '.$draggable.'" ><table class="data_table" width="100%">';
				
				if($i%2==0) $css_class = 'row_odd';
				else $css_class = 'row_even';
				$i++;


				if ($show_forum) {
					$form_count++;
					$mywhatsnew_post_info=isset($whatsnew_post_info[$forum['forum_id']]) ? $whatsnew_post_info[$forum['forum_id']]: null;				
                    echo "\t<tr class='".$css_class."'>\n";
				if(api_is_allowed_to_edit ()) echo "<td width='5%'>".Display::return_icon('pixel.gif',get_lang('Drag'), array('class' => 'actionplaceholdericon actionsdraganddrop'))."</td>";
				// Showing the image
						if(!empty($forum['forum_image'])) {

							$image_path = api_get_path(WEB_COURSE_PATH).api_get_course_path().'/upload/forum/images/'.$forum['forum_image'];
							$image_size = api_getimagesize($image_path);

							$img_attributes = '';
							if (!empty($image_size)) {
								if ($image_size[0] > 100 || $image_size[1] > 100) {
									//limit display width and height to 100px
									$img_attributes = 'width="100" height="100"';
								}
								echo "<img src=\"$image_path\" $img_attributes>";
							}
						}
				echo "\t\t<td class='nodrag' width='30%' colspan=\"2\" style=\"text-align:left;\">";
				if ($forum['forum_of_group']!=='0') {
							if (is_array($mywhatsnew_post_info) and !empty($mywhatsnew_post_info)) {
								echo Display::return_icon('pixel.gif',get_lang('GroupForum'), array('class' => 'actionplaceholdericon actionsmembers'));
							} else {
								echo Display::return_icon('pixel.gif',get_lang('GroupForum'), array('class' => 'actionplaceholdericon actionsmembers'));
							}
						} else {
							   echo Display::return_icon('pixel.gif',get_lang('Forum'), array('class' => 'actionplaceholdericon actionforum'));

						}			
				}
				//validacion when belongs to a session
						$session_img = api_get_session_image($forum['session_id'], $_user['status']);
						
						if ($forum['forum_of_group'] <> '0') {
							$my_all_groups_forum_name = isset($all_groups[$forum['forum_of_group']]['name']) ? $all_groups[$forum['forum_of_group']]['name'] : null;
							$my_all_groups_forum_id = isset($all_groups[$forum['forum_of_group']]['id']) ? $all_groups[$forum['forum_of_group']]['id'] : null;
							$group_title = api_substr($my_all_groups_forum_name, 0, 30);
							$forum_title_group_addition = ' (<a href="../group/group_space.php?'.api_get_cidreq().'&gidReq='.$forum['forum_of_group'].'" class="forum_group_link">'.get_lang('GoTo').' '.$group_title.'</a>)' . $session_img;
						} else {
							$forum_title_group_addition = '';
						}

						if((!isset($_SESSION['id_session']) || $_SESSION['id_session']==0) && !empty($forum['session_name'])) {
							$session_displayed = ' ('.$forum['session_name'].')';
						} else {
							$session_displayed = '';
						}
						$forum['forum_of_group']==0?$groupid='':$groupid=$forum['forum_of_group'];
						echo "<a href=\"viewforum.php?".api_get_cidreq()."&gidReq=".Security::remove_XSS($groupid)."&forum=".prepare4display($forum['forum_id'])."\" ".class_visible_invisible(prepare4display($forum['visibility'])).">".prepare4display($forum['forum_title']).$session_displayed.'</a>'.$forum_title_group_addition.'<br />'.prepare4display($forum['forum_comment'])."</td>\n";
                        $number_threads=isset($forum['number_of_threads']) ? $forum['number_of_threads'] : null;
                        $number_posts  =isset($forum['number_of_posts']) ? $forum['number_of_posts'] : null;
                        echo "\t\t<td class='nodrag' width='8%'>".$number_threads."</td>\n";
                        echo "\t\t<td class='nodrag' width='8%'>".$number_posts."</td>\n";
                        // the last post in the forum
                        if ($forum['last_poster_name']<>'') {
                            $name=$forum['last_poster_name'];
                            $poster_id=0;
                        } else {
                            $name = api_get_person_name($forum['last_poster_firstname'], $forum['last_poster_lastname']);
                            $poster_id=$forum['last_poster_id'];
                        }
                        echo "\t\t<td class='nodrag' width='30%'>";
                        if (!empty($forum['last_post_id'])) {
							echo $forum['last_post_date']."<br /> ".get_lang('By').' '.display_user_link($poster_id, $name);
						}
                        echo "</td>\n";
                        echo "\t\t<td class='nodrag' width='26%' align='right'>";
                        if (api_is_allowed_to_edit(false,true) && !($forum['session_id']==0 && intval($session_id)!=0)) {
							echo "<a class='action' href=\"".api_get_self()."?".api_get_cidreq()."&gradebook=$gradebook&action=edit&amp;content=forum&amp;id=".$forum['forum_id']."\">".Display::return_icon('pixel.gif',get_lang('Edit'), array('class' => 'actionplaceholdericon actionedit'))."</a>";
							echo "<a class='action' href=\"".api_get_self()."?".api_get_cidreq()."&gradebook=$gradebook&action=delete&amp;content=forum&amp;id=".$forum['forum_id']."\" onclick=\"javascript:if(!confirm('".addslashes(api_htmlentities(get_lang("DeleteForum"),ENT_QUOTES,$charset))."')) return false;\">".Display::return_icon('pixel.gif',get_lang('Delete'), array('class' => 'actionplaceholdericon actiondelete'))."</a>";
							display_visible_invisible_icon('forum',$forum['forum_id'], $forum['visibility']);
							display_lock_unlock_icon('forum',$forum['forum_id'], $forum['locked']);
						//	display_up_down_icon('forum',$forum['forum_id'], $forums_in_category);
						}
						$iconnotify = 'actionplaceholdericon actionsmessage';
						$session_forum_noti=isset($_SESSION['forum_notification']['forum']) ? $_SESSION['forum_notification']['forum'] : false;
						if (is_array($session_forum_noti)) {
							if (in_array($forum['forum_id'],$session_forum_noti)) {
								$iconnotify = 'actionplaceholdericon actionsmessagereply';
							}
						}

						if (!api_is_anonymous()  && api_is_allowed_to_session_edit(false,true) ) {		
							echo "<a class='action' href=\"".api_get_self()."?".api_get_cidreq()."&gradebook=$gradebook&action=notify&amp;content=forum&amp;id=".$forum['forum_id']."\">".Display::return_icon('pixel.gif',get_lang('NotifyMe'), array('class' => $iconnotify))."</a>";
						}
				echo "</td>\n";
				echo "\t</tr>\n";					
				echo '</table></li>';
				}// if allowed to edit        
		} //foreach loop on each forum
                
        if($state == false){
            echo get_lang('ThereAreNoForumsInThisCategory');
        }
		echo '<br/><br/></ul></div></div>';
	//	echo '</td></tr>';
		echo '</li>'; //this li is for forum category
	}		
	}
		echo '</ul></div></div>'; 

// ending div#content
echo '</div>';

// bottom actions toolbars
echo '<div class="actions">';
echo '</div>';

//Display :: display_footer();
Display::display_tool_footer();
?>