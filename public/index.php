<?php
preg_match("/^\/public(\/index\.php)?(.*)$/", $_SERVER["REQUEST_URI"], $match);
if ($match) {
	header("Location: " . $match[2]);
}

try {
	require(__DIR__ . "/../system/_bootstrap.inc.php");
} catch (\Exception $e) {
	header("HTTP/1.0 500 Internal Server Error");
	header("Content-Type: application/json");
	die(@json_encode((array)$e));
}
