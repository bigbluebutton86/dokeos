<?php // $Id: slideshowoptions.php 22565 2009-08-02 21:12:59Z yannoo $

/* For licensing terms, see /dokeos_license.txt */

/**
==============================================================================
* 	This is a plugin for the documents tool. It looks for .jpg, .jpeg, .gif, .png
* 	files (since these are the files that can be viewed in a browser) and creates
* 	a slideshow with it by allowing to go to the next/previous image.
* 	You can also have a quick overview (thumbnail view) of all the images in
* 	that particular folder.
* 	Maybe it is important to notice that each slideshow is folder based. Only
* 	the images of the chosen folder are shown.
* 
* 	On this page the options of the slideshow can be set: maintain the original file
* 	or resize the file to a given width.
* 
*	@author Patrick Cool
*	@package dokeos.document
==============================================================================
*/

// name of the language file that needs to be included
$language_file = array ('slideshow', 'document');

// include the global Dokeos file
require_once '../inc/global.inc.php';

$path = Security::remove_XSS($_GET['curdirpath']);
$pathurl = urlencode($path);

// breadcrumb navigation
$url="document.php?curdirpath=".$pathurl;
$originaltoolname=get_lang('Documents');
$interbreadcrumb[]= array ("url"=>$url, "name"=>$originaltoolname );

$url="slideshow.php?curdirpath=".$pathurl;
$originaltoolname=get_lang('SlideShow');
$interbreadcrumb[]= array ("url"=>$url, "name"=>$originaltoolname );

// because $nametools uses $_SERVER['PHP_SELF'] for the breadcrumbs instead of $_SERVER['REQUEST_URI'], I had to
// bypass the $nametools thing and use <b></b> tags in the $interbreadcrump array
$url="slideshowoptions.php?curdirpath=".$pathurl;
$originaltoolname="<b>".get_lang('_slideshow_options')."</b>";
$interbreadcrumb[]= array ("url"=>$url, "name"=>$originaltoolname );

Display::display_tool_header($originalToolName,"Doc");

// can't remember why I put this here. This is probably obsolete code
// loading the slides from the session
//$image_files_only = $_SESSION["image_files_only"];

// calculating the current slide, next slide, previous slide and the number of slides
/*if ($slide_id)
	{
	$slide=$slide_id;
	}
else
	{
	$slide=0;
	}
$previous_slide=$slide-1;
$next_slide=$slide+1;
$total_slides=count($image_files_only);
*/
?>
<style type="text/css">
<!--
.disabled_input {
	background-color: #cccccc;
}
.enabled_input {
	background-color: #ffffff;
}
-->
</style>

<script language="JavaScript" type="text/JavaScript">
<!--


function enableresizing() { //v2.0
	document.options.width.disabled=false;
	document.options.width.className='enabled_input';
	document.options.height.disabled=false;
	document.options.height.className='enabled_input';
}
function disableresizing() { //v2.0
	document.options.width.disabled=true;
	document.options.width.className='disabled_input';
	document.options.height.disabled=true;
	document.options.height.className='disabled_input';
}

window.onload = <?php echo $_SESSION['image_resizing'] == 'resizing' ? 'enableresizing' : 'disableresizing'; ?>;

//-->
</script>


<?php
echo '<div class="actions">';
echo '<a href="document.php?action=exit_slideshow&curdirpath='.$pathurl.'">'.Display::return_icon('back.png').get_lang('BackTo').' '.get_lang('DocumentsOverview').'</a>';
echo '<a href="slideshow.php?curdirpath='.$pathurl.'">'.Display::return_icon('images_gallery.gif').get_lang('BackTo').' '.get_lang('SlideShow').'</a>';
echo '</div>';
?>

<form action="slideshow.php?curdirpath=<?php echo $pathurl; ?>" method="post" name="options" id="options">

	<div class="row"><div class="form_header"><?php echo get_lang('_slideshow_options') ?></div></div>

	<div class="row">
		<div class="label">
			<input class="checkbox" name="radio_resizing" type="radio" onClick="disableresizing()" value="noresizing"
	  <?php
	  $image_resizing=$_SESSION["image_resizing"];
	  if($image_resizing=="noresizing" or $image_resizing=="")
	  	{
		echo " checked";
		}
			?>>
		</div>
		<div class="formw"><?php echo get_lang('_no_resizing');?><br /><?php echo get_lang('_no_resizing_comment');?>
		</div>
	</div>



	<div class="row">
		<div class="label">
			<input class="checkbox" name="radio_resizing" type="radio" onClick="enableresizing()" value="resizing"
	  <?php
	  $image_resizing=$_SESSION["image_resizing"];
	  if($image_resizing=="resizing")
	  	{
		echo " checked";
		$width=$_SESSION["image_resizing_width"];
		$height=$_SESSION["image_resizing_height"];
		}
			?>>
		</div>
		<div class="formw">
			<?php echo get_lang('_resizing');?><br /><?php echo get_lang('_resizing_comment');?><br />
        <?php echo get_lang('_width');?>:
	    &nbsp;<input name="width" type="text" id="width"
		<?php
		if ($image_resizing=="resizing") {
			echo " value='".$width."'";
			echo " class=\"enabled_input\"";
	    } else {
            echo " class=\"disabled_input\"";
        }
		?> >
        <br />
        <?php echo get_lang('_height');?>:
        &nbsp;&nbsp;&nbsp;&nbsp;<input name="height" type="text" id="height"
		<?php
		if ($image_resizing=="resizing") {
			echo " value='".$height."'";
			echo " class=\"enabled_input\"";
		} else {
            echo " class=\"disabled_input\"";
        }
		?> >
		</div>
	</div>
	<div class="row">
		<div class="label">
		</div>
		<div class="formw">
			<br />
			<button type="submit" class="save" name="Submit" value="Save" ><?php echo get_lang('Save'); ?></button>
		</div>
	</div>
</form>
<?php
Display::display_footer();
