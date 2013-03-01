<?php
/*
==============================================================================
	Dokeos - elearning and course management software

	Copyright (c) 2008 Dokeos Latinoamerica SAC
	Copyright (c) 2006 Dokeos SPRL
	Copyright (c) 2006 Ghent University (UGent)
	Copyright (c) various contributors

	For a full list of contributors, see "credits.txt".
	The full license can be read in "license.txt".

	This program is free software; you can redistribute it and/or
	modify it under the terms of the GNU General Public License
	as published by the Free Software Foundation; either version 2
	of the License, or (at your option) any later version.

	See the GNU General Public License for more details.

	Contact address: Dokeos, rue du Corbeau, 108, B-1030 Brussels, Belgium
	Mail: info@dokeos.com
==============================================================================
*/
/**
 * Class to select, sort and transform object data into array data,
 * used for the general gradebook view
 * @author Bert Steppé
 */
class GradebookDataGenerator
{

	// Sorting types constants
	const GDG_SORT_TYPE = 1;
	const GDG_SORT_NAME = 2;
	const GDG_SORT_DESCRIPTION = 4;
	const GDG_SORT_WEIGHT = 8;
	const GDG_SORT_DATE = 16;

	const GDG_SORT_ASC = 32;
	const GDG_SORT_DESC = 64;


	private $items;
	private $evals_links;

    function GradebookDataGenerator($cats = array(), $evals = array(), $links = array()) {
		$allcats = (isset($cats) ? $cats : array());
		$allevals = (isset($evals) ? $evals : array());
		$alllinks = (isset($links) ? $links : array());
		// merge categories, evaluations and links
		$this->items = array_merge($allcats, $allevals, $alllinks);
		$this->evals_links = array_merge($allevals, $alllinks);
    }


	/**
	 * Get total number of items (rows)
	 */
	public function get_total_items_count() {
		return count($this->items);
	}




	/**
	 * Get actual array data
	 * @return array 2-dimensional array - each array contains the elements:
	 * 0: cat/eval/link object
	 * 1: item name
	 * 2: description
	 * 3: weight
	 * 4: date
	 * 5: student's score (if student logged in)
	 */
	public function get_data ($sorting = 0, $start = 0, $count = null, $ignore_score_color = false) {
		$status=CourseManager::get_user_in_course_status(api_get_user_id(), api_get_course_id());
		// do some checks on count, redefine if invalid value
		if (!isset($count)) {
			$count = count ($this->items) - $start;
		}
		if ($count < 0) {
			$count = 0;
		}
		$allitems = $this->items;
		// sort array
		if ($sorting & self :: GDG_SORT_TYPE) {
			usort($allitems, array('GradebookDataGenerator', 'sort_by_type'));
		} elseif ($sorting & self :: GDG_SORT_NAME) {
			usort($allitems, array('GradebookDataGenerator', 'sort_by_name'));
		} elseif ($sorting & self :: GDG_SORT_DESCRIPTION) {
			usort($allitems, array('GradebookDataGenerator', 'sort_by_description'));
		} elseif ($sorting & self :: GDG_SORT_WEIGHT) {
			usort($allitems, array('GradebookDataGenerator', 'sort_by_weight'));
		} elseif ($sorting & self :: GDG_SORT_DATE) {
			usort($allitems, array('GradebookDataGenerator', 'sort_by_date'));
		}
		if ($sorting & self :: GDG_SORT_DESC) {
			$allitems = array_reverse($allitems);
		}
		// get selected items
		$visibleitems = array_slice($allitems, $start, $count);
		//status de user in course
	    $user_id=api_get_user_id();
		$course_code=api_get_course_id();
		$status_user=api_get_status_of_user_in_course ($user_id,$course_code);
		// generate the data to display
		foreach ($visibleitems as $item) {
			$row = array ();
			$row[] = $item;
			$row[] = $item->get_name();
			
		/*	if ($item->get_type() == 'presence')
			{
				$description_array = unserialize($item->get_description());
				$description .= get_lang('Trainer').': '.$description_array['presence_trainer'].'<br />';
				$description .= get_lang('PresenceSheetCreatedBy').': '.$description_array['presence_creator'].'<br />';
				$description .= get_lang('Date').': '.$description_array['presence_date'].'<br />';
				$description .= get_lang('Duration').': '.$description_array['presence_duration'].'<br />';
				$row[] = $description;
			}
			else 
			{
				$row[] = $item->get_description();
			}*/

			$row[] = $this->build_mark_column ($item);			

			$row[] = $item->get_weight();
			if (($status==1 || is_null($status))  && api_is_allowed_to_create_course()) {
				$row[] = $this->build_date_column ($item);
			}
			if(count($this->evals_links)>0)
				if (!api_is_allowed_to_create_course() || $status_user!=1) 

					$row[] = $this->build_result_column ($item, $ignore_score_color);
					//$row[] = $this->get_certificate_link ($item);
					
				/*	if (
						((!$_GET['view'] OR $_GET['view'] == 'evaluation') AND ($item->get_type()<>'presence' AND $item->get_type() <> 'category'))
						OR ($_GET['view'] == 'presence' AND ($item->get_type() == 'presence' OR $item->get_type() == 'category'))
						OR ((!$_GET['view'] OR $_GET['view'] == 'evaluation') AND (!isset($_GET['selectcat']) OR $_GET['selectcat'] == '0') AND $item->get_type() == 'category')
						)
					{*/
						//echo ' ';
						$data[] = $row;
				//	}	
					
                
			 }

		return $data;

	}

