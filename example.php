<?php

// Include core HTTP library
require_once 'class.HTTP.php';

// Initialize HTTP object
$http = new HTTP([
	// User agent for Google Chrome
	'userAgent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/61.0.3163.100 Safari/537.36',

	// Request timed out in 60 seconds
	'timeout' => 60,

	// Simulate we clicked from https://www.google.com
	'referer' => 'https://www.google.com',
]);

// Visit to https://www.reddit.com
$response = $http->get('https://www.reddit.com');

if ($response) {
	echo '<pre>';
	print_r($response['header']);
	echo htmlentities($response['body']);

	echo '</pre>';
}

$logs = $http->getLogs();

echo '<pre>';
print_r($logs);
echo '</pre>';
