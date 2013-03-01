<?php
/* For licensing terms, see /dokeos_license.txt */

/**
* @package dokeos.main
*/

// Language files that should be included
$language_file = array ('courses', 'index', 'widgets');

// forcing the 'current course' reset, as we're not inside a course anymore
$cidReset = true; 

// global Dokeos file
require_once './main/inc/global.inc.php';

// the section (for the tabs)
$this_section = SECTION_COURSES;

// Check if we have a CSS with tablet support
$css_info = array();
$css_info = api_get_css_info();
$css_type = !is_null($css_info['type']) ? $css_info['type'] : '';

if (api_get_setting('portal_view') == 'widget'){
	require_once 'user_portal_widget.php';
} elseif ($css_type == 'tablet') { // User portal for the tablet
	require_once 'tablet_user_portal.php';	
}else {
	require_once 'user_portal_classic.php';	
}
?>
