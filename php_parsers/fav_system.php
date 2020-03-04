<?php
  // Check to see if the user is not logged in
  require_once '../php_includes/check_login_statues.php';
  require_once '../php_includes/perform_checks.php';
  require_once '../php_includes/sentToFols.php';

  if(!$user_ok || !$log_username) {
    exit();
  }

  class FollowHandler {
    public function __construct($conn, $p, $u) {
      $this->p = $p;
      $this->u = mysqli_real_escape_string($conn, $u);
    }

    public function alreadyFaved($conn, $log_username) {
      $sql = "SELECT COUNT(id) FROM fav_art WHERE username=? AND art_time=? AND art_uname=?
        LIMIT 1";
      $stmt = $conn->prepare($sql);
      $stmt->bind_param("sss", $log_username, $this->p, $this->u);
      $stmt->execute();
      $stmt->bind_result($row_count1);
      $stmt->fetch();
      return $row_count1;
    }

    public function insertFav($conn, $log_username) {
      $sql = "INSERT INTO fav_art(username, art_time, art_uname, fav_time)
          VALUES (?,?,?,NOW())";
      $stmt = $conn->prepare($sql);
      $stmt->bind_param("sss", $log_username, $this->p, $this->u);
      $stmt->execute();
      $stmt->close();
    }

    public function delFav($conn, $log_username) {
      $sql = "DELETE FROM fav_art WHERE username=? AND art_time=? AND art_uname=? LIMIT 1";
      $stmt = $conn->prepare($sql);
      $stmt->bind_param("sss", $log_username, $this->p, $this->u);
      $stmt->execute();
      $stmt->close();
    }
  }

  if(isset($_POST['type']) && isset($_POST['p']) && isset($_POST['u'])){
    $folObj = new FollowHandler($conn, $_POST['p'], $_POST['u']);
    
    // Make sure the user exists in the database
    userExists($conn, $log_username);

    if($_POST['type'] == "fav"){
      $row_count1 = $folObj->alreadyFaved($conn, $log_username);

      // Check to see the user has already liked it
      if($row_count1 > 0){
        echo "You have already added as a favourite";
        exit();
      }else{
        // Insert into database
        $folObj->insertFav($conn, $log_username);

        // Notif send
        $sendNotif = new SendToFols($conn, $log_username, $log_username);

        $app = "Favourite Article <img src='/images/lace.png' class='notfimg'>";
        $note = $log_username.' added an article as a favourite: <br />
          <a href="/articles/'.$ptime.'/'.$u.'">Check it now</a>';

        $sendNotif->sendNotif($log_username, $app, $note, $conn);

        mysqli_close($conn);
        echo "fav_success";
        exit();
      }
    }else if($_POST['type'] == "unfav"){
      // Make sure already faved
      $row_count1 = $folObj->alreadyFaved($conn, $log_username);

      if($row_count1 > 0){
        $folObj->delFav($conn, $log_username);
        mysqli_close($conn);
        echo "unfav_success";
        exit();
      }else{
        mysqli_close($conn);
        echo "You do not favourite this post";
        exit();
      }
    }
  }
?>
