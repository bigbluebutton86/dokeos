<?php
/* For licensing terms, see /dokeos_license.txt */

/*
* These are functions used in gradebook
*
* @author Stijn Konings <konings.stijn@skynet.be>, Hogeschool Ghent
* @version april 2007
*/
require_once ('gradebook_functions_users.inc.php');


/**
 * Adds a resource to the unique gradebook of a given course
 * @param   string  Course code
 * @param   int     Resource type (use constants defined in linkfactory.class.php)
 * @param   int     Resource ID in the corresponding tool
 * @param   string  Resource name to show in the gradebook
 * @param   int     Resource weight to set in the gradebook
 * @param   int     Resource max
 * @param   string  Resource description
 * @param   string  Date
 * @param   int     Visibility (0 hidden, 1 shown)
 * @param   int     Session ID (optional or 0 if not defined)
 * @return  boolean True on success, false on failure
 */
function add_resource_to_course_gradebook($course_code, $resource_type, $resource_id, $resource_name='', $weight=0, $max=0, $resource_description='', $date=null, $visible=0, $session_id = 0) {
    /* See defines in lib/be/linkfactory.class.php
    define('LINK_EXERCISE',1);
    define('LINK_DROPBOX',2);
    define('LINK_STUDENTPUBLICATION',3);
    define('LINK_LEARNPATH',4);
    define('LINK_FORUM_THREAD',5),
    define('LINK_WORK',6);
    */
    $category = 0;
    require_once (api_get_path(SYS_CODE_PATH).'gradebook/lib/be.inc.php');
    $link= LinkFactory :: create($resource_type);
    $link->set_user_id(api_get_user_id());
    $link->set_course_code($course_code);
    // TODO find the corresponding category (the first one for this course, ordered by ID)
    $t = Database::get_main_table(TABLE_MAIN_GRADEBOOK_CATEGORY);
    $sql = "SELECT * FROM $t WHERE course_code = '".Database::escape_string($course_code)."' ";
    if (!empty($session_id)) {
    	$sql .= " AND session_id = ".(int)$session_id;
    } else {
    	$sql .= " AND (session_id IS NULL OR session_id = 0) ";
    }
    $sql .= " ORDER BY id";
    $res = Database::query($sql,__FILE__,__LINE__);
    if (Database::num_rows($res)<1){
        //there is no unique category for this course+session combination,
        // => create one
        $cat= new Category();
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
        $cat->set_visible(0);
        $can_edit = api_is_allowed_to_edit(true, true);
        if ($can_edit) {
            $cat->add();
        }
        $category = $cat->get_id();
        unset ($cat);
    } else {
        $row = Database::fetch_array($res);
        $category = $row['id'];
    }
    $link->set_category_id($category);

    if ($link->needs_name_and_description()) {
    	$link->set_name($resource_name);
    } else {
    	$link->set_ref_id($resource_id);
    }
    $link->set_weight($weight);

    if ($link->needs_max()) {
    	$link->set_max($max);
    }
    if (isset($date)) {
        $link->set_date($date);
    }
    if ($link->needs_name_and_description()) {
    	$link->set_description($resource_description);
    }

    $link->set_visible(empty ($visible) ? 0 : 1);

    if (!empty($session_id)) {
    	$link->set_session_id($session_id);
    }
    $link->add();
    return true;
}

function block_students() {
	if (!api_is_allowed_to_create_course()) {
		require_once (api_get_path(INCLUDE_PATH)."header.inc.php");
		api_not_allowed();
	}
}

/**
 * Returns the info header for the user result page
 * @param $userid
 */

/**
 * Returns the course name from a given code
 * @param string $code
 */
function get_course_name_from_code($code) {
	$tbl_main_categories= Database :: get_main_table(TABLE_MAIN_COURSE);
	$sql= 'SELECT title,code FROM ' . $tbl_main_categories . 'WHERE code = "' . $code . '"';
	$result= Database::query($sql,__FILE__,__LINE__);
	if ($col= Database::fetch_array($result)) {
		return $col['title'];
	}
}
/**
 * Builds an img tag for a gradebook item
 * @param string $type value returned by a gradebookitem's get_icon_name()
 */
function build_type_icon_tag($kind) {
	return '<img src="' . get_icon_file_name ($kind) . '" border="0" hspace="5" align="middle" alt="" />';
}


