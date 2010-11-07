<?php
class AppController extends Controller {
	
	var $components = array(
		'HttpBlacklist'
		);
	
	var $helpers = array(
		'HttpBlacklist'
		);
	
	function beforeFilter() {
		parent::beforeFilter();
		
		// Blacklist requests from malicious IPs
		$this->HttpBlacklist->blockMalicious();
	}
}
?>