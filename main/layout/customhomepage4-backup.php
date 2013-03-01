<style>
#coursehomepagecontainer {
	padding-left: 250px;   /* LC width */
	padding-right: 250px;  /* RC width */
}
#coursehomepagecontainer .location {
	position: relative;
	float: left;
	min-height:100px;
	min-width:100px;
}
#location1 {
	clear: both;
}
#location2 {
	width: 100%;
	/*border: 1px solid red;*/
}
#location3 {
	width: 250px;          /* LC width */
	right: 250px;          /* LC width */
	margin-left: -100%;
	max-width:250px;
	/*border: 1px solid green;*/
}
#location4 {
	width: 250px;          /* RC width */
	margin-right: -250px;  /* RC width */
	/*border: 1px solid blue;*/
}
#location5 {
	clear: both;
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
	<div id="location2" class="location widget" title="<?php echo get_lang('LocationMain');?>">
		<?php load_widgets('location2');?>  			
	</div>
	
	<div id="location3" class="location widget" title="<?php echo get_lang('LocationSidebarLeft');?>">
		<?php load_widgets('location3');?> 
		<?php 
		if (api_is_allowed_to_edit()) {
			load_configuration_widget();
		}
		load_user_widget();
		?> 
	</div>
	
	
	<div id="location4" class="location widget" title="<?php echo get_lang('LocationSidebarRight');?>">
		<?php load_widgets('location4');?>                  
	</div>
</div>

<div id="location5" class="location widget" title="<?php echo get_lang('LocationFooter');?>"><?php load_widgets('location5');?></div>
