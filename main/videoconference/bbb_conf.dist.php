<?php
// This is the security salt that must match the value set in the BigBlueButton server
$salt = "SALT";

// This is the URL for the BigBlueButton server 
//Make sure the url ends with /bigbluebutton/
$url = "http://HOST/bigbluebutton/";

//$coursesurl = parse_url($_SESSION['checkDokeosURL']);
$coursesurl = parse_url(api_get_path(WEB_PATH));
$moderator_password = 'i637_2fnz347bdk2f535b59z#';
$viewer_password = '@8n9nfs@8d_nkh9$';

?>