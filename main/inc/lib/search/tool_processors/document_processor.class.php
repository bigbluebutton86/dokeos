<?php
include_once dirname(__FILE__) . '/../../../global.inc.php';
require_once dirname(__FILE__) . '/search_processor.class.php';

/**
 * Process documents before pass it to search listing scripts
 */
class document_processor extends search_processor {
    
    /**
     * check if multisite is enable
     * @global configuration $_configuration
     * @param string $courseid
     * @return bool 
     */
    function is_enabled_multisite($courseid){
        //check if multisite is enable
        global $_configuration;
        if($_configuration['multiple_access_urls']){
            require_once api_get_path(LIBRARY_PATH).'urlmanager.lib.php';
            $url = new UrlManager();
            $access_url_id = api_get_current_access_url_id();
            $exist_relation = $url->relation_url_course_exist($courseid, $access_url_id);
            return $exist_relation > 0 ? true : false;
        }
        return true;;
    }
    
    function document_processor($rows) {
        foreach ($rows as $row_id => $row_val) {
            $courseid = $row_val['courseid'];
            $exist_relation = $this->is_enabled_multisite($courseid);
            if($exist_relation) {
                $this->rows[] = $row_val;
            }
        }
    }

    public function process() {
        $results = array();
        foreach ($this->rows as $row_val) {
          $search_show_unlinked_results = (api_get_setting('search_show_unlinked_results') == 'true');
          $course_visible_for_user = api_is_course_visible_for_user(NULL, $row_val['courseid']);
          // can view course?
          if ($course_visible_for_user || $search_show_unlinked_results) {
            // is visible?
            $visibility = api_get_item_visibility(api_get_course_info($row_val['courseid']), TOOL_DOCUMENT, $row_val['xapian_data'][SE_DATA]['doc_id']);
            if ($visibility) {
                  list($thumbnail, $image, $name, $author, $url) = $this->get_information($row_val['courseid'], $row_val['xapian_data'][SE_DATA]['doc_id']);
                  $result = array(
                      'toolid' => TOOL_DOCUMENT,
                      'courseid' => $row_val['courseid'],
                      'score' => $row_val['score'],
                      'url' => $url,
                      'thumbnail' => $thumbnail,
                      'image' => $image,
                      'title' => $name,
                      'author' => $author,
                  );
                  if ($course_visible_for_user) {
                      $results[] = $result;
                  } else { // course not visible for user
                      if ($search_show_unlinked_results) {
                          $result['url'] = '';
                          $results[] = $result;
                      }
                  }
            }
          }
        }

        // get information to sort
        foreach ($results as $key => $row) {
          $score[$key]  = $row['score'];
        }

        // Sort results with score descending
        array_multisort($score, SORT_DESC, $results);

        return $results;
    }

    /**
     * Get document information
     */
    private function get_information($course_id, $doc_id) {
        $doc_table = Database::get_course_table_from_code($course_id, TABLE_DOCUMENT);
        $item_property_table = Database::get_course_table_from_code($course_id, TABLE_ITEM_PROPERTY);
		$doc_id = Database::escape_string($doc_id);
        $sql = "SELECT *
          FROM       $doc_table
          WHERE      $doc_table.id = $doc_id
          LIMIT 1";
        $dk_result = Database::query ($sql);

        $sql = "SELECT insert_user_id
          FROM       $item_property_table
          WHERE      ref = $doc_id
                     AND tool = '". TOOL_DOCUMENT ."'
          LIMIT 1";

        $name = '';
        if ($row = Database::fetch_array ($dk_result)) {
            $name = $row['title'];
            $url = api_get_path(WEB_PATH) . 'courses/%s/document%s';
            $url = sprintf($url, api_get_course_path($course_id), $row['path']);
            // Get the image path
            include_once api_get_path(LIBRARY_PATH). 'fileDisplay.lib.php';
            $icon = choose_image(basename($row['path']));
            $thumbnail = api_get_path(WEB_CODE_PATH) .'img/'. $icon;
            $image = $thumbnail;
            //FIXME: use big images
            // get author
            $author = '';
            $item_result = Database::query ($sql);
            if ($row = Database::fetch_array ($item_result)) {
                $user_data = api_get_user_info($row['insert_user_id']);
                $author = api_get_person_name($user_data['firstName'], $user_data['lastName']);
            }
        }

        return array($thumbnail, $image, $name, $author, $url); // FIXME: is it posible to get an author here?
    }
}
?>
