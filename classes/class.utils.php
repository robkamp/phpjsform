<?php
	
	class utils {
		
    static function uuid() {
			return sprintf( '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
					mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ),
					mt_rand( 0, 0x0fff ) | 0x4000,
					mt_rand( 0, 0x3fff ) | 0x8000,
					mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ) );
		}

		static function debug ($data,$title='debug') {
			echo sprintf('<div style="float: right;"><fieldset><legend>%s</legend><pre>',$title);
			print_r($data);
			echo '</pre></fieldset></div>';
		}
	
	}

?>