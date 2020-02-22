<?php
  require_once 'headers.php';

  function sec_session_start() {
    $session_name = '__Secure-'; // Set a custom session name 
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
    session_set_cookie_params($cookieParams["lifetime"], $cookieParams["path"],
      $cookieParams["domain"], $secure, $httponly);

    // Sets the session name to the one set above.
    //session_name($session_name);
    session_start();            // Start the PHP session
    session_regenerate_id();    // regenerated the session, delete the old one.
  }
?>
