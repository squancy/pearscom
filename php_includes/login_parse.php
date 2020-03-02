<?php
  // Start user session
  session_start();

  if(isset($_POST["e"])){
    // Get user ip address
    $ip = preg_replace('#[^0-9.]#', '', getenv('REMOTE_ADDR'));

    // Get referer from header
    $refer = preg_replace('#[^a-z0-9 -._]#i', '.', getenv('HTTP_REFERER'));  

    // Set variable for possible logging
    $csrf = "";

    // Check for login session  
    if(isset($_SESSION['login']) && isset($_SESSION['login']['tm']) &&
      isset($_SESSION['login']['tk']) && isset($_POST['t'])){
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
      } else {
        $csrf .= "A critical session or form token post was empty after sanitization|";
      }  
    } else {
      // Something fishy is going on .. our session is not set
      $csrf .= "A critical session or form token post was not set|";    
    }

    require_once "../php_includes/conn.php";
    
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
      $stmt->bind_param("issss", $ip, $refer, $csrf, $e, $p);
      $stmt->execute();
      $stmt->close();

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

    $e = mysqli_real_escape_string($conn, $_POST['e']);
    $p = md5($_POST['p']);

    if($e == "" || $p == ""){
      echo "login_failed";
      exit();
    } else {
      $one = "1";
      $sql = "SELECT id, username, password FROM users WHERE email=? AND activated=? LIMIT 1";
      $stmt = $conn->prepare($sql);
      $stmt->bind_param("ss",$e,$one);
      $stmt->bind_result($db_id);
      $stmt->bind_result($db_username);
      $stmt->bind_result($db_pass_str);
      $stmt->fetch();
      $stmt->close();
      if($p != $db_pass_str){
        echo "login_failed";
        exit();
      } else {
        $rme = $_POST['rme'];
        $_SESSION['userid'] = $db_id;
        $_SESSION['username'] = $db_username;
        $_SESSION['password'] = $db_pass_str;
        setcookie("id", $db_id, strtotime( '+30 days' ), "/", "", "", TRUE);
        setcookie("user", $db_username, strtotime( '+30 days' ), "/", "", "", TRUE);
        setcookie("pass", $db_pass_str, strtotime( '+30 days' ), "/", "", "", TRUE);

        if(!empty($rme) {
          setcookie ("user",$db_username,time()+ (10 * 365 * 24 * 60 * 60));
          setcookie ("pass",$db_pass_str,time()+ (10 * 365 * 24 * 60 * 60));
        }
        $sql = "UPDATE users SET ip=?, lastlogin=now() WHERE username=? LIMIT 1";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("is",$ip,$db_username);
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