/**
 * Returns the icon filename for a gradebook item
 * @param string $type value returned by a gradebookitem's get_icon_name()
 */
function get_icon_file_name ($type) {
	if ($type == 'cat') {
		return api_get_path(WEB_CODE_PATH) . 'img/gradebook.gif';
	} elseif ($type == 'evalempty') {
		return api_get_path(WEB_CODE_PATH) . 'img/empty.gif';
	} elseif ($type == 'evalnotempty') {
		return api_get_path(WEB_CODE_PATH) . 'img/gradebook_eval_not_empty.gif';
	} elseif ($type == 'link') {
		return api_get_path(WEB_CODE_PATH) . 'img/link.gif';
	} else {
		return null;
	}
}



/**
 * Builds the course or platform admin icons to edit a category
 * @param object $cat category object
 * @param int $selectcat id of selected category
 */
function build_edit_icons_cat($cat, $selectcat) {

	$show_message=$cat->show_message_resource_delete($cat->get_course_code());
	if ($show_message===false) {
		$visibility_icon= ($cat->is_visible() == 0) ? 'invisible' : 'visible';
		$visibility_command= ($cat->is_visible() == 0) ? 'set_visible' : 'set_invisible';
		$modify_icons= '<a href="gradebook_edit_cat.php?editcat=' . $cat->get_id() . '&amp;cidReq='.$cat->get_course_code().'">'.Display::return_icon('pixel.gif',get_lang('Edit'),array('class'=>'actionplaceholdericon actionedit','alt'=>'','align'=>'middle','hspace'=>'20')).'</a>';
	//	$modify_icons .= '&nbsp;<a href="' . api_get_self() . '?deletecat=' . $cat->get_id() . '&amp;selectcat=' . $selectcat . '&amp;cidReq='.$cat->get_course_code().'" onclick="return confirmation();"><img src="../img/delete.png" border="0" title="' . get_lang('DeleteAll') . '" alt="" /></a>';

		//no move ability for root categories
	/*	if ($cat->is_movable()) {
			$modify_icons .= '&nbsp;<a href="' . api_get_self() . '?movecat=' . $cat->get_id() . '&amp;selectcat=' . $selectcat . ' &amp;cidReq='.$cat->get_course_code().'"><img src="../img/deplacer_fichier.gif" border="0" title="' . get_lang('Move') . '" alt="" /></a>';
		} else {
			//$modify_icons .= '&nbsp;<img src="../img/deplacer_fichier_na.gif" border="0" title="' . get_lang('Move') . '" alt="" />';
		}
		$modify_icons .= '&nbsp;<a href="' . api_get_self() . '?visiblecat=' . $cat->get_id() . '&amp;' . $visibility_command . '=&amp;selectcat=' . $selectcat . ' "><img src="../img/' . $visibility_icon . '.gif" border="0" title="' . get_lang('Visible') . '" alt="" /></a>';*/

		return $modify_icons;
	}
}

/**
 * Builds the course or platform admin icons to delete a category
 * @param object $cat category object
 * @param int $selectcat id of selected category
 */
function build_delete_icons_cat($cat, $selectcat) {

	$show_message=$cat->show_message_resource_delete($cat->get_course_code());
	if ($show_message===false) {		
		$modify_icons = '&nbsp;<a href="' . api_get_self() . '?deletecat=' . $cat->get_id() . '&amp;selectcat=' . $selectcat . '&amp;cidReq='.$cat->get_course_code().'" onclick="return confirmation();">'.Display::return_icon('pixel.gif',get_lang('DeleteAll'),array('class'=>'actionplaceholdericon actiondelete','alt'=>'','align'=>'middle','hspace'=>'20')).' </a>';		

		return $modify_icons;
	}
}

/**
 * Builds the course or platform admin icons to edit an evaluation
 * @param object $eval evaluation object
 * @param int $selectcat id of selected category
 */
