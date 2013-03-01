<?php

/* For licensing terms, see /dokeos_license.txt */

class DisplayGradebook
{
	/**
	* Displays the header for the result page containing the navigation tree and links
	* @param $evalobj
	* @param $selectcat
	* @param $shownavbar 1=show navigation bar
	* @param $forpdf only output for pdf file
	*/
	function display_header_result($evalobj, $selectcat, $shownavbar) {
		$status=CourseManager::get_user_in_course_status(api_get_user_id(), api_get_course_id());
		if ($shownavbar == '1' && $status==1) {
			$header = '<div class="actions">';
			$header .= '<a href="'.$_SESSION['gradebook_dest'].'?selectcat=' . $selectcat . '&'.  api_get_cidreq().'">'. Display::return_icon('pixel.gif', get_lang('FolderView'), array('class' => 'toolactionplaceholdericon toolactionback')) . get_lang('FolderView') . '</a>';
			if ($evalobj->get_course_code() == null) {
				$header .= '<a href="gradebook_add_user.php?selecteval=' . $evalobj->get_id() . '&'.  api_get_cidreq().'"><img src="../img/add_user_big.gif" alt="' . get_lang('AddStudent') . '" align="absmiddle" /> ' . get_lang('AddStudent') . '</a>';
			}
			elseif (!$evalobj->has_results()) {
				$header .=	'<a href="gradebook_add_result.php?selectcat=' . $selectcat . '&selecteval=' . $evalobj->get_id() . '&'.  api_get_cidreq().'">'.
//								'<img src="../tick.png" alt="' . get_lang('AddResult') . '" align="absmiddle"/> ' . get_lang('AddResult') .
								Display::return_icon(('pixel.gif'),get_lang('AddResult'),array('class'=>'toolactionplaceholdericon toolactionmark_learners')) . get_lang('AddResult').
							 '</a>';
			}
			$header .= 	'<a href="' . api_get_self() . '?&selecteval=' . $evalobj->get_id() . '&import=&'.  api_get_cidreq().'">'.
//						'<img src="../img/import_data.gif" border="0" alt="" />' . ' ' . get_lang('ImportResult').
						Display::return_icon(('pixel.gif'),get_lang('ImportResult'),array('class'=>'toolactionplaceholdericon toolactionimport_32')). get_lang('ImportResult').
						'</a>';
			if ($evalobj->has_results()) {
				$header .= '<a href="' . api_get_self() . '?&selecteval=' . $evalobj->get_id() . '&export=&'.  api_get_cidreq().'">'.Display::return_icon('pixel.gif',get_lang('ExportResult'),array('class'=>'toolactionplaceholdericon toolaction32x32file_pdf') ).'  ' . get_lang('ExportResult') . '</a>';
				$header .= '<a href="gradebook_edit_result.php?selecteval=' . $evalobj->get_id() . '&'.  api_get_cidreq().'">'.Display::return_icon('pixel.gif',get_lang('EditResult'),array('class'=>'toolactionplaceholdericon toolactionmark_learners') ).'  ' . get_lang('EditResult') . '</a>';
				$header .= '<a href="' . api_get_self() . '?&selecteval=' . $evalobj->get_id() . '&deleteall=&'.  api_get_cidreq().'" onclick="return confirmationall();">'.Display::return_icon('pixel.gif',get_lang('DeleteResult'),array('class'=>'toolactionplaceholdericon toolactiondelete') ).' ' . get_lang('DeleteResult') . '</a>';
			}
			$header .= 	'<a href="' . api_get_self() . '?print=&selecteval=' . $evalobj->get_id() . '&'.  api_get_cidreq().'" target="_blank">'.
//						'<img src="../img/print32.png" alt="' . get_lang('Print') . '" /> ' . get_lang('Print') .
						Display::return_icon(('pixel.gif'),get_lang('Print'),array('class'=>'toolactionplaceholdericon toolactionprint32')) . get_lang('Print').
						'</a>';

			$header .= '</div>';
		} elseif($status==5 || is_null($header)) { // Is student
			$header = '<div class="actions">';
			$header .= '<a href="'.$_SESSION['gradebook_dest'].'?selectcat=' . $selectcat . '&'.  api_get_cidreq().'">'. Display::return_icon('pixel.gif', get_lang('FolderView'), array('class' => 'toolactionplaceholdericon toolactionback')) . get_lang('FolderView') . '</a>';
			$header .= '</div>';
        }

		echo $header;

	}

