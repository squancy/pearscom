<?php
  // Check to see if the $_SESSION is set
  if(!isset($_SESSION)){
    session_start();
  }

  // Connect to the database
  require_once "conn.php";

  // Initialize some vars
  $user_ok = false;
  $log_id = "";
  $log_username = "";
  $log_password = "";
  $password_u = "";

  // User Verify function
  function evalLoggedUser($conx, $id, $u, $p){
    $v = '1';
    $sql = "SELECT * FROM users WHERE id=? AND username=? AND activated=? LIMIT 1";
    $stmt = $conx->prepare($sql);
    $stmt->bind_param("iss", $id, $u, $v);
    $stmt->execute();
    $result = $stmt->get_result();
    if($result->num_rows > 0){
      while($row = $result->fetch_assoc()){
        $password_u = $row["password"];
      }
    }
    $stmt->close();

    // Recheck with the password
    $sql = "SELECT id FROM users WHERE id=? AND username=? AND password=? AND activated=?
      LIMIT 1";
    $stmt = $conx->prepare($sql);
    $stmt->bind_param("isss", $id, $u, $password_u, $v);
    $stmt->execute();
    $stmt->store_result();
    $stmt->fetch();
    $numrows = $stmt->num_rows;
    if($numrows > 0){
      return true;
    }
  }

  // Clean the users variables
  if(isset($_SESSION["userid"]) && isset($_SESSION["username"]) &&
    isset($_SESSION["password"])) {
    $log_id = preg_replace('#[^0-9]#', '', $_SESSION['userid']);
    $log_username = mysqli_real_escape_string($conn, $_SESSION['username']);
    $log_password = $_SESSION['password'];

    // Verify the user
    $user_ok = evalLoggedUser($conn,$log_id,$log_username,$log_password);

    // Set their cookies and sessions
  } else if(isset($_COOKIE["id"]) && isset($_COOKIE["user"]) && isset($_COOKIE["pass"])){
    $_SESSION['userid'] = preg_replace('#[^0-9]#', '', $_COOKIE['id']);
    $_SESSION['username'] = mysqli_real_escape_string($conn, $_COOKIE['user']);
    $_SESSION['password'] = $_COOKIE['pass'];
    $log_id = $_SESSION['userid'];
    $log_username = $_SESSION['username'];
    $log_password = $_SESSION['password'];

    // Verify the user
    $user_ok = evalLoggedUser($conn,$log_id,$log_username,$log_password);
    if($user_ok == true){
      // Update their lastlogin datetime field
      $sql = "UPDATE users SET lastlogin=NOW() WHERE id=? LIMIT 1";
      $stmt = $conn->prepare($sql);
      $stmt->bind_param("s",$log_id);
      $stmt->execute();
      $stmt->close();
    }
  }
?>
