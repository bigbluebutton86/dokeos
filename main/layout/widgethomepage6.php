<style>
#coursehomepagecontainer {
	float: left;
	width: 100%;
}
#location1 {
	clear: both;
}
#location2 {
	margin-right: 500px; /*Set right margin to (LeftColumnWidth + RightColumnWidth)*/
}
#location3 {
	float: left;
	width: 250px; /*Width of left column in percentage*/
	margin-left: -500px; /*Set left margin to -(LeftColumnWidth + RightColumnWidth)*/
	max-width:250px;
}
#location4 {
	float: left;
	width: 250px; /*Width of right column in pixels*/
	margin-left: -250px; /*Set margin to -(RightColumnWidth)*/
}
#location5 {
	clear: left;
}
	
/*** IE6 Fix ***/
* html #coursehomepageleft {
	left: 250px;           /* RC width */
}  
	
.portlet { margin: 0 1em 1em 0; }
.portlet-header { margin: 0.3em; padding-bottom: 4px; padding-left: 0.2em; }
.portlet-header .ui-icon { float: right; }
.portlet-content { padding: 0.4em; }
.ui-sortable-placeholder { border: 1px dotted black; visibility: visible !important; height: 50px !important; }
.ui-sortable-placeholder * { visibility: hidden; }
</style>
	
<div id="location1" class="location widget" title="<?php echo get_lang('LocationHeader');?>">
	<?php load_widgets('location1');?>  
</div>
    
<div id="coursehomepagecontainer">
	<div id="location2" class="location widget"  title="<?php echo get_lang('LocationMain');?>">
		<?php load_widgets('location2');?>  	
		<?php 
		if (api_is_allowed_to_edit()) {
			load_configuration_widget();
		}
		load_user_widget();
		?> 
	&nbsp;
	</div>
</div>  
	  
<div id="location3" class="location widget" title="<?php echo get_lang('LocationSidebarRight');?>">
	<?php load_widgets('location3');?>               
</div>

<div id="location4" class="location widget" title="<?php echo get_lang('LocationSidebarExtremeRight');?>">
	<?php load_widgets('location4');?>
</div>

<div id="location5" class="location widget" title="<?php echo get_lang('LocationFooter');?>">
	<?php load_widgets('location5');?>
</div>
