<?php
  require_once '../php_includes/check_login_statues.php';
  require_once '../php_includes/perform_checks.php';
  require_once '../php_includes/sentToFols.php';

  if(!$user_ok || !$log_username){
    exit();
  }

  class FriendHandle {
    public function __construct($conn, $user) {
      $this->user = mysqli_real_escape_string($conn, $user);
    }

    public function checkFriend($conn) {
      $one = '1';
      $sql = "SELECT COUNT(id) FROM friends WHERE user1=? AND accepted=? OR user2=? AND
        accepted=?";
      $stmt = $conn->prepare($sql);
      $stmt->bind_param("ssss", $this->user, $one, $this->user, $one);
      $stmt->execute();
      $stmt->bind_result($friend_count);
      $stmt->fetch();
      $stmt->close();
      return $friend_count;
    }

    public function blockCheck($conn, $log_username, $user) {
      $sql = "SELECT COUNT(id) FROM blockedusers WHERE blocker=? AND blockee=? LIMIT 1";
      $stmt = $conn->prepare($sql);
      $stmt->bind_param("ss", $user, $log_username);
      $stmt->execute();
      $stmt->bind_result($blockcount1);
      $stmt->fetch();
      $stmt->close();
      return $blockcount1;
    }

    public function friendCheck($conn, $log_username, $user) {
      $one = '1';
      $sql = "SELECT COUNT(id) FROM friends WHERE user1=? AND user2=? AND accepted=? LIMIT 1";
      $stmt = $conn->prepare($sql);
      $stmt->bind_param("sss", $log_username, $user, $one);
      $stmt->execute();
      $stmt->bind_result($row_count1);
      $stmt->fetch();
      return $row_count1;
    }

    public function checkPending($conn, $log_username, $user) {
      $zero = '0';
      $sql = "SELECT COUNT(id) FROM friends WHERE user1=? AND user2=? AND accepted=? LIMIT 1";
      $stmt = $conn->prepare($sql);
      $stmt->bind_param("sss", $log_username, $user, $zero);
      $stmt->execute();
      $stmt->bind_result($row_count3);
      $stmt->fetch();
      return $row_count3;
    }

    public function insertToDb($conn, $log_username) {
      $sql = "INSERT INTO friends(user1, user2, datemade) VALUES(?,?,NOW())";
      $stmt = $conn->prepare($sql);
      $stmt->bind_param("ss", $log_username, $this->user);
      $stmt->execute();
      $stmt->close();
    }

    public function delFromDb($conn, $log_username, $user) {
      $one = '1';
      $sql = "DELETE FROM friends WHERE user1=? AND user2=? AND accepted=? LIMIT 1";
      $stmt = $conn->prepare($sql);
      $stmt->bind_param("sss", $log_username, $user, $one);
      $stmt->execute();
      $stmt->close();
    }
  }

  class FriendReq extends FriendHandle {
    public function __construct($conn, $reqid, $user) {
      $this->reqid = preg_replace('#[^0-9]#', '', $reqid);
      $this->user = mysqli_real_escape_string($conn, $user);
    }

    public function acceptReq($conn, $log_username) {
      $one = '1';
      $sql = "UPDATE friends SET accepted=? WHERE id=? AND user1=? AND user2=? LIMIT 1";
      $stmt =$conn->prepare($sql);
      $stmt->bind_param("ssss", $one, $this->reqid, $this->user, $log_username);
      $stmt->execute();
      $stmt->close();
    }

    public function rejectReq($conn, $log_username) {
      $zero = '0';
      $sql = "DELETE FROM friends WHERE id=? AND user1=? AND user2=? AND accepted=? LIMIT 1";
      $stmt = $conn->prepare($sql);
      $stmt->bind_param("ssss", $this->reqid, $this->user, $log_username, $zero);
      $stmt->execute();
      $stmt->close();
    }
  }

  if(isset($_POST['type']) && isset($_POST['user'])){
    $friendObj = new FriendHandle($conn, $_POST['user']);

    // Make sure the user exists in the database
    userExists($conn, $friendObj->user);

    if($_POST['type'] == "friend"){
      // Already friends?
      $friendObj->checkFriend($conn);
      
      // Check to see the user is not blocking me
      $blockcount1 = $friendObj->blockCheck($conn, $friendObj->user, $log_username);

      // Check to see I am not blocking the user
      $blockcount2 = $friendObj->blockCheck($conn, $log_username, $friendObj->user);

      if($blockcount2 && !$blockcount1){
        mysqli_close($conn);
        echo "You must first unblock $user in order to friend with them.";
        exit();
      }

      // Check to see we're alerady friends
      $row_count1 = $friendObj->friendCheck($conn, $log_username, $friendObj->user);

      if ($row_count1 && !$blockcount2 && !$blockcount1) {
        mysqli_close($conn);
        echo "You are already friends with $user.";
        exit();
      }

      // Check to see we're already friends
      $row_count2 = $friendObj->friendCheck($conn, $friendObj->user, $log_username);
      if ($row_count2 && !$blockcount2 && !$row_count1 && !$blockcount1) {
        mysqli_close($conn);
        echo "You are already friends with $user.";
        exit();
      }

      // Check to see there is a pending friend request
      $row_count3 = $friendObj->checkPending($conn, $log_username, $friendObj->user);
      if ($row_count3 && !$blockcount2 && !$row_count1 && !$row_count2 && !$blockcount1) {
        mysqli_close($conn);
        echo "You have a pending friend request already sent to $user.";
        exit();
      }

      // Check to see there is a pending friend request
      $row_count4 = $friendObj->checkPending($conn, $friendObj->user, $log_username);

      if ($row_count4 && !$blockcount2 && !$row_count1 && !$row_count2 && !$row_count3 &&
        !$blockcount1) {
        mysqli_close($conn);
        echo "$user has requested to friend with you first. Check your friend requests.";
        exit();
      }

      if(!$row_count4 && !$blockcount2 && !$row_count1 && !$row_count2 && !$row_count3
        && !$blockcount1){
        // Insert into the database
        $friendObj->insertToDb($conn, $log_username);

        // Insert notifications to all friends of the post author
        $sendNotif = new SendToFols($conn, $log_username, $log_username);
    
        $app = "New Friend <img src='/images/nfri.png' class='notfimg'>";
        $note = $log_username.' and '.$friendObj->user.' are now friends<br />
          <a href="/user/'.$friendObj->user.'/">Check '.$friendObj->user.'&#39;s profile now
          </a>';

        $sendNotif->sendNotif($log_username, $app, $note, $conn);

        mysqli_close($conn);
        echo "friend_request_sent";
        exit();
      }
    } else if ($_POST['type'] == "unfriend"){
      // Analyze both incident
      $row_count1 = $friendObj->friendCheck($conn, $log_username, $friendObj->user);
      
      if ($row_count1) {
        // Delete friendship
        $friendObj->delFromDb($conn, $log_username, $friendObj->user);

        mysqli_close($conn);
        echo "unfriend_ok";
        exit();
      }

      $row_count2 = $friendObj->friendCheck($conn, $friendObj->user, $log_username);
      var_dump($row_count1);
      var_dump($row_count2);
      
      if ($row_count2 && !$row_count1) {
        // Del friendship
        $friendObj->delFromDb($conn, $friendObj->user, $log_username);

        mysqli_close($conn);
        echo "unfriend_ok";
        exit();
      } else if(!$row_count1 && !$row_count2){
        mysqli_close($conn);
        echo "No friendship could be found between your account and $user, therefore we cannot
          unfriend you.";
        exit();
      }
      exit();
    }
  }

  if (isset($_POST['action']) && isset($_POST['reqid']) && isset($_POST['user1'])){
    $fReq = new FriendReq($conn, $_POST['reqid'], $_POST['user']);

    // Make sure the user exists in the database
    userExists($conn, $fReq->user);

    if($_POST['action'] == "accept"){
      // Make sure they're not already friends
      $row_count1 = $friendObj->friendCheck($conn, $log_username, $fReq->user);
      if ($row_count1 > 0 && $row_count2 == 0) {
        mysqli_close($conn);
        echo "You are already friends with $fReq->user.";
        exit();
      }

      $row_count2 = $friendObj->friendCheck($conn, $fReq->user, $log_username);
      if ($row_count2 && !$row_count1) {  
        mysqli_close($conn);
        echo "You are already friends with $fReq->user.";
        exit();
      }

      if(!$row_count2 && !$row_count1) {
        $fReq->acceptReq($conn, $log_username);
        mysqli_close($conn);
        echo "accept_ok";
        exit();
      }
    } else if($_POST['action'] == "reject"){
      $fReq->rejectReq($conn, $log_username);
      mysqli_close($conn);
      echo "reject_ok";
      exit();
    }
  }
?>
