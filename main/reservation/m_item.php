<?php
//* For licensing terms, see /dokeos_license.txt */

/**
==============================================================================
*	@package dokeos.booking
==============================================================================
*/

require_once('rsys.php');

// language file
$language_file = 'admin';

// not inside a course
$cidReset = true;

// including the global Dokeos file
require_once ('../inc/global.inc.php');

// setting the tabs
$this_section = SECTION_PLATFORM_ADMIN;

// access restriction
api_protect_admin_script();
Rsys :: protect_script('m_item');

// tool name
$tool_name = get_lang('ViewResources');

// breadcrumbs
$interbreadcrumb[] = array ("url" => "../admin/index.php", "name" => get_lang('PlatformAdmin'));

/**
    ---------------------------------------------------------------------
 */

/**
 *  Filter to display the modify-buttons
 *
 *  @param  -   int     $id     The item-id
 */
function modify_filter($id) {
    $str='';
	$outtt=false;

	// view the booking periods for this resource
	$str .= '<a href="m_reservation.php?action=overviewsubscriptions&amp;resource='.$id.'">'.Display::return_icon('calendar_week.gif', get_lang('ViewBookingPeriods')).'</a>';
	
	// enable or disable the resource
	if(Rsys::item_allow($id,'edit')){
        $number = Rsys :: get_item($id);
        //checking the status
        if ($number[5]==1) {
        	$str.= ' <a href="m_item.php?action=blackout&amp;id='.$id.'" title="'.get_lang('Inactive').'">'.Display::return_icon('wrong.gif',get_lang('Wrong')).'</a>';
        } else {
        	$str.= ' <a href="m_item.php?action=blackout&amp;id='.$id.'" title="'.get_lang('Active').'">'.Display::return_icon('right.gif',get_lang('Right')).'</a>';
        }
    }

    // edit the resource
    if(Rsys::item_allow($id,'edit')){
        $str.='<a href="m_item.php?action=edit&amp;id='.$id.'" title="'.get_lang("EditItem2").'">'.Display::return_icon('edit.png',get_lang('Edit')).'</a>';
    }
    
    // delete the resource
    //if(Rsys::item_allow($id,'m_rights')) $str.=' &nbsp;<a href="m_item.php?action=m_rights&amp;item_id='.$id.'" title="'.get_lang("MRights").'">'.Display::return_icon('info_small.gif').'</a>';
    if(Rsys::item_allow($id,'delete')) $str.=' <a href="m_item.php?action=delete&amp;id='.$id.'" title="'.get_lang('DeleteResource').'" onclick="javascript:if(!confirm('."'".addslashes(api_htmlentities(get_lang("ConfirmDeleteResource")))."'".')) return false;">'.Display::return_icon('delete.png',get_lang('Delete')).'</a>';

    return $str;
}

function resourcename_filter($name,$url_params,$row)
{
	return '<a href="m_reservation.php?action=overviewsubscriptions&resource='.$row[5].'">'.$name.'</a>';
}

/**
 *  Filter to display the modify-buttons
 *
 *  @param  -   int     $id     The item-rights-id's
 */
function modify_rights_filter($id) {
	return ' <a href="m_item.php?action=m_rights&amp;subaction=delete&amp;item_id='.substr($id, 0, strpos($id, '-')).'&amp;class_id='.substr($id, strrpos($id, '-') + 1).'" title="'.get_lang("RemoveClassRights").'" onclick="javascript:if(!confirm('."'".addslashes(api_htmlentities(get_lang("ConfirmDeleteResource")))."'".')) return false;">'.Display::return_icon('delete.png',get_lang('Delete')).'</a>';
}

/**
    ---------------------------------------------------------------------
 */

