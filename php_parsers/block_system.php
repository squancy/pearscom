<?php
  // Check to see if the user is not logged in
  require_once '../php_includes/check_login_statues.php';
  require_once '../php_includes/perform_checks.php';
  require_once '../php_includes/isfriend.php';

  // Prevent script from direct access
  if($user_ok != true || !$log_username) {
    exit();
  }

  class BlockHandler {
    public function __construct($conn, $blockee) {
      $this->blockee = mysqli_real_escape_string($conn, $blockee);
    }

    public function alreadyBlocked($conn, $log_username) {
      $sql = "SELECT id FROM blockedusers WHERE blocker=? AND blockee=? LIMIT 1";
      $stmt = $conn->prepare($sql);
      $stmt->bind_param("ss", $log_username, $this->blockee);
      $stmt->execute();
      $stmt->store_result();
      $stmt->fetch();
      $numrows = $stmt->num_rows;
      $stmt->close();
      return $numrows;
    }

    public function insertBlock($conn, $log_username) {
      $sql = "INSERT INTO blockedusers(blocker, blockee, blockdate) VALUES(?,?,NOW())";
      $stmt = $conn->prepare($sql);
      $stmt->bind_param("ss", $log_username, $this->blockee);
      $stmt->execute();
      $stmt->close();
    }

    public function deleteFromFriends($conn, $log_username) {
      $one = '1';
      $sql = "DELETE FROM friends WHERE (user1=? AND user2=? AND accepted=?)
        OR (user1=? AND user2=? AND accepted=?) LIMIT 1";
      $stmt = $conn->prepare($sql);
      $stmt->bind_param("ssssss", $log_username, $this->blockee, $one, $this->blockee,
        $log_username, $one);
      $stmt->execute();
      $stmt->close();
    }

    public function delBlock($conn, $log_username) {
      $sql = "DELETE FROM blockedusers WHERE blocker=? AND blockee=? LIMIT 1";
      $stmt = $conn->prepare($sql);
      $stmt->bind_param("ss", $log_username, $this->blockee);
      $stmt->execute();
      $stmt->close();
    }
  }

  if (isset($_POST['type']) && isset($_POST['blockee'])){
    $blockObj = new BlockHandler($conn, $_POST['blockee']);
    // Make sure the blockee is exists in the database 
    userExists($conn, $blockObj->blockee);

    // Check to see if the user has already blocked the blockee
    $numrows = $blockObj->alreadyBlocked($conn, $log_username);

    // User wants to block someone
    if($_POST['type'] == "block"){
      if ($numrows > 0) {
        mysqli_close($conn);
        echo "You already have this member blocked.";
        exit();
      } else {
        // Insert into database
        $blockObj->insertBlock($conn, $log_username);

        // Check to see if the blockee is the user's friend
        $isFriend = isFriend($blockObj->blockee, $log_username, true, $conn);

        // If it is, first delete from friends
        if($isFriend){
          $blockObj->deleteFromFriends($conn, $log_username);
        }

        mysqli_close($conn);
        echo "blocked_ok";
        exit();
      }
    } else if($_POST['type'] == "unblock"){
      // Check to see if the user is not blocked before
      if (!$numrows) {
        mysqli_close($conn);
        echo "You do not have this user blocked, therefore we cannot unblock them.";
        exit();
      } else {
        // Delete from database
        $blockObj->delBlock($conn, $log_username);
        mysqli_close($conn);
        echo "unblocked_ok";
        exit();
      }
    }
  }
?>
