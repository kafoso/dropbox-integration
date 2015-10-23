<?php
$autoloader = require(__DIR__ . "/library/composer/vendor/autoload.php");

$autoloadClassmap = require(__DIR__ . "/autoload_classmap.php");
$autoloader->addClassMap($autoloadClassmap);

require(__DIR__ . "/config/definitions.php");

// Setup error reporting

require(ROOT_PATH . "/system/library/composer/vendor/dropbox/dropbox-sdk/lib/Dropbox/strict.php");

// Setup application

session_start();

$scheme = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https" : "http";
$baseURI = $scheme . "://" . $_SERVER["HTTP_HOST"];
$relativeURI = preg_replace("/\/$/", "", $_SERVER["REQUEST_URI"]);
$fullURI = $baseURI . $relativeURI;
$uriArray = parse_url($fullURI);

$appInfoFilePath = ROOT_PATH . "/system/config/auth/dropbox-auth.json";

if (!file_exists($appInfoFilePath)) {
	throw new \RuntimeException(sprintf(
		"Invalid auth file path: %s",
		$appInfoFilePath
	));
}

$authenticator = new \Application\MVC\Model\DropboxIntegration\Authenticator(
	$appInfoFilePath,
	$_SESSION
);

$controller = new \Application\MVC\Controller\IndexController(
	$_SERVER,
	$_SESSION,
	$_REQUEST,
	$authenticator
);

$path = preg_replace("/\/$/", "", $uriArray["path"]);

// Map paths to controller (poor-mans routing)

try {
	switch ($path) {
		case "/dropbox-integration":
			$controller->index();
			break;
		case "/dropbox-integration/auth":
			$controller->auth();
			break;
		case "/dropbox-integration/auth/callback":
			$controller->authCallback();
			break;
		case "/dropbox-integration/view":
			$controller->view();
			break;
		case "/dropbox-integration/download":
			$controller->download();
			break;
		case "/dropbox-integration/upload":
			$controller->upload();
			break;
	}
} catch (\Exception $e) {
	header("HTTP/1.0 500 Internal Server Error");
	header("Content-Type: application/json");
	die(@json_encode((array)$e));
}

header("HTTP/1.0 404 Not Found");
die("404 Not Found");