function build_edit_icons_eval($eval, $selectcat) {
	$status=CourseManager::get_user_in_course_status(api_get_user_id(), api_get_course_id());
	$eval->get_course_code();
	$cat=new Category();
	$message_eval=$cat->show_message_resource_delete($eval->get_course_code());
	if ($message_eval===false) {
		$visibility_icon= ($eval->is_visible() == 0) ? 'invisible' : 'visible';
		$visibility_command= ($eval->is_visible() == 0) ? 'set_visible' : 'set_invisible';
		if ($eval->get_type() <> 'presence')
		{
			//$modify_icons= '<a href="gradebook_edit_eval.php?editeval=' . $eval->get_id() . '&amp;cidReq='.$eval->get_course_code().'">'.Display::return_icon('edit.png', get_lang('Modify')).'</a>';
			$modify_icons= '<a href="gradebook_edit_eval.php?editeval=' . $eval->get_id() . ' &amp;cidReq='.$eval->get_course_code().'">'.Display::return_icon('pixel.gif',get_lang('Edit'),array('class'=>'actionplaceholdericon actionedit','alt'=>'','align'=>'middle','hspace'=>'15')).'</a>';
		}
		else 
		{
			$modify_icons= '<a href="gradebook_edit_presence.php?editeval=' . $eval->get_id() . '&amp;cidReq='.$eval->get_course_code().'&amp;course='.Security::remove_XSS($_GET['course']).'">'.Display::return_icon('pixel.gif',get_lang('Edit'),array('class'=>'actionplaceholdericon actionedit','alt'=>'','align'=>'middle','hspace'=>'15')).'</a>';
		}
		
		
		$view = isset($_GET['view'])?'&amp;view='.Security::remove_XSS($_GET['view']):'';		
	/*	$modify_icons .= '&nbsp;<a href="' . api_get_self() . '?deleteeval=' . $eval->get_id() . '&selectcat=' . $selectcat . ' &amp;cidReq='.$eval->get_course_code().$view.'&amp;course='.Security::remove_XSS($_GET['course']).'" onclick="return confirmation();"><img src="../img/delete.png" border="0" title="' . get_lang('Delete') . '" alt="" /></a>';
		//$modify_icons .= '&nbsp;<a href="' . api_get_self() . '?moveeval=' . $eval->get_id() . '&selectcat=' . $selectcat . '"><img src="../img/deplacer_fichier.gif" border="0" title="' . get_lang('Move') . '" alt="" /></a>';
		$modify_icons .= '&nbsp;<a href="' . api_get_self() . '?visibleeval=' . $eval->get_id() . '&amp;' . $visibility_command . '=&amp;selectcat=' . $selectcat .$view. '&amp;course='.Security::remove_XSS($_GET['course']).' "><img src="../img/' . $visibility_icon . '.gif" border="0" title="' . get_lang('Visible') . '" alt="" /></a>';
		
		if ($status==1 || is_null($status)){
			$modify_icons .= '&nbsp;<a href="gradebook_showlog_eval.php?visiblelog=' . $eval->get_id() . '&amp;selectcat=' . $selectcat . ' &amp;cidReq='.$eval->get_course_code().'&amp;course='.Security::remove_XSS($_GET['course']).'"><img src="../img/file_txt_small.gif" border="0" title="' . get_lang('GradebookQualifyLog') . '" alt="" /></a>';
		}*/
		return $modify_icons;
	}
}

/**
 * Builds the course or platform admin icons to delete an evaluation
 * @param object $eval evaluation object
 * @param int $selectcat id of selected category
 */
function build_delete_icons_eval($eval, $selectcat) {
	$status=CourseManager::get_user_in_course_status(api_get_user_id(), api_get_course_id());
	$eval->get_course_code();
	$cat=new Category();
	$message_eval=$cat->show_message_resource_delete($eval->get_course_code());
	if ($message_eval===false) {		
		
		$view = isset($_GET['view'])?'&amp;view='.Security::remove_XSS($_GET['view']):'';		
		$modify_icons = '&nbsp;<a href="' . api_get_self() . '?deleteeval=' . $eval->get_id() . '&selectcat=' . $selectcat . ' &amp;cidReq='.$eval->get_course_code().$view.'&amp;course='.Security::remove_XSS($_GET['course']).'" onclick="return confirmation();">'.Display::return_icon('pixel.gif',get_lang('Delete'),array('class'=>'actionplaceholdericon actiondelete','alt'=>'','align'=>'middle','hspace'=>'15')).' </a>';
		
		return $modify_icons;
	}
}

/**
 * Builds the course or platform admin icons to edit a link
 * @param object $linkobject
 * @param int $selectcat id of selected category
 */
