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

  $p = "";
  if(isset($_POST["phot"]) && $_POST["phot"] != ""){
    $p = mysqli_real_escape_string($conn, $_POST["phot"]);
  }else if(isset($_SESSION['photo']) && !empty($_SESSION['photo'])){
    $p = $_SESSION["photo"];
  }

  $one = "1";
  $zero = "0";
  $a = "a";
  $b = "b";

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

    // Insert the status reply post into the database now
    $sql = "INSERT INTO photos_status(account_name, author, type, data, photo, postdate)
            VALUES(?,?,?,?,?,NOW())";
    $statPost->pushToDb($conn, $sql, 'sssss', $statPost->account_name, $log_username,
      $statPost->type, $statPost->data, $p);

    $sql = "UPDATE photos_status SET osid=? WHERE id=? LIMIT 1";
    $statPost->updateId($conn, $sql, 'ii', $statPost->id, $statPost->id);

    // Insert notifications to all friends of the post author
    $sendPost = new SendToFols($conn, $log_username, $log_username);

    $app = "Photo Status Post <img src='/images/post.png' class='notfimg'>";
    $note = $log_username.' posted on: <br />
      <a href="/photo_zoom/'.$p.'/'.$log_username.'/#status_'.$statPost->id.'">Check it now</a>';

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

    if (!$p) {
      $p = indexId($conn, $replyPost->osid, "photos_status", "photo");
    }

    // Only image or only text or both
    $replyPost->setData();
    
    // Make sure account name exists (the profile being posted on)
    userExists($conn, $replyPost->account_name);

    // Insert the status reply post into the database now
    $sql = "INSERT INTO photos_status(osid, account_name, author, type, data, photo, postdate)
            VALUES(?,?,?,?,?,?,NOW())";
    $replyPost->pushToDb($conn, $sql, 'isssss', $replyPost->osid, $replyPost->account_name,
      $log_username, 'b', $replyPost->data, $p);

    // Insert notifications for everybody in the conversation except this author
    $sendReply = new SendToFols($conn, $log_username, $log_username);

    $app = "Photo Status Post Reply <img src='/images/reply.png' class='notfimg'>";
    $note = $log_username.' commented below a photo: <br />
      <a href="/photo_zoom/'.$p.'/'.$log_username.'/#status_'.$replyPost->id.'">
        Check it now</a>';

    $sendReply->sendNotif($log_username, $app, $note, $conn);

    mysqli_close($conn);
    echo "reply_ok|$replyPost->id";
    exit();
  }

  if (isset($_POST['action']) && $_POST['action'] == "delete_status"){
    $delStat = new DeleteGeneral($_POST['statusid']);

    // Make sure id is not empty and set
    $delStat->checkEmptyId($conn);

    // Check to make sure this logged in user actually owns that comment
    $sql = "SELECT account_name, author, data FROM photos_status WHERE id=? AND photo = ?
      LIMIT 1";
    $delStat->userOwnsComment($conn, $sql, 'is', $delStat->statusid, $p);
    if ($delStat->author == $log_username || $delStat->account_name == $log_username) {
      // Check for images
      $delStat->checkForImg();

      // Delete status
      $sql = "DELETE FROM photos_status WHERE osid=? AND photo = ?";
      $delStat->delComment($conn, $sql, 'is', $delStat->statusid, $p);
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
    $sql = "SELECT osid, account_name, author FROM photos_status WHERE id=? AND photo = ?
      LIMIT 1";
    $delReply->userOwnsComment($conn, $sql, 'is', $delReply->statusid, $p);

    if ($delReply->author == $log_username || $delReply->account_name == $log_username) {
      $delReply->checkForImg();

      // Delete reply
      $sql = "DELETE FROM photos_status WHERE id=? AND photo = ?";
      $delReply->delComment($conn, $sql, 'is', $delReply->statusid, $p);
    }

    mysqli_close($conn);
    echo "delete_ok";
    exit();
  }

  if(isset($_POST['action']) && $_POST['action'] == "share"){
    $shareComm = new ShareComment($_POST['id']);

    // Check if id is set and valid
    $shareComm->checkId($conn);

    if (!$p) {
      $p = indexId($conn, $id, "photos_status", "photo");
    }

    $sql = "SELECT author, data FROM photos_status WHERE id=? AND photo = ? LIMIT 1";
    $shareComm->postExists($conn, $sql, 'is', $shareComm->id, $p);
    
    // Insert share to db
    $sql = "SELECT * FROM photos_status WHERE id=? AND photo = ? LIMIT 1";
    $shareComm->insertToDb($conn, $sql, 'is', $log_username, $shareComm->id, $p);

    mysqli_close($conn);
    echo "share_ok";
    exit();
  }
?>