	/**
	* Return the evaluation result page
	* @param $evalobj
	* @param $selectcat
    * @return string
	*/
	function return_eval_result($evalobj, $selectcat) {
		if ($evalobj->is_visible() == '1') {
			$visible= get_lang('Yes');
		} else {
			$visible= get_lang('No');
		}

		$scoredisplay = ScoreDisplay :: instance();
		if (($evalobj->has_results())){ // TODO this check needed ?
            if (api_is_platform_admin () || api_is_allowed_to_edit()) {
              $student_id = null;
            } else {
              $student_id = api_get_user_id();
            }
			$score= $evalobj->calc_score($student_id);
			if ($score != null)
				$average= get_lang('Average') . ' :<b> ' .$scoredisplay->display_score($score,SCORE_AVERAGE) . '</b>';
		}
		if (!$evalobj->get_description() == '') {
			if ($evalobj->get_type() == 'presence')
			{
				$description_array = unserialize($evalobj->get_description());
				$description .= get_lang('Trainer').': '.$description_array['presence_trainer'].'<br />';
				$description .= get_lang('PresenceSheetCreatedBy').': '.$description_array['presence_creator'].'<br />';
				$description .= get_lang('Date').': '.$description_array['presence_date'].'<br />';
				$description .= get_lang('Duration').': '.$description_array['presence_duration'].'<br />';
				$desc = $description;
			}
			else
			{
				$desc = $evalobj->get_description();
			}
			$description= get_lang('Description') . ' :<b> ' . $desc . '</b><br>';
		}
		if ($evalobj->get_course_code() == null) {
			$course= get_lang('CourseIndependent');
		} else {
			$course= get_course_name_from_code($evalobj->get_course_code());
		}

		$evalinfo= '<table width="100%" border="0"><tr><td>';
		$evalinfo .= get_lang('EvaluationName') . ' :<b> ' . $evalobj->get_name() . ' </b>(' . date('j/n/Y g:i', $evalobj->get_date()) . ')<br>' . get_lang('Course') . ' :<b> ' . $course . '</b><br>' . get_lang('Weight') . ' :<b> ' . $evalobj->get_weight() . '</b><br>' . get_lang('Max') . ' :<b> ' . $evalobj->get_max() . '</b><br>' . $description . get_lang('Visible') . ' :<b> ' . $visible . '</b><br>' . $average;
		if (!$evalobj->has_results())
			$evalinfo .= '<br /><i>' . get_lang('NoResultsInEvaluation') . '</i>';
		elseif ($scoredisplay->is_custom() && api_get_self() != '/dokeos/main/gradebook/gradebook_statistics.php')
			$evalinfo .= '<br /><br /><a href="gradebook_statistics.php?selecteval='.Security::remove_XSS($_GET['selecteval']).'"> '. get_lang('ViewStatistics') . '</a>';
		$evalinfo .= '</td><td>'.Display::return_icon('pixel.gif','',array('class'=>'tutorial','style'=>'float:right; position:relative;')).'</td></table>';
		return $evalinfo;
	}

