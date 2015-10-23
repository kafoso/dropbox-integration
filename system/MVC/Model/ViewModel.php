<?php
namespace System\MVC\Model;

class ViewModel {
	protected $templateFilePath;

	public function render(){
		if (!$this->templateFilePath) {
			throw new \RuntimeException("No template specified!");
		} else if (!file_exists($this->templateFilePath)) {
			throw new \RuntimeException("File template file exists on location: " . $this->templateFilePath);
		}
		ob_start();
		require($this->templateFilePath);
		$output = ob_get_contents();
		ob_end_clean();
		return $output;
	}

	public function setTemplateFilePath($templateFilePath){
		$this->templateFilePath = @strval($templateFilePath);
	}
}
