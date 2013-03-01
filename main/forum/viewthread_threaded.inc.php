<?php
/*
==============================================================================
	Dokeos - elearning and course management software

	Copyright (c) 2006-2008 Dokeos SPRL
	Copyright (c) 2006 Ghent University (UGent)

	For a full list of contributors, see "credits.txt".
	The full license can be read in "license.txt".

	This program is free software; you can redistribute it and/or
	modify it under the terms of the GNU General Public License
	as published by the Free Software Foundation; either version 2
	of the License, or (at your option) any later version.

	See the GNU General Public License for more details.

	Contact address: Dokeos, 108 rue du Corbeau, B-1030 Brussels, Belgium
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

/**
 **************************************************************************
 *						IMPORTANT NOTICE
 * Please do not change anything is this code yet because there are still
 * some significant code that need to happen and I do not have the time to
 * merge files and test it all over again. So for the moment, please do not
 * touch the code
 * 							-- Patrick Cool <patrick.cool@UGent.be>
 **************************************************************************
 */

$rows=get_posts($_GET['thread']); // note: this has to be cleaned first
$rows=calculate_children($rows);

if ($_GET['post'])
{
	$display_post_id=Security::remove_XSS($_GET['post']); // note: this has to be cleaned first
}
else
{
	// we need to display the first post
	reset($rows);
	$current=current($rows);
	$display_post_id=$current['post_id'];
}

//are we in a lp ?
$origin = '';
if(isset($_GET['origin']))
{
    $origin =  Security::remove_XSS($_GET['origin']);
}

//delete attachment file
if ((isset($_GET['action']) && $_GET['action']=='delete_attach') && isset($_GET['id_attach'])) {
	delete_attachment(0,$_GET['id_attach']);
}
// --------------------------------------
// 		Displaying the thread (structure)
// --------------------------------------
$thread_structure="<div class=\"structure\">".get_lang('Structure')."</div>";
$counter=0;
$count=0;
$prev_next_array=array();
foreach ($rows as $post)
{
	$counter++;
	$indent=$post['indent_cnt']*'20';
	$thread_structure.= "<div style=\"margin-left: ".$indent."px;\">";

	if (isset($whatsnew_post_info[$current_forum['forum_id']][$current_thread['thread_id']][$post['post_id']]) and !empty($whatsnew_post_info[$current_forum['forum_id']][$current_thread['thread_id']][$post['post_id']]) and !empty($whatsnew_post_info[$_GET['forum']][$post['thread_id']]))
	{
		$post_image=icon('../img/forumpostnew.gif');
	}
	else
	{
		$post_image=icon('../img/forumpost.gif');
	}
	$thread_structure.= $post_image;
	if ($_GET['post']==$post['post_id'] OR ($counter==1 AND !isset($_GET['post'])))
	{
		$thread_structure.='<strong>'.prepare4display($post['post_title']).'</strong></div>';
		$prev_next_array[]=$post['post_id'];
	}
	else
	{
		if ($post['visible']=='0')
		{
			$class=' class="invisible"';
		}
		else
		{
			$class='';
		}
		$count_loop=($count==0)?'&id=1' : '';
		$thread_structure.= "<a href=\"viewthread.php?".api_get_cidreq()."&forum=".Security::remove_XSS($_GET['forum'])."&amp;thread=".Security::remove_XSS($_GET['thread'])."&amp;post=".$post['post_id']."&amp;origin=$origin$count_loop\" $class>".prepare4display($post['post_title'])."</a></div>\n";
		$prev_next_array[]=$post['post_id'];
	}
	$count++;
}

/*--------------------------------------------------
				 NAVIGATION CONTROLS
----------------------------------------------------
*/

$current_id=array_search($display_post_id,$prev_next_array);
$max=count($prev_next_array);
$next_id=$current_id+1;
$prev_id=$current_id-1;

// text
$first_message=get_lang('FirstMessage');
$last_message=get_lang('LastMessage');
$next_message=get_lang('NextMessage');
$prev_message=get_lang('PrevMessage');

// images
$first_img 	= Display::return_icon('first.png',get_lang('FirstMessage'),array('style'=>'vertical-align: middle;'));
$last_img 	= Display::return_icon('last.png',get_lang('LastMessage'),array('style'=>'vertical-align: middle;'));
$prev_img 	= Display::return_icon('prev.png',get_lang('PrevMessage'),array('style'=>'vertical-align: middle;'));
$next_img 	= Display::return_icon('next.png',get_lang('NextMessage'),array('style'=>'vertical-align: middle;'));

