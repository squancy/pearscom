<?php
  /*
    Perform authorization and authentication of users. Required on most pages.
  */

  function isLoggedIn($user_ok, $log_username) {
    if($user_ok != true || $log_username == ""){
      header('Location: /index');
      exit();
    }
  }

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
    if($u == $log_username && $user_ok){
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

  function getUserAvatar($conn, $u) {
    $one = '1';
    $sql = "SELECT avatar FROM users WHERE username=? AND activated=? LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $u, $one);
    $stmt->execute();
    $stmt->bind_result($avatar);
    $stmt->fetch();
    $stmt->close();

    $profile_pic = '/user/'.$u.'/'.$avatar;

    if($avatar == NULL){
      $profile_pic = '/images/avdef.png';
    }
    return $profile_pic;
  }

  function isBlocked($conn, $log_username, $u) {
    global $user_ok;
    $isBlock = false;
    if($user_ok){
      $block_check = "SELECT id FROM blockedusers WHERE blockee=? AND blocker=?";
      $stmt = $conn->prepare($block_check);
      $stmt->bind_param("ss", $log_username, $u);
      $stmt->execute();
      $stmt->store_result();
      $stmt->fetch();
      $numrows = $stmt->num_rows;
      if($numrows > 0){
        $isBlock = true;
      }
      $stmt->close();
    }
    return $isBlock;
  }

  function cleanStr($str) {
    // replace new lines with <br>
    $str = preg_replace("/\r\n/", "<br>", $str);
    $str = str_replace("\\n", '<br>', $str);
    $str = str_replace('\\\'', '&#39;', $str);
    $str = str_replace('\\\'', '&#34;', $str);
    return $str;
  }

  function getUsersFriends($conn, $u, $log_username,
    $defSQL = "SELECT user1, user2 FROM friends WHERE (user1 = ? OR user2 = ?) AND accepted=?") {
    $one = "1";
    $all_friends = array();
    $sql = $defSQL;
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sss", $u, $u, $one);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
      if($row["user1"] == $log_username) {
        $friend = $row["user2"];
      } else {
        $friend = $row["user1"];
      }
      array_push($all_friends, $friend);
    }
    $stmt->close();
    return $all_friends;
  }

  function getFollowers($conn, $curar, $u){
    $all_followers = array();
    $sql = "SELECT following FROM follow WHERE follower = ? AND following NOT IN('$curar')";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $u);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
      if ($row["following"] != $u) {
        array_push($all_followers, $row["following"]);
      }
    }
    $stmt->close();
    return $all_followers;
  }

  function getLatLon($conn, $log_username) {
    $sql = "SELECT lat, lon FROM users WHERE username = ? LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $log_username);
    $stmt->execute();
    $stmt->bind_result($lat, $lon);
    $stmt->fetch();
    $stmt->close();
    return [$lat, $lon];
  }

  function countComments($conn, $db, $param, $param1, ...$values) {
    $sql = "SELECT COUNT(id) FROM ".$db." WHERE ".$param." = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($param1, ...$values);
    $stmt->execute();
    $stmt->bind_result($all_count);
    $stmt->fetch();
    $stmt->close();
    return $all_count;
  }

  function cntLikesNew($conn, $param, $db, $field) {
    $sql = "SELECT COUNT(id) FROM ".$db." WHERE ".$field." = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $param);
    $stmt->execute();
    $stmt->bind_result($out_likes);
    $stmt->fetch();
    $stmt->close();
    return $out_likes;
  }

  function getFollowings($conn, $curar, $u, &$all_friends) {
    $sql = "SELECT following FROM follow WHERE follower = ? AND following NOT IN('$curar')";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $u);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
      if ($row["following"] != $u){
        array_push($all_friends, $row["following"]);
      }
    }
    $stmt->close();
  }

  function isFollow($conn, $log_username, $u) {
    global $user_ok;
    if($u != $log_username && $user_ok){
      $follow_check = "SELECT id FROM follow WHERE follower=? AND following=? LIMIT 1";
      $stmt = $conn->prepare($follow_check);
      $stmt->bind_param("ss",$log_username, $u);
      $stmt->execute();
      $stmt->store_result();
      $stmt->fetch();
      $numrows = $stmt->num_rows;
      $stmt->close();
      if($numrows > 0){
        return true;
      }
    }
    return false;
  }

  function getFols($conn, $log_username) {
    $followers = array();
    $sql = "SELECT * FROM follow WHERE following = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $log_username);
    $stmt->execute();
    $result = $stmt->get_result();
    while($row = $result->fetch_assoc()){
      array_push($followers, $row["follower"]);
    }
    $stmt->close();
    return $followers;
  }

  function countFols($conn, $u, $db) {
    $sql = "SELECT COUNT(id) FROM follow WHERE ".$db."=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $u);
    $stmt->execute();
    $stmt->bind_result($follower_count);
    $stmt->fetch();
    $stmt->close();
    return $follower_count;
  }

  function getPostTime($conn, $ar) {
    $sql = "SELECT post_time FROM articles WHERE id = ? LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $ar);
    $stmt->execute();
    $stmt->bind_result($ptime);
    $stmt->fetch();
    $stmt->close();
    return $ptime;
  }
?>
