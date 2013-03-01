<?php
/* For licensing terms, see /dokeos_license.txt */

/**
 * 	Learning Path
 * 	@package dokeos.learnpath
 * 	@author	Yannick Warnier - cleaning and update for new SCORM tool
 * 	@author Patrick Cool
 * 	@author Denes Nagy
 * 	@author Roan Embrechts, refactoring and code cleaning
 */
$this_section = SECTION_COURSES;

api_protect_course_script();

/*
  -----------------------------------------------------------
  Libraries
  -----------------------------------------------------------
 */
//the main_api.lib.php, database.lib.php and display.lib.php
//libraries are included by default

include('learnpath_functions.inc.php');
//include('../resourcelinker/resourcelinker.inc.php');
include('resourcelinker.inc.php');
//rewrite the language file, sadly overwritten by resourcelinker.inc.php
$language_file = "learnpath";

// we set the encoding of the lp
if (!empty($_SESSION['oLP']->encoding)) {
    $charset = $_SESSION['oLP']->encoding;
    // Check if we have a valid api encoding
    $valid_encodings = api_get_valid_encodings();
    $has_valid_encoding = false;
    foreach ($valid_encodings as $valid_encoding) {
        if (strcasecmp($charset, $valid_encoding) == 0) {
            $has_valid_encoding = true;
        }
    }
    // If the scorm packages has not a valid charset, i.e : UTF-16 we are displaying
    if ($has_valid_encoding === false) {
        $charset = api_get_system_encoding();
    }
} else {
    $charset = api_get_system_encoding();
}
if (empty($charset)) {
    $charset = 'ISO-8859-1';
}

/*
  -----------------------------------------------------------
  Constants and variables
  -----------------------------------------------------------
 */
$is_allowed_to_edit = api_is_allowed_to_edit(null, true);

$tbl_lp = Database::get_course_table(TABLE_LP_MAIN);
$tbl_lp_item = Database::get_course_table(TABLE_LP_ITEM);
$tbl_lp_view = Database::get_course_table(TABLE_LP_VIEW);

$isStudentView = (int) $_REQUEST['isStudentView'];
$learnpath_id = (int) $_REQUEST['lp_id'];
$submit = $_POST['submit_button'];
/*
  $chapter_id     = $_GET['chapter_id'];
  $title          = $_POST['title'];
  $description   = $_POST['description'];
  $Submititem     = $_POST['Submititem'];
  $action         = $_REQUEST['action'];
  $id             = (int) $_REQUEST['id'];
  $type           = $_REQUEST['type'];
  $direction      = $_REQUEST['direction'];
  $moduleid       = $_REQUEST['moduleid'];
  $prereq         = $_REQUEST['prereq'];
  $type           = $_REQUEST['type'];
 */
/*
  ==============================================================================
  MAIN CODE
  ==============================================================================
 */
// using the resource linker as a tool for adding resources to the learning path
if ($action == "add" and $type == "learnpathitem") {
    $htmlHeadXtra[] = "<script language='JavaScript' type='text/javascript'> window.location=\"../resourcelinker/resourcelinker.php?source_id=5&action=$action&learnpath_id=$learnpath_id&chapter_id=$chapter_id&originalresource=no\"; </script>";
}
if ((!$is_allowed_to_edit) or ($isStudentView)) {
    error_log('New LP - User not authorized in lp_admin_view.php');
    header('location:lp_controller.php?action=view&lp_id=' . $learnpath_id);
}
//from here on, we are admin because of the previous condition, so don't check anymore

$sql_query = "SELECT * FROM $tbl_lp WHERE id = $learnpath_id";
$result = Database::query($sql_query);
$therow = Database::fetch_array($result);

//$admin_output = '';
/*
  -----------------------------------------------------------
  Course admin section
  - all the functions not available for students - always available in this case (page only shown to admin)
  -----------------------------------------------------------
 */