function build_edit_icons_link($link, $selectcat) {

	$link->get_course_code();
	$cat=new Category();
	$message_link=$cat->show_message_resource_delete($link->get_course_code());
	if ($message_link===false) {
		$visibility_icon= ($link->is_visible() == 0) ? 'invisible' : 'visible';
		$visibility_command= ($link->is_visible() == 0) ? 'set_visible' : 'set_invisible';
		$modify_icons= '<a href="gradebook_edit_link.php?editlink=' . $link->get_id() . ' &amp;cidReq='.$link->get_course_code().'">'.Display::return_icon('pixel.gif',get_lang('Edit'),array('class'=>'actionplaceholdericon actionedit','alt'=>'','align'=>'middle','hspace'=>'15')).'</a>';
	/*	$modify_icons .= '&nbsp;<a href="' . api_get_self() . '?deletelink=' . $link->get_id() . '&selectcat=' . $selectcat . ' &amp;cidReq='.$link->get_course_code().'" onclick="return confirmation();"><img src="../img/delete.png" border="0" title="' . get_lang('Delete') . '" alt="" /></a>';
		//$modify_icons .= '&nbsp;<a href="' . api_get_self() . '?movelink=' . $link->get_id() . '&selectcat=' . $selectcat . '"><img src="../img/deplacer_fichier.gif" border="0" title="' . get_lang('Move') . '" alt="" /></a>';
		$modify_icons .= '&nbsp;<a href="' . api_get_self() . '?visiblelink=' . $link->get_id() . '&amp;' . $visibility_command . '=&amp;selectcat=' . $selectcat . ' "><img src="../img/' . $visibility_icon . '.gif" border="0" title="' . get_lang('Visible') . '" alt="" /></a>';
		$modify_icons .= '&nbsp;<a href="gradebook_showlog_link.php?visiblelink=' . $link->get_id() . '&amp;selectcat=' . $selectcat . '&amp;cidReq='.$link->get_course_code().'"><img src="../img/file_txt_small.gif" border="0" title="' . get_lang('GradebookQualifyLog') . '" alt="" /></a>';
		//if (api_is_course_admin() == true) {
			//$modify_icons .= '&nbsp;<a href="gradebook_showlog_eval.php?visiblelog=' . $eval->get_id() . '&amp;' . $visibility_command . '=&amp;selectcat=' . $selectcat . '"><img src="../img/file_txt_small.gif" border="0" title="' . get_lang('GradebookQualifyLog') . '" alt="" /></a>';
		//}*/
		return $modify_icons;
	}
}

/**
 * Builds the course or platform admin icons to delete a link
 * @param object $linkobject
 * @param int $selectcat id of selected category
 */
function build_delete_icons_link($link, $selectcat) {

	$link->get_course_code();
	$cat=new Category();
	$message_link=$cat->show_message_resource_delete($link->get_course_code());
	if ($message_link===false) {		
		$modify_icons = '&nbsp;<a href="' . api_get_self() . '?deletelink=' . $link->get_id() . '&selectcat=' . $selectcat . ' &amp;cidReq='.$link->get_course_code().'" onclick="return confirmation();">'.Display::return_icon('pixel.gif',get_lang('Delete'),array('class'=>'actionplaceholdericon actiondelete','alt'=>'','align'=>'middle','hspace'=>'15')).' </a>';
		
		return $modify_icons;
	}
}

/**
 * Checks if a resource is in the unique gradebook of a given course
 * @param    string  Course code
 * @param    int     Resource type (use constants defined in linkfactory.class.php)
 * @param    int     Resource ID in the corresponding tool
 * @param    int     Session ID (optional -  0 if not defined)
 * @return   int     false on error or link ID
 */
