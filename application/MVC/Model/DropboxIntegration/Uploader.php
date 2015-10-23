<?php
namespace Application\MVC\Model\DropboxIntegration;

class Uploader {
	protected $file;
	protected $folder;

	protected $result = null;

	public function __construct($file, $folder){
		$this->file = $file;
		$this->folder = $folder ?: "/";
	}

	public function validate(){
		if (empty($this->file['name'])) {
			throw new \RuntimeException("No upload file specified");
		}
		if (!empty($this->file['error'])) {
			throw new \RuntimeException("Unable to upload. Error: " . $this->file['error']);
		}
		if ($this->isFileTooLarge()) {
			$maxFileSize = self::getUploadMaxFilesize();
			throw new \RuntimeException(sprintf(
				"File is too big. Max allowed: %s Byte. Provided file's size: %s Byte",
				$maxFileSize,
				$this->file["size"]
			));
		}
	}

	public function uploadFile(\Dropbox\Client $dropboxClient){
		$remotePath = rtrim($this->folder, "/")."/".$this->file['name'];
		$fileHandle = fopen($this->file['tmp_name'], "rb");
		$this->result = $dropboxClient->uploadFile($remotePath, \Dropbox\WriteMode::add(), $fileHandle);
		fclose($fileHandle);
	}

	public function getResult(){
		return $this->result;
	}

	protected function isFileTooLarge(){
		return (intval($this->file["size"]) > self::getUploadMaxFilesize());
	}

	public static function getUploadMaxFilesize(){
		$uploadMaxFilesize = self::phpInitSizeToByteSize(ini_get("upload_max_filesize"));
		$postMaxSize = self::phpInitSizeToByteSize(ini_get("post_max_size"));
		return min($uploadMaxFilesize, $postMaxSize);
	}

	public static function phpInitSizeToByteSize($str) {
		$str = preg_replace("/\s+/", "", trim($str));
		$unit = preg_replace('/[^bkmgtpezy]/i', '', $str); // Remove the non-unit characters from the size.
		$str = preg_replace('/[^0-9\.]/', '', $str); // Remove the non-numeric characters from the size.
		if ($unit) {
			return round($str * pow(1024, stripos('bkmgtpezy', $unit[0])));
		}
		return round($str);
	}
}
