<?php

/* For licensing terms, see /dokeos_license.txt */


/**
==============================================================================
*	@package dokeos.calendar
==============================================================================
*/

// google calendar import
if (api_get_setting('calendar_google_import')=='true'){
	require_once ('google.inc.php');	
}

/**
 * Display the links that appear in the action bar in the course calendar
 *
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University, Belgium
 * @version june 2010
 */
function calendar_actions() {
	global $_course, $_user;

	// Additional libraries
	require_once(api_get_path(LIBRARY_PATH).'groupmanager.lib.php');

	$return = '';

	// if group members can add events we have to retrieve the groups of the user
	if (api_get_setting('user_manage_group_agenda')=='true'){
		$group_memberships		= GroupManager::get_group_ids($_course['dbName'],$_user['user_id']);
	}

	//if (api_get_setting('calendar_navigation') == 'actions' AND $_GET['action'] <> 'detail' AND $_GET['action'] <> 'edit' AND $_GET['action'] <> 'add'){
        if ((api_get_setting('calendar_navigation') == 'actions' && $_GET['op'] == 'new') || !isset($_GET['action'])){
		// javascript for the buttons in the actions bar
		$return .= '<script type="text/javascript">
					$(document).ready(function() {
						var d = $("#calendar").fullCalendar("getDate");

						var view = $("#calendar").fullCalendar("getView");
						$("#my-title").html(view.title);

						// default view
						$(".fc-button-'.api_get_setting('agenda_default_view').'").addClass("fc-state-active");


						// previous
						$("#my-prev-button").click(function() {
                            //move to previous month/week/day
							$("#calendar").fullCalendar("prev");
							view = $("#calendar").fullCalendar("getView");
							// make the today button active
							$("#my-today-button").removeClass("fc-state-disabled");

							// update the title
							$("#my-title").html(view.title);
						});

						// next
						$("#my-next-button").click(function() {
							//move to next month/week/day
							$("#calendar").fullCalendar("next");
							view = $("#calendar").fullCalendar("getView");
							// make the today button active
							$("#my-today-button").removeClass("fc-state-disabled");

							// update the title
							$("#my-title").html(view.title);
						});

						// today
						$("#my-today-button").click(function() {
							// move to today
							$("#calendar").fullCalendar("today");
							view = $("#calendar").fullCalendar("getView");
							// make the today button inactive
							$("#my-today-button").addClass("fc-state-disabled");

							// update the title
							$("#my-title").html(view.title);
						});

						// month view
						$("#my-month-view").click(function() {
							// change view to month view
							$("#calendar").fullCalendar("changeView","month");
							view = $("#calendar").fullCalendar("getView");
							// make all the views inactive except the one clicked
							$(".myview").removeClass("fc-state-active");
							$("#my-month-view").addClass("fc-state-active");

							// update the title
							$("#my-title").html(view.title);
						});

						// week view
						$("#my-week-view").click(function() {
							// change view to month view
							$("#calendar").fullCalendar("changeView","agendaWeek");
							view = $("#calendar").fullCalendar("getView");
							// make all the views inactive except the one clicked
							$(".myview").removeClass("fc-state-active");
							$("#my-week-view").addClass("fc-state-active");

							// update the title
							$("#my-title").html(view.title);
						});

						// day view
						$("#my-day-view").click(function() {
							// change view to month view
							$("#calendar").fullCalendar("changeView","agendaDay");
							view = $("#calendar").fullCalendar("getView");
							// make all the views inactive except the one clicked
							$(".myview").removeClass("fc-state-active");
							$("#my-day-view").addClass("fc-state-active");

							// update the title
							$("#my-title").html(view.title);
						});
					});
					</script>
					';
              // the 	buttons in the actions bar
            $return .= '<table border="0" id="my-navigation">';
            $return .= '<tr>';
            $return .= '<td width="50%" id="my-fc-header-center">';
            $return .= '			<center><h2 id="my-title" class="fc-header-title"></h2></center>';
            $return .= '</td>';
            $return .= '<td width="25%">';
            $return .= '	<table border="0" id="my-fc-header-left" >';
            $return .= '		<tr>';
            $return .= '			<td><div id="my-prev-button" class="fc-button-prev fc-state-default fc-corner-left fc-no-right"><a><span>&nbsp;&#9668;&nbsp;</span></a></div></td>';
            $return .= '			<td><div id="my-next-button" class="fc-button-next fc-state-default fc-corner-right"><a><span>&nbsp;&#9658;&nbsp;</span></a></div></td>';
            $return .= '			<td><span class="fc-header-space"></span></td>';
            $return .= '			<td><div id="my-today-button" class="fc-button-today fc-state-default fc-corner-left fc-corner-right fc-state-disabled"><a><span>'.get_lang('Today').'</span></a></div></td>';
            $return .= '		</tr>';
            $return .= '	</table>';
            $return .= '</td>';
            $return .= '<td width="25%">';
            $return .= '	<table border="0" id="my-fc-header-right">';
            $return .= '		<tr>';
            $return .= '			<td><div id="my-month-view" class="myview fc-button-month fc-state-default fc-corner-left fc-no-right"><a><span>'.get_lang('MonthView').'</span></a></div></td>';
            $return .= '			<td><div id="my-week-view" class="myview fc-button-agendaWeek fc-state-default fc-no-right"><a><span>'.get_lang('WeekView').'</span></a></div></td>';
            $return .= '			<td><div id="my-day-view" class="myview fc-button-agendaDay fc-state-default fc-corner-right"><a><span>'.get_lang('DayView').'</span></a></div></td>';
            $return .= '		</tr>';
            $return .= '	</table>';
            $return .= '</td>';
            $return .= '</table>';
	}
        

	switch ($_GET ['action']) {
		case 'add' :
			// return to the calendar display
			if (!isset($_POST['submit_agenda_item'])) {
				$return .= '<a href="agenda.php?'.api_get_cidreq().'">'.Display::return_icon('pixel.gif', get_lang('ReturnToCalendar'), array('class' => 'toolactionplaceholdericon toolactionback')).get_lang('ReturnToCalendar').'</a>';
			}
			$return .= '<a href="agenda.php?'.api_get_cidreq().'&action=add">'.Display::return_icon('pixel.gif', get_lang('AgendaAdd'), array('class' => 'toolactionplaceholdericon toolcalendaraddevent')).get_lang('AgendaAdd').'</a>';
			break;
		case 'edit' :
			// return to the calendar display
			if (!isset($_POST['submit_agenda_item'])) {
				$return .= '<a href="agenda.php?'.api_get_cidreq().'">'.Display::return_icon('pixel.gif', get_lang('ReturnToCalendar'), array('class' => 'toolactionplaceholdericon toolactionback')).get_lang('ReturnToCalendar').'</a>';
			}
			// adding of event
			if (api_is_allowed_to_edit(false,true) OR (api_get_course_setting('allow_user_edit_agenda') && !api_is_anonymous() && api_is_allowed_to_session_edit(false,true) ) OR (api_get_setting('user_manage_group_agenda')=='true' AND !empty($group_memberships))){
				$return .= '<a href="agenda.php?'.api_get_cidreq().'&action=add">'.Display::return_icon('pixel.gif', get_lang('AgendaAdd'), array('class' => 'toolactionplaceholdericon toolcalendaraddevent')).get_lang('AgendaAdd').'</a>';
			}
			break;
		case 'detail' :
			// return to the calendar display
			$return .= '<a href="agenda.php?'.api_get_cidreq().'">'.Display::return_icon('pixel.gif', get_lang('ReturnToCalendar'), array('class' => 'toolactionplaceholdericon toolactionback')).get_lang('ReturnToCalendar').'</a>';
                        break;
		case 'delete' :
			// return to the calendar display
			$return .= '<a href="agenda.php?'.api_get_cidreq().'">'.Display::return_icon('pixel.gif', get_lang('ReturnToCalendar'), array('class' => 'toolactionplaceholdericon toolactionback')).get_lang('ReturnToCalendar').'</a>';
		case '':
			// adding of event
			if (api_is_allowed_to_edit(false,true) OR (api_get_course_setting('allow_user_edit_agenda') && !api_is_anonymous() && api_is_allowed_to_session_edit(false,true) ) OR (api_get_setting('user_manage_group_agenda')=='true' AND !empty($group_memberships))){
				$return .= '<a href="agenda.php?'.api_get_cidreq().'&action=add">'.Display::return_icon('pixel.gif', get_lang('AgendaAdd'), array('class' => 'toolactionplaceholdericon toolcalendaraddevent')).get_lang('AgendaAdd').'</a>';
			}
			break;
	}
	// export of all events
	if (api_get_setting('calendar_export_all')=='true'){
		$return .= '<a href="agenda.php?action=export">'.Display::return_icon('pixel.gif', get_lang('IcalExport'), array('class' => 'toolactionplaceholdericon toolcalendaricalexport')).get_lang('IcalExport').'</a>';
	}
	if (api_get_setting('calendar_google_import')=='true'){
		$return .= google_calendar_action_link();
	}
	return $return;
}

/**
 * Display the links that appear in the action bar in the "My calendar"
 *
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University, Belgium
 * @version june 2010
 */
function mycalendar_actions() {
	$return = '';

	if (api_get_setting('calendar_navigation') == 'actions'){
		// javascript for the buttons in the actions bar
		$return .= '<script type="text/javascript">
					$(document).ready(function() {
						var d = $("#calendar").fullCalendar("getDate");

						var view = $("#calendar").fullCalendar("getView");
						$("#my-title").html(view.title);

						// default view
						$(".fc-button-'.api_get_setting('agenda_default_view').'").addClass("fc-state-active");


						// previous
						$("#my-prev-button").click(function() {
							//move to previous month/week/day
							$("#calendar").fullCalendar("prev");
                            view = $("#calendar").fullCalendar("getView");
							// make the today button active
							$("#my-today-button").removeClass("fc-state-disabled");

							// update the title
							$("#my-title").html(view.title);
						});

						// next
						$("#my-next-button").click(function() {
							//move to next month/week/day
							$("#calendar").fullCalendar("next");
                            view = $("#calendar").fullCalendar("getView");
							// make the today button active
							$("#my-today-button").removeClass("fc-state-disabled");

							// update the title
							$("#my-title").html(view.title);
						});

						// today
						$("#my-today-button").click(function() {
							// move to today
							$("#calendar").fullCalendar("today");
                            view = $("#calendar").fullCalendar("getView");
							// make the today button inactive
							$("#my-today-button").addClass("fc-state-disabled");

							// update the title
							$("#my-title").html(view.title);
						});

						// month view
						$("#my-month-view").click(function() {
							// change view to month view
							$("#calendar").fullCalendar("changeView","month");
                            view = $("#calendar").fullCalendar("getView");
							// make all the views inactive except the one clicked
							$(".myview").removeClass("fc-state-active");
							$("#my-month-view").addClass("fc-state-active");

							// update the title
							$("#my-title").html(view.title);
						});

						// week view
						$("#my-week-view").click(function() {
							// change view to month view
							$("#calendar").fullCalendar("changeView","agendaWeek");
                            view = $("#calendar").fullCalendar("getView");
							// make all the views inactive except the one clicked
							$(".myview").removeClass("fc-state-active");
							$("#my-week-view").addClass("fc-state-active");

							// update the title
							$("#my-title").html(view.title);
						});

						// day view
						$("#my-day-view").click(function() {
							// change view to month view
							$("#calendar").fullCalendar("changeView","agendaDay");
                            view = $("#calendar").fullCalendar("getView");
							// make all the views inactive except the one clicked
							$(".myview").removeClass("fc-state-active");
							$("#my-day-view").addClass("fc-state-active");

							// update the title
							$("#my-title").html(view.title);
						});

					});
					</script>
					';


		// the 	buttons in the actions bar
                if (!in_array($_GET['action'], array('myadd', 'myedit'))) {
                    $return .= '<table border="0" id="my-navigation">';
                    $return .= '<tr>';
                    $return .= '<td width="50%" id="my-fc-header-center">';
                    $return .= '			<center><h2 id="my-title" class="fc-header-title"></h2></center>';
                    $return .= '</td>';
                    $return .= '<td width="25%">';
                    $return .= '	<table border="0" id="my-fc-header-left" >';
                    $return .= '		<tr>';
                    $return .= '			<td><div id="my-prev-button" class="fc-button-prev fc-state-default fc-corner-left fc-no-right"><a><span>&nbsp;&#9668;&nbsp;</span></a></div></td>';
                    $return .= '			<td><div id="my-next-button" class="fc-button-next fc-state-default fc-corner-right"><a><span>&nbsp;&#9658;&nbsp;</span></a></div></td>';
                    $return .= '			<td><span class="fc-header-space"></span></td>';
                    $return .= '			<td><div id="my-today-button" class="fc-button-today fc-state-default fc-corner-left fc-corner-right fc-state-disabled"><a><span>'.get_lang('Today').'</span></a></div></td>';
                    $return .= '		</tr>';
                    $return .= '	</table>';
                    $return .= '</td>';
                    $return .= '<td width="25%">';
                    $return .= '	<table border="0" id="my-fc-header-right">';
                    $return .= '		<tr>';
                    $return .= '			<td><div id="my-month-view" class="myview fc-button-month fc-state-default fc-corner-left fc-no-right"><a><span>'.get_lang('MonthView').'</span></a></div></td>';
                    $return .= '			<td><div id="my-week-view" class="myview fc-button-agendaWeek fc-state-default fc-no-right"><a><span>'.get_lang('WeekView').'</span></a></div></td>';
                    $return .= '			<td><div id="my-day-view" class="myview fc-button-agendaDay fc-state-default fc-corner-right"><a><span>'.get_lang('DayView').'</span></a></div></td>';
                    $return .= '		</tr>';
                    $return .= '	</table>';
                    $return .= '</td>';
                    $return .= '</table>';
                }
                
	}

	switch ($_GET ['action']) {
		case 'myadd' :
			// return to the calendar display
			$return .= '<a href="myagenda.php">'.Display::return_icon('pixel.gif', get_lang('ReturnToCalendar'), array('class' => 'toolactionplaceholdericon toolactionback')).get_lang('ReturnToCalendar').'</a>';
			break;
		case 'myedit' :
			// return to the calendar display
			$return .= '<a href="myagenda.php">'.Display::return_icon('pixel.gif', get_lang('ReturnToCalendar'), array('class' => 'toolactionplaceholdericon toolactionback')).get_lang('ReturnToCalendar').'</a>';
			break;
		case '':
			// add an agenda
			if (api_get_setting('allow_personal_agenda') == 'true'){
				$return .= '<a href="myagenda.php?action=myadd">'.Display::return_icon('pixel.gif', get_lang('AgendaAddPersonal'), array('class' => 'toolactionplaceholdericon toolcalendaraddevent')).get_lang('AgendaAddPersonal').'</a>';
			}
			break;
	}

	return $return;
}

/**
 * Display the links that appear in the action bar in the "My calendar"
 *
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University, Belgium
 * @version june 2010
 */
function platformcalendar_actions() {
	$return = '';

	// adding of agenda item
	$return .= '<a href="agenda.php?action=platformadd">'.Display::return_icon('pixel.gif', get_lang('AgendaAddPlatform'), array('class' => 'toolactionplaceholdericon toolcalendaraddevent')).get_lang('AgendaAddPlatform').'</a>';
	return $return;
}

/**
 * Action handling for adding, editing or exporting agenda items
 * Moving, deleting and changing the visibility is handled through ajax.php
 *
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University, Belgium
 * @version june 2010
 */
function handle_calendar_actions() {
	global $_course, $_user;

	// if group members can add events we have to retrieve the groups of the user
	if (api_get_setting('user_manage_group_agenda')=='true'){
		// Additional libraries
		require_once(api_get_path(LIBRARY_PATH).'groupmanager.lib.php');
		$group_memberships		= GroupManager::get_group_ids($_course['dbName'],$_user['user_id']);
	}

	switch ($_GET ['action']) {
		case 'add' :
			if (api_is_allowed_to_edit(false,true) OR (api_get_course_setting('allow_user_edit_agenda') && !api_is_anonymous() && api_is_allowed_to_session_edit(false,true) ) OR (api_get_setting('user_manage_group_agenda')=='true' AND !empty($group_memberships))){
				show_agenda_form (array(),$_GET['date']);
			}
			break;
		case 'edit' :
			if (api_is_allowed_to_edit(false,true) OR (api_get_course_setting('allow_user_edit_agenda') && !api_is_anonymous() && api_is_allowed_to_session_edit(false,true) ) OR (api_get_setting('user_manage_group_agenda')=='true' AND !empty($group_memberships))){
				$input_values = get_course_agenda_item ( $_GET ['id'] );
				show_agenda_form ( $input_values );
			}
			break;
		case 'delete' :
			if (api_is_allowed_to_edit(false,true) OR (api_get_course_setting('allow_user_edit_agenda') && !api_is_anonymous() && api_is_allowed_to_session_edit(false,true) ) OR (api_get_setting('user_manage_group_agenda')=='true' AND !empty($group_memberships))){
				delete_agenda_item($_GET['id']);
				Display::display_confirmation_message(get_lang('AgendaDeleteSuccess'));
			}
			break;
		case 'show' :
			if (api_is_allowed_to_edit(false,true) OR (api_get_course_setting('allow_user_edit_agenda') && !api_is_anonymous() && api_is_allowed_to_session_edit(false,true) ) OR (api_get_setting('user_manage_group_agenda')=='true' AND !empty($group_memberships))){
				// second parameter = current status
				change_visibility_agenda_item((int)$_GET['id'],'invisible');
				Display::display_confirmation_message(get_lang("VisibilityChanged"));
			}
			break;
		case 'hide' :
			if (api_is_allowed_to_edit(false,true) OR (api_get_course_setting('allow_user_edit_agenda') && !api_is_anonymous() && api_is_allowed_to_session_edit(false,true) ) OR (api_get_setting('user_manage_group_agenda')=='true' AND !empty($group_memberships))){
				// second parameter = current status
				change_visibility_agenda_item((int)$_GET['id'],'visible');
				Display::display_confirmation_message(get_lang("VisibilityChanged"));
			}
			break;
		case 'detail' :
			display_detail($_GET['id'], $_GET['event_type']);
			break;
	}

	if (api_get_setting('calendar_google_import')=='true'){
		google_calendar_action_handling();
	}
}

function handle_header_calendar_actions(){
	switch ($_GET ['action']) {
		case 'export' :
			export_events('course',$_GET['id']);
			exit;
			break;
	}
}

/**
 * Action handling for adding, editing or exporting agenda items
 *
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University, Belgium
 * @version june 2010
 */
function handle_mycalendar_actions() {
	switch ($_GET ['action']) {
		case 'myadd' :
			if (api_get_setting('allow_personal_agenda') == 'true'){
				show_myagenda_form (array(),$_GET['date']);
			}
			break;
		case 'myedit' :
			if (api_get_setting('allow_personal_agenda') == 'true'){
				$input_values = get_myagenda_item ( $_GET ['id'] );
				show_myagenda_form ( $input_values );
			}
			break;
	}
}

/**
 * Action handling for adding, editing or exporting agenda items
 *
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University, Belgium
 * @version june 2010
 */
function handle_platformcalendar_actions() {
	switch ($_GET ['action']) {
		case 'platformadd' :
			show_platformagenda_form (array(),$_GET['date']);
			break;
		case 'platformedit' :
			$input_values = get_platformagenda_item ( $_GET ['id'] );
			show_platformagenda_form ( $input_values );
			break;
	}
}

/**
 * Display the form to add an agenda
 *
 * @param array $input_values containing all the form information that has already been typed or that was already stored in the database (editing)
 *
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University, Belgium
 * @version june 2010
 */
