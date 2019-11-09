<?php
	function base64url_encode($pure_string) {
	    $dirty = array("+", "/", "=");
	    $clean = array("HpaSLusH", "unMasJiNius", "uhaEuAl");
	    $iv_size = mcrypt_get_iv_size(MCRYPT_BLOWFISH, MCRYPT_MODE_ECB);
	    $_SESSION['iv'] = mcrypt_create_iv($iv_size, MCRYPT_RAND);
	    $encrypted_string = mcrypt_encrypt(MCRYPT_BLOWFISH, $_SESSION['encryption-key'], utf8_encode($pure_string), MCRYPT_MODE_ECB, $_SESSION['iv']);
	    $encrypted_string = base64_encode($encrypted_string);
	    return str_replace($dirty, $clean, $encrypted_string);
	}

	function base64url_decode($encrypted_string) { 
	    $dirty = array("+", "/", "=");
	    $clean = array("HpaSLusH", "unMasJiNius", "uhaEuAl");

	    $string = base64_decode(str_replace($clean, $dirty, $encrypted_string));

	    $decrypted_string = mcrypt_decrypt(MCRYPT_BLOWFISH, $_SESSION['encryption-key'],$string, MCRYPT_MODE_ECB, $_SESSION['iv']);
	    return $decrypted_string;
	}
?> 