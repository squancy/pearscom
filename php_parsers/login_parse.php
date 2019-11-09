<?php
	require_once '../sec_session_start.php';
	header('X-Powered-By: PHP/7.1.15');
  	header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
  	header('X-Content-Type-Options: nosniff');
 	header("X-XSS-Protection: 1; mode=block");
 	header('Content-Security-Policy: frame-ancestors https://www.pearscom.com');
 	header("Content-Security-Policy: default-src https: 'unsafe-eval' 'unsafe-inline'; object-src 'none'");
 	header('Referrer-Policy: no-referrer, strict-origin-when-cross-origin');
 	header('Public-Key-Pins: max-age=1296000; includeSubDomains; pin-sha256="oO+llhra8ivcCOlAIrletxRgtAEq5jZGwgqhPM+sFFI=";
 pin-sha256="YLh1dUR9y6Kja30RrAn7JKnbQG/uEtLMkBgFF2Fuihg="; pin-sha256="Vjs8r4z+80wjNcr1YKepWQboSIRi63WsWXhIMN+eWys="');
 	header('Access-Control-Allow-Origin: https://pearscom.com');
	$domain = "pearscom.com";
	sec_session_start();
	$one = "1";
	$yes = "yes";
	// AJAX CALLS THIS LOGIN CODE TO EXECUTE
	if(isset($_POST["e"])){
		// START Expansion
	// Get user ip address
	$ip = preg_replace('#[^0-9.]#', '', getenv('REMOTE_ADDR'));
	// Get referer from header
	$refer = preg_replace('#[^a-z0-9 -._]#i', '.', getenv('HTTP_REFERER'));	
	// Set variable for possible logging
	$csrf = "";
	// Check for login session	
	if(isset($_SESSION['login']) && isset($_SESSION['login']['tm']) && isset($_SESSION['login']['tk']) && isset($_POST['t'])){
		// Sanitize everything now
		$sTimestamp = preg_replace('#[^0-9]#', '', $_SESSION['login']['tm']);
		$sToken = preg_replace('#[^a-z0-9.-]#i', '', $_SESSION['login']['tk']);
		$fToken = preg_replace('#[^a-z0-9.-]#i', '', $_POST['t']);
		// Make sure we have values after sanitizing
		if($sTimestamp != "" && $sToken != "" && $fToken != ""){
			// Check if session and post token match
			if($fToken !== $sToken){
				$csrf .= "Form token and session token do not match|";
			}
			// Do 5 minute check
			$elapsed = time() - $sTimestamp;
			if($elapsed > 300){
				$csrf .= "Expired session|";
			}
			// add more checks here if needed			
		} else {
			$csrf .= "A critical session or form token post was empty after sanitization|";
		}	
	} else {
		// Something fishy is going on .. our session is not set
		$csrf .= "A critical session or form token post was not set|";		
	}
	// CONNECT TO THE DATABASE
	include_once("../php_includes/conn.php");
	
	// Check our errors here
	if($csrf !== ""){
		// At least one of our tests above was failed
		// Sanitize the e & p posts for logging
		$e = mysqli_real_escape_string($conn, $_POST['e']);
		$p = mysqli_real_escape_string($conn, $_POST['p']);
		// Time to log this
		$sql = "INSERT INTO logging (dt, ip, referer, issues, epost, ppost)       
		        VALUES(NOW(),?,?,?,?,?)";
		$stmt = $conn->prepare($sql);
		$stmt->bind_param("issss",$ip,$refer,$csrf,$e,$p);
		$stmt->execute();
		$stmt->close();
		mysqli_close($conn);
		// Unset 
		if(isset($_SESSION['login'])){
			unset($_SESSION['login']);
		}
		// Throttle back the attack
		sleep(3);
		// Return generic login_failed and exit script
		echo "login_failed";
        exit();
	}
	
	// Move ip grabber to top of this script
	// Change database connection
	// Move database connection into this script
	// Add session unset in existing form processing if they log in	
	// END Expansion

		// GATHER THE POSTED DATA INTO LOCAL VARIABLES AND SANITIZE
		$e = mysqli_real_escape_string($conn, $_POST['e']);
		$p = $_POST['p'];

		// GET USER IP ADDRESS
	    // $ip = preg_replace('#[^0-9.]#', '', getenv('REMOTE_ADDR'));
		// FORM DATA ERROR HANDLING
		if($e == "" || $p == ""){
			echo "login_failed";
	        exit();
		} else {
		// END FORM DATA ERROR HANDLING
			$db_id = "";
			$db_username = "";
			$db_pass_str = "";
			$sql = "SELECT id, username, password FROM users WHERE email=? AND activated=? LIMIT 1";
	        $stmt = $conn->prepare($sql);
	        $stmt->bind_param("ss",$e,$one);
	        $stmt->execute();
	        $result = $stmt->get_result();
	        while($row = $result->fetch_assoc()){
	        	$db_id = $row["id"];
				$db_username = $row["username"];
	       	 	$db_pass_str = $row["password"];
	        }
	        $stmt->close();
			if(!password_verify($p, $db_pass_str)){
				echo "login_failed";
	            exit();
			} else {
				// CREATE THEIR SESSIONS AND COOKIES
				$_SESSION['userid'] = $db_id;
				$_SESSION['username'] = $db_username;
				$_SESSION['password'] = $db_pass_str;
				setcookie("id", $db_id, strtotime( '+30 days' ), "/", "", "", TRUE);
				setcookie("user", $db_username, strtotime( '+30 days' ), "/", "", TRUE, TRUE);
	    		setcookie("pass", $db_pass_str, strtotime( '+30 days' ), "/", "", TRUE, TRUE);
	    		session_regenerate_id();
				// UPDATE THEIR "IP" AND "LASTLOGIN" FIELDS
				$sql = "UPDATE users SET ip=?, online=?, lastlogin=NOW() WHERE username=? LIMIT 1";
	            $stmt = $conn->prepare($sql);
	            $stmt->bind_param("iss",$ip,$yes,$db_username);
	            $stmt->execute();
	            $stmt->close();

	            // Unset that session if they logged in
	            if(isset($_SESSION['login'])){
	            	unset($_SESSION['login']);
	            }

				echo $db_username;
			    exit();
			}
		}
		exit();
	}
?>