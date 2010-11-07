<?php 
/**
 * Http Blacklist Helper Class
 * 
 * Based on http://wordpress.org/extend/plugins/httpbl/
 * 
 * @author Thomas Heymann
 * @version	0.1
 * @license	http://www.opensource.org/licenses/mit-license.php The MIT License
 * @package app
 * @subpackage app.views.helpers
 **/
class HttpBlacklistComponent extends Object {
	
	var $components = array(
		'RequestHandler',
		'Session'
		);
	
	var $response = array();
	
	// http:BL service provides you information about the date of the last activity of a checked IP. Due to the fact that the information in the Project Honey Pot database may be obsolete, you may set an age threshold, counted in days. If the verified IP hasn't been active for a period of time longer than the threshold it will be regarded as harmless.
	var $ageThres = 14;
	
	// Each suspicious IP address is given a threat score. This scored is asigned by Project Honey Pot basing on various factors, such as the IP's activity or the damage done during the visits. The score is a number between 0 and 255, where 0 is no threat at all and 255 is extremely harmful. In the field above you may set the threat score threshold. IP address with a score greater than the given number will be regarded as harmful.
	var $suspiciousThreatThres = 25;
	var $harvesterThreatThres = 25;
	var $commentspammerThreatThres = 25;
	
	// Types of visitors to be treated as malicious
	var $denied = array(
		0 => false, // searchEngine
		1 => true, // suspicious
		2 => true, // harvester
		4 => true, // commentSpammer
		8 => null,
		16 => null,
		32 => null,
		64 => null,
		128 => null
		);
	
	// Called before the Controller::beforeFilter().
	function initialize(&$controller, $options) {
        $this->controller =& $controller;
	}
	
	function blockMalicious($ip = null) {
		if ( $this->isMalicious($ip) )
			$this->block();
	}
	function block() {
		if ( $honeyPot = Configure::read('HttpBlacklist.honeyPot') )
			$this->controller->redirect($honeyPot, 301);
		exit;
	}
	function isMalicious($ip = null) {
		if ( empty($ip) )
			$ip = $this->RequestHandler->getClientIP();
		
		// Cache result to minimize requests
		$integerIp = sprintf('%u', ip2long($ip));
		if ( $this->Session->check("HttpBlacklist.$integerIp") && $this->Session->read("HttpBlacklist.$integerIp.expires") < time() )
			return $this->Session->read("HttpBlacklist.$integerIp.isMalicious");
		
		// Reset
		$this->response = array();
		
		// Assume best intentions
		$age = false;
		$threat = false;
		$deny = false;
		$blocked = false;
		
		// Get access key
		$accessKey = Configure::read('HttpBlacklist.accessKey');
		
		// Get client ip and format for http blacklist
		$reversedIp = implode('.', array_reverse(explode('.', $ip)));
		$dnsQuery = "$accessKey.$reversedIp.dnsbl.httpbl.org";
		
		// Query http blacklist (http://www.projecthoneypot.org/httpbl_api.php)
		if ( ($dnsResult = gethostbyname($dnsQuery)) != $dnsQuery ) {
			$this->response = 
			$result = explode('.', $dnsResult);
		
			// If the response is positive,
			if ( $result[0] == 127 ) {

				// Below age threshold?
				if ( $result[1] < $this->ageThres )
					$age = true;

				// Check suspicious threat
				if ( ($result[3] & 1) && $result[2] > $this->suspiciousThreatThres )
					$threat = true;

				// Check harvester threat
				if ( ($result[3] & 2) && $result[2] > $this->harvesterThreatThres )
					$threat = true;

				// Check comment spammer threat
				if ( ($result[3] & 4) && $result[2] > $this->commentspammerThreatThres )
					$threat = true;
			
				// Deny type of visitor?
				foreach ( $this->denied as $key => $value ) {
					if ( $value && ($result[3]-($result[3]%$key)) > 0 ) {
						$deny = true;
						break;
					}
				}
			}
		}
		
		$isMalicious = ($deny && $age && $threat);
		$this->Session->write("HttpBlacklist.$integerIp.isMalicious", $isMalicious);
		$this->Session->write("HttpBlacklist.$integerIp.expires", time()+(60*60*3));
		return $isMalicious;
	}
}
?>