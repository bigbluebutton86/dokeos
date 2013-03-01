<?php
/* For licensing terms, see /dokeos_license.txt */

/**
* @package dokeos.admin
*/

// name of the language file that needs to be included
$language_file = array ('registration','admin');

// resetting the course id
$cidReset = true;

// setting the help
$help_content = 'platformadministrationsessioncategorylist';

// including the global Dokeos file
require ('../inc/global.inc.php');

// including additional libraries
require_once (api_get_path(LIBRARY_PATH).'formvalidator/FormValidator.class.php');
require_once (api_get_path(LIBRARY_PATH).'sessionmanager.lib.php');

// setting the section (for the tabs)
$this_section = SECTION_PLATFORM_ADMIN;

// Access restrictions
api_protect_admin_script(true);

// setting breadcrumbs
$interbreadcrumb[]=array("url" => "index.php","name" => get_lang('PlatformAdmin'));

// additional javascript, css, ...
$htmlHeadXtra[] = '<script language="javascript">

				function selectAll(idCheck,numRows,action) {

					for(i=0;i<numRows;i++) {
						idcheck = document.getElementById(idCheck+"_"+i);
						if (action == "true"){
							idcheck.checked = true;
						} else {
							idcheck.checked = false;
						}
					}

				}
				</script>';

// Database table definitions
$tbl_session_category 	= Database::get_main_table(TABLE_MAIN_SESSION_CATEGORY);
$tbl_session 			= Database::get_main_table(TABLE_MAIN_SESSION);

// variable handling
$page=intval($_GET['page']);
$action= Security::remove_XSS($_REQUEST['action']);
$sort=in_array($_GET['sort'],array('name','nbr_session','date_start','date_end'))? Security::remove_XSS($_GET['sort']) : 'name';
$idChecked = Security::remove_XSS($_REQUEST['idChecked']);
$order = (isset($_REQUEST['order']))? Security::remove_XSS($_REQUEST['order']): 'ASC';

if ($action == 'delete_on_session' || $action == 'delete_off_session') {
	$delete_session = ($action == 'delete_on_session')? true: false;
	SessionManager::delete_session_category($idChecked, $delete_session);
	header('Location: '.api_get_self().'?sort='.$sort.'&action=show_message&message='.urlencode(get_lang('SessionCategoryDelete')));
	exit();
}

