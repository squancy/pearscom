<?php
  /*
    Perform authorization and authentication of users. Required on most pages.
  */

  function checkU($get_u, $conn) {
    if(isset($get_u)){
      $u = mysqli_real_escape_string($conn, $get_u);
    }else{
      header('Location: /index');
      exit();
    }
    return $u;
  }

  function userExists($conn, $u) {
    $one = "1";
    $sql = "SELECT * FROM users WHERE username=? AND activated=? LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $u, $one);
    $stmt->execute();
    $stmt->store_result();
    $stmt->fetch();
    $numrows = $stmt->num_rows;

    if($numrows < 1){
      header('location: /usernotexist');
      exit();
    }
  }

  function isOwner($u, $log_username, $user_ok) {
    $isOwner = "No";
    if($u == $log_username && $user_ok == true){
      $isOwner = "Yes";
    }
    return $isOwner;
  }

  function validateUser($user_ok, $log_username) {
    if($user_ok != true || $log_username == ""){
      header('Location: /index');
      exit();
    }
  }

  function numOfFriends($conn, $user) {
    $one = "1";
    $sql = "SELECT COUNT(id) FROM friends WHERE (user1 = ? OR user2 = ?) AND accepted = ?";
		$stmt = $conn->prepare($sql);
		$stmt->bind_param("sss", $user, $user, $one);
		$stmt->execute();
		$stmt->bind_result($numoffs);
		$stmt->fetch();
		$stmt->close();
    return $numoffs;
  } 
?>
