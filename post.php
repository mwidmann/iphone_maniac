<?php
    require "settings.php";
    require "weblib.php";
    
    // thread stuff
    $mode = "messagesave";
    $brdid = $_REQUEST['brdid'];
    $msgid = $_REQUEST['msgid'];
    
    // user stuff
	$nick = ($_REQUEST['nick']);
	$pass = ($_REQUEST['pass']);
	
	// message stuff
	$subject = stripslashes(($_REQUEST['subject']));
	$body = stripslashes(($_REQUEST['body']));
	
	
	
	// the post comes here
	
	$response = PostRequest(POST_URL, '', array(
			"mode" => $mode,
			"brdid" => $brdid,
			"msgid" => $msgid,
			"nick" => $nick,
			"pass" => $pass,
			"subject" => $subject,
			"body" => $body
		));

	$tryagain = '<a href="#post" onclick="document.getElementById(\'post\').style.top=60;">erneut versuchen</a>';
?>


<div id="posting_response:<?php echo $brdid ?>:<?php echo $msgid ?>" class="postingresponse" hideBackButton="true">
	<?php
		if (strpos($response[1], '-= board: confirm =-') !== FALSE) {
			echo "Posting gelungen! Thread über Titelleiste neu laden! Jetzt aber: KHADD!";
		} else if (strpos($response[1], 'fehler 14') !== FALSE) {
			echo "POSTING FEHLER 14: Beitrag schon vorhanden!<br/>{$tryagain}";
		} else if (strpos($response[1], 'fehler 3') !== FALSE) {
			echo "POSTING FEHLER 3: Password falsch! KHADD!<br/>{$tryagain}";
		} else {
			echo "POSTING FEHLER: Posting leider misslungen!<br/>{$tryagain}";
		}
	?>
</div>