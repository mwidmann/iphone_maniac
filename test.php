<?php


var_dump($_POST);

/*

require "weblib.php";

$request = new HTTPRequest("http://www.maniac.de/forum.php?dm=0&id=101835&itemid=0&m=ij2&p=1&r=20&s=");
$json = $request->DownloadToString();
$decoded = json_decode($json, true);

$threads = array();

foreach ($decoded["items"] as $item) {
	
	$pid = $item["pid"];
	if ($threads[$pid] == null)
		$threads[$pid] = array();
		
	array_push($threads[$pid], $item);
}
?>
<div backHref="board.php?id=1" backText="Smalltalk" closed="0" class="thread" title="Thread" id="board:1:thread:119186" refreshUrl="/iphone_maniac/thread.php?id=1&thread=119186&closed=0&r=1">
<?php
showItems($threads,0);
?>
</div>
<?php

function showItems($threads, $id) {
	echo "<ul>";
	for ($i = sizeof($threads[$id]) -1; $i >= 0; $i--) {
		$curr_item = $threads[$id][$i];
		echo "<li>";
		echo $curr_item["title"];
		echo "<pre>";
		var_dump($curr_item);
		echo "</pre>";
		showItems($threads, $curr_item["cid"]);
		echo "</li>";
	}
	echo "</ul>";
}
*/