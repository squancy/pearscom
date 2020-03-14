<?php
  require_once '../php_includes/check_login_statues.php';
  require_once '../php_includes/sentToFols.php';
  require_once '../php_includes/perform_checks.php';

  if(!$user_ok || !$log_username) {
    exit();
  }

  class FollowHandler {
    public function __construct($conn, $user) {
      $this->user = mysqli_real_escape_string($conn, $user);
    }

    public function insertToDb($conn, $log_username) {
      $sql = "INSERT INTO follow(follower, following, follow_time)
        VALUES (?,?,NOW())";
      $stmt = $conn->prepare($sql);
      $stmt->bind_param("ss", $log_username, $this->user);
      $stmt->execute();
      $stmt->close();
    }

    public function delFromDb($conn, $log_username) {
      $sql = "DELETE FROM follow WHERE follower=? AND following=? LIMIT 1";
      $stmt = $conn->prepare($sql);
      $stmt->bind_param("ss", $log_username, $this->user);
      $stmt->execute();
      $stmt->close();
    }
  } 

  if(isset($_POST['type']) && isset($_POST['user'])){
    $folObj = new FollowHandler($conn, $_POST['user']);

    // Make sure the user exists in the database
    userExists($conn, $folObj->user);

    if($_POST['type'] == "follow"){
      // Check to see if the user is alerady following
      $row_count1 = isFollow($conn, $log_username, $folObj->user);

      if($row_count1){
        echo "You are already following $user";
        exit();
      }else{
        // Insert into the database
        $folObj->insertToDb($conn, $log_username);

        // Insert notifications to all friends of the post author
        $sendNotif = new SendToFols($conn, $log_username, $log_username);

        $app = "New follower <img src='/images/nfol.png' class='notfimg'>";
        $note = $log_username.' started to follow '.$folObj->user.': <br />
          <a href="/user/'.$folObj->user.'/">Check it now</a>';

        $sendNotif->sendNotif($log_username, $app, $note, $conn);

        mysqli_close($conn);
        echo "follow_success";
        exit();
      }
    }else if($_POST['type'] == "unfollow"){
      // Check to see the user is the following or the follower
      $row_count1 = isFollow($conn, $folObj->user, $log_username);
      $row_count2 = isFollow($conn, $log_username, $folObj->user);

      if($row_count2 && !$row_count1){
        $folObj->delFromDb($conn, $log_username);

        mysqli_close($conn);
        echo "unfollow_success";
        exit();
      }else if(!$row_count1 && !$row_count2){
        mysqli_close($conn);
        echo "You do not follow this user";
        exit();
      }
    }
  }
?>
