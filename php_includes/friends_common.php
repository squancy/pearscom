<?php
  /*
    Commonly used functions & classes used in the friend suggestion system
  */

  function isAccepted($conn, $log_username, $unamerq) {
    $sql = "SELECT accepted FROM friends WHERE user1 = ? AND user2 = ? OR user1 = ? AND
      user2 = ? LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssss", $log_username, $unamerq, $unamerq, $log_username);
    $stmt->execute();
    $stmt->bind_result($zeroone);
    $stmt->fetch();
    $stmt->close();
    return $zeroone;
  }

  function isMoMo($ordtype, $moMoFriends) {
    if(isset($ordtype) && $moMoFriends == ""){
      echo "
        <p style='color: #999; text-align: center;'>
          Sorry, there are no friend suggestions in this category
        </p>
      ";
      exit();
    }else if(isset($ordtype) && $moMoFriends != ""){
      echo $moMoFriends;
      exit();
    }
  }

  function genUserBox($row, $conn) {
    $avatar = $row["avatar"];
    $country = $row["country"];
    $gender = $row["gender"];
    $uname = $row["username"];
    $unameori = urlencode($uname);
    $unamerq = $row["username"];
   
    $uname = wrapText($uname, 20);
    $country = wrapText($country, 20);
    $gender = wrapText($gender, 20);

    $online = $row["online"];
    if($online == "yes"){
      $online = "border: 2px solid #00a1ff";
    }else{
      $online = "border: 2px solid #999";
    }
    
    $zeroone = isAccepted($conn, $log_username, $unamerq);

    $pcurl = "/user/".$unamerq."/".$avatar;

    if($zeroone == '0'){
      $friend_btn = "<p style='color: #999; margin-right: 5px;'>Friend request sent</p>";
    }else if($zeroone == NULL || $zeroone == ""){
      $friend_btn = '
        <span id="friendBtn_'.$unamerq.'">
          <button onclick="friendToggle(\'friend\', \''.$unamerq.'\', \'friendBtn_'.$unamerq.'\')"
            class="main_btn_fill" style="border: 0; border-radius: 20px; padding: 7px;
            margin-top: 5px;">
            Request as friend
          </button>
        </span>
      ';
    }

    $pcurl = avatarImg($unamerq, $avatar);

    return '
      <div>
        <a href="/user/'.$unameori.'/">
          <div data-src=\''.$pcurl.'\' style="background-repeat: no-repeat;
            background-size: cover; background-position: center; width: 70px; height: 70px;
            float: right; display: inline-block; border-radius: 50%; '.$online.'"
            class="lazy-bg">
          </div>
        </a>
        <p>
          <a href="/user/'.$unameori.'/">
            '.$uname.'
          </a>
        </p>
        <p>
          '.$country.'
        </p>
        '.$friend_btn.'
      </div>
    ';
  }
?>