if (isset ($_POST['action'])) {
	switch ($_POST['action']) {
		case 'delete_items' :
			$ids = $_POST['items'];
			$warning = false;
			if (count($ids) > 0) {
				foreach ($ids as $id) {
					$result = Rsys :: delete_item($id);
					if ($result != 0 && $warning <> true)
						$warning = true;
				}
				ob_start();
				if ($warning) {
					Display :: display_error_message(get_lang('ItemNotDeleted'), false);

				} else {

					Display :: display_confirmation_message(get_lang('ResourceDeleted'), false);
				}
				$msg = ob_get_contents();
				ob_end_clean();
			}
			break;
		case 'delete_itemrights' :
			$ids = $_POST['itemrights'];
			if (count($ids) > 0) {
				foreach ($ids as $id)
					Rsys :: delete_item_right(substr($id, 0, strpos($id, '-')), substr($id, strrpos($id, '-') + 1));
				ob_start();
				Display :: display_confirmation_message(get_lang('ItemRightDeleted'),false);
				$msg = ob_get_contents();
				ob_end_clean();
				$_GET['item_id'] = substr($id, 0, strpos($id, '-'));
			} else {
				header("Location: ".$_SERVER['HTTP_REFERER']);
				exit ();
			}
			$_GET['action'] = 'm_rights';
			break;
        case 'set_r_rights' :
            $ids = $_POST['itemrights'];
            if (count($ids) > 0) {
                foreach ($ids as $id)
                    Rsys :: set_new_right(substr($id, 0, strpos($id, '-')), substr($id, strrpos($id, '-') + 1), 'view_right', 1);
                $_GET['item_id'] = substr($id, 0, strpos($id, '-'));
            } else {
                header("Location: ".$_SERVER['HTTP_REFERER']);
                exit ();
            }
            $_GET['action'] = 'm_rights';
            break;
       case 'unset_r_rights' :
            $ids = $_POST['itemrights'];
            if (count($ids) > 0) {
                foreach ($ids as $id)
                    Rsys :: set_new_right(substr($id, 0, strpos($id, '-')), substr($id, strrpos($id, '-') + 1), 'view_right', 0);
                $_GET['item_id'] = substr($id, 0, strpos($id, '-'));
            } else {
                header("Location: ".$_SERVER['HTTP_REFERER']);
                exit ();
            }
            $_GET['action'] = 'm_rights';
            break;
      case 'set_edit_rights' :
            $ids = $_POST['itemrights'];
            if (count($ids) > 0) {
                foreach ($ids as $id)
                    Rsys :: set_new_right(substr($id, 0, strpos($id, '-')), substr($id, strrpos($id, '-') + 1), 'edit_right', 1);
                $_GET['item_id'] = substr($id, 0, strpos($id, '-'));
            } else {
                header("Location: ".$_SERVER['HTTP_REFERER']);
                exit ();
            }
            $_GET['action'] = 'm_rights';
            break;
        case 'unset_edit_rights' :
            $ids = $_POST['itemrights'];
            if (count($ids) > 0) {
                foreach ($ids as $id)
                    Rsys :: set_new_right(substr($id, 0, strpos($id, '-')), substr($id, strrpos($id, '-') + 1), 'edit_right', 0);
                $_GET['item_id'] = substr($id, 0, strpos($id, '-'));
            } else {
                header("Location: ".$_SERVER['HTTP_REFERER']);
                exit ();
            }
            $_GET['action'] = 'm_rights';
            break;
        case 'set_delete_rights' :
            $ids = $_POST['itemrights'];
            if (count($ids) > 0) {
                foreach ($ids as $id)
                    Rsys :: set_new_right(substr($id, 0, strpos($id, '-')), substr($id, strrpos($id, '-') + 1), 'delete_right', 1);
                $_GET['item_id'] = substr($id, 0, strpos($id, '-'));
            } else {
                header("Location: ".$_SERVER['HTTP_REFERER']);
                exit ();
            }
            $_GET['action'] = 'm_rights';
            break;
        case 'unset_delete_rights' :
            $ids = $_POST['itemrights'];
            if (count($ids) > 0) {
                foreach ($ids as $id)
                    Rsys :: set_new_right(substr($id, 0, strpos($id, '-')), substr($id, strrpos($id, '-') + 1), 'delete_right', 0);
                $_GET['item_id'] = substr($id, 0, strpos($id, '-'));
            } else {
                header("Location: ".$_SERVER['HTTP_REFERER']);
                exit ();
            }
            $_GET['action'] = 'm_rights';
            break;
        case 'set_mres_rights' :
            $ids = $_POST['itemrights'];
            if (count($ids) > 0) {
                foreach ($ids as $id)
                    Rsys :: set_new_right(substr($id, 0, strpos($id, '-')), substr($id, strrpos($id, '-') + 1), 'm_reservation', 1);
                $_GET['item_id'] = substr($id, 0, strpos($id, '-'));
            } else {
                header("Location: ".$_SERVER['HTTP_REFERER']);
                exit ();
            }
            $_GET['action'] = 'm_rights';
            break;
        case 'unset_mres_rights' :
            $ids = $_POST['itemrights'];
            if (count($ids) > 0) {
                foreach ($ids as $id)
                    Rsys :: set_new_right(substr($id, 0, strpos($id, '-')), substr($id, strrpos($id, '-') + 1), 'm_reservation', 0);
                $_GET['item_id'] = substr($id, 0, strpos($id, '-'));
            } else {
                header("Location: ".$_SERVER['HTTP_REFERER']);
                exit ();
            }
            $_GET['action'] = 'm_rights';
            break;
       case 'set_all_rights' :
            $ids = $_POST['itemrights'];
            if (count($ids) > 0) {
                foreach ($ids as $id){
                    Rsys :: set_new_right(substr($id, 0, strpos($id, '-')), substr($id, strrpos($id, '-') + 1), 'm_reservation', 1);
                    Rsys :: set_new_right(substr($id, 0, strpos($id, '-')), substr($id, strrpos($id, '-') + 1), 'edit_right', 1);
                    Rsys :: set_new_right(substr($id, 0, strpos($id, '-')), substr($id, strrpos($id, '-') + 1), 'delete_right', 1);
                    Rsys :: set_new_right(substr($id, 0, strpos($id, '-')), substr($id, strrpos($id, '-') + 1), 'view_right', 1);
                }
                $_GET['item_id'] = substr($id, 0, strpos($id, '-'));
            } else {
                header("Location: ".$_SERVER['HTTP_REFERER']);
                exit ();
            }
            $_GET['action'] = 'm_rights';
            break;
        case 'unset_all_rights' :
            $ids = $_POST['itemrights'];
            if (count($ids) > 0) {
                foreach ($ids as $id){
                    Rsys :: set_new_right(substr($id, 0, strpos($id, '-')), substr($id, strrpos($id, '-') + 1), 'm_reservation', 0);
                    Rsys :: set_new_right(substr($id, 0, strpos($id, '-')), substr($id, strrpos($id, '-') + 1), 'edit_right', 0);
                    Rsys :: set_new_right(substr($id, 0, strpos($id, '-')), substr($id, strrpos($id, '-') + 1), 'delete_right', 0);
                    Rsys :: set_new_right(substr($id, 0, strpos($id, '-')), substr($id, strrpos($id, '-') + 1), 'view_right', 0);
                }
                $_GET['item_id'] = substr($id, 0, strpos($id, '-'));
            } else {
                header("Location: ".$_SERVER['HTTP_REFERER']);
                exit ();
            }
            $_GET['action'] = 'm_rights';
            break;
	}
}
switch ($_GET['action']) {
	case 'm_rights' :
        if(!Rsys::item_allow($_GET['item_id'],'m_rights')) die('No Access!');
		switch ($_GET['subaction']) {
			case 'edit' :
				$item = Rsys :: get_item($_GET['item_id']);
				$classDB = Rsys :: get_class_group($_GET['class_id']);
				$item_rights = Rsys :: get_item_rights($_GET['item_id'], $_GET['class_id']);

				// breadcrumbs
				$interbreadcrumb[] = array ("url" => "mysubscriptions.php", "name" => get_lang('Booking'));
				$interbreadcrumb[] = array ("url" => "m_item.php", "name" => $tool_name);
				$interbreadcrumb[] = array ("url" => "m_item.php?&action=m_rights&id=".$item['id'], "name" => str_replace('#ITEM#', $item['name'], get_lang('MItemRights')));

				// header
				Display :: display_header(get_lang('EditRight'));
				
				// tool title
				// api_display_tool_title(get_lang('EditRight'));
				$form = new FormValidator('itemright', 'post', "m_item.php?id=".$item['id']."&action=m_rights&subaction=edit");

				$form->add_textfield('classn', get_lang('Class'), true, array ('readonly' => 'readonly'));

				$form->addElement('checkbox', 'edit_right', get_lang('EditRight'));
				$form->addElement('checkbox', 'delete_right', get_lang('DeleteRight'));
				$form->addElement('checkbox', 'm_reservation', get_lang('MReservationRight'));

				$form->addElement('hidden', 'item_id', $item['id']);
				$form->addElement('hidden', 'class_id', $_GET['class_id']);

				$item_right['classn'] = $classDB[0]['name'];
				$item_right['edit_right'] = $item_rights[0]['edit_right'];
				$item_right['delete_right'] = $item_rights[0]['delete_right'];
				$item_right['m_reservation'] = $item_rights[0]['m_reservation'];
				$form->setDefaults($item_right);

				$form->addElement('style_submit_button', 'submit', get_lang('Ok'),'class="save"');
				if ($form->validate()) {
					$values = $form->exportValues();
					Rsys :: edit_item_right($values['item_id'], $values['class_id'], $values['edit_right'], $values['delete_right'], $values['m_reservation']);
					Display :: display_confirmation_message(Rsys :: get_return_msg(get_lang('ItemRightEdited'), "m_item.php?id=".$_GET['id']."&action=m_rights", str_replace('#ITEM#', $item['name'], get_lang('MItemRights'))),false);
				} else
					$form->display();
				break;
			case 'delete' :
				Rsys :: delete_item_right($_GET['item_id'], $_GET['class_id']);
				ob_start();
				Display :: display_normal_message(get_lang('ItemRightDeleted'),false);
				$msg = ob_get_contents();
				ob_end_clean();
			case 'switch' :
				switch ($_GET['switch']) {
					case 'edit' :
						Rsys :: set_new_right($_GET['item_id'], $_GET['class_id'], 'edit_right', $_GET['set']);
						break;
					case 'delete' :
						Rsys :: set_new_right($_GET['item_id'], $_GET['class_id'], 'delete_right', $_GET['set']);
						break;
					case 'manage' :
						Rsys :: set_new_right($_GET['item_id'], $_GET['class_id'], 'm_reservation', $_GET['set']);
						break;
					case 'view' :
                        Rsys :: set_new_right($_GET['item_id'], $_GET['class_id'], 'view_right', $_GET['set']);
                        break;
				}
			default :
				$item = Rsys :: get_item($_GET['item_id']);
				$NoSearchResults = get_lang('NoRights');


				$interbreadcrumb[] = array ("url" => "mysubscriptions.php", "name" => get_lang('Booking'));
				$interbreadcrumb[] = array ("url" => "m_item.php", "name" => get_lang('ManageResources'));
				Display :: display_header(str_replace('#ITEM#', $item['name'], get_lang('MItemRights')));

				// tool title
				// api_display_tool_title(get_lang('MItemRights2'));

				echo $msg;
				$_s_item['id'] = $_GET['item_id'];
				$_s_item['name'] = $item['name'];
				api_session_register('_s_item');
				//api_session_register('s_item_name');
				//echo "<a href=\"m_item.php?action=add_classgroup\">".get_lang('MAddClassgroup')."</a>";
				$table = new SortableTable('itemrights', array ('Rsys', 'get_num_itemrights'), array ('Rsys', 'get_table_itemrights'), 1);
				$table->set_header(0, '', false, array ('style' => 'width:10px'));
				$table->set_additional_parameters(array('action'=>'m_rights','item_id'=>$_GET['item_id']));
				$table->set_header(1, get_lang('Class'), false);
                $table->set_header(2, get_lang('EditItemRight'), false);
				$table->set_header(3, get_lang('DeleteItemRight'), false);
				$table->set_header(4, get_lang('MBookingPeriodsRight'), false);
                $table->set_header(5, get_lang('ViewItemRight'), false);
				$table->set_header(6, '', false, array ('style' => 'width:50px;'));
				$table->set_column_filter(6, 'modify_rights_filter');
				$table->set_form_actions(array (
                                        'delete_itemrights'     => get_lang('DeleteSelectedItemRights'),
                                        'set_edit_rights'       => get_lang('SetEditRights'),
                                        'unset_edit_rights'     => get_lang('UnsetEditRights'),
                                        'set_delete_rights'     => get_lang('SetDeleteRights'),
                                        'unset_delete_rights'   => get_lang('UnsetDeleteRights'),
                                        'set_mres_rights'       => get_lang('SetMresRights'),
                                        'unset_mres_rights'     => get_lang('UnsetMresRights'),
                                        'set_r_rights'          => get_lang('SetViewRights'),
                                        'unset_r_rights'        => get_lang('UnsetViewRights'),
                                        'set_all_rights'        => get_lang('SetAllRights'),
                                        'unset_all_rights'      => get_lang('UnsetAllRights')
                                        ), 'itemrights');
				$table->display();
		}
		break;
	case 'add' :
		// breadcrumbs
		//$interbreadcrumb[] = array ('url' => 'm_category.php', 'name' => get_lang('BookingSystem'));
		//$interbreadcrumb[] = array ("url" => "m_item.php", "name" => get_lang('ManageResources'));
		//$interbreadcrumb[] = array ("url" => "m_item.php", "name" => $tool_name);

		// tool name
		$tool_name = get_lang('AddNewResource');

		// start output buffering
		ob_start();

		// start the form
		$form = new FormValidator('item', 'post', 'm_item.php?action=add&cat='.Security::Remove_XSS($_GET['cat']));
	
		// getting all the defined categories
		$cats = Rsys :: get_category();
		foreach ($cats as $cat) {
			$catOptions[$cat['id']] = $cat['name'];
		}
		
		// adding the form elements
		$form->addElement('header','',get_lang('AddNewResource'));
		$form->addElement('select', 'category', get_lang('ResourceType'), $catOptions);
		$form->add_textfield('name', get_lang('ResourceName'), true, array ('maxlength' => '128'));
		$form->addElement('textarea', 'description', get_lang('Description'), array ('rows' => '3', 'cols' => '40'));
		$form->addElement('checkbox', 'always_available', get_lang('AlwaysAvailable'));
		$form->addRule('category', get_lang('ThisFieldIsRequired'), 'required');

		// TODO: get list of courses (to link it to the item)
		//$form->addElement('select', 'course_code', get_lang('ItemCourse'),array(''=>'','value'=>'tag'));
		//$form->addRule('course', get_lang('ThisFieldIsRequired'), 'required');

		// default values
		if (is_numeric($_GET['cat']) AND isset($_GET['cat']))
		{
			$defaults['category'] = $_GET['cat'];
		}
		$form->setDefaults($defaults);

		$form->addElement('style_submit_button', 'submit', get_lang('AddNewResource'),'class="add"');
		if ($form->validate()) {
			$values = $form->exportValues();
			if (Rsys :: add_item($values['name'], $values['description'], $values['category'], $values['course_code'], $values['always_available']))
			{
				Display :: display_confirmation_message(get_lang('ResourceAdded'),false);
			}
			else
			{
				Display :: display_error_message(Rsys :: get_return_msg(get_lang('ResourceExist'), "m_item.php?action=add&cat=".Security::Remove_XSS($_GET['cat']), get_lang('AddNewResource')),false);
			}
		} else {
			$form->display();
		}
		
		$msg = ob_get_contents();
		ob_end_clean();
		
		break;
	case 'edit' :
		$item = Rsys :: get_item($_GET['id']);
		$cats = Rsys :: get_category();
		foreach ($cats as $cat)
			$catOptions[$cat['id']] = $cat['name'];

		// breadcrumbs	
		$interbreadcrumb[] = array ('url' => 'm_category.php', 'name' => get_lang('BookingSystem'));
		//$interbreadcrumb[] = array ("url" => "m_item.php", "name" => get_lang('ManageResources'));

		Display :: display_header(str_replace('#ITEM#', $item['name'], get_lang('EditResource')));
			
		// tool title
		// api_display_tool_title(get_lang('EditResource'));
		
		$form = new FormValidator('item', 'post', 'm_item.php?action=edit');
		$form->addElement('select', 'category_id', get_lang('ResourceType'), $catOptions);
		$form->add_textfield('name', get_lang('ResourceName'), array ('maxlength' => '128'));
		$form->addElement('textarea', 'description', get_lang('Description'), array ('rows' => '3', 'cols' => '40'));
		$form->addElement('checkbox', 'always_available', get_lang('AlwaysAvailable'));
		$form->addRule('category_id', get_lang('ThisFieldIsRequired'), 'required');
		$form->addElement('hidden', 'id', $item['id']);
		$form->addElement('style_submit_button', 'submit', get_lang('EditResource'),'class="save"');
		$form->setDefaults($item);
		if ($form->validate()) {
			$values = $form->exportValues();
			if (Rsys :: edit_item($values['id'], $values['name'], $values['description'], $values['category_id'], $values['course_id'], $values['always_available']))
				Display :: display_confirmation_message(get_lang('ResourceEdited'),false);
			else
				Display :: display_error_message(get_lang('ResourceExist'),false);
		} else
			$form->display();
		break;
	case 'delete' :
		$result = Rsys :: delete_item($_GET['id']);
		ob_start();
		if($result == '0'){
			Display :: display_confirmation_message(get_lang('ResourceDeleted'),false);}
		else
			Display :: display_error_message(str_replace('#NUM#', $result, get_lang('ItemHasReservations')),false);
		$msg = ob_get_contents();
		ob_end_clean();
		break;
}

