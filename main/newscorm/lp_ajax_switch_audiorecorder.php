<?php
/* For licensing terms, see /dokeos_license.txt */

/**
* 	Learning Path
*	This script allow switch audiorecorder for each item
*	@package dokeos.learnpath
*/
require_once('../inc/global.inc.php');
$output = '';
if (api_get_setting('audio_recorder') == 'true') {
    require_once api_get_path(SYS_CODE_PATH).'inc/lib/audiorecorder/audiorecorder_conf.php';
     // get audio     
     $dialog = '';
     $action        = Security::remove_XSS($_GET['action']);
     $lp_id         = intval($_GET['lp_id']);
     $current_item  = intval($_GET['lp_item_id']);
     $item_info     = get_lp_item_info($current_item);
     $audio_name    = $item_info['audio'];
     $web_title     = $item_info['title'];
     $item_title    = trim($web_title);
     // Unique ID for avoid use the same FLV video for create MP3 files
     $item_title = $item_title.'_hash_'.uniqid('',true);
     $item_title = str_replace(array(' ','.'),'_', $item_title);
     if (!empty($audio_name)) {        
        $image_mp3 = 'sound_mp3.png';
        $event_click = 'deleteSoundMp3(' . $lp_id . ', \'' . $action . '\',\'' . api_get_course_id() . '\',' . $current_item . ',\'' . $audio_name . ' \'  );';
    } else {
        
        $event_click = 'recordDialog('.$current_item.');';
        $image_mp3 = 'record_mp3.png';
        $dialog .= '<div id="record' . $current_item . '" title="'.$web_title.'" style="display: none">';
        $dialog .= '<p>';

        // AUDIO RECORDER
        //if ($audiorecorder == 1) {                        
            $action = Security::remove_XSS($_GET['action']);
            $lp_id = Security::remove_XSS($_GET['lp_id']);                       
            $dialog .='<script type="text/javascript" src="'.api_get_path(WEB_LIBRARY_PATH).'audiorecorder/js/audiorecorder.js"></script>';
            $dialog .='<script type="text/javascript" src="'.api_get_path(WEB_LIBRARY_PATH).'audiorecorder/js/flashplayer.js"></script>';
            $dialog .='<link rel="stylesheet" href="'.api_get_path(WEB_LIBRARY_PATH).'audiorecorder/css/audiorecorder.css" type="text/css" media="projection, screen">';
            $dialog .='<script type="text/javascript" src="'.api_get_path(WEB_LIBRARY_PATH).'audiorecorder/audiorecorder.php?'.api_get_cidreq().'&id='.$current_item.'&action=' . $action . '&lp_id=' . $lp_id . '&title='.$item_title.'"></script>';

            $dialog .= '<object classid="clsid:D27CDB6E-AE6D-11cf-96B8-444553540000"
                                id="audioRecorder" width="600" height="300"
                                codebase="http://fpdownload.macromedia.com/get/flashplayer/current/swflash.cab">';

            $dialog .= '<param name="movie" value="'.api_get_path(WEB_LIBRARY_PATH).'audiorecorder/swf/audioRecorder.swf" />';
            $dialog .= '<param name="quality" value="high" />
                                <param name="bgcolor" value="#869ca7" />
                                <param name="allowScriptAccess" value="sameDomain" />';
            $dialog .= '<param name="flashvars" value="myServer=rtmp://' . $url['host'] . '/oflaDemo&amp;timeLimit=' . $time_limit . '&amp;urlDokeos=' . api_get_path(WEB_PATH) . 'main/document/upload_audio.php&mySound='.str_replace('','_',$item_title).'">';
            $dialog .= '<embed src="'.api_get_path(WEB_LIBRARY_PATH).'audiorecorder/swf/audioRecorder.swf" quality="high" bgcolor="#869ca7"
                                    width="600" height="300" name="audioRecorder" align="middle"
                                    play="true"
                                    loop="false"
                                    quality="high"
                                    allowScriptAccess="sameDomain"
                                    type="application/x-shockwave-flash"
                                    flashvars="myServer=rtmp://' . $url['host'] . '/oflaDemo&amp;timeLimit=' . $time_limit . '&amp;urlDokeos=' . api_get_path(WEB_PATH) . 'main/document/upload_audio.php&mySound='.str_replace('','_',$item_title).'"
                                    pluginspage="http://www.adobe.com/go/getflashplayer"> 
                                 </embed>
                                 </object>';      
            
            $dialog .= '</p>';
            $dialog .= '</div>';
    }
}

$output .= '<a href="javascript:void(0)" onclick="'.$event_click.'" ><img style="padding-left:22px;" src="../img/' . $image_mp3 . '"></a>';
$output .= $dialog;
echo $output;

/**
* Get audio by item 
*/
function get_lp_item_info($item_id) {
    $tbl_lp_item = Database :: get_course_table(TABLE_LP_ITEM);
    $info = '';
    $rs = Database::query("SELECT * FROM $tbl_lp_item WHERE id = ".intval($item_id));
    if (Database::num_rows($rs) > 0) {            
        $row = Database::fetch_array($rs, 'ASSOC');
        $info = $row;
    }
    return $info;
}

?>
