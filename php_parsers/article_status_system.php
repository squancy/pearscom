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

  // Set article id
  $ar = "";
  if(isset($_POST["arid"]) && $_POST["arid"] != ""){
    $ar = $_POST["arid"];
  }else if(isset($_SESSION["id"]) && !empty($_SESSION["id"])){
    $ar = $_SESSION["id"];
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
    $sql = "INSERT INTO article_status(account_name, author, type, data, artid, postdate) 
      VALUES(?,?,?,?,?,NOW())";
    $statPost->pushToDb($conn, $sql, 'ssssi', $statPost->account_name, $log_username,
      $statPost->type, $statPost->data, $ar);

    $sql = "UPDATE article_status SET osid=? WHERE id=? AND artid = ? LIMIT 1";
    $statPost->updateId($conn, $sql, 'iii', $statPost->id, $statPost->id, $ar);

    // Insert notifications to all friends of the post author
    $sendPost = new SendToFols($conn, $log_username, $log_username);

    $ptime = getPostTime($conn, $ar);

    $app = "Article Status Post <img src='/images/post.png' class='notfimg'>";
    $note = $log_username.' posted on: <br />
      <a href="/articles/'.$ptime.'/'.$log_username.'/#status_'.$statPost->id.'">
        Below an article</a>';

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

    if (!$ar) {
      $ar = indexId($conn, $replyPost->osid, "article_status", "artid");
    }

    // Only image or only text or both
    $replyPost->setData();

    // Make sure account name exists (the profile being posted on)
    userExists($conn, $replyPost->account_name);

    // Insert reply to db
    $sql = "INSERT INTO article_status(osid, account_name, author, type, data, artid,
      postdate) VALUES(?,?,?,?,?,?,NOW())";
    $replyPost->pushToDb($conn, $sql, 'issssi', $replyPost->osid, $replyPost->account_name,
      $log_username, 'b', $replyPost->data, $ar);
    
    // Send notif about reply
    $sendReply = new SendToFols($conn, $log_username, $log_username);

    $ptime = getPostTime($conn, $ar);

    $app = "Article Status Reply <img src='/images/reply.png' class='notfimg'>";
    $note = $log_username.' posted on: <br />
      <a href="/articles/'.$ptime.'/'.$log_username.'/#status_'.$replyPost->osid.'">
        Below an article</a>';

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
    $sql = "SELECT account_name, author, data FROM article_status WHERE id=? AND artid=?
      LIMIT 1";
    $delStat->userOwnsComment($conn, $sql, 'ii', $delStat->statusid, $ar);
    if ($delStat->author == $log_username || $delStat->account_name == $log_username) {
      $delStat->checkForImg();

      // Delete status
      $sql = "DELETE FROM article_status WHERE osid=? AND artid = ?";
      $delStat->delComment($conn, $sql, 'ii', $delStat->statusid, $ar);
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
    $sql = "SELECT osid, account_name, author FROM article_status WHERE id=? AND artid = ?
      LIMIT 1";
    $delReply->userOwnsComment($conn, $sql, 'ii', $delReply->statusid, $ar);
    if ($delReply->author == $log_username || $delReply->account_name == $log_username) {
      $delReply->checkForImg();

      // Delete reply
      $sql = "DELETE FROM article_status WHERE id=? AND artid = ?";
      $delReply->delComment($conn, $sql, 'ii', $delReply->statusid, $ar);
    }

    mysqli_close($conn);
    echo "delete_ok";
    exit();
  }

  if(isset($_POST['action']) && $_POST['action'] == "share"){
    $shareComm = new ShareComment($_POST['id']);

    // Check if id is set and valid
    $shareComm->checkId($conn);

    if ($ar == "") {
      $ar = indexId($conn, $id, "article_status", "artid");
    }

    // Make sure post exists in db
    $sql = "SELECT author, data FROM article_status WHERE id=? LIMIT 1";
    $shareComm->postExists($conn, $sql, 'i', $shareComm->id);

    // Insert share to db
    $sql = "SELECT * FROM article_status WHERE id=? LIMIT 1";
    $shareComm->insertToDb($conn, $sql, 'i', $log_username, $shareComm->id);

    mysqli_close($conn);
    echo "share_ok";
    exit();
  }
?>
