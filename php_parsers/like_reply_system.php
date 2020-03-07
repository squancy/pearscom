<?php
  require_once '../php_includes/check_login_statues.php';
  require_once '../php_includes/perform_checks.php';
  require_once '../php_includes/sentToFols.php';
  require_once '../php_includes/like_common.php';

  if(!$user_ok || !$log_username) {
    exit();
  }

  if(isset($_POST['type']) && isset($_POST['id'])){
    $statLike = new LikeGeneral($conn, preg_replace('#[^0-9]#i', '', $_POST['id']), NULL);

    // Make sure user exists in db
    userExists($conn, $log_username);

    if($_POST['type'] == "like"){
      $sql = "SELECT COUNT(id) FROM reply_likes WHERE username=? AND reply=? LIMIT 1";
      $row_count1 = $statLike->checkIfLiked($conn, $sql, 'si', $log_username, $statLike->p1);

      if($row_count1){
        echo "You have already liked it";
        exit();
      }else{
        // Insert into db
        $sql = "INSERT INTO reply_likes(username, reply, like_time)
            VALUES (?,?,NOW())";
        $statLike->manageDb($conn, $sql, 'si', $log_username, $statLike->p1);

         // Insert notifications to all friends of the post author
        $sendNotif = new SendToFols($conn, $log_username, $log_username);

        // Get account name
        $sql = "SELECT * FROM status WHERE id = ? LIMIT 1";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $statLike->p1);
        $stmt->execute();
        $res = $stmt->get_result();
        if($row = $res->fetch_assoc()){
          $accname = $row["account_name"];
        }
        $stmt->close();

        $app = "Status Reply Like <img src='/images/likeb.png' class='notfimg'>";
        $note = $log_username.' liked a reply on: <br />
          <a href="/user/'.$accname.'#reply_'.$sendNotif->p1.'/">Check it now</a>';

        $sendNotif->sendNotif($log_username, $app, $note, $conn);

        mysqli_close($conn);
        echo "like_reply_success";
        exit();
      }
    }else if($_POST['type'] == "unlike"){
      // Check if already liked
      $sql = "SELECT COUNT(id) FROM reply_likes WHERE username=? AND reply=? LIMIT 1";
      $row_count1 = $statLike->checkIfLiked($conn, $sql, 'si', $log_username, $statLike->p1);

      if($row_count1){
        // Del from db
        $sql = "DELETE FROM reply_likes WHERE username=? AND reply=? LIMIT 1";
        $statLike->manageDb($conn, $sql, 'si', $log_username, $statLike->p1);

        mysqli_close($conn);
        echo "unlike_reply_success";
        exit();
      }else{
        mysqli_close($conn);
        echo "You do not like this post";
        exit();
      }
    }
  }
?>