/* ==================================================
  SHOWING THE ADMIN TOOLS
  ================================================== */



/* ==================================================
  prerequisites setting end
  ================================================== */
if (isset($_SESSION['gradebook'])) {
    $gradebook = $_SESSION['gradebook'];
}

if (!empty($gradebook) && $gradebook == 'view') {
    $interbreadcrumb[] = array(
        'url' => '../gradebook/' . $_SESSION['gradebook_dest'],
        'name' => get_lang('Gradebook')
    );
}

$interbreadcrumb[] = array("url" => "lp_controller.php?action=list", "name" => get_lang("_learning_path"));

$interbreadcrumb[] = array("url" => api_get_self() . "?action=admin_view&lp_id=$learnpath_id", "name" => stripslashes("{$therow['name']}"));

//Theme calls
$show_learn_path = true;
$lp_theme_css = $_SESSION['oLP']->get_theme();
Display::display_tool_header(null, 'Path');
//api_display_tool_title($therow['name']);

$suredel = trim(get_lang('AreYouSureToDelete'));
//$suredelstep = trim(get_lang('AreYouSureToDeleteSteps'));
?>
<script type='text/javascript'>
    /* <![CDATA[ */
    function stripslashes(str) {
        str=str.replace(/\\'/g,'\'');
        str=str.replace(/\\"/g,'"');
        str=str.replace(/\\\\/g,'\\');
        str=str.replace(/\\0/g,'\0');
        return str;
    }
    function confirmation(name)
    {
        name=stripslashes(name);
        if (confirm("<?php echo $suredel; ?> " + name + " ?"))
        {
            return true;
        }
        else
        {
            return false;
        }
    }
</script>
<script type="text/javascript" language="javascript">
      $(function(){
         $("<div id='hdnTypeSort'><input type='hidden' name='hdnType' value='Sortable'></div>").insertBefore("body");
         
         $( "#GalleryContainer" ).sortable({
            connectWith: "#GalleryContainer",
            stop: function(event) {
               $this=$(event.target);

               $("input[name='hdnItemOrder[]']").each(function(i){
                 $("input[name='hdnItemOrder[]']").eq(i).val(i+1);
               });

               var query = $("#hdnTypeSort input").add($this.find("input[name='hdnItemId[]']")).add($this.find("input[name='hdnItemOrder[]']")).serialize();                        
               $.ajax({
                  type: "GET",
                  url: "lp_ajax_order_items_scenario.php?"+query,
                  success: function(msg){}
              })                                                
            }
       });
       $( ".imageBox" ).addClass( "ui-widget ui-widget-content ui-helper-clearfix ui-corner-all" );
       $( "#GalleryContainer" ).disableSelection();
      });
   </script>
   
	<style>
	.column { width: 300px; float: left; padding-bottom: 100px; border: 1px solid red; }
	.portlet { margin: 0 1em 1em 0; width: 100px; float: left;}
	.portlet-header { margin: 0.3em; padding-bottom: 4px; padding-left: 0.2em; }
	.portlet-header .ui-icon { float: right; }
	.portlet-content { padding: 0.4em; }
	.ui-sortable-placeholder { border: 1px dotted black; visibility: visible !important; height: 160px !important; }
	.ui-sortable-placeholder * { visibility: hidden; }
	</style>
<?php
/*
  -----------------------------------------------------------
  DISPLAY SECTION
  -----------------------------------------------------------
 */

switch ($_GET['action']) {
    case 'edit_item':

        if (isset($is_success) && $is_success === true) {
            Display::display_confirmation_message(get_lang("_learnpath_item_edited"));
        } else {
            echo $_SESSION['oLP']->display_edit_item($_GET['id']);
        }

        break;

    case 'delete_item':

        if (isset($is_success) && $is_success === true) {
            Display::display_confirmation_message(get_lang("_learnpath_item_deleted"));
        }

        break;
}

// POST action handling (uploading mp3, deleting mp3)
if (isset($_POST['save_audio'])) {
    // deleting the audio fragments
    foreach ($_POST as $key => $value) {
        if (substr($key, 0, 9) == 'removemp3') {
            $lp_items_to_remove_audio[] = str_ireplace('removemp3', '', $key);
            // removing the audio from the learning path item
            $tbl_lp_item = Database::get_course_table(TABLE_LP_ITEM);
            $in = implode(',', $lp_items_to_remove_audio);
        }
    }
    if (count($lp_items_to_remove_audio) > 0) {
        $sql = "UPDATE $tbl_lp_item SET audio = '' WHERE id IN (" . $in . ")";
        $result = Database::query($sql, __FILE__, __LINE__);
    }

    // uploading the audio files
    foreach ($_FILES as $key => $value) {
        if (substr($key, 0, 7) == 'mp3file' AND !empty($_FILES[$key]['tmp_name'])) {
            // the id of the learning path item
            $lp_item_id = str_ireplace('mp3file', '', $key);

            // create the audio folder if it does not exist yet
            global $_course;
            $filepath = api_get_path('SYS_COURSE_PATH') . $_course['path'] . '/document/';
            if (!is_dir($filepath . 'audio')) {
                $perm = api_get_setting('permissions_for_new_directories');
                $perm = octdec(!empty($perm) ? $perm : '0770');
                mkdir($filepath . 'audio', $perm);
                $audio_id = add_document($_course, '/audio', 'folder', 0, 'audio');
                api_item_property_update($_course, TOOL_DOCUMENT, $audio_id, 'FolderCreated', api_get_user_id(), null, null, null, null, api_get_session_id());
            }

            // check if file already exits into document/audio/
            $file_name = $_FILES[$key]['name'];
            $file_name = stripslashes($file_name);
            //add extension to files without one (if possible)
            $file_name = add_ext_on_mime($file_name, $_FILES[$key]['type']);

            $clean_name = replace_dangerous_char($file_name);
            //no "dangerous" files
            $clean_name = disable_dangerous_file($clean_name);

            $check_file_path = api_get_path('SYS_COURSE_PATH') . $_course['path'] . '/document/audio/' . $clean_name;

            // if the file exists we generate a new name
            if (file_exists($check_file_path)) {
                $filename_components = explode('.', $clean_name);
                // gettting the extension of the file
                $file_extension = $filename_components[count($filename_components) - 1];
                // adding something random to prevent overwriting
                $filename_components[count($filename_components) - 1] = time();
                // reconstructing the new filename
                $clean_name = implode($filename_components) . '.' . $file_extension;
                // using the new name in the $_FILES superglobal
                $_FILES[$key]['name'] = $clean_name;
            }

            // upload the file in the documents tool
            include_once(api_get_path(LIBRARY_PATH) . 'fileUpload.lib.php');
            $file_path = handle_uploaded_document($_course, $_FILES[$key], api_get_path('SYS_COURSE_PATH') . $_course['path'] . '/document', '/audio', api_get_user_id(), '', '', '', '', '', false);

            // getting the filename only
            $file_components = explode('/', $file_path);
            $file = $file_components[count($file_components) - 1];

            // store the mp3 file in the lp_item table
            $tbl_lp_item = Database::get_course_table(TABLE_LP_ITEM);
            $sql_insert_audio = "UPDATE $tbl_lp_item SET audio = '" . Database::escape_string($file) . "' WHERE id = '" . Database::escape_string($lp_item_id) . "'";
            Database::query($sql_insert_audio, __FILE__, __LINE__);
        }
    }

    Display::display_confirmation_message(get_lang('ChangesStored'));
}

echo $_SESSION['oLP']->overview();



//Bottom bar
echo '<div class="actions">&nbsp;</div>';

/*
  ==============================================================================
  FOOTER
  ==============================================================================
 */

Display::display_footer();
?>