function is_resource_in_course_gradebook($course_code, $resource_type, $resource_id, $session_id = 0) {
    /* See defines in lib/be/linkfactory.class.php
    define('LINK_EXERCISE',1);
    define('LINK_DROPBOX',2);
    define('LINK_STUDENTPUBLICATION',3);
    define('LINK_LEARNPATH',4);
    define('LINK_FORUM_THREAD',5),
    define('LINK_WORK',6);
    */
    require_once(api_get_path(SYS_CODE_PATH).'gradebook/lib/be/linkfactory.class.php');
    require_once (api_get_path(SYS_CODE_PATH).'gradebook/lib/be.inc.php');
	require_once(api_get_path(SYS_CODE_PATH).'gradebook/lib/be/linkfactory.class.php');
    // TODO find the corresponding category (the first one for this course, ordered by ID)
    $t = Database::get_main_table(TABLE_MAIN_GRADEBOOK_CATEGORY);
    $l = Database::get_main_table(TABLE_MAIN_GRADEBOOK_LINK);
    $sql = "SELECT * FROM $t WHERE course_code = '".Database::escape_string($course_code)."' ";
    if (!empty($session_id)) {
        $sql .= " AND session_id = ".(int)$session_id;
    } else {
        $sql .= " AND (session_id IS NULL OR session_id = 0) ";
    }
    $sql .= " ORDER BY id";
    $res = Database::query($sql,__FILE__,__LINE__);
    if (Database::num_rows($res)<1) {
    	return false;
    }
    $row = Database::fetch_array($res);
    $category = $row['id'];
    $sql = "SELECT * FROM $l l WHERE l.category_id = $category AND type = ".(int) $resource_type." and ref_id = ".(int) $resource_id;
    $res = Database::query($sql,__FILE__,__LINE__);
    if (Database::num_rows($res)<1) {
    	return false;
    }
    $row = Database::fetch_array($res);
    return $row['id'];
}
/**
 * Remove a resource from the unique gradebook of a given course
 * @param    int     Link/Resource ID
 * @return   bool    false on error, true on success
 */
function remove_resource_from_course_gradebook($link_id) {
    if ( empty($link_id) ) { return false; }
    require_once (api_get_path(SYS_CODE_PATH).'gradebook/lib/be.inc.php');
    // TODO find the corresponding category (the first one for this course, ordered by ID)
    $l = Database::get_main_table(TABLE_MAIN_GRADEBOOK_LINK);
    $sql = "DELETE FROM $l WHERE id = ".(int)$link_id;
    $res = Database::query($sql,__FILE__,__LINE__);
    return true;
}
/**
 * return the database name
 * @param    int
 * @return   String
 */
