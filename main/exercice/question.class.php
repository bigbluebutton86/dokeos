<?php
// $Id: question.class.php 22257 2009-07-20 17:50:09Z juliomontoya $

/* For licensing terms, see /dokeos_license.txt */

/**
 * 	File containing the Question class.
 * 	@package dokeos.exercise
 * 	@author Olivier Brouckaert
 * 	@version $Id: question.class.php 22257 2009-07-20 17:50:09Z juliomontoya $
 */
if (!class_exists('Question')):

// answer types
 define('UNIQUE_ANSWER', 1);
 define('MULTIPLE_ANSWER', 2);
 define('FILL_IN_BLANKS', 3);
 define('MATCHING', 4);
 define('FREE_ANSWER', 5);
 define('HOT_SPOT', 6);
 define('HOT_SPOT_ORDER', 7);
 define('REASONING', 8);
 define('HOT_SPOT_DELINEATION', 9);

//define('DOKEOS_QUIZGALLERY', true);

 /**
   CLASS QUESTION
  *
  * 	This class allows to instantiate an object of type Question
  *
  * 	@author Olivier Brouckaert, original author
  * 	@author Patrick Cool, LaTeX support
  * 	@package dokeos.exercise
  */
 abstract class Question {

  var $id;
  var $question;
  var $description;
  var $weighting;
  var $position;
  var $type;
  var $level;
  var $category;
  var $picture;
  var $exerciseList;  // array with the list of exercises which this question is in
  var $mediaPosition;

  static $typePicture = 'new_question.png';
  static $explanationLangVar = '';
  static $questionTypes = array(
      UNIQUE_ANSWER => array('unique_answer.class.php', 'UniqueAnswer'),
      MULTIPLE_ANSWER => array('multiple_answer.class.php', 'MultipleAnswer'),
      FILL_IN_BLANKS => array('fill_blanks.class.php', 'FillBlanks'),
      MATCHING => array('matching.class.php', 'Matching'),
      FREE_ANSWER => array('freeanswer.class.php', 'FreeAnswer'),
      REASONING => array('reasoning.class.php', 'Reasoning'),
      HOT_SPOT => array('hotspot.class.php', 'HotSpot'),
      HOT_SPOT_DELINEATION => array('hotspot_delineation.class.php', 'HotspotDelineation')
  );

  /**
   * constructor of the class
   *
   * @author - Olivier Brouckaert
   */
  function Question() {
   $this->id = 0;
   $this->question = '';
   $this->description = '';
   $this->weighting = 20;
   $this->position = 1;
   $this->picture = '';
   $this->level = 1;
   $this->category = 0;
   $this->mediaPosition = 'right';
   $this->exerciseList = array();
  }

  /**
   * reads question informations from the data base
   *
   * @author - Olivier Brouckaert
   * @param - integer $id - question ID
   * @return - boolean - true if question exists, otherwise false
   */
  static function read($id) {
   global $_course;

   $db_name = !empty($_course['dbName']) ? $_course['dbName'] : $_SESSION['dbName'];
   $TBL_EXERCICES = Database::get_course_table(TABLE_QUIZ_TEST, $db_name);
   $TBL_QUESTIONS = Database::get_course_table(TABLE_QUIZ_QUESTION, $db_name);
   $TBL_EXERCICE_QUESTION = Database::get_course_table(TABLE_QUIZ_TEST_QUESTION, $db_name);

   $sql_count = "SELECT count(*) as count FROM $TBL_QUESTIONS WHERE id='" . Database::escape_string($id) . "'";
   $rs_count = Database::query($sql_count, __FILE__, __LINE__);
   $count = Database::result($rs_count, 0);

   // if the question has been found
   if ($count > 0) {
    $sql = "SELECT question,description,ponderation,position,type,picture,level,category,media_position FROM $TBL_QUESTIONS WHERE id='" . Database::escape_string($id) . "' order by position";
    $result = Database::query($sql, __FILE__, __LINE__);

    $object = Database::fetch_object($result);
    $type = $object->type;
 /* if($type == 9) {//Hotspot delineation is still in development
        $type = 6;
    }*/
    $objQuestion = Question::getInstance($type);
    $objQuestion->id = $id;
    $objQuestion->question = $object->question;
    $objQuestion->description = $object->description;
    $objQuestion->weighting = $object->ponderation;
    $objQuestion->position = $object->position;
    $objQuestion->type = $object->type;
    $objQuestion->picture = $object->picture;
    $objQuestion->level = (int) $object->level;
	$objQuestion->category = $object->category;
	$objQuestion->mediaPosition = $object->media_position;

    $sql = "SELECT exercice_id FROM $TBL_EXERCICE_QUESTION WHERE question_id='" . intval($id) . "'";
    $quiz_result = api_sql_query($sql, __FILE__, __LINE__);

    // fills the array with the exercises which this question is in
    while ($quiz_object = Database::fetch_object($quiz_result)) {
     $objQuestion->exerciseList[] = $quiz_object->exercice_id;
    }
    return $objQuestion;
   }
   // question not found
   return false;
  }

  /**
   * Set media position field 
   */
  function selectMediaPosition() {      
   return $this->mediaPosition;
  }

  /**
   * returns the question ID
   *
   * @author - Olivier Brouckaert
   * @return - integer - question ID
   */
  function selectId() {
   return $this->id;
  }

  /**
   * returns the question title
   *
   * @author - Olivier Brouckaert
   * @return - string - question title
   */
  function selectTitle() {
   $this->question = api_parse_tex($this->question);
   return $this->question;
  }

  /**
   * returns the question description
   *
   * @author - Olivier Brouckaert
   * @return - string - question description
   */
  function selectDescription() {
   $this->description = api_parse_tex($this->description);
   return $this->description;
  }

  /**
   * returns the question weighting
   *
   * @author - Olivier Brouckaert
   * @return - integer - question weighting
   */
  function selectWeighting() {
   return $this->weighting;
  }

  /**
   * returns the question position
   *
   * @author - Olivier Brouckaert
   * @return - integer - question position
   */
  function selectPosition() {
   return $this->position;
  }

  /**
   * returns the answer type
   *
   * @author - Olivier Brouckaert
   * @return - integer - answer type
   */
  function selectType() {
   return $this->type;
  }

  /**
   * returns the level of the question
   *
   * @author - Nicolas Raynaud
   * @return - integer - level of the question, 0 by default.
   */
  function selectLevel() {
   return $this->level;
  }

   /**
   * returns the category of the question
   *
   * @author - Nicolas Raynaud
   * @return - category of the question, 0 by default.
   */
  function selectCategory() {
   return $this->category;
  }


  /**
   * returns the picture name
   *
   * @author - Olivier Brouckaert
   * @return - string - picture name
   */
  function selectPicture() {
   return $this->picture;
  }

  /**
   * returns the array with the exercise ID list
   *
   * @author - Olivier Brouckaert
   * @return - array - list of exercise ID which the question is in
   */
  function selectExerciseList() {
   return $this->exerciseList;
  }

  /**
   * returns the number of exercises which this question is in
   *
   * @author - Olivier Brouckaert
   * @return - integer - number of exercises
   */
  function selectNbrExercises() {
   return sizeof($this->exerciseList);
  }

  /**
   * changes the question title
   *
   * @author - Olivier Brouckaert
   * @param - string $title - question title
   */
  function updateTitle($title) {
   $this->question = $title;
  }

  /**
   * changes the question description
   *
   * @author - Olivier Brouckaert
   * @param - string $description - question description
   */
  function updateDescription($description) {
   $this->description = $description;
  }

  /**
   * changes the question weighting
   *
   * @author - Olivier Brouckaert
   * @param - integer $weighting - question weighting
   */
  function updateWeighting($weighting) {
   $this->weighting = $weighting;
  }

  /**
   * changes the question position
   *
   * @author - Olivier Brouckaert
   * @param - integer $position - question position
   */
  function updatePosition($position) {
   $this->position = $position;
  }

  /**
   * changes the question level
   *
   * @author - Nicolas Raynaud
   * @param - integer $level - question level
   */
  function updateLevel($level) {
   $this->level = $level;
  }

  /**
   * changes the question category
   *
   * @author - Nicolas Raynaud
   * @param - integer $level - question level
   */
  function updateCategory($category) {
   $this->category = $category;
  }

  /**
   * changes the answer type. If the user changes the type from "unique answer" to "multiple answers"
   * (or conversely) answers are not deleted, otherwise yes
   *
   * @author - Olivier Brouckaert
   * @param - integer $type - answer type
   */
  function updateType($type) {
   global $TBL_REPONSES;

   // if we really change the type
   if ($type != $this->type) {
    // if we don't change from "unique answer" to "multiple answers" (or conversely)
    if (!in_array($this->type, array(UNIQUE_ANSWER, MULTIPLE_ANSWER)) || !in_array($type, array(UNIQUE_ANSWER, MULTIPLE_ANSWER))) {
     // removes old answers
     $sql = "DELETE FROM $TBL_REPONSES WHERE question_id='" . Database::escape_string($this->id) . "'";
     api_sql_query($sql, __FILE__, __LINE__);
    }

    $this->type = $type;
   }
  }

  function updateMediaPosition($mediaPosition) {
   $this->mediaPosition = $mediaPosition;
  }

  /**
   * adds a picture to the question
   *
   * @author - Olivier Brouckaert
   * @param - string $Picture - temporary path of the picture to upload
   * @param - string $PictureName - Name of the picture
   * @return - boolean - true if uploaded, otherwise false
   */
  function uploadPicture($Picture, $PictureName) {
   global $picturePath, $_course, $_user;

   // if the question has got an ID
   if ($this->id) {

    $extension = pathinfo($PictureName, PATHINFO_EXTENSION);
    $this->picture = 'quiz-' . $this->id . '.jpg';
    if ($extension == 'gif' || $extension == 'png') {
     $o_img = new image($Picture);
     $o_img->send_image('JPG', $picturePath . '/' . $this->picture);
     $document_id = add_document($_course, '/images/' . $this->picture, 'file', filesize($picturePath . '/' . $this->picture), $this->picture);
    } else {
     move_uploaded_file($Picture, $picturePath . '/' . $this->picture) ? true : false;
    }
    $document_id = add_document($_course, '/images/' . $this->picture, 'file', filesize($picturePath . '/' . $this->picture), $this->picture);
    if ($document_id) {
     return api_item_property_update($_course, TOOL_DOCUMENT, $document_id, 'DocumentAdded', $_user['user_id']);
    }
   }

   return false;
  }

  /**
   * Resizes a picture || Warning!: can only be called after uploadPicture, or if picture is already available in object.
   *
   * @author - Toon Keppens
   * @param - string $Dimension - Resizing happens proportional according to given dimension: height|width|any
   * @param - integer $Max - Maximum size
   * @return - boolean - true if success, false if failed
   */
  function resizePicture($Dimension, $Max) {
   global $picturePath;

   // if the question has an ID
   if ($this->id) {
    // Get dimensions from current image.
    $current_img = imagecreatefromjpeg($picturePath . '/' . $this->picture);

    $current_image_size = getimagesize($picturePath . '/' . $this->picture);
    $current_height = imagesy($current_img);
    $current_width = imagesx($current_img);

    if ($current_image_size[0] < $Max && $current_image_size[1] < $Max)
     return true;
    elseif ($current_height == "")
     return false;

    // Resize according to height.
    if ($Dimension == "height") {
     $resize_scale = $current_height / $Max;
     $new_height = $Max;
     $new_width = ceil($current_width / $resize_scale);
    }

    // Resize according to width
    if ($Dimension == "width") {
     $resize_scale = $current_width / $Max;
     $new_width = $Max;
     $new_height = ceil($current_height / $resize_scale);
    }

    // Resize according to height or width, both should not be larger than $Max after resizing.
    if ($Dimension == "any") {
     if ($current_height > $current_width || $current_height == $current_width) {
      $resize_scale = $current_height / $Max;
      $new_height = $Max;
      $new_width = ceil($current_width / $resize_scale);
     }
     if ($current_height < $current_width) {
      $resize_scale = $current_width / $Max;
      $new_width = $Max;
      $new_height = ceil($current_height / $resize_scale);
     }
    }

    // Create new image
    $new_img = imagecreatetruecolor($new_width, $new_height);
    $bgColor = imagecolorallocate($new_img, 255, 255, 255);
    imagefill($new_img, 0, 0, $bgColor);

    // Resize image
    imagecopyresized($new_img, $current_img, 0, 0, 0, 0, $new_width, $new_height, $current_width, $current_height);

    // Write image to file
    $result = imagejpeg($new_img, $picturePath . '/' . $this->picture, 100);

    // Delete temperory images, clear memory
    imagedestroy($current_img);
    imagedestroy($new_img);

    if ($result) {
     return true;
    } else {
     return false;
    }
   }
  }

  /**
   * deletes the picture
   *
   * @author - Olivier Brouckaert
   * @return - boolean - true if removed, otherwise false
   */
  function removePicture() {
   global $picturePath;

   // if the question has got an ID and if the picture exists
   if ($this->id) {
    $picture = $this->picture;
    $this->picture = '';

    return @unlink($picturePath . '/' . $picture) ? true : false;
   }

   return false;
  }

  /**
   * exports a picture to another question
   *
   * @author - Olivier Brouckaert
   * @param - integer $questionId - ID of the target question
   * @return - boolean - true if copied, otherwise false
   */
  function exportPicture($questionId) {
   global $TBL_QUESTIONS, $picturePath;

   // if the question has got an ID and if the picture exists
   if ($this->id && !empty($this->picture)) {
    $picture = explode('.', $this->picture);
    $Extension = $picture[sizeof($picture) - 1];
    $picture = 'quiz-' . $questionId . '.' . $Extension;

    $sql = "UPDATE $TBL_QUESTIONS SET picture='" . Database::escape_string($picture) . "' WHERE id='" . Database::escape_string($questionId) . "'";
    api_sql_query($sql, __FILE__, __LINE__);

    return @copy($picturePath . '/' . $this->picture, $picturePath . '/' . $picture) ? true : false;
   }

   return false;
  }

  /**
   * saves the picture coming from POST into a temporary file
   * Temporary pictures are used when we don't want to save a picture right after a form submission.
   * For example, if we first show a confirmation box.
   *
   * @author - Olivier Brouckaert
   * @param - string $Picture - temporary path of the picture to move
   * @param - string $PictureName - Name of the picture
   */
  function setTmpPicture($Picture, $PictureName) {
   global $picturePath;

   $PictureName = explode('.', $PictureName);
   $Extension = $PictureName[sizeof($PictureName) - 1];

   // saves the picture into a temporary file
   @move_uploaded_file($Picture, $picturePath . '/tmp.' . $Extension);
  }

  /**
   * moves the temporary question "tmp" to "quiz-$questionId"
   * Temporary pictures are used when we don't want to save a picture right after a form submission.
   * For example, if we first show a confirmation box.
   *
   * @author - Olivier Brouckaert
   * @return - boolean - true if moved, otherwise false
   */
  function getTmpPicture() {
   global $picturePath;

   // if the question has got an ID and if the picture exists
   if ($this->id) {
    if (file_exists($picturePath . '/tmp.jpg')) {
     $Extension = 'jpg';
    } elseif (file_exists($picturePath . '/tmp.gif')) {
     $Extension = 'gif';
    } elseif (file_exists($picturePath . '/tmp.png')) {
     $Extension = 'png';
    }

    $this->picture = 'quiz-' . $this->id . '.' . $Extension;

    return @rename($picturePath . '/tmp.' . $Extension, $picturePath . '/' . $this->picture) ? true : false;
   }

   return false;
  }

  /**
   * updates the question in the data base
   * if an exercise ID is provided, we add that exercise ID into the exercise list
   *
   * @author - Olivier Brouckaert
   * @param - integer $exerciseId - exercise ID if saving in an exercise
   */
  function save($exerciseId=0) {
   global $_course, $_user;

   $TBL_EXERCICE_QUESTION = Database::get_course_table(TABLE_QUIZ_TEST_QUESTION);
   $TBL_QUESTIONS = Database::get_course_table(TABLE_QUIZ_QUESTION);
   $id = $this->id;
   $question = $this->question;
   $description = $this->description;
   $weighting = $this->weighting;
   $position = $this->position;
   $type = $this->type;
   $picture = $this->picture;
   $level = $this->level;
   $mediaPosition = $this->mediaPosition;

   if (($_SESSION['fromTpl'] == '1') && ($_SESSION['editQn'] == '')) {
     $id = '';
	 $exerciseId = $_REQUEST['exerciseId'];
   }

   // question already exists
   if (!empty($id)) {
    $sql = "UPDATE $TBL_QUESTIONS SET
					question 		='" . Database::escape_string($question) . "',
					description		='" . Database::escape_string(Security::remove_XSS(api_html_entity_decode($description), COURSEMANAGERLOWSECURITY)) . "',
					ponderation		='" . Database::escape_string($weighting) . "',
					position		='" . Database::escape_string($position) . "',
					type			='" . Database::escape_string($type) . "',
					picture			='" . Database::escape_string($picture) . "',
					level			='" . Database::escape_string($level) . "',
					media_position  ='" . Database::escape_string($mediaPosition) . "' 
					WHERE id='" . Database::escape_string($id) . "'";
    api_sql_query($sql, __FILE__, __LINE__);
    if (!empty($exerciseId)) {
     api_item_property_update($_course, TOOL_QUIZ, $id, 'QuizQuestionUpdated', $_user['user_id']);
    }
    if (api_get_setting('search_enabled') == 'true') {
     if ($exerciseId != 0) {
      //$this->search_engine_edit($exerciseId);
     } else {
      /**
       * actually there is *not* an user interface for
       * creating questions without a relation with an exercise
       */
     }
    }
   } else {// creates a new question
    $sql = "SELECT max(position) FROM $TBL_QUESTIONS as question, $TBL_EXERCICE_QUESTION as test_question WHERE question.id=test_question.question_id AND test_question.exercice_id='" . Database::escape_string($exerciseId) . "'";

    $result = api_sql_query($sql);
    $current_position = Database::result($result, 0, 0);
    $this->updatePosition($current_position + 1);
    $position = $this->position;

    $sql = "INSERT INTO $TBL_QUESTIONS(question,description,ponderation,position,type,picture,level,media_position) VALUES(
					'" . Database::escape_string(Security::remove_XSS($question,COURSEMANAGERLOWSECURITY)) . "',
					'" . Database::escape_string(Security::remove_XSS(api_html_entity_decode($description), COURSEMANAGERLOWSECURITY)) . "',
					'" . Database::escape_string($weighting) . "',
					'" . Database::escape_string($position) . "',
					'" . Database::escape_string($type) . "',
					'" . Database::escape_string($picture) . "',
					'" . Database::escape_string($level) . "',
					'" . Database::escape_string($mediaPosition) . "'
					)";

    api_sql_query($sql, __FILE__, __LINE__);

    $this->id = Database::get_last_insert_id();

    api_item_property_update($_course, TOOL_QUIZ, $this->id, 'QuizQuestionAdded', $_user['user_id']);

    // If hotspot, create first answer
    if ($type == HOT_SPOT || $type == HOT_SPOT_ORDER) {
     $TBL_ANSWERS = Database::get_course_table(TABLE_QUIZ_ANSWER);

     $sql = "INSERT INTO $TBL_ANSWERS (`id` , `question_id` , `answer` , `correct` , `comment` , `ponderation` , `position` , `hotspot_coordinates` , `hotspot_type` ) VALUES ('1', '" . Database::escape_string($this->id) . "', '', NULL , '', '10' , '1', '0;0|0|0', 'square')";
     api_sql_query($sql, __FILE__, __LINE__);
    }

    if (api_get_setting('search_enabled') == 'true') {
     if ($exerciseId != 0) {
      //$this->search_engine_edit($exerciseId, TRUE);
     } else {
      /**
       * actually there is *not* an user interface for
       * creating questions without a relation with an exercise
       */
     }
    }
   }

   // if the question is created in an exercise
   if ($exerciseId) {
    /*
      $sql = 'UPDATE '.Database::get_course_table(TABLE_LP_ITEM).'
      SET max_score = '.intval($weighting).'
      WHERE item_type = "'.TOOL_QUIZ.'"
      AND path='.intval($exerciseId);
      api_sql_query($sql,__FILE__,__LINE__);
     */
    // adds the exercise into the exercise list of this question
    $this->addToList($exerciseId, TRUE);
   }
  }

  function create_question_from_an_attached_file ($quiz_id, $question_name, $ponderation = 0, $type = 1, $level = 1) {
    $tbl_quiz_question = Database::get_course_table(TABLE_QUIZ_QUESTION);
    $tbl_quiz_rel_question = Database::get_course_table(TABLE_QUIZ_TEST_QUESTION);

    // Get the max position
    $sql = "SELECT max(position) as max_position FROM $tbl_quiz_question q INNER JOIN $tbl_quiz_rel_question r
    ON q.id = r.question_id AND exercice_id = '".Database::escape_string($quiz_id)."'";
    $rs_max = Database::query($sql, __FILE__, __LINE__);
    $row_max = Database::fetch_object($rs_max);
    $max_position = $row_max->max_position +1;

	$question_description = '<table height="100%" width="98%" cellspacing="2" cellpadding="0" style="font-family: Comic Sans MS; font-size: 16px;"><tbody><tr><td height="323px" align="center"><img height="310px" src="../img/instructor-idea.jpg" alt="" /></td></tr></tbody></table>';

    // Insert the new question
    $sql = "INSERT INTO $tbl_quiz_question (question,description,ponderation,position,type,level)
    VALUES('".Database::escape_string($question_name)."', '".Database::escape_string($question_description)."', '".$ponderation."', '".$max_position."',
     '".$type."', '".$level."')";
    $rs = Database::query($sql, __FILE__, __LINE__);
    // Get the question ID
    $question_id = Database::get_last_insert_id();

    // Get the max question_order
    $sql = "SELECT max(question_order) as max_order FROM $tbl_quiz_rel_question WHERE exercice_id ='".$quiz_id."'
     AND exercice_id ='".$quiz_id."'";
    $rs_max_order = Database::query($sql, __FILE__, __LINE__);
    $row_max_order = Database::fetch_object($rs_max);
    $max_order = $row_max_order->max_order + 1;

    // Attach questions to quiz
    $sql = "INSERT INTO $tbl_quiz_rel_question(question_id,exercice_id,question_order)
    VALUES('".$question_id."', '".$quiz_id."', '".$max_order."')";
    $rs = Database::query($sql, __FILE__, __LINE__);
    return $question_id;
  }
  function search_engine_edit($exerciseId, $addQs=FALSE, $rmQs=FALSE) {
   // update search engine and its values table if enabled
   if (api_get_setting('search_enabled') == 'true' && extension_loaded('xapian')) {
    $course_id = api_get_course_id();
    // get search_did
    $tbl_se_ref = Database::get_main_table(TABLE_MAIN_SEARCH_ENGINE_REF);
    if ($addQs || $rmQs) {
     //there's only one row per question on normal db and one document per question on search engine db
     $sql = 'SELECT * FROM %s WHERE course_code=\'%s\' AND tool_id=\'%s\' AND ref_id_second_level=%s LIMIT 1';
     $sql = sprintf($sql, $tbl_se_ref, $course_id, TOOL_QUIZ, $this->id);
    } else {
     $sql = 'SELECT * FROM %s WHERE course_code=\'%s\' AND tool_id=\'%s\' AND ref_id_high_level=%s AND ref_id_second_level=%s LIMIT 1';
     $sql = sprintf($sql, $tbl_se_ref, $course_id, TOOL_QUIZ, $exerciseId, $this->id);
    }
    $res = api_sql_query($sql, __FILE__, __LINE__);

    if (Database::num_rows($res) > 0 || $addQs) {
     require_once(api_get_path(LIBRARY_PATH) . 'search/DokeosIndexer.class.php');
     require_once(api_get_path(LIBRARY_PATH) . 'search/IndexableChunk.class.php');

     $di = new DokeosIndexer();
     if ($addQs) {
      $question_exercises = array((int) $exerciseId);
     } else {
      $question_exercises = array();
     }
     isset($_POST['language']) ? $lang = Database::escape_string($_POST['language']) : $lang = 'english';
     $di->connectDb(NULL, NULL, $lang);

     // retrieve others exercise ids
     $se_ref = Database::fetch_array($res);
     $se_doc = $di->get_document((int) $se_ref['search_did']);
     if ($se_doc !== FALSE) {
      if (($se_doc_data = $di->get_document_data($se_doc)) !== FALSE) {
       $se_doc_data = unserialize($se_doc_data);
       if (isset($se_doc_data[SE_DATA]['type']) && $se_doc_data[SE_DATA]['type'] == SE_DOCTYPE_EXERCISE_QUESTION) {
        if (isset($se_doc_data[SE_DATA]['exercise_ids']) && is_array($se_doc_data[SE_DATA]['exercise_ids'])) {
         foreach ($se_doc_data[SE_DATA]['exercise_ids'] as $old_value) {
          if (!in_array($old_value, $question_exercises)) {
           $question_exercises[] = $old_value;
          }
         }
        }
       }
      }
     }
     if ($rmQs) {
      while (($key = array_search($exerciseId, $question_exercises)) !== FALSE) {
       unset($question_exercises[$key]);
      }
     }

     // build the chunk to index
     $ic_slide = new IndexableChunk();
     $ic_slide->addValue("title", $this->question);
     $ic_slide->addCourseId($course_id);
     $ic_slide->addToolId(TOOL_QUIZ);
     $xapian_data = array(
         SE_COURSE_ID => $course_id,
         SE_TOOL_ID => TOOL_QUIZ,
         SE_DATA => array('type' => SE_DOCTYPE_EXERCISE_QUESTION, 'exercise_ids' => $question_exercises, 'question_id' => (int) $this->id),
         SE_USER => (int) api_get_user_id(),
     );
     $ic_slide->xapian_data = serialize($xapian_data);
     $question_description = !empty($this->description) ? $this->description : $this->question;
     $ic_slide->addValue("content", $question_description);

     //TODO: index answers, see also form validation on question_admin.inc.php

     $di->remove_document((int) $se_ref['search_did']);
     $di->addChunk($ic_slide);

     //index and return search engine document id
     if (!empty($question_exercises)) { // if empty there is nothing to index
      $did = $di->index();
      unset($di);
     }
     if ($did || $rmQs) {
      // save it to db
      if ($addQs || $rmQs) {
       $sql = 'DELETE FROM %s WHERE course_code=\'%s\' AND tool_id=\'%s\' AND ref_id_second_level=\'%s\'';
       $sql = sprintf($sql, $tbl_se_ref, $course_id, TOOL_QUIZ, $this->id);
      } else {
       $sql = 'DELETE FROM %s WHERE course_code=\'%s\' AND tool_id=\'%s\' AND ref_id_high_level=\'%s\' AND ref_id_second_level=\'%s\'';
       $sql = sprintf($sql, $tbl_se_ref, $course_id, TOOL_QUIZ, $exerciseId, $this->id);
      }
      api_sql_query($sql, __FILE__, __LINE__);
      if ($rmQs) {
       if (!empty($question_exercises)) {
        $sql = 'INSERT INTO %s (id, course_code, tool_id, ref_id_high_level, ref_id_second_level, search_did)
                              VALUES (NULL , \'%s\', \'%s\', %s, %s, %s)';
        $sql = sprintf($sql, $tbl_se_ref, $course_id, TOOL_QUIZ, array_shift($question_exercises), $this->id, $did);
        api_sql_query($sql, __FILE__, __LINE__);
       }
      } else {
       $sql = 'INSERT INTO %s (id, course_code, tool_id, ref_id_high_level, ref_id_second_level, search_did)
                            VALUES (NULL , \'%s\', \'%s\', %s, %s, %s)';
       $sql = sprintf($sql, $tbl_se_ref, $course_id, TOOL_QUIZ, $exerciseId, $this->id, $did);
       api_sql_query($sql, __FILE__, __LINE__);
      }
     }
    }
   }
  }

  /**
   * adds an exercise into the exercise list
   *
   * @author - Olivier Brouckaert
   * @param - integer $exerciseId - exercise ID
   * @param - boolean $fromSave - comming from $this->save() or not
   */
  function addToList($exerciseId, $fromSave=FALSE) {
   global $TBL_EXERCICE_QUESTION;
   $TBL_EXERCICE_QUESTION = Database::get_course_table(TABLE_QUIZ_TEST_QUESTION);
   $id = $this->id;
   // checks if the exercise ID is not in the list
   if (!in_array($exerciseId, $this->exerciseList)) {
    $this->exerciseList[] = $exerciseId;
    // Get the max value of question_order field
    $sql = "SELECT max(question_order) AS last_order FROM $TBL_EXERCICE_QUESTION WHERE exercice_id='".Database::escape_string($exerciseId)."' ";
    $res = Database::query($sql, __FILE__, __LINE__);
    $row = Database::fetch_object($res);
    // Next question order
    $next_order = $row->last_order + 1;
    // Save new question to quiz
    $sql = "INSERT INTO $TBL_EXERCICE_QUESTION (question_id, exercice_id, question_order) VALUES('" . Database::escape_string($id) . "','" . Database::escape_string($exerciseId) . "','" . Database::escape_string($next_order) . "')";
    Database::query($sql, __FILE__, __LINE__);

    // we do not want to reindex if we had just saved adnd indexed the question
    if (!$fromSave) {
     $this->search_engine_edit($exerciseId, TRUE);
    }
   }

   /*if($_REQUEST['fromTpl'] == 1)
	{
	$sql = "SELECT max(question_order) AS last_order FROM $TBL_EXERCICE_QUESTION WHERE exercice_id='".Database::escape_string($exerciseId)."' ";
    $res = Database::query($sql, __FILE__, __LINE__);
    $row = Database::fetch_object($res);
    // Next question order
    $next_order = $row->last_order + 1;
    // Save new question to quiz
    $sql = "INSERT INTO $TBL_EXERCICE_QUESTION (question_id, exercice_id, question_order) VALUES('" . Database::escape_string($id) . "','" . Database::escape_string($exerciseId) . "','" . Database::escape_string($next_order) . "')";
    Database::query($sql, __FILE__, __LINE__);
	}*/
  }

  /**
   * removes an exercise from the exercise list
   *
   * @author - Olivier Brouckaert
   * @param - integer $exerciseId - exercise ID
   * @return - boolean - true if removed, otherwise false
   */
  function removeFromList($exerciseId) {
   global $TBL_EXERCICE_QUESTION;

   $id = $this->id;

   // searches the position of the exercise ID in the list
   $pos = array_search($exerciseId, $this->exerciseList);

   // exercise not found
   if ($pos === false) {
    return false;
   } else {
    // deletes the position in the array containing the wanted exercise ID
    unset($this->exerciseList[$pos]);
    //update order of other elements
    $sql = "SELECT question_order FROM $TBL_EXERCICE_QUESTION WHERE question_id='" . Database::escape_string($id) . "' AND exercice_id='" . Database::escape_string($exerciseId) . "'";
    $res = api_sql_query($sql, __FILE__, __LINE__);
    if (Database::num_rows($res) > 0) {
     $row = Database::fetch_array($res);
     if (!empty($row['question_order'])) {
      $sql = "UPDATE $TBL_EXERCICE_QUESTION SET question_order = question_order-1 WHERE exercice_id='" . Database::escape_string($exerciseId) . "' AND question_order > " . $row['question_order'];
      $res = api_sql_query($sql, __FILE__, __LINE__);
     }
    }

    $sql = "DELETE FROM $TBL_EXERCICE_QUESTION WHERE question_id='" . Database::escape_string($id) . "' AND exercice_id='" . Database::escape_string($exerciseId) . "'";
    api_sql_query($sql, __FILE__, __LINE__);

    return true;
   }
  }

  /**
   * deletes a question from the database
   * the parameter tells if the question is removed from all exercises (value = 0),
   * or just from one exercise (value = exercise ID)
   *
   * @author - Olivier Brouckaert
   * @param - integer $deleteFromEx - exercise ID if the question is only removed from one exercise
   */
  function delete($deleteFromEx=0) {
   global $_course, $_user;

   $TBL_EXERCICE_QUESTION = Database::get_course_table(TABLE_QUIZ_TEST_QUESTION);
   $TBL_QUESTIONS = Database::get_course_table(TABLE_QUIZ_QUESTION);
   $TBL_REPONSES = Database::get_course_table(TABLE_QUIZ_ANSWER);

   $id = $this->id;

   // if the question must be removed from all exercises
   if (!$deleteFromEx) {
    //update the question_order of each question to avoid inconsistencies
    $sql = "SELECT exercice_id, question_order FROM $TBL_EXERCICE_QUESTION WHERE question_id='" . Database::escape_string($id) . "'";
    $res = api_sql_query($sql, __FILE__, __LINE__);
    if (Database::num_rows($res) > 0) {
     while ($row = Database::fetch_array($res)) {
      if (!empty($row['question_order'])) {
       $sql = "UPDATE $TBL_EXERCICE_QUESTION SET question_order = question_order-1 WHERE exercice_id='" . Database::escape_string($row['exercice_id']) . "' AND question_order > " . $row['question_order'];
       $res = api_sql_query($sql, __FILE__, __LINE__);
      }
     }
    }
    $sql = "DELETE FROM $TBL_EXERCICE_QUESTION WHERE question_id='" . Database::escape_string($id) . "'";
    api_sql_query($sql, __FILE__, __LINE__);

    $sql = "DELETE FROM $TBL_QUESTIONS WHERE id='" . Database::escape_string($id) . "'";
    api_sql_query($sql, __FILE__, __LINE__);

    $sql = "DELETE FROM $TBL_REPONSES WHERE question_id='" . Database::escape_string($id) . "'";
    api_sql_query($sql, __FILE__, __LINE__);

    api_item_property_update($_course, TOOL_QUIZ, $id, 'QuizQuestionDeleted', $_user['user_id']);
    $this->removePicture();

    // resets the object
    $this->Question();
   }
   // just removes the exercise from the list
   else {
    $this->removeFromList($deleteFromEx);
    if (api_get_setting('search_enabled') == 'true' && extension_loaded('xapian')) {
     // disassociate question with this exercise
     $this->search_engine_edit($deleteFromEx, FALSE, TRUE);
    }
    api_item_property_update($_course, TOOL_QUIZ, $id, 'QuizQuestionDeleted', $_user['user_id']);
   }
  }

  /**
   * duplicates the question
   *
   * @author - Olivier Brouckaert
   * @return - integer - ID of the new question
   */
  function duplicate() {
   global $TBL_QUESTIONS, $picturePath;

   $question = $this->question;
   $description = $this->description;
   $weighting = $this->weighting;
   $position = $this->position;
   $type = $this->type;

   $sql = "INSERT INTO $TBL_QUESTIONS(question,description,ponderation,position,type) VALUES('" . Database::escape_string($question) . "','" . Database::escape_string($description) . "','" . Database::escape_string($weighting) . "','" . Database::escape_string($position) . "','" . Database::escape_string($type) . "')";
   api_sql_query($sql, __FILE__, __LINE__);

   $id = Database::get_last_insert_id();
   // duplicates the picture
   $this->exportPicture($id);

   return $id;
  }

  /**
   * Returns an instance of the class corresponding to the type
   * @param integer $type the type of the question
   * @return an instance of a Question subclass (or of Questionc class by default)
   */
  static function getInstance($type) {
   if (!is_null($type)) {
    list($file_name, $class_name) = self::$questionTypes[$type];
    include_once($file_name);
    if (class_exists($class_name)) {
     return new $class_name();
    } else {
     echo 'Can\'t instanciate class ' . $class_name . ' of type ' . $type;
     return null;
    }
   }
  }

  /**
   * Creates the form to create / edit a question
   * A subclass can redifine this function to add fields...
   * @param FormValidator $form the formvalidator instance (by reference)
   */
  function createForm(&$form, $fck_config=0) {
   global $charset;
   echo '<style>
					div.row div.label{ width: 10%; }
					div.row div.formw{ width: 89%; }
				</style>';

   echo '<script>
			function show_media()
			{
			if(document.getElementById(\'media\').style.display == \'none\') {
				document.getElementById(\'media\').style.display = \'block\';
				document.getElementById(\'media_icon\').innerHTML=\'&nbsp;<img style="vertical-align: middle;" src="../img/looknfeel.png" alt="" />&nbsp;' . get_lang('EnrichQuestion') . '\';
			} else {
				document.getElementById(\'media\').style.display = \'none\';
				document.getElementById(\'media_icon\').innerHTML=\'&nbsp;<img style="vertical-align: middle;" src="../img/looknfeelna.png" alt="" />&nbsp;' . get_lang('EnrichQuestion') . '\';
			}
		}

		function bigform()
			{
			if(document.getElementById(\'newform\').style.display == \'none\') {
				document.getElementById(\'newform\').style.display = \'block\';
				document.getElementById(\'big_icon\').innerHTML=\'<img src="../img/BigFormFilled.png" alt="" onclick="smallform()" />\';
				document.getElementById(\'small_icon\').innerHTML=\'<img src="../img/SmallFormClosed.png" alt="" onclick="bigform()" />\';
				formlineshigh();
			} else {
				document.getElementById(\'newform\').style.display = \'none\';
				document.getElementById(\'big_icon\').innerHTML=\'<img src="../img/BigFormClosed.png" alt="" onclick="smallform()" />\';
				document.getElementById(\'small_icon\').innerHTML=\'<img src="../img/SmallFormFilled.png" alt="" onclick="bigform()" />\';
				formlineslow();
			}

		}
		function smallform()
			{
			if(document.getElementById(\'newform\').style.display == \'none\') {
				document.getElementById(\'newform\').style.display = \'block\';
				document.getElementById(\'small_icon\').innerHTML=\'<img src="../img/SmallFormClosed.png" alt="" onclick="bigform()" />\';
				document.getElementById(\'big_icon\').innerHTML=\'<img src="../img/BigFormFilled.png" alt="" onclick="smallform()" />\';
				formlineshigh();
			} else {
				document.getElementById(\'newform\').style.display = \'none\';
				document.getElementById(\'small_icon\').innerHTML=\'<img src="../img/SmallFormFilled.png" alt="" onclick="bigform()" />\';
				document.getElementById(\'big_icon\').innerHTML=\'<img src="../img/BigFormClosed.png" alt="" onclick="smallform()" />\';
				formlineslow();
			}

		}
		function highlineform()
	  {
		 document.getElementById("questionName___Frame").style.height = "150px";
		 var questiontype = document.question_admin_form.questiontype.value;

		 if(questiontype != 6)
	     {
			 if(questiontype == 4 || questiontype == 5)
			 {
			 document.getElementById("questionDescription___Frame").style.height = "400px";
			 }
			 else
			 {
			 document.getElementById("questionDescription___Frame").style.height = "520px";
			 }
         }

		 if(questiontype != "4")
			{
				document.question_admin_form.formsize.value = "High";
				if(questiontype == "6")
				{
					var nb_matches = document.question_admin_form.nb_matches.value;
					var nb_options = document.question_admin_form.nb_options.value;

					for(var i=1;i<=nb_matches;i++)
					{
						document.getElementById("nos["+i+"]").style.height = "160px";
						document.getElementById("answer["+i+"]___Frame").style.height = "150px";
					}

					for(var i=1;i<=nb_options;i++)
					{
						document.getElementById("alpha["+i+"]").style.height = "160px";
						document.getElementById("option["+i+"]___Frame").style.height = "150px";
					}
				}
				else
				{
					var nb_answers = document.question_admin_form.nb_answers.value;

					for(var i=1;i<=nb_answers;i++)
					{
						document.getElementById("answer["+i+"]___Frame").style.height = "150px";
					}
				}
			}
			if(questiontype != 5)
			{
				document.getElementById("comment[1]___Frame").style.height = "150px";
				document.getElementById("comment[2]___Frame").style.height = "150px";
			}
			if(questiontype == 4)
			{
				document.getElementById("answer___Frame").style.height = "400px";
			}
	  }

	  function lowlineform()
	  {
		 document.getElementById("questionName___Frame").style.height = "40px";
		 var questiontype = document.question_admin_form.questiontype.value;
		 if(questiontype != 6)
	     {
		 document.getElementById("questionDescription___Frame").style.height = "300px";
         }

		 if(questiontype != "4")
			{
				document.question_admin_form.formsize.value = "Low";
				if(questiontype == "6")
				{
					var nb_matches = document.question_admin_form.nb_matches.value;
					var nb_options = document.question_admin_form.nb_options.value;
					for(var i=1;i<=nb_matches;i++)
					{
						document.getElementById("nos["+i+"]").style.height = "50px";
						document.getElementById("answer["+i+"]___Frame").style.height = "40px";
					}

					for(var i=1;i<=nb_options;i++)
					{
						document.getElementById("alpha["+i+"]").style.height = "52px";
						document.getElementById("option["+i+"]___Frame").style.height = "40px";
					}
				}
				else
				{
					var nb_answers = document.question_admin_form.nb_answers.value;

					for(var i=1;i<=nb_answers;i++)
					{
						document.getElementById("answer["+i+"]___Frame").style.height = "40px";
					}
				}
			}
			if(questiontype != 5)
			{
				document.getElementById("comment[1]___Frame").style.height = "40px";
				document.getElementById("comment[2]___Frame").style.height = "40px";
			}
			if(questiontype == 4)
			{
				document.getElementById("answer___Frame").style.height = "250px";
			}
	  }

		function FCKeditor_OnComplete( editorInstance ) {
        // Get the fck instance
        _currentEditor = editorInstance;
        // automated event loaded by each fckeditor area when loaded
        editorInstance.Events.AttachEvent( "OnSelectionChange", takeFocus ) ;
        var oFCKeditor=FCKeditorAPI.GetInstance("questionName") ;
        oFCKeditor.Focus();
        var questiontype = document.question_admin_form.questiontype.value;

		if(questiontype == 4)
	    {
		   if (window.attachEvent) {
			  editorInstance.EditorDocument.attachEvent("onkeyup", updateBlanks) ;
		   } else {
			  editorInstance.EditorDocument.addEventListener("keyup",updateBlanks,true);
		   }
        }
	}

	var _currentEditor;

	function takeFocus(editor){
	_currentEditor = editor
	}

	function makeitbold(){
	_currentEditor.Commands.GetCommand("Bold").Execute();

	}

	function word(){
	_currentEditor.Commands.GetCommand("PasteWord").Execute();

	}
	function link(){
	_currentEditor.Commands.GetCommand("Link").Execute();

	}
	function youtube(){
	_currentEditor.Commands.GetCommand("YouTube").Execute();

	}
	function image(){
	_currentEditor.Commands.GetCommand("Image").Execute();

	}
	function mindmap(){
	_currentEditor.Commands.GetCommand("MindmapManager").Execute();

	}
	function mascot(){
	_currentEditor.Commands.GetCommand("MascotManager").Execute();

	}
	function flash(){
	_currentEditor.Commands.GetCommand("Flash").Execute();

	}
	function embedmovies(){
	_currentEditor.Commands.GetCommand("EmbedMovies").Execute();

	}
	function audio(){
	_currentEditor.Commands.GetCommand("MP3").Execute();

	}
	function table(){
	_currentEditor.Commands.GetCommand("Table").Execute();

	}
	function unordered(){
	_currentEditor.Commands.GetCommand("InsertUnorderedList").Execute();

	}
	function source(){
	_currentEditor.Commands.GetCommand("Source").Execute();

	}
	function alignleft(){
	_currentEditor.Commands.GetCommand("JustifyLeft").Execute();

	}
	function aligncenter(){
	_currentEditor.Commands.GetCommand("JustifyCenter").Execute();

	}
	function alignright(){
	_currentEditor.Commands.GetCommand("JustifyRight").Execute();

	}
        /*
	function flvplayer(){
	_currentEditor.Commands.GetCommand("flvPlayer").Execute();
	}*/
        
        function videoplayer(){
	_currentEditor.Commands.GetCommand("videoPlayer").Execute();
	}


	function imagemap(){
	_currentEditor.Commands.GetCommand("imgmapPopup").Execute();

	}
	function fontcolor(event){
	event = $.event.fix(event);
	_currentEditor.Commands.GetCommand("TextColor").Execute(-120,20,event.target);

	}

	function glossary(){
	_currentEditor.Commands.GetCommand("Glossary").Execute();

	}

	function fontsize() {
	var font_option = document.question_admin_form.font.value;
	//var selection = (_currentEditor.EditorWindow.getSelection ? _currentEditor.EditorWindow.getSelection() : _currentEditor.EditorDocument.selection);
	var selection = "";
	if(_currentEditor.EditorDocument.selection != null) {
	  selection = _currentEditor.EditorDocument.selection.createRange().text;
	}
	else {
	  selection = _currentEditor.EditorWindow.getSelection();
	}
	var new_selection = "<span style=\"font-size:"+font_option+";\">"+selection+"</span>";
	_currentEditor.InsertHtml(new_selection);
	}
				</script>';


   $renderer = $form->defaultRenderer();

   // Main container
   $form->addElement('html', '<div class="form-main-container">');
   $glossary_plugin = '';
   if (api_get_setting('show_glossary_in_documents') == 'ismanual') {
     $glossary_plugin = '<td width="5px;" class="toolbar_style"><img onclick="glossary();" src="'.api_get_path(WEB_LIBRARY_PATH).'fckeditor/editor/plugins/glossary/glossary.gif" alt="'.get_lang('Glossary').'" title="'.get_lang('Glossary').'"></td>';
   }
   if (isset($_GET['answerType']) && $_GET['answerType'] != 6 || isset($_GET['type']) && $_GET['type'] != 6 || isset($_GET['fromTpl'])) {
   $form->addElement('html','<table cellspacing="3" width="100%" height="50px" class="toolbar_style"><tr><td width="80%"><table width="100%"><tr style="height:5px;"><td colspan="3"></td></tr>
   <tr>
   <td width="5px"><img src="../img/toolbar_start.gif"></td>
   <td width="5px" class="toolbar_style">
   <img src="../img/pasteword_icon.png" onclick="word();" alt="'.get_lang('PasteWord').'" title="'.get_lang('PasteWord').'"></td>
   <td width="5px"><img src="../img/toolbar_start.gif"></td>
   <td width="5px;" class="toolbar_style"><img src="../img/link_icon.png" onclick="link();" alt="'.get_lang('Link').'" title="'.get_lang('Link').'"></td>
   <td width="5px"><img src="../img/toolbar_start.gif"></td><td width="5px;" class="toolbar_style">'.Display::return_icon('pixel.gif',get_lang('Images'),array('class'=>'fckactionplaceholdericon fckactionimages_icon','onclick'=>'image();')) .'</td>
   <td width="5px;" class="toolbar_style">'.Display::return_icon('pixel.gif',get_lang('Imagemap'),array('class'=>'fckactionplaceholdericon fckactionimagemap','onclick'=>'imagemap();')).'</td>
   <td width="5px;" class="toolbar_style">'.Display::return_icon('pixel.gif',get_lang('Mindmap'),array('class'=>'fckactionplaceholdericon fckactionmindmap_18','onclick'=>'mindmap();')).'</td>
   <td width="5px;" class="toolbar_style">'.Display::return_icon('pixel.gif',get_lang('Mascot'),array('class'=>'fckactionplaceholdericon fckactionmascot_icon','onclick'=>'mascot();')).'</td>
   <td width="5px;" class="toolbar_style">'.Display::return_icon('pixel.gif',get_lang('Videoplayer'),array('class'=>'fckactionplaceholdericon fckactionvideoPlayer','onclick'=>'videoplayer();')).'</td>
   <td width="5px;" class="toolbar_style">'.Display::return_icon('pixel.gif',get_lang('Audio'),array('class'=>'fckactionplaceholdericon fckactionaudio','onclick'=>'audio();')).'</td>
   '.$glossary_plugin.'
   <td width="5px"><img src="../img/toolbar_start.gif"></td>
   <td width="5px;" class="toolbar_style">'.Display::return_icon('pixel.gif',get_lang('Table'),array('class'=>'fckactionplaceholdericon fckactiontable','onclick'=>'table();')).'</td>
   <td width="5px;" class="toolbar_style"><img src="../img/unordered_list.png" onclick="unordered();" alt="'.get_lang('Orderedlist').'" title="'.get_lang('Orderedlist').'"></td>
   <td width="5px;" class="toolbar_style"><img src="../img/view_source.png" onclick="source();" alt="'.get_lang('Source').'" title="'.get_lang('Source').'"></td>
   <td width="5px"><img src="../img/toolbar_start.gif"></td>
   <td width="5px;" class="toolbar_style"><img src="../img/text_bold.png" onclick="makeitbold();" alt="'.get_lang('Bold').'" title="'.get_lang('Bold').'"></td>
   <td width="5px"><img src="../img/toolbar_start.gif"></td><td width="5px;" class="toolbar_style"><img src="../img/text_left.png" onclick="alignleft();" alt="'.get_lang('Alignleft').'" title="'.get_lang('Alignleft').'"></td>
   <td width="5px;" class="toolbar_style"><img src="../img/text_center.png" onclick="aligncenter();" alt="'.get_lang('Aligncenter').'" title="'.get_lang('Aligncenter').'"></td>
   <td width="5px;" class="toolbar_style">'.Display::return_icon('pixel.gif',get_lang('Textcolor'), array('class' => 'fckactionplaceholdericon fckactionfontcolor', 'onclick' => 'fontcolor(event);')).'</td>
   <td width="5px"><img src="../img/toolbar_start.gif"></td>
   </tr>
   <tr height="5px">
   <td></td></tr>
   </table></td>
   <td>
   <table width="100%">
   <tr><td><span style="color:#333333;">Font:</span><select name="font" onchange="fontsize()"><option></option><option value="smaller" style="font-size: smaller;">smaller</option><option value="larger" style="font-size: larger;">larger</option><option value="xx-small" style="font-size: xx-small;">xx-small</option><option value="x-small" style="font-size: x-small;">x-small</option><option value="small" style="font-size: small;">small</option><option value="medium" style="font-size: medium;">medium</option><option value="large" style="font-size: large;">large</option><option value="x-large" style="font-size: x-large;">x-large</option><option value="xx-large" style="font-size: x-large;">xx-large</option></select></td></tr></table></td></tr></table></td></tr></table><br>');
   }

  if(empty($this->level))
  {
		$questionLevel = '';
  }
  else
  {
		if($this->level == "1")
		{
			$questionLevel = "Prerequestie";
		}
		if($this->level == "2")
		{
			$questionLevel = "Beginner";
		}
		if($this->level == "3")
		{
			$questionLevel = "Intermediate";
		}
		if($this->level == "4")
		{
			$questionLevel = "Advanced";
		}
  }

  if(isset($_POST['formsize']))
  {
	 $formsize = $_POST['formsize'];
  }
  else
  {
	  $formsize = '';
  }

  if(empty($formsize) || $formsize == 'Low')
  {
	  $formsize_px = "40px";
  }
  else
  {
	  $formsize_px = "150px";
  }

  if (isset($_GET['answerType']) && $_GET['answerType'] == 4 || isset($_GET['type']) && $_GET['type'] == 4) {
	 $formsize_px = "90px";
  }

   // Left container
   $form->addElement('html', '<div class="form-left" style="float:left">');

   // question name
   //$form->addElement('text','questionName','<span class="form_required"></span> '.get_lang('Question'),'size="40"');
   $form->addElement('html', '<div class="form-left-left">'.get_lang('Question'));
//   $form->addElement('textarea', 'questionName', '<span class="form_required"></span> ' . get_lang('Question'), 'id="questionName" cols="50" rows="1"');
   $form->add_html_editor('questionName','', false, false, array('ToolbarSet' => 'TestProposedAnswer', 'Width' => '450px', 'Height' => ''.$formsize_px.''));
   $form->addElement('html', '</div>');
   // Question Score
   $score_options = array();
   $score_options = range(0, 20);

   $show_score = true;
   if (isset($_GET['answerType']) && $_GET['answerType'] == 3 || isset($_GET['type']) && $_GET['type'] == 3) {
     $show_score = false;
   }
   if ($show_score === false) {
     $form->addElement('html', '<div class="form-left-right" style="display:none;">');
   } else {
     $form->addElement('html', '<div class="form-left-right">');
   }

   if (isset($_GET['answerType']) && $_GET['answerType'] != 6 || isset($_GET['type']) && $_GET['type'] != 6  || isset($_GET['fromTpl'])) {
   $form->addElement('select', 'scoreQuestions', get_lang('Score'), $score_options);
   }
   $form->addElement('html', '</div>');

   $form->addElement('html', '<div>&nbsp;</div>');
/* $select_level = array(1, 2, 3, 4, 5);
   foreach ($select_level as $val) {
    if ($val == '1') {
     $level = 'Prerequisite';
    }
    if ($val == '2') {
     $level = 'Beginner';
    }
    if ($val == '3') {
     $level = 'Intermediate';
    }
    if ($val == '4') {
     $level = 'Advanced';
    }
    if ($val == '5') {
     $level = 'Expert';
    }
    $radios_results_enabled[] = FormValidator :: createElement('radio', null, null, $level, $val);
   }
   $form->addGroup($radios_results_enabled, 'questionLevel', get_lang('Difficulty'));*/

   $form->addElement('hidden','questionLevel');

   $renderer->setElementTemplate('<div><div class="label">{label}</div><div class="formw" >{element}</div></div>', 'scoreQuestions');
// $renderer->setElementTemplate('<div><div class="label">{label}</div><div class="formw" >{element}</div></div>', 'questionName');
// $renderer->setElementTemplate('<div><div class="label">{label}</div><div class="formw">{element}</div></div>', 'questionLevel');
   $form->addRule('questionName', get_lang('GiveQuestion'), 'required');

/* $form->addElement('html', '<div><table width="100%"><tr><td align="right"><span id="small_icon"> <img src="../img/SmallFormFilled.png" alt="" onclick="smallform()" /></span><span id="big_icon"> <img src="../img/BigFormClosed.png" alt="" onclick="bigform()" /></span></td></tr></table></div>');
   $form -> addElement ('html','<div align="right" id="newform" style="display:none;"></div>');

   $form->addElement('html','<br/><div id="level">Level (Each square represent level.Click on it ) - <input type="text" size="20" name="qnlevel" value="'.$questionLevel.'" disabled /></div><div class="level_style_advanced" onclick="level(\'advanced\');" title="Advanced"></div><div class="level_style_intermediate" onclick="level(\'intermediate\');" title="Intermediate"></div><div class="level_style_beginner" onclick="level(\'beginner\');" title="Beginner"></div><div class="level_style_prerequestie" onclick="level(\'prerequestie\');" title="Prerequestie"></div>');*/

   // question type
   $answerType = intval($_REQUEST['answerType']);
   $form->addElement('hidden', 'answerType', $_REQUEST['answerType']);

   // html editor
   $editor_config = array('ToolbarSet' => 'TestQuestionDescription', 'Width' => '90%', 'Height' => '500');
   if (is_array($fck_config)) {
    $editor_config = array_merge($editor_config, $fck_config);
   }
   if (!api_is_allowed_to_edit())
    $editor_config['UserStatus'] = 'student';

   /* if(($this->type == '1') || ($this->type == '2')  || ($this->type == '4')){
     $form -> addElement('html','<div class="row">
     <div class="label"></div>
     <div class="formw" style="height:50px">
     <a href="javascript://" onclick=" return show_media()"> <span id="media_icon"> <img style="vertical-align: middle;" src="../img/looknfeelna.png" alt="" />&nbsp;'.get_lang('EnrichQuestion').'</span></a>
     </div>
     </div>');

     $form -> addElement ('html','<div id="media" style="display:none;">');
     $form->addElement('html_editor', 'questionDescription',null,'style="vertical-align:middle"',array('ToolbarSet' => 'TestQuestionDescription', 'Width' => '90%', 'Height' => '250'));
     $form -> addElement ('html','</div><br/><br/>');
     } */

   //$renderer->setElementTemplate('<div class="row"><div class="label">{label}</div><div class="formw">{element}</div></div>','questionDescription');
   // hidden values
   $form->addElement('hidden', 'myid', $_REQUEST['myid']);
   $form->addElement('html', '</div>');
   $quizmedia_lang_var = api_convert_encoding(get_lang('QuizMedia'), $charset, api_get_system_encoding());   

   // Setting for "Matching" question type
   if ((isset($_REQUEST['answerType']) && $_REQUEST['answerType'] == 4) || (isset($_GET['type']) && $_GET['type'] == 4)) {
	 $media_img = "deco_matching.png";
    $default_image = '<div style="text-align: center;"><img  src="../img/'.$media_img.'"/></div>';
     // Right container - Movie image
     $form->addElement('html', '<div class="quiz_little_squarebox">');
     $form->add_html_editor('questionDescription',$quizmedia_lang_var, false, false, array('ToolbarSet' => 'TestProposedAnswer', 'Width' => '350px', 'Height' => '90px'));
    
   } elseif ((isset($_REQUEST['answerType']) && $_REQUEST['answerType'] == 6) || (isset($_REQUEST['type']) && $_REQUEST['type'] == 6)) {
   // Right container - Movie image
    $form->addElement('html', '<div class="quiz_little_squarebox" style="width:420px">');
   } else {
   // Right container - Movie image
		if($this->mediaPosition == 'nomedia'){
			$display_css = 'display:none';
			$form->addElement('html', '<div id="rightcontainer"  class="quiz_questions_small_squarebox">');
		}
		else {
			$display_css = 'display:';
			$form->addElement('html', '<div id="rightcontainer"  class="quiz_questions_squarebox">');
		}

		$form->addElement('html','<div id="mediatext" style="'.$display_css.';text-align:left">'.$quizmedia_lang_var.'</div><div id="media" style="'.$display_css.';">');
		$form->add_html_editor('questionDescription','', false, false, array('ToolbarSet' => 'TestProposedAnswer', 'Width' => '100%', 'Height' => '300px'));

		if($_REQUEST['answerType'] == '1')
		{
			$media_img = "instructor-faq.png";
		}
		elseif($_REQUEST['answerType'] == '2')
		{
			$media_img = "instructor-books.jpg";
		}
		elseif($_REQUEST['answerType'] == '8')
		{
			$media_img = "instructor-think.png";
		}
		elseif($_REQUEST['answerType'] == '3')
		{
			$media_img = "KnockOnWood.png";
		}
		elseif($_REQUEST['answerType'] == '5')
		{
			$media_img = "instructor-idea.jpg";
		}
		   $default_image = '<div align="center"><br/><img height="240"  src="../img/'.$media_img.'"/></div>';
   }

   $form->addElement('html','</div>');
  
   echo '<script>
   $(document).ready(function(){';
   if($this->mediaPosition == 'nomedia'){
		echo '$("#leftcontainer").removeClass().addClass("quiz_answer_squarebox");';
   }
   else{
		echo '$("#leftcontainer").removeClass().addClass("quiz_answer_small_squarebox");';
   }
	    echo '$("#mediaposition").change(onSelectChange);
	});
	function onSelectChange(){	
	var selected = $("#mediaposition option:selected");   
	var mediaposition = selected.val();	
	if(mediaposition == "nomedia"){		
		$("#media").hide();
		$("#mediatext").hide();
		$("#rightcontainer").removeClass().addClass("quiz_questions_small_squarebox");
		$("#leftcontainer").removeClass().addClass("quiz_answer_squarebox");
	}
	else {
		$("#media").show();
		$("#mediatext").show();
		$("#rightcontainer").removeClass().addClass("quiz_questions_squarebox");
		$("#leftcontainer").removeClass().addClass("quiz_answer_small_squarebox");
	}
  }
   </script>';

   if ((isset($_REQUEST['answerType']) && $_REQUEST['answerType'] <> 4) || (isset($_GET['type']) && $_GET['type'] <> 4) || isset($_GET['fromTpl'])) {
	   // Select position media
	   $form->addElement('html','<div style="clear:both;"></div><br/>');
	   $form->addElement('html','<div class="form-left">');
	   $form->addElement('html', '<div style="float:left">'.get_lang('PositionBlockMedia'));
	   $form->addElement('select', 'mediaPosition', '', array('top'=>get_lang('TopSide'), 'right'=>get_lang('RightSide'), 'nomedia'=>get_lang('NoMedia')),'id="mediaposition" onChange="javascript:call(this.value)"');
	   $form->addElement('html','</div></div>');
	   
	   $form->addElement('html', '<div>&nbsp;</div>');

	   // Close right container
	   $form->addElement('html','</div>');
   }  
   
   // Close main container
   $form->addElement('html', '</div>');
   if ($this->description != "") {
     $default_image = $this->description;
   }

   // default values
   $defaults = array();
   $defaults['questionName'] = $this->question;
   $defaults['questionLevel'] = $this->level;
   $defaults['scoreQuestions'] = $this->weighting;
   $defaults['mediaPosition']   = $this->mediaPosition;
   $defaults['questionDescription'] = $default_image;
   $form->setDefaults($defaults);
  }

  /**
   * function which process the creation of questions
   * @param FormValidator $form the formvalidator instance
   * @param Exercise $objExercise the Exercise instance
   */
  function processCreation($form, $objExercise) {

   $this->updateTitle($form->getSubmitValue('questionName'));
   $this->updateDescription($form->getSubmitValue('questionDescription'));
   $this->updateLevel($form->getSubmitValue('questionLevel'));
   $this->updateMediaPosition($form->getSubmitValue('mediaPosition')); 
   $this->save($objExercise->id);

   // modify the exercise
   $objExercise->addToList($this->id);
   $objExercise->update_question_positions();
  }

  /**
   * abstract function which creates the form to create / edit the answers of the question
   * @param the formvalidator instance
   */
  abstract function createAnswersForm($form);

  /**
   * abstract function which process the creation of answers
   * @param the formvalidator instance
   */
  abstract function processAnswersCreation($form);

  /**
   * Displays the menu of question types
   */
  /* 	static function display_type_menu ($feedbacktype = 0)
    {
    global $exerciseId;
    // 1. by default we show all the question types
    $question_type_custom_list = self::$questionTypes;

    if (!isset($feedbacktype)) $feedbacktype=0;
    if ($feedbacktype==1) {
    //2. but if it is a feedback DIRECT we only show the UNIQUE_ANSWER type that is currently available
    $question_type_custom_list = array ( UNIQUE_ANSWER => self::$questionTypes[UNIQUE_ANSWER]);
    }
    echo '<ul class="question_menu" style="padding:0px; margin:-2px;">';
    foreach ($question_type_custom_list as $i=>$a_type) {
    // include the class of the type
    include_once($a_type[0]);
    // get the picture of the type and the langvar which describes it
    eval('$img = '.$a_type[1].'::$typePicture;');
    eval('$explanation = get_lang('.$a_type[1].'::$explanationLangVar);');
    echo '<li>';
    echo '<div class="icon_image_content">';
    echo '<a href="admin.php?newQuestion=yes&answerType='.$i.'">'.Display::return_icon($img, $explanation).'</a>';
    echo '<br>';
    echo '<a href="admin.php?newQuestion=yes&answerType='.$i.'">'.$explanation.'</a>';
    echo '</div>';
    echo '</li>';
    }
    echo '<li>';
    echo '<div class="icon_image_content">';
    if ($feedbacktype==1) {
    echo $url = '<a href="question_pool.php?type=1&fromExercise='.$exerciseId.'">';
    } else {
    echo $url = '<a href="question_pool.php?fromExercise='.$exerciseId.'">';
    }
    echo Display::return_icon('database.png', get_lang('GetExistingQuestion'), '');
    echo '</a><br>';
    echo $url;
    echo get_lang('GetExistingQuestion');
    echo '</a>';
    echo '</div></li>';
    echo '</ul>';
    } */

  static function display_type_menu($feedbacktype = 0) {
   global $exerciseId,$charset;
   // 1. by default we show all the question types
   $question_type_custom_list = self::$questionTypes;

   if (!isset($feedbacktype))
    $feedbacktype = 0;
   if ($feedbacktype == 1) {
    //2. but if it is a feedback DIRECT we only show the UNIQUE_ANSWER type that is currently available
    $question_type_custom_list = array(UNIQUE_ANSWER => self::$questionTypes[UNIQUE_ANSWER]);
    $url = 'admin.php?newQuestion=yes&' . api_get_cidreq() . '&fromExercise=' . $exerciseId . '&answerType=';
   } else {
    $url = 'admin.php?newQuestion=yes&' . api_get_cidreq() . '&fromExercise=' . $exerciseId . '&answerType=';
   }
   echo '<div class="actions">';
   echo '<div class="overflow_h" style="margin-left:10px;">';

   $question_list_items = array();
   $question_list_id = array();
   foreach ($question_type_custom_list as $i => $my_question) {
    switch ($my_question[1]) {
     case 'UniqueAnswer':
      $question_list_id['UniqueAnswer'] = $i;
      $question_list_items[0] = $my_question;
      break;
     case 'MultipleAnswer':
      $question_list_id['MultipleAnswer'] = $i;
      $question_list_items[1] = $my_question;
      break;
     case 'FillBlanks':
      $question_list_id['FillBlanks'] = $i;
      $question_list_items[3] = $my_question;
      break;
     case 'Matching':
      $question_list_id['Matching'] = $i;
      $question_list_items[5] = $my_question;
      break;
     case 'FreeAnswer':
      $question_list_id['FreeAnswer'] = $i;
      $question_list_items[4] = $my_question;
      break;
     case 'Reasoning':
      $question_list_id['Reasoning'] = $i;
      $question_list_items[2] = $my_question;
      break;
     case 'HotSpot':
      $question_list_id['HotSpot'] = $i;
      $question_list_items[6] = $my_question;
      break;
     case 'HotspotDelineation':
   //   if(api_get_setting('hotspost_delineation') == "true"){
        $question_list_id['HotspotDelineation'] = $i;
        $question_list_items[7] = $my_question;
    //  }
      break;
      
    }
   }

   for ($j = 0; $j < count($question_list_items); $j++) {
    if (!is_null($question_list_items[$j][0])) {
     include_once $question_list_items[$j][0];

     //eval('$explanation = get_lang(' . $question_list_items[$j][1] . '::$explanationLangVar);');
	 $explanation_lang_var = api_convert_encoding(get_lang($question_list_items[$j][1]), $charset, api_get_system_encoding());

     $k = $question_list_id[$question_list_items[$j][1]];
     if (!isset($_SESSION['fromlp'])) {
		echo '<div class="questionType">';
		echo '<a href="'.$url . $k.'">';
     }
     else
     {
		echo '<a href="'.$url . $k.'">';
     }

     switch ($question_list_items[$j][1]) {
		  case 'UniqueAnswer':			echo Display::return_icon('pixel.gif', $explanation_lang_var, array('class' => 'quiztypeplaceholdericon quiztype_multiple_choice'));		break;
		  case 'MultipleAnswer':		echo Display::return_icon('pixel.gif', $explanation_lang_var, array('class' => 'quiztypeplaceholdericon quiztype_multiple_answer'));		break;
		  case 'FillBlanks':			echo Display::return_icon('pixel.gif', $explanation_lang_var, array('class' => 'quiztypeplaceholdericon quiztype_fill_blanks'));			break;
		  case 'Matching':				echo Display::return_icon('pixel.gif', $explanation_lang_var, array('class' => 'quiztypeplaceholdericon quiztype_matching'));				break;
		  case 'FreeAnswer':			echo Display::return_icon('pixel.gif', $explanation_lang_var, array('class' => 'quiztypeplaceholdericon quiztype_open_question'));		break;
		  case 'Reasoning':				echo Display::return_icon('pixel.gif', $explanation_lang_var, array('class' => 'quiztypeplaceholdericon quiztype_reasoning'));			break;
		  case 'HotSpot':				echo Display::return_icon('pixel.gif', $explanation_lang_var, array('class' => 'quiztypeplaceholdericon quiztype_hotspots'));			break;
		  case 'HotspotDelineation':	echo Display::return_icon('pixel.gif', $explanation_lang_var, array('class' => 'quiztypeplaceholdericon quiztype_contour'));	break;
     }

     echo '<br /><font size="2">' . $explanation_lang_var . '</font></a>';
     echo '</div>';
    }
   }
	$template_lang_var = api_convert_encoding(get_lang('Templates'), $charset, api_get_system_encoding());
	// Add the templates feature
	echo '<div class="questionType">';
		echo '<a href="template_gallery.php?fromExercise=' . Security::remove_XSS($_REQUEST['exerciseId']) . '&' . api_get_cidreq() . '">';
			echo Display::return_icon('pixel.gif', $template_lang_var, array('class' => 'quiztypeplaceholdericon quiztype_templates'));
			echo '<br /><font size="2">' . $template_lang_var . '</font></a>';
		echo '</a>';
	echo'</div>';

   echo '</div>';
   echo '</div>';
  }

  static function get_types_information() {
   return self::$questionTypes;
  }

 }

 endif;
?>