	/**
	 * Returns the link to the certificate generation, if the score is enough, otherwise
	 * returns an empty string. This only works with categories.
	 * @param	object Item
	 */
	function get_certificate_link($item) {
		if(is_a($item, 'Category')) {
			if($item->is_certificate_available(api_get_user_id())) {
				$link = '<a href="'.$_SESSION['gradebook_dest'].'?export_certificate=1&cat='.$item->get_id().'&user='.api_get_user_id().'">'.get_lang('Certificate').'</a>';
				return $link;
			}
		}
		return '';
	}
// Sort functions
// Make sure to only use functions as defined in the GradebookItem interface !

	function sort_by_name($item1, $item2) {
		return api_strnatcmp($item1->get_name(), $item2->get_name());
	}

	function sort_by_type($item1, $item2) {
		if ($item1->get_item_type() == $item2->get_item_type()) {
			return $this->sort_by_name($item1,$item2);
		} else {
			return ($item1->get_item_type() < $item2->get_item_type() ? -1 : 1);
		}
	}

	function sort_by_description($item1, $item2) {
		$result = api_strcmp($item1->get_description(), $item2->get_description());
		if ($result == 0) {
			return $this->sort_by_name($item1,$item2);
		}
		return $result;
	}

	function sort_by_weight($item1, $item2) {
		if ($item1->get_weight() == $item2->get_weight()) {
			return $this->sort_by_name($item1,$item2);
		} else {
			return ($item1->get_weight() < $item2->get_weight() ? -1 : 1);
		}
	}

	function sort_by_date($item1, $item2) {
		if ($item1->get_date() == $item2->get_date()) {
			return $this->sort_by_name($item1,$item2);
		} else {
			return ($item1->get_date() < $item2->get_date() ? -1 : 1);
		}
	}


// Other functions

	private function build_result_column ($item, $ignore_score_color)
	{
		$scoredisplay = ScoreDisplay :: instance();
		$score = $item->calc_score(api_get_user_id());

		switch ($item->get_item_type()) {
			// category
			case 'C' :
				if ($score != null) {
					$displaytype = SCORE_PERCENT;
					if ($ignore_score_color) {
						$displaytype |= SCORE_IGNORE_SPLIT;
					}
						return get_lang('Total') . ' : '. $scoredisplay->display_score($score,$displaytype);
				} else {
					return '';
				}
			// evaluation and link
			case 'E' :
			case 'L' :
				$displaytype = SCORE_DIV_PERCENT;
				if ($ignore_score_color) {
					$displaytype |= SCORE_IGNORE_SPLIT;
				}
				return $scoredisplay->display_score($score,$displaytype);
		}
	}

	private function build_date_column ($item) {
		$date = $item->get_date();
		if (!isset($date) || empty($date)) {
			return '';
		} else {
		//	return date("j/n/Y g:i", $date);
			return date("j/n/Y", $date);
		}
	}

	private function build_mark_column ($item) {
        $cat=new Category();
        $show_message=$cat->show_message_resource_delete($item->get_course_code());
        if ($show_message!==false) {
            $no_link = '<img src="../img/folder_new_22_na.png" border="0" hspace="35" align="middle" alt="" />';
        }
		if($item->get_type() == 'evaluation' || $item->get_type() == 'presence'){

            if (api_is_allowed_to_edit () && $item->get_type() != 'category') {
             $image = "mark_22.png";
            } elseif ($item->get_type() == 'category') {
             $image = "folder_new_22.png";
            }else {
             $image = "defaut.gif";
            }
            if (api_is_allowed_to_edit ()) {
			  $mark_link = '<a href="gradebook_view_result.php?'.api_get_cidReq().'&selecteval='.$item->get_id().'"><img src="../img/'.$image.'" border="0" hspace="35" align="middle" alt="" /></a>';
            } else {
			  $mark_link = '<a href="gradebook_statistics.php?'.api_get_cidReq().'&selecteval='.$item->get_id().'"><img src="../img/'.$image.'" border="0" hspace="35" align="middle" alt="" /></a>';
            }
		}
		else
		{
            if (!isset($_GET['selectcat']) || (isset($_GET['selectcat']) && $_GET['selectcat']==0)) {
			  $url = 'gradebook.php?selectcat='.$item->get_id().'&cidReq='.$item->get_course_code();
            } else {
			  $url = $item->get_link();
            }
            if (api_is_allowed_to_edit () && $item->get_type() != 'category') {
             $image = "mark_22.png";
            } elseif ($item->get_type() == 'category') {
             $image = "folder_new_22.png";
            }else {
             $image = "defaut.gif";
            }
			$mark_link = '<a href="'.$url.'"><img src="../img/'.$image.'" border="0" hspace="35" align="middle" alt="" /></a>';
		}
        if ($show_message!==false) {
          $mark_link = $no_link;
        }
		return $mark_link;
	}
}
