<?php
/* For licensing terms, see /dokeos_license.txt */

$language_file = 'gradebook';
//$cidReset = true;
require_once '../inc/global.inc.php';
require_once 'lib/gradebook_functions.inc.php';
require_once api_get_path(LIBRARY_PATH).'usermanager.lib.php';

//extra javascript functions for in html head:
$htmlHeadXtra[] =
"<script language='javascript' type='text/javascript'>
function confirmation()
{
	if (confirm(\" ".trim(get_lang('AreYouSureToDelete'))." ?\"))
		{return true;}
	else
		{return false;} 
}
</script>";
api_block_anonymous_users();
if (!api_is_allowed_to_edit()) {
	api_not_allowed(true);
}
$interbreadcrumb[] = array (
	'url' => $_SESSION['gradebook_dest'].'?',
	'name' => get_lang('Gradebook'
));
$interbreadcrumb[] = array (
	'url' => $_SESSION['gradebook_dest'].'?selectcat='.Security::remove_XSS($_GET['cat_id']),
	'name' => get_lang('Details'
));

$interbreadcrumb[] = array (
	'url' => 'gradebook_display_certificate.php?cat_id='.Security::remove_XSS($_GET['cat_id']),
	'name' => get_lang('GradebookListOfStudentsCertificates')
);
Display::display_tool_header('');
?>
<?php
if (isset($_GET['user_id']) && $_GET['user_id']==strval(intval($_GET['user_id'])) && isset($_GET['cat_id']) && $_GET['cat_id']==strval(intval($_GET['cat_id']))) {
	$info=delete_certificate ($_GET['cat_id'],$_GET['user_id']);
	if ($info===true) {
		Display::display_confirmation_message(get_lang('CertificateRemoved'));
	} else  {
		Display::display_error_message(get_lang('CertificateNotRemoved'));		
	}
	
}
?>
<h3 class="actions" style="border-top:1px #999999 solid;border-bottom:1px #999999 solid;border-left:1px #999999 solid;border-right:1px #999999 solid;"><?php echo get_lang('GradebookListOfStudentsCertificates'); ?></h3>

<table border="0" width="100%" style="border-top:1px #999999 solid;border-bottom:1px #999999 solid;border-left:1px #999999 solid;border-right:1px #999999 solid;">
	<?php
	$cat_id=isset($_GET['cat_id']) ? (int)$_GET['cat_id'] : null;
	if (count(get_list_users_certificates($cat_id))==0) {
		echo get_lang('NoResultsAvailable');
	} else {
		foreach (get_list_users_certificates($cat_id) as $index=>$value) {
	?>
	<tr>
		<td width="100%" class="actions" style="border-top:1px #999999 solid;border-bottom:1px #999999 solid;border-left:1px #999999 solid;border-right:1px #999999 solid;"><?php echo get_lang('Student').' : '. $value['firstname'].' '.$value['lastname'] ?>
		</td>
		
	</tr>
	<tr>
	<td>
	<table  width="100%" >
		<?php
		$list_certificate=get_list_gradebook_certificates_by_user_id ($value['user_id'],$cat_id);
		foreach ($list_certificate as $index_certificate=>$value_certificate) {
		?>
		<tr >
		<td width="50%" style="border-top:1px #999999 solid;border-bottom:1px #999999 solid;border-left:1px #999999 solid;border-right:1px #999999 solid;"><?php echo get_lang('Score').' : '.$value_certificate['score_certificate'] ?></td>
		<td width="30%" style="border-top:1px #999999 solid;border-bottom:1px #999999 solid;border-left:1px #999999 solid;border-right:1px #999999 solid;"><?php echo get_lang('Date').' : '.$value_certificate['date_certificate'] ?></td>
		<td width="20%" style="border-top:1px #999999 solid;border-bottom:1px #999999 solid;border-left:1px #999999 solid;border-right:1px #999999 solid;"><a  onclick="return confirmation();" href="gradebook_display_certificate.php?<?php echo 'user_id='.$value_certificate['user_id'].'&amp;cat_id='.$value_certificate['cat_id'] ?>"><?php echo Display::return_icon('delete.png',get_lang('Delete')); ?></a></td>
		</tr>
		<?php
		}
		?>
		</table>
	</td>
	</tr>
    <?php
		}
	}
    ?>
</table>
<?php
Display::display_footer();
?>