// links
$first_href = 'viewthread.php?'.api_get_cidreq().'&amp;forum='.Security::remove_XSS($_GET['forum']).'&amp;thread='.Security::remove_XSS($_GET['thread']).'&amp;gradebook='.$gradebook.'&amp;origin='.$origin.'&amp;id=1&amp;post='.$prev_next_array[0];
$last_href 	= 'viewthread.php?'.api_get_cidreq()."&amp;forum=".Security::remove_XSS($_GET['forum'])."&amp;thread=".Security::remove_XSS($_GET['thread'])."&amp;gradebook='.$gradebook.'&amp;origin=".$origin."&amp;post=".$prev_next_array[$max-1];
$prev_href	= 'viewthread.php?'.api_get_cidreq().'&amp;forum='.Security::remove_XSS($_GET['forum']).'&amp;thread='.Security::remove_XSS($_GET['thread']).'&amp;gradebook='.$gradebook.'&amp;origin='.$origin.'&amp;post='.$prev_next_array[$prev_id];
$next_href	= 'viewthread.php?'.api_get_cidreq().'&amp;forum='.Security::remove_XSS($_GET['forum']).'&amp;thread='.Security::remove_XSS($_GET['thread']).'&amp;gradebook='.$gradebook.'&amp;origin='.$origin.'&amp;post='.$prev_next_array[$next_id];

echo '<center>';
//go to: first and previous
if ((int)$current_id > 0)
{
	echo '<a href="'.$first_href.'" '.$class.' title='.$first_message.'>'.$first_img.' '.$first_message.'</a>';
	echo '<a href="'.$prev_href.'" '.$class_prev.' title='.$prev_message.'>'.$prev_img.' '.$prev_message.'</a>';
}
else
{
	echo '<b><span class="invisible">'.$first_img.' '.$first_message.'</b></span>';
	echo '<b><span class="invisible">'.$prev_img.' '.$prev_message.'</b></span>';
}

//  current counter
echo  ' [ '.($current_id+1).' / '.$max.' ] ';

// go to: next and last
if (($current_id+1) < $max)
{
	echo '<a href="'.$next_href.'" '.$class_next.' title='.$next_message.'>'.$next_message.' '.$next_img.'</a>';
	echo '<a href="'.$last_href.'" '.$class.' title='.$last_message.'>'.$last_message.' '.$last_img.'</a>';
}
else
{
	echo '<b><span class="invisible">'.$next_message.' '.$next_img.'</b></span>';
	echo '<b><span class="invisible">'.$last_message.' '.$last_img.'</b></span>';
}
echo '</center>';

//--------------------------------------------------------------------------------------------

// the style depends on the status of the message: approved or not
if ($rows[$display_post_id]['visible']=='0')
{
	$titleclass='forum_message_post_title_2_be_approved';
	$messageclass='forum_message_post_text_2_be_approved';
	$leftclass='forum_message_left_2_be_approved';
}
else
{
	$titleclass='forum_message_post_title';
	$messageclass='forum_message_post_text';
	$leftclass='forum_message_left';
}

// --------------------------------------
// 		Displaying the message
// --------------------------------------

// we mark the image we are displaying as set
unset($whatsnew_post_info[$current_forum['forum_id']][$current_thread['thread_id']][$rows[$display_post_id]['post_id']]);

echo "<table width=\"100%\"  class=\"post\"  cellspacing=\"5\" border=\"0\">\n";
echo "\t<tr>\n";
echo "\t\t<td rowspan=\"3\" class=\"$leftclass\">";
if ($rows[$display_post_id]['user_id']=='0' OR empty($rows[$display_post_id]['user_id']))
{
	$name=prepare4display($rows[$display_post_id]['poster_name']);
}
else
{
	$name=api_get_person_name($rows[$display_post_id]['firstname'], $rows[$display_post_id]['lastname']);
}
if (api_get_course_setting('allow_user_image_forum')) {echo '<br />'.display_user_image($rows[$display_post_id]['user_id'],$name, $origin).'<br />';	}
echo display_user_link($rows[$display_post_id]['user_id'], $name, $origin).'<br />';
echo $rows[$display_post_id]['post_date'].'<br /><br />';
// get attach id
$attachment_list=get_attachment($display_post_id);
$id_attach = !empty($attachment_list)?$attachment_list['id']:'';

