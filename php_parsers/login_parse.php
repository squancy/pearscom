<?php
  require_once '../sec_session_start.php';
  require_once '../php_includes/conn.php';

  $domain = "pearscom.com";
  sec_session_start();

  class LoginHandler {
    public function __construct($tstamp1, $tstamp2, $tstamp3) {
      $this->sTimestamp = preg_replace('#[^0-9]#', '', $tstamp1);
      $this->sToken = preg_replace('#[^a-z0-9.-]#i', '', $tstamp2);
      $this->fToken = preg_replace('#[^a-z0-9.-]#i', '', $tstamp3);
      $this->csrf = '';
    }

    public function checkTimestamp() {
      if($this->sTimestamp && $this->sToken && $this->fToken){
        // Check if session and post token match
        if($this->fToken !== $this->sToken){
          $this->csrf .= "Form token and session token do not match|";
        }

        // Do 5 minute check
        $elapsed = time() - $this->sTimestamp;
        if($elapsed > 300){
          $this->csrf .= "Expired session|";
        }
      } else {
        $this->csrf .= "A critical session or form token post was empty after sanitization|";
      }
    }

    public function getUserInfo($conn, $e) {
      $one = '1';
      $sql = "SELECT id, username, password FROM users WHERE email=? AND activated=? LIMIT 1";
      $stmt = $conn->prepare($sql);
      $stmt->bind_param("ss", $e, $one);
      $stmt->execute();
      $result = $stmt->get_result();
      if($row = $result->fetch_assoc()){
        $this->db_id = $row["id"];
        $this->db_username = $row["username"];
        $this->db_pass_str = $row["password"];
      }
      $stmt->close();
    }

    public function setCookieData() {
      $_SESSION['userid'] = $this->db_id;
      $_SESSION['username'] = $this->db_username;
      $_SESSION['password'] = $this->db_pass_str;
      setcookie("id", $this->db_id, strtotime( '+30 days' ), "/", "", "", TRUE);
      setcookie("user", $this->db_username, strtotime( '+30 days' ), "/", "", TRUE, TRUE);
      setcookie("pass", $this->db_pass_str, strtotime( '+30 days' ), "/", "", TRUE, TRUE);
      session_regenerate_id();
    }

    public function updateDb($conn, $ip) {
      $yes = 'yes';
      $sql = "UPDATE users SET ip=?, online=?, lastlogin=NOW() WHERE username=? LIMIT 1";
      $stmt = $conn->prepare($sql);
      $stmt->bind_param("iss", $ip, $yes, $this->db_username);
      $stmt->execute();
      $stmt->close();
    }
  }

  function recordLogin($conn, $ip, $refer, $csrf, $e, $p) {
    $sql = "INSERT INTO logging (dt, ip, referer, issues, epost, ppost)       
            VALUES(NOW(),?,?,?,?,?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("issss", $ip, $refer, $csrf, $e, $p);
    $stmt->execute();
    $stmt->close();
    mysqli_close($conn);
  }

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
      $stamp = new LoginHandler($_SESSION['login']['tm'], $_SESSION['login']['tk'],
        $_POST['t']);

      $stamp->checkTimestamp();
    } else {
      $csrf .= "A critical session or form token post was not set|";    
    }
    
    if($csrf || $stamp->csrf){
      $e = mysqli_real_escape_string($conn, $_POST['e']);
      $p = mysqli_real_escape_string($conn, $_POST['p']);

      // Time to log this
      recordLogin($conn, $ip, $refer, $csrf, $e, $p);

      if(isset($_SESSION['login'])){
        unset($_SESSION['login']);
      }

      // Throttle back the attack
      sleep(3);
      echo $csrf.$stamp->csrf;
      echo "login_failed .";
      exit();
    }
    
    $e = mysqli_real_escape_string($conn, $_POST['e']);
    $p = $_POST['p'];

    if(!$e || !$p){
      echo "login_failed ? ";
      exit();
    } else {
      $stamp->getUserInfo($conn, $e);

      if(!password_verify($p, $stamp->db_pass_str)){
        echo "login_failed";
        exit();
      } else {
        $stamp->setCookieData();

        // Update info in db
        $stamp->updateDb($conn, $ip);

        // Unset that session if they logged in
        if(isset($_SESSION['login'])){
          unset($_SESSION['login']);
        }

        echo $stamp->db_username;
        exit();
      }
    }
    exit();
  }
?>
