<?php
/**
 * Http Blacklist Helper Class
 * 
 * @author Thomas Heymann
 * @version	0.1
 * @license	http://www.opensource.org/licenses/mit-license.php The MIT License
 * @package app
 * @subpackage app.views.helpers
 **/
class HttpBlacklistHelper extends AppHelper {
	
	function honeyPot($num = 1) {
		if ( $honeyPot = Configure::read('HttpBlacklist.honeyPot') ) {
			// Generate random word
			$length = rand(4, 16);
			$word = '';
			while ( strlen($word) < $length) {
				// $a=48; $b=57; // Numbers
				// $a=65; $b=90;  // UpperCase
				// $a=97; $b=122; // LowerCase
				$a = 97; $b = 122;
				$word .= chr(rand($a, $b));
			}
			
			$hiddenLinks = array(
				"<a href=\"$honeyPot\"><!-- $word --></a>",
				"<a href=\"$honeyPot\" style=\"display: none;\">$word</a>",
				"<div style=\"display: none;\"><a href=\"$honeyPot\">$word</a></div>",
				"<a href=\"$honeyPot\"></a>",
				"<!-- <a href=\"$honeyPot\">$word</a> -->",
				"<div style=\"position: absolute; top: -250px; left: -250px;\"><a href=\"$honeyPot\">$word</a></div>",
				"<a href=\"$honeyPot\"><span style=\"display: none;\">$word</span></a>",
				"<a href=\"$honeyPot\"><div style=\"height: 0px; width: 0px;\"></div></a>"
				);
			shuffle($hiddenLinks);
			return implode(' ', array_slice($hiddenLinks, 0, $num));
		}
		return false;
	}
}
?>