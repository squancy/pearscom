<?php
  // Protect this script from direct url access
  require_once '../php_includes/check_login_statues.php';
  require_once '../php_includes/perform_checks.php';
  require_once '../php_includes/insertImage.php';
  require_once '../php_includes/post_general.php';

  if(!$user_ok || !$log_username) {
    exit();
  }

  $one = "1";
  $zero = "0";

  class PMHandler extends PostGeneral{
    public function __construct($conn, $fuser, $tuser, $data, $data2, $img) {
      $this->fuser = mysqli_real_escape_string($conn, $fuser);
      $this->tuser = mysqli_real_escape_string($conn, $tuser);
      $this->data = htmlentities($data);
      $this->data2 = htmlentities($data2);
      $this->image = $img;
    }

    public function dataEmpty($conn) {
      if(strlen($this->data) < 1 || strlen($this->data2) < 1){
        mysqli_close($conn);
        echo "data_empty";
        exit();
      }
    }

    public function isYourself($log_username) {
      if ($log_username == $sendPM->tuser){
        echo "cannot_message_self";
        exit();
      }
    }

    public function insertToDb($conn) {
      $defaultP = "x";
      $sql = "INSERT INTO pm(receiver, sender, senttime, subject, message, parent) 
          VALUES(?,?,NOW(),?,?,?)";
      $stmt = $conn->prepare($sql);
      $stmt->bind_param("sssss", $this->tuser, $this->fuser, $this->data2,
        $this->data, $defaultP);
      $stmt->execute();
      $stmt->close();
    }
  }

  class ReplyHandler extends PMHandler {
    public function __construct($conn, $osid, $account_name, $osender, $data, $image) {
      $this->osid = preg_replace('#[^0-9]#', '', $osid);
      $this->account_name = mysqli_real_escape_string($conn, $account_name);
      $this->osender = mysqli_real_escape_string($conn, $osender);
      $this->data = $data;
      $this->data2 = $this->data;
      $this->image = mysqli_real_escape_string($conn, $image);
    }
    
    public function insertToDb($conn) {
      $x = "x";
      $sql = "INSERT INTO pm(receiver, sender, senttime, subject, message, parent)
              VALUES(?,?,NOW(),?,?,?)";
      $stmt = $conn->prepare($sql);
      $stmt->bind_param("ssssi", $x, $this->account_name, $x, $this->data, $this->osid);
      $stmt->execute();
      $stmt->close();
      $this->id = mysqli_insert_id($conn);
    }

    public function updateDb($conn, $sql, $first = true) {
      $one = '1';
      $zero = '0';
      $stmt = $conn->prepare($sql);
      if ($first) {
        $stmt->bind_param("sssi", $one, $one, $zero, $this->osid);
      } else {
        $stmt->bind_param("sssi", $one, $zero, $one, $this->osid);
      }
      $stmt->execute();
      $stmt->close();
    }
  }

  class PMAction {
    public function __construct($conn, $pmid, $orig) {
      $this->pmid = preg_replace('#[^0-9]#', '', $pmid);
      $this->originator = mysqli_real_escape_string($conn, $orig);
    }

    public function checkEmpty($conn) {
      if (!$this->pmid || !$this->originator) {
        mysqli_close($conn);
        echo "originator or pmid is missing";
        exit();
      }
    }

    public function performPM($conn, $sql) {
      $one = '1';
      $stmt = $conn->prepare($sql);
      $stmt->bind_param("si", $one, $this->pmid);
      $stmt->execute();
      $stmt->close();
    }
  }
  
  class DelMsg {
    public function __construct($conn, $pmid, $uname, $stime) {
      $this->pmid = preg_replace('#[^0-9]#', '', $pmid);
      $this->uname = mysqli_real_escape_string($conn, $uname);
      $this->stime = $stime;
    }

    public function deleteMsg($conn) {
      $sql = "DELETE FROM pm WHERE sender = ? AND senttime = ?";
      $stmt = $conn->prepare($sql);
      $stmt->bind_param("ss", $this->uname, $this->stime);
      $stmt->execute();
      $stmt->close();
    }
  }

  // New PM
  if (isset($_POST['action']) && $_POST['action'] == "new_pm"){
    $sendPM = new PMHandler($conn, $_POST['fuser'], $_POST['tuser'], $_POST['data'],
      $_POST['data2'], $_POST['image']);

    // Make sure post data is not empty
    $sendPM->dataEmpty($conn);
    
    // Move the image(s) to the permanent folder
    if($sendPM->image != "na"){
      $valImg = new InImage();
      $valImg->doInsert($sendPM->image);
    }

    // Img + text to data
    $sendPM->setData();
    
    // Make sure account name exists (the profile being posted on)
    userExists($conn, $sendPM->tuser);

    // No message to yourself
    $sendPM->isYourself($log_username);

    // Insert the status post into the database now
    $sendPM->insertToDb($conn);

    mysqli_close($conn);
    echo "pm_sent";
    exit();
  }

  // Reply To PM
  if (isset($_POST['action']) && $_POST['action'] == "pm_reply"){
    $replyPM = new ReplyHandler($conn, $_POST['pmid'], $_POST['user'], $_POST['osender'],
      $_POST['data'], $_POST['image']);

    // Make sure data is not empty
    $replyPM->dataEmpty($conn);

    // Make sure account name exists (the profile being posted on)
    userExists($conn, $replyPM->account_name);

    // Move the image(s) to the permanent folder
    if($replyPM->image != "na"){
      $valImg = new InImage();
      $valImg->doInsert($replyPM->image);
    }

    // Img + text
    $replyPM->setData();

    // Insert the pm reply post into the database now
    $replyPM->insertToDb($conn);
   
    // Update db + notif
    $sql = "UPDATE pm SET hasreplies=?, rread=?, sread=? WHERE id=? LIMIT 1";
    if ($log_username != $replyPM->osender){
      $replyPM->updateDb($conn, $sql);
    } else {
      $replyPM->updateDb($conn, $sql, false);
    }

    mysqli_close($conn);
    echo "reply_ok|$replyPM->id";
    exit();
  }

  // Delete PM
  if (isset($_POST['action']) && $_POST['action'] == "delete_pm"){
    $delPM = new PMAction($conn, $_POST['pmid'], $_POST['originator']);

    // Error check
    $delPM->checkEmpty($conn);

    // Del PM
    $sql = "UPDATE pm SET sdelete=? WHERE id=? LIMIT 1";
    $delPM->performPM($conn, $sql);

    mysqli_close($conn);
    echo "delete_ok";
    exit();
  }

  // Mark As Read
  if (isset($_POST['action']) && $_POST['action'] == "mark_as_read"){
    $markRead = new PMAction($conn, $_POST['pmid'], $_POST['originator']);

    // Error check
    $markRead->checkEmpty($conn);

    // Mark PM as read
    $sql = "UPDATE pm SET mread=? WHERE id=? LIMIT 1";
    $markRead->performPM($conn, $sql);

    mysqli_close($conn);
    echo "read_ok";
    exit();
  }

  if (isset($_POST['action']) && $_POST['action'] == "deletemessage"){
    $delMsg = new DelMsg($conn, $_POST['pmid'], $_POST['uname'], $_POST['stime']);

    // Make sure user exists in db
    userExists($conn, $log_username);

    // Del msg
    $delMsg->deleteMsg($conn);

    echo "deletemessage_ok";
    exit();
  }
?>