	/**
	* Displays the header for the flatview page containing filters
	* @param $catobj
	* @param $showeval
	* @param $showlink
	*/
	function display_header_flatview($catobj, $showeval, $showlink,$simple_search_form) {
		$header= '<table border="0" cellpadding="5">';
		$header .= '<td style="vertical-align: top;"><a href="'.$_SESSION['gradebook_dest'].'?selectcat=' . Security::remove_XSS($_GET['selectcat']) . '">' . Display::return_icon('gradebook.gif') . get_lang('Gradebook') . '</a></td>';
		$header .= '<td style="vertical-align: top;">' . get_lang('FilterCategory') . '</td><td style="vertical-align: top;"><form name="selector"><select name="selectcat" onchange="document.selector.submit()">';
		$cats= Category :: load();
		$tree= $cats[0]->get_tree();
		unset ($cats);
		foreach ($tree as $cat) {
			for ($i= 0; $i < $cat[2]; $i++) {
				$line .= '&mdash;';
			}
			if ($_GET['selectcat'] == $cat[0]) {
				$header .= '<option selected="selected" value=' . $cat[0] . '>' . $line . ' ' . $cat[1] . '</option>';
			} else {
				$header .= '<option value=' . $cat[0] . '>' . $line . ' ' . $cat[1] . '</option>';
			}
			$line= '';
		}
		$header .= '</td></select></form>';
		if (!$catobj->get_id() == '0') {
			$header .= '<td style="vertical-align: top;"><a href="' . api_get_self() . '?selectcat=' . $catobj->get_parent_id() . '"><img src="../img/gradebook.gif" border="0" alt="'.get_lang('Up').'" /></a></td>';
		}
		$header .= '<td style="vertical-align: top;">'.$simple_search_form->toHtml().'</td>';
		$header .= '<td style="vertical-align: top;"><a href="' . api_get_self() . '?exportpdf=&offset='.Security::remove_XSS($_GET['offset']).'&search=' . Security::remove_XSS($_GET['search']).'&selectcat=' . $catobj->get_id() . '"><img src=../img/file_pdf.gif alt=' . get_lang('ExportPDF') . '/> ' . get_lang('ExportPDF') . '</a>';
		$header .= '<td style="vertical-align: top;"><a href="' . api_get_self() . '?print=&selectcat=' . $catobj->get_id() . '" target="_blank">'.
		Display::return_icon(('pixel.gif'),get_lang('Print'),array('class'=>'toolactionplaceholdericon toolactionprint32')) . get_lang('Print') . '</a>';
		$header .= '</td></tr></table>';
		if (!$catobj->get_id() == '0') {
			$header .= '<table border="0" cellpadding="5"><tr><td><form name="itemfilter" method="post" action="' . api_get_self() . '?selectcat=' . $catobj->get_id() . '"><input type="checkbox" name="showeval" onclick="document.itemfilter.submit()" ' . (($showeval == '1') ? 'checked' : '') . '>Show Evaluations &nbsp;';
			$header .= '<input type="checkbox" name="showlink" onclick="document.itemfilter.submit()" ' . (($showlink == '1') ? 'checked' : '') . '>'.get_lang('ShowLinks').'</form></td></tr></table>';
		}
		if (isset ($_GET['search'])) {
			$header .= '<b>'.get_lang('SearchResults').' :</b>';
		}
		echo $header;
	}

		/**
	* Displays the header for the flatview page containing filters
	* @param $catobj
	* @param $showeval
	* @param $showlink
	*/
	function display_header_reduce_flatview($catobj, $showeval, $showlink,$simple_search_form) {
		$header = '<div class="actions">';
		$header .= '<a href="'.$_SESSION['gradebook_dest'].'?'.api_get_cidreq().'&selectcat='.Security::remove_XSS($_GET['selectcat']).'">'. Display::return_icon('pixel.gif', get_lang('FolderView'), array('class' => 'toolactionplaceholdericon toolactionback')) . get_lang('FolderView') . '</a>';
		$header .= '<span><a class="quiz_export_link" href="javascript: void(0);" onclick="javascript: document.form1b.submit();">'.Display::return_icon('pixel.gif', get_lang('ExportAsXLS'),array('class'=>'toolactionplaceholdericon toolactionexportcourse')).' '.get_lang('ExportAsXLS').'</a></span>';
//		$header .= '<td style="vertical-align: top;"><a href="' . api_get_self() . '?exportpdf=&offset='.Security::remove_XSS($_GET['offset']).'&search=' . Security::remove_XSS($_GET['search']).'&selectcat=' . $catobj->get_id() . '"><img src=../img/file_pdf.gif alt=' . get_lang('ExportPDF') . '/> ' . get_lang('ExportPDF') . '</a>';

		// this MUST be a GET variable not a POST
		if (isset($_GET['show'])) {
			$show=Security::remove_XSS($_GET['show']);
		} else {
			$show='';
		}
		echo '<form id="form1a" name="form1a" method="post" action="'.api_get_self().'?show='.$show.'">';
		echo '<input type="hidden" name="export_report" value="export_report">';
		echo '<input type="hidden" name="selectcat" value="'.$catobj->get_id() .'">';

		echo '<input type="hidden" name="export_format" value="csv">';
		echo '</form>';

		echo '<form id="form1b" name="form1b" method="post" action="'.api_get_self().'?show='.$show.'">';
		echo '<input type="hidden" name="export_report" value="export_report">';
		echo '<input type="hidden" name="selectcat" value="'.$catobj->get_id() .'">';
		echo '<input type="hidden" name="export_format" value="xls">';
		echo '</form>';

		//$header .= '<a class="quiz_export_link" href="javascript: void(0);" onclick="javascript: document.form1a.submit();">'.Display::return_icon('csv.gif', get_lang('ExportAsCSV')).' '.get_lang('ExportAsCSV').'</a>';
		$header .= '<a href="' . api_get_self() . '?print=&selectcat=' . $catobj->get_id() . '" target="_blank">'.Display::return_icon('pixel.gif', get_lang('Print'),array('class'=>'toolactionplaceholdericon toolactionprint32')).' ' . get_lang('Print') . '</a>';
		$header .= '<a href="' . api_get_self() . '?exportpdf=&selectcat=' . $catobj->get_id() . '" >'.Display::return_icon('pixel.gif', get_lang('ExportToPDF'),array('class'=>'toolactionplaceholdericon toolaction32x32file_pdf')).' ' . get_lang('ExportToPDF') . '</a>';
		//exportpdf
		//<div class="clear">
		$header .= '</div>';
		if (!$catobj->get_id() == '0') {
			//this is necessary?
			//$header .= '<table border="0" cellpadding="5"><tr><td><form name="itemfilter" method="post" action="' . api_get_self() . '?selectcat=' . $catobj->get_id() . '"><input type="checkbox" name="showeval" onclick="document.itemfilter.submit()" ' . (($showeval == '1') ? 'checked' : '') . '>Show Evaluations &nbsp;';
			//$header .= '<input type="checkbox" name="showlink" onclick="document.itemfilter.submit()" ' . (($showlink == '1') ? 'checked' : '') . '>'.get_lang('ShowLinks').'</form></td></tr></table>';
		}
		/*
		if (isset ($_GET['search'])) {
			$header .= '<b>'.get_lang('SearchResults').' :</b>';
		}*/
		echo $header;
	}

