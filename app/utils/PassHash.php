<?php
class PassHash {
	
	// cost parameter
	private static $options = array (
			'cost' => 10 
	);
	
	// this will be used to generate a hash
	public static function hash($password) {
		return password_hash ( $password, PASSWORD_BCRYPT, PassHash::$options );
	}
	
	// this will be used to compare a password against a hash
	public static function check_password($hash, $password) {
		return (password_verify ( $password, $hash ));
	}
}