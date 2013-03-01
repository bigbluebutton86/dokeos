<?php

/**
 * @todo get_error_message wordpress/wp-db.php 
 */

require '../inc/lib/main_api.lib.php';
require '../lang/english/trad4all.inc.php';
require '../lang/english/install.inc.php';

$link = @mysql_connect($_POST['database_host'], $_POST['database_user'], $_POST['database_pass'], true);

if(!$link)
{
	echo '
			<div style="float:left;" class="quiz_content_actions">
				<div style="float:left;">
				<strong>MySQL error: '.mysql_errno().'</strong><br />
				'.mysql_error().'<br/>
				<strong>'.get_lang('Details').': '. get_lang('FailedConectionDatabase').'</strong><br />
				'.get_lang('IfStillTypingPleaseContinue').'
				</div>
			</div>';
	exit;
}

// This is used for install dokeos on private servers
if (isset($_POST['database_mode']) && $_POST['database_mode'] == 2) {

if(!@mysql_query('CREATE DATABASE '.addslashes($_POST['database_prefix']).'dokeos_database_connection_test', $link))
{
	echo '
			<div style="float:left;" class="quiz_content_actions">
				<div style="float:left;">
				<strong>MySQL error: '.mysql_errno().'</strong><br />
				'.mysql_error().'<br/>
				<strong>'.get_lang('Details').': '. get_lang('FailedConectionDatabase').'</strong><br />
				</div>
			</div>';
	exit;
}

@mysql_query('DROP DATABASE '.addslashes($_POST['database_prefix']).'dokeos_database_connection_test', $link);
echo '
		<div class="warning-message-install">
			<strong>'.get_lang('MysqlConnectionOk').'</strong><br />
			MySQL host info: '.mysql_get_host_info().'<br />
			MySQL server version: '.mysql_get_server_info().'<br />
			MySQL protocol version: '.mysql_get_proto_info().'
			<div style="clear:both;"></div>
		</div>
';
} else if (isset($_POST['database_mode']) && $_POST['database_mode'] == 1) { // This is used ofr setup DOKEOS LMS on shared hosting
  $main_database = $_POST['main_database'];
  $db_selected = mysql_select_db($main_database, $link);
  if ($db_selected) {
    echo '
            <div class="warning-message-install">
                <strong>'.get_lang('MysqlConnectionOk').'</strong><br />
                MySQL host info: '.mysql_get_host_info().'<br />
                MySQL server version: '.mysql_get_server_info().'<br />
                MySQL protocol version: '.mysql_get_proto_info().'
                <div style="clear:both;"></div>
            </div>';  
  } else {
	echo '
			<div style="float:left;" class="quiz_content_actions">
				<div style="float:left;">
				<strong>MySQL error: '.mysql_errno().'</strong><br />
				'.mysql_error().'<br/>
				<strong>'.get_lang('Details').': '. get_lang('FailedConectionDatabase').'</strong><br />
				</div>
			</div>';
	exit;
      
  }

}
?>
