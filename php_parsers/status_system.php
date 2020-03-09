<?php
  require_once '../php_includes/check_login_statues.php';
  require_once '../php_includes/share_general.php';
  require_once '../php_includes/perform_checks.php';
  require_once '../php_includes/insertImage.php';
  require_once '../php_includes/del_general.php';
  require_once '../php_includes/wrapText.php';
  require_once '../php_includes/sentToFols.php';
  require_once '../php_includes/post_general.php';
  require_once '../safe_encrypt.php';
  require_once '../php_includes/ind.php';

  // Make sure file is not accessed directly
  if(!$user_ok || !$log_username) {
    exit();
  }

  class BdWish {
    public function __construct($conn, $type, $account_name, $data) {
      $this->type = mysqli_real_escape_string($conn, $type);
      $this->account_name = mysqli_real_escape_string($conn, $account_name);
      $this->data = htmlentities($data);
    }

    public function errorCheck($conn) {
      if(strlen($this->data) < 1 || !$this->account_name || $this->type != 'bd_wish') {
        mysqli_close($conn);
        echo "data or type is invalid";
        exit();
      }
    }
    
    public function setData($log_username) {
      $this->data = '
        <span style="color: red">
          '.$log_username.' wished a happy birthday to you and wrote this message:
          <img src="/images/bdcake.png" width="14" height="14" style="margin-bottom: -2px;">
        </span>
        <br>'.$this->data;
    }

    private function updateDb($conn) {
      $sql = "UPDATE status SET osid=? WHERE id=? LIMIT 1";
      $stmt = $conn->prepare($sql);
      $stmt->bind_param("ii", $this->id, $this->id);
      $stmt->execute();
      $stmt->close();
    }

    public function pushToDb($conn, $log_username) {
      $sql = "INSERT INTO status(account_name, author, type, data, postdate) 
        VALUES(?,?,?,?,NOW())";
      $stmt = $conn->prepare($sql);
      $stmt->bind_param("ssss", $this->account_name, $log_username, $this->type, $this->data);
      $stmt->execute();
      $stmt->close();
      $this->id = mysqli_insert_id($conn);
      $this->updateDb($conn);
    }
  }

  function shareArt($row) {
    global $hshkey, $wbauth;
    $written_by = $row["written_by"];
    $wb_original = $written_by;
    $title = $row["title"];
    $tags = $row["tags"];
    $post_time_ = $row["post_time"];
    $pt = base64url_encode($post_time_,$hshkey);
    $posttime = strftime("%b %d, %Y", strtotime($post_time_));
    $cat = $row["category"];
    $cover = chooseCover($cat);
    $wb_auth = $wb_original;
    
    $cover = preg_replace('/<img src="\/images\/\w+\/(\w+)\.jpg"\s+class="cover_art">/',
      "/images/art_cover/$1.jpg", $cover);
 
    /*
      $data is inserted to db directly -> bad integration with SQL, potential TODO
    */
    $data = '
      <div style="box-sizing: border-box; text-align: center; color: white; background-color: #282828; border-radius: 20px; font-size: 16px; margin-top: 40px; padding: 5px;"><p>Shared article via <a href="/user/'.$wb_original.'/">'.$written_by.'</a></p></div><hr class="dim"><a href="/articles/'.$pt.'/'.$wb_original.'"><div class="genBg lazy-bg shareImg" style="display: block; height: 300px; margin: 0 auto; border-radius: 20px;" data-src=\''.$cover.'\'></div></a><div class="txtc">
        <b style="font-size: 14px;">Title: </b>'.$title.'
        <b style="font-size: 14px;">Published: </b>'.$posttime.'
        <b style="font-size: 14px;">Category: </b>'.$cat.'</div>
    ';
    return $data;
  }

  function sharePhot($row) {
      global $photUname, $photFname;
      $user = $row["user"];
      $user_ori = $user;
      $gallery = $row["gallery"];
      $filename = $row["filename"];
      $des = wrapText($row["description"], 60);
      if (!$des) {
        $des = 'No description given';
      }
      $uploaddate_ = $row["uploaddate"];
      $ud = strftime("%b %d, %Y", strtotime($uploaddate_));

      $photUname = $user_ori;
      $photFname = $filename;

      $data = '<div style="box-sizing: border-box; text-align: center; color: white; background-color: #282828; border-radius: 20px; font-size: 16px; margin-top: 40px; padding: 5px;"><p>Shared photo via <a href="/user/'.$user_ori.'/">'.$user.'</a></p></div><hr class="dim"><a href="/photo_zoom/'.$user_ori.'/'.$filename.'"><div class="genBg lazy-bg shareImg" style="display: block; height: 300px; margin: 0 auto; border-radius: 20px;" data-src="/user/'.$user_ori.'/'.$filename.'"></div></a><br><div style="text-align: center;"><b style="font-size: 14px;">Gallery: </b>'.$gallery.'<br /><b style="font-size: 14px;">Published: </b>'.$ud.'<br /><b style="font-size: 14px;">Description: </b>'.$des.'</div>';
      return $data;
  }

  function shareVid($row) {
    global $hshkey, $vidId;
    $user = $row["user"];
    $id = $row["id"];
    $user_ori = $user;
    $video_name = $row["video_name"];
    $video_description = $row["video_description"];
    $video_poster = $row["video_poster"];
    $video_file = $row["video_file"];
    $uploaddate = $row["video_upload"];
    $vup = strftime("%b %d, %Y", strtotime($uploaddate));
    if(!$video_name){
      $video_name = "Untitled";
    }

    if(!$video_description){
      $video_description = "No description given";
    }
    
    $video_description = wrapText($video_description, 60);

    if(!$video_poster){
      $video_poster = 'images/defaultimage.png';
    }else{
      $video_poster = '/user/'.$user_ori.'/videos/'.$video_poster;
    }
    $id = base64url_encode($id, $hshkey);
    $vidId = $id;
    $data = '<div style="box-sizing: border-box; text-align: center; color: white; background-color: #282828; border-radius: 20px; font-size: 16px; margin-top: 40px; padding: 5px;"><p>Shared video via <a href="/user/'.$user_ori.'/">'.$user.'</a></p></div><hr class="dim">';
    $data .= '<a href="/video_zoom/'.$id.'"><div class="genBg lazy-bg shareImg" style="display: block; height: 300px; margin: 0 auto; border-radius: 20px;" data-src=\''.$video_poster.'\'></div></a><br />';
    $data .= '<div class="txtc"><b style="font-size: 14px;">Title: </b>'.$video_name.'<br />';
    $data .= '<b style="font-size: 14px;">Description: </b>'.$video_description.'<br />';
    $data .= '<b style="font-size: 14px;">Published: </b>'.$vup.'</div>';
    return $data;
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
    $sql = "INSERT INTO status(account_name, author, type, data, postdate) 
        VALUES(?,?,?,?,NOW())";
    $statPost->pushToDb($conn, $sql, 'ssss', $statPost->account_name, $log_username,
      $statPost->type, $statPost->data);

    $sql = "UPDATE status SET osid=? WHERE id=? LIMIT 1";
    $statPost->updateId($conn, $sql, 'ii', $statPost->id, $statPost->id);

    // Insert notifications to all friends of the post author
    $sendPost = new SendToFols($conn, $log_username, $log_username);

    $app = "Status Post <img src='/images/post.png' class='notfimg'>";
    $note = $log_username.' posted on '.$statPost->account_name.'&#39;s profile: <br />
      <a href="/user/'.$statPost->account_name.'/#status_'.$statPost->id.'">Check it now</a>';

    $sendPost->sendNotif($log_username, $app, $note, $conn);

    mysqli_close($conn);
    echo "post_ok|$statPost->id";
    exit();
  }

  if (isset($_POST['action']) && $_POST['action'] == "bd_wish"){
    $bdWish = new BdWish($conn, $_POST['type'], $_POST['bduser'], $_POST['data']);

    // Make sure post data and image is not empty
    $bdWish->errorCheck($conn);

    // Append static msg
    $bdWish->setData($log_username);

    // Make sure account name exists (the profile being posted on)
    userExists($conn, $bdWish->account_name);

    // Insert the status post into the database now
    $bdWish->pushToDb($conn, $log_username);

    // Insert notifications to all friends of the post author
    $sendPost = new SendToFols($conn, $log_username, $log_username);

    $app = "Birthday Wish <img src='/images/post.png' class='notfimg'>";
    $note = $log_username.' posted on '.$bdWish->account_name.'&#39;s birthday: <br />
      <a href="/user/'.$bdWish->account_name.'/#status_'.$bdWish->id.'">Check it now</a>';

    $sendPost->sendNotif($log_username, $app, $note, $conn);

    mysqli_close($conn);
    echo "bdsent_ok";
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

    // Only image or only text or both
    $replyPost->setData();

    // Make sure account name exists (the profile being posted on)
    userExists($conn, $replyPost->account_name);

    // Insert the status reply post into the database now
    $sql = "INSERT INTO status(osid, account_name, author, type, data, postdate)
            VALUES(?,?,?,?,?,NOW())";
    $replyPost->pushToDb($conn, $sql, 'issss', $replyPost->osid, $replyPost->account_name,
      $log_username, 'b', $replyPost->data);

    // Insert the status post into the database now
    $sendReply = new SendToFols($conn, $log_username, $log_username);

    $app = "Status Reply <img src='/images/reply.png' class='notfimg'>";
    $note = $log_username.' commented on '.$replyPost->account_name.'&#39;s profile: <br />
      <a href="/user/'.$replyPost->account_name.'/#reply_'.$replyPost->id.'">Check it now</a>';

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
    $sql = "SELECT account_name, author, data FROM status WHERE id=? LIMIT 1";
    $delStat->userOwnsComment($conn, $sql, 'i', $delStat->statusid);
    if ($delStat->author == $log_username || $delStat->account_name == $log_username) {
      $delStat->checkForImg();

      // Delete status
      $sql = "DELETE FROM status WHERE osid=?";
      $delStat->delComment($conn, $sql, 'i', $delStat->statusid);
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
    $sql = "SELECT osid, account_name, author FROM status WHERE id=? LIMIT 1";
    $delReply->userOwnsComment($conn, $sql, 'i', $delReply->statusid);
    if ($delReply->author == $log_username || $delReply->account_name == $log_username) {
      $delReply->checkForImg();

      // Delete reply
      $sql = "DELETE FROM status WHERE id=?";
      $delReply->delComment($conn, $sql, 'i', $delReply->statusid);
    }

    mysqli_close($conn);
    echo "delete_ok";
    exit();
  }

  if(isset($_POST['action']) && $_POST['action'] == "share"){
    $shareComm = new ShareComment($_POST['id']);

    // Check if id is set and valid
    $shareComm->checkId($conn);

    // Make sure post exists in db
    $sql = "SELECT author, data FROM status WHERE id=? LIMIT 1";
    $shareComm->postExists($conn, $sql, 'i', $shareComm->id);

    $sql = "SELECT * FROM status WHERE id=? LIMIT 1";
    $shareComm->insertToDb($conn, $sql, 'i', $log_username, $shareComm->id);

    // Send notif
    $sendReply = new SendToFols($conn, $log_username, $log_username);

    $app = "Status Share <img src='/images/black_share.png' class='notfimg'>";
    $note = $log_username.' posted on '.$replyPost->account_name.'&#39;s profile: <br />
      <a href="/user/'.$replyPost->account_name.'/#status_'.$replyPost->id.'">Check it now</a>';

    $sendReply->sendNotif($log_username, $app, $note, $conn);

    mysqli_close($conn);
    echo "share_ok";
    exit();
  }

  if(isset($_POST['action']) && $_POST['action'] == "share_art"){
    $shareComm = new ShareComment($_POST['id'], "shareArt");

    // Check if id is set and valid
    $shareComm->checkId($conn);

    // Make sure post exists in db
    $sql = "SELECT id FROM articles WHERE id=? LIMIT 1";
    $shareComm->postExists($conn, $sql, 'i', $shareComm->id);

    $sql = "SELECT * FROM articles WHERE id=? LIMIT 1";
    $shareComm->insertToDb($conn, $sql, 'i', $log_username, $shareComm->id);

    // Send notif
    $sendReply = new SendToFols($conn, $log_username, $log_username);

    $ptime = getPostTime($conn, $shareComm->id);

    $app = "Article Share <img src='/images/black_share.png' class='notfimg'>";
    $note = $log_username.' shared an article: <br />
      <a href="/articles/'.$ptime.'/'.$wb_auth.'">Check it now</a>';

    $sendReply->sendNotif($log_username, $app, $note, $conn);

    mysqli_close($conn);
    echo "share_art_ok";
    exit();
  }

  if(isset($_POST['action']) && $_POST['action'] == "share_photo"){
    $shareComm = new ShareComment($_POST['id'], "sharePhot");

    // Check if id is set and valid
    $shareComm->checkId($conn);

    // Make sure post exists in db
    $sql = "SELECT id FROM photos WHERE id=? LIMIT 1";
    $shareComm->postExists($conn, $sql, 'i', $shareComm->id);

    $sql = "SELECT * FROM photos WHERE id=? LIMIT 1";
    $shareComm->insertToDb($conn, $sql, 'i', $log_username, $shareComm->id);

    // Insert notifications to all friends of the post author
    $sendReply = new SendToFols($conn, $log_username, $log_username);

    $app = "Shared Photo <img src='/images/black_share.png' class='notfimg'>";
    $note = $log_username.' shared a photo.<br />
      <a href="/photo_zoom/'.$photUname.'/'.$photFname.'">Check it now</a>';

    $sendReply->sendNotif($log_username, $app, $note, $conn);

    mysqli_close($conn);
    echo "share_photo_ok";
    exit();
  }

  if(isset($_POST['action']) && $_POST['action'] == "share_video"){
    $shareComm = new ShareComment($_POST['id'], "shareVid");

    // Check if id is set and valid
    $shareComm->checkId($conn);

    // Make sure vid exists
    $sql = "SELECT id FROM videos WHERE id=? LIMIT 1";
    $shareComm->postExists($conn, $sql, 'i', $shareComm->id);

    // Share vid
    $sql = "SELECT * FROM videos WHERE id=? LIMIT 1";
    $shareComm->insertToDb($conn, $sql, 'i', $log_username, $shareComm->id);

    // Insert notifications to all friends of the post author
    $sendReply = new SendToFols($conn, $log_username, $log_username);

    $app = "Shared Video <img src='/images/black_share.png' class='notfimg'>";
    $note = $log_username.' shared a video.<br />
      <a href="/video_zoom/'.$vidId.'">Check it now</a>';

    $sendReply->sendNotif($log_username, $app, $note, $conn);

    mysqli_close($conn);
    echo "share_video_ok";
    exit();
  }
?>
