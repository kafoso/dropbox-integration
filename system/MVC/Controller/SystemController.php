<?php
namespace System\MVC\Controller;

abstract class SystemController {
	protected $server;
	protected $authenticator;

	private $_urlArray;
	private $_scheme;
	private $_host;
	private $_path;
	private $_query;
	private $_queryArray;
	private $_fragment;

	public function __construct(array $server, $authenticator){
		$this->server = $server;
		$this->authenticator = $authenticator;
	}

	public function respondWithError($statusCode, $statusMessage, $body = ""){
		$proto = $this->server['SERVER_PROTOCOL'];
    header("$proto $statusCode $statusMessage", true, $statusCode);
		$output = "<h1>{$statusMessage}</h1>";
		if ($body) {
			$output .= "<p>{$body}</p>";
		}
		die($outuput);
	}

	public function getUrlArray(){
		if (!$this->_urlArray) {
			$this->_urlArray = [
				"base" => $this->getBaseURL(),
				"full" => $this->getFullURL(),
				"scheme" => $this->getScheme(),
				"host" => $this->getHost(),
				"path" => $this->getPath(),
				"query" => $this->getQuery(),
				"queryArray" => $this->getQueryArray(),
				"fragment" => $this->getFragment(),
			];
		}
		return $this->_urlArray;
	}

	public function getBaseURL(){
		$url = $this->getScheme() . "://" . $this->getHost();
		if (defined("DIR_REL")) {
			$url .= "/" . DIR_REL;
		}
		return $url;
	}

	public function getFullURL(){
		$url = $this->getBaseURL();
		$path = substr($this->getPath(), (@strlen(DIR_REL)+1));
		$url .= $path;
		if ($query = $this->getQuery()) {
			$url .= "?" . $query;
		}
		if ($fragment = $this->getFragment()) {
			$url .= "#" . $fragment;
		}
		return $url;
	}

	public function getScheme(){
		if (!$this->_scheme) {
			$this->_scheme = (isset($this->server['HTTPS']) && $this->server['HTTPS'] !== 'off') ? "https" : "http";
		}
		return $this->_scheme;
	}

	public function getHost(){
		if (!$this->_host) {
			$this->_host = $this->server["HTTP_HOST"];
		}
		return $this->_host;
	}

	public function getPath(){
		if (!$this->_path) {
			$this->_path = parse_url($this->server["REQUEST_URI"], PHP_URL_PATH);
		}
		return $this->_path;
	}

	public function getQuery(){
		if (!$this->_query) {
			$this->_query = parse_url($this->server["REQUEST_URI"], PHP_URL_QUERY);
		}
		return $this->_query;
	}

	public function getQueryArray(){
		if (!$this->_queryArray) {
			$this->_queryArray = [];
			parse_str($this->getQuery(), $this->_queryArray);
		}
		return $this->_queryArray;
	}

	public function getFragment(){
		if (!$this->_fragment) {
			$this->_fragment = parse_url($this->server["REQUEST_URI"], PHP_URL_FRAGMENT);
		}
		return $this->_fragment;
	}
}
