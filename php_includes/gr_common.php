<?php
  /*
    Common functions and tools that are used on group pages
  */

  function genGrBox($row) {
    require_once 'timeelapsedstring.php';
    require_once 'ccovg.php';

    $logo = $row["logo"];
    $groupname = $row["name"];
    $gnameori = $groupname;
    $gnameori = urlencode($gnameori);
    $gnameim = $groupname;
    $est = $row["creation"];
    $creatorMy = $row["creator"];
    $est_ = strftime("%R, %b %d, %Y", strtotime($est));
    $agoform = time_elapsed_string($est);
    $des = $row["des"];

    if($logo != "gdef.png"){
      $logo = '/groups/'.$gnameim.'/'.$logo.'';
    }else{
      $logo = '/images/gdef.png';
    }

    if($des == NULL || $des == ""){
      $des = "not given";
    }

    $des = str_replace('\n', ' ', $des);

    $cat = $row["cat"];
    $cat = chooseCat($cat);

    return '
      <a href="/group/'.$gnameori.'">
        <div class="article_echo_2" style="height: auto; width: 100%;">
          <div data-src=\''.$logo.'\' style="background-repeat: no-repeat;
            background-position: center; background-size: cover; width: 80px; height: 80px;
            float: right; border-radius: 50%;" class="lazy-bg">
          </div>
        <div>

        <p class="title_">
          <b>Name: </b>'.$groupname.'
        </p>

        <p class="title_">
          <b>Creator: </b>'.$creatorMy.'
        </p>

        <p class="title_">
          <b>Established: </b>'.$agoform.' ago
        </p>

        <p class="title_">
          <b>Description: </b>'.$des.'
        </p>

        <p class="title_">
          <b>Category: </b>'.$cat.'
        </p>
      </div>
    </div>
    </a>
    '; 
  }
  
  function isApproved($app, &$pending, &$approved, $mName) {
    switch($app){
      case 0:
        array_push($pending, $mName);
      break;

      case 1:
        array_push($approved, $mName);
      break;
    }
  }

  function cntLikes($conn, $id, $g, $db) {
    $sql = "SELECT COUNT(id) FROM ".$db." WHERE gpost = ? AND gname = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("is", $id, $g);
    $stmt->execute();
    $stmt->bind_result($count);
    $stmt->fetch();
    $stmt->close();
    return $count;
  }

  function cntTypes($conn, $g, $type) {
    $sql = "SELECT COUNT(id) FROM grouppost WHERE gname = ? AND type = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $g, $type);
    $stmt->execute();
    $stmt->bind_result($cnt);
    $stmt->fetch();
    $stmt->close();
    return $cnt;
  }

  function cntRecords($conn, $g) {
    $sql = "SELECT COUNT(id) FROM grouppost WHERE gname = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $g);
    $stmt->execute();
    $stmt->bind_result($record_count);
    $stmt->fetch();
    $stmt->close();
    return $record_count;
  }

  function cntReplies($conn, $g, $id) {
    $one = "1";
    $sql = "SELECT COUNT(id) FROM grouppost WHERE type = ? AND gname = ? AND pid = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssi", $one, $g, $id);
    $stmt->execute();
    $stmt->bind_result($count);
    $stmt->fetch();
    $stmt->close();
    return $count;
  }

  function genJoinBtn($all, $pending, $g) {
    if ((isset($_SESSION['username'])) && (!in_array($_SESSION['username'], $all))){
      return '
        <button id="joinBtn" class="main_btn_fill fixRed btnUimg"
          onclick="joinGroup(\''.$g.'\')">Join group</button>';
    }else if(in_array($_SESSION['username'], $pending)){
      return '
        <p style="font-size: 14px; color: #999;" class="btnUimg wfa">
          Waiting for approval
        </p>
      ';
    }
    return '';
  }

  function isAdmin($admin, $mName, &$moderators) {
    if($admin){
      if(!in_array($mName, $moderators)){
        array_push($moderators, $mName);
      }
    }
  }

  function chooseClass($moderators, $auth, $creator) {
    if($auth == $creator){
      return "grCreat";
    }else if(in_array($auth, $moderators)){
      return "grMod";
    }else{
      return "grMem";
    }
  }

  function isLiked($conn, $user_ok, $log_username, $post_id, $g, $db) {
    if($user_ok){
      $like_check = "SELECT id FROM ".$db." WHERE username=? AND gpost=?
        AND gname = ? LIMIT 1";
      $stmt = $conn->prepare($like_check);
      $stmt->bind_param("sis", $log_username, $post_id, $g);
      $stmt->execute();
      $stmt->store_result();
      $stmt->fetch();
      $numrows = $stmt->num_rows;
      if($numrows > 0){
        return true;
      }
      $stmt->close();
    }
    return false;
  }
?>
