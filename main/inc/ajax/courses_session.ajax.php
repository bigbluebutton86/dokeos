<?php

require_once('../global.inc.php');


switch ($_GET['action']) {
 case 'changeCourseSessionOrder' :
  if (api_is_allowed_to_edit ()) {
   $output = changeCourseSessionOrder($_GET['disporder']);
  }
  break;
}

/**
 * Allow reorder the question list using Drag and drop
 * @author Breetha Mohan <breetha.mohan@dokeos.com>
 * @param array $disporder 
 * @return boolean true if success
 */
function changeCourseSessionOrder($disporder) {   
    $tbl_session_rel_course = Database::get_main_table(TABLE_MAIN_SESSION_COURSE);   
    $disparr = explode(",", $disporder);
    echo $len = sizeof($disparr);
    $listingCounter = 1;
    for ($i = 0; $i < sizeof($disparr); $i++) {
        list($sess_id, $course_code) = explode('|', $disparr[$i]);
        $sql = "UPDATE $tbl_session_rel_course SET position = ".$listingCounter." WHERE id_session = $sess_id AND course_code = '$course_code'; ";        
        echo $sql;
        $res = Database::query($sql, __FILE__, __LINE__);
        $listingCounter++;
    }  
    return true;  
}

?>
