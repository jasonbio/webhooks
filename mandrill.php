<?php
// webhook to handle Mandrill API call when event is triggered. In this case, it de-activates the email in the SQL table so it isn't triggered a second time
if ($_POST['mandrill_events']) {
	$towrite = @file_get_contents('php://input');
	$dec_url = urldecode($towrite);
	$jsonready = substr($dec_url, 16);
	$data = json_decode($jsonready,true);

	$event = $data['0']['event'];
	$recipent = $data['0']['msg']['email'];
	$sender = $data['0']['msg']['sender'];

	$notificationType = $data['0']['event'];
	$bounceType = $data['0']['event'];
	$problem_email = $data['0']['msg']['email'];
	$from_email = $data['0']['msg']['sender'];

	$host = "localhost";
	$username = "SOME SQL USER";
	$password = "SOME SQL PASSWORD";
	$db_name = "SOME SQL DB";
	mysql_connect($host, $username, $password);
	mysql_select_db($db_name) or die(mysql_error());
	$currentemailstring = mysql_real_escape_string($problem_email);
	$query = "UPDATE `table` SET active = 0 WHERE active = 1 AND email = '$currentemailstring'";

	if ($notificationType == 'hard_bounce') {
		mysql_query($query)or die(mysql_error());
		if (mysql_affected_rows() >= 1) {
			return true;
		}
    }
    else if ($notificationType == 'reject') {
    	mysql_query($query)or die(mysql_error());
		if (mysql_affected_rows() >= 1) {
			return true;
		}
    }
    else if ($notificationType == 'unsub') {
    	mysql_query($query)or die(mysql_error());
		if (mysql_affected_rows() >= 1) {
			return true;
		}
    }
    else if ($notificationType == 'spam') {
    	mysql_query($query)or die(mysql_error());
		if (mysql_affected_rows() >= 1) {
			return true;
		}
    }
}
?>