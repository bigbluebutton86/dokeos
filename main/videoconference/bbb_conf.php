<?php
// This is the security salt that must match the value set in the BigBlueButton server
$salt = "86fa5f53e1d50c6e23c764e36f54fccc";

// This is the URL for the BigBlueButton server 
//Make sure the url ends with /bigbluebutton/
$url = "http://85.214.32.22/bigbluebutton/";

//$coursesurl = parse_url($_SESSION['checkDokeosURL']);
$coursesurl = parse_url(api_get_path(WEB_PATH));
$moderator_password = 'i637_2fnz347bdk2f535b59z#';
$viewer_password = '@8n9nfs@8d_nkh9$';

?>