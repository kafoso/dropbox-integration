<?php
namespace Application\MVC\Model\DropboxIntegration;

class Authenticator {
	protected $appInfo;

	public function __construct($appInfoFilePath){
		try {
				$appInfo = \Dropbox\AppInfo::loadFromJsonFile($appInfoFilePath);
		}
		catch (\Dropbox\AppInfoLoadException $ex) {
				throw new Exception("Unable to load \"$appInfoFilePath\": " . $ex->getMessage());
		}
		$this->appInfo = $appInfo;
	}

	public function getAppInfo(){
		return $this->appInfo;
	}

	public function isAuthenticated(){
		return isset($_SESSION['access-token']);
	}

	public function getWebAuth($redirectUri){
		$csrfTokenStore = new \Dropbox\ArrayEntryStore($_SESSION, 'dropbox-auth-csrf-token');
		return new \Dropbox\WebAuth(
			$this->getAppInfo(),
			$this->getClientIdentifier(),
			$redirectUri,
			$csrfTokenStore,
			null
		);
	}

	public function getClientIdentifier(){
		return "examples-web-file-browser";
	}
}
