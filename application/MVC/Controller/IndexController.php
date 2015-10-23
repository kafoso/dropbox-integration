<?php
namespace Application\MVC\Controller;

use System\MVC\Controller\SystemController;
use System\MVC\Model\ViewModel;
use Application\MVC\Model\DropboxIntegration\Uploader;

class IndexController extends SystemController {
	protected $dropboxClient;

	public function index(){
		if ($this->authenticator->isAuthenticated()) {
			header("Location: " . $this->getBaseURL() . "/view");
			die;
		}
		header("Location: " . $this->getBaseURL() . "/auth");
		die;
	}

	public function auth(){
		if ($this->authenticator->isAuthenticated()) {
			header("Location: " . $this->getBaseURL() . "/view");
			die;
		}
		$webAuth = $this->authenticator->getWebAuth($this->getBaseURL() . "/auth/callback");
		$authorizeUrl = $webAuth->start();
		header("Location: $authorizeUrl");
		die;
	}

	public function authCallback(){
		try {
			list($accessToken, $userId, $urlState) = $this->authenticator->getWebAuth($this->getBaseURL() . "/auth/callback")->finish($_GET);
			assert($urlState === null);
		}
		catch (dbx\WebAuthException_BadRequest $ex) {
			$this->respondWithError(400, "Bad Request");
		}
		catch (dbx\WebAuthException_BadState $ex) {
			header("Location: " . $this->getBaseURL());
			die;
		}
		catch (dbx\WebAuthException_Csrf $ex) {
			$this->respondWithError(403, "Unauthorized", "CSRF mismatch");
		}
		catch (dbx\WebAuthException_NotApproved $ex) {
			$this->respondWithError(404, "Not Authorized?", "Why not?");
		}
		catch (dbx\WebAuthException_Provider $ex) {
			$this->respondWithError(500, "Internal Server Error");
		}
		catch (dbx\Exception $ex) {
			$this->respondWithError(500, "Internal Server Error");
		}
		$_SESSION['access-token'] = $accessToken;
		header("Location: " . $this->getBaseURL() . "/view");
		die;
	}

	public function logout(){
		unset($_SESSION['access-token']);
		$this->redirectToIndex();
	}

	public function view(){
		$this->guardMustBeAuthenticatedOtherwiseRedirectToIndex();
		$dropboxPath = @strval($this->getQueryArray()["path"]) ?: "/";

		$dropboxClient = $this->getDropboxClient();
		$entry = $dropboxClient->getMetadataWithChildren($dropboxPath);

		$layout = new ViewModel;
		$layout->setTemplateFilePath(__DIR__ . "/../View/layout/default.phtml");
		$layout->title = $entry["path"];

		$view = new ViewModel;
		$view->entry = $entry;
		$view->pathView = $this->getPath();
		if ($entry["is_dir"]) {
			$view->setTemplateFilePath(__DIR__ . "/../View/application/folder.phtml");
			$view->uploadUrl = $this->getBaseURL() . "/upload";
			$view->uploadFolderPath = $entry["path"];
			$view->uploadMaxFilesize = Uploader::getUploadMaxFilesize();
		} else {
			$view->setTemplateFilePath(__DIR__ . "/../View/application/file.phtml");
			$view->pathDownload = "/dropbox-integration/download";
		}
		$layout->content = $view->render();
		echo $layout->render();
		die;
	}

	public function download(){
		$this->guardMustBeAuthenticatedOtherwiseRedirectToIndex();
		$dropboxPath = @strval($this->getQueryArray()["path"]);
		if (!$dropboxPath) {
			header("Location: " . $this->getBaseURL() . "/view");
			die;
		}
		$atteptsIndex = 0;
		$attempsMax = 5;
		for (; $atteptsIndex<5; $atteptsIndex++) {
			$randomizedFileName = uniqid() . intval(microtime(true)*1000) . md5(rand());
			$outputFilePath = ROOT_PATH . "/data/files/" . $randomizedFileName;
			if (!file_exists($outputFilePath)) {
				break;
			}
		}
		if ($atteptsIndex >= $attempsMax) {
			throw new \RuntimeException("Unable to generate a unique filename. Last filename generated was: " . $randomizedFileName);
		}

		$dropboxClient = $this->getDropboxClient();
		$fileHandle = fopen($outputFilePath, "wb");
		$metadata = $dropboxClient->getFile($dropboxPath, $fileHandle);
		fseek($fileHandle, 0);
		fpassthru($fileHandle);
		fclose($fileHandle);
		header("Pragma: public");
		header("Expires: 0");
		header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
		header("Content-Description: File Transfer");
		header("Content-Transfer-Encoding: binary");
		header("Content-disposition: attachment; filename=" . basename($metadata["path"]));
		header("Content-Length: ".filesize($outputFilePath));
		header("Content-Type: " . $metadata["mime_type"]);
		readfile($outputFilePath);
		unlink($outputFilePath);
		die;
	}

	public function upload(){
		$this->guardMustBeAuthenticatedOtherwiseRedirectToIndex();
		if (@$_SERVER["HTTP_X_REQUESTED_WITH"] != "XMLHttpRequest") {
			throw new \RuntimeException("Request must be performed via XHR");
			exit;
		}
		if ($_SERVER["REQUEST_METHOD"] != "POST") {
			header("Location: " . $this->getBaseURL() . "/view");
			die;
		}
		$uploader = new Uploader(@$_FILES["file"], @$_POST["folder"]);
		$uploader->validate();
		$uploader->uploadFile($this->getDropboxClient());
		die(@json_encode($uploader->getResult()));
	}

	protected function guardMustBeAuthenticatedOtherwiseRedirectToIndex(){
		if (!$this->authenticator->isAuthenticated()) {
			$this->redirectToIndex();
		}
	}

	protected function redirectToIndex(){
		header("Location: " . $this->getBaseURL());
		die;
	}

	protected function getDropboxClient(){
		if (!$this->dropboxClient) {
			$this->dropboxClient =  new \Dropbox\Client(
				$_SESSION['access-token'],
				$this->authenticator->getClientIdentifier(),
				null,
				$this->authenticator->getAppInfo()->getHost()
			);
		}
		return $this->dropboxClient;
	}
}
