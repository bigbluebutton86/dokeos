<?php 
// including the widgets language file
$language_file = array ('widgets');

// include the global Dokeos file
include_once('../../inc/global.inc.php');

// load the specific widget settings
api_load_widget_settings();

//action handling
switch ($_POST['action']) {
	case 'get_widget_information':
		agenda_get_information();
		break;
	case 'get_widget_content':
		agenda_get_content();
		break;			
}
switch ($_GET['action']) {
	case 'get_widget_information':
		agenda_get_information();
		break;
	case 'get_widget_content':
		agenda_get_content();
		break;
	case 'get_widget_title':
		agenda_get_title();
		break;				
}

/**
 * This function determines if the widget can be used inside a course, outside a course or both
 * 
 * @return array 
 * @version Dokeos 1.9
 * @since January 2010
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University, Belgium
 */
function agenda_get_scope(){
	return array('course', 'platform');
}

function agenda_get_content(){
?>
<link rel='stylesheet' type='text/css' href='<?php echo api_get_path(WEB_CODE_PATH); ?>course_home/css/fullcalendar.css' />
<script type='text/javascript' src='<?php echo api_get_path(WEB_CODE_PATH); ?>course_home/js/fullcalendar.min.js '></script>
<script>
	$(document).ready(function() {
	
		var date = new Date();
		var d = date.getDate();
		var m = date.getMonth();
		var y = date.getFullYear();
		
		$('#widget_agenda .portlet-content').fullCalendar({
			header: {
				left: 'prev,next',
				center: '',
				right: 'month,agendaWeek,agendaDay'
			},
			weekMode: 'variable',
			allDaySlot: false,
			editable: true,
			axisFormat: 'HH(:mm)',
			timeFormat: 'HH:mm{ - HH:mm}',
			events: "<?php echo api_get_path(WEB_CODE_PATH); ?>widgets/agenda/events.php"
		});
		
	});

</script>
<div id='calendar'></div>
<?php
}

function agenda_get_title($param1, $original_title=false) {
	$config_title = api_get_setting('agenda', 'title');
	if (!empty($config_title) AND $original_title==false){
		return $config_title;
	} else {
		return get_lang('Agenda');
	}
}

function agenda_get_information(){
	echo '<span style="float:right;">';
	agenda_get_screenshot();
	echo '</span>';		
	echo get_lang('AgendaExplanation')	;
}
function agenda_get_screenshot(){
	echo '<img src="'.api_get_path(WEB_PATH).'main/widgets/agenda/screenshot.jpg" alt="'.get_lang('WidgetScreenshot').'"/>';
}


function agenda_settings_form(){
}
?>
