<?php

/* For licensing terms, see /dokeos_license.txt */

/**
 * 	Exercise class: This class allows to instantiate an object of type Exercise
 * 	@package dokeos.exercise
 * 	@author Olivier Brouckaert
 * 	@version $Id: exercise.class.php 22046 2009-07-14 01:45:19Z ivantcholakov $
 */
if (!class_exists('Exercise')):

 class Exercise {

  var $id;
  var $exercise;
  var $description;
  var $sound;
  var $type;
  var $random;
  var $active;
  var $timeLimit;
  var $attempts;
  var $feedbacktype;
  var $end_time;
  var $start_time;
  var $questionList;  // array with the list of this exercise's questions
  var $results_disabled;
  var $expired_time;
  var $scenario;


  /**
   * constructor of the class
   *
   * @author - Olivier Brouckaert
   */
  function Exercise($quiz_scenario = NULL) {
   $this->id = 0;
   $this->exercise = '';
   $this->description = '';
   $this->sound = '';
   $this->type = 1;
   $this->random = 0;
   $this->active = 1;
   $this->questionList = array();
   $this->timeLimit = 0;
   $this->end_time = '0000-00-00 00:00:00';
   $this->start_time = '0000-00-00 00:00:00';
   $this->results_disabled = 1;
   $this->expired_time = '0000-00-00 00:00:00';
   $this->scenario = $quiz_scenario;
  }

  /**
   * reads exercise informations from the data base
   *
   * @author - Olivier Brouckaert
   * @param - integer $id - exercise ID
   * @return - boolean - true if exercise exists, otherwise false
   */
  function read($id) {
   global $_course;
   global $_configuration;
   global $questionList;

   $TBL_EXERCICE_QUESTION = Database::get_course_table(TABLE_QUIZ_TEST_QUESTION);
   $TBL_EXERCICES = Database::get_course_table(TABLE_QUIZ_TEST);
   $TBL_QUESTIONS = Database::get_course_table(TABLE_QUIZ_QUESTION);
   $TBL_EXERCICES_SCENARIO = Database::get_course_table(TABLE_QUIZ_SCENARIO);

   // Check if scenario does not exists
   $sql_count = "SELECT count(*) AS count FROM $TBL_EXERCICES_SCENARIO WHERE exercice_id='" . Database::escape_string($id) . "' AND scenario_type='" . $this->scenario . "'";
   $rs = Database::query($sql_count,__FILE__,__LINE__);
   $row = Database::fetch_array($rs);
   // Count the matches
   $count_scenario = $row['count'];
   // If matches is zero then the scenario does not exists and we must create one
   if ($count_scenario == 0) {
      $session_id = api_get_session_id();
      $sql = "SELECT id,title,description,sound,type,random,active, results_disabled, max_attempt,start_time,end_time,feedback_type,expired_time FROM $TBL_EXERCICES WHERE id='" . Database::escape_string($id) . "'";
      $rs = Database::query($sql,__FILE__,__LINE__);
      $rowquiz = Database::fetch_object($rs);
      // Sql for create a new scenario
      $sql_scenario = "INSERT INTO $TBL_EXERCICES_SCENARIO (exercice_id, scenario_type,
      title, description, sound, type, random, active, results_disabled,
      max_attempt, start_time, end_time, feedback_type,
      expired_time, session_id) VALUES('".$rowquiz->id."','".$this->scenario."',
      '".Database::escape_string($rowquiz->title)."','".Database::escape_string($rowquiz->description)."','".$rowquiz->sound."',
      '".$rowquiz->type."','".$rowquiz->random."','".$rowquiz->active."',
      '".$rowquiz->results_disabled."','".$rowquiz->max_attempt."','".$rowquiz->start_time."',
      '".$rowquiz->end_time."','".$rowquiz->feedback_type."','".$rowquiz->expired_time."',
      '".$session_id."')";
      $rs_scenario = Database::query($sql_scenario, __FILE__, __LINE__);
   }

   if (is_numeric($this->scenario)) {
     $sql = "SELECT title,description,sound,type,random,active, results_disabled, max_attempt,start_time,end_time,feedback_type,expired_time FROM $TBL_EXERCICES_SCENARIO WHERE exercice_id='" . Database::escape_string($id) . "' AND scenario_type='" . $this->scenario . "'";
   } else {
     $sql = "SELECT title,description,sound,type,random,active, results_disabled, max_attempt,start_time,end_time,feedback_type,expired_time FROM $TBL_EXERCICES WHERE id='" . Database::escape_string($id) . "'";
   }

   $result = api_sql_query($sql, __FILE__, __LINE__);

   // if the exercise has been found
   $object = Database::fetch_object($result);
   if (is_object($object)) {
    $this->id = $id;
    $this->exercise = $object->title;
    $this->description = $object->description;
    $this->sound = $object->sound;
    $this->type = $object->type;
    $this->random = $object->random;
    $this->active = $object->active;
    $this->results_disabled = $object->results_disabled;
    $this->attempts = $object->max_attempt;
    $this->feedbacktype = $object->feedback_type;
    $this->end_time = $object->end_time;
    $this->start_time = $object->start_time;
    $this->expired_time = $object->expired_time;
    $sql = "SELECT question_id, question_order FROM $TBL_EXERCICE_QUESTION,$TBL_QUESTIONS WHERE question_id=id AND exercice_id='" . Database::escape_string($id) . "' ORDER BY question_order";
    $result = api_sql_query($sql, __FILE__, __LINE__);

    // fills the array with the question ID for this exercise
    // the key of the array is the question position
    while ($object = Database::fetch_object($result)) {
     // makes sure that the question position is unique
     while (isset($this->questionList[$object->question_order])) {
      $object->question_order++;
     }

     $this->questionList[$object->question_order] = $object->question_id;
    }
    //var_dump($this->end_time,$object->start_time);
    if ($this->random > 0) {
     $this->questionList = $this->selectRandomList();
    }
    //overload questions list with recorded questions list
    //load questions only for exercises of type 'one question per page'
    //this is needed only is there is no questions
    //
    if ($this->type == 2 && $_configuration['live_exercise_tracking'] == true && $_SERVER['REQUEST_METHOD'] != 'POST' && defined('QUESTION_LIST_ALREADY_LOGGED')) {
     //if(empty($_SESSION['questionList']))
     $this->questionList = $questionList;
    }
    return true;
   }

   // exercise not found
   return false;
  }

  /**
   * returns the exercise ID
   *
   * @author - Olivier Brouckaert
   * @return - integer - exercise ID
   */
  function selectId() {
   return $this->id;
  }

  /**
   * returns the exercise title
   *
   * @author - Olivier Brouckaert
   * @return - string - exercise title
   */
  function selectTitle() {
   return $this->exercise;
  }

  /**
   * returns the number of attempts setted
   *
   * @return - numeric - exercise attempts
   */
  function selectAttempts() {
   return $this->attempts;
  }

  /** returns the number of FeedbackType  *
   *  0=>Feedback , 1=>DirectFeedback, 2=>NoFeedback
   * @return - numeric - exercise attempts
   */
  function selectFeedbackType() {
   return $this->feedbacktype;
  }

  /**
   * returns the time limit
   */
  function selectTimeLimit() {
   return $this->timeLimit;
  }

  /**
   * returns the exercise description
   *
   * @author - Olivier Brouckaert
   * @return - string - exercise description
   */
  function selectDescription() {
   return $this->description;
  }

  /**
   * returns the exercise sound file
   *
   * @author - Olivier Brouckaert
   * @return - string - exercise description
   */
  function selectSound() {
   return $this->sound;
  }

  /**
   * returns the exercise type
   *
   * @author - Olivier Brouckaert
   * @return - integer - exercise type
   */
  function selectType() {
   return $this->type;
  }

  /**
   * tells if questions are selected randomly, and if so returns the draws
   *
   * @author - Carlos Vargas
   * @return - integer - results disabled exercise
   */
  function selectResultsDisabled() {
   return $this->results_disabled;
  }

  /**
   * tells if questions are selected randomly, and if so returns the draws
   *
   * @author - Olivier Brouckaert
   * @return - integer - 0 if not random, otherwise the draws
   */
  function isRandom() {
   if ($this->random > 0) {
    return true;
   } else {
    return false;
   }
  }

  /**
   * Same as isRandom() but has a name applied to values different than 0 or 1
   */
  function getShuffle() {
   return $this->random;
  }

  /**
   * returns the exercise status (1 = enabled ; 0 = disabled)
   *
   * @author - Olivier Brouckaert
   * @return - boolean - true if enabled, otherwise false
   */
  function selectStatus() {
   return $this->active;
  }

  /**
   * returns the array with the question ID list
   *
   * @author - Olivier Brouckaert
   * @return - array - question ID list
   */
  function selectQuestionList() {
   return $this->questionList;
  }

  /**
   * returns the number of questions in this exercise
   *
   * @author - Olivier Brouckaert
   * @return - integer - number of questions
   */
  function selectNbrQuestions() {
   return sizeof($this->questionList);
  }

  /**
   * selects questions randomly in the question list
   *
   * @author - Olivier Brouckaert
   * @return - array - if the exercise is not set to take questions randomly, returns the question list
   * 					 without randomizing, otherwise, returns the list with questions selected randomly
   */
  function selectRandomList() {
   $nbQuestions = $this->selectNbrQuestions();
   $temp_list = $this->questionList;
   if (count($temp_list) <> 0) {
    shuffle($temp_list);
    return array_combine(range(1, $nbQuestions), $temp_list);
   }


   $nbQuestions = $this->selectNbrQuestions();

   //Not a random exercise, or if there are not at least 2 questions
   if ($this->random == 0 || $nbQuestions < 2) {
    return $this->questionList;
   }

   $randQuestionList = array();
   $alreadyChosen = array();

   for ($i = 0; $i < $this->random; $i++) {
    if ($i < $nbQuestions) {
     do {
      $rand = rand(1, $nbQuestions);
     } while (in_array($rand, $alreadyChosen));

     $alreadyChosen[] = $rand;
     $randQuestionList[$rand] = $this->questionList[$rand];
    }
   }

   return $randQuestionList;
  }

  /**
   * returns 'true' if the question ID is in the question list
   *
   * @author - Olivier Brouckaert
   * @param - integer $questionId - question ID
   * @return - boolean - true if in the list, otherwise false
   */
  function isInList($questionId) {
   if (is_array($this->questionList))
    return in_array($questionId, $this->questionList);
   else
    return false;
  }

  /**
   * changes the exercise title
   *
   * @author - Olivier Brouckaert
   * @param - string $title - exercise title
   */
  function updateTitle($title) {
   $this->exercise = $title;
  }
  /**
   * changes the exercise scenario
   *
   * @author - Isaac flores
   * @param - int $scenario - exercise scenario
   */
  function updateScenario($scenario) {
   $this->scenario = $scenario;
  }
  /**
   * changes the exercise max attempts
   *
   * @param - numeric $attempts - exercise max attempts
   */
  function updateAttempts($attempts) {
   $this->attempts = $attempts;
  }

  /**
   * changes the exercise feedback type
   *
   * @param - numeric $attempts - exercise max attempts
   */
  function updateFeedbackType($feedback_type) {
   $this->feedbacktype = $feedback_type;
  }

  /**
   * changes the exercise description
   *
   * @author - Olivier Brouckaert
   * @param - string $description - exercise description
   */
  function updateDescription($description) {
   $this->description = $description;
  }

  function updateExpiredTime($expired_time) {
   $this->expired_time = $expired_time;
  }

  /**
   * changes the exercise sound file
   *
   * @author - Olivier Brouckaert
   * @param - string $sound - exercise sound file
   * @param - string $delete - ask to delete the file
   */
  function updateSound($sound, $delete) {
   global $audioPath, $documentPath, $_course, $_user;
   $TBL_DOCUMENT = Database::get_course_table(TABLE_DOCUMENT);
   $TBL_ITEM_PROPERTY = Database::get_course_table(TABLE_ITEM_PROPERTY);

   if ($sound['size'] && (strstr($sound['type'], 'audio') || strstr($sound['type'], 'video'))) {
    $this->sound = $sound['name'];

    if (@move_uploaded_file($sound['tmp_name'], $audioPath . '/' . $this->sound)) {
     $query = "SELECT 1 FROM $TBL_DOCUMENT "
             . " WHERE path='" . str_replace($documentPath, '', $audioPath) . '/' . $this->sound . "'";
     $result = api_sql_query($query, __FILE__, __LINE__);

     if (!mysql_num_rows($result)) {
      /* $query="INSERT INTO $TBL_DOCUMENT(path,filetype) VALUES "
        ." ('".str_replace($documentPath,'',$audioPath).'/'.$this->sound."','file')";
        api_sql_query($query,__FILE__,__LINE__); */
      $id = add_document($_course, str_replace($documentPath, '', $audioPath) . '/' . $this->sound, 'file', $sound['size'], $sound['name']);

      //$id = Database::get_last_insert_id();
      //$time = time();
      //$time = date("Y-m-d H:i:s", $time);
      // insert into the item_property table, using default visibility of "visible"
      /* $query = "INSERT INTO $TBL_ITEM_PROPERTY "
        ."(tool, ref, insert_user_id,to_group_id, insert_date, lastedit_date, lastedit_type) "
        ." VALUES "
        ."('".TOOL_DOCUMENT."', $id, $_user['user_id'], 0, '$time', '$time', 'DocumentAdded' )";
        api_sql_query($query,__FILE__,__LINE__); */
      api_item_property_update($_course, TOOL_DOCUMENT, $id, 'DocumentAdded', $_user['user_id']);
      item_property_update_on_folder($_course, str_replace($documentPath, '', $audioPath), $_user['user_id']);
     }
    }
   } elseif ($delete && is_file($audioPath . '/' . $this->sound)) {
    $this->sound = '';
   }
  }

  /**
   * changes the exercise type
   *
   * @author - Olivier Brouckaert
   * @param - integer $type - exercise type
   */
  function updateType($type) {
   $this->type = $type;
  }

  /**
   * sets to 0 if questions are not selected randomly
   * if questions are selected randomly, sets the draws
   *
   * @author - Olivier Brouckaert
   * @param - integer $random - 0 if not random, otherwise the draws
   */
  function setRandom($random) {
   $this->random = $random;
  }

  /**
   * enables the exercise
   *
   * @author - Olivier Brouckaert
   */
  function enable() {
   $this->active = 1;
  }

  /**
   * disables the exercise
   *
   * @author - Olivier Brouckaert
   */
  function disable() {
   $this->active = 0;
  }

  function disable_results() {
   $this->results_disabled = true;
  }

  function enable_results() {
   $this->results_disabled = false;
  }

  function updateResultsDisabled($results_disabled) {
   if ($results_disabled == 1) {
    $this->results_disabled = true;
   } else {
    $this->results_disabled = false;
   }
  }

  /**
   * updates the exercise in the data base
   *
   * @author - Olivier Brouckaert
   */
  function save($type_e='') {
   global $_course, $_user,$charset;
   $TBL_EXERCICES = Database::get_course_table(TABLE_QUIZ_TEST);
   $TBL_QUESTIONS = Database::get_course_table(TABLE_QUIZ_QUESTION);
   $TBL_QUIZ_QUESTION = Database::get_course_table(TABLE_QUIZ_TEST_QUESTION);

   $id = $this->id;
   $exercise = $this->exercise;
   $description = $this->description;
   $sound = $this->sound;
   $type = $this->type;
   $attempts = $this->attempts;
   $feedbacktype = $this->feedbacktype;
   $random = $this->random;
   $active = $this->active;
   $session_id = api_get_session_id();
   $expired_time = $this->expired_time;
   $scenario = $this->scenario;
   if ($feedbacktype == 1) {
    $results_disabled = 1;
   } else {
    $results_disabled = intval($this->results_disabled);
   }
   // exercise already exists
   if ($id) {
    /*
      title='".Database::escape_string(Security::remove_XSS($exercise))."',
      description='".Database::escape_string(Security::remove_XSS(api_html_entity_decode($description),COURSEMANAGERLOWSECURITY))."'";
     */
    $sql = "UPDATE $TBL_EXERCICES SET
						title='" . Database::escape_string($exercise) . "',
						description='" . Database::escape_string($description) . "'";

    if ($type_e != 'simple') {
     $sql .= ", sound='" . Database::escape_string($sound) . "',
						type='" . Database::escape_string($type) . "',
						random='" . Database::escape_string($random) . "',
						active='" . Database::escape_string($active) . "',
						feedback_type='" . Database::escape_string($feedbacktype) . "',
						start_time='".Database::escape_string($this->start_time)."',end_time='".Database::escape_string($this->end_time)."',
						max_attempt='" . Database::escape_string($attempts) . "',
						expired_time='" . Database::escape_string($expired_time) . "'," .
      "results_disabled='" . Database::escape_string($results_disabled) . "'";
    }
    $sql .= " WHERE id='" . Database::escape_string($id) . "'";

    api_sql_query($sql, __FILE__, __LINE__);

    // Update into the item_property table
    api_item_property_update($_course, TOOL_QUIZ, $id, 'QuizUpdated', $_user['user_id']);

    // Update data into quiz scenario table
    $quiz_info = array();
    $quiz_info['quiz_id'] = $id;
    $quiz_info['scenario'] = $scenario;
    $quiz_info['start_time'] = $this->start_time;
    $quiz_info['end_time'] = $this->end_time;
    $quiz_info['title'] = $exercise;
    $quiz_info['description'] = $description;
    $quiz_info['sound'] = $sound;
    $quiz_info['type'] = $type;
    $quiz_info['random'] = $random;
    $quiz_info['active'] = $active;
    $quiz_info['results_disabled'] = $results_disabled;
    $quiz_info['attempts'] = $attempts;
    $quiz_info['feedback'] = $feedbacktype;
    $quiz_info['expired_time'] = $expired_time;

    $quiz_data = (object)$quiz_info;

    // Update scenario
    $this->update_scenario($quiz_data);

     if (api_get_setting('search_enabled') == 'true') {
       $this->search_engine_edit();
     }
   } else {// creates a new exercise
    //add condition by anonymous user

    /* if (!api_is_anonymous()) {
      //is course manager
      $cond1=Database::escape_string($exercise);
      $cond2=Database::escape_string($description);
      } else {
      //is anonymous user
      $cond1=Database::escape_string(Security::remove_XSS($exercise));
      $cond2=Database::escape_string(Security::remove_XSS(api_html_entity_decode($description),COURSEMANAGERLOWSECURITY));
      } */
    $type = '2';
    $feedbacktype = '3';

    // get last position
    $sql = 'SELECT MAX(position)+1 as newPosition FROM ' . $TBL_EXERCICES;
    $rs = api_sql_query($sql, __FILE__, __LINE__);
    $newPosition = Database::result($rs, 0);

    $sql = "INSERT INTO $TBL_EXERCICES (start_time,end_time,title,description,sound,type,random,active, results_disabled, max_attempt,feedback_type,expired_time, position)
					VALUES(
						'$start_time','$end_time',
						'" . Database::escape_string($exercise) . "',
						'" . Database::escape_string($description) . "',
						'" . Database::escape_string($sound) . "',
						'" . Database::escape_string($type) . "',
						'" . Database::escape_string($random) . "',
						'" . Database::escape_string($active) . "',
						'" . Database::escape_string($results_disabled) . "',
						'" . Database::escape_string($attempts) . "',
						'" . Database::escape_string($feedbacktype) . "',
						'" . Database::escape_string($expired_time) . "',
						" . intval($newPosition) . "
						)";
    api_sql_query($sql, __FILE__, __LINE__);
    $this->id = Database::insert_id();

    // insert into the item_property table
    api_item_property_update($_course, TOOL_QUIZ, $this->id, 'QuizAdded', $_user['user_id']);

    // Add data into quiz scenario table
    $quiz_info = array();
    $quiz_info['quiz_id'] = $this->id;
    $quiz_info['start_time'] = $start_time;
    $quiz_info['end_time'] = $end_time;
    $quiz_info['title'] = $exercise;
    $quiz_info['description'] = $description;
    $quiz_info['sound'] = $sound;
    $quiz_info['type'] = $type;
    $quiz_info['random'] = $random;
    $quiz_info['active'] = $active;
    $quiz_info['results_disabled'] = $results_disabled;
    $quiz_info['attempts'] = $attempts;
    $quiz_info['feedback'] = $feedbacktype;
    $quiz_info['expired_time'] = $expired_time;

    // Add scenarios to quiz
    $quiz_data = (object)$quiz_info;
    $this->save_scenario($quiz_data);
    // Add quiz into Learning Path
    $this->save_quiz_into_learning_path($quiz_data);

     if (api_get_setting('search_enabled') == 'true' && extension_loaded('xapian')) {
       $this->search_engine_save();
     }
   }

   // updates the question position
   $this->update_question_positions();
  }

  function update_question_positions() {
   // updates the question position
   $TBL_QUIZ_QUESTION = Database::get_course_table(TABLE_QUIZ_TEST_QUESTION);
   foreach ($this->questionList as $position => $questionId) {
    //$sql="UPDATE $TBL_QUESTIONS SET position='".Database::escape_string($position)."' WHERE id='".Database::escape_string($questionId)."'";
    $sql = "UPDATE $TBL_QUIZ_QUESTION SET question_order='" . Database::escape_string($position) . "' " .
            "WHERE question_id='" . Database::escape_string($questionId) . "' and exercice_id=" . Database::escape_string($this->id) . "";
    api_sql_query($sql, __FILE__, __LINE__);
   }
  }
  /**
   * Update the scenario of quiz
   * @param object
   * @return boolean
   */
  function update_scenario ($quiz) {
    $tbl_quiz_scenario = Database::get_course_table(TABLE_QUIZ_SCENARIO);
    $session_id = api_get_session_id();
     $sql = "UPDATE $tbl_quiz_scenario SET
						title='" . Database::escape_string($quiz->title) . "',
						description='" . Database::escape_string($quiz->description) . "'";
     $sql .= ", sound='" . $quiz->sound . "',
						type='" . $quiz->type . "',
						random='" . $quiz->random . "',
						active='" . $quiz->active . "',
						feedback_type='" . $quiz->feedback . "',
						start_time='$quiz->start_time',end_time='$quiz->end_time',
						max_attempt='" . $quiz->attempts . "',
						expired_time='" . $quiz->expired_time . "'," .
      "results_disabled='" . $quiz->results_disabled . "'";
    $sql .= " WHERE exercice_id='" . $quiz->quiz_id . "' AND scenario_type='" . $quiz->scenario . "' ";

    $rs = Database::query($sql,__FILE__,__LINE__);
  }

  /**
   * moves a question up in the list
   *
   * @author - Olivier Brouckaert
   * @author - Julio Montoya (rewrote the code)
   * @param - integer $id - question ID to move up
   */
  function moveUp($id) {
   // there is a bug with some version of PHP with the key and prev functions
   // the script commented was tested in dev.dokeos.com with no success
   // Instead of using prev and next this was change with arrays.
   /*
     foreach($this->questionList as $position=>$questionId)
     {
     // if question ID found
     if($questionId == $id)
     {
     // position of question in the array
     echo $pos1=$position; //1
     echo "<br>";

     prev($this->questionList);
     prev($this->questionList);

     // position of previous question in the array
     $pos2=key($this->questionList);
     //if the cursor of the array hit the end
     // then we must reset the array to get the previous key

     if($pos2===null)
     {
     end($this->questionList);
     prev($this->questionList);
     $pos2=key($this->questionList);
     }

     // error, can't move question
     if(!$pos2)
     {
     //echo 'cant move!';
     $pos2=key($this->questionList);
     reset($this->questionList);
     }
     $id2=$this->questionList[$pos2];
     // exits foreach()
     break;
     }
     $i++;
     }
    */
   $question_list = array();
   foreach ($this->questionList as $position => $questionId) {
    $question_list[] = $questionId;
   }
   $len = count($question_list);
   $orderlist = array_keys($this->questionList);
   for ($i = 0; $i < $len; $i++) {
    $questionId = $question_list[$i];
    if ($questionId == $id) {
     // position of question in the array
     $pos1 = $orderlist[$i];
     $pos2 = $orderlist[$i - 1];
     if ($pos2 === null) {
      $pos2 = $orderlist[$len - 1];
     }
     // error, can't move question
     if (!$pos2) {
      $pos2 = $orderlist[0];
      $i = 0;
     }
     break;
    }
   }
   // permutes questions in the array
   $temp = $this->questionList[$pos2];
   $this->questionList[$pos2] = $this->questionList[$pos1];
   $this->questionList[$pos1] = $temp;
  }

  /**
   * moves a question down in the list
   *
   * @author - Olivier Brouckaert
   * @param - integer $id - question ID to move down
   */
  function moveDown($id) {
   // there is a bug with some version of PHP with the key and prev functions
   // the script commented was tested in dev.dokeos.com with no success
   // Instead of using prev and next this was change with arrays.

   /*
     foreach($this->questionList as $position=>$questionId)
     {
     // if question ID found
     if($questionId == $id)
     {
     // position of question in the array
     $pos1=$position;

     //next($this->questionList);

     // position of next question in the array
     $pos2=key($this->questionList);

     // error, can't move question
     if(!$pos2)
     {
     //echo 'cant move!';
     return;
     }

     $id2=$this->questionList[$pos2];

     // exits foreach()
     break;
     }
     }
    */

   $question_list = array();
   foreach ($this->questionList as $position => $questionId) {
    $question_list[] = $questionId;
   }
   $len = count($question_list);
   $orderlist = array_keys($this->questionList);

   for ($i = 0; $i < $len; $i++) {
    $questionId = $question_list[$i];
    if ($questionId == $id) {
     $pos1 = $orderlist[$i + 1];
     $pos2 = $orderlist[$i];
     if (!$pos2) {
      //echo 'cant move!';
     }
     break;
    }
   }

   // permutes questions in the array
   $temp = $this->questionList[$pos2];
   $this->questionList[$pos2] = $this->questionList[$pos1];
   $this->questionList[$pos1] = $temp;
  }

  /**
   * adds a question into the question list
   *
   * @author - Olivier Brouckaert
   * @param - integer $questionId - question ID
   * @return - boolean - true if the question has been added, otherwise false
   */
  function addToList($questionId) {
   // checks if the question ID is not in the list
   if (!$this->isInList($questionId)) {
    // selects the max position
    if (!$this->selectNbrQuestions()) {
     $pos = 1;
    } else {
     if (is_array($this->questionList))
      $pos = max(array_keys($this->questionList)) + 1;
    }

    $this->questionList[$pos] = $pos;

    return true;
   }

   return false;
  }

  /**
   * removes a question from the question list
   *
   * @author - Olivier Brouckaert
   * @param - integer $questionId - question ID
   * @return - boolean - true if the question has been removed, otherwise false
   */
  function removeFromList($questionId) {
   // searches the position of the question ID in the list
   $pos = array_search($questionId, $this->questionList);

   // question not found
   if ($pos === false) {
    return false;
   } else {
    // deletes the position from the array containing the wanted question ID
    unset($this->questionList[$pos]);

    return true;
   }
  }

  /**
   * deletes the exercise from the database
   * Notice : leaves the question in the data base
   *
   * @author - Olivier Brouckaert
   */
  function delete() {
   global $_course, $_user;
   $TBL_EXERCICES = Database::get_course_table(TABLE_QUIZ_TEST);

   //select current position to update the global order
   $sql = 'SELECT position FROM ' . $TBL_EXERCICES . ' WHERE id=' . intval($this->id);
   $rs = api_sql_query($sql, __FILE__, __LINE__);
   $position = Database::result($rs, 0);
   $sql = 'UPDATE ' . $TBL_EXERCICES . ' SET position = position -1 WHERE position > ' . intval($position);
   api_sql_query($sql, __FILE__, __LINE__);

   $sql = "UPDATE $TBL_EXERCICES SET active='-1', position=0 WHERE id='" . Database::escape_string($this->id) . "'";
   api_sql_query($sql, __FILE__, __LINE__);
   api_item_property_update($_course, TOOL_QUIZ, $this->id, 'QuizDeleted', $_user['user_id']);

   if (api_get_setting('search_enabled') == 'true' && extension_loaded('xapian')) {
    $this->search_engine_delete();
   }
  }

  /**
   * Creates the form to create / edit an exercise
   * @param FormValidator $form the formvalidator instance (by reference)
   */
  function createForm($form, $type='full') {
   global $id;

   if (empty($type)) {
    $type = 'full';
   }

   // form title
   /* if (!empty($_GET['exerciseId'])) {
     $form_title = get_lang('ModifyExercise');
     } else {
     $form_title = get_lang('NewEx');
     }
     $form->addElement('header', '', $form_title);
     // title
     $form -> addElement('text', 'exerciseTitle', get_lang('ExerciseName'),'class="input_titles"'); */
   //$form->applyFilter('exerciseTitle','html_filter');

   /* if(isset($_REQUEST['modifyExercise'])){
     $form -> addElement('html','<div class="row">
     <div class="label"></div>
     <div class="formw" style="height:50px">
     <a href="javascript://" onclick=" return show_media()"> <span id="media_icon"> <img style="vertical-align: middle;" src="../img/looknfeel.png" alt="" />&nbsp;'.get_lang('ExerciseDescription').'</span></a>
     </div>
     </div>');


     $editor_config = array('ToolbarSet' => 'TestQuestionDescription', 'Width' => '100%', 'Height' => '150');
     if(is_array($type)){
     $editor_config = array_merge($editor_config, $type);
     }

     $form -> addElement ('html','<div id="HiddenFCKexerciseDescription" >');
     $form -> add_html_editor('exerciseDescription', get_lang('langExerciseDescription'), false, false, $editor_config);
     $form -> addElement ('html','</div>');
     } */

   if (isset($_REQUEST['modifyExercise'])) {
    // Random questions
    //$form -> addElement('html','<div id="options" style="display:block">');
    if ($type == 'full') {
     // feedback type
     /* $radios_feedback = array();
       $radios_feedback[] = FormValidator :: createElement ('radio', 'exerciseFeedbackType', null, get_lang('ExerciseAtTheEndOfTheTest'),'0');
       $radios_feedback[] = FormValidator :: createElement ('radio', 'exerciseFeedbackType', null, get_lang('NoFeedback'),'2');
       $radios_feedback[] = FormValidator :: createElement ('radio', 'exerciseFeedbackType', null, get_lang('Direct Feedback'),'3');
       $form -> addGroup($radios_feedback, null, get_lang('FeedbackType'));

       $feedback_option[0]=get_lang('ExerciseAtTheEndOfTheTest');
       $feedback_option[2]=get_lang('NoFeedback');
       $feedback_option[3]=get_lang('Direct Feedback'); */

     //Can't modify a DirectFeedback question
     /* if ($this->selectFeedbackType() != 1 ) {
       //	$form -> addElement('select', 'exerciseFeedbackType',get_lang('FeedbackType'),$feedback_option,'onchange="javascript:feedbackselection()"');
       // test type
       $radios = array();
       $radios[] = FormValidator :: createElement ('radio', 'exerciseType', null, get_lang('QuestionsPerPageOne'),'2');
       $radios[] = FormValidator :: createElement ('radio', 'exerciseType', null, get_lang('QuestionsPerPageAll'),'1');
       $form -> addGroup($radios, null, get_lang('QuestionsPerPage'));
       } else {
       // if is Directfeedback but has not questions we can allow to modify the question type
       if ($this->selectNbrQuestions()== 0) {
       $form -> addElement('select', 'exerciseFeedbackType',get_lang('FeedbackType'),$feedback_option,'onchange="javascript:feedbackselection()"');
       // test type
       $radios = array();
       $radios[] = FormValidator :: createElement ('radio', 'exerciseType', null, get_lang('SimpleExercise'),'1');
       $radios[] = FormValidator :: createElement ('radio', 'exerciseType', null, get_lang('SequentialExercise'),'2');
       $form -> addGroup($radios, null, get_lang('ExerciseType'));
       } else {
       //we force the options to the DirectFeedback exercisetype
       $form -> addElement('hidden', 'exerciseFeedbackType','3');
       $form -> addElement('hidden', 'exerciseType','2');
       }
       } */

     /* $radios_results_disabled = array();
       $radios_results_disabled[] = FormValidator :: createElement ('radio', 'results_disabled', null, get_lang('Yes'),'0');
       $radios_results_disabled[] = FormValidator :: createElement ('radio', 'results_disabled', null, get_lang('No'),'1');
       $form -> addGroup($radios_results_disabled, null, get_lang('ShowResultsToStudents')); */

     /* 	$random = array();
       $option=array();
       $max = ($this->id > 0) ? $this->selectNbrQuestions() : 10 ;
       $option = range(0,$max);
       $option[0]=get_lang('No');

       $random[] = FormValidator :: createElement ('select', 'randomQuestions',null,$option);
       $random[] = FormValidator :: createElement ('static', 'help','help','<span style="font-style: italic;">'.get_lang('RandomQuestionsHelp').'</span>');
       //$random[] = FormValidator :: createElement ('text', 'randomQuestions', null,null,'0');
       $form -> addGroup($random,null,get_lang('RandomQuestions'),'<br />'); */
     /*
       $attempt_option=range(0,10);
       $attempt_option[0]=get_lang('Infinite');

       $form -> addElement('select', 'exerciseAttempts',get_lang('ExerciseAttempts'),$attempt_option);

       $form -> addElement('checkbox', 'enabletimelimit',get_lang('EnableTimeLimits'),null,'onclick = "  return timelimit() "');

       $var= Exercise::selectTimeLimit(); */

     /* $form -> addElement('html','</div>');

       if(($this -> start_time!='0000-00-00 00:00:00')||($this -> end_time!='0000-00-00 00:00:00'))
       $form -> addElement('html','<div id="options2" style="display:block;">');
       else
       $form -> addElement('html','<div id="options2" style="display:block;">'); */

     //$form -> addElement('date', 'start_time', get_lang('ExeStartTime'), array('language'=>'es','format' => 'dMYHi'));
     //$form -> addElement('date', 'end_time', get_lang('ExeEndTime'), array('language'=>'es','format' => 'dMYHi'));
     /* $form->addElement('datepicker', 'start_time', get_lang('ExeStartTime'), array('form_name'=>'exercise_admin'));
       $form->addElement('datepicker', 'end_time', get_lang('ExeEndTime'), array('form_name'=>'exercise_admin')); */

     //$form -> addElement('text', 'exerciseAttempts', get_lang('ExerciseAttempts').' : ',array('size'=>'2'));
     /* $form -> addElement('html','</div>');

       $check_option=$this -> selectType();
       $form -> addElement('html','<div id="divtimecontrol"  style="display:block">');
      */
     //Timer control
     /*
       $time_hours_option = range(0,12);
       $time_minutes_option = range(0,59);
       $form -> addElement('checkbox', 'enabletimercontrol',get_lang('EnableTimerControl'),null,array('onclick' =>'option_time_expired()','id'=>'enabletimercontrol','onload'=>'check_load_time()'));
       $expired_date = (int)$this->selectExpiredTime();
      */
     /* if($this->expired_time != '0') {
       $form -> addElement('html','<div id="timercontrol" style="display:block;">');
       } else {
       $form -> addElement('html','<div id="timercontrol" style="display:block;">');
       }//

       /*$form -> addElement('text', 'enabletimercontroltotalminutes',get_lang('ExerciseTotalDurationInMinutes'),array('style' => 'width : 35px','id' => 'enabletimercontroltotalminutes')); */
     //$form -> addElement('html','</div>');
     //$form -> addElement('text', 'exerciseAttempts', get_lang('ExerciseAttempts').' : ',array('size'=>'2'));

     /* $form -> addElement('html','</div>');  //End advanced setting
       $form -> addElement('html','</div>'); */

     $defaults = array();

     /*if (api_get_setting('search_enabled') === 'true') {
      require_once(api_get_path(LIBRARY_PATH) . 'specific_fields_manager.lib.php');

      $form->addElement('checkbox', 'index_document', '', get_lang('SearchFeatureDoIndexDocument'));
      $form->addElement('html', '<br /><div class="row">');
      $form->addElement('html', '<div class="label">' . get_lang('SearchFeatureDocumentLanguage') . '</div>');
      $form->addElement('html', '<div class="formw">' . api_get_languages_combo() . '</div>');
      $form->addElement('html', '</div><div class="sub-form">');

      $specific_fields = get_specific_field_list();
      foreach ($specific_fields as $specific_field) {
       $form->addElement('text', $specific_field['code'], $specific_field['name']);
       $filter = array('course_code' => "'" . api_get_course_id() . "'", 'field_id' => $specific_field['id'], 'ref_id' => $this->id, 'tool_id' => '\'' . TOOL_QUIZ . '\'');
       $values = get_specific_field_values_list($filter, array('value'));
       if (!empty($values)) {
        $arr_str_values = array();
        foreach ($values as $value) {
         $arr_str_values[] = $value['value'];
        }
        $defaults[$specific_field['code']] = implode(', ', $arr_str_values);
       }
      }
      $form->addElement('html', '</div>');
     }*/
    }
   }
   // submit
   isset($_GET['exerciseId']) ? $text = get_lang('Validate') : $text = get_lang('ProcedToQuestions');

   // form title
   if (!isset($_GET['exerciseId'])) {
    $form_title = get_lang('NewEx');
//	$form->addElement('html', '<div style="padding-left:50px;">');
	$form->addElement('html', '<div style="padding-left:30px;">');
    $form->addElement('header', '', $form_title);
   } elseif (isset($_GET['exerciseId']) && isset($_REQUEST['modifyExercise'])) {
    //---------------------------------- MAX NUMBER ATTEMPTS FOR DOKEOS 2.0 ---------------------------//
    $form->addElement('html', '<div style="display:none;">');
    $attempt_option = range(0, 10);
    $attempt_option[0] = get_lang('Infinite');
    $form->addElement('select', 'exerciseAttempts', get_lang('ExerciseAttempts'), $attempt_option);
    $form->addElement('checkbox', 'enabletimelimit', get_lang('EnableTimeLimits'), null, 'onclick = "  return timelimit() "');
    $var = Exercise::selectTimeLimit();
    $form->addElement('html', '</div>');
    //---------------------------------- TIMER CONTROL FOR DOKEOS 2.0 ---------------------------------//
    $form->addElement('html', '<div>');
    $form->addElement('html', '<div align="center"> ');
    // Div container of image and message for the self evaluation/ Exam
    $form->addElement('html', '<div class="squarebox_white" style="width:875px;height:60px;background:#FFFFFF;"> ');
    
    // Self Evaluation / Exam description
    
    if (!is_null($this->scenario)) {
     $timer_id = 'enabletimercontroltotalminutes'.$this->scenario;
     $form -> addElement ('hidden','scenario',$this->scenario);
     if ($this->scenario == 1) {
      /*$title_scenario = get_lang('QuizSelfEvaluationTitle');
      $body_scenario = get_lang('QuizSelfEvaluationMessage');
      $scenario_image = "../img/self_eval.png";*/
	  $title_scenario = get_lang('QuizScenario');
	  $body_scenario = get_lang('QuizSelfEvaluationMessage');
	  $scenario_image = "../img/dokeos_exam.png";
     } elseif ($this->scenario == 2) {
      $title_scenario = get_lang('QuizExamTitle');
      $body_scenario = get_lang('QuizExamMessage');
      $scenario_image = "../img/dokeos_exam.png";
     }
    }/* else {
      $title_scenario = get_lang('QuizSelfEvaluationTitle');
      $body_scenario = get_lang('QuizSelfEvaluationMessage');
      $scenario_image = "../img/self_eval.png";
      $timer_id = 'enabletimercontroltotalminutes';
    }*/

    // Message
    $form->addElement('html', '<div style="float:left;height:60px;width:700px;text-align:left;padding-top:10px;"><div style="margin-left:5px;"> '.$title_scenario.'</div><div style="margin-left:5px;"> '.$body_scenario.'</div>');
    $form->addElement('html', '</div>');

    // Self evaluation Image
    $form->addElement('html', '<div style="float:right;height:60px;width:100px;">'.Display::return_icon('pixel.gif','',array('class'=>'dokeos_exam')).'');
    $form->addElement('html', '</div>');
    // Clear
    $form->addElement('html', '<div style="clear: both; font-size: 0;"></div>');
    $form->addElement('html', '</div>');
 //   $form->addElement('html', '</div>');
 //   $form->addElement('html', '</div>');
    // Indexing document
     if (api_get_setting('search_enabled') === 'true') {
      require_once(api_get_path(LIBRARY_PATH) . 'specific_fields_manager.lib.php');
      //$form->addElement('checkbox', 'index_document', '', get_lang('SearchFeatureDoIndexDocument'));

      $form->addElement('hidden','index_document', 1);
      $form->addElement('hidden','language', api_get_setting('platformLanguage'));
      $form->addElement('html', '<div class="row">');
      //$form->addElement('html', '<div class="label">' . get_lang('SearchFeatureDocumentLanguage') . '</div>');
      //$form->addElement('html', '<div class="formw">' . api_get_languages_combo() . '</div>');
      //$form->addElement('textarea','search_terms','<br/>'.get_lang('SearchEngineKeywords').': ',array ('rows' => '3', 'cols' => '50'));
      $form->addElement('html', '</div>');
     }
 // $form->addElement('text', 'enabletimercontroltotalminutes', get_lang('Minutes'), array('style' => 'width : 35px', 'id' => $timer_id));
	$timer = array();
	$timer[] = FormValidator :: createElement('text', 'enabletimercontroltotalminutes', get_lang('Minutes'), array('style' => 'width : 35px', 'id' => $timer_id));
    $timer[] = FormValidator :: createElement('static', 'minutes', get_lang('Minutes'));//, '<span>' . get_lang('RandomQuestionsHelp') . '</span>'
    $form->addGroup($timer, null, get_lang('Chronometer'),'&nbsp;&nbsp;'.get_lang('Minutes'));

    //-------------------------- START DATE / END DATE FOR DOKEOS 2.0 ---------------------------------//
    $form->addElement('datepicker', 'start_time', get_lang('ExeStartTime'), array('form_name' => 'exercise_admin'.$this->scenario));
    $form->addElement('datepicker', 'end_time', get_lang('ExeEndTime'), array('form_name' => 'exercise_admin'.$this->scenario));
    //-------------------------- RANDOM CONFIGURATION FOR DOKEOS 2.0 ----------------------------------//
    $random = array();
    $option = array();
    $max = ($this->id > 0) ? $this->selectNbrQuestions() : 10;
    $option = range(0, $max);
    $option[0] = get_lang('No');

    $random[] = FormValidator :: createElement('select', 'randomQuestions', null, $option);
    $random[] = FormValidator :: createElement('static', 'help', 'help');//, '<span>' . get_lang('RandomQuestionsHelp') . '</span>'
    $form->addGroup($random, null, get_lang('RandomQuestions'), '<br />');
    $attempt_option = range(0, 10);
    $attempt_option[0] = get_lang('Infinite');
    $form->addElement('select', 'exerciseAttempts', get_lang('ExerciseAttempts'), $attempt_option);
    //------------------------- FEEDBACK CONFIGURATION FOR DOKEOS 2.0 ---------------------------------//
    // feedback type
    $radios_feedback = array();
	$radios_feedback[] = FormValidator :: createElement('static', '', null, get_lang('FeedbackType'));
    $radios_feedback[] = FormValidator :: createElement('radio', 'exerciseFeedbackType', null, get_lang('ExerciseAtTheEndOfTheTest'), '0');
    $radios_feedback[] = FormValidator :: createElement('radio', 'exerciseFeedbackType', null, get_lang('NoFeedback'), '2');
    $radios_feedback[] = FormValidator :: createElement('radio', 'exerciseFeedbackType', null, get_lang('DirectFeedback'), '3');
    $form->addGroup($radios_feedback, null, '');	

    $feedback_option[0] = get_lang('ExerciseAtTheEndOfTheTest');
    $feedback_option[2] = get_lang('NoFeedback');
    $feedback_option[3] = get_lang('DirectFeedback');

    //------------------------- QUESTION PER PAGE CONFIGURATION FOR DOKEOS 2.0 -------------------------//
    //Can't modify a DirectFeedback question
    if ($this->selectFeedbackType() != 1) {
     //	$form -> addElement('select', 'exerciseFeedbackType',get_lang('FeedbackType'),$feedback_option,'onchange="javascript:feedbackselection()"');
     // test type
     $radios = array();
	 $radios[] = FormValidator :: createElement('static', '', null, get_lang('QuestionsPerPage'));
     $radios[] = FormValidator :: createElement('radio', 'exerciseType', null, get_lang('QuestionsPerPageOne'), '2');
     $radios[] = FormValidator :: createElement('radio', 'exerciseType', null, get_lang('QuestionsPerPageAll'), '1');
     $form->addGroup($radios, null, '');
    } else {
     // if is Directfeedback but has not questions we can allow to modify the question type
     if ($this->selectNbrQuestions() == 0) {
      $form->addElement('select', 'exerciseFeedbackType', get_lang('FeedbackType'), $feedback_option, 'onchange="javascript:feedbackselection()"');
      // test type
      $radios = array();
      $radios[] = FormValidator :: createElement('radio', 'exerciseType', null, get_lang('SimpleExercise'), '1');
      $radios[] = FormValidator :: createElement('radio', 'exerciseType', null, get_lang('SequentialExercise'), '2');
      $form->addGroup($radios, null, get_lang('ExerciseType'));
     } else {
      //we force the options to the DirectFeedback exercisetype
      $form->addElement('hidden', 'exerciseFeedbackType', '3');
      $form->addElement('hidden', 'exerciseType', '2');
     }
    }
    //------------------------- SHOW RESULT OPTION FOR DOKEOS 2.0 -------------------------//
    $radios_results_disabled = array();
	$radios_results_disabled[] = FormValidator :: createElement('static', '', null, get_lang('ShowResultsToStudents'));
    $radios_results_disabled[] = FormValidator :: createElement('radio', 'results_disabled', null, get_lang('Yes'), '0');
    $radios_results_disabled[] = FormValidator :: createElement('radio', 'results_disabled', null, get_lang('No'), '1');
    $form->addGroup($radios_results_disabled, null, '');
   }
   //------------------------- FORM TITLE FOR DOKEOS 2.0 ---------------------------------//
    $form->addElement('text', 'exerciseTitle', get_lang('ExerciseName'), 'class="focus";size="50"');	
	
	if(api_get_setting('show_quizcategory') == 'true')
	{
		$TBL_QUIZ_CATEGORY = Database::get_course_table(TABLE_QUIZ_CATEGORY);
		$TBL_QUIZ_TYPE = Database::get_course_table(TABLE_QUIZ_TYPE);

		$quiz_category = array();
		$quizcat = 'Select';
		$quizcat_id = '0';
		$quiz_level = array('Select','Prerequistie','Beginner','Intermediate','Advanced');		
		$numberofquestion = array('Select','1','2','3','4','5','6','7','8','9','10');
		$quizlevel = "Select,Prerequistie,Beginner,Intermediate,Advanced";
		$quizlevel_id = "0,1,2,3,4";
		$query = "SELECT * FROM $TBL_QUIZ_CATEGORY ORDER BY display_order";
		$result = api_sql_query($query, __FILE__, __LINE__);
		$quiz_category[] = "Select";
		while ($row = Database::fetch_array($result)) {
			$quiz_category[] = $row['category_title'];			
			$quizcat = $quizcat.",".$row['category_title'];
			$quizcat_id = $quizcat_id.",".$row['id'];
		}
		
		if(isset($_GET['exerciseId']))
		{
			$form->addElement('html', '<br/><br/><br/><div class="quiz_content_actions" style="width:60%;float:left;"><div class="quiz_header" align="left">'.get_lang('QuestionCategories').'</div><br/><table align="left" width="100%" border="0"><tr><td align="right"><img src="../img/add_22.png" id="addButton_'.$this->scenario.'">&nbsp;&nbsp;<img src="../img/wrong.png" id="removeButton_'.$this->scenario.'"></td></tr></table>');

			$query = "SELECT * FROM $TBL_QUIZ_TYPE WHERE exercice_id = ".$_GET['exerciseId']." AND scenario_type = ".$this->scenario;
			$result = api_sql_query($query, __FILE__, __LINE__);
			$count_rows = Database::num_rows($result);	
			if($count_rows == 0)
			{
				$count_rows = 1;
				$form->addElement('html', '<table width="100%" border="0"><tr><td width="45%">'.get_lang('Category').'</td><td width="25%">'.get_lang('Level').'</td><td width="30%">'.get_lang('Numberofquestion').'</td></tr><tr><td colspan="3"><div id="TextBoxesGroup_'.$this->scenario.'"><div id="TextBoxDiv1_'.$this->scenario.'">');		
				$form->addElement('html','</div></div></td></tr></table>');		
				$form->addElement('hidden','quizcategory_'.$this->scenario.'',$quizcat);
				$form->addElement('hidden','quizcategory_id_'.$this->scenario.'',$quizcat_id);
				$form->addElement('hidden','quiz_level_'.$this->scenario.'',$quizlevel);
				$form->addElement('hidden','quiz_level_id_'.$this->scenario.'',$quizlevel_id);
				$form->addElement('hidden','counter_'.$this->scenario.'',$count_rows);
			}
			else
			{
				$i = 1;
				$form->addElement('html', '<table width="100%"><tr><td><div id="TextBoxesGroup_'.$this->scenario.'">');
				while($row = Database::fetch_array($result))
				{
					$db_quizlevel = $row['quiz_level'];				
				
				$form->addElement('html','<div id="TextBoxDiv'.$i."_".$this->scenario.'"><table width="100%"><tr><td width="45%">');
				if($i == 1)
				{
				$form->addElement('select', 'quizcategory_'.$i.'_'.$this->scenario, get_lang('Category'),$quiz_category);
				$form->addElement('html','</td><td width="25%">');
				$form->addElement('select', 'quizlevel_'.$i.'_'.$this->scenario, get_lang('Level'),$quiz_level);	
				$form->addElement('html','</td><td width="30%" align="right">');
				$form->addElement('select', 'numberofquestion_'.$i.'_'.$this->scenario, get_lang('Numberofquestion'), $numberofquestion);		
				$form->addElement('html','</td>');
				$form->addElement('html','</tr></table>');			
				}
				else
				{
				$form->addElement('select', 'quizcategory_'.$i.'_'.$this->scenario, '',$quiz_category);
				$form->addElement('html','</td><td width="25%">');
				$form->addElement('select', 'quizlevel_'.$i.'_'.$this->scenario, '',$quiz_level);	
				$form->addElement('html','</td><td width="30%" align="right">');
				$form->addElement('select', 'numberofquestion_'.$i.'_'.$this->scenario, '', $numberofquestion);		
				$form->addElement('html','</td>');
				$form->addElement('html','<td width="15%">&nbsp;</td></tr></table>');
				}	
				$form->addElement('hidden','quizcategory_'.$this->scenario.'',$quizcat);
				$form->addElement('hidden','quizcategory_id_'.$this->scenario.'',$quizcat_id);
				$form->addElement('hidden','quiz_level_'.$this->scenario.'',$quizlevel);
				$form->addElement('hidden','quiz_level_id_'.$this->scenario.'',$quizlevel_id);
				$form->addElement('hidden','counter_'.$this->scenario.'',$count_rows);
				$defaults['quizcategory_'.$i.'_'.$this->scenario] = $row['category_id'];
				$defaults['quizlevel_'.$i.'_'.$this->scenario] = $db_quizlevel;
				$defaults['numberofquestion_'.$i.'_'.$this->scenario] = $row['number_of_question'];
				$i++;
				}
				$form->addElement('html','</div></div></td></tr></table></div>');
			}
		}
		$form->addElement('html','</div>');
	}
   $form->addElement('html', '<br />');
   //------------------------- BUTTON FOR DOKEOS 2.0 --------------------------------------//
   $form->addElement('style_submit_button', 'submitExercise', $text, 'class="save"');
   $form->addElement('html', '</div>');

   $form->addRule('exerciseTitle', get_lang('GiveExerciseName'), 'required');


   if ($type == 'full') {
    // rules
    if (isset($_REQUEST['modifyExercise'])) {
     $form->addRule('exerciseAttempts', get_lang('Numeric'), 'numeric');
     $form->addRule('start_time', get_lang('InvalidDate'), 'date');
     $form->addRule('end_time', get_lang('InvalidDate'), 'date');
     $form->addRule(array('start_time', 'end_time'), get_lang('StartDateShouldBeBeforeEndDate'), 'date_compare', 'lte');
    }
   }

   // defaults
   if ($type == 'full') {
    if (isset($_REQUEST['modifyExercise'])) {
     if ($this->id > 0) {
      if ($this->random > $this->selectNbrQuestions()) {
       $defaults['randomQuestions'] = $this->selectNbrQuestions();
      } else {
       $defaults['randomQuestions'] = $this->random;
      }

      $defaults['exerciseType'] = $this->selectType();
      $defaults['exerciseTitle'] = $this->selectTitle();
      $defaults['exerciseDescription'] = $this->selectDescription();
      $defaults['exerciseAttempts'] = $this->selectAttempts();
      $defaults['exerciseFeedbackType'] = $this->selectFeedbackType();
      $defaults['results_disabled'] = $this->selectResultsDisabled();

      if (($this->start_time != '0000-00-00 00:00:00') || ($this->end_time != '0000-00-00 00:00:00'))
       $defaults['enabletimelimit'] = 1;

      $defaults['start_time'] = ($this->start_time != '0000-00-00 00:00:00') ? $this->start_time : date('Y-m-d 12:00:00');
      $defaults['end_time'] = ($this->end_time != '0000-00-00 00:00:00') ? $this->end_time : date('Y-m-d 12:00:00', time() + 84600);


      if ($this->expired_time > '0') {
       $defaults['enabletimercontroltotalminutes'] = $this->expired_time;
      } else {
       $defaults['enabletimercontroltotalminutes'] = 0;
      }
     } else {
      $defaults['exerciseType'] = 2;
      $defaults['exerciseAttempts'] = 0;
      $defaults['randomQuestions'] = 0;
      $defaults['exerciseDescription'] = '';
      $defaults['exerciseFeedbackType'] = 3;
      $defaults['results_disabled'] = 0;

      $defaults['start_time'] = date('Y-m-d 12:00:00');
      $defaults['end_time'] = date('Y-m-d 12:00:00', time() + 84600);
     }
    } else {
     $defaults['exerciseTitle'] = $this->selectTitle();
     $defaults['exerciseDescription'] = $this->selectDescription();
    }
    if (api_get_setting('search_enabled') === 'true') {
     $defaults['index_document'] = 'checked="checked"';
    }
   }
   $form->setDefaults($defaults);
  }

  /**
   * function which process the creation of exercises
   * @param FormValidator $form the formvalidator instance
   */
  function processCreation($form, $type='') {

   $this->updateTitle($form->getSubmitValue('exerciseTitle'));
   $this->updateDescription($form->getSubmitValue('exerciseDescription'));
   $this->updateAttempts($form->getSubmitValue('exerciseAttempts'));
   $this->updateFeedbackType($form->getSubmitValue('exerciseFeedbackType'));
   $this->updateType($form->getSubmitValue('exerciseType'));
   $this->setRandom($form->getSubmitValue('randomQuestions'));
   $this->updateResultsDisabled($form->getSubmitValue('results_disabled'));
   $this->updateExpiredTime($form->getSubmitValue('enabletimercontroltotalminutes'));
   $this->updateScenario($form->getSubmitValue('scenario'));

   if (true) { // $form->getSubmitValue('enabletimelimit') == 1
    $start_time = $form->getSubmitValue('start_time');
    $this->start_time = $start_time['Y'] . '-' . $start_time['F'] . '-' . $start_time['d'] . ' ' . $start_time['H'] . ':' . $start_time['i'] . ':00';
    $end_time = $form->getSubmitValue('end_time');
    $this->end_time = $end_time['Y'] . '-' . $end_time['F'] . '-' . $end_time['d'] . ' ' . $end_time['H'] . ':' . $end_time['i'] . ':00';
    // Time validation
    $date_start = strtotime($this->start_time);
    $date_end = strtotime($this->end_time);
    if ($date_end < $date_start) {
      $this->start_time = $end_time['Y'] . '-' . $end_time['F'] . '-' . $end_time['d'] . ' ' . $end_time['H'] . ':' . $end_time['i'] . ':00';
    }
   } else {
    $this->start_time = '0000-00-00 00:00:00';
    $this->end_time = '0000-00-00 00:00:00';
   }

   if (true) { // $form->getSubmitValue('enabletimercontrol') == 1)
    $expired_total_time = $form->getSubmitValue('enabletimercontroltotalminutes');
    if ($this->expired_time == 0) {
     $this->expired_time = $expired_total_time;
    }
   } else {
    $this->expired_time = 0;
   }
   //echo $end_time;exit;
   $id = $this->save($type);
   $TBL_QUIZ_TYPE = Database::get_course_table(TABLE_QUIZ_TYPE);
   if ($form -> getSubmitValue('edit') == 'true') {
	   $id = Security::remove_XSS($_GET['exerciseId']);
	   $sql = "DELETE FROM $TBL_QUIZ_TYPE WHERE exercice_id = ".Database::escape_string($id)." AND scenario_type = ".$this->scenario;
	   api_sql_query($sql, __FILE__, __LINE__);

	   $sql = "UPDATE $TBL_QUIZ_TYPE SET current_active = 0 WHERE exercice_id = ".Database::escape_string($id)." AND scenario_type <> ".$this->scenario;
	   api_sql_query($sql, __FILE__, __LINE__);
   }
   $counter = $form -> getSubmitValue('counter_'.$this->scenario); 
   
   for($i=1;$i<=$counter;$i++)
   {	   
	   $quiz_level = $form -> getSubmitValue('quizlevel_'.$i.'_'.$this->scenario);
		
	   $sql = "INSERT INTO $TBL_QUIZ_TYPE (exercice_id,category_id,quiz_level,number_of_question,scenario_type,current_active,session_id) VALUES(
				".Database::escape_string($id).",".Database::escape_string($form -> getSubmitValue('quizcategory_'.$i.'_'.$this->scenario)).",'"
				.Database::escape_string($quiz_level)."',".Database::escape_string($form -> getSubmitValue('numberofquestion_'.$i.'_'.$this->scenario))
				.",".$this->scenario.", 1, ".api_get_session_id().")";
		
	   api_sql_query($sql, __FILE__, __LINE__);
   }
  }

  function search_engine_save() {
   $search_db_path = api_get_path(SYS_PATH).'searchdb';
   if (is_writable($search_db_path)) {
   $course_id = api_get_course_id();

   require_once(api_get_path(LIBRARY_PATH) . 'search/DokeosIndexer.class.php');
   require_once(api_get_path(LIBRARY_PATH) . 'search/IndexableChunk.class.php');
   require_once(api_get_path(LIBRARY_PATH) . 'specific_fields_manager.lib.php');

   //$specific_fields = get_specific_field_list();
   $ic_slide = new IndexableChunk();

   /*$all_specific_terms = '';
   foreach ($specific_fields as $specific_field) {
    if (isset($_REQUEST[$specific_field['code']])) {
     $sterms = trim($_REQUEST[$specific_field['code']]);
     if (!empty($sterms)) {
      $all_specific_terms .= ' ' . $sterms;
      $sterms = explode(',', $sterms);
      foreach ($sterms as $sterm) {
       $ic_slide->addTerm(trim($sterm), $specific_field['code']);
       add_specific_field_value($specific_field['id'], $course_id, TOOL_QUIZ, $this->id, $sterm);
      }
     }
    }
   }*/

   // build the chunk to index
   $ic_slide->addValue("title", $this->exercise);
   $ic_slide->addCourseId($course_id);
   $ic_slide->addToolId(TOOL_QUIZ);
   $xapian_data = array(
       SE_COURSE_ID => $course_id,
       SE_TOOL_ID => TOOL_QUIZ,
       SE_DATA => array('type' => SE_DOCTYPE_EXERCISE_EXERCISE, 'exercise_id' => (int) $this->id),
       SE_USER => (int) api_get_user_id(),
   );
   $ic_slide->xapian_data = serialize($xapian_data);
   $exercise_description = !empty($this->description) ? $this->description : $this->exercise;

   if (isset($_POST['search_terms'])) {
    $add_extra_terms = Security::remove_XSS($_POST['search_terms']).' ';
    }

    $file_content = $add_extra_terms.$exercise_description;
    $ic_slide->addValue("content", $file_content);

   //$ic_slide->addValue("content", $exercise_description);

   $di = new DokeosIndexer();
   isset($_POST['language']) ? $lang = Database::escape_string($_POST['language']) : $lang = 'english';
   $di->connectDb(NULL, NULL, $lang);
   $di->addChunk($ic_slide);

   //index and return search engine document id
   $did = $di->index();
   if ($did) {
    // save it to db
    $tbl_se_ref = Database::get_main_table(TABLE_MAIN_SEARCH_ENGINE_REF);
    $sql = 'INSERT INTO %s (id, course_code, tool_id, ref_id_high_level, search_did)
			    VALUES (NULL , \'%s\', \'%s\', %s, %s)';
    $sql = sprintf($sql, $tbl_se_ref, $course_id, TOOL_QUIZ, $this->id, $did);
    api_sql_query($sql, __FILE__, __LINE__);
   }
   } else {
       return false;
   }
  }

  function search_engine_edit() {
   // update search enchine and its values table if enabled + check if database has write permissions
    $search_db_path = api_get_path(SYS_PATH).'searchdb';
   if (api_get_setting('search_enabled') == 'true' && extension_loaded('xapian') && is_writable($search_db_path)) {
    $course_id = api_get_course_id();

    // actually, it consists on delete terms from db, insert new ones, create a new search engine document, and remove the old one
    // get search_did
    $tbl_se_ref = Database::get_main_table(TABLE_MAIN_SEARCH_ENGINE_REF);
    $sql = 'SELECT * FROM %s WHERE course_code=\'%s\' AND tool_id=\'%s\' AND ref_id_high_level=%s LIMIT 1';
    $sql = sprintf($sql, $tbl_se_ref, $course_id, TOOL_QUIZ, $this->id);
    $res = api_sql_query($sql, __FILE__, __LINE__);

    if (Database::num_rows($res) > 0) {
     require_once(api_get_path(LIBRARY_PATH) . 'search/DokeosIndexer.class.php');
     require_once(api_get_path(LIBRARY_PATH) . 'search/IndexableChunk.class.php');
     require_once(api_get_path(LIBRARY_PATH) . 'specific_fields_manager.lib.php');

     $se_ref = Database::fetch_array($res);
     //$specific_fields = get_specific_field_list();
     $ic_slide = new IndexableChunk();

     /*$all_specific_terms = '';
     foreach ($specific_fields as $specific_field) {
      delete_all_specific_field_value($course_id, $specific_field['id'], TOOL_QUIZ, $this->id);
      if (isset($_REQUEST[$specific_field['code']])) {
       $sterms = trim($_REQUEST[$specific_field['code']]);
       $all_specific_terms .= ' ' . $sterms;
       $sterms = explode(',', $sterms);
       foreach ($sterms as $sterm) {
        $ic_slide->addTerm(trim($sterm), $specific_field['code']);
        add_specific_field_value($specific_field['id'], $course_id, TOOL_QUIZ, $this->id, $sterm);
       }
      }
     }*/

     // build the chunk to index
     $ic_slide->addValue("title", $this->exercise);
     $ic_slide->addCourseId($course_id);
     $ic_slide->addToolId(TOOL_QUIZ);
     $xapian_data = array(
         SE_COURSE_ID => $course_id,
         SE_TOOL_ID => TOOL_QUIZ,
         SE_DATA => array('type' => SE_DOCTYPE_EXERCISE_EXERCISE, 'exercise_id' => (int) $this->id),
         SE_USER => (int) api_get_user_id(),
     );
     $ic_slide->xapian_data = serialize($xapian_data);
     $exercise_description = !empty($this->description) ? $this->description : $this->exercise;

     if (isset($_POST['search_terms'])) {
       $add_extra_terms = Security::remove_XSS($_POST['search_terms']).' ';
     }

     $file_content = $add_extra_terms.$exercise_description;
     $ic_slide->addValue("content", $file_content);


     //$ic_slide->addValue("content", $exercise_description);

     $di = new DokeosIndexer();
     isset($_POST['language']) ? $lang = Database::escape_string($_POST['language']) : $lang = 'english';
     $di->connectDb(NULL, NULL, $lang);
     $di->remove_document((int) $se_ref['search_did']);
     $di->addChunk($ic_slide);

     //index and return search engine document id
     $did = $di->index();
     if ($did) {
      // save it to db
      $sql = 'DELETE FROM %s WHERE course_code=\'%s\' AND tool_id=\'%s\' AND ref_id_high_level=\'%s\'';
      $sql = sprintf($sql, $tbl_se_ref, $course_id, TOOL_QUIZ, $this->id);
      api_sql_query($sql, __FILE__, __LINE__);
      
      $sql = 'INSERT INTO %s (id, course_code, tool_id, ref_id_high_level, search_did)
                        VALUES (NULL , \'%s\', \'%s\', %s, %s)';
      $sql = sprintf($sql, $tbl_se_ref, $course_id, TOOL_QUIZ, $this->id, $did);
      api_sql_query($sql, __FILE__, __LINE__);
     }
    }
   } else {
       if (!is_writable($search_db_path)) {
           return false;
       }
   }
  }

  function search_engine_delete() {
   // remove from search engine if enabled
   if (api_get_setting('search_enabled') == 'true' && extension_loaded('xapian')) {
    $course_id = api_get_course_id();
    $tbl_se_ref = Database::get_main_table(TABLE_MAIN_SEARCH_ENGINE_REF);
    $sql = 'SELECT * FROM %s WHERE course_code=\'%s\' AND tool_id=\'%s\' AND ref_id_high_level=%s AND ref_id_second_level IS NULL LIMIT 1';
    $sql = sprintf($sql, $tbl_se_ref, $course_id, TOOL_QUIZ, $this->id);
    $res = api_sql_query($sql, __FILE__, __LINE__);
    if (Database::num_rows($res) > 0) {
     $row = Database::fetch_array($res);
     require_once(api_get_path(LIBRARY_PATH) . 'search/DokeosIndexer.class.php');
     $di = new DokeosIndexer();
     $di->remove_document((int) $row['search_did']);
     unset($di);
     $tbl_quiz_question = Database::get_course_table(TABLE_QUIZ_QUESTION);
     foreach ($this->questionList as $question_i) {
      $sql = 'SELECT type FROM %s WHERE id=%s';
      $sql = sprintf($sql, $tbl_quiz_question, $question_i);
      $qres = api_sql_query($sql, __FILE__, __LINE__);
      if (Database::num_rows($qres) > 0) {
       $qrow = Database::fetch_array($qres);
       $objQuestion = Question::getInstance($qrow['type']);
       $objQuestion = Question::read((int) $question_i);
       $objQuestion->search_engine_edit($this->id, FALSE, TRUE);
       unset($objQuestion);
      }
     }
    }
    $sql = 'DELETE FROM %s WHERE course_code=\'%s\' AND tool_id=\'%s\' AND ref_id_high_level=%s AND ref_id_second_level IS NULL LIMIT 1';
    $sql = sprintf($sql, $tbl_se_ref, $course_id, TOOL_QUIZ, $this->id);
    api_sql_query($sql, __FILE__, __LINE__);

    // remove terms from db
    require_once(api_get_path(LIBRARY_PATH) . 'specific_fields_manager.lib.php');
    delete_all_values_for_item($course_id, TOOL_QUIZ, $this->id);
   }
  }

  function selectExpiredTime() {
   return $this->expired_time;
  }

  /**
   * Return the nnumber of students who answered the quiz
   * @return integer the number of attempts
   */
  function tracking_select_nb_attempts() {

   $preparedSql = 'SELECT COUNT(exe_id)
						FROM ' . Database::get_statistic_table(TABLE_STATISTIC_TRACK_E_EXERCICES) . '
						WHERE exe_cours_id LIKE "%s"
						AND exe_exo_id = %d';
   $sql = sprintf($preparedSql, api_get_course_id(), $this->id);
   $rs = Database::query($sql);
   return mysql_result($rs, 0, 0);
  }

  function getTrackingScore($type) {

   $preparedSql = 'SELECT exe_weighting, ' . $type . '(exe_result) as score
						FROM ' . Database::get_statistic_table(TABLE_STATISTIC_TRACK_E_EXERCICES) . '
						WHERE exe_cours_id LIKE "%s"
						AND exe_exo_id = %d';
   $sql = sprintf($preparedSql, api_get_course_id(), $this->id);
   $rs = Database::query($sql);
   if (Database::num_rows($rs) > 0 && mysql_result($rs, 0, 'exe_weighting') != null)
    return mysql_result($rs, 0, 'score') / mysql_result($rs, 0, 'exe_weighting') * 100;
   else
    return 0;
  }
  /**
   * Save the scenarios when a quiz is added
   * @author Isaac flores paz <florespaz_isaac@hotmail.com>
   * @param object The quiz data
   * @return boolean true if succesful, false on error
   */
  function save_scenario ($quiz_data) {
    $tbl_quiz_scenario = Database::get_course_table(TABLE_QUIZ_SCENARIO);
    $session_id = api_get_session_id();
    // there are 2 types of scenarios
/*  $scenario_types = array(1, 2);
    foreach ($scenario_types as $scenario) {
    $rs = Database::query("INSERT INTO $tbl_quiz_scenario (exercice_id, scenario_type,
    title, description, sound, type, random, active, results_disabled,
    max_attempt, start_time, end_time, feedback_type,
    expired_time, session_id) VALUES('".$quiz_data->quiz_id."','".$scenario."',
    '".$quiz_data->title."','".$quiz_data->description."','".$quiz_data->sound."',
    '".$quiz_data->type."','".$quiz_data->random."','".$quiz_data->active."',
    '".$quiz_data->results_disabled."','".$quiz_data->attempts."','".$quiz_data->start_time."',
    '".$quiz_data->end_time."','".$quiz_data->feedback."','".$quiz_data->expired_time."',
    '".$session_id."')", __FILE__, __LINE__);
    }*/
	$rs = Database::query("INSERT INTO $tbl_quiz_scenario (exercice_id, scenario_type,
    title, description, sound, type, random, active, results_disabled,
    max_attempt, start_time, end_time, feedback_type,
    expired_time, session_id) VALUES('".$quiz_data->quiz_id."','1',
    '".$quiz_data->title."','".$quiz_data->description."','".$quiz_data->sound."',
    '".$quiz_data->type."','".$quiz_data->random."','".$quiz_data->active."',
    '".$quiz_data->results_disabled."','".$quiz_data->attempts."','".$quiz_data->start_time."',
    '".$quiz_data->end_time."','".$quiz_data->feedback."','".$quiz_data->expired_time."',
    '".$session_id."')", __FILE__, __LINE__);
    if ($rs !== FALSE) {
      return true;
    } else {
      return false;
    }
  }
  /**
   * Save the quiz into Learnin path, The quiz is added as Learning path item
   * @param object The quiz data(title,id)
   */
  function save_quiz_into_learning_path ($quiz_data) {
    $parent = 0;
    $title = $quiz_data->title;
    $docid = $quiz_data->quiz_id;
    if (isset($_GET['lp_id']) && $_GET['lp_id'] > 0 && isset($_SESSION['oLP']) && $docid > 0) {
      // Get the previous item ID
      $previous = $_SESSION['oLP']->select_previous_item_id();
      // Add quiz as Lp Item
      $_SESSION['oLP']->add_item($parent, $previous, TOOL_QUIZ, $docid, $title, '');
    }
  }
  /**
   * Create a quiz of an uploaded file
   * @param object $data
   */
  function create_quiz_from_an_attached_file ($title, $expired_time = 0, $type = 2, $random = 0, $active = 1, $results = 0, $max_attempt = 0, $feedback = 3) {
    $tbl_quiz = Database::get_course_table(TABLE_QUIZ_TEST);
    $start = '';
    $end = '';
    $max_position = 0;

    // Get the max position
    $sql = "SELECT max(position) as max_position FROM $tbl_quiz WHERE session_id = '".api_get_session_id()."'";
    $rs = Database::query($sql, __FILE__, __LINE__);
    $row_max = Database::fetch_object($rs);
    $max_position = $row_max->max_position + 1;
    // Save a new quiz
    $sql = "INSERT INTO $tbl_quiz (title,type,random,active,results_disabled,
     max_attempt,start_time,end_time,feedback_type,expired_time, position,
     session_id) VALUES('".Database::escape_string($title)."','".Database::escape_string($type)."','".Database::escape_string($random)."','".Database::escape_string($active)."','".Database::escape_string($results)."',
     '".Database::escape_string($max_attempt)."','".$start."','".$end."','".$feedback."','".$expired_time."','".$max_position."','".api_get_session_id()."')";
    $rs = Database::query($sql, __FILE__, __LINE__);
    $quiz_id = Database::get_last_insert_id();
    return $quiz_id;
  }

}

 endif;
?>