$NoSearchResults = get_lang('NoItems');		
		
// breadcrumbs
$interbreadcrumb[] = array ('url' => 'm_category.php', 'name' => get_lang('BookingSystem'));
if (is_numeric($_GET['cat']) AND isset($_GET['cat']))	
{
	$category_information = Rsys :: get_category(Security::Remove_XSS($_GET['cat']));
	$interbreadcrumb[] = array ('url' => 'm_category.php?cat='.Security::Remove_XSS($_GET['cat']), 'name' => $category_information['name']);
}
//$interbreadcrumb[] = array ("url" => "m_item.php", "name" => get_lang('ManageResources'));

// Header
Display :: display_header($tool_name);
	
// tool title
// api_display_tool_title(get_lang('ResourceList'));

echo $msg;

if($_GET['action'] == 'blackout'){
			$result = Rsys :: black_out_changer($_GET['id']);
			if ($result==1) {
		Display :: display_confirmation_message(get_lang('ResourceInactivated'),false);
			}
			else {
		Display :: display_confirmation_message(get_lang('ResourceActivated'),false);
		}
}

// actions
echo '<div class="actions">';
echo '<form id="cat_form" action="m_item.php" method="get" style="float: right;">';
echo get_lang('ResourceFilter').': ';
echo '<select name="cat" onchange="this.form.submit();"><option value="0"> '.get_lang('All').' </option>';
$cats = Rsys :: get_category_with_items_manager();
foreach ($cats as $cat) {
			echo '<option value="'.$cat['id'].'"'. ($cat['id'] == $_GET['cat'] ? ' selected="selected"' : '').'>'.$cat['name'].'</option>';
}
echo '</select></form>';
echo '<a href="m_category.php">'.Display::return_icon('folder_document.gif', get_lang('ViewResourceTypes')).get_lang('ViewResourceTypes').'</a>';
echo '<a href="m_category.php?action=add">'.Display::return_icon('folder_new.gif',get_lang('AddNewResourceType')).get_lang('AddNewResourceType').'</a>';
echo '<a href="m_item.php">'.Display::return_icon('cube.png',get_lang('ViewResources')).get_lang('ViewResources').'</a>';
echo '<a href="m_item.php?action=add&amp;cat='.Security::Remove_XSS($_GET['cat']).'">'.Display::return_icon('cube_add.png',get_lang('AddNewResource')).get_lang('AddNewResource').'</a>';
echo '<a href="m_reservation.php?action=overviewsubscriptions">'.Display::return_icon('calendar_week.gif',get_lang('ViewBookingPeriods')).get_lang('ViewBookingPeriods').'</a>';
echo '<a href="m_reservation.php?action=add&amp;cat_id='.Security::Remove_XSS($_GET['cat']).'">'.Display::return_icon('calendar_add.gif',get_lang('AddNewBookingPeriod')).get_lang('AddNewBookingPeriod').'</a>';
echo '</div>';
		
// start the content div
echo '<div id="content">';

$table = new SortableTable('item', array ('Rsys', 'get_num_items'), array ('Rsys', 'get_table_items'), 1);
$table->set_additional_parameters(array('cat'=>$_GET['cat']));
$table->set_header(0, '', false, array ('style' => 'width:10px'));
$table->set_header(1, get_lang('ResourceName'), true);
$table->set_header(2, get_lang('Description'), true);
$table->set_header(3, get_lang('ResourceType'), true);
$table->set_header(4, get_lang('Owner'), true);
$table->set_header(5, '', false, array ('style' => 'width:100px;'));
$table->set_column_filter(1, 'resourcename_filter');
$table->set_column_filter(5, 'modify_filter');
$table->set_form_actions(array ('delete_items' => get_lang('DeleteSelectedResources')), 'items');
$table->display();

// close the content div
echo '</div>';

// footer
Display :: display_footer();
?>