	/**
	 * Displays the header for the gradebook containing the navigation tree and links
	 * @param category_object $currentcat
	 * @param int $showtree '1' will show the browse tree and naviation buttons
	 * @param boolean $is_course_admin
	 * @param boolean $is_platform_admin
     * @param boolean Whether to show or not the link to add a new qualification (we hide it in case of the course-embedded tool where we have only one calification per course or session)
     * @param boolean Whether to show or not the link to add a new item inside the qualification (we hide it in case of the course-embedded tool where we have only one calification per course or session)
     * @return void Everything is printed on screen upon closing
	 */
	function display_header_gradebook($catobj, $showtree, $selectcat, $is_course_admin, $is_platform_admin, $simple_search_form, $show_add_qualification = true, $show_add_link = true, $show_form = true) {
		//student
		$status=CourseManager::get_user_in_course_status(api_get_user_id(), api_get_course_id());
		$objcat=new Category();
		//$objdat=new Database();
		$course_id=Database::get_course_by_category($selectcat);
		$message_resource=$objcat->show_message_resource_delete($course_id);

		if (!$is_course_admin && $status<>1 && $selectcat<>0) {
			$user_id = api_get_user_id();
			$user= get_user_info_from_id($user_id);

			$catcourse= Category :: load($catobj->get_id());
			$scoredisplay = ScoreDisplay :: instance();
			$scorecourse = $catcourse[0]->calc_score($user_id);

			// generating the total score for a course
			$allevals= $catcourse[0]->get_evaluations($user_id,true);
			$alllinks= $catcourse[0]->get_links($user_id,true);
			$evals_links = array_merge($allevals, $alllinks);
			$item_value=0;
			$item_total=0;
			for ($count=0; $count < count($evals_links); $count++) {
				$item = $evals_links[$count];
				$score = $item->calc_score($user_id);
				$my_score_denom=($score[1]==0) ? 1 : $score[1];
				$item_value+=$score[0]/$my_score_denom*$item->get_weight();
				$item_total+=$item->get_weight();
				//$row[] = $scoredisplay->display_score($score,SCORE_DIV_PERCENT);
			}
			$item_value = number_format($item_value, 2, '.', ' ');
			$total_score=array($item_value,$item_total);
			$scorecourse_display = $scoredisplay->display_score($total_score,SCORE_DIV_PERCENT);
			//----------------------
			//$scorecourse_display = (isset($scorecourse) ? $scoredisplay->display_score($scorecourse,SCORE_AVERAGE) : get_lang('NoResultsAvailable'));
			$cattotal = Category :: load(0);
			$scoretotal= $cattotal[0]->calc_score(api_get_user_id());
			$scoretotal_display = (isset($scoretotal) ? $scoredisplay->display_score($scoretotal,SCORE_PERCENT) : get_lang('NoResultsAvailable'));
			$scoreinfo = get_lang('StatsStudent') . ' :<b> '.api_get_person_name($user['firstname'], $user['lastname']).'</b><br />';
			if ((!$catobj->get_id() == '0') && (!isset ($_GET['studentoverview'])) && (!isset ($_GET['search']))) {
				$scoreinfo.= '<br />'.get_lang('Total') . ' : <b>' . $scorecourse_display . '</b>';
			}
			//$scoreinfo.= '<br />'.get_lang('Total') . ' : <b>' . $scoretotal_display . '</b>';
			Display :: display_normal_message($scoreinfo,false);
		}
		// show navigation tree and buttons?
		$header='';
		$header .= '<div class="actions">';

         $header .='<table border=0 width="100%">';
		if (($showtree == '1') || (isset ($_GET['studentoverview']))) {

			$header .= '<tr>';
			
			if (!$selectcat == '0') {
				//$header .= '<td width="13%"><a href="' . api_get_self() . '?selectcat=' . $catobj->get_parent_id() . '">'.Display::return_icon('pixel.gif', get_lang('Back'), array('class' => 'toolactionplaceholdericon toolactionback')).get_lang('Back').'</a></td>';
			}
			
			$header .= '<td width="9%" align="right">' . get_lang('Gradebook') . '</td>' .
					'<td style=" "><form  id="selector" name="selector"><select name="selectcat" id="selectcat">';
			$cats= Category :: load();

			$tree= $cats[0]->get_tree();
			unset ($cats);
			foreach ($tree as $cat) {
				for ($i= 0; $i < $cat[2]; $i++) {
					$line .= '&mdash;';
				}
				$line=isset($line) ? $line : '';
				if (isset($_GET['selectcat']) && $_GET['selectcat'] == $cat[0]) {
					$header .= '<option selected value=' . $cat[0] .'|__|'.$cat[1] . '>' . $line . ' ' . $cat[1] . '</option>';
				} else {
					$header .= '<option value=' . $cat[0] .'|__|'.$cat[1]. '>' . $line . ' ' . $cat[1] . '</option>';
				}
				$line= '';
			}
			$header .= '</select>';
            $header .='</form><form id="gradebook_form">
              <input type="hidden" id="selectcat_id" name="selectcat" value="0">
              <input type="hidden" id="cidreq_id" name="cidReq" value="0">
              </form></td>';
            if (!empty($simple_search_form) && $message_resource===false) {
			    $header .= '<td align="right">'.$simple_search_form->toHtml().'</td>';
            } else {
            	$header .= '<td></td>';
            }
			if ($is_course_admin && $message_resource===false && $_GET['selectcat']!=0) {
				/*$header .= '<td style="vertical-align: top;"><a href="gradebook_flatview.php?'.api_get_cidreq().'&selectcat=' . $catobj->get_id() . '"><img src="../img/view_list.gif" alt="' . get_lang('FlatView') . '" /> ' . get_lang('FlatView') . '</a>';
				if ($is_course_admin && $message_resource===false) {
					$header .= '<td style="vertical-align: top;"><a href="gradebook_scoring_system.php?'.api_get_cidreq().'&selectcat=' . $catobj->get_id() .'"><img src="../img/acces_tool.gif" alt="' . get_lang('ScoreEdit') . '" /> ' . get_lang('ScoreEdit') . '</a>';
				}*/
			} elseif (!(isset ($_GET['studentoverview']))) {
				if ( $message_resource===false ) {
					//$header .= '<td style="vertical-align: top;"><a href="'.api_get_self().'?'.api_get_cidreq().'&studentoverview=&selectcat=' . $catobj->get_id() . '"><img src="../img/view_list.gif" alt="' . get_lang('FlatView') . '" /> ' . get_lang('FlatView') . '</a>';
				}
			} else {
				$header .= '<td style="vertical-align: top;"><a href="'.api_get_self().'?'.api_get_cidreq().'&studentoverview=&exportpdf=&selectcat=' . $catobj->get_id() . '" target="_blank"><img src="../img/file_pdf.gif" alt="' . get_lang('ExportPDF') . '" /> ' . get_lang('ExportPDF') . '</a>';
			}
			$header .= '</td></tr>';
		}
		$header.='</table></div>';

		// for course admin & platform admin add item buttons are added to the header
        $header_actions = '';
		$header_actions .= '<div class="actions">';
		$my_category=$catobj->shows_all_information_an_category($catobj->get_id());
		$user_id=api_get_user_id();
		$course_code=$my_category['course_code'];
		$status_user=api_get_status_of_user_in_course ($user_id,$course_code);
		if (($status_user==1 && $is_course_admin && !isset ($_GET['search'])) || api_is_platform_admin()) {
			if ($selectcat == '0') {
                if ($show_add_qualification === true) {
				   // $header .= '<a href="gradebook_add_cat.php?'.api_get_cidreq().'&selectcat=0"><img src="../img/folder_new.gif" alt="' . get_lang('NewCategory') . '" /> ' . get_lang('NewCategory') . '</a></td>';
                }
                if ($show_add_link === true) {
				    //$header .= '<td><a href="gradebook_add_eval.php?'.api_get_cidreq().'"><img src="../img/filenew.gif" alt="' . get_lang('NewEvaluation') . '" /> ' . get_lang('NewEvaluation') . '</a>';
                }
			} else {
                if ($show_add_qualification === true && $message_resource===false) {
    				//$header .= '<a href="gradebook_add_cat.php?'.api_get_cidreq().'&selectcat=' . $catobj->get_id() . '" ><img src="../img/folder_new.gif" alt="' . get_lang('NewSubCategory') . '" align="absmiddle" /> ' . get_lang('NewSubCategory') . '</a></td>';
                }
               $my_category=$catobj->shows_all_information_an_category($catobj->get_id());
				$my_api_cidreq = api_get_cidreq();
				if ($my_api_cidreq=='') {
					$my_api_cidreq='cidReq='.$my_category['course_code'];
				}				
                if ($show_add_link === true && $message_resource==false) {
					$course = api_get_course_id();	
					$_GET['view']=$_GET['view']; // to make sure that the view key exists
                	foreach ($_GET as $key=>$value)
                	{
                		if ($key <> 'view')	
                		{
                			$get_parameters .='&amp;'.$key.'='.Security::remove_XSS($value);
                		}
                		else 
                		{
                			if ($_GET['view'] == 'presence')
                			{
                				$get_parameters .='&amp;'.$key.'=evaluation';
                			}
                			else 
                			{
                				$get_parameters .='&amp;'.$key.'=presence';
                			}
                		}
                	}

    				$header_actions .= '<a href="gradebook_add_eval.php?'.$my_api_cidreq.'&selectcat=' . $catobj->get_id() . '" >'.Display::return_icon('pixel.gif', get_lang('Exam'),array('class'=>'toolactionplaceholdericon toolactionexamen_32')).' ' . get_lang('Exam') . '</a>&nbsp;';
					$header_actions .= '<a href="gradebook_add_presence.php?'.$my_api_cidreq.'&selectcat='.$catobj->get_id().'&course='.$course.'">'.Display::return_icon('pixel.gif', get_lang('Presence'),array('class'=>'toolactionplaceholdericon toolactionpresencie')).' ' . get_lang('Presence') . '</a>';
                    $cats= Category :: load($selectcat);
                    if ($cats[0]->get_course_code() != null && $message_resource===false) {
                        //$header .= '<td><a href="gradebook_add_link.php?'.api_get_cidreq().'&selectcat=' . $catobj->get_id() . '"><img src="../img/link.gif" alt="' . get_lang('MakeLink') . '" align="absmiddle" /> ' . get_lang('MakeLink') . '</a>';
                        $header_actions .= '<a href="gradebook_add_link.php?'.$my_api_cidreq.'&selectcat=' . $catobj->get_id() . '">'.Display::return_icon('pixel.gif', get_lang('OnLine'),array('class'=>'toolactionplaceholdericon toolactiononline')).' ' . get_lang('OnLine') . '</a>&nbsp;';

                    } else {
                        $header_actions .= '<a href="gradebook_add_link_select_course.php?'.$my_api_cidreq.'&selectcat=' . $catobj->get_id() . '">'.Display::return_icon('online_32.png', get_lang('OnLine')).' ' . get_lang('OnLine') . '</a>&nbsp;';
                    }
                }

                if ($message_resource===false ) {
                	$myname=$catobj->shows_all_information_an_category($catobj->get_id());
                    //$header_actions .= '<a href="gradebook_edit_all.php?id_session='.$_SESSION['id_session'].'&amp;'.$my_api_cidreq.'&selectcat=' . $catobj->get_id() . '">'.Display::return_icon('statistics.png', get_lang('EditAllWeights')).' ' . get_lang('EditAllWeights') . '</a>';
                	$my_course_id=api_get_course_id();
                	$my_file= substr($_SESSION['gradebook_dest'],0,5);
                	if (($my_file!='index' || $status_user==1) || api_is_platform_admin()) {
                        if (api_is_platform_admin()) {// Scoring is only configured for platform admin, this feature should be implemented for be configured by teachers in the future
                          $header_actions .= '<a href="gradebook_scoring_system.php?'.$my_api_cidreq.'&selectcat=' . $catobj->get_id() .'">'.Display::return_icon('pixel.gif', get_lang('ScoreEdit'),array('class'=>'toolactionplaceholdericon toolactionskills_ranking')).' ' . get_lang('ScoreEdit') . '</a>';
                        }
                        $header_actions .='<a href="gradebook_edit_all.php?'.$my_api_cidreq.'&selectcat=' . $catobj->get_id() . '">'.Display::return_icon('pixel.gif', get_lang('EditAllWeights'),array('class'=>'toolactionplaceholdericon toolactionweight_in_report')).' ' . get_lang('EditAllWeights') . '</a>';
	                	$header_actions .= '<a href="gradebook_flatview.php?'.$my_api_cidreq.'&selectcat=' . $catobj->get_id() . '">'.Display::return_icon('pixel.gif', get_lang('Report'),array('class'=>'toolactionplaceholdericon toolactionquizscores')).' ' . get_lang('Report') . '</a>';
	                	//$header_actions .= '<a href="../document/document.php?curdirpath=/certificates&'.$my_api_cidreq.'&origin=gradebook&selectcat=' . $catobj->get_id() . '">'.Display::return_icon('certificate_32.png', get_lang('AttachCertificate')).' ' . get_lang('AttachCertificate') . '</a>';
						//	if(($is_course_admin && $message_resource===false && $status_user==1) || api_is_platform_admin())
						/*if ( api_is_platform_admin()) {
							$header_actions .= '<a href="gradebook_scoring_system.php?'.$my_api_cidreq.'&selectcat=' . $catobj->get_id() .'">'.Display::return_icon('acces_tool.gif', get_lang('ScoreEdit')).' ' . get_lang('ScoreEdit') . '</a>';
						}*/
					}
                }
			}
		} elseif (isset ($_GET['search'])) {
			$header_actions .= '<b>'.get_lang('SearchResults').' :</b>';
		}
		$header_actions .= '</div>';

        if ($show_form) {
		  echo $header;
        }
        if ($catobj->get_id() > 0 && api_is_allowed_to_edit()) {
          echo $header_actions;
        }
	}

