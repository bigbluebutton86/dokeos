<?php
/* For licensing terms, see /dokeos_license.txt */

/**
==============================================================================
*	@package dokeos.document
==============================================================================
 */

// Language files that should be included
$language_file = array('document');

// setting the help
$help_content = 'mediabox';

// include the global Dokeos file
include ('../inc/global.inc.php');

// section (for the tabs)
$this_section = SECTION_COURSES;

// variable initialisation
$_SESSION['whereami'] = 'document/create';
$path = Security::remove_XSS($_GET['curdirpath']);
$pathurl = urlencode($path);
$imagepath = '/images';
$photopath = '/photos';
$mindmappath = '/mindmaps';
$mascotpath = '/mascot';
$audiopath = '/audio';
$videopath = '/video';
$podcastpath = '/podcasts';
$screenpath = '/screencasts';
$animationpath = '/animations';

// setting the breadcrumbs
$interbreadcrumb[] = array ("url" => Security::remove_XSS("document.php?curdirpath=".$pathurl), "name" => get_lang('Documents'));

$htmlHeadXtra[] =
"<script type=\"text/javascript\">
function confirmation (name) {
	if (confirm(\" ". get_lang("AreYouSureToDelete") ." \"+ name + \" ?\"))
		{return true;}
	else
		{return false;}
}
</script>";

// display the header
Display :: display_tool_header(get_lang('Mediabox'));

// actions
echo '<div class="actions">' . PHP_EOL;
echo '<a href="document.php?'.api_get_cidReq().'">'.Display::return_icon('pixel.gif', get_lang('Documents'), array('class' => 'toolactionplaceholdericon toolactionback')).' '.get_lang('Documents').'</a>' . PHP_EOL;
echo '<a href="mediabox.php?'.api_get_cidreq().'&curdirpath='.$path.'">'.Display::return_icon('pixel.gif', get_lang('Mediabox'), array('class' => 'toolactionplaceholdericon toolactionmediabox')).' '.get_lang('Mediabox').'</a>';
echo '</div>';

// start the content div
echo '<div id="content">' . PHP_EOL;

$commonCssClasses = "big_button three_buttons rounded grey_border";

// Image page
$href = 'slideshow.php?'.api_get_cidReq().'&slide_id=all&curdirpath='.urlencode($imagepath);
$return .= '<a href="'.$href . '" class="'.$commonCssClasses.' create_image_button">' . get_lang("Images") . '</a>';
// Photos page
$href = 'slideshow.php?'.api_get_cidReq().'&slide_id=all&curdirpath='.urlencode($photopath);
$return .= '<a href="'.$href . '" class="'.$commonCssClasses.' create_photos_button">' . get_lang("Photos") . '</a>';
// Mascot page
$href = 'slideshow.php?'.api_get_cidReq().'&slide_id=all&curdirpath='.urlencode($mascotpath);
$return .= '<a href="'.$href . '" class="'.$commonCssClasses.' create_mascot_button">' . get_lang("Mascot") . '</a>';
// Audio page
$href = 'mediabox_view.php?'.api_get_cidReq().'&slide_id=all&curdirpath='.urlencode($audiopath);
$return .= '<a href="'.$href . '" class="'.$commonCssClasses.' create_audio_button">' . get_lang("Audio") . '</a>';
// Video page
$href = 'mediabox_view.php?'.api_get_cidReq().'&slide_id=all&curdirpath='.urlencode($videopath);
$return .= '<a href="'.$href . '" class="'.$commonCssClasses.' create_video_button">' . get_lang("Video") . '</a>';
// Podcasts page
$href = 'mediabox_view.php?'.api_get_cidReq().'&slide_id=all&curdirpath='.urlencode($podcastpath);
$return .= '<a href="'.$href . '" class="'.$commonCssClasses.' create_podcast_button">' . get_lang("Podcasts") . '</a>';
// Screencast page
$href = 'mediabox_view.php?'.api_get_cidReq().'&slide_id=all&curdirpath='.urlencode($screenpath);
$return .= '<a href="'.$href . '" class="'.$commonCssClasses.' create_screencast_button">' . get_lang("Screencasts") . '</a>';
// Animation page
$href = 'mediabox_view.php?'.api_get_cidReq().'&slide_id=all&curdirpath='.urlencode($animationpath);
$return .= '<a href="'.$href . '" class="'.$commonCssClasses.' create_animation_button">' . get_lang("Animations") . '</a>';
//	Mindmaps page
$href = 'slideshow.php?'.api_get_cidReq().'&slide_id=all&curdirpath='.urlencode($mindmappath);
$return .= '<a href="'.$href . '" class="'.$commonCssClasses.' create_mindmap_button">' . get_lang("Mindmaps") . '</a>';

/*if (api_get_setting('enable_certificate') === 'true') {
	$href = 'certificate.php?'.api_get_cidReq();
	$return .= '<a href="'.$href . '" class="'.$commonCssClasses.' create_certificate_button">' . get_lang("Certificate") . '</a>';
}
else {
	$href = 'slideshow.php?'.api_get_cidReq().'&slide_id=all&curdirpath='.urlencode($mindmappath);
	$return .= '<a href="'.$href . '" class="'.$commonCssClasses.' create_mindmap_button">' . get_lang("Mindmaps") . '</a>';
}*/
echo $return;

// close the content div
echo '</div>';

// bottom actions bar
echo '<div class="actions">';
echo '</div>';

// display the footer
Display::display_footer();
?>
