<?php
	require_once 'php_includes/check_login_statues.php';
	require_once 'sec_session_start.php';
	require_once 'headers.php';
	$no = "no";
	$domain = "pearscom.com";
	sec_session_start();
	// Set Session data to an empty array

	// Expire their cookie files
	if(isset($_COOKIE["id"]) && isset($_COOKIE["user"]) && isset($_COOKIE["pass"])) {
		setcookie("id", '', strtotime( '-5 days' ), '/', '', true, true);
	    setcookie("user", '', strtotime( '-5 days' ), '/', '', true, true);
		setcookie("pass", '', strtotime( '-5 days' ), '/', '', true, true);
	}

	// Set online to "no"
	$sql = "UPDATE users SET online=?, lastlogin=NOW() WHERE username=?";
	$stmt = $conn->prepare($sql);
	$stmt->bind_param("ss",$no,$log_username);
	$stmt->execute();
	$stmt->close();

	session_regenerate_id(true);
	// Destroy the session variables
	foreach($_SESSION as $key => $val)
    {
    
        if ($key !== 'cookieset_')
        {
    
          unset($_SESSION[$key]);
    
        }
    
    }
	// Double check to see if their sessions exists
	if(isset($_SESSION['username'])){
		header("location: /logoutfail");
	} else {
		header("location: /login");
		exit();
	}
?>