	function display_reduce_header_gradebook($catobj,$is_course_admin, $is_platform_admin, $simple_search_form, $show_add_qualification = true, $show_add_link = true) {
		//student
		if (!$is_course_admin) {
			/*$user= get_user_info_from_id(api_get_user_id());
			$catcourse= Category :: load($catobj->get_id());
			$scoredisplay = ScoreDisplay :: instance();
			$scorecourse = $catcourse[0]->calc_score(api_get_user_id());
			$scorecourse_display = (isset($scorecourse) ? $scoredisplay->display_score($scorecourse,SCORE_AVERAGE) : get_lang('NoResultsAvailable'));
			$cattotal = Category :: load(0);
			$scoretotal= $cattotal[0]->calc_score(api_get_user_id());
			$scoretotal_display = (isset($scoretotal) ? $scoredisplay->display_score($scoretotal,SCORE_PERCENT) : get_lang('NoResultsAvailable'));
			$scoreinfo = get_lang('StatsStudent') . ' :<b> '.api_get_person_name($user['firstname'], $user['lastname']).'</b><br />';
			if ((!$catobj->get_id() == '0') && (!isset ($_GET['studentoverview'])) && (!isset ($_GET['search'])))
				$scoreinfo.= '<br />'.get_lang('TotalForThisCategory') . ' : <b>' . $scorecourse_display . '</b>';
			$scoreinfo.= '<br />'.get_lang('Total') . ' : <b>' . $scoretotal_display . '</b>';
			Display :: display_normal_message($scoreinfo,false);
			*/
		}
			// show navigation tree and buttons?
			$header = '<div class="actions">';

			if ($is_course_admin) {
				$header .= '<a href="gradebook_flatview.php?'.api_get_cidreq().'&selectcat=' . $catobj->get_id() . '">'.Display::return_icon('stats_access.gif', get_lang('FlatView')).' '. get_lang('FlatView') . '</a>';
				if ( api_is_platform_admin() /*$is_platform_admin || $is_course_admin*/)
					$header .= '<a href="gradebook_scoring_system.php?'.api_get_cidreq().'&selectcat=' . $catobj->get_id() .'">'.Display::return_icon('acces_tool.gif', get_lang('ScoreEdit')).' ' . get_lang('ScoreEdit') . '</a>';
			} elseif (!(isset ($_GET['studentoverview']))) {
				$header .= '<a href="'.api_get_self().'?'.api_get_cidreq().'&studentoverview=&selectcat=' . $catobj->get_id() . '">'.Display::return_icon('view_list.gif', get_lang('FlatView')).' ' . get_lang('FlatView') . '</a>';
			} else {
				$header .= '<a href="'.api_get_self().'?'.api_get_cidreq().'&studentoverview=&exportpdf=&selectcat=' . $catobj->get_id() . '" target="_blank">'.Display::return_icon('file_pdf.gif', get_lang('ExportPDF')).' ' . get_lang('ExportPDF') . '</a>';
			}
		$header.='</div>';
		echo $header;
	}