// The user who posted it can edit his thread only if the course admin allowed this in the properties of the forum
// The course admin him/herself can do this off course always
if (($current_forum['allow_edit']==1 AND $rows[$display_post_id]['user_id']==$_user['user_id'] AND !api_is_anonymous()) or (api_is_allowed_to_edit(false,true) && !(api_is_course_coach() && $current_forum['session_id']!=$_SESSION['id_session'])))
{
	echo "<a href=\"editpost.php?".api_get_cidreq()."&forum=".Security::remove_XSS($_GET['forum'])."&amp;thread=".Security::remove_XSS($_GET['thread'])."&amp;origin=".$origin."&amp;post=".$rows[$display_post_id]['post_id']."&id_attach=".$id_attach."\">".Display::return_icon('pixel.gif',get_lang('Edit'),array('class'=>'actionplaceholdericon actionedit'))."</a>\n";
}
if (api_is_allowed_to_edit(false,true)  && !(api_is_course_coach() && $current_forum['session_id']!=$_SESSION['id_session']))
{
	echo "<a href=\"".api_get_self()."?".api_get_cidreq()."&forum=".Security::remove_XSS($_GET['forum'])."&amp;thread=".Security::remove_XSS($_GET['thread'])."&amp;action=delete&amp;content=post&amp;id=".$rows[$display_post_id]['post_id']."\" onclick=\"javascript:if(!confirm('".addslashes(api_htmlentities(get_lang("DeletePost"),ENT_QUOTES,$charset))."')) return false;\">".Display::return_icon('pixel.gif',get_lang('Delete'),array('class'=>'actionplaceholdericon actiondelete'))."</a>\n";
	display_visible_invisible_icon('post', $rows[$display_post_id]['post_id'], $rows[$display_post_id]['visible'],array('forum'=>Security::remove_XSS($_GET['forum']),'thread'=>Security::remove_XSS($_GET['thread']), 'post'=>Security::remove_XSS($_GET['post']) ));
	echo "\n";
	//verified the post minor
	$my_post=get_posts($_GET['thread']);
	$id_posts=array();

	foreach ($my_post as $post_value) {
		$id_posts[]=$post_value['post_id'];
	}

	sort($id_posts,SORT_NUMERIC);
	reset($id_posts);
	//the post minor
	$post_minor=(int)$id_posts[0];
	$post_id = isset($_GET['post'])?(int)$_GET['post']:0;
	if (!isset($_GET['id']) && $post_id>$post_minor) {
		echo "<a href=\"viewthread.php?".api_get_cidreq()."&forum=".Security::remove_XSS($_GET['forum'])."&amp;thread=".Security::remove_XSS($_GET['thread'])."&amp;origin=".$origin."&amp;action=move&amp;post=".$rows[$display_post_id]['post_id']."\">".icon('../img/go-next.png',get_lang('MovePost'))."</a>\n";
	}
}
$userinf=api_get_user_info($rows[$display_post_id]['user_id']);
$user_status=api_get_status_of_user_in_course($rows[$display_post_id]['user_id'],api_get_course_id());
if (api_is_allowed_to_edit(null,true)) {
	if($post_id>$post_minor )
	{
		if($user_status!=1)
		{
			$current_qualify_thread=show_qualify('1',$_GET['cidReq'],$_GET['forum'],$rows[$display_post_id]['user_id'],$_GET['thread']);
			echo "<a href=\"forumqualify.php?".api_get_cidreq()."&forum=".Security::remove_XSS($_GET['forum'])."&amp;thread=".Security::remove_XSS($_GET['thread'])."&amp;action=list&amp;post=".$rows[$display_post_id]['post_id']."&amp;user=".$rows[$display_post_id]['user_id']."&user_id=".$rows[$display_post_id]['user_id']."&origin=".$origin."&idtextqualify=".$current_qualify_thread."\" >".icon('../img/checkok.png',get_lang('Qualify'))."</a>\n";
		}
	}
}
//echo '<br /><br />';
//if (($current_forum_category['locked']==0 AND $current_forum['locked']==0 AND $current_thread['locked']==0) OR api_is_allowed_to_edit())
if ($current_forum_category['locked']==0 AND $current_forum['locked']==0 AND $current_thread['locked']==0 OR api_is_allowed_to_edit(false,true))
{
	if ($_user['user_id'] OR ($current_forum['allow_anonymous']==1 AND !$_user['user_id']) OR  api_is_allowed_to_session_edit(false,true))
	{
		if (!api_is_anonymous() && api_is_allowed_to_session_edit(false,true)) {
			echo '<a href="reply.php?'.api_get_cidreq().'&forum='.Security::remove_XSS($_GET['forum']).'&amp;thread='.Security::remove_XSS($_GET['thread']).'&amp;post='.$rows[$display_post_id]['post_id'].'&amp;action=replymessage&amp;origin='. $origin .'">'.Display :: return_icon('message_reply_forum.png', get_lang('ReplyToMessage'))."</a>\n";
			echo '<a href="reply.php?'.api_get_cidreq().'&forum='.Security::remove_XSS($_GET['forum']).'&amp;thread='.Security::remove_XSS($_GET['thread']).'&amp;post='.$rows[$display_post_id]['post_id'].'&amp;action=quote&amp;origin='. $origin .'">'.Display :: return_icon('quote.gif', get_lang('QuoteMessage'))."</a>\n";
		}
	}
}
else
{
	if ($current_forum_category['locked']==1)
{
		echo get_lang('ForumcategoryLocked').'<br />';
	}
	if ($current_forum['locked']==1)
	{
		echo get_lang('ForumLocked').'<br />';
}
	if ($current_thread['locked']==1)
	{
		echo get_lang('ThreadLocked').'<br />';
}
}
echo "</td>\n";
// note: this can be removed here because it will be displayed in the tree
if (isset($whatsnew_post_info[$current_forum['forum_id']][$current_thread['thread_id']][$rows[$display_post_id]['post_id']]) and !empty($whatsnew_post_info[$current_forum['forum_id']][$current_thread['thread_id']][$rows[$display_post_id]['post_id']]) and !empty($whatsnew_post_info[$_GET['forum']][$rows[$display_post_id]['thread_id']]))
{
	$post_image=icon('../img/forumpostnew.gif');
}
else
{
	$post_image=icon('../img/forumpost.gif');
}
if ($rows[$display_post_id]['post_notification']=='1' AND $rows[$display_post_id]['poster_id']==$_user['user_id'])
{
	$post_image.=icon('../img/forumnotification.gif',get_lang('YouWillBeNotified'));
}
// The post title
echo "\t\t<td class=\"$titleclass\">".prepare4display($rows[$display_post_id]['post_title'])."</td>\n";
echo "\t</tr>\n";

