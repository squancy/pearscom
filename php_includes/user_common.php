<?php
  // Common functions & classes used by user.php

  function checkGETParams($getw, $conn, $res, $clause) {
    $wart = NULL;
    if(isset($getw)){
      $wart = mysqli_real_escape_string($conn, $getw);
    }
    return $wart == $clause ? $res : '';
  }

  function genAgeString($bdor) {
    $age = floor((time() - strtotime($bdor)) / 31556926);
    if($age < 18){
      return [' (underage)', $age];
    }else{
      return [' (adult)', $age];
    }
  }

  function checkUserlevel($userlevel) {
    if($userlevel == "a"){
      return "Verified";
    }else if($userlevel == "b"){
      return "Not Verified";
    }else{
      return "Unauthorized";
    }
  }

  function checkBlockings($conn, $u, $log_username) {
    $block_check1 = "SELECT id FROM blockedusers WHERE blocker=? AND blockee=? LIMIT 1";
    $stmt = $conn->prepare($block_check1);
    $stmt->bind_param("ss", $u, $log_username);
    $stmt->execute();
    $stmt->store_result();
    $stmt->fetch();
    $numrows2 = $stmt->num_rows;
    $stmt->close();
    return $numrows2 > 0 ? true : false;
  }

  function genRightBox($row, $conn) {
    $friend_username = $row["username"];
    $friend_avatar = $row["avatar"];
    $fuco = $row["country"];
    $ison = $row["online"];
    $flat = $row["lat"];
    $flon = $row["lon"];

    // Get distance
    $dist = vincentyGreatCircleDistance($lat, $lon, $flat, $flon);

    // From miles to km
    $dist = round($dist * 1.609344);

    $isonimg = isOn($ison);
    $friend_pic = avatarImg($friend_username, $friend_avatar);

    $funames = wrapText($friend_username, 20);
    $fuco = wrapText($fuco, 20);
    
    $numoffs = numOfFriends($conn, $friend_username);

    return '
      <a href="/user/'.$friend_username.'/">
        <div data-src=\''.$friend_pic.'\' style="background-repeat: no-repeat;
          background-size: cover; background-position: center; width: 50px; height: 50px;
          display: inline-block;" class="friendpics lazy-bg">
        </div>
        <div class="infousrdiv">
          <div data-src=\''.$friend_pic.'\' style="background-repeat: no-repeat;
            background-size: cover; background-position: center; width: 60px; height: 60px;
            display: inline-block; float: left; border-radius: 50%;" class="lazy-bg">
          </div>
          <span style="float: left; margin-left: 2px;">
            <span style="color: red;">'.$funames.'</span>&nbsp;'.$isonimg.'<br>
            <img src="/images/pcountry.png" width="12" height="12">&nbsp;'.$fuco.'<br>
            <img src="/images/udist.png" width="12" height="12">&nbsp;Distance: '.$dist.' km<br>
            <img src="/images/fus.png" width="12" height="12">&nbsp;Friends: '.$numoffs.'
          </span>
        </div>
      </a>
    '; 
  }
?>
