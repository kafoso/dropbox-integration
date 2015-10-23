<?php
namespace Application\MVC\Model\DropboxIntegration;

class Authenticator {
	protected $appInfo;
	protected $session;

	public function __construct($appInfoFilePath, &$session){
		try {
				$appInfo = \Dropbox\AppInfo::loadFromJsonFile($appInfoFilePath);
		}
		catch (\Dropbox\AppInfoLoadException $ex) {
				throw new Exception("Unable to load \"$appInfoFilePath\": " . $ex->getMessage());
		}
		$this->appInfo = $appInfo;
		$this->session = &$session;
	}

	public function getAppInfo(){
		return $this->appInfo;
	}

	public function isAuthenticated(){
		return isset($this->session['access-token']);
	}

	public function getWebAuth($redirectUri){
		$csrfTokenStore = new \Dropbox\ArrayEntryStore($this->session, 'dropbox-auth-csrf-token');
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