if (isset ($_GET['search']) && $_GET['search'] == 'advanced') {
	$interbreadcrumb[] = array ("url" => 'session_category_list.php', "name" => get_lang('ListSessionCategory'));
	$tool_name = get_lang('SearchASession');

	// Display the header
	Display :: display_header($tool_name);

	// start the content div
	echo '<div id="content">';

	// create the form
	$form = new FormValidator('advanced_search','get');
	$form->addElement('header', '', $tool_name);
	$active_group = array();
	$active_group[] = $form->createElement('checkbox','active','',get_lang('Active'));
	$active_group[] = $form->createElement('checkbox','inactive','',get_lang('Inactive'));
	$form->addGroup($active_group,'',get_lang('ActiveSession'),'<br/>',false);

	$form->addElement('style_submit_button', 'submit',get_lang('SearchUsers'),'class="search"');
	$defaults['active'] = 1;
	$defaults['inactive'] = 1;
	$form->setDefaults($defaults);

	// Display the form
	$form->display();
} else {
	$limit = 20;
	$from = $page * $limit;
	//if user is crfp admin only list its sessions
	if(!api_is_platform_admin()) {
		$where .= (empty($_REQUEST['keyword']) ? " " : " WHERE name LIKE '%".addslashes($_REQUEST['keyword'])."%'");
	}
	else {
		$where .= (empty($_REQUEST['keyword']) ? " " : " WHERE name LIKE '%".addslashes($_REQUEST['keyword'])."%'");
	}

	$query = "SELECT sc.*, (select count(id) FROM $tbl_session WHERE session_category_id = sc.id) as nbr_session
	 			FROM $tbl_session_category sc
	 			$where
	 			ORDER BY $sort $order
	 			LIMIT $from,".($limit+1);

	$query_rows = "SELECT count(*) as total_rows
				FROM $tbl_session_category sc $where ";

	$order = ($order == 'ASC')? 'DESC': 'ASC';
	$result_rows = Database::query($query_rows,__FILE__,__LINE__);
	$recorset = Database::fetch_array($result_rows);
	$num = $recorset['total_rows'];
	$result = Database::query($query,__FILE__,__LINE__);
	$Sessions = Database::store_result($result);
	$nbr_results = sizeof($Sessions);

	// Display the header
	Display::display_header(get_lang('ListSessionCategory'));

	// Display the tool title
	// api_display_tool_title(get_lang('ListSessionCategory'));

    if (!empty($_GET['warn'])) {
        Display::display_warning_message(urldecode($_GET['warn']),false);
    }
    if(isset($_GET['action'])) {
        Display::display_confirmation_message(stripslashes($_GET['message']),false);
    }
    ?>

	<div class="actions">
	<?php
	echo '<div style="float:right;">
			<a href="'.api_get_path(WEB_CODE_PATH).'admin/session_category_add.php">'.Display::return_icon('view_more_stats.gif',get_lang('AddSession')).get_lang('AddSessionCategory').'</a>
	 	  </div>';
	?>
	<form method="POST" action="session_category_list.php">
		<input type="text" name="keyword" value="<?php echo Security::remove_XSS($_GET['keyword']); ?>"/>
		<button class="search" type="submit" name="name" value="<?php echo get_lang('Search') ?>"><?php echo get_lang('Search') ?></button>
		<!-- <a href="session_list.php?search=advanced"><?php echo get_lang('AdvancedSearch'); ?></a> -->
		</form>
	<form method="post" action="<?php echo api_get_self(); ?>?action=delete&sort=<?php echo $sort; ?>" onsubmit="javascript:if(!confirm('<?php echo get_lang('ConfirmYourChoice'); ?>')) return false;">
	 </div>
	<div align="left">
	<?php
	if(count($Sessions)==0 && isset($_POST['keyword'])) {
		echo get_lang('NoSearchResults');
		echo '</div>';
	} else {
		if ($num > $limit) {
			if ($page) {
			?>
			<a href="<?php echo api_get_self(); ?>?page=<?php echo $page-1; ?>&sort=<?php echo $sort; ?>&order=<?php echo  Security::remove_XSS($_REQUEST['order']); ?>&keyword=<?php echo $_REQUEST['keyword']; ?><?php echo @$cond_url; ?>"><?php echo get_lang('Previous'); ?></a>
			<?php
			} else {
				echo get_lang('Previous');
			}
			?>
			|
			<?php
			if($nbr_results > $limit) {
				?>
				<a href="<?php echo api_get_self(); ?>?page=<?php echo $page+1; ?>&sort=<?php echo $sort; ?>&order=<?php echo  Security::remove_XSS($_REQUEST['order']); ?>&keyword=<?php echo $_REQUEST['keyword']; ?><?php echo @$cond_url; ?>"><?php echo get_lang('Next'); ?></a>
				<?php
			} else {
				echo get_lang('Next');
			}
		}
		?>
	</div>
	
	<?php 
	// start the content div
	echo '<div id="content">';
	?>

		<table class="data_table" width="100%">
		<tr>
		  <th>&nbsp;</th>
		  <th><a href="<?php echo api_get_self(); ?>?sort=name&order=<?php echo ($sort=='name')? $order: 'ASC'; ?>"><?php echo get_lang('SessionCategoryName'); ?></a></th>
		  <th><a href="<?php echo api_get_self(); ?>?sort=nbr_session&order=<?php echo ($sort=='nbr_session')? $order: 'ASC'; ?>"><?php echo get_lang('NumberOfSession'); ?></a></th>
		  <th><a href="<?php echo api_get_self(); ?>?sort=date_start&order=<?php echo ($sort=='date_start')? $order: 'ASC'; ?>"><?php echo get_lang('StartDate'); ?></a></th>
		  <th><a href="<?php echo api_get_self(); ?>?sort=date_end&order=<?php echo ($sort=='date_end')? $order: 'ASC'; ?>"><?php echo get_lang('EndDate'); ?></a></th>
		  <th><?php echo get_lang('Actions'); ?></th>
		</tr>

		<?php
		$i=0;
		$x=0;
		foreach ($Sessions as $key=>$enreg) {
			if($key == $limit) {
				break;
			}
		$sql = 'SELECT COUNT(session_category_id) FROM '.$tbl_session.' WHERE session_category_id = '.intval($enreg['id']);
		$rs = Database::query($sql, __FILE__, __LINE__);
		list($nb_courses) = Database::fetch_array($rs);
		?>
		<tr class="<?php echo $i?'row_odd':'row_even'; ?>">
		  <td><input type="checkbox" id="idChecked_<?php echo $x; ?>" name="idChecked[]" value="<?php echo $enreg['id']; ?>"></td>
		  <td><?php echo api_htmlentities($enreg['name'],ENT_QUOTES,$charset); ?></td>
		  <td><?php echo "<a href=\"session_list.php?id_category=".$enreg['id']."\">".$nb_courses." Sesion(es) </a>"; ?></td>
		  <td><?php echo api_htmlentities($enreg['date_start'],ENT_QUOTES,$charset); ?></td>
		  <td><?php echo api_htmlentities($enreg['date_end'],ENT_QUOTES,$charset); ?></td>
		  <td>
			<a href="session_category_edit.php?&id=<?php echo $enreg['id']; ?>"><?php Display::display_icon('pixel.gif', get_lang('Edit'),array('class'=>'actionplaceholdericon actionedit')); ?></a>
			<a href="<?php echo api_get_self(); ?>?sort=<?php echo $sort; ?>&action=delete_off_session&idChecked=<?php echo $enreg['id']; ?>" onclick="javascript:if(!confirm('<?php echo get_lang('ConfirmYourChoice'); ?>')) return false;"><?php Display::display_icon('delete.png', get_lang('Delete')); ?></a>
		  </td>
		</tr>
		<?php
			$i=$i ? 0 : 1;
			$x++;
		}
		unset($Sessions);
		?>
		</table>
		<br />

		<div align="left">

		<?php

		if ($num > $limit) {
		if ($page)
			{
			?>

			<a href="<?php echo api_get_self(); ?>?page=<?php echo $page-1; ?>&sort=<?php echo $sort; ?>&order=<?php echo  Security::remove_XSS($_REQUEST['order']); ?>&keyword=<?php echo $_REQUEST['keyword']; ?><?php echo @$cond_url; ?>"><?php echo get_lang('Previous'); ?></a>

			<?php
			}
			else
			{
				echo get_lang('Previous');
			}
			?>

			|

			<?php
			if($nbr_results > $limit)
			{
			?>

			<a href="<?php echo api_get_self(); ?>?page=<?php echo $page+1; ?>&sort=<?php echo $sort; ?>&order=<?php echo  Security::remove_XSS($_REQUEST['order']); ?>&keyword=<?php echo $_REQUEST['keyword']; ?><?php echo @$cond_url; ?>"><?php echo get_lang('Next'); ?></a>

			<?php
			}
			else
			{
				echo get_lang('Next');
			}
		} ?>
		</div>
		<br />
		<a href="#" onclick="selectAll('idChecked',<?php echo $x; ?>,'true');return false;"><?php echo get_lang('SelectAll') ?></a>&nbsp;-&nbsp;
		<a href="#" onclick="selectAll('idChecked',<?php echo $x; ?>,'false');return false;"><?php echo get_lang('UnSelectAll') ?></a>
		<select name="action">
			<option value="delete_off_session" selected="selected"><?php echo get_lang('DeleteSelectedSessionCategory'); ?></option>
			<option value="delete_on_session"><?php echo get_lang('DeleteSelectedFullSessionCategory'); ?></option>
		</select>
		<button class="save" type="submit" name="name" value="<?php echo get_lang('Ok') ?>"><?php echo get_lang('Ok') ?></button>
		<?php } ?>
	</table>

<?php
}

// close the content div
echo '</div>';

// display the footer
Display::display_footer();
?>
