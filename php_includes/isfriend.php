<?php
  /*
    Check whether 2 users are friends or not. Used by many pages.
  */

  function isFriend($u, $log_username, $user_ok, $conn) {
    $one = "1";
    $isFriend = false;
    if($u != $log_username && $user_ok){
      $friend_check = "SELECT id FROM friends WHERE user1=? AND user2=? AND accepted=?
        OR user1=? AND user2=? AND accepted=? LIMIT 1";
      $stmt = $conn->prepare($friend_check);
      $stmt->bind_param("ssssss", $log_username, $u, $one, $u, $log_username, $one);
      $stmt->execute();
      $stmt->store_result();
      $stmt->fetch();
      $numrows = $stmt->num_rows;
      if($numrows > 0){
        $isFriend = true;
      }
      $stmt->close();
    }
    return $isFriend;
  }
?>
