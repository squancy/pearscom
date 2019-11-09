<?php
	header('X-Powered-By: PHP/7.1.15');
  	header('Strict-Transport-Security: max-age=31536000; includeSubDomains; preload');
  	header('X-Content-Type-Options: nosniff');
 	header("X-XSS-Protection: 1; mode=block");
 	header("Content-Security-Policy: default-src https: 'unsafe-eval' 'unsafe-inline'; object-src 'none'; frame-ancestors https://www.pearscom.com");
 	header('Referrer-Policy: no-referrer, strict-origin-when-cross-origin');
 	header('Public-Key-Pins: max-age=1296000; includeSubDomains; pin-sha256="oO+llhra8ivcCOlAIrletxRgtAEq5jZGwgqhPM+sFFI=";
 pin-sha256="YLh1dUR9y6Kja30RrAn7JKnbQG/uEtLMkBgFF2Fuihg="; pin-sha256="Vjs8r4z+80wjNcr1YKepWQboSIRi63WsWXhIMN+eWys="');
 	header('Access-Control-Allow-Origin: https://pearscom.com');
	
	function sec_session_start() {
	    $session_name = '__Secure-';   // Set a custom session name 
	    $secure = TRUE;
	    // This stops JavaScript being able to access the session id.
	    $httponly = TRUE;
	    // Forces sessions to only use cookies.
	    if (ini_set('session.use_only_cookies', 1) === FALSE) {
	        header("Location: ../index");
	        exit();
	    }
	    // Gets current cookies params.
	    $cookieParams = session_get_cookie_params();
	    session_set_cookie_params($cookieParams["lifetime"], $cookieParams["path"], $cookieParams["domain"], $secure, $httponly);
	    // Sets the session name to the one set above.
	    //session_name($session_name);
	    session_start();            // Start the PHP session
	    session_regenerate_id();    // regenerated the session, delete the old one.
}
?>