	function display_header_user($userid) {
		$select_cat=Security::remove_XSS($_GET['selectcat']);
		$user_id = $userid;
		$user= get_user_info_from_id($user_id);

		$catcourse= Category :: load($select_cat);
		$scoredisplay = ScoreDisplay :: instance();
		$scorecourse = $catcourse[0]->calc_score($user_id);

		// generating the total score for a course
		$allevals= $catcourse[0]->get_evaluations($user_id,true);
		$alllinks= $catcourse[0]->get_links($user_id,true);
		$evals_links = array_merge($allevals, $alllinks);
		$item_value=0;
		$item_total=0;
		for ($count=0; $count < count($evals_links); $count++) {
			$item = $evals_links[$count];
			$score = $item->calc_score($user_id);
			$my_score_denom=($score[1]==0) ? 1 : $score[1];
			$item_value+=$score[0]/$my_score_denom*$item->get_weight();
			$item_total+=$item->get_weight();
			//$row[] = $scoredisplay->display_score($score,SCORE_DIV_PERCENT);
		}
		$item_value = number_format($item_value, 2, '.', ' ');
		$total_score=array($item_value,$item_total);
		$scorecourse_display = $scoredisplay->display_score($total_score,SCORE_DIV_PERCENT);
		//----------------------

		//$scorecourse_display = (isset($scorecourse) ? $scoredisplay->display_score($scorecourse,SCORE_AVERAGE) : get_lang('NoResultsAvailable'));
		$cattotal = Category :: load(0);
		$scoretotal= $cattotal[0]->calc_score($user_id);
		$scoretotal_display = (isset($scoretotal) ? $scoredisplay->display_score($scoretotal,SCORE_PERCENT) : get_lang('NoResultsAvailable'));
	//---------------------
		$image_syspath = UserManager::get_user_picture_path_by_id($userid,'system',false,true);
        $image_size = getimagesize($image_syspath['dir'].$image_syspath['file']);
        //Web path
        $image_path = UserManager::get_user_picture_path_by_id($userid,'web',false,true);
        $image_file = $image_path['dir'].$image_path['file'];
		$img_attributes= 'src="' . $image_file . '?rand=' . time() . '" ' . 'alt="' . api_get_person_name($user['firstname'], $user['lastname']) . '" ';
		if ($image_size[0] > 200) {
		 //limit display width to 200px
 			$img_attributes .= 'width="200" ';
		}
		$info = '<table width="100%" border=0 cellpadding=5><tr><td width="80%">';
		$info.= get_lang('Name') . ' : <b>' . api_get_person_name($user['firstname'], $user['lastname']) . '</b> ( <a href="user_info.php?userid=' . $userid . '&selectcat=' . Security::remove_XSS($_GET['selectcat']) . '">' . get_lang('MoreInfo') . '...</a> )<br>';
		$info.= get_lang('Email') . ' : <b><a href="mailto:' . $user['email'] . '">' . $user['email'] . '</a></b><br><br>';
		$info.= get_lang('TotalUser') . ' : <b>' . $scorecourse_display . '</b><br>';
		$info.= '</td><td>';
		$info.= '<img ' . $img_attributes . '/></td></tr></table>';


	//--------------
		//$scoreinfo = get_lang('StatsStudent') . ' :<b> '.api_get_person_name($user['lastname'], $user['firstname']).'</b><br />';
		//$scoreinfo.= '<br />'.get_lang('Total') . ' : <b>' . $scorecourse_display . '</b>';

		//$scoreinfo.= '<br />'.get_lang('Total') . ' : <b>' . $scoretotal_display . '</b>';
		Display :: display_normal_message($info,false);
	}
}
