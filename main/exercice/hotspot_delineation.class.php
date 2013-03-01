<?php //$id:$
/* For licensing terms, see /dokeos_license.txt */
//error_log(__FILE__);

/**
*	File containing the HotSpot class.
*	@package dokeos.exercise
* 	@author Eric Marguin
* 	@version $Id: admin.php 10680 2007-01-11 21:26:23Z pcool $
*/


if(!class_exists('HotSpotDelineation')):

/**
	CLASS HotSpot
 *
 *	This class allows to instantiate an object of type HotSpot (MULTIPLE CHOICE, UNIQUE ANSWER),
 *	extending the class question
 *
 *	@author Eric Marguin
 *	@package dokeos.exercise
 **/

class HotSpotDelineation extends Question {

	static $typePicture = 'hotspot.gif';
	static $explanationLangVar = 'HotspotDelineation';


	function HotSpotDelineation(){
		parent::question();
		$this -> type = HOT_SPOT_DELINEATION;
	}

	function display(){

	}
	
	function createForm ($form) {
		parent::createForm ($form);
		global $text, $class;
		if(!isset($_GET['editQuestion'])) {
			$renderer = $form->defaultRenderer();
   $form->addElement('html','<div class="hotspot-form">');
   $form->addElement('html','<div class="upload-form" style="float:left">');
			$form->addElement('html', '<div class="row"><div class="label"></div><div class="formw">'.get_lang('UploadJpgPicture').'</div></div>');
			//$form->addElement('file','imageUpload','<span class="form_required">*</span><img src="../img/hotspots.png" />');
			$form->addElement('file','imageUpload');
			// setting the save button here and not in the question class.php
   $form->addElement('html','</div>');
   $form->addElement('html','<div class="button-form" style="float:right;margin-top:14px;">');
			// Saving a question
			$form->addElement('style_submit_button','submitQuestion',get_lang('Upload'), 'class="'.$class.'" style="float:right"');
			$renderer->setElementTemplate('<div class="row"><div class="label" style="margin-top:-30px;">{label}</div><div class="formw" >{element}</div></div>','imageUpload');
			$form->addRule('imageUpload', get_lang('OnlyImagesAllowed'), 'filetype', array ('jpg', 'jpeg', 'png', 'gif'));
			$form->addRule('imageUpload', get_lang('NoImage'), 'uploadedfile');
   $form->addElement('html','</div></div>');
		} else {
			// setting the save button here and not in the question class.php
   $form->addElement('html','<div class="button-form" style="float:right;margin-top:14px;">');
			// Editing a question
			$form->addElement('style_submit_button','submitQuestion',get_lang('Upload'), 'class="'.$class.'" style="float:right"');
   $form->addElement('html','</div>');
		}
		$form -> addElement('hidden', 'submitform');
		$form->addElement('hidden', 'questiontype','7');
  //$form->addElement('html','</div>');

  // Hotspot Screen
  
	}

	function processCreation ($form, $objExercise) {
		$file_info = $form -> getSubmitValue('imageUpload');
		parent::processCreation ($form, $objExercise);
		if(!empty($file_info['tmp_name']))
		{
			$this->uploadPicture($file_info['tmp_name'], $file_info['name']);
			//list($width,$height) = @getimagesize($file_info['tmp_name']);
			list($width,$height) = api_getimagesize($file_info['tmp_name']);
			if($width>=$height) {
				$this->resizePicture('width',600);
			} else {
				$this->resizePicture('height',350);
			}
			$this->save();
		}
	}

	function createAnswersForm ($form) {

    	// nothing

	}

	function processAnswersCreation ($form) {

		// nothing

	}
	
		
	/**
	 * Display the question in tracking mode (use templates in tracking/questions_templates)
	 * @param $nbAttemptsInExercise the number of users who answered the quiz
	 */
	function displayTracking($exerciseId, $nbAttemptsInExercise){
		
		if(!class_exists('Answer'))
			require_once(api_get_path(SYS_CODE_PATH).'exercice/answer.class.php');
			
		$stats = $this->getAverageStats($exerciseId, $nbAttemptsInExercise);
		include(api_get_path(SYS_CODE_PATH).'exercice/tracking/questions_templates/hotspot.page');
		
	}
	
	/**
	 * Returns learners choices for each question in percents
	 * @param $nbAttemptsInExercise the number of users who answered the quiz
	 * @return array the percents
	 */
	function getAverageStats($exerciseId, $nbAttemptsInExercise){
		
		$preparedSql = 'SELECT COUNT(1) as nbCorrectAttempts
						FROM '.Database::get_statistic_table(TABLE_STATISTIC_TRACK_E_ATTEMPT).' as attempts
						INNER JOIN '.Database::get_statistic_table(TABLE_STATISTIC_TRACK_E_EXERCICES).' as exercises
							ON exercises.exe_id = attempts.exe_id
						WHERE course_code = "%s"
						AND exercises.exe_exo_id = %d
						AND attempts.question_id = %d
						AND marks = %d
						GROUP BY answer';
		$sql = sprintf($preparedSql, api_get_course_id(), $exerciseId, $this->id, $this->weighting);
		$rs = Database::query($sql, __FILE__, __LINE__);
		
		$stats['correct'] = array();
		$stats['correct']['total'] = intval(@mysql_result($rs, 0 ,'nbCorrectAttempts'));
		$stats['correct']['average'] = $stats['correct']['total'] / $nbAttemptsInExercise * 100;
		
		$stats['wrong'] = array();
		$stats['wrong']['total'] = $nbAttemptsInExercise - $stats['correct']['total'];
		$stats['wrong']['average'] = 100 - $stats['correct']['average'];
		
		
		return $stats;
		
	}
	

}

endif;
?>
