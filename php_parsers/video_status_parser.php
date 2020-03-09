<?php
  require_once '../php_includes/check_login_statues.php';
  require_once '../php_includes/share_general.php';
  require_once '../php_includes/perform_checks.php';
  require_once '../php_includes/insertImage.php';
  require_once '../php_includes/del_general.php';
  require_once '../php_includes/sentToFols.php';
  require_once '../php_includes/post_general.php';
  require_once '../safe_encrypt.php';
  require_once '../php_includes/ind.php';

  // Make sure file is not accessed directly
  if(!$user_ok || !$log_username) {
    exit();
  }

  $vi = "";
  if(isset($_POST["vid"]) && $_POST["vid"] != ""){
    $vi = mysqli_real_escape_string($conn, $_POST["vid"]);
  }else if(isset($_SESSION['id']) && !empty($_SESSION['id'])){
    $vi = $_SESSION["id"];
    $vi = base64url_decode($vi, $hshkey);
  }

  if (isset($_POST['action']) && $_POST['action'] == "status_post"){
    $statPost = new PostGeneral($_POST['type'], $_POST['user'], $_POST['data'],
      $_POST['image'], $conn);

    // Make sure posted data is not empty
    $statPost->checkForEmpty($conn);

    // Validate image, if any
    if($statPost->image != "na"){
      $valImg = new InImage();
      $valImg->doInsert($statPost->image);
    }

    // Make sure type is either a or c
    $statPost->typeCheck($conn);

    // Only image or only text or both
    $statPost->setData();

    // Make sure account name exists (the profile being posted on)
    userExists($conn, $statPost->account_name);

    // Insert the status post into the database now
    $sql = "INSERT INTO video_status(account_name, author, type, data, vidid, postdate) 
            VALUES(?,?,?,?,?,NOW())";
    $statPost->pushToDb($conn, $sql, 'ssssi', $statPost->account_name, $log_username,
      $statPost->type, $statPost->data, $vi);

    $sql = "UPDATE video_status SET osid=? WHERE id=? AND vidid = ? LIMIT 1";
    $statPost->updateId($conn, $sql, 'iii', $statPost->id, $statPost->id, $vi);

    // Insert notifications to all friends of the post author
    $sendPost = new SendToFols($conn, $log_username, $log_username);

    $app = "Video Status Post <img src='/images/post.png' class='notfimg'>";
    $note = $log_username.' posted below a video: <br />
      <a href="/video_zoom/'.$vi.'/#status_'.$statPost->id.'">Check it now</a>';

    $sendPost->sendNotif($log_username, $app, $note, $conn);

    mysqli_close($conn);
    echo "post_ok|$statPost->id";
    exit();
  }

  if (isset($_POST['action']) && $_POST['action'] == "status_reply"){
    $replyPost = new PostReply($_POST['sid'], $_POST['user'], $_POST['data'], $_POST['image'],
      $conn);

    // Make sure no empty post
    $replyPost->checkForEmpty($conn);

    // Validate image, if any
    if($replyPost->image != "na"){
      $valImg = new InImage();
      $valImg->doInsert($replyPost->image);
    }

    if (!$vi) {
      $vi = indexId($conn, $osid, "video_status", "vidid");
    }

    // Only image or only text or both
    $replyPost->setData();
    
    // Make sure account name exists (the profile being posted on)
    userExists($conn, $replyPost->account_name);

    // Insert the status reply post into the database now
    $sql = "INSERT INTO video_status(osid, account_name, author, type, data, vidid, postdate)
            VALUES(?,?,?,?,?,?,NOW())";
    $replyPost->pushToDb($conn, $sql, 'issssi', $replyPost->osid, $replyPost->account_name,
      $log_username, 'b', $replyPost->data, $vi);

    // Insert notifications to all friends of the post author
    $sendReply = new SendToFols($conn, $log_username, $log_username);

    $app = "Video Status Reply <img src='/images/reply.png' class='notfimg'>";
    $note = $log_username.' commented below a video: <br />
      <a href="/video_zoom/'.$vi.'/#reply_'.$replyPost->id.'">Check it now</a>';

    $sendReply->sendNotif($log_username, $app, $note, $conn);

    mysqli_close($conn);
    echo "reply_ok|$id";
    exit();
  }

  if (isset($_POST['action']) && $_POST['action'] == "delete_status"){
    $delStat = new DeleteGeneral($_POST['statusid']);

    // Make sure id is not empty and set
    $delStat->checkEmptyId($conn);

    // Check to make sure this logged in user actually owns that comment
    $sql = "SELECT account_name, author, data FROM video_status WHERE id=? AND vidid = ?
      LIMIT 1";
    $delStat->userOwnsComment($conn, $sql, 'ii', $delStat->statusid, $vi);
    if ($delStat->author == $log_username || $delStat->account_name == $log_username) {
      // Check for images
      $delStat->checkForImg();

      // Delete status
      $sql = "DELETE FROM video_status WHERE osid=? AND vidid = ?";
      $delStat->delComment($conn, $sql, 'ii', $delStat->statusid, $vi);
    }

    mysqli_close($conn);
    echo "delete_ok";
    exit();
  }

  if (isset($_POST['action']) && $_POST['action'] == "delete_reply"){
    $delReply = new DeleteGeneral($_POST['replyid']);

    // Make sure id is not empty and set
    $delReply->checkEmptyId($conn);

    // Check to make sure this logged in user actually owns that comment
    $sql = "SELECT osid, account_name, author FROM video_status WHERE id=? AND vidid = ?
      LIMIT 1";
    $delReply->userOwnsComment($conn, $sql, 'ii', $delReply->statusid, $vi);

    if ($delReply->author == $log_username || $delReply->account_name == $log_username) {
      $delReply->checkForImg();

      // Delete reply
      $sql = "DELETE FROM video_status WHERE id=? AND vidid = ?";
      $delReply->delComment($conn, $sql, 'ii', $delReply->statusid, $vi);
    }

    mysqli_close($conn);
    echo "delete_ok";
    exit();
  }

  if(isset($_POST['action']) && $_POST['action'] == "share"){
    $shareComm = new ShareComment($_POST['id']);

    // Check if id is set and valid
    $shareComm->checkId($conn);

    if (!$vi) {
      // Fired from index.php like so get the photo file name from status id
      $vi = indexId($conn, $id, "video_status", "vidid");
    }

    $sql = "SELECT author, data FROM video_status WHERE id=? AND vidid = ? LIMIT 1";
    $shareComm->postExists($conn, $sql, 'ii', $shareComm->id, $vi);

    // Insert share to db
    $sql = "SELECT * FROM video_status WHERE id=? AND vidid = ? LIMIT 1";
    $shareComm->insertToDb($conn, $sql, 'ii', $log_username, $shareComm->id, $vi);

    mysqli_close($conn);
    echo "share_ok";
    exit();
  }
?>