function get_database_name_by_link_id($id_link) {
	$course_table = Database::get_main_table(TABLE_MAIN_COURSE);
	$tbl_grade_links = Database :: get_main_table(TABLE_MAIN_GRADEBOOK_LINK);
	$res=Database::query('SELECT db_name from '.$course_table.' c inner join '.$tbl_grade_links.' l
	on c.code=l.course_code WHERE l.id='.$id_link.' OR l.category_id='.$id_link);
	$my_db_name=Database::fetch_array($res,'ASSOC');
	return $my_db_name['db_name'];
}

 /**
  * update user info about certificate
  * @param int The category id
  * @param int The user id
  * @param string the path name of the certificate
  * @return void() 
  */
  function update_user_info_about_certificate ($cat_id,$user_id,$path_certificate) {
  	$table_certificate = Database::get_main_table(TABLE_MAIN_GRADEBOOK_CERTIFICATE);
  	if (!UserManager::is_user_certified($cat_id,$user_id)) {
  		$sql='UPDATE '.$table_certificate.' SET path_certificate="'.$path_certificate.'"  
		WHERE cat_id="'.$cat_id.'" AND user_id="'.$user_id.'" ';
		$rs=Database::query($sql,__FILE__,__LINE__);
  	}
  }
  
  /**
  * register user info about certificate
  * @param int The category id
  * @param int The user id
  * @param float The score obtained for certified
  * @param Datetime The date when you obtained the certificate  
  * @return void() 
  */
  function register_user_info_about_certificate ($cat_id,$user_id,$score_certificate,
  $date_certificate) {
  	$table_certificate = Database::get_main_table(TABLE_MAIN_GRADEBOOK_CERTIFICATE);
  	$sql_exist='SELECT COUNT(*) as count FROM '.$table_certificate.' gc 
	WHERE gc.cat_id="'.$cat_id.'" AND user_id="'.$user_id.'" ';
	$rs_exist=Database::query($sql_exist,__FILE__,__LINE__);
	$row=Database::fetch_array($rs_exist);
	if ($row['count']==0) {
		$sql='INSERT INTO '.$table_certificate.'
		(cat_id,user_id,score_certificate,date_certificate)
		VALUES("'.$cat_id.'","'.$user_id.'","'.$score_certificate.'","'.$date_certificate.'")';
		$rs=Database::query($sql,__FILE__,__LINE__);  
	}
	
  }
  /**
  * Get date of user certificate
  * @param int The category id
  * @param int The user id
  * @return Datetime The date when you obtained the certificate   
  */  
  function get_certificate_date_by_user_id ($cat_id,$user_id) {
    	$table_certificate = Database::get_main_table(TABLE_MAIN_GRADEBOOK_CERTIFICATE);
    	$sql_get_date='SELECT date_certificate FROM '.$table_certificate.' WHERE cat_id="'.$cat_id.'" AND user_id="'.$user_id.'"';
    	$rs_get_date=Database::query($sql_get_date,__FILE__,__LINE__);
    	$row_get_date=Database::fetch_array($rs_get_date,'ASSOC');
    	return $row_get_date['date_certificate'];
  }
  
  /**
  *Get list of users certificates
  *@param int The category id
  *@return array
  */
  function get_list_users_certificates ($cat_id=null) {
    $table_certificate = Database::get_main_table(TABLE_MAIN_GRADEBOOK_CERTIFICATE); 	
    $table_user = Database::get_main_table(TABLE_MAIN_USER);
  	$sql='SELECT DISTINCT u.user_id,u.lastname,u.firstname,u.username FROM '.$table_user.' u INNER JOIN '.$table_certificate.' gc 
	ON u.user_id=gc.user_id ';
	if (!is_null($cat_id) && $cat_id>0) {
  		$sql.=' WHERE cat_id='.Database::escape_string($cat_id);
  	}
  	$sql.=' ORDER BY u.firstname';
	$rs=Database::query($sql,__FILE__,__LINE__);
	$list_users=array();
	while ($row=Database::fetch_array($rs)) {
		$list_users[]=$row;
	}
	return $list_users;
  }

  /**
  *Get list of certificates by user id
  *@param int The user id
  *@param int The category id
  *@retun array
  */
  function get_list_gradebook_certificates_by_user_id ($user_id,$cat_id=null) {
  	$table_certificate = Database::get_main_table(TABLE_MAIN_GRADEBOOK_CERTIFICATE); 	
  	$sql='SELECT gc.score_certificate,gc.date_certificate,gc.path_certificate,gc.cat_id,gc.user_id FROM  '.$table_certificate.' gc 
		WHERE gc.user_id="'.Database::escape_string($user_id).'" ';
	if (!is_null($cat_id) && $cat_id>0) {
  		$sql.=' AND cat_id='.Database::escape_string($cat_id);
  	}
  	$rs=Database::query($sql,__FILE__,__LINE__);
  	$list_certificate=array();
  	while ($row=Database::fetch_array($rs)) {
  		$list_certificate[]=$row;
  	}
  	return $list_certificate;
  }
  /**
  *Allow remove certificate
  *@param int The category id
  *@param int The user id
  *@return boolean
  */
  function delete_certificate ($cat_id,$user_id) {
  	
    $table_certificate = Database::get_main_table(TABLE_MAIN_GRADEBOOK_CERTIFICATE); 		
  	$sql_verified='SELECT count(*) AS count,path_certificate as path,user_id FROM '.$table_certificate.' gc WHERE cat_id="'.Database::escape_string($cat_id).'" AND user_id="'.Database::escape_string($user_id).'" GROUP BY user_id,cat_id';
  	$rs_verified=Database::query($sql_verified,__FILE__,__LINE__);
  	$path=Database::result($rs_verified,0,'path');
  	$user_id=Database::result($rs_verified,0,'user_id');
  	if (!is_null($path) || $path!='' || strlen($path)) {
  			$path_info= UserManager::get_user_picture_path_by_id($user_id,'system',true);
			$path_directory_user_certificate=$path_info['dir'].'certificate'.$path;
			if (is_file($path_directory_user_certificate)) {
				unlink($path_directory_user_certificate);
				if (is_file($path_directory_user_certificate)===false) {
					$delete_db=true;
				} else {
					$delete_db=false;
				}
			}
	  	if (Database::result($rs_verified,0,'count')==1 && $delete_db===true) {
	  		$sql_delete='DELETE FROM '.$table_certificate.' WHERE cat_id="'.Database::escape_string($cat_id).'" AND user_id="'.Database::escape_string($user_id).'" ';
	  		$rs_delete=Database::query($sql_delete,__FILE__,__LINE__);
	  		return true;
	  	} else {
	  		return false;
	  	}
  	}
  }