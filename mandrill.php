<?php
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
	$problem_email = $data['0']['msg']['email']; // email to deal with
	$from_email = $data['0']['msg']['sender'];
}
?>