function show_agenda_form($input_values,$date='') {
	global $_user, $_course;

	// initiate the object
	$form = new FormValidator ( 'new_agenda_item', 'post', $_SERVER ['REQUEST_URI'].'&op=new' );

	// the header for the form
	if ($_GET ['action'] == 'add') {
		$form->addElement ( 'header', '', get_lang ( 'AgendaAdd' ) );
	}
	if ($_GET ['action'] == 'edit') {
		$form->addElement ( 'header', '', get_lang ( 'AgendaEdit' ) );
	}

	// a hidden form with the id of the agenda item we are editing
	$form->addElement ( 'hidden', 'agenda_id', $input_values['agenda_id'], array('id' => 'agenda_id'));

	if (api_is_allowed_to_edit(false,true) OR (api_get_course_setting('allow_user_edit_agenda') && !api_is_anonymous() && api_is_allowed_to_session_edit(false,true) )){
		// The receivers: groups
		$course_groups = CourseManager::get_group_list_of_course(api_get_course_id(), intval($_SESSION['id_session']));
		foreach ( $course_groups as $key => $group ) {
			$receivers ['G' . $key] = '-G- ' . $group ['name'];
		}
		// The receivers: users
		$course_users = CourseManager::get_user_list_from_course_code(api_get_course_id(), intval($_SESSION['id_session']));
		foreach ( $course_users as $key => $user ) {
			$receivers ['U' . $key] = $user ['lastname'] . ' ' . $user ['firstname'];
		}
	} else {
		// if group members can add events we have to retrieve the groups of the user
		if (api_get_setting('user_manage_group_agenda')=='true' AND !api_is_allowed_to_edit(false,true)){
			// Additional libraries
			require_once(api_get_path(LIBRARY_PATH).'groupmanager.lib.php');

			// The receivers: groups (the user belongs to)
			$group_memberships		= GroupManager::get_selected_group_info_of_subscribed_groups($_course['dbName'],$_user['user_id'],array('g.id','name'));
			foreach ( $group_memberships as $key => $group ) {
				$receivers ['G' . $group['id']] = '-G- ' . $group['name'];
			}

			// The receivers: other users of the groups (the user belongs to)

		}
	}

	$form->addElement ( 'receivers', 'send_to', get_lang ( 'VisibleFor' ), array ('receivers' => $receivers, 'receivers_selected' => '' ) );

	// Start Date
	$form->addElement ( 'datepicker', 'start_date', get_lang ( 'StartDate' ), array ('form_name' => 'new_agenda_item', 'id' => 'start_date', 'onchange' => 'update_enddate();' ) );

	// End date
	$form->addElement ( 'datepicker', 'end_date', get_lang ( 'EndDate' ), array ('form_name' => 'new_agenda_item', 'id' => 'end_date' ) );

	// The title
	$form->addElement ( 'text', 'title', get_lang ( 'Title' ), array ('maxlength' => '250', 'style' => 'width:600px', 'class' => 'focus' ) );

	// The message
	$form->addElement ( 'textarea', 'content', get_lang ( 'Text' ), 'rows="12" cols="83"');

	// the recurrence button
	//$form->addElement ( 'static', 'recurrence_image', null, '<span onclick="toggle_recurrency_form();" class="link_alike">' . Display::return_icon ( 'recurrence.png' ) . ' ' . get_lang ( 'Recurrence' ) . '</span>' );

	// the recurrency form elements
	$form->addElement ( 'html', '<div id="recurrency_form" style="display:none;">' );
	//$form->addElement('html','<div class="row"><div class="formw">');
	$form->addElement ( 'select', 'recurrency_frequency', get_lang ( 'Frequency' ), array ('once' => get_lang ( 'Once' ), 'daily' => get_lang ( 'Daily' ), 'weekly' => get_lang ( 'Weekly' ), 'monthly' => get_lang ( 'Monthly' ) ), array ('id' => 'recurrency_frequency' ) );
	//$form->addElement('html','</div></div>');
	$form->addElement ( 'html', '<div id="recurrency_form_detail" style="display:none;">' );
	$grp = array ();
	$grp [] = & $form->createElement ( 'static', null, null, get_lang ( 'Every' ) );
	$grp [] = & $form->createElement ( 'text', 'recurrency_repeat_every' );
	$grp [] = & $form->createElement ( 'static', null, null, '<span id="recurrency_repeat_every_timeperiod"></span>' );
	$form->addGroup ( $grp, 'recurrency_repeat', '', null, false );
	$grp = array ();
	$grp [] = & $form->createElement ( 'radio', 'recurrency_ends_on', '', get_lang ( 'After' ), 'recurrency_ends_after_times' );
	$grp [] = & $form->createElement ( 'text', 'recurrency_ends_after_times', '', array ('maxlength' => '2', 'size' => '2' ) );
	$grp [] = & $form->createElement ( 'static', null, null, get_lang ( 'Times' ) );
	$form->addGroup ( $grp, 'recurrency_ends', get_lang ( 'Ends' ), null, false );
	$grp = array ();
	$grp [] = & $form->createElement ( 'radio', 'recurrency_ends_on', '', get_lang ( 'On' ), 'recurrency_ends_on_date' );
	$grp [] = & $form->createElement ( 'datepicker', 'recurrency_ends_on_date', 'recurrency_ends_on_date', array ('form_name' => 'new_agenda_item', 'dateformat' => 'dFY', 'timeformat' => '' ) );
	$form->addGroup ( $grp, 'recurrency_ends2', '', null, false );

	$form->addElement ( 'html', '</div>' );
	$form->addElement ( 'html', '</div>' );

	// google calendar import
	if (api_get_setting('calendar_google_import')=='true'){
		google_calendar_form($form);
	}

	// export and delete
	if (!empty($input_values) AND api_get_setting('agenda_show_actions_on_edit')=='true')
	{
		$actions_on_edit .= '';
		$actions_on_edit .= '<a href="agenda.php?'.api_get_cidreq().'&action=export&amp;id='.$input_values['id'].'">'.Display :: return_icon('download_manager.gif', get_lang('IcalExport'),array('style'=>'vertical-align:middle')).' '.get_lang('IcalExport').'</a> ';
		$actions_on_edit .= '<a href="agenda.php?action=delete&amp;id='.$input_values['id'].'">'.Display :: return_icon('delete.png', get_lang('Delete'), array('onclick' => "javascript:if(!confirm('".addslashes(htmlentities(get_lang("ConfirmYourChoice")))."')) return false;",'style'=>'vertical-align:middle')).' '.get_lang('Delete').'</a>';
		//$form->addElement('static','test','',$actions_on_edit);
		$group='';
		$group[] =& HTML_QuickForm::createElement('static', 'test','test',$actions_on_edit,0);

		// The OK button
		$group[] =& HTML_QuickForm::createElement( 'style_submit_button', 'submit_agenda_item', get_lang ( 'SaveEvent' ) ,'class="save" style="margin-left:355px;"');

		$form->addGroup($group, 'formactions','', '&nbsp;');
	}
	else
	{
		// The OK button
		$form->addElement ( 'style_submit_button', 'submit_agenda_item', get_lang ( 'SaveEvent' ) ,'id="submit_agenda_item" class="save" style="margin-left:520px;"');
	}


				//$return .= ';

	// The javascript for updating the end date
	$form->addElement ( 'html', "<script type=\"text/javascript\">
			function toggle_recurrency_form(){
				$('#recurrency_form').slideToggle();
			}

			$('#recurrency_frequency').change(function() {
				// the value of the frequency
				var tmp = $('#recurrency_frequency').val();

				// if the frequency is not once we display the advanced form elements
			  	if( tmp != 'once'){
					$('#recurrency_form_detail').css('display','block');

					// displaying the correct time period
					if (tmp == 'daily'){
						$('#recurrency_repeat_every_timeperiod').html('" . get_lang ( 'Days' ) . "');
					}
					if (tmp == 'weekly'){
						$('#recurrency_repeat_every_timeperiod').html('" . get_lang ( 'Weeks' ) . "');
					}
					if (tmp == 'monthly'){
						$('#recurrency_repeat_every_timeperiod').html('" . get_lang ( 'Months' ) . "');
					}
			  	} else {
					$('#recurrency_form_detail').css('display','none');
			  	}
			});

			function update_enddate()
			{
				var start_date_d = $('select[name=\'start_date\\[d\\]\']').val();
				var start_date_F = $('select[name=\'start_date\\[F\\]\']').val();
				var start_date_Y = $('select[name=\'start_date\\[Y\\]\']').val();
				var start_date_H = $('select[name=\'start_date\\[H\\]\']').val();
				var start_date_i = $('select[name=\'start_date\\[i\\]\']').val();

				$('select[name=\'end_date\\[d\\]\']').val(start_date_d);
				$('select[name=\'end_date\\[F\\]\']').val(start_date_F);
				$('select[name=\'end_date\\[Y\\]\']').val(start_date_Y);
				$('select[name=\'end_date\\[H\\]\']').val(parseInt(start_date_H) + 1);
				$('select[name=\'end_date\\[i\\]\']').val(start_date_i);
			}


			</script>\n" );

	// The form values
	// Defaults
	// Time : should propose next hour and not NOW. For instance if I am 10h19, scroll should not propose to start 10h19 because few events start 10h19. Should propose 11h00. And end : should propose 12h00
	$current_hour = date ( 'H' );
	$current_hour = $current_hour + 1;
	if ($current_hour == 23) {
		$current_hour = 0;
	}
	$next_hour = $current_hour + 1;
	if ($next_hour == 23) {
		$next_hour = 0;
	}

	$defaults ['send_to'] ['receivers'] = 0;
	$defaults ['start_date'] = array ('d' => date ( 'd' ), 'F' => date ( 'm' ), 'Y' => date ( 'Y' ), 'H' => $current_hour, 'i' => 0 );
	$defaults ['end_date'] = array ('d' => date ( 'd' ), 'F' => date ( 'm' ), 'Y' => date ( 'Y' ), 'H' => ($next_hour), 'i' => 0 );
	$defaults ['recurrency_ends_on_date'] = array ('d' => date ( 'd' ), 'F' => date ( 'm' ), 'Y' => date ( 'Y' ), 'H' => $current_hour, 'i' => date ( 'i' ) );
	$defaults ['recurrency_repeat_every'] = 1;

	// When a item has to be added to a certain date
	if (!empty($date)) {
		$defaults ['start_date']['d'] = (int)substr($date,8,2);
		$defaults ['end_date']  ['d'] = (int)substr($date,8,2);
		$defaults ['start_date']['F'] = (int)substr($date,5,2);
		$defaults ['end_date'] 	['F'] = (int)substr($date,5,2);
		$defaults ['start_date']['Y'] = (int)substr($date,0,4);
		$defaults ['end_date']  ['Y'] = (int)substr($date,0,4);
		if (substr($date,11,2) == '00' AND substr($date,14,2)=='00')
		{
			// add from month view => we use the current time
			$defaults ['start_date']['H'] = date ('H');
			$defaults ['end_date']  ['H'] = date('H',time()+(60*60));
			$defaults ['start_date']['i'] = date ('i');
			$defaults ['end_date']  ['i'] = date ('i');
		}
		else
		{
			$defaults ['start_date']['H'] = (int)substr($date,11,2);
			$defaults ['end_date']  ['H'] = (int)substr($date,11,2) + 1;
			$defaults ['start_date']['i'] = (int)substr($date,14,2);
			$defaults ['end_date']  ['i'] = (int)substr($date,14,2);
		}
	}

	// when we are editing we have to overwrite all this with the information form the event we are editing
	if (!empty($input_values))
	{
		$defaults = $input_values;
		if ($input_values['visibilty'] == 0){
			$defaults['send_to']['receivers'] = '-1';
		}
	}

	// The rules (required fields)
	$form->addRule ( 'title', get_lang ( 'ThisFieldIsRequired' ), 'required' );
	$form->addRule ( 'start_date', get_lang ( 'ThisFieldIsRequired' ), 'required' );

	// The validation or display
	if ($form->validate ()) {
		$values = $form->exportValues ();
		$values = $form->exportValues ();
                $form_actions = $values['formactions'];
		if (isset ( $_POST ['submit_agenda_item'] ) || isset($form_actions['submit_agenda_item'])) {
			// store_agenda_item
			if (! $values ['agenda_id'] or ! is_numeric ( $values ['agenda_id'] )) {
				$id = store_new_agenda_item ( $values );
				//store_added_resources ( TOOL_CALENDAR_EVENT, $id );
			} else {
				store_edit_agenda_item ( $values );
				//update_added_resources ( TOOL_CALENDAR_EVENT, $values ['agenda_id'] );
			}
			Display::display_confirmation_message ( get_lang ( 'AgendaStored' ) );
			echo calendar_javascript();
		}

	} else {
		$form->setDefaults ($defaults);
		$form->display ();

		if ($defaults ['send_to'] ['receivers'] == 0 OR $defaults ['send_to'] ['receivers'] == '-1') {
			$js = "<script type=\"text/javascript\">/* <![CDATA[ */ receivers_hide('receivers_to'); /* ]]> */</script>\n";
		} else {
			$js = "<script type=\"text/javascript\">/* <![CDATA[ */ receivers_show('receivers_to'); /* ]]> */ </script>\n";
		}
		echo $js;
	}
}

/**
 * Enter description here...
 *
 * @param array $input_values containing all the form information that has already been typed (returning to
 * this page after coming from resourcelinker page or the recurrence page)
 * or that was already stored in the database (editing)
 *
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University, Belgium
 * @version june 2010
 */
function show_myagenda_form($input_values,$date='') {
	// initiate the object
	$form = new FormValidator ( 'new_myagenda_item', 'post', $_SERVER ['REQUEST_URI'] );

	// the header for the form
	if ($_GET ['action'] == 'myadd') {
		$form->addElement ( 'header', '', get_lang ( 'AgendaAdd' ) );
	}
	if ($_GET ['action'] == 'myedit') {
		$form->addElement ( 'header', '', get_lang ( 'AgendaEdit' ) );
	}

	// a hidden form with the id of the agenda item we are editing
	if ($input_values and is_numeric ( $input_values ['agenda_id'] )) {
		$form->addElement ( 'hidden', 'agenda_id', $input_values ['agenda_id'] );
	}

        // The receivers: users
        if ($_GET ['action'] == 'myadd') {
            $receivers = array();
            $user_table = Database::get_main_table(TABLE_MAIN_USER);
            $rs_users = Database::query("SELECT user_id, lastname, firstname FROM $user_table WHERE status <> ".ANONYMOUS." AND user_id <> ".api_get_user_id());
            if (Database::num_rows($rs_users) > 0) {
                while ($row_users = Database::fetch_array($rs_users, 'ASSOC')) {
                    $receivers[$row_users['user_id']] = $row_users['lastname'] . ' ' . $row_users['firstname'];
                }
            }
            $form->addElement ( 'receivers', 'send_to', get_lang ( 'VisibleFor' ), array ('receivers' => $receivers, 'receivers_selected' => '' ));
        }
	// Start Date
	$form->addElement ( 'datepicker', 'start_date', get_lang ( 'StartDate' ), array ('form_name' => 'new_agenda_item', 'id' => 'start_date', 'onchange' => 'update_enddate();' ) );

	// End date
	$form->addElement ( 'datepicker', 'end_date', get_lang ( 'EndDate' ), array ('form_name' => 'new_agenda_item', 'id' => 'end_date' ) );

	// The title
	$form->addElement ( 'text', 'title', get_lang ( 'Title' ), array ('maxlength' => '250', 'size' => '80', 'class' => 'focus' ) );

	// The message
	$form->addElement ( 'textarea', 'content', get_lang ( 'Text' ), 'rows="12" cols="90"');

	// the recurrence button
	//$form->addElement ( 'static', 'recurrence_image', null, '<span onclick="toggle_recurrency_form();" class="link_alike">' . Display::return_icon ( 'recurrence.png' ) . ' ' . get_lang ( 'Recurrence' ) . '</span>' );

	// the recurrency form elements
	$form->addElement ( 'html', '<div id="recurrency_form" style="display:none;">' );
	//$form->addElement('html','<div class="row"><div class="formw">');
	$form->addElement ( 'select', 'recurrency_frequency', get_lang ( 'Frequency' ), array ('once' => get_lang ( 'Once' ), 'daily' => get_lang ( 'Daily' ), 'weekly' => get_lang ( 'Weekly' ), 'monthly' => get_lang ( 'Monthly' ) ), array ('id' => 'recurrency_frequency' ) );
	//$form->addElement('html','</div></div>');
	$form->addElement ( 'html', '<div id="recurrency_form_detail" style="display:none;">' );
	$grp = array ();
	$grp [] = & $form->createElement ( 'static', null, null, get_lang ( 'Every' ) );
	$grp [] = & $form->createElement ( 'text', 'recurrency_repeat_every' );
	$grp [] = & $form->createElement ( 'static', null, null, '<span id="recurrency_repeat_every_timeperiod"></span>' );
	$form->addGroup ( $grp, 'recurrency_repeat', '', null, false );
	$grp = array ();
	$grp [] = & $form->createElement ( 'radio', 'recurrency_ends_on', '', get_lang ( 'After' ), 'recurrency_ends_after_times' );
	$grp [] = & $form->createElement ( 'text', 'recurrency_ends_after_times', '', array ('maxlength' => '2', 'size' => '2' ) );
	$grp [] = & $form->createElement ( 'static', null, null, get_lang ( 'Times' ) );
	$form->addGroup ( $grp, 'recurrency_ends', get_lang ( 'Ends' ), null, false );
	$grp = array ();
	$grp [] = & $form->createElement ( 'radio', 'recurrency_ends_on', '', get_lang ( 'On' ), 'recurrency_ends_on_date' );
	$grp [] = & $form->createElement ( 'datepicker', 'recurrency_ends_on_date', 'recurrency_ends_on_date', array ('form_name' => 'new_agenda_item', 'dateformat' => 'dFY', 'timeformat' => '' ) );
	$form->addGroup ( $grp, 'recurrency_ends2', '', null, false );

	$form->addElement ( 'html', '</div>' );
	$form->addElement ( 'html', '</div>' );

	// The OK button
	$form->addElement ( 'style_submit_button', 'submit_agenda_item', get_lang ( 'SaveEvent' ) ,'class="save" style="margin-left:520px;"');

	// The javascript for updating the end date
	$form->addElement ( 'html', "<script type=\"text/javascript\">
			function toggle_recurrency_form(){
				$('#recurrency_form').slideToggle();
			}

			$('#recurrency_frequency').change(function() {
				// the value of the frequency
				var tmp = $('#recurrency_frequency').val();

				// if the frequency is not once we display the advanced form elements
			  	if( tmp != 'once'){
					$('#recurrency_form_detail').css('display','block');

					// displaying the correct time period
					if (tmp == 'daily'){
						$('#recurrency_repeat_every_timeperiod').html('" . get_lang ( 'Days' ) . "');
					}
					if (tmp == 'weekly'){
						$('#recurrency_repeat_every_timeperiod').html('" . get_lang ( 'Weeks' ) . "');
					}
					if (tmp == 'monthly'){
						$('#recurrency_repeat_every_timeperiod').html('" . get_lang ( 'Months' ) . "');
					}
			  	} else {
					$('#recurrency_form_detail').css('display','none');
			  	}
			});

			function update_enddate()
			{
				var start_date_d = $('select[name=\'start_date\\[d\\]\']').val();
				var start_date_F = $('select[name=\'start_date\\[F\\]\']').val();
				var start_date_Y = $('select[name=\'start_date\\[Y\\]\']').val();
				var start_date_H = $('select[name=\'start_date\\[H\\]\']').val();
				var start_date_i = $('select[name=\'start_date\\[i\\]\']').val();

				$('select[name=\'end_date\\[d\\]\']').val(start_date_d);
				$('select[name=\'end_date\\[F\\]\']').val(start_date_F);
				$('select[name=\'end_date\\[Y\\]\']').val(start_date_Y);
				$('select[name=\'end_date\\[H\\]\']').val(parseInt(start_date_H) + 1);
				$('select[name=\'end_date\\[i\\]\']').val(start_date_i);
			}


			</script>\n" );

	// The form values
	// Defaults
	$current_hour = date ( 'H' );
	$current_hour = $current_hour + 1;
	if ($current_hour == 23) {
		$current_hour = 0;
	}
	$next_hour = $current_hour + 1;
	if ($next_hour == 23) {
		$next_hour = 0;
	}
	$defaults ['send_to'] ['receivers'] = 0;
	$defaults ['start_date'] = array ('d' => date ( 'd' ), 'F' => date ( 'm' ), 'Y' => date ( 'Y' ), 'H' => $current_hour, 'i' => 0 );
	$defaults ['end_date'] = array ('d' => date ( 'd' ), 'F' => date ( 'm' ), 'Y' => date ( 'Y' ), 'H' => $next_hour, 'i' => 0 );
	$defaults ['recurrency_ends_on_date'] = array ('d' => date ( 'd' ), 'F' => date ( 'm' ), 'Y' => date ( 'Y' ), 'H' => $current_hour, 'i' => date ( 'i' ) );

	// When a item has to be added to a certain date
	if (!empty($date)) {
		$defaults ['start_date']['d'] = (int)substr($date,8,2);
		$defaults ['end_date']  ['d'] = (int)substr($date,8,2);
		$defaults ['start_date']['F'] = (int)substr($date,5,2);
		$defaults ['end_date'] 	['F'] = (int)substr($date,5,2);
		$defaults ['start_date']['Y'] = (int)substr($date,0,4);
		$defaults ['end_date']  ['Y'] = (int)substr($date,0,4);
		$defaults ['start_date']['H'] = $_GET ['hour'];
		$defaults ['end_date']  ['H'] = $_GET ['hour'];
		$defaults ['start_date']['i'] = $_GET ['minute'];
		$defaults ['end_date']  ['i'] = $_GET ['minute'];
	}

	// when we are editing we have to overwrite all this with the information form the event we are editing
	if (!empty($input_values))
	{
		$defaults = $input_values;
	}

	// The rules (required fields)
	$form->addRule ( 'title', get_lang ( 'ThisFieldIsRequired' ), 'required' );
	$form->addRule ( 'start_date', get_lang ( 'ThisFieldIsRequired' ), 'required' );

	// The validation or display
	if ($form->validate ()) {
		$values = $form->exportValues ();
                $check = Security::check_token('post');
                if($check) {
                    if (isset ( $_POST ['submit_agenda_item'] )) {
                            // store_agenda_item
                            if (! $values ['agenda_id'] or ! is_numeric ( $values ['agenda_id'] )) {
                                $saved = store_new_myagenda_item ( $values );
                            } else {
                                $saved = store_edit_myagenda_item ( $values );
                            }
                            Display::display_confirmation_message ( get_lang ( 'AgendaStored' ) );
                            echo '<script>location.href="'.api_get_path(WEB_CODE_PATH).'calendar/myagenda.php";</script>';
                            //echo mycalendar_javascript();
                    }
                } else {
                    //echo mycalendar_javascript();
                    echo '<script>location.href="'.api_get_path(WEB_CODE_PATH).'calendar/myagenda.php";</script>';
                }
                Security::clear_token();
	} else {
                if (isset($_POST['submit'])) {
                        Security::clear_token();
                }
                $token = Security::get_token();
                $form->addElement('hidden', 'sec_token');
                $defaults['sec_token'] = $token;
            
		$form->setDefaults ($defaults);
		$form->display ();
	}
}

/**
 * Enter description here...
 *
 * @param array $input_values containing all the form information that has already been typed (returning to
 * this page after coming from resourcelinker page or the recurrence page)
 * or that was already stored in the database (editing)
 *
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University, Belgium
 * @version june 2010
 */
function show_platformagenda_form($input_values,$date='') {
	// initiate the object
	$form = new FormValidator ( 'new_myagenda_item', 'post', $_SERVER ['REQUEST_URI'] );
        $editor_config = array('ToolbarSet' => 'PortalNews', 'Width' => '97%', 'Height' => '300');
	// the header for the form
	if ($_GET ['action'] == 'add') {
		$form->addElement ( 'header', '', get_lang ( 'AgendaAdd' ) );
	}
	if ($_GET ['action'] == 'edit') {
		$form->addElement ( 'header', '', get_lang ( 'AgendaEdit' ) );
	}

	// a hidden form with the id of the agenda item we are editing
	if ($input_values and is_numeric ( $input_values ['agenda_id'] )) {
		$form->addElement ( 'hidden', 'agenda_id', $input_values ['agenda_id'] );
	}

	// Start Date
	$form->addElement ( 'datepicker', 'start_date', get_lang ( 'StartDate' ), array ('form_name' => 'new_agenda_item', 'id' => 'start_date', 'onchange' => 'update_enddate();' ) );

	// End date
	$form->addElement ( 'datepicker', 'end_date', get_lang ( 'EndDate' ), array ('form_name' => 'new_agenda_item', 'id' => 'end_date' ) );

	// The title
	$form->addElement ( 'text', 'title', get_lang ( 'Title' ), array ('maxlength' => '250', 'style' => 'width:450px', 'class' => 'focus' ) );

	// The message
	$form->addElement ( 'html_editor', 'content', get_lang ( 'Text' ),'style="vertical-align:middle"',$editor_config);

	// the recurrence button
	$form->addElement ( 'static', 'recurrence_image', null, '<span onclick="toggle_recurrency_form();" class="link_alike">' . Display::return_icon ( 'recurrence.png',get_lang ( 'Recurrence' )) . ' ' . get_lang ( 'Recurrence' ) . '</span>' );

	// the recurrency form elements
	$form->addElement ( 'html', '<div id="recurrency_form" style="display:none;">' );
	//$form->addElement('html','<div class="row"><div class="formw">');
	$form->addElement ( 'select', 'recurrency_frequency', get_lang ( 'Frequency' ), array ('once' => get_lang ( 'Once' ), 'daily' => get_lang ( 'Daily' ), 'weekly' => get_lang ( 'Weekly' ), 'monthly' => get_lang ( 'Monthly' ) ), array ('id' => 'recurrency_frequency' ) );
	//$form->addElement('html','</div></div>');
	$form->addElement ( 'html', '<div id="recurrency_form_detail" style="display:none;">' );
	$grp = array ();
	$grp [] = & $form->createElement ( 'static', null, null, get_lang ( 'Every' ) );
	$grp [] = & $form->createElement ( 'text', 'recurrency_repeat_every' );
	$grp [] = & $form->createElement ( 'static', null, null, '<span id="recurrency_repeat_every_timeperiod"></span>' );
	$form->addGroup ( $grp, 'recurrency_repeat', '', null, false );
	$grp = array ();
	$grp [] = & $form->createElement ( 'radio', 'recurrency_ends_on', '', get_lang ( 'After' ), 'recurrency_ends_after_times' );
	$grp [] = & $form->createElement ( 'text', 'recurrency_ends_after_times', '', array ('maxlength' => '2', 'size' => '2' ) );
	$grp [] = & $form->createElement ( 'static', null, null, get_lang ( 'Times' ) );
	$form->addGroup ( $grp, 'recurrency_ends', get_lang ( 'Ends' ), null, false );
	$grp = array ();
	$grp [] = & $form->createElement ( 'radio', 'recurrency_ends_on', '', get_lang ( 'On' ), 'recurrency_ends_on_date' );
	$grp [] = & $form->createElement ( 'datepicker', 'recurrency_ends_on_date', 'recurrency_ends_on_date', array ('form_name' => 'new_agenda_item', 'dateformat' => 'dFY', 'timeformat' => '' ) );
	$form->addGroup ( $grp, 'recurrency_ends2', '', null, false );

	$form->addElement ( 'html', '</div>' );
	$form->addElement ( 'html', '</div>' );

	// The OK button
	$form->addElement ( 'style_submit_button', 'submit_agenda_item', get_lang ( 'SaveEvent' ) ,'class="save" style="margin-left:520px;"');

	// The javascript for updating the end date
	$form->addElement ( 'html', "<script type=\"text/javascript\">
			function toggle_recurrency_form(){
				$('#recurrency_form').slideToggle();
			}

			$('#recurrency_frequency').change(function() {
				// the value of the frequency
				var tmp = $('#recurrency_frequency').val();

				// if the frequency is not once we display the advanced form elements
			  	if( tmp != 'once'){
					$('#recurrency_form_detail').css('display','block');

					// displaying the correct time period
					if (tmp == 'daily'){
						$('#recurrency_repeat_every_timeperiod').html('" . get_lang ( 'Days' ) . "');
					}
					if (tmp == 'weekly'){
						$('#recurrency_repeat_every_timeperiod').html('" . get_lang ( 'Weeks' ) . "');
					}
					if (tmp == 'monthly'){
						$('#recurrency_repeat_every_timeperiod').html('" . get_lang ( 'Months' ) . "');
					}
			  	} else {
					$('#recurrency_form_detail').css('display','none');
			  	}
			});

			function update_enddate()
			{
				var start_date_d = $('select[name=\'start_date\\[d\\]\']').val();
				var start_date_F = $('select[name=\'start_date\\[F\\]\']').val();
				var start_date_Y = $('select[name=\'start_date\\[Y\\]\']').val();
				var start_date_H = $('select[name=\'start_date\\[H\\]\']').val();
				var start_date_i = $('select[name=\'start_date\\[i\\]\']').val();

				$('select[name=\'end_date\\[d\\]\']').val(start_date_d);
				$('select[name=\'end_date\\[F\\]\']').val(start_date_F);
				$('select[name=\'end_date\\[Y\\]\']').val(start_date_Y);
				$('select[name=\'end_date\\[H\\]\']').val(start_date_H);
				$('select[name=\'end_date\\[i\\]\']').val(start_date_i);
			}


			</script>\n" );

	// The form values
	// Defaults
	$current_hour = date ( 'H' );
	$current_hour = $current_hour + 1;
	if ($current_hour == 23) {
		$current_hour = 0;
	}
	$next_hour = $current_hour + 1;
	if ($next_hour == 23) {
		$next_hour = 0;
	}
	$defaults ['start_date'] = array ('d' => date ( 'd' ), 'F' => date ( 'm' ), 'Y' => date ( 'Y' ), 'H' => $current_hour, 'i' => date ( 'i' ) );
	$defaults ['end_date'] = array ('d' => date ( 'd' ), 'F' => date ( 'm' ), 'Y' => date ( 'Y' ), 'H' => $next_hour, 'i' => date ( 'i' ) );
	$defaults ['recurrency_ends_on_date'] = array ('d' => date ( 'd' ), 'F' => date ( 'm' ), 'Y' => date ( 'Y' ), 'H' => $current_hour, 'i' => date ( 'i' ) );

	// When a item has to be added to a certain date
	if (!empty($date)) {
		$defaults ['start_date']['d'] = (int)substr($date,8,2);
		$defaults ['end_date']  ['d'] = (int)substr($date,8,2);
		$defaults ['start_date']['F'] = (int)substr($date,5,2);
		$defaults ['end_date'] 	['F'] = (int)substr($date,5,2);
		$defaults ['start_date']['Y'] = (int)substr($date,0,4);
		$defaults ['end_date']  ['Y'] = (int)substr($date,0,4);
		$defaults ['start_date']['H'] = Security::remove_XSS($_GET ['hour']);
		$defaults ['end_date']  ['H'] = Security::remove_XSS($_GET ['hour']) + 1;
		$defaults ['start_date']['i'] = Security::remove_XSS($_GET ['minute']);
		$defaults ['end_date']  ['i'] = Security::remove_XSS($_GET ['minute']);
	}

	// when we are editing we have to overwrite all this with the information form the event we are editing
	if (!empty($input_values))
	{
		$defaults = $input_values;
	}

	// The rules (required fields)
	$form->addRule ( 'title', get_lang ( 'ThisFieldIsRequired' ), 'required' );
	$form->addRule ( 'start_date', get_lang ( 'ThisFieldIsRequired' ), 'required' );

	// The validation or display
	if ($form->validate ()) {
		$values = $form->exportValues ();

		if (isset ( $_POST ['submit_agenda_item'] )) {
			// store_agenda_item
			if (! $values ['agenda_id'] or ! is_numeric ( $values ['agenda_id'] )) {
				$id = store_new_platformagenda_item ( $values );
			} else {
				store_edit_platformagenda_item ( $values );
			}
			Display::display_confirmation_message ( get_lang ( 'AgendaStored' ) );
		}

	} else {
		$form->setDefaults ( $defaults );
		$form->display ();
	}
}

/**
 * This function stores the Agenda Item in the table calendar_event and updates the item_property table also
 *
 * @return integer the id of the last added agenda item
 *
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University, Belgium
 * @version june 2010
 */
function store_new_agenda_item($values) {
	global $_course;

	// Database table definition
	$table_agenda = Database::get_course_table ( TABLE_AGENDA );

	// some filtering of the input data
	$title = strip_tags ( trim ( $values ['title'] ) ); // no html allowed in the title
	$content = trim ( $values ['content'] );
	$start_date = $values ['start_date'];
	$end_date = $values ['end_date'];
	//$start_date=(int)$_POST['fyear']."-".(int)$_POST['fmonth']."-".(int)$_POST['fday']." ".(int)$_POST['fhour'].":".(int)$_POST['fminute'].":00";
	//$end_date=(int)$_POST['end_fyear']."-".(int)$_POST['end_fmonth']."-".(int)$_POST['end_fday']." ".(int)$_POST['end_fhour'].":".(int)$_POST['end_fminute'].":00";


	// store in the table calendar_event
	$sql = "INSERT INTO " . $table_agenda . "
					        (title,content, start_date, end_date)
					        VALUES (
					        	'" . Database::escape_string ( $title ) . "',
					        	'" . Database::escape_string ( $content ) . "',
					        	'" . Database::escape_string ( $start_date ) . "',
					        	'" . Database::escape_string ( $end_date ) . "'
					        	)";

	$result = api_sql_query ( $sql, __FILE__, __LINE__ );
	$last_id = mysql_insert_id ();

	// store in item_property (visibility, insert_date, target users/groups, visibility timewindow, ...)
	store_item_property ( $values, $last_id, 'AgendaAdded' );

	// storing the resources
	//store_resources($_SESSION['source_type'],$last_id);

	// storing the recurrence
	do_recurrence ( $values );

	if (api_get_setting('calendar_google_import')=='true'){
		store_google($values, $last_id);
	}

	return $last_id;
}

/**
 * This function stores the Agenda Item in the table calendar_event and updates the item_property table also
 *
 * @return integer the id of the last added agenda item
 *
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University, Belgium
 * @version june 2010
 */
function store_new_myagenda_item($values) {
	global $_course, $_user;

	// Database table definition
	$table_agenda = Database :: get_user_personal_table(TABLE_PERSONAL_AGENDA);

	// some filtering of the input data
	$title = strip_tags ( trim ( $values ['title'] ) ); // no html allowed in the title
	$content = trim ( $values ['content'] );
	$start_date = $values ['start_date'];
	$end_date = $values ['end_date'];

        $affected_rows = 0;
        // first save the original event
        Database::query("INSERT INTO ".$table_agenda."(user, title, text, date, enddate)
					        VALUES (
                                        '" . intval($_user['user_id'])."',
					        	'" . Database::escape_string ( $title ) . "',
					        	'" . Database::escape_string ( $content ) . "',
					        	'" . Database::escape_string ( $start_date ) . "',
					        	'" . Database::escape_string ( $end_date ) . "'
                                        )");         
        $parent_event_id = Database::insert_id();
        $affected_rows += Database::affected_rows();
        // Now send the event to selected users
        if ($parent_event_id) {
            if (isset($values['send_to']['receivers'])) {
                $receivers = intval($values['send_to']['receivers']);                
                if ($receivers == 0) {                                        
                    $user_table = Database::get_main_table(TABLE_MAIN_USER);
                    $rs_users = Database::query("SELECT user_id FROM $user_table WHERE status <> ".ANONYMOUS." AND user_id <> ".api_get_user_id());
                    if (Database::num_rows($rs_users) > 0) {
                        while ($row_users = Database::fetch_array($rs_users, 'ASSOC')) {
                            Database::query("INSERT INTO ".$table_agenda."(user, title, text, date, enddate, parent_event_id)
                                            VALUES (
                                                        '" . intval($row_users['user_id'])."',
                                                        '" . Database::escape_string ($title) . "',
                                                        '" . Database::escape_string ($content) . "',
                                                        '" . Database::escape_string ($start_date) . "',
                                                        '" . Database::escape_string ($end_date) . "',
                                                        '".intval($parent_event_id)."'
                                                        )");
                            $affected_rows += Database::affected_rows();
}
                    }
                } else if ($receivers == 1) {
                     $users = $values['send_to']['to'];
                     if (!empty($users)) {
                        foreach ($users as $user_id) {
                            if (api_is_anonymous($user_id, true) || $user_id == $_user['user_id']) { continue; }                
                            Database::query("INSERT INTO ".$table_agenda."(user, title, text, date, enddate, parent_event_id)
                                                VALUES (
                                                            '" . intval($user_id)."',
                                                            '" . Database::escape_string ($title) . "',
                                                            '" . Database::escape_string ($content) . "',
                                                            '" . Database::escape_string ($start_date) . "',
                                                            '" . Database::escape_string ($end_date) . "',
                                                            '".intval($parent_event_id)."'
                                                            )");
                            $affected_rows += Database::affected_rows();
                        }
                    }                                        
                }                               
            }
        }           
	return $affected_rows;
}

/**
 * Enter description here...
 *
 * @param unknown_type $values
 * @todo consider moving this into the function for storing a new agenda item
 *
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University, Belgium
 * @version june 2010
 */
function store_edit_agenda_item($values) {
	// Database table definition
	$tbl_agenda = Database::get_course_table ( TABLE_AGENDA );
	$tbl_item_property = Database::get_course_table ( TABLE_ITEM_PROPERTY );

	// Step 1: we have to find the old item_property information of the item
	// This is needed because we do not want to lose when it was first inserted and by who
	$sql = "SELECT * FROM $tbl_item_property WHERE tool='" . Database::escape_string ( TOOL_CALENDAR_EVENT ) . "' AND ref='" . Database::escape_string ( $values ['agenda_id'] ) . "'";
	$result = api_sql_query ( $sql, __LINE__, __FILE__ );
	$old_itemproperty_info = mysql_fetch_assoc ( $result );

	// Step 2: updating the information in the calendar_event table
	$update_calendar_event_sql = "UPDATE $tbl_agenda SET
										title		= '" . Database::escape_string ( $values ['title'] ) . "',
										content		= '" . Database::escape_string ( $values ['content'] ) . "',
										start_date	= '" . Database::escape_string ( $values ['start_date'] ) . "',
										end_date	= '" . Database::escape_string ( $values ['end_date'] ) . "'
									WHERE id = '" . Database::escape_string ( $values ['agenda_id'] ) . "'";
	$result = api_sql_query ( $update_calendar_event_sql, __LINE__, __FILE__ );

	// Step 3: removing all the old entries for this agenda item from item_property
	$del_old_itemproperty_sql = "DELETE FROM $tbl_item_property WHERE tool='" . Database::escape_string ( TOOL_CALENDAR_EVENT ) . "' AND ref='" . Database::escape_string ( $values ['agenda_id'] ) . "'";
	$result = api_sql_query ( $del_old_itemproperty_sql, __LINE__, __FILE__ );

	// Step 4: updating the information in the item_property table
	store_item_property ( $values, $values ['agenda_id'], 'AgendaModified' );

	// storing the recurrence
	//do_recurrence ( $values );

	// Step 5: updating the information in the item_property table with the old item_property information
	// note: we can consider changing the function store_item_property so that it no longer uses api_item_property_update
	// but a (api_store_item_property) general function that receives all the field information (too, insert_user_id, insert_date, lastedit_date, ref, ...)
	// and stores it in the item_property table
	$sql = "UPDATE $tbl_item_property SET
				insert_user_id	= '" . Database::escape_string ( $old_itemproperty_info ['insert_user_id'] ) . "',
				insert_date		= '" . Database::escape_string ( $old_itemproperty_info ['insert_date'] ) . "'
			WHERE tool = '" . Database::escape_string ( TOOL_CALENDAR_EVENT ) . "' AND ref = '" . Database::escape_string ( $values ['agenda_id'] ) . "'";
	$result = api_sql_query ( $sql, __LINE__, __FILE__ );
}

/**
 * This function stores the Agenda Item in the table calendar_event and updates the item_property table also
 *
 * @return integer the id of the last added agenda item
 *
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University, Belgium
 * @version june 2010
 */
function store_edit_myagenda_item($values) {
	global $_course, $_user;

	// Database table definition
	$table_agenda = Database :: get_user_personal_table(TABLE_PERSONAL_AGENDA);

	// some filtering of the input data
	$title = strip_tags ( trim ( $values ['title'] ) ); // no html allowed in the title
	$content = trim ( $values ['content'] );
	$start_date = $values ['start_date'];
	$end_date = $values ['end_date'];

        $affected_rows = 0;

        // first edit the original event
	Database::query("UPDATE " . $table_agenda . " SET
							title = '" . Database::escape_string ( $title ) . "',
							`text` = '" . Database::escape_string ( $content ) . "',
							`date` = '" . Database::escape_string ( $start_date ) . "',
							enddate = '" . Database::escape_string ( $end_date ) . "'
				WHERE id = " . intval( $values ['agenda_id'] )."
                        AND user = " . intval( $_user['user_id']));
        $affected_rows += Database::affected_rows();

        // now edit the copied messages
        Database::query("UPDATE " . $table_agenda . " SET
                            title = '" . Database::escape_string ( $title ) . "',
                            `text` = '" . Database::escape_string ( $content ) . "',
                            `date` = '" . Database::escape_string ( $start_date ) . "',
                            enddate = '" . Database::escape_string ( $end_date ) . "'
                        WHERE parent_event_id = " . intval( $values ['agenda_id'] ));
        $affected_rows += Database::affected_rows();
        
	// storing the recurrence
	do_recurrence ( $values , 'personal');

	return $affected_rows;
}

/**
 * This function stores the Agenda Item in the table calendar_event and updates the item_property table also
 *
 * @return integer the id of the last added agenda item
 *
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University, Belgium
 * @version june 2010
 */
function store_edit_platformagenda_item($values) {
	global $_course, $_user;

	// Database table definition
	$table_agenda = Database::get_main_table(TABLE_MAIN_SYSTEM_CALENDAR);

	// some filtering of the input data
	$title = strip_tags ( trim ( $values ['title'] ) ); // no html allowed in the title
	$content = trim ( $values ['content'] );
	$start_date = $values ['start_date'];
	$end_date = $values ['end_date'];
	//$start_date=(int)$_POST['fyear']."-".(int)$_POST['fmonth']."-".(int)$_POST['fday']." ".(int)$_POST['fhour'].":".(int)$_POST['fminute'].":00";
	//$end_date=(int)$_POST['end_fyear']."-".(int)$_POST['end_fmonth']."-".(int)$_POST['end_fday']." ".(int)$_POST['end_fhour'].":".(int)$_POST['end_fminute'].":00";


	// store in the table calendar_event
	$sql = "UPDATE " . $table_agenda . " SET
							title = '" . Database::escape_string ( $title ) . "',
							content = '" . Database::escape_string ( $content ) . "',
							start_date = '" . Database::escape_string ( $start_date ) . "',
							end_date = '" . Database::escape_string ( $end_date ) . "'
				WHERE id = " . intval( $values ['agenda_id'] );
	$result = api_sql_query ( $sql, __FILE__, __LINE__ );
	$last_id = mysql_insert_id ();

	// storing the recurrence
	do_recurrence ( $values , 'platform');

	return $last_id;
}

function store_new_platformagenda_item ($values) {
	$tbl_platformagenda = Database::get_main_table(TABLE_MAIN_SYSTEM_CALENDAR);

	// store the platform event
	$sql = "INSERT INTO ".$tbl_platformagenda."
          (title,content, start_date, end_date)
          VALUES
          ('".Database::escape_string(strip_tags(trim($values['title'])))."','".Database::escape_string(trim($values['content']))."','".Database::escape_string($values['start_date'])."','".Database::escape_string($values['end_date'])."' )";

	$rs = Database::query($sql, __FILE__,__LINE__);
	$id = Database::get_last_insert_id();

	return $id;
}

/**
 * Enter description here...
 *
 * @param unknown_type $values
 * @param unknown_type $id
 * @param unknown_type $action_string
 *
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University, Belgium
 * @version june 2010
 */
function store_item_property($values, $id, $action_string) {
	global $_course;
	global $_user;

	if ($values ['send_to'] ['receivers'] == 0) {
		api_item_property_update ( $_course, TOOL_CALENDAR_EVENT, $id, $action_string, $_user ['user_id'], '', '', $start_visible, $end_visible );
	}
	if ($values ['send_to'] ['receivers'] == 1) {
		foreach ( $values ['send_to'] ['to'] as $key => $target ) {
			if (substr ( $target, 0, 1 ) == 'U') {
				$user = substr ( $target, 1 );
				api_item_property_update ( $_course, TOOL_CALENDAR_EVENT, $id, $action_string, $_user ['user_id'], '', $user, $start_visible, $end_visible );
				//api_item_property_update($_course, $tool, 				$item_id, 	$lastedit_type, $user_id, 	$to_group_id = 0, 	$to_user_id = NULL, $start_visible = 0, $end_visible = 0)
			}
			if (substr ( $target, 0, 1 ) == 'G') {
				$group = substr ( $target, 1 );
				api_item_property_update ( $_course, TOOL_CALENDAR_EVENT, $id, $action_string, $_user ['user_id'], $group, '', $start_visible, $end_visible );
			}
		}
	}
	if ($values ['send_to'] ['receivers'] == '-1') {
		// adding to everybody
		api_item_property_update ( $_course, TOOL_CALENDAR_EVENT, $id, $action_string, $_user ['user_id'], '', '', $start_visible, $end_visible );
		// making it invisible
		api_item_property_update($_course, TOOL_CALENDAR_EVENT, $id, 'invisible');
	}
}

/**
 * This function handles the actual recurrence.
 *
 * @param array $values the array containing all the information for the database (from the initial agenda item): title, content, start date, end date
 *
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University, Belgium
 * @version may 2010
 */
function do_recurrence($values, $type='course') {
	global $_course;

	// Table Definitions
	if ($type == 'course')
	{
		$tbl_agenda = Database::get_course_table ( TABLE_AGENDA );
	}
	elseif ($type == 'platform')
	{
		$tbl_agenda = Database :: get_user_personal_table(TABLE_PERSONAL_AGENDA);
	}
	elseif ($type == 'personal')
	{
		$tbl_agenda = Database :: get_user_personal_table(TABLE_PERSONAL_AGENDA);
	}
	$tbl_item_property = Database::get_course_table ( TABLE_ITEM_PROPERTY );

	if ($values['recurrency_frequency']<>'once') {
		if ($values ['recurrency_ends_on'] == 'recurrency_ends_after_times') {
			$counter = 1;
			while ( $counter < $values['recurrency_ends_after_times'] ) {
				store_recurrence ( $values, $counter, $type);
				$counter ++;
			}
		} elseif ($values ['recurrency_ends_on'] == 'recurrency_ends_on_date') {
			$counter = 1;
			$continue = true;
			while ( $continue == true ) {
				// storing a recurrent agenda item
				$last_id = store_recurrence ( $values, $counter, $type );

				// checking if the recurrent agenda item is still OK
				$sql = "SELECT * FROM $tbl_agenda WHERE id = '" . Database::escape_string ( $last_id ) . "'";
				$result = api_sql_query ( $sql, __FILE__, __LINE__ );
				$row = mysql_fetch_assoc ( $result );

				if ($row ['start_date'] <= $values['recurrency_ends_on_date'].' 23:59:59') {
					$continue = true;
					$counter ++;
				} else {
					$sql = "DELETE FROM $tbl_agenda WHERE id = '" . Database::escape_string ( $last_id ) . "'";
					$result = api_sql_query ( $sql, __FILE__, __LINE__ );
					if ($type == 'course')
					{
						$sql = "DELETE FROM $tbl_item_property WHERE ref= '" . Database::escape_string ( $last_id ) . "' AND tool = '" . Database::escape_string ( TOOL_CALENDAR_EVENT ) . "'";
						$result = api_sql_query ( $sql, __FILE__, __LINE__ );
					}
					$continue = false;
				}
			}
			$counter ++;
		}
	}
}


/**
 * Enter description here...
 *
 * @param unknown_type $values
 * @param unknown_type $counter
 * @param string $type this determines if it is a course agenda item, a personal agenda item or a platform agenda item
 * @return unknown
 *
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University, Belgium
 * @version may 2006
 */
function store_recurrence($values, $counter, $type='course')
{
	global $_user;

	// Table Definitions
	if ($type == 'course')
	{
		$tbl_agenda 		= Database::get_course_table(TABLE_AGENDA);
	}
	elseif ($type == 'platform')
	{
		$tbl_agenda = Database::get_main_table(TABLE_MAIN_SYSTEM_CALENDAR);
	}
	elseif ($type == 'personal')
	{
		$tbl_agenda = Database :: get_user_personal_table(TABLE_PERSONAL_AGENDA);
	}
	$tbl_item_property	= Database::get_course_table(TABLE_ITEM_PROPERTY);


	if ($values['recurrency_frequency']=='daily')
	{
		$mysql_interval = 'INTERVAL '.$values['recurrency_repeat_every'] * $counter.' DAY';
	}
	if ($values['recurrency_frequency']=='weekly')
	{
		// MySQL >= 5.0.0
		//$mysql_interval = 'INTERVAL '.$values['recurrency_repeat_every'] * $counter.' DAY';
		// MySQL < 5.0.0
		$mysql_interval = 'INTERVAL '.$values['recurrency_repeat_every'] * $counter * 7 .' DAY';
	}
	if ($values['recurrency_frequency']=='monthly')
	{
		$mysql_interval = 'INTERVAL '.$values['recurrency_repeat_every'] * $counter.' MONTH';
	}

	// store in the table calendar_event
	$sql = "INSERT INTO ".$tbl_agenda."
	        (title,content, start_date, end_date)
	        VALUES
	        ('".Database::escape_string(strip_tags(trim($values['title'])))."','".Database::escape_string(trim($values['content']))."', DATE_ADD('".$values['start_date']."', $mysql_interval), DATE_ADD('".$values['end_date']."', $mysql_interval) )";
	if ($type == 'personal')
	{
		$sql = "INSERT INTO ".$tbl_agenda."
		        (title,text, date, enddate, user)
		        VALUES
		        ('".Database::escape_string(strip_tags(trim($values['title'])))."','".Database::escape_string(trim($values['content']))."', DATE_ADD('".$values['start_date']."', $mysql_interval), DATE_ADD('".$values['end_date']."', $mysql_interval),".$_user['user_id'].")";
	}
	$result = api_sql_query($sql,__FILE__,__LINE__);
	$last_id=mysql_insert_id();

	// store in item_property (visibility, insert_date, target users/groups, visibility timewindow, ...)
	if ($type == 'course')
	{
		store_item_property($values, $last_id, 'AgendaAdded');
	}

	return $last_id;
}

/**
 * This function gets all the information about an agenda item
 *
 * @param unknown_type $agenda_id
 * @return unknown
 * @todo consider moving this to the itemmanager.lib.php and/or replace this by the function get_item_information
 *
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University, Belgium
 * @version june 2006
 */
function get_course_agenda_item($agenda_id) {
	// table definitions
	$tbl_agenda = Database::get_course_table ( TABLE_AGENDA );
	$tbl_item_property = Database::get_course_table ( TABLE_ITEM_PROPERTY );

	// The sql statement to get all the information of one particular event
	$sql = "SELECT
			agenda.*,
				HOUR(agenda.start_date) as start_hour,
				MINUTE(agenda.start_date) as start_minute,
				DAYOFMONTH(agenda.start_date) AS start_day,
				MONTH(agenda.start_date) AS start_month,
				YEAR(agenda.start_date) AS start_year,
				HOUR(agenda.end_date) as end_hour,
				MINUTE(agenda.end_date) as end_minute,
				DAYOFMONTH(agenda.end_date) AS end_day,
				MONTH(agenda.end_date) AS end_month,
				YEAR(agenda.end_date) AS end_year,
				UNIX_TIMESTAMP(start_date) as start,
				UNIX_TIMESTAMP(end_date) as end,
				toolitemproperties.*
			FROM " . $tbl_agenda . " agenda, " . $tbl_item_property . " toolitemproperties
			WHERE `agenda`.`id` = `toolitemproperties`.`ref`
			AND `toolitemproperties`.`tool`='" . TOOL_CALENDAR_EVENT . "'
			AND `agenda`.`id`='" . Database::escape_string ( $agenda_id ) . "'";
	$result = Database::query ( $sql, __FILE__, __LINE__ );
	$return ['send_to'] ['receivers'] = 1;
	while ( $row = Database::fetch_array( $result, 'ASSOC' ) ) {
		$return ['id'] 					= $row ['id'];
		if ($row ['to_group_id'] == '0' and $row ['to_user_id'] == null) {
			$return ['send_to'] ['receivers'] = 0;
		}
		if ($row ['to_group_id'] != null and $row ['to_group_id'] != '0') {
			$return ['send_to'] ['to'] [] = 'G' . $row ['to_group_id'];
		}
		if ($row ['to_user_id'] != null) {
			$return ['send_to'] ['to'] [] = 'U' . $row ['to_user_id'];
		}
		$return ['start_date'] ['d'] = $row ['start_day'];
		$return ['start_date'] ['F'] = $row ['start_month'];
		$return ['start_date'] ['Y'] = $row ['start_year'];
		$return ['start_date'] ['H'] = $row ['start_hour'];
		$return ['start_date'] ['i'] = $row ['start_minute'];
		$return ['end_date'] ['d'] = $row ['end_day'];
		$return ['end_date'] ['F'] = $row ['end_month'];
		$return ['end_date'] ['Y'] = $row ['end_year'];
		$return ['end_date'] ['H'] = $row ['end_hour'];
		$return ['end_date'] ['i'] = $row ['end_minute'];
		$return ['title'] 				= $row ['title'];
		$return ['content']		 		= $row ['content'];
		$return ['agenda_id'] 			= $row ['id'];
		$return ['start_full'] 			= $row['start'];
		$return ['start'] 				= date('c',$row['start']);
		$return ['end_full'] 			= $row['end'];
		$return ['end'] 				= date('c',$row['end']);
		$return ['type']				= 'course';
		$return ['visibility']			= $row['visibility'];
	}
	return $return;
}

/**
 *
 * Get the quiz events by the quiz ID
 * @param integer $quiz_id
 * @return array
 */
function get_quiz_events_by_quiz_id ($quiz_id) {
	// table definitions
	$tbl_quiz = Database::get_course_table ( TABLE_QUIZ_TEST );
	// The sql statement to get all the information of one particular event
	$sql = "SELECT title,description,HOUR(start_time) as start_hour,
				MINUTE(start_time) as start_minute,
				DAYOFMONTH(start_time) AS start_day,
				MONTH(start_time) AS start_month,
				YEAR(start_time) AS start_year,
				HOUR(end_time) as end_hour,
				MINUTE(end_time) as end_minute,
				DAYOFMONTH(end_time) AS end_day,
				MONTH(end_time) AS end_month,
				YEAR(end_time) AS end_year,UNIX_TIMESTAMP(start_time) as start,UNIX_TIMESTAMP(end_time) as end FROM $tbl_quiz WHERE session_id='".api_get_session_id()."' AND id='".Database::escape_string(Security::remove_XSS($quiz_id))."'";
	$result = Database::query ( $sql, __FILE__, __LINE__ );

	while ( $row = Database::fetch_array( $result, 'ASSOC' ) ) {
		$return ['start_date'] ['d'] = $row ['start_day'];
		$return ['start_date'] ['F'] = $row ['start_month'];
		$return ['start_date'] ['Y'] = $row ['start_year'];
		$return ['start_date'] ['H'] = $row ['start_hour'];
		$return ['start_date'] ['i'] = $row ['start_minute'];
		$return ['end_date'] ['d'] = $row ['end_day'];
		$return ['end_date'] ['F'] = $row ['end_month'];
		$return ['end_date'] ['Y'] = $row ['end_year'];
		$return ['end_date'] ['H'] = $row ['end_hour'];
		$return ['end_date'] ['i'] = $row ['end_minute'];
		$return ['title'] 				= $row ['title'];
		$return ['content']		 		= $row ['description'];
		$return ['agenda_id'] 			= $row ['id'];
		$return ['start_full'] 			= $row['start'];
		$return ['start'] 				= date('c',strtotime($row['start']));
		$return ['end_full'] 			= $row['end'];
		$return ['end'] 				= date('c',strtotime($row['end']));
		$return ['type']				= 'quiz';
		$return ['visibility']			= $row['visibility'];
	}
	return $return;
}

/**
 *
 * Get assignment events by assigment ID
 * @param integer $assignment_id
 * @return array
 */
function get_assignment_events_by_assignment_id($assignment_id)
{
	$events = array();

	// Database table definition
	$table_student_publication 				= Database :: get_course_table(TABLE_STUDENT_PUBLICATION);
	$table_student_publication_assignment	= Database :: get_course_table(TABLE_STUDENT_PUBLICATION_ASSIGNMENT);

	$sql = "SELECT
				student_publication.id 	AS id,
				student_publication.url AS title,student_publication.description,HOUR(student_publication.sent_date) as start_hour,
				MINUTE(student_publication.sent_date) as start_minute,
				DAYOFMONTH(student_publication.sent_date) AS start_day,
				MONTH(student_publication.sent_date) AS start_month,
				YEAR(student_publication.sent_date) AS start_year,
				HOUR(student_publication_assignment.expires_on) as end_hour,
				MINUTE(student_publication_assignment.expires_on) as end_minute,
				DAYOFMONTH(student_publication_assignment.expires_on) AS end_day,
				MONTH(student_publication_assignment.expires_on) AS end_month,
				YEAR(student_publication_assignment.expires_on) AS end_year,
				UNIX_TIMESTAMP(student_publication.sent_date) AS start,
				UNIX_TIMESTAMP(student_publication_assignment.expires_on) AS end
			FROM $table_student_publication student_publication, $table_student_publication_assignment student_publication_assignment
			WHERE student_publication_assignment.add_to_calendar <> 0
			AND student_publication.id = student_publication_assignment.publication_id AND student_publication.id ='".Database::escape_string(Security::remove_XSS($assignment_id))."'";

	$result = Database::query ( $sql, __FILE__, __LINE__ );

	while ( $row = Database::fetch_array( $result, 'ASSOC' ) ) {
		$return ['start_date'] ['d'] = $row ['start_day'];
		$return ['start_date'] ['F'] = $row ['start_month'];
		$return ['start_date'] ['Y'] = $row ['start_year'];
		$return ['start_date'] ['H'] = $row ['start_hour'];
		$return ['start_date'] ['i'] = $row ['start_minute'];
		$return ['end_date'] ['d'] = $row ['end_day'];
		$return ['end_date'] ['F'] = $row ['end_month'];
		$return ['end_date'] ['Y'] = $row ['end_year'];
		$return ['end_date'] ['H'] = $row ['end_hour'];
		$return ['end_date'] ['i'] = $row ['end_minute'];
		$return ['title'] 				= substr($row ['title'],1);
		$return ['content']		 		= $row ['description'];
		$return ['agenda_id'] 			= $row ['id'];
		$return ['start_full'] 			= $row['start'];
		$return ['start'] 				= date('c',strtotime($row['start']));
		$return ['end_full'] 			= $row['end'];
		$return ['end'] 				= date('c',strtotime($row['end']));
		$return ['type']				= 'assignment';
		$return ['visibility']			= $row['visibility'];
	}
	return $return;
}

/**
 *
 * Get session events by session ID
 * @param integer $session_id
 * @return array
 */
function get_session_events_by_session_id($session_id)
{
	$events = array();

	// Database table definition
	$table_session 				= Database::get_main_table(TABLE_MAIN_SESSION);

	$sql = "SELECT session.id, session.name as title,HOUR(session.date_start) as start_hour,
				MINUTE(session.date_start) as start_minute,
				DAYOFMONTH(session.date_start) AS start_day,
				MONTH(session.date_start) AS start_month,
				YEAR(session.date_start) AS start_year,
				HOUR(session.date_end) as end_hour,
				MINUTE(session.date_end) as end_minute,
				DAYOFMONTH(session.date_end) AS end_day,
				MONTH(session.date_end) AS end_month,
				YEAR(session.date_end) AS end_year,
				UNIX_TIMESTAMP(session.date_start) as start, UNIX_TIMESTAMP(session.date_end) as end
		FROM $table_session session
		WHERE session.id = '".Database::escape_string($session_id)."'";
	$result = Database::query ( $sql, __FILE__, __LINE__ );
 
	while ( $row = Database::fetch_array( $result, 'ASSOC' ) ) {
		$return ['start_date'] ['d'] = $row ['start_day'];
		$return ['start_date'] ['F'] = $row ['start_month'];
		$return ['start_date'] ['Y'] = $row ['start_year'];
		$return ['start_date'] ['H'] = $row ['start_hour'];
		$return ['start_date'] ['i'] = $row ['start_minute'];
		$return ['end_date'] ['d'] = $row ['end_day'];
		$return ['end_date'] ['F'] = $row ['end_month'];
		$return ['end_date'] ['Y'] = $row ['end_year'];
		$return ['end_date'] ['H'] = $row ['end_hour'];
		$return ['end_date'] ['i'] = $row ['end_minute'];
		$return ['title'] 				= $row ['title'];
		$return ['content']		 		= $row ['description'];
		$return ['agenda_id'] 			= $row ['id'];
		$return ['start_full'] 			= $row['start'];
		$return ['start'] 				= date('c',strtotime($row['start']));
		$return ['end_full'] 			= $row['end'];
		$return ['end'] 				= date('c',strtotime($row['end']));
		$return ['type']				= 'session';
		$return ['visibility']			= $row['visibility'];
	}
	return $return;
}

/**
 * This function gets all the information about an agenda item
 *
 * @param unknown_type $agenda_id
 * @return unknown
 * @todo consider moving this to the itemmanager.lib.php and/or replace this by the function get_item_information
 *
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University, Belgium
 * @version june 2006
 */
function get_myagenda_item($agenda_id) {
	global $_user;

	// table definitions
	$tbl_agenda = Database :: get_user_personal_table(TABLE_PERSONAL_AGENDA);

	// The sql statement to get all the information of one particular
	$sql = "SELECT
			agenda.*,
				HOUR(agenda.date) as start_hour,
				MINUTE(agenda.date) as start_minute,
				DAYOFMONTH(agenda.date) AS start_day,
				MONTH(agenda.date) AS start_month,
				YEAR(agenda.date) AS start_year,
				HOUR(agenda.enddate) as end_hour,
				MINUTE(agenda.enddate) as end_minute,
				DAYOFMONTH(agenda.enddate) AS end_day,
				MONTH(agenda.enddate) AS end_month,
				YEAR(agenda.enddate) AS end_year
			FROM " . $tbl_agenda . " agenda
			WHERE `agenda`.`id`='" . Database::escape_string ( $agenda_id ) . "'
			AND user = ".intval($_user['user_id']);
	$result = api_sql_query ( $sql, __FILE__, __LINE__ );
	while ( $row = mysql_fetch_assoc ( $result ) ) {
		$return ['start_date'] ['d'] 	= $row ['start_day'];
		$return ['start_date'] ['F'] 	= $row ['start_month'];
		$return ['start_date'] ['Y'] 	= $row ['start_year'];
		$return ['start_date'] ['H'] 	= $row ['start_hour'];
		$return ['start_date'] ['i'] 	= $row ['start_minute'];
		$return ['end_date'] ['d'] 		= $row ['end_day'];
		$return ['end_date'] ['F'] 		= $row ['end_month'];
		$return ['end_date'] ['Y'] 		= $row ['end_year'];
		$return ['end_date'] ['H'] 		= $row ['end_hour'];
		$return ['end_date'] ['i'] 		= $row ['end_minute'];
		$return ['title']		 		= $row ['title'];
		$return ['content'] 			= $row ['text'];
		$return ['agenda_id'] = $row ['id'];
	}
	return $return;
}

/**
 * This function gets all the information about an agenda item
 *
 * @param unknown_type $agenda_id
 * @return unknown
 * @todo consider moving this to the itemmanager.lib.php and/or replace this by the function get_item_information
 *
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University, Belgium
 * @version june 2006
 */
function get_platformagenda_item($agenda_id) {
	global $_user;

	// table definitions
	$tbl_agenda = Database::get_main_table(TABLE_MAIN_SYSTEM_CALENDAR);

	// The sql statement to get all the information of one particular
	$sql = "SELECT
			agenda.*,
				HOUR(agenda.start_date) as start_hour,
				MINUTE(agenda.start_date) as start_minute,
				DAYOFMONTH(agenda.start_date) AS start_day,
				MONTH(agenda.start_date) AS start_month,
				YEAR(agenda.start_date) AS start_year,
				HOUR(agenda.end_date) as end_hour,
				MINUTE(agenda.end_date) as end_minute,
				DAYOFMONTH(agenda.end_date) AS end_day,
				MONTH(agenda.end_date) AS end_month,
				YEAR(agenda.end_date) AS end_year
			FROM " . $tbl_agenda . " agenda
			WHERE `agenda`.`id`=" . intval( $agenda_id );
	$result = api_sql_query ( $sql, __FILE__, __LINE__ );
	while ( $row = mysql_fetch_assoc ( $result ) ) {
		$return ['start_date'] ['d'] 	= $row ['start_day'];
		$return ['start_date'] ['F'] 	= $row ['start_month'];
		$return ['start_date'] ['Y'] 	= $row ['start_year'];
		$return ['start_date'] ['H'] 	= $row ['start_hour'];
		$return ['start_date'] ['i'] 	= $row ['start_minute'];
		$return ['end_date'] ['d'] 		= $row ['end_day'];
		$return ['end_date'] ['F'] 		= $row ['end_month'];
		$return ['end_date'] ['Y'] 		= $row ['end_year'];
		$return ['end_date'] ['H'] 		= $row ['end_hour'];
		$return ['end_date'] ['i'] 		= $row ['end_minute'];
		$return ['title'] 				= $row ['title'];
		$return ['content'] 			= $row ['content'];
		$return ['agenda_id'] 			= $row ['id'];
	}
	return $return;
}

/**
 * This is the function that deletes an agenda item.
 * The agenda item is no longer fycically deleted but the visibility in the item_property table is set to 2
 * which means that it is invisible for the student AND course admin. Only the platform administrator can see it.
 * This will in a later stage allow the platform administrator to recover resources that were mistakenly deleted
 * by the course administrator
 *
 * @param integer the id of the agenda item wa are deleting
 *
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University, Belgium
 * @version june 2010
 */
function delete_agenda_item($id)
{
	global $_course, $_user;
	if (api_get_setting('calendar_google_import')=='true' AND strstr($id,'google_')){
		google_calendar_delete_event($id);
		return true;
	}
	if (api_is_allowed_to_edit(false,true)  OR (api_get_course_setting('allow_user_edit_agenda') && !api_is_anonymous()))
	{
		if (isset($_GET['id']) && $_GET['id'] && isset($_GET['action']) && $_GET['action']=="delete")
		{
			$id=(int)$id;
			api_item_property_update($_course,TOOL_CALENDAR_EVENT,$id,'delete',api_get_user_id());
		}
	}
}

/**
 * Delete a personal event. Contrary to the course events, the personal events are actually deleted from the database.
 *
 * @param $id integer the id of the personal event
 *
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University, Belgium
 * @version june 2010
 */
function delete_myagenda_item($id)
{
	global $_user;

	// Database table definition
	$tbl_agenda = Database :: get_user_personal_table(TABLE_PERSONAL_AGENDA);

	$sql = "DELETE FROM $tbl_agenda WHERE id = ".intval($id)." AND user = ".intval($_user['user_id']);
	$result = api_sql_query ( $sql, __FILE__, __LINE__ );
}

/**
 * Delete a platform event. Contrary to the course events, the platform events are actually deleted from the database.
 *
 * @param $id integer the id of the platform event
 *
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University, Belgium
 * @version june 2010
 */
function delete_platformagenda_item($id)
{
	global $_user;

	// Database table definition
	$tbl_agenda = Database::get_main_table(TABLE_MAIN_SYSTEM_CALENDAR);
        $sql = "DELETE FROM $tbl_agenda WHERE id = ".intval($id);
	$result = api_sql_query ( $sql, __FILE__, __LINE__ );
}

/**
 * Enter description here...
 *
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University, Belgium
 * @version june 2010
 */
function change_visibility_agenda_item($id,$current_status)
{
	global $_course, $_user;

	if (api_is_allowed_to_edit(false,true)  OR (api_get_course_setting('allow_user_edit_agenda') && !api_is_anonymous()))
	{
		if (isset($_GET['id']) && $_GET['id'] && isset($_GET['action']) && $_GET['action']=="visibility")
		{
			$id=(int)$_GET['id'];

			if ($current_status == 'visible'){
				return change_visibility(TOOL_CALENDAR_EVENT,$id,0);
			}
			else {
				return change_visibility(TOOL_CALENDAR_EVENT,$id,1);
			}
		}
	}
}

/**
 * Enter description here...
 *
 * @author move_platformagenda_item
 * @version 
 */
function move_platformagenda_item($id,$day_delta, $minute_delta)
{
        // Database table definition
	$tbl_agenda = Database::get_main_table(TABLE_MAIN_SYSTEM_CALENDAR);

        // we convert the hour delta into minutes and add the minute delta
	$delta = ($day_delta * 60 * 24) + $minute_delta;

	$sql = "UPDATE $tbl_agenda SET start_date = DATE_ADD(start_date,INTERVAL $delta MINUTE), end_date = DATE_ADD(end_date,INTERVAL $delta MINUTE) WHERE id = ".intval($id);
	$result = api_sql_query ( $sql, __FILE__, __LINE__ );
}

/**
 * Enter description here...
 *
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University, Belgium
 * @version june 2010
 */
function move_agenda_item($id,$day_delta, $minute_delta)
{
	// table definitions
	$table_agenda = Database::get_course_table ( TABLE_AGENDA );

	// we convert the hour delta into minutes and add the minute delta
	$delta = ($day_delta * 60 * 24) + $minute_delta;

	$sql = "UPDATE $table_agenda SET start_date = DATE_ADD(start_date,INTERVAL $delta MINUTE), end_date = DATE_ADD(end_date,INTERVAL $delta MINUTE) WHERE id=".Database::escape_string($id);
        $result = api_sql_query ( $sql, __FILE__, __LINE__ );
}

/**
 * Enter description here...
 *
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University, Belgium
 * @version june 2010
 */
function mymove_agenda_item($id,$day_delta, $minute_delta)
{
	// table definitions
	$table_agenda = Database :: get_user_personal_table(TABLE_PERSONAL_AGENDA);
	// we convert the hour delta into minutes and add the minute delta
	$delta = ($day_delta * 60 * 24) + $minute_delta;
	Database::query("UPDATE $table_agenda SET `date` = DATE_ADD(date,INTERVAL $delta MINUTE), enddate = DATE_ADD(enddate,INTERVAL $delta MINUTE) WHERE id = ".intval($id));
        if (Database::affected_rows()) {
            Database::query("UPDATE $table_agenda SET `date` = DATE_ADD(date,INTERVAL $delta MINUTE), enddate = DATE_ADD(enddate,INTERVAL $delta MINUTE) WHERE parent_event_id = ".intval($id));
          }
}

/**
 * This functions swithes the visibility a course resource using the visible field in 'last_tooledit' values: 0 = invisible
 *
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University, Belgium
 * @version june 2010
 */
function change_visibility($tool,$id,$visibility)
{
	global $_course;

	// table definition
	$TABLE_ITEM_PROPERTY = Database::get_course_table(TABLE_ITEM_PROPERTY);

	if ($visibility == 0)
	{
		$sql_visibility="UPDATE $TABLE_ITEM_PROPERTY SET visibility='0' WHERE tool='".Database::escape_string($tool)."' AND ref='".Database::escape_string($id)."'";
		api_item_property_update($_course,TOOL_CALENDAR_EVENT,$id,"invisible",api_get_user_id());
	}
	else
	{
		$sql_visibility="UPDATE $TABLE_ITEM_PROPERTY SET visibility='1' WHERE tool='".Database::escape_string($tool)."' AND ref='".Database::escape_string($id)."'";
		api_item_property_update($_course,TOOL_CALENDAR_EVENT,$id,"visible",api_get_user_id());
	}
}

/**
 * Enter description here...
 *
 * @author 
 * @version 
 */
function resize_platformagenda_item($id,$day_delta, $minute_delta)
{
	// table definitions
	$tbl_agenda = Database::get_main_table(TABLE_MAIN_SYSTEM_CALENDAR);

	// we convert the hour delta into minutes and add the minute delta
	$delta = ($day_delta * 60 * 24) + $minute_delta;

	$sql = "UPDATE $tbl_agenda SET end_date = DATE_ADD(end_date,INTERVAL $delta MINUTE) WHERE id=".Database::escape_string($id);
	$result = api_sql_query ( $sql, __FILE__, __LINE__ );
}



/**
 * Enter description here...
 *
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University, Belgium
 * @version July 2010
 */
function resize_agenda_item($id,$day_delta, $minute_delta)
{
	// table definitions
	$table_agenda = Database::get_course_table ( TABLE_AGENDA );

	// we convert the hour delta into minutes and add the minute delta
	$delta = ($day_delta * 60 * 24) + $minute_delta;

	$sql = "UPDATE $table_agenda SET end_date = DATE_ADD(end_date,INTERVAL $delta MINUTE) WHERE id=".Database::escape_string($id);
	$result = api_sql_query ( $sql, __FILE__, __LINE__ );
}

/**
 * Enter description here...
 *
 * @author Eric Marguin <e.marguin@webapart.fr>, Web à part, France
 * @version March 2011
 */
function myresize_agenda_item($id,$day_delta, $minute_delta)
{
	// table definitions
	$table_agenda = Database :: get_user_personal_table(TABLE_PERSONAL_AGENDA);

	// we convert the hour delta into minutes and add the minute delta
	$delta = ($day_delta * 60 * 24) + $minute_delta;
	Database::query("UPDATE $table_agenda SET enddate = DATE_ADD(enddate,INTERVAL $delta MINUTE) WHERE id=".intval($id)); 
        if (Database::affected_rows()) {
            Database::query("UPDATE $table_agenda SET enddate = DATE_ADD(enddate,INTERVAL $delta MINUTE) WHERE parent_event_id=".intval($id));
        }
}

/**
 * Get all the agenda items of the course. If no parameter $course_db is specified
 * we assume that we are already inside a course and use $_course
 *
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University, Belgium
 * @version june 2010
 */
function get_course_agenda_items($course_db='', $coursecode, $full_information=false,$editable=true){
	global $_course, $_user;

	// variable initialisation
	$events = array();

	// Additional libraries
	require_once(api_get_path(LIBRARY_PATH).'groupmanager.lib.php');

	// Database table definitions
	if (empty($course_db)){
		if (!empty($_course)){
			$table_agenda 			= Database::get_course_table(TABLE_AGENDA);
			$table_item_property	= Database::get_course_table(TABLE_ITEM_PROPERTY);
			$table_user 			= Database::get_main_table(TABLE_MAIN_USER);
		} else	{
			return array();
		}
		$course_db = $_course['dbName'];
	} else{
		// Database table definition
		$table_agenda 			= Database::get_course_table(TABLE_AGENDA,$course_db);
		$table_item_property	= Database::get_course_table(TABLE_ITEM_PROPERTY,$course_db);
		$table_user 			= Database::get_main_table(TABLE_MAIN_USER);
	}

	// get all the group memberships of the user
	if (api_is_allowed_to_edit(false,true)){
		//$group_memberships = '';
	} else {
		$group_memberships		= GroupManager::get_group_ids($_course['dbName'],$_user['user_id']);
		$group_memberships[]	= 0;
	}

	// session restriction
	$session_condition = intval($_SESSION['id_session'])==0 ? '' : ' AND agenda.session_id IN (0,'.intval($_SESSION['id_session']).') ';        
	if (api_is_allowed_to_edit(false,true) OR (api_get_course_setting('allow_user_edit_agenda', $coursecode) == 'true' && !api_is_anonymous()))
	{
		// A.1. you are a course admin with a USER filter
		// => see only the messages of this specific user + the messages of the group (s)he is member of.
		if (!empty($_GET['user']))
		{
			if (is_array($group_memberships) && count($group_memberships)>0)
			{
				$sql="SELECT
					agenda.*, UNIX_TIMESTAMP(start_date) as start, UNIX_TIMESTAMP(end_date) as end, toolitemproperties.*
					FROM ".$table_agenda." agenda, ".$table_item_property." toolitemproperties
					WHERE agenda.id = toolitemproperties.ref
					AND toolitemproperties.tool='".TOOL_CALENDAR_EVENT."'
					AND	( toolitemproperties.to_user_id=".Database::escape_string($_user['user_id'])." OR toolitemproperties.to_group_id IN (0, ".implode(", ", $group_memberships).") )
					AND toolitemproperties.visibility='1'
					$session_condition";
			}
			else
			{
				$sql="SELECT
					agenda.*, UNIX_TIMESTAMP(start_date) as start, UNIX_TIMESTAMP(end_date) as end, toolitemproperties.*
					FROM ".$table_agenda." agenda, ".$table_item_property." toolitemproperties
					WHERE agenda.id = toolitemproperties.ref
					AND toolitemproperties.tool='".TOOL_CALENDAR_EVENT."'
					AND ( toolitemproperties.to_user_id=".Database::escape_string($_user['user_id'])." OR toolitemproperties.to_group_id='0')
					AND toolitemproperties.visibility='1'
					$session_condition";
			}
		}
		// A.2. you are a course admin with a GROUP filter
		// => see only the messages of this specific group
		elseif (!empty($_GET['group']))
		{
			$sql="SELECT
				agenda.*, UNIX_TIMESTAMP(start_date) as start, UNIX_TIMESTAMP(end_date) as end, toolitemproperties.*
				FROM ".$table_agenda." agenda, ".$table_item_property." toolitemproperties
				WHERE agenda.id = toolitemproperties.ref
				AND toolitemproperties.tool='".TOOL_CALENDAR_EVENT."'
				AND ( toolitemproperties.to_group_id='".Database::escape_string(Security::Remove_XSS($_GET['group']))."' OR toolitemproperties.to_group_id='0')
				AND toolitemproperties.visibility='1'
				$session_condition";
		}
		// A.3 you are a course admin without any group or user filter
		else
		{
			// A.3.a you are a course admin without user or group filter but WITH studentview
			// => see all the messages of all the users and groups without editing possibilities
			if ($_GET['isStudentView']=='true')
			{
				$sql="SELECT
					agenda.*, UNIX_TIMESTAMP(start_date) as start, UNIX_TIMESTAMP(end_date) as end, toolitemproperties.*
					FROM ".$table_agenda." agenda, ".$TABLE_ITEM_PROPERTY." toolitemproperties
					WHERE agenda.id = toolitemproperties.ref
					AND toolitemproperties.tool='".TOOL_CALENDAR_EVENT."'
					AND toolitemproperties.visibility<>'1'
					$session_condition";
			}
			// A.3.b you are a course admin without user or group filter and WITHOUT studentview (= the normal course admin view)
			// => see all the messages of all the users and groups with editing possibilities
			else
			{
				$sql="SELECT
					agenda.*, UNIX_TIMESTAMP(start_date) as start, UNIX_TIMESTAMP(end_date) as end, toolitemproperties.*
					FROM ".$table_agenda." agenda, ".$table_item_property." toolitemproperties
					WHERE agenda.id = toolitemproperties.ref
					AND toolitemproperties.tool='".TOOL_CALENDAR_EVENT."'
					AND ( toolitemproperties.visibility='0' or toolitemproperties.visibility='1')
					$session_condition";
			}
		}

	} //if (is_allowed_to_edit() OR( api_get_course_setting('allow_user_edit_agenda') && !api_is_anonymous()))

	// B. you are a student
	else
	{
		if (is_array($group_memberships) and count($group_memberships)>0)
		{
			$sql="SELECT
				agenda.*, UNIX_TIMESTAMP(start_date) as start, UNIX_TIMESTAMP(end_date) as end, toolitemproperties.*
				FROM ".$table_agenda." agenda, ".$table_item_property." toolitemproperties
				WHERE agenda.id = toolitemproperties.ref
				AND toolitemproperties.tool='".TOOL_CALENDAR_EVENT."'
				AND	( toolitemproperties.to_user_id=".Database::escape_string($_user['user_id'])." OR toolitemproperties.to_group_id IN (0, ".implode(", ", $group_memberships).") )
				AND toolitemproperties.visibility='1'
				$session_condition";
		}
		else
		{
			if ($_user['user_id'])
			{
				$sql="SELECT
					agenda.*, UNIX_TIMESTAMP(start_date) as start, UNIX_TIMESTAMP(end_date) as end, toolitemproperties.*
					FROM ".$table_agenda." agenda, ".$TABLE_ITEM_PROPERTY." toolitemproperties
					WHERE agenda.id = toolitemproperties.ref
					AND toolitemproperties.tool='".TOOL_CALENDAR_EVENT."'
					AND ( toolitemproperties.to_user_id=".Database::escape_string($_user['user_id'])." OR toolitemproperties.to_group_id='0')
					AND toolitemproperties.visibility='1'
					$session_condition";
			}
			else
			{
				$sql="SELECT
					agenda.*, UNIX_TIMESTAMP(start_date) as start, UNIX_TIMESTAMP(end_date) as end, toolitemproperties.*
					FROM ".$table_agenda." agenda, ".$table_item_property." toolitemproperties
					WHERE agenda.id = toolitemproperties.ref
					AND toolitemproperties.tool='".TOOL_CALENDAR_EVENT."'
					AND toolitemproperties.to_group_id='0'
					AND toolitemproperties.visibility='1'
					$session_condition";
			}
		}
	} // you are a student

	// filter for the dates (if any)
	if (isset($_GET['start']) AND isset($_GET['end']) AND !empty($_GET['start']) AND !empty($_GET['end']))
	{
		$sql .= " AND start_date >= FROM_UNIXTIME(".$_GET['start'].")
				  AND start_date <= FROM_UNIXTIME(".$_GET['end'].")
				  ORDER BY start_date DESC";
	}
	else
	{
		$sql .= " ORDER BY start_date DESC";
	}

	$result=Database::query($sql,__FILE__,__LINE__) or die(Database::error());

	$events = array();
	while ($row=Database::fetch_array($result))
	{
		// additional classes depending on visibility
		if ($row['visibility'] == 0){
			$class_name = 'invisible courseagenda '.$coursecode;
		} else {
			$class_name = 'courseagenda '.$coursecode;
		}

		// if the course code is not empty we have to provide a link to the course
		if (!empty($coursecode)){
			$row['title'] = '<span class="'.$coursecode.'">'.$coursecode.' - '.$row['title'].'</span>';
		}

		// do we need to get all the information (list view) or only the information that is displayed in the fullcalendar views?
		if ($full_information)
		{
			// if the event already exists we only have to add the receivers without overwriting all the rest (or we lose the previously added receivers)
			if (key_exists($row['id'],$events))
			{
				if ($row ['to_group_id'] != null and $row ['to_group_id'] != '0') {
					$events[$row['id']] ['send_to'] ['to'] [] = 'G' . $row ['to_group_id'];
				}
				if ($row ['to_user_id'] != null) {
					$events[$row['id']] ['send_to'] ['to'] [] = 'U' . $row ['to_user_id'];
				}
			}
			else
			{
				if ($row ['to_group_id'] == '0' and $row ['to_user_id'] == null) {
					$row ['send_to'] ['receivers'] = 0;
				}
				if ($row ['to_group_id'] != null and $row ['to_group_id'] != '0') {
					$row ['send_to'] ['to'] [] = 'G' . $row ['to_group_id'];
				}
				if ($row ['to_user_id'] != null) {
					$row ['send_to'] ['to'] [] = 'U' . $row ['to_user_id'];
				}
				$row['start_full'] 	= $row['start'];
				$row['start'] 		= date('c',$row['start']);
				$row['end_full'] 	= $row['end'];
				$row['end'] 		= date('c',$row['end']);
				$row['allDay'] 		= false;
				$row['className'] 	= $class_name;
				$row['type']		= 'course';
				$row['editable']	= $editable;
				$events[$row['id']] = $row;
			}
		}
		else
		{                                            
                    $events[$row['id']] = array('id'=>'course_events'.$row['id'], 'title'=>htmlentities($row['title']), 'start'=>date('c',$row['start']), 'end'=>date('c',$row['start']), "allDay"=>false, 'visibility'=>$row['visibility'],'className'=>$class_name, 'type'=>'course', 'editable'=>$editable);
		}
	}

	return $events;
}

/**
 * get all the events that need to be displayed int he course calendar
 *
 * @param $coursedb the database of the course
 *
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University, Belgium
 * @version june 2010
 */
function get_all_course_agenda_items($coursedb, $coursecode = null){
	global $_user;
  	global $_course;
	// variable initialisation
	$events = array();

        if (!isset($coursecode)) {
            $coursecode = $_SESSION['sysCode'];
            if (empty($coursedb)) {
                    $coursedb = $_course['dbName'];
            }
        }
        
	// getting all the course events
        $editable = api_is_allowed_to_edit()?true:false;
        $course_events = get_course_agenda_items($coursedb, $coursecode, false, $editable);
        $events = array_merge($events,$course_events);

	// getting the platform events
	if (api_get_setting('calendar_types','platformevents') == 'true')
	{
		$platform_events = get_platform_agenda_items();
		$events = array_merge($events,$platform_events);
	}

	// getting the session events
	if (api_get_setting('calendar_types','sessionevents') == 'true')
	{
		$session_events = get_session_events($coursecode);
		$events = array_merge($events,$session_events);
	}

	// getting the quiz events
	if (api_get_setting('calendar_types','quizevents') == 'true')
	{
		$quiz_events = get_quiz_events($coursedb);
		$events = array_merge($events,$quiz_events);

	}

	// getting the assignment events
	$assignments_events = get_assignment_events($coursedb);
	$events = array_merge($events,$assignments_events);

	return $events;
}

/**
 * get the information of the sessions that are linked to this course
 *
 * @param $coursecode the (sys)code of the course
 *
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University, Belgium
 * @version august 2010
 */
function get_session_events($coursecode){
	global $_course;

	if (empty($coursecode)){
		if (empty($_course)){
			return array();
		} else {
			$coursecode = $_course['sysCode'];
		}
	}

	// variable initialisation
	$events = array();

	// Database table definition
	$table_session 				= Database::get_main_table(TABLE_MAIN_SESSION);
	$table_session_rel_course 	= Database::get_main_table(TABLE_MAIN_SESSION_COURSE);
                
	$sql = "SELECT session.id, session.name, UNIX_TIMESTAMP(session.date_start) as date_start, UNIX_TIMESTAMP(session.date_end) as date_end
		FROM $table_session session
		LEFT JOIN $table_session_rel_course sessioncourse ON session.id = sessioncourse.id_session
		WHERE sessioncourse.course_code = '".Database::escape_string($coursecode)."'";
	$result=Database::query($sql,__FILE__,__LINE__) or die(Database::error());
        
	while ($row=Database::fetch_array($result)) {
		//$events[] = array('id'=>'session'.$row['id'], 'title'=>htmlentities($row['name']), 'start'=>date('c',$row['date_start']), 'end'=>date('c',$row['date_end']), "allDay"=>false, 'visibility'=>'1','className'=>'session', 'editable' => false);
                $events[] = array('id'=>'session'.$row['id'], 'title'=>htmlentities($row['name']), 'start'=>date('c',$row['date_start']), 'end'=>date('c',$row['date_start']), "allDay"=>false, 'visibility'=>'1','className'=>'session', 'editable' => false);
	}                
	return $events;
}

/**
 * get the information of the quizes of this course
 *
 * @param $coursecode the (sys)code of the course
 *
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University, Belgium
 * @version august 2010
 */
function get_quiz_events($coursedb){
	global $_course;

	// variable initialisation
	$events = array();

	// Database table definition
	$table_quiz 			= Database :: get_course_table(TABLE_QUIZ_TEST,$coursedb);
	$table_item_property 	= Database :: get_course_table(TABLE_ITEM_PROPERTY,$coursedb);


	if (api_is_allowed_to_edit(false,true)){
		$sql = "SELECT quiz.id, quiz.title, UNIX_TIMESTAMP(quiz.start_time) as date_start, UNIX_TIMESTAMP(quiz.end_time) as end_start, item_property.visibility as visibilty
				FROM $table_quiz quiz, $table_item_property item_property
				WHERE item_property.tool= '".TOOL_QUIZ."'
				AND item_property.ref=quiz.id
				AND item_property.visibility <> '2'";
	} else {
		$sql = "SELECT quiz.id,quiz.title, UNIX_TIMESTAMP(quiz.start_time) as date_start, UNIX_TIMESTAMP(quiz.end_time) as end_start, item_property.visibility as visibilty
				FROM $table_quiz quiz, $table_item_property item_property
				WHERE item_property.tool= '".TOOL_QUIZ."'
				AND item_property.ref=quiz.id
				AND item_property.visibility = '1'";
	}

	// filter for the dates (if any)
	if (isset($_GET['start']) AND isset($_GET['end']) AND !empty($_GET['start']) AND !empty($_GET['end']))
	{
		$sql .= " AND start_time >= FROM_UNIXTIME(".$_GET['start'].")
				  AND start_time <= FROM_UNIXTIME(".$_GET['end'].")
				  ORDER BY start_time DESC";
	}

	$result=Database::query($sql,__FILE__,__LINE__) or die(Database::error());
	while ($row=Database::fetch_array($result))
	{
		//$events[] = array('id'=>'quiz'.$row['id'], 'title'=>htmlentities($row['title']), 'start'=>date('c',$row['date_start']), 'end'=>date('c',$row['date_end']), "allDay"=>false, 'visibility'=>$row['visiblity'],'className'=>'quiz', 'editable' => false);
                $events[] = array('id'=>'quiz'.$row['id'], 'title'=>htmlentities($row['title']), 'start'=>date('c',$row['date_start']), 'end'=>date('c',$row['date_start']), "allDay"=>false, 'visibility'=>$row['visiblity'],'className'=>'quiz', 'editable' => false);
	}

	return $events;
}

function get_assignment_events($coursedb)
{
	$events = array();

	// Database table definition
	$table_student_publication 				= Database :: get_course_table(TABLE_STUDENT_PUBLICATION,$coursedb);
	$table_student_publication_assignment	= Database :: get_course_table(TABLE_STUDENT_PUBLICATION_ASSIGNMENT,$coursedb);

	$sql = "SELECT
				student_publication.id 	AS id,
				student_publication.url AS title,
				UNIX_TIMESTAMP(student_publication.sent_date) AS date_start,
				UNIX_TIMESTAMP(student_publication_assignment.expires_on) AS date_end
			FROM $table_student_publication student_publication, $table_student_publication_assignment student_publication_assignment
			WHERE student_publication_assignment.add_to_calendar <> 0
			AND student_publication.id = student_publication_assignment.publication_id";

	$result=Database::query($sql,__FILE__,__LINE__) or die(Database::error());
	while ($row=Database::fetch_array($result))
	{
		//$events[] = array('id'=>'assignment'.$row['id'], 'title'=>htmlentities(str_replace('/','',$row['title'])), 'start'=>date('c',$row['date_start']), 'end'=>date('c',$row['date_end']), "allDay"=>false, 'visibility'=>'1','className'=>'assignment', 'editable' => false);
                $events[] = array('id'=>'assignment'.$row['id'], 'title'=>htmlentities(str_replace('/','',$row['title'])), 'start'=>date('c',$row['date_start']), 'end'=>date('c',$row['date_start']), "allDay"=>false, 'visibility'=>'1','className'=>'assignment', 'editable' => false);
	}
	//return array();
	return $events;
}

/**
 * get the platform events
 *
 * @param boolean $full_information should we get all the information or not
 * @param boolean $editable determines if the event is editable or not
 *
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University, Belgium
 * @version august 2010
 */
function get_platform_agenda_items($full_information=false,$editable = false) {
	// variable initialisation
	$events = array();

	// table definition
	$tbl_main_agenda	= Database::get_main_table(TABLE_MAIN_SYSTEM_CALENDAR);

	$sql = "SELECT agenda.*, UNIX_TIMESTAMP(start_date) as start, UNIX_TIMESTAMP(end_date) as end
				FROM $tbl_main_agenda agenda";

	// filter for the dates (if any)
	if (isset($_GET['start']) AND isset($_GET['end']) AND !empty($_GET['start']) AND !empty($_GET['end']))
	{
		$sql .= " WHERE start_date >= FROM_UNIXTIME(".Database::escape_string(Security::remove_XSS($_GET['start'])).")
				  AND start_date <= FROM_UNIXTIME(".Database::escape_string(Security::remove_XSS($_GET['end'])).")
				  ORDER BY start_date DESC";
	}
	else
	{
		$sql .= " ORDER BY start_date DESC";
	}

	$result=api_sql_query($sql,__FILE__,__LINE__);

	$events = array();
	while ($row=mysql_fetch_assoc($result))
	{
		if ($full_information)
		{
			$row['start_full'] 	= $row['start'];
			$row['start'] 		= date('c',$row['start']);
			$row['end_full'] 	= $row['end'];
			//$row['end'] 		= date('c',$row['end']);
                        $row['end'] 		= date('c',$row['start']);
			$row['allDay'] 		= false;
			$row['className'] 	= 'platform';
			$row['type']		= 'platform';
			$row['editable']	= $editable;
			$events[] 			= $row;
		}
		else
		{
			//$events[] = array('id'=>'platform'.$row['id'], 'title'=>htmlentities($row['title']), 'start'=>date('c',$row['start']), 'end'=>date('c',$row['end']), "allDay"=>false, 'visibility'=>'1','className'=>'platform', 'editable'=>$editable);
                        $events[] = array('id'=>'platform'.$row['id'], 'title'=>htmlentities($row['title']), 'start'=>date('c',$row['start']), 'end'=>date('c',$row['start']), "allDay"=>false, 'visibility'=>'1','className'=>'platform', 'editable'=>$editable);
	}
	}
	return $events;
}

/**
 * get the personal events
 *
 * @param boolean $full_information should we get all the information or not
 *
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University, Belgium
 * @version august 2010
 */
function get_personal_agenda_items($full_information=false) {
	global $_user;

	// variable initialisation
	$events = array();

	// table definition
	$tbl_agenda = Database :: get_user_personal_table(TABLE_PERSONAL_AGENDA);

	$sql = "SELECT agenda.*, UNIX_TIMESTAMP(date) as start, UNIX_TIMESTAMP(enddate) as end
				FROM $tbl_agenda agenda
				WHERE date >= FROM_UNIXTIME(".$_GET['start'].")
				AND date <= FROM_UNIXTIME(".$_GET['end'].")
				AND user = ".(int)Database::escape_string($_user['user_id']);
	$result=api_sql_query($sql,__FILE__,__LINE__);

	$events = array();
	while ($row=mysql_fetch_assoc($result))
	{
		if ($full_information)
		{
			$row['start_full'] 	= $row['start'];
			$row['start'] 		= date('c',$row['start']);
			$row['end_full'] 	= $row['end'];
			$row['end'] 		= date('c',$row['end']);
			$row['allDay'] 		= false;
			$row['className'] 	= $class_name;
			$row['type']		= 'course';
                        $row['parent_event_id'] =  isset($row['parent_event_id'])?intval($row['parent_event_id']):0;
                        $row['editable']	= !empty($row['parent_event_id'])?false:true;
			$events[$row['id']]     = $row;
		}
		else
		{
			$events[$row['id']] = array('id'=>'personal'.$row['id'], 'title'=>htmlentities($row['title']), 'start'=>date('c',$row['start']), 'end'=>date('c',$row['end']), "allDay"=>false, 'visibility'=>'1','className'=>'personal', 'parent_event_id' => isset($row['parent_event_id'])?intval($row['parent_event_id']):0, 'editable' => !empty($row['parent_event_id'])?false:true);
		}
	}
	return $events;
}

/**
 * Get all the agenda items: personal agenda items, platform agenda items and course agenda items
 * although the key of the separate types of events ($course_events, $personal_events and $platform_events)
 * all contain the id of the event they will not be overwritten thanks to a feature in the the array_merge function
 * http://be2.php.net/manual/en/function.array-merge.php
 * <quote>
 * 		If the input arrays have the same string keys, then the later value for that key will overwrite the previous one.
 * 		If, however, the arrays contain numeric keys, the later value will not overwrite the original value, but will be appended.
 * 		If all of the arrays contain only numeric keys, the resulting array is given incrementing keys starting from zero.
 * </quote>
 *
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University, Belgium
 * @version june 2010
 */

function get_all_agenda_items($full_information=false) {
	// getting all the courses that this user is subscribed to
	$courses = get_all_courses_of_user();
	if (!is_array($courses)) // this is for the special case if the user has no courses (otherwise you get an error)
	{
		$courses = array ();
	}

	// variable initialisation
	$events = array();
	// getting all the calendar events of the courses
        $course_events = array();
	foreach ($courses as $key=>$course){                        
            $course_events = get_all_course_agenda_items($course['db'], $course['code']);
            $events = array_merge($events,$course_events);
	}
        
	// getting the personal calendar events
	$personal_events = get_personal_agenda_items($full_information);
	$events = array_merge($events,$personal_events);

	// getting the platform agenda items
	$platform_events = get_platform_agenda_items($full_information,false);
	$events = array_merge($events,$platform_events);

	return $events;
}

/**
 * create output
 *
 * @param array $events the events of the calendar
 * @param string $output determines how the information should be outputted
 *
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University, Belgium
 * @version june 2010
 */
function create_agenda_output($events,$output){
	switch($output){
		case 'json':
			echo json_encode($events);
			break;
		case 'list':
			echo list_output($events);
			break;
	}
}

/**
 * create the list output
 *
 * @param array $events the events of the calendar
 *
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University, Belgium
 * @version june 2010
 */
function list_output($events)
{
	ob_start();

	// Defining the months of the year to allow translation of the months. We use camelcase because these are arrays of language variables
	$MonthsLong = array (get_lang("JanuaryLong"), get_lang("FebruaryLong"), get_lang("MarchLong"), get_lang("AprilLong"), get_lang("MayLong"), get_lang("JuneLong"), get_lang("JulyLong"), get_lang("AugustLong"), get_lang("SeptemberLong"), get_lang("OctoberLong"), get_lang("NovemberLong"), get_lang("DecemberLong"));

	// defining the colors of the predefined types of events
	$color['course'] 	= 'red';
	$color['platform']	= '#FFCC00';
	$color['personal'] 	= 'green';

	// initialisation
	$oldmonth 	= '';
	$oldyear 	= '';

	// looping through the events
	foreach ($events as $key=>$event)
	{
		// all the information about the start date
		$year 		= substr($event['start'],0,4);
		$month 		= substr($event['start'],5,2);
		$day 		= substr($event['start'],0,4);
		$hour		= substr($event['start'],0,4);
		$minutes	= substr($event['start'],0,4);

		// initiate the object
		$form = new FormValidator ( 'new_agenda_item', 'post', $_SERVER ['REQUEST_URI'] );
		$form->addElement ( 'static', 'start_date', get_lang ( 'StartDate' ),'<strong>'.agenda_date($event,'start').'</strong>');
		$form->addElement ( 'static', 'end_date', get_lang ( 'EndDate' ),'<strong>'.agenda_date($event,'end').'</strong>');
		$form->addElement ( 'static', 'title', get_lang ( 'Title' ), $event['title']);
		$form->addElement ( 'static', 'content', get_lang ( 'Text' ), text_filter(strip_tags($event['content'])));

		// Event action icons
		if (api_is_allowed_to_edit() OR $event['type'] == 'personal')
		{
			if ($event['type'] == 'personal')
			{
				$action_edit = 'editpersonal';
				$action_delete = 'deletepersonal';
			}
			else
			{
				$action_edit = 'edit';
				$action_delete = 'delete';
			}

			if ($event['type'] <> 'platform')
			{
				$actionicons .= '<a href="agenda.php?action='.$action_edit.'&amp;id='.$event['id'].'&amp;view=list&amp;reverse='.$_GET['reverse'].'">'.Display :: return_icon('edit.png', get_lang('Edit')).'</a>';
				$actionicons .= '<a href="agenda.php?action='.$action_delete.'&amp;id='.$event['id'].'&amp;view=list&amp;reverse='.$_GET['reverse'].'">'.Display :: return_icon('delete.png', get_lang('Delete'), array('onclick' => "javascript:if(!confirm('".addslashes(htmlentities(get_lang("ConfirmYourChoice")))."')) return false;")).'</a>';
				if ($event['type'] <> 'personal')
				{
					if ($event['visibility']=='1')
					{
						$actionicons .= '<a href="agenda.php?action=hide&amp;id='.$event['id'].'&amp;view='.$_GET['view'].'&amp;reverse='.$_GET['reverse'].'">'.Display :: return_icon('visible.gif', get_lang('Visible'),array('class'=>'visibility')).'</a>';
					}
					else
					{
						$actionicons .= '<a href="agenda.php?action=show&amp;id='.$event['id'].'&amp;view='.$_GET['view'].'&amp;reverse='.$_GET['reverse'].'">'.Display :: return_icon('invisible.gif', get_lang('Invisible'),array('class'=>'visibility')).'</a>';
					}
				}
			}
		}
		$actionicons .= '<a href="agenda.php?'.api_get_cidreq().'&action=export&amp;id='.$event['id'].'">'.Display :: return_icon('download_manager.gif', get_lang('IcalExport')).' '.get_lang('IcalExport').'</a>';
		$form->addElement ( 'static', 'actionicons', '', $actionicons);


		$form->display();
	}
	$return = ob_get_contents();
	ob_end_clean();

	return $return;
}

function agenda_date($event,$startend){
		if (!is_array($event[$startend.'_date']))
		{
			// happens when information is retrieved from get_course_agena_itemS()
			$return .= $event[$startend.'_date'];
		}
		else
		{
			// happens when information is retrieved from get_course_agenda_item() (intended for filling the edit form)
			$return .= date('Y-m-d H:i:s',$event[$startend.'_full']);
		}
	return $return;
}

/**
 * This function finds all the courses (also those of sessions) of the user and returns an array containing the
 * database name of the courses.
 * @author Noel Dieschburg <noel.dieschburg@dokeos.com>
 * @todo there is probably a better function in course.lib.php
 */
function get_all_courses_of_user()
{
		global $_user;

		// Database table definition
        $tbl_course 				= Database :: get_main_table(TABLE_MAIN_COURSE);
		$tbl_course_user 			= Database :: get_main_table(TABLE_MAIN_COURSE_USER);
        $tbl_session_course     	= Database :: get_main_table(TABLE_MAIN_SESSION_COURSE);
        $tbl_session_course_user	= Database :: get_main_table(TABLE_MAIN_SESSION_COURSE_USER);
        $tbl_session				= Database :: get_main_table(TABLE_MAIN_SESSION);


        $sql_select_courses = "SELECT c.code k, c.visual_code  vc, c.title i, c.tutor_name t,
                                      c.db_name db, c.directory dir, '5' as status
                                FROM $tbl_course c, $tbl_session_course_user srcu
                                WHERE srcu.id_user='".$_user['user_id']."'
                                AND c.code=srcu.course_code
                                UNION
                               SELECT c.code k, c.visual_code  vc, c.title i, c.tutor_name t,
                                      c.db_name db, c.directory dir, cru.status status
                                FROM $tbl_course c, $tbl_course_user cru
                                WHERE cru.user_id='".Database::escape_string($_user['user_id'])."'
                                AND c.code=cru.course_code";
        $result = Database::query($sql_select_courses);
        while ($row = Database::fetch_array($result))
        {
                $courses[] = array ('db' => $row['db'], 'code' => $row['k'], 'visual_code' => $row['vc'], 'title' => $row['i'], 'directory' => $row['dir'], 'status' => $row['status']);
        }
        return $courses;
 }

/**
 * export the events to the ical format
 *
 * @param string $content determines what has to be exported (personal events, platform events, course events)
 * @param string $id (optionally) which event has to be exported
 *
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University, Belgium
 * @version june 2010
 */
function export_events($content,$id)
{
	global $_course, $_user;

	include_once('ical/class.iCal.inc.php');

	// variable initialisation
	$events = array();

	// exporting the course events
	if ($content == 'course' AND !empty($_course))
	{
		if (empty($id))
		{
			$events = get_course_agenda_items('','', true);
		}
		else
		{
			$events[] = get_course_agenda_item($id);
		}
	}

	// exporting the personal events
	if ($content == 'personal')
	{
		if (empty($id))
		{
			$events = get_personal_agenda_items(true);
		}
	}

	// exporting the platform events
	if ($content == 'platform')
	{
		if (empty($id))
		{
			$events = get_platform_agenda_items(true);
		}
	}

	// exporting the platform events
	if ($content == 'all')
	{
		$events = get_all_agenda_items(true);
	}

	$iCal = (object) new iCal('', 0, ''); // (ProgrammID, Method (1 = Publish | 0 = Request), Download Directory)

	foreach ($events as $key=>$event)
	{
		$iCal->addEvent(
						'', // Organizer
						(int)$event['start_full'], // Start Time (timestamp; for an allday event the startdate has to start at YYYY-mm-dd 00:00:00)
						(int)$event['end_full'], // End Time (write 'allday' for an allday event instead of a timestamp)
						'', // Location
						0, // Transparancy (0 = OPAQUE | 1 = TRANSPARENT)
						'', // Array with Strings
						$event['content'], // Description
						$event['title'], // Title
						0, // Class (0 = PRIVATE | 1 = PUBLIC | 2 = CONFIDENTIAL)
						'', // Array (key = attendee name, value = e-mail, second value = role of the attendee [0 = CHAIR | 1 = REQ | 2 = OPT | 3 =NON])
						5, // Priority = 0-9
						0, // frequency: 0 = once, secoundly - yearly = 1-7
						10, // recurrency end: ('' = forever | integer = number of times | timestring = explicit date)
						'', // Interval for frequency (every 2,3,4 weeks...)
						'', // Array with the number of the days the event accures (example: array(0,1,5) = Sunday, Monday, Friday
						0, // Startday of the Week ( 0 = Sunday - 6 = Saturday)
						'', // exeption dates: Array with timestamps of dates that should not be includes in the recurring event
						'',  // Sets the time in minutes an alarm appears before the event in the programm. no alarm if empty string or 0
						1, // Status of the event (0 = TENTATIVE, 1 = CONFIRMED, 2 = CANCELLED)
						'http://minerva.ugent.be', // optional URL for that event
						'en', // Language of the Strings
		                'Minerva.ugent.be/'.$event['type'].'/'.$_course['code'].'/'.$event['id'] // Optional UID for this event
					   );
	}
	//debug($events);
	//debug($iCal);
	$iCal->outputFile('ics'); // output file as ics (xcs and rdf possible)
}

/**
 * Display the detail view of an event
 *
 * @param integer $resouce_id the id of the resource "quiz,assignment,agenda..." that has to be displayed
 * @param string $event_type the type of event "quiz,assignment,session,platform,course event" that has to be displayed
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University, Belgium
 * @author Isaac flores <florespaz_isaac@hotmail.com>
 * @version june 2010
 */
function display_detail($resouce_id, $event_type)
{       // We display the event detail here
        switch ($event_type) {
          case 'course_events':
	    $event = get_course_agenda_item($resouce_id);
            break;
          case 'quiz':
	    $event = get_quiz_events_by_quiz_id($resouce_id);
            break;
          case 'assignment':
	    $event = get_assignment_events_by_assignment_id($resouce_id);
            break;
          case 'session':
	    $event = get_session_events_by_session_id($resouce_id);
            break;
          case 'platform':
	    $event = get_platformagenda_item($resouce_id);
            break;
           default :
             $event = array();
        }

	// getting the user information and group information if neccessary
	if ($event['send_to'] ['receivers'] == '1')
	{
		$audience = create_audience($event['send_to'] ['to']);
	}

	echo list_output(array($event));
}

/**
 * create a list of people / groups that the event has been sent to
 *
 * @param array $array an array containing all the users and groups that have received the event
 * @param string $returnas determines how the information should be outputted
 *
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University, Belgium
 * @version june 2010
 */
function create_audience($array,$returnas='html')
{
	global $_course;

	// Database table definition
	$tbl_user = Database :: get_main_table(TABLE_MAIN_USER);

	// variable initialisation
	$groups = array();
	$users = array();
	$return = '';

	// first we loop through the array to separate the users and the groups
	foreach ($array as $key=>$value)
	{
		if (substr($value,0,1) == 'U')
		{
			$users[]=str_ireplace('U','',$value);
		}
			if (substr($value,0,1) == 'G')
		{
			$groups[]=str_ireplace('G','',$value);
		}
	}

	// get all the needed information of the users
	if (!empty($users))
	{
		$sql = "SELECT * FROM $tbl_user WHERE user_id IN ('".Database::escape_string(implode(',',$users))."')";
		$result=api_sql_query($sql,__FILE__,__LINE__) or die(Database::error());
		while ($userinfo=Database::fetch_array($result))
		{
			if ($returnas == 'html')
			{
				$return_array[] = '<a href="../user/userInfo.php?editMainUserInfo='.$userinfo['user_id'].'">'.$userinfo['firstname'].' '.$userinfo['lastname'].'</a>';
			}
			if ($returnas == 'array')
			{
				$return_array[] = $userinfo;
			}
		}
	}

	// get all the needed information of the groups
	if (!empty($groups))
	{
		$groupinfo = GroupManager::get_group_list(null, $_course['course_code']);

		foreach ($groups as $key=>$groupid)
		{
			if ($returnas == 'html')
			{
				$return_array[] = '<a href="../group/group_space.php?gidReq='.$groupid.'">'.$groupinfo[$groupid]['name'].'</a>';
			}
			if ($returnas == 'array')
			{
				$return_array[] = $groupinfo[$groupid];
			}
		}
	}

	if ($returnas == 'html')
	{
		return implode(', ',$return_array);
	}
	if ($returnas == 'array')
	{
		return $return_array;
	}
}

function calendar_javascript(){
	// defaultview (this has to become a platform setting)
	if ($_GET ['view'] and in_array ( $_GET ['view'], array ('month', 'agendaWeek', 'agendaDay' ) )) {
		$defaultview = 'defaultView: \'' . Security::remove_XSS($_GET ['view']) . '\',';
	} else {
		$defaultview = 'defaultView: \'' . api_get_setting('agenda_default_view') . '\',';
	}

	// edit, delete and visiblity buttons or not
	if (api_get_setting('agenda_action_icons') == 'true'){
		$actionicons = "$(this).children('a').append('<span class=\"fc-event-actions\"><img src=\"../img/edit.png\" id=\"edit_'+calEvent.id+'\" class=\"edit\" alt=\"" . get_lang ( 'Edit' ) . "\"/><img src=\"../img/delete.png\" id=\"delete_'+calEvent.id+'\" class=\"delete\" alt=\"" . get_lang ( 'Delete' ) . "\"/><img src=\"../img/'+visibility_icon+'\" id=\"visibility_'+calEvent.id+'\" class=\"visibility\" alt=\"" . get_lang ( 'ChangeVisibility' ) . "\"/><img src=\"../img/export.png\" id=\"export_'+calEvent.id+'\" class=\"export\" alt=\"" . get_lang ( 'IcalExport' ) . "\"/></span>');";
	}

	// google calendar import
	if (api_get_setting('calendar_google_import')=='true'){
		$google_calendar_get_id_js = google_calendar_get_id_js();
	}

	// detail view
	if (api_get_setting('calendar_detail_view') == 'detail'){
		$detailview = 'detail';
	} else {
		$detailview = 'edit';
	}

	// how should the calendar header look like
	if (api_get_setting('calendar_navigation') == 'actions'){
		$header = 	"header: {
						left: '',
						center: '',
						right: ''
					},";
	} else {
		$header = 	"header: {
						left: 'prev,next today',
						center: 'title',
						right: 'month,agendaWeek,agendaDay'
					},";
	}

	// Sources of the events
	global $sources, $htmlHeadXtra;
	$sources[] = "'ajax.php?action=getevents&output=json&group=".Security::Remove_XSS($_GET['group'])."'";
	if (api_get_setting('calendar_google_import')=='true'){
		google_calendar_sources();
	}

	if (api_is_allowed_to_edit())
	{
		return "<script type='text/javascript'>

		function html_entity_decode (string, quote_style) {
			// http://kevin.vanzonneveld.net
			// +   original by: john (http://www.jd-tech.net)
			// +      input by: ger
			// +   improved by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
			// +    revised by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
			// +   bugfixed by: Onno Marsman
			// +   improved by: marc andreu
			// +    revised by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
			// +      input by: Ratheous
			// +   bugfixed by: Brett Zamir (http://brett-zamir.me)
			// +      input by: Nick Kolosov (http://sammy.ru)
			// +   bugfixed by: Fox
			// -    depends on: get_html_translation_table
			// *     example 1: html_entity_decode('Kevin &amp; van Zonneveld');
			// *     returns 1: 'Kevin & van Zonneveld'
			// *     example 2: html_entity_decode('&amp;lt;');
			// *     returns 2: '&lt;'

			var hash_map = {}, symbol = '', tmp_str = '', entity = '';
			tmp_str = string.toString();

			if (false === (hash_map = this.get_html_translation_table('HTML_ENTITIES', quote_style))) {
			    return false;
			}

			// fix &amp; problem
			// http://phpjs.org/functions/get_html_translation_table:416#comment_97660
			delete(hash_map['&']);
			hash_map['&'] = '&amp;';

			for (symbol in hash_map) {
			    entity = hash_map[symbol];
			    tmp_str = tmp_str.split(entity).join(symbol);
			}
			tmp_str = tmp_str.split('&#039;').join(\"'\");

			return tmp_str;
		}

		function get_html_translation_table (table, quote_style) {
		    // http://kevin.vanzonneveld.net
		    // +   original by: Philip Peterson
		    // +    revised by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
		    // +   bugfixed by: noname
		    // +   bugfixed by: Alex
		    // +   bugfixed by: Marco
		    // +   bugfixed by: madipta
		    // +   improved by: KELAN
		    // +   improved by: Brett Zamir (http://brett-zamir.me)
		    // +   bugfixed by: Brett Zamir (http://brett-zamir.me)
		    // +      input by: Frank Forte
		    // +   bugfixed by: T.Wild
		    // +      input by: Ratheous
		    // %          note: It has been decided that we're not going to add global
		    // %          note: dependencies to php.js, meaning the constants are not
		    // %          note: real constants, but strings instead. Integers are also supported if someone
		    // %          note: chooses to create the constants themselves.
		    // *     example 1: get_html_translation_table('HTML_SPECIALCHARS');
		    // *     returns 1: {'\"': '&quot;', '&': '&amp;', '<': '&lt;', '>': '&gt;'}

		    var entities = {}, hash_map = {}, decimal = 0, symbol = '';
		    var constMappingTable = {}, constMappingQuoteStyle = {};
		    var useTable = {}, useQuoteStyle = {};

		    // Translate arguments
		    constMappingTable[0]      = 'HTML_SPECIALCHARS';
		    constMappingTable[1]      = 'HTML_ENTITIES';
		    constMappingQuoteStyle[0] = 'ENT_NOQUOTES';
		    constMappingQuoteStyle[2] = 'ENT_COMPAT';
		    constMappingQuoteStyle[3] = 'ENT_QUOTES';

		    useTable       = !isNaN(table) ? constMappingTable[table] : table ? table.toUpperCase() : 'HTML_SPECIALCHARS';
		    useQuoteStyle = !isNaN(quote_style) ? constMappingQuoteStyle[quote_style] : quote_style ? quote_style.toUpperCase() : 'ENT_COMPAT';

		    if (useTable !== 'HTML_SPECIALCHARS' && useTable !== 'HTML_ENTITIES') {
			throw new Error(\"Table: \"+useTable+' not supported');
			// return false;
		    }

		    entities['38'] = '&amp;';
		    if (useTable === 'HTML_ENTITIES') {
			entities['160'] = '&nbsp;';
			entities['161'] = '&iexcl;';
			entities['162'] = '&cent;';
			entities['163'] = '&pound;';
			entities['164'] = '&curren;';
			entities['165'] = '&yen;';
			entities['166'] = '&brvbar;';
			entities['167'] = '&sect;';
			entities['168'] = '&uml;';
			entities['169'] = '&copy;';
			entities['170'] = '&ordf;';
			entities['171'] = '&laquo;';
			entities['172'] = '&not;';
			entities['173'] = '&shy;';
			entities['174'] = '&reg;';
			entities['175'] = '&macr;';
			entities['176'] = '&deg;';
			entities['177'] = '&plusmn;';
			entities['178'] = '&sup2;';
			entities['179'] = '&sup3;';
			entities['180'] = '&acute;';
			entities['181'] = '&micro;';
			entities['182'] = '&para;';
			entities['183'] = '&middot;';
			entities['184'] = '&cedil;';
			entities['185'] = '&sup1;';
			entities['186'] = '&ordm;';
			entities['187'] = '&raquo;';
			entities['188'] = '&frac14;';
			entities['189'] = '&frac12;';
			entities['190'] = '&frac34;';
			entities['191'] = '&iquest;';
			entities['192'] = '&Agrave;';
			entities['193'] = '&Aacute;';
			entities['194'] = '&Acirc;';
			entities['195'] = '&Atilde;';
			entities['196'] = '&Auml;';
			entities['197'] = '&Aring;';
			entities['198'] = '&AElig;';
			entities['199'] = '&Ccedil;';
			entities['200'] = '&Egrave;';
			entities['201'] = '&Eacute;';
			entities['202'] = '&Ecirc;';
			entities['203'] = '&Euml;';
			entities['204'] = '&Igrave;';
			entities['205'] = '&Iacute;';
			entities['206'] = '&Icirc;';
			entities['207'] = '&Iuml;';
			entities['208'] = '&ETH;';
			entities['209'] = '&Ntilde;';
			entities['210'] = '&Ograve;';
			entities['211'] = '&Oacute;';
			entities['212'] = '&Ocirc;';
			entities['213'] = '&Otilde;';
			entities['214'] = '&Ouml;';
			entities['215'] = '&times;';
			entities['216'] = '&Oslash;';
			entities['217'] = '&Ugrave;';
			entities['218'] = '&Uacute;';
			entities['219'] = '&Ucirc;';
			entities['220'] = '&Uuml;';
			entities['221'] = '&Yacute;';
			entities['222'] = '&THORN;';
			entities['223'] = '&szlig;';
			entities['224'] = '&agrave;';
			entities['225'] = '&aacute;';
			entities['226'] = '&acirc;';
			entities['227'] = '&atilde;';
			entities['228'] = '&auml;';
			entities['229'] = '&aring;';
			entities['230'] = '&aelig;';
			entities['231'] = '&ccedil;';
			entities['232'] = '&egrave;';
			entities['233'] = '&eacute;';
			entities['234'] = '&ecirc;';
			entities['235'] = '&euml;';
			entities['236'] = '&igrave;';
			entities['237'] = '&iacute;';
			entities['238'] = '&icirc;';
			entities['239'] = '&iuml;';
			entities['240'] = '&eth;';
			entities['241'] = '&ntilde;';
			entities['242'] = '&ograve;';
			entities['243'] = '&oacute;';
			entities['244'] = '&ocirc;';
			entities['245'] = '&otilde;';
			entities['246'] = '&ouml;';
			entities['247'] = '&divide;';
			entities['248'] = '&oslash;';
			entities['249'] = '&ugrave;';
			entities['250'] = '&uacute;';
			entities['251'] = '&ucirc;';
			entities['252'] = '&uuml;';
			entities['253'] = '&yacute;';
			entities['254'] = '&thorn;';
			entities['255'] = '&yuml;';
		    }

		    if (useQuoteStyle !== 'ENT_NOQUOTES') {
			entities['34'] = '&quot;';
		    }
		    if (useQuoteStyle === 'ENT_QUOTES') {
			entities['39'] = '&#39;';
		    }
		    entities['60'] = '&lt;';
		    entities['62'] = '&gt;';


		    // ascii decimals to real symbols
		    for (decimal in entities) {
			symbol = String.fromCharCode(decimal);
			hash_map[symbol] = entities[decimal];
		    }

		    return hash_map;
		}

			$(document).ready(function() {

				var date = new Date();
				var d = date.getDate();
				var m = date.getMonth();
				var y = date.getFullYear();

				var DokeosCalendar = $('#calendar').fullCalendar({
					".$header."
					buttonText: { today: '".addslashes(get_lang('Today'))."', month: '".addslashes(get_lang('MonthView'))."', week: '".addslashes(get_lang('WeekView'))."', day: '".addslashes(get_lang('DayView'))."'}, 
					monthNames: ['".addslashes(ucfirst(get_lang('JanuaryLong')))."', '".addslashes(ucfirst(get_lang('FebruaryLong')))."', '".addslashes(ucfirst(get_lang('MarchLong')))."', '".addslashes(ucfirst(get_lang('AprilLong')))."', '".addslashes(ucfirst(get_lang('MayLong')))."', '".addslashes(ucfirst(get_lang('JuneLong')))."', '".addslashes(ucfirst(get_lang('JulyLong')))."', '".addslashes(ucfirst(get_lang('AugustLong')))."', '".addslashes(ucfirst(get_lang('SeptemberLong')))."', '".addslashes(ucfirst(get_lang('OctoberLong')))."', '".addslashes(ucfirst(get_lang('NovemberLong')))."', '".addslashes(ucfirst(get_lang('DecemberLong')))."'],
					monthNamesShort: ['".addslashes(ucfirst(get_lang('JanuaryShort')))."', '".addslashes(ucfirst(get_lang('FebruaryShort')))."', '".addslashes(ucfirst(get_lang('MarchShort')))."', '".addslashes(ucfirst(get_lang('AprilShort')))."', '".addslashes(ucfirst(get_lang('MayShort')))."', '".addslashes(ucfirst(get_lang('JuneShort')))."', '".addslashes(ucfirst(get_lang('JulyShort')))."', '".addslashes(ucfirst(get_lang('AugustShort')))."', '".addslashes(ucfirst(get_lang('SeptemberShort')))."', '".addslashes(ucfirst(get_lang('OctoberShort')))."', '".addslashes(ucfirst(get_lang('NovemberShort')))."', '".addslashes(ucfirst(get_lang('DecemberShort')))."'],
					dayNames: ['".addslashes(ucfirst(get_lang('SundayLong')))."', '".addslashes(ucfirst(get_lang('MondayLong')))."', '".addslashes(ucfirst(get_lang('TuesdayLong')))."', '".addslashes(ucfirst(get_lang('WednesdayLong')))."', '".addslashes(ucfirst(get_lang('ThursdayLong')))."', '".addslashes(ucfirst(get_lang('FridayLong')))."', '".addslashes(ucfirst(get_lang('SaturdayLong')))."'],
					dayNamesShort: ['".addslashes(ucfirst(get_lang('SundayShort')))."', '".addslashes(ucfirst(get_lang('MondayShort')))."', '".addslashes(ucfirst(get_lang('TuesdayShort')))."', '".addslashes(ucfirst(get_lang('WednesdayShort')))."', '".addslashes(ucfirst(get_lang('ThursdayShort')))."', '".addslashes(ucfirst(get_lang('FridayShort')))."', '".addslashes(ucfirst(get_lang('SaturdayShort')))."'],
					weekMode: 'variable',
					allDaySlot: false,
					firstDay: 1,
					axisFormat: 'HH(:mm)',
					timeFormat: 'HH:mm{ - HH:mm}',
					height: 1200,
					" . $defaultview . "
					editable: true,
					eventSources: [
						".implode(",",$sources)."
						],
					eventMouseover: function(calEvent,jsEvent) {
							// the appropriate visibility icon
							if (calEvent.visibility == 1){
								var visibility_icon = 'visible.gif';
							} else {
								var visibility_icon = 'invisible.gif';
							}
						if ($(this).hasClass('courseagenda')){
							".$actionicons."
						}
						if ($(this).hasClass('gcal-event')) {
							$(this).children('a').append('<span class=\"fc-event-actions\"><img src=\"../img/edit.gif\" id=\"edit_google_'+calEvent.id+'\" class=\"edit gcaledit\" alt=\"" . get_lang ( 'Edit' ) . "\"/><img src=\"../img/delete.gif\" id=\"delete_google_'+calEvent.id+'\" class=\"delete gcaldelete\" alt=\"" . get_lang ( 'Delete' ) . "\"/></span>');
						}
					},
					eventMouseout: function(calEvent,jsEvent) {
							$('.fc-event-actions').remove();
					},
					eventRender: function(calEvent, element) {
						// add the id to the rendered element so that we can use this for the detail
						element.attr('id',calEvent.id);
						$('.fc-event-title',element).html(html_entity_decode(calEvent.title));
					},
					eventDrop: function(event,dayDelta,minuteDelta,allDay,revertFunc) {
						$.ajax({
						  url: 'ajax.php',
						  data: {action: 'move', id: event.id, daydelta: dayDelta, minutedelta: minuteDelta}
						});
					},
				eventResize: function(event,dayDelta,minuteDelta,revertFunc) {
					$.ajax({
					  url: 'ajax.php',
					  data: {action: 'resize', id: event.id, daydelta: dayDelta, minutedelta: minuteDelta}
					});
		    	},
		    	dayClick: function(date, allDay, jsEvent, view){
		    		if(view.name == 'month'){
						DokeosCalendar.fullCalendar( 'changeView', 'agendaDay').fullCalendar( 'gotoDate', date );
					}
		    		//$(location).attr('href','agenda.php?action=add&date='+$.fullCalendar.formatDate( date, 'yyyy/MM/dd/HH/mm'));
		    	},
		    	eventClick: function(event,jsEvent,view){

				}
			});

			// clicking the event. We could have used the eventClick functionality of fullcalendar but this caused problems when changing the visibility
			$('.courseagenda').live('click', function(){
				        id=$(this).attr('id');
                        // Generic variables
                        var data_id = new  Array();
                        var get_id = 0;
                        var _event_type = '';
                        var find = true;
                        // Get the platform event ID
                        try {
                          _event_type = 'platform';
                          data_id = id.split('platform');
                          get_id = data_id[1];// Get the ID of the platform event
                          if (get_id > 0) {
                            find = false;
                          }
                        } catch(e){get_id = 0;}

                        // Get the assignment event ID
                        if (find === true) {
                          try {
                            _event_type = 'assignment';
                            data_id = id.split('assignment');
                            get_id = data_id[1];// Get the ID of the assignment
                            if (get_id > 0) {
                              find = false;
                            }
                          } catch(e){get_id = 0;}
                        }

                        // Get the quiz event ID
                        if (find === true) {
                          try {
                            _event_type = 'quiz';
                            data_id = id.split('quiz');
                            get_id = data_id[1];// Get the ID of the quiz
                            if (get_id > 0) {
                              find = false;
                            }
                          } catch(e){get_id = 0;}
                        }

                        // Get the session event ID
                        if (find === true) {
                          try {
                            _event_type = 'session';
                            data_id = id.split('session');
                            get_id = data_id[1];// Get the ID of the session
                            if (get_id > 0) {
                              find = false;
                            }
                          } catch(e){get_id = 0;}
                        }

                        // Get the course event ID
                        if (find === true) {
                          try {
                            _event_type = 'course_events';
                            data_id = id.split('course_events');
                            get_id = data_id[1];// Get the ID of the session
                            if (get_id > 0) {
                              find = false;
                            }
                          } catch(e){get_id = 0;}
                        }
      
				$(location).attr('href','agenda.php?".api_get_cidreq()."&action=".$detailview."&id='+get_id+'&event_type='+_event_type);
			});

			// clicking the edit icon
				$('.fc-event-actions .edit').live('click', function(){
					id=$(this).attr('id');
					var gcal_id = '';
					".$google_calendar_get_id_js."
					$(location).attr('href','agenda.php?".api_get_cidreq()."&action=edit&id='+id.replace('edit_','') + '&calendar=' + gcal_id);
					event.stopPropagation();
					return false;
				});

			// clicking the delete icon
			$('.fc-event-actions .delete').live('click', function(){
				id=$(this).attr('id');

				var gcal_id = '';
				".$google_calendar_get_id_js."

				// remove from database
				$.ajax({
				  url: 'ajax.php',
				  data: {action: 'delete', id: id.replace('delete_',''), calendar: gcal_id},
				  success: function(data){
						$('#content').html(data);
					}
				});

				// get the fc_index
				var fc_index = $('.fc-event').index($(this).parent().parent().parent());

				// remove the fc-event
				DokeosCalendar.fullCalendar('removeEvents',id.replace('delete_',''));

				// also updating the list view of the item
				$('#list_'+id.replace('delete_','')).remove();

				return false;
			});

			// clicking the visibility icon
			$('.fc-event-actions .visibility').live('click', function(){
				id=$(this).attr('id');
				current_status = $(this).attr('src').replace('../img/','').replace('.gif','');

				// change visibility
				$.ajax({
				  url: 'ajax.php',
				  data: {action: 'visibility', id: id.replace('visibility_',''), status: current_status},
				  success: function(){
						// change the icon
						if (current_status == 'visible'){
							var new_action_icon = '../img/invisible.gif';
							$(this).attr('src',new_action_icon);
							var new_visibility = 0;
							var list_view_wrapper_class = 'agenda_item_wrapper_hidden';
						} else {
							var new_action_icon = '../img/visible.gif';
							$(this).attr('src',new_action_icon);
							var new_visibility = 1;
							var list_view_wrapper_class = 'agenda_item_wrapper';
						}

						// toggle the class (invisible) (not needed because handled by 'refetchEvents')
						//$(this).parent().parent().parent().toggleClass('invisible');

						// get the fc_index
						var fc_index = $('.fc-event').index($(this).parent().parent().parent());

						// updating the event information
						DokeosCalendar.fullCalendar( 'refetchEvents' );

						// also updating the list view of the item
						$('#list_'+id.replace('visibility_','')).attr('class',list_view_wrapper_class);
						$('#list_'+id.replace('visibility_','')+' .visibility').attr('src',new_action_icon);
			      }
				});
				event.stopPropagation();
			});

			// clicking the export icon
			$('.fc-event-actions .export').live('click', function(){
				id=$(this).attr('id');
				$(location).attr('href','agenda.php?action=export&id='+id.replace('export_',''));
				event.stopPropagation();
			});

			// add the list button
			// $('.fc-header-right table tbody tr td').eq(0).before('<td><div class=\"fc-button-list fc-state-default fc-no-right fc-corner-left\"><a><span>" . get_lang ( 'ListView' ) . "</span></a></div></td>');
	
			// show the list view
			$('.fc-button-list').click(function(){
				DokeosCalendar.fullCalendar('changeView','basicWeek');

				// make all the view buttons inactive
				$('.fc-state-active').toggleClass('fc-state-active');

				// make the current view button active
				$(this).toggleClass('fc-state-active');

				// hide all the views
				$('.fc-view').hide();

				// add a new view or show if if it already exists (but hidden)
				if ( $('.fc-view-list').length ){
					$('.fc-view-list').show();
				} else {
					$.ajax({
					  url: 'ajax.php',
					  data: {action: 'getevents', output: 'list', full_info: 'true'},
					  success: function(data){
							$('.fc-content').append('<div class=\"fc-view fc-view-list\">'+data+'</div>');
							$('.visiblefor').expander({
							    slicePoint:       200,  // default is 100
							    expandText:         '[...]', // default is 'read more...'
							    collapseTimer:    5000, // re-collapses after 5 seconds; default is 0, so no re-collapsing
							    userCollapseText: '[^]'  // default is '[collapse expanded text]'
							  });
						}
					});
					  }
					});

			// desactive the list button when another button is clicked and hide the list view
			$('.fc-button-month, .fc-button-agendaWeek, .fc-button-agendaDay').click(function(){
				$('.fc-button-list').removeClass('fc-state-active');
				$('.fc-view-list').hide();
			});

			// change the size if month view is clicked
			$('.fc-button-month').click(function(){
				DokeosCalendar.fullCalendar('option', 'height', 500);
			});

			// change the size if month view is clicked
			$('.fc-button-agendaWeek, .fc-button-agendaDay, .fc-content').click(function(){
				DokeosCalendar.fullCalendar('option', 'height', 1200);
			});

		});

	</script>";
	} else  { // Code for display the calendar for students
		return "<script type='text/javascript'>

		function html_entity_decode (string, quote_style) {
			// http://kevin.vanzonneveld.net
			// +   original by: john (http://www.jd-tech.net)
			// +      input by: ger
			// +   improved by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
			// +    revised by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
			// +   bugfixed by: Onno Marsman
			// +   improved by: marc andreu
			// +    revised by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
			// +      input by: Ratheous
			// +   bugfixed by: Brett Zamir (http://brett-zamir.me)
			// +      input by: Nick Kolosov (http://sammy.ru)
			// +   bugfixed by: Fox
			// -    depends on: get_html_translation_table
			// *     example 1: html_entity_decode('Kevin &amp; van Zonneveld');
			// *     returns 1: 'Kevin & van Zonneveld'
			// *     example 2: html_entity_decode('&amp;lt;');
			// *     returns 2: '&lt;'

			var hash_map = {}, symbol = '', tmp_str = '', entity = '';
			tmp_str = string.toString();

			if (false === (hash_map = this.get_html_translation_table('HTML_ENTITIES', quote_style))) {
			    return false;
			}

			// fix &amp; problem
			// http://phpjs.org/functions/get_html_translation_table:416#comment_97660
			delete(hash_map['&']);
			hash_map['&'] = '&amp;';

			for (symbol in hash_map) {
			    entity = hash_map[symbol];
			    tmp_str = tmp_str.split(entity).join(symbol);
			}
			tmp_str = tmp_str.split('&#039;').join(\"'\");

			return tmp_str;
		}

		function get_html_translation_table (table, quote_style) {
		    // http://kevin.vanzonneveld.net
		    // +   original by: Philip Peterson
		    // +    revised by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
		    // +   bugfixed by: noname
		    // +   bugfixed by: Alex
		    // +   bugfixed by: Marco
		    // +   bugfixed by: madipta
		    // +   improved by: KELAN
		    // +   improved by: Brett Zamir (http://brett-zamir.me)
		    // +   bugfixed by: Brett Zamir (http://brett-zamir.me)
		    // +      input by: Frank Forte
		    // +   bugfixed by: T.Wild
		    // +      input by: Ratheous
		    // %          note: It has been decided that we're not going to add global
		    // %          note: dependencies to php.js, meaning the constants are not
		    // %          note: real constants, but strings instead. Integers are also supported if someone
		    // %          note: chooses to create the constants themselves.
		    // *     example 1: get_html_translation_table('HTML_SPECIALCHARS');
		    // *     returns 1: {'\"': '&quot;', '&': '&amp;', '<': '&lt;', '>': '&gt;'}

		    var entities = {}, hash_map = {}, decimal = 0, symbol = '';
		    var constMappingTable = {}, constMappingQuoteStyle = {};
		    var useTable = {}, useQuoteStyle = {};

		    // Translate arguments
		    constMappingTable[0]      = 'HTML_SPECIALCHARS';
		    constMappingTable[1]      = 'HTML_ENTITIES';
		    constMappingQuoteStyle[0] = 'ENT_NOQUOTES';
		    constMappingQuoteStyle[2] = 'ENT_COMPAT';
		    constMappingQuoteStyle[3] = 'ENT_QUOTES';

		    useTable       = !isNaN(table) ? constMappingTable[table] : table ? table.toUpperCase() : 'HTML_SPECIALCHARS';
		    useQuoteStyle = !isNaN(quote_style) ? constMappingQuoteStyle[quote_style] : quote_style ? quote_style.toUpperCase() : 'ENT_COMPAT';

		    if (useTable !== 'HTML_SPECIALCHARS' && useTable !== 'HTML_ENTITIES') {
			throw new Error(\"Table: \"+useTable+' not supported');
			// return false;
		    }

		    entities['38'] = '&amp;';
		    if (useTable === 'HTML_ENTITIES') {
			entities['160'] = '&nbsp;';
			entities['161'] = '&iexcl;';
			entities['162'] = '&cent;';
			entities['163'] = '&pound;';
			entities['164'] = '&curren;';
			entities['165'] = '&yen;';
			entities['166'] = '&brvbar;';
			entities['167'] = '&sect;';
			entities['168'] = '&uml;';
			entities['169'] = '&copy;';
			entities['170'] = '&ordf;';
			entities['171'] = '&laquo;';
			entities['172'] = '&not;';
			entities['173'] = '&shy;';
			entities['174'] = '&reg;';
			entities['175'] = '&macr;';
			entities['176'] = '&deg;';
			entities['177'] = '&plusmn;';
			entities['178'] = '&sup2;';
			entities['179'] = '&sup3;';
			entities['180'] = '&acute;';
			entities['181'] = '&micro;';
			entities['182'] = '&para;';
			entities['183'] = '&middot;';
			entities['184'] = '&cedil;';
			entities['185'] = '&sup1;';
			entities['186'] = '&ordm;';
			entities['187'] = '&raquo;';
			entities['188'] = '&frac14;';
			entities['189'] = '&frac12;';
			entities['190'] = '&frac34;';
			entities['191'] = '&iquest;';
			entities['192'] = '&Agrave;';
			entities['193'] = '&Aacute;';
			entities['194'] = '&Acirc;';
			entities['195'] = '&Atilde;';
			entities['196'] = '&Auml;';
			entities['197'] = '&Aring;';
			entities['198'] = '&AElig;';
			entities['199'] = '&Ccedil;';
			entities['200'] = '&Egrave;';
			entities['201'] = '&Eacute;';
			entities['202'] = '&Ecirc;';
			entities['203'] = '&Euml;';
			entities['204'] = '&Igrave;';
			entities['205'] = '&Iacute;';
			entities['206'] = '&Icirc;';
			entities['207'] = '&Iuml;';
			entities['208'] = '&ETH;';
			entities['209'] = '&Ntilde;';
			entities['210'] = '&Ograve;';
			entities['211'] = '&Oacute;';
			entities['212'] = '&Ocirc;';
			entities['213'] = '&Otilde;';
			entities['214'] = '&Ouml;';
			entities['215'] = '&times;';
			entities['216'] = '&Oslash;';
			entities['217'] = '&Ugrave;';
			entities['218'] = '&Uacute;';
			entities['219'] = '&Ucirc;';
			entities['220'] = '&Uuml;';
			entities['221'] = '&Yacute;';
			entities['222'] = '&THORN;';
			entities['223'] = '&szlig;';
			entities['224'] = '&agrave;';
			entities['225'] = '&aacute;';
			entities['226'] = '&acirc;';
			entities['227'] = '&atilde;';
			entities['228'] = '&auml;';
			entities['229'] = '&aring;';
			entities['230'] = '&aelig;';
			entities['231'] = '&ccedil;';
			entities['232'] = '&egrave;';
			entities['233'] = '&eacute;';
			entities['234'] = '&ecirc;';
			entities['235'] = '&euml;';
			entities['236'] = '&igrave;';
			entities['237'] = '&iacute;';
			entities['238'] = '&icirc;';
			entities['239'] = '&iuml;';
			entities['240'] = '&eth;';
			entities['241'] = '&ntilde;';
			entities['242'] = '&ograve;';
			entities['243'] = '&oacute;';
			entities['244'] = '&ocirc;';
			entities['245'] = '&otilde;';
			entities['246'] = '&ouml;';
			entities['247'] = '&divide;';
			entities['248'] = '&oslash;';
			entities['249'] = '&ugrave;';
			entities['250'] = '&uacute;';
			entities['251'] = '&ucirc;';
			entities['252'] = '&uuml;';
			entities['253'] = '&yacute;';
			entities['254'] = '&thorn;';
			entities['255'] = '&yuml;';
		    }

		    if (useQuoteStyle !== 'ENT_NOQUOTES') {
			entities['34'] = '&quot;';
		    }
		    if (useQuoteStyle === 'ENT_QUOTES') {
			entities['39'] = '&#39;';
		    }
		    entities['60'] = '&lt;';
		    entities['62'] = '&gt;';


		    // ascii decimals to real symbols
		    for (decimal in entities) {
			symbol = String.fromCharCode(decimal);
			hash_map[symbol] = entities[decimal];
		    }

		    return hash_map;
		}

		$(document).ready(function() {

			var date = new Date();
			var d = date.getDate();
			var m = date.getMonth();
			var y = date.getFullYear();

			var DokeosCalendar = $('#calendar').fullCalendar({
				".$header."
				buttonText: { today: '".addslashes(get_lang('Today'))."', month: '".addslashes(get_lang('MonthView'))."', week: '".addslashes(get_lang('WeekView'))."', day: '".addslashes(get_lang('DayView'))."'}, 
				monthNames: ['".addslashes(ucfirst(get_lang('JanuaryLong')))."', '".addslashes(ucfirst(get_lang('FebruaryLong')))."', '".addslashes(ucfirst(get_lang('MarchLong')))."', '".addslashes(ucfirst(get_lang('AprilLong')))."', '".addslashes(ucfirst(get_lang('MayLong')))."', '".addslashes(ucfirst(get_lang('JuneLong')))."', '".addslashes(ucfirst(get_lang('JulyLong')))."', '".addslashes(ucfirst(get_lang('AugustLong')))."', '".addslashes(ucfirst(get_lang('SeptemberLong')))."', '".addslashes(ucfirst(get_lang('OctoberLong')))."', '".addslashes(ucfirst(get_lang('NovemberLong')))."', '".addslashes(ucfirst(get_lang('DecemberLong')))."'],
				monthNamesShort: ['".addslashes(ucfirst(get_lang('JanuaryShort')))."', '".addslashes(ucfirst(get_lang('FebruaryShort')))."', '".addslashes(ucfirst(get_lang('MarchShort')))."', '".addslashes(ucfirst(get_lang('AprilShort')))."', '".addslashes(ucfirst(get_lang('MayShort')))."', '".addslashes(ucfirst(get_lang('JuneShort')))."', '".addslashes(ucfirst(get_lang('JulyShort')))."', '".addslashes(ucfirst(get_lang('AugustShort')))."', '".addslashes(ucfirst(get_lang('SeptemberShort')))."', '".addslashes(ucfirst(get_lang('OctoberShort')))."', '".addslashes(ucfirst(get_lang('NovemberShort')))."', '".addslashes(ucfirst(get_lang('DecemberShort')))."'],
				dayNames: ['".addslashes(ucfirst(get_lang('SundayLong')))."', '".addslashes(ucfirst(get_lang('MondayLong')))."', '".addslashes(ucfirst(get_lang('TuesdayLong')))."', '".addslashes(ucfirst(get_lang('WednesdayLong')))."', '".addslashes(ucfirst(get_lang('ThursdayLong')))."', '".addslashes(ucfirst(get_lang('FridayLong')))."', '".addslashes(ucfirst(get_lang('SaturdayLong')))."'],
				dayNamesShort: ['".addslashes(ucfirst(get_lang('SundayShort')))."', '".addslashes(ucfirst(get_lang('MondayShort')))."', '".addslashes(ucfirst(get_lang('TuesdayShort')))."', '".addslashes(ucfirst(get_lang('WednesdayShort')))."', '".addslashes(ucfirst(get_lang('ThursdayShort')))."', '".addslashes(ucfirst(get_lang('FridayShort')))."', '".addslashes(ucfirst(get_lang('SaturdayShort')))."'],
				weekMode: 'variable',
				allDaySlot: false,
				firstDay: 1,
				axisFormat: 'HH(:mm)',
				timeFormat: 'HH:mm{ - HH:mm}',
				height: 1200,
				" . $defaultview . "
				editable: false,
				events: \"ajax.php?action=getevents&output=json".$student_view."&group=".Security::Remove_XSS($_GET['group'])."\",
				eventRender: function(calEvent, element) {
					// add the id to the rendered element so that we can use this for the detail
					element.attr('id',calEvent.id);
					$('.fc-event-title',element).html(html_entity_decode(calEvent.title));
				}
			});

			// clicking the event. We could have used the eventClick functionality of fullcalendar but this caused problems when changing the visibility
			$('.fc-event').live('click', function(){
			id = $(this).attr('id');

                        // Generic variables
                        var data_id = new  Array();
                        var get_id = 0;
                        var _event_type = '';
                        var find = true;
                        // Get the platform event ID
                        try {
                          _event_type = 'platform';
                          data_id = id.split('platform');
                          get_id = data_id[1];// Get the ID of the platform event
                          if (get_id > 0) {
                            find = false;
                          }
                        } catch(e){get_id = 0;}

                        // Get the assignment event ID
                        if (find === true) {
                          try {
                            _event_type = 'assignment';
                            data_id = id.split('assignment');
                            get_id = data_id[1];// Get the ID of the assignment
                            if (get_id > 0) {
                              find = false;
                            }
                          } catch(e){get_id = 0;}
                        }

                        // Get the quiz event ID
                        if (find === true) {
                          try {
                            _event_type = 'quiz';
                            data_id = id.split('quiz');
                            get_id = data_id[1];// Get the ID of the quiz
                            if (get_id > 0) {
                              find = false;
                            }
                          } catch(e){get_id = 0;}
                        }

                        // Get the session event ID
                        if (find === true) {
                          try {
                            _event_type = 'session';
                            data_id = id.split('session');
                            get_id = data_id[1];// Get the ID of the session
                            if (get_id > 0) {
                              find = false;
                            }
                          } catch(e){get_id = 0;}
                        }

                        // Get the course event ID
                        if (find === true) {
                          try {
                            _event_type = 'course_events';
                            data_id = id.split('course_events');
                            // Get the ID of the session
                            if (data_id[1] == 'undefined') {
                                get_id = id;
                            } else {
                                get_id = data_id[1];
                            }
                            
                            if (get_id > 0) {
                              find = false;
                            }
                          } catch(e){get_id = 0;}
                        }

			    $(location).attr('href','agenda.php?".api_get_cidreq()."&action=detail&id='+get_id+'&event_type='+_event_type);
			});

			// add the list button
			//$('.fc-header-right table tbody tr td').eq(0).before('<td><div class=\"fc-button-list fc-state-default fc-no-right fc-corner-left\"><a><span>" . get_lang ( 'ListView' ) . "</span></a></div></td>');

			// show the list view
			$('.fc-button-list').click(function(){
				DokeosCalendar.fullCalendar('changeView','basicWeek');

				// make all the view buttons inactive
				$('.fc-state-active').toggleClass('fc-state-active');

				// make the current view button active
				$(this).toggleClass('fc-state-active');

				// hide all the views
				$('.fc-view').hide();

				// add a new view or show if if it already exists (but hidden)
				if ( $('.fc-view-list').length ){
					$('.fc-view-list').show();
				} else {
					$.ajax({
					  url: 'ajax.php',
					  data: {action: 'getevents', output: 'list', full_info: 'true'},
					  success: function(data){
							$('.fc-content').append('<div class=\"fc-view fc-view-list\">'+data+'</div>');
						}
					});
				}
			});

			// desactive the list button when another button is clicked and hide the list view
			$('.fc-button-month, .fc-button-agendaWeek, .fc-button-agendaDay').click(function(){
				$('.fc-button-list').removeClass('fc-state-active');
				$('.fc-view-list').hide();
				});

			});

			// change the size if month view is clicked
			$('.fc-button-month').click(function(){
				DokeosCalendar.fullCalendar('option', 'height', 500);
			});

			// change the size if month view is clicked
			$('.fc-button-agendaWeek, .fc-button-agendaDay').click(function(){
				DokeosCalendar.fullCalendar('option', 'height', 1200);
			});

		</script>";
	}
}

function mycalendar_javascript(){
	// defaultview (this has to become a platform setting)
	if ($_GET ['view'] and in_array ( $_GET ['view'], array ('month', 'agendaWeek', 'agendaDay' ) )) {
		$defaultview = 'defaultView: \'' . Security::remove_XSS($_GET ['view']) . '\',';
	} else {
		$defaultview = 'defaultView: \'' . api_get_setting('agenda_default_view') . '\',';
	}

	// display action icons or not
	if (api_get_setting('agenda_action_icons') == 'true') {                                        
            $actionicons = "
                if (calEvent.parent_event_id && calEvent.parent_event_id != 0) {
                    $(this).children('a').append('<span class=\"fc-event-actions\"><img src=\"../img/delete.png\" id=\"delete_'+calEvent.id+'\" class=\"delete\" alt=\"" . get_lang ( 'Delete' ) . "\"/></span>');
                } else {
                    $(this).children('a').append('<span class=\"fc-event-actions\"><img src=\"../img/edit.png\" id=\"edit_'+calEvent.id+'\" class=\"edit\" alt=\"" . get_lang ( 'Edit' ) . "\"/><img src=\"../img/delete.png\" id=\"delete_'+calEvent.id+'\" class=\"delete\" alt=\"" . get_lang ( 'Delete' ) . "\"/></span>');
	}
            ";
	}

	// detail view
	if (api_get_setting('calendar_detail_view') == 'detail'){
		$detailview = 'detail';
	} else {
		$detailview = 'myedit';
	}

	// how should the calendar header look like
	if (api_get_setting('calendar_navigation') == 'actions'){
		$header = 	"header: {
						left: '',
						center: '',
						right: ''
					},";
	} else {
		$header = 	"header: {
						left: 'prev,next today',
						center: 'title',
						right: 'month,agendaWeek,agendaDay'
					},";
	}

	return "<script type='text/javascript'>

		function html_entity_decode (string, quote_style) {
			// http://kevin.vanzonneveld.net
			// +   original by: john (http://www.jd-tech.net)
			// +      input by: ger
			// +   improved by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
			// +    revised by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
			// +   bugfixed by: Onno Marsman
			// +   improved by: marc andreu
			// +    revised by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
			// +      input by: Ratheous
			// +   bugfixed by: Brett Zamir (http://brett-zamir.me)
			// +      input by: Nick Kolosov (http://sammy.ru)
			// +   bugfixed by: Fox
			// -    depends on: get_html_translation_table
			// *     example 1: html_entity_decode('Kevin &amp; van Zonneveld');
			// *     returns 1: 'Kevin & van Zonneveld'
			// *     example 2: html_entity_decode('&amp;lt;');
			// *     returns 2: '&lt;'

			var hash_map = {}, symbol = '', tmp_str = '', entity = '';
			tmp_str = string.toString();

			if (false === (hash_map = this.get_html_translation_table('HTML_ENTITIES', quote_style))) {
			    return false;
			}

			// fix &amp; problem
			// http://phpjs.org/functions/get_html_translation_table:416#comment_97660
			delete(hash_map['&']);
			hash_map['&'] = '&amp;';

			for (symbol in hash_map) {
			    entity = hash_map[symbol];
			    tmp_str = tmp_str.split(entity).join(symbol);
			}
			tmp_str = tmp_str.split('&#039;').join(\"'\");

			return tmp_str;
		}

		function get_html_translation_table (table, quote_style) {
		    // http://kevin.vanzonneveld.net
		    // +   original by: Philip Peterson
		    // +    revised by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
		    // +   bugfixed by: noname
		    // +   bugfixed by: Alex
		    // +   bugfixed by: Marco
		    // +   bugfixed by: madipta
		    // +   improved by: KELAN
		    // +   improved by: Brett Zamir (http://brett-zamir.me)
		    // +   bugfixed by: Brett Zamir (http://brett-zamir.me)
		    // +      input by: Frank Forte
		    // +   bugfixed by: T.Wild
		    // +      input by: Ratheous
		    // %          note: It has been decided that we're not going to add global
		    // %          note: dependencies to php.js, meaning the constants are not
		    // %          note: real constants, but strings instead. Integers are also supported if someone
		    // %          note: chooses to create the constants themselves.
		    // *     example 1: get_html_translation_table('HTML_SPECIALCHARS');
		    // *     returns 1: {'\"': '&quot;', '&': '&amp;', '<': '&lt;', '>': '&gt;'}

		    var entities = {}, hash_map = {}, decimal = 0, symbol = '';
		    var constMappingTable = {}, constMappingQuoteStyle = {};
		    var useTable = {}, useQuoteStyle = {};

		    // Translate arguments
		    constMappingTable[0]      = 'HTML_SPECIALCHARS';
		    constMappingTable[1]      = 'HTML_ENTITIES';
		    constMappingQuoteStyle[0] = 'ENT_NOQUOTES';
		    constMappingQuoteStyle[2] = 'ENT_COMPAT';
		    constMappingQuoteStyle[3] = 'ENT_QUOTES';

		    useTable       = !isNaN(table) ? constMappingTable[table] : table ? table.toUpperCase() : 'HTML_SPECIALCHARS';
		    useQuoteStyle = !isNaN(quote_style) ? constMappingQuoteStyle[quote_style] : quote_style ? quote_style.toUpperCase() : 'ENT_COMPAT';

		    if (useTable !== 'HTML_SPECIALCHARS' && useTable !== 'HTML_ENTITIES') {
			throw new Error(\"Table: \"+useTable+' not supported');
			// return false;
		    }

		    entities['38'] = '&amp;';
		    if (useTable === 'HTML_ENTITIES') {
			entities['160'] = '&nbsp;';
			entities['161'] = '&iexcl;';
			entities['162'] = '&cent;';
			entities['163'] = '&pound;';
			entities['164'] = '&curren;';
			entities['165'] = '&yen;';
			entities['166'] = '&brvbar;';
			entities['167'] = '&sect;';
			entities['168'] = '&uml;';
			entities['169'] = '&copy;';
			entities['170'] = '&ordf;';
			entities['171'] = '&laquo;';
			entities['172'] = '&not;';
			entities['173'] = '&shy;';
			entities['174'] = '&reg;';
			entities['175'] = '&macr;';
			entities['176'] = '&deg;';
			entities['177'] = '&plusmn;';
			entities['178'] = '&sup2;';
			entities['179'] = '&sup3;';
			entities['180'] = '&acute;';
			entities['181'] = '&micro;';
			entities['182'] = '&para;';
			entities['183'] = '&middot;';
			entities['184'] = '&cedil;';
			entities['185'] = '&sup1;';
			entities['186'] = '&ordm;';
			entities['187'] = '&raquo;';
			entities['188'] = '&frac14;';
			entities['189'] = '&frac12;';
			entities['190'] = '&frac34;';
			entities['191'] = '&iquest;';
			entities['192'] = '&Agrave;';
			entities['193'] = '&Aacute;';
			entities['194'] = '&Acirc;';
			entities['195'] = '&Atilde;';
			entities['196'] = '&Auml;';
			entities['197'] = '&Aring;';
			entities['198'] = '&AElig;';
			entities['199'] = '&Ccedil;';
			entities['200'] = '&Egrave;';
			entities['201'] = '&Eacute;';
			entities['202'] = '&Ecirc;';
			entities['203'] = '&Euml;';
			entities['204'] = '&Igrave;';
			entities['205'] = '&Iacute;';
			entities['206'] = '&Icirc;';
			entities['207'] = '&Iuml;';
			entities['208'] = '&ETH;';
			entities['209'] = '&Ntilde;';
			entities['210'] = '&Ograve;';
			entities['211'] = '&Oacute;';
			entities['212'] = '&Ocirc;';
			entities['213'] = '&Otilde;';
			entities['214'] = '&Ouml;';
			entities['215'] = '&times;';
			entities['216'] = '&Oslash;';
			entities['217'] = '&Ugrave;';
			entities['218'] = '&Uacute;';
			entities['219'] = '&Ucirc;';
			entities['220'] = '&Uuml;';
			entities['221'] = '&Yacute;';
			entities['222'] = '&THORN;';
			entities['223'] = '&szlig;';
			entities['224'] = '&agrave;';
			entities['225'] = '&aacute;';
			entities['226'] = '&acirc;';
			entities['227'] = '&atilde;';
			entities['228'] = '&auml;';
			entities['229'] = '&aring;';
			entities['230'] = '&aelig;';
			entities['231'] = '&ccedil;';
			entities['232'] = '&egrave;';
			entities['233'] = '&eacute;';
			entities['234'] = '&ecirc;';
			entities['235'] = '&euml;';
			entities['236'] = '&igrave;';
			entities['237'] = '&iacute;';
			entities['238'] = '&icirc;';
			entities['239'] = '&iuml;';
			entities['240'] = '&eth;';
			entities['241'] = '&ntilde;';
			entities['242'] = '&ograve;';
			entities['243'] = '&oacute;';
			entities['244'] = '&ocirc;';
			entities['245'] = '&otilde;';
			entities['246'] = '&ouml;';
			entities['247'] = '&divide;';
			entities['248'] = '&oslash;';
			entities['249'] = '&ugrave;';
			entities['250'] = '&uacute;';
			entities['251'] = '&ucirc;';
			entities['252'] = '&uuml;';
			entities['253'] = '&yacute;';
			entities['254'] = '&thorn;';
			entities['255'] = '&yuml;';
		    }

		    if (useQuoteStyle !== 'ENT_NOQUOTES') {
			entities['34'] = '&quot;';
		    }
		    if (useQuoteStyle === 'ENT_QUOTES') {
			entities['39'] = '&#39;';
		    }
		    entities['60'] = '&lt;';
		    entities['62'] = '&gt;';


		    // ascii decimals to real symbols
		    for (decimal in entities) {
			symbol = String.fromCharCode(decimal);
			hash_map[symbol] = entities[decimal];
		    }

		    return hash_map;
		}

		$(document).ready(function() {

			var date = new Date();
			var d = date.getDate();
			var m = date.getMonth();
			var y = date.getFullYear();

			var DokeosCalendar = $('#calendar').fullCalendar({
				".$header."
				buttonText: { today: '".addslashes(get_lang('Today'))."', month: '".addslashes(get_lang('MonthView'))."', week: '".addslashes(get_lang('WeekView'))."', day: '".addslashes(get_lang('DayView'))."'}, 
				monthNames: ['".addslashes(ucfirst(get_lang('JanuaryLong')))."', '".addslashes(ucfirst(get_lang('FebruaryLong')))."', '".addslashes(ucfirst(get_lang('MarchLong')))."', '".addslashes(ucfirst(get_lang('AprilLong')))."', '".addslashes(ucfirst(get_lang('MayLong')))."', '".addslashes(ucfirst(get_lang('JuneLong')))."', '".addslashes(ucfirst(get_lang('JulyLong')))."', '".addslashes(ucfirst(get_lang('AugustLong')))."', '".addslashes(ucfirst(get_lang('SeptemberLong')))."', '".addslashes(ucfirst(get_lang('OctoberLong')))."', '".addslashes(ucfirst(get_lang('NovemberLong')))."', '".addslashes(ucfirst(get_lang('DecemberLong')))."'],
				monthNamesShort: ['".addslashes(ucfirst(get_lang('JanuaryShort')))."', '".addslashes(ucfirst(get_lang('FebruaryShort')))."', '".addslashes(ucfirst(get_lang('MarchShort')))."', '".addslashes(ucfirst(get_lang('AprilShort')))."', '".addslashes(ucfirst(get_lang('MayShort')))."', '".addslashes(ucfirst(get_lang('JuneShort')))."', '".addslashes(ucfirst(get_lang('JulyShort')))."', '".addslashes(ucfirst(get_lang('AugustShort')))."', '".addslashes(ucfirst(get_lang('SeptemberShort')))."', '".addslashes(ucfirst(get_lang('OctoberShort')))."', '".addslashes(ucfirst(get_lang('NovemberShort')))."', '".addslashes(ucfirst(get_lang('DecemberShort')))."'],
				dayNames: ['".addslashes(ucfirst(get_lang('SundayLong')))."', '".addslashes(ucfirst(get_lang('MondayLong')))."', '".addslashes(ucfirst(get_lang('TuesdayLong')))."', '".addslashes(ucfirst(get_lang('WednesdayLong')))."', '".addslashes(ucfirst(get_lang('ThursdayLong')))."', '".addslashes(ucfirst(get_lang('FridayLong')))."', '".addslashes(ucfirst(get_lang('SaturdayLong')))."'],
				dayNamesShort: ['".addslashes(ucfirst(get_lang('SundayShort')))."', '".addslashes(ucfirst(get_lang('MondayShort')))."', '".addslashes(ucfirst(get_lang('TuesdayShort')))."', '".addslashes(ucfirst(get_lang('WednesdayShort')))."', '".addslashes(ucfirst(get_lang('ThursdayShort')))."', '".addslashes(ucfirst(get_lang('FridayShort')))."', '".addslashes(ucfirst(get_lang('SaturdayShort')))."'],
				weekMode: 'variable',
				allDaySlot: false,
				firstDay: 1,
				axisFormat: 'HH(:mm)',
				timeFormat: 'HH:mm{ - HH:mm}',
				height: 1200,
				" . $defaultview . "
				editable: true,
				events: \"ajax.php?action=getallevents&output=json\",
				eventMouseover: function(calEvent,jsEvent) {
						// the appropriate visibility icon
						if (calEvent.visibility == 1){
							var visibility_icon = 'visible.gif';
						} else {
							var visibility_icon = 'invisible.gif';
	}
						if ($(this).hasClass('personal')) {
							".$actionicons."
						}
				},
				eventMouseout: function(calEvent,jsEvent) {
					$('.fc-event-actions').remove();
				},
				eventRender: function(calEvent, element) {
					// add the id to the rendered element so that we can use this for the detail
					element.attr('id',calEvent.id);
					$('.fc-event-title',element).html(html_entity_decode(calEvent.title));
				},
				eventDrop: function(event,dayDelta,minuteDelta,allDay,revertFunc) {
					$.ajax({
					  url: 'ajax.php',
					  data: {action: 'mymove', id: event.id.replace('personal',''), daydelta: dayDelta, minutedelta: minuteDelta}
					});
				},
				dayClick: function(date, allDay, jsEvent, view){
					$(location).attr('href','myagenda.php?action=myadd&date='+$.fullCalendar.formatDate( date, 'yyyy/MM/dd/HH/mm'));
				},
				eventResize: function(event,dayDelta,minuteDelta,revertFunc) {
					$.ajax({
					  url: 'ajax.php',
					  data: {action: 'myresize', id: event.id, daydelta: dayDelta, minutedelta: minuteDelta}
					});
				}

			});

			$('.fc-event-actions .edit').live('click', function(){
				id=$(this).attr('id');
				$(location).attr('href','myagenda.php?".api_get_cidreq()."&action=myedit&id='+id.replace('edit_personal',''));
			});

			$('.fc-event-actions .delete').live('click', function(){
				id=$(this).attr('id');

				// remove from database
				$.ajax({
				  url: 'ajax.php',
				  data: {action: 'mydelete', id: id.replace('delete_personal','')}
				});

				// get the fc_index
				var fc_index = $('.fc-event').index($(this).parent().parent().parent());

				// remove the fc-event
				$(this).parent().parent().parent().remove();
			});

			$('.fc-event-title span').live('click', function(){
				var coursecode = $(this).attr('class')
				$(location).attr('href','agenda.php?cidReq='+coursecode);
			});

			// change the size if month view is clicked
			$('.fc-button-month').click(function(){
				DokeosCalendar.fullCalendar('option', 'height', 500);
			});

			// change the size if month view is clicked
			$('.fc-button-agendaWeek, .fc-button-agendaDay').click(function(){
				DokeosCalendar.fullCalendar('option', 'height', 1200);
			});

		});
	</script>";

}
?>