// The post message
echo "\t<tr>\n";
echo "\t\t<td class=\"$messageclass\">".prepare4display($rows[$display_post_id]['post_text'])."</td>\n";
echo "\t</tr>\n";

// The check if there is an attachment
$attachment_list=get_attachment($display_post_id);

if (!empty($attachment_list))
{
	echo '<tr><td height="50%">';
	$realname=$attachment_list['path'];
	$user_filename=$attachment_list['filename'];

	echo Display::return_icon('attachment.gif',get_lang('Attachment'));
	echo '<a href="download.php?file=';
	echo $realname;
	echo ' "> '.$user_filename.' </a>';
	echo '<span class="forum_attach_comment" >'.$attachment_list['comment'].'</span>';
	if (($current_forum['allow_edit']==1 AND $rows[$display_post_id]['user_id']==$_user['user_id']) or (api_is_allowed_to_edit(false,true)  && !(api_is_course_coach() && $current_forum['session_id']!=$_SESSION['id_session'])))	{
		echo '&nbsp;&nbsp;<a href="'.api_get_self().'?'.api_get_cidreq().'&amp;origin='.Security::remove_XSS($_GET['origin']).'&amp;action=delete_attach&amp;id_attach='.$attachment_list['id'].'&amp;forum='.Security::remove_XSS($_GET['forum']).'&amp;thread='.Security::remove_XSS($_GET['thread']).'" onclick="javascript:if(!confirm(\''.addslashes(api_htmlentities(get_lang("ConfirmYourChoice"),ENT_QUOTES,$charset)).'\')) return false;">'.Display::return_icon('pixel.gif',get_lang('Delete'),array('class'=>'actionplaceholdericon actiondelete')).'</a><br />';
	}
	echo '</td></tr>';
}



// The post has been displayed => it can be removed from the what's new array
unset($whatsnew_post_info[$current_forum['forum_id']][$current_thread['thread_id']][$row['post_id']]);
unset($_SESSION['whatsnew_post_info'][$current_forum['forum_id']][$current_thread['thread_id']][$row['post_id']]);
echo "</table>";

// --------------------------------------
// 		Displaying the thread (structure)
// --------------------------------------

echo $thread_structure;


/**
* This function builds an array of all the posts in a given thread where the key of the array is the post_id
* It also adds an element children to the array which itself is an array that contains all the id's of the first-level children
* @return an array containing all the information on the posts of a thread
* @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
*/
function calculate_children($rows)
{
	foreach($rows as $row)
	{
		$rows_with_children[$row["post_id"]]=$row;
		$rows_with_children[$row["post_parent_id"]]["children"][]=$row["post_id"];
	}
	$rows=$rows_with_children;
	$sorted_rows=array(0=>array());
	_phorum_recursive_sort($rows, $sorted_rows);
	unset($sorted_rows[0]);
	return $sorted_rows;
}

function _phorum_recursive_sort($rows, &$threads, $seed=0, $indent=0)
{
	if($seed>0)
	{
		$threads[$rows[$seed]["post_id"]]=$rows[$seed];
		$threads[$rows[$seed]["post_id"]]["indent_cnt"]=$indent;
		$indent++;
	}

	if(isset($rows[$seed]["children"]))
	{
		foreach($rows[$seed]["children"] as $child)
		{
			_phorum_recursive_sort($rows, $threads, $child, $indent);
		}
	}
}
