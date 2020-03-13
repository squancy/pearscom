<?php
  require_once '../php_includes/check_login_statues.php';
  require_once '../php_includes/perform_checks.php';
  require_once '../php_includes/sentToFols.php';
  require_once '../php_includes/index_fire.php';
  require_once '../php_includes/like_common.php';
  require_once '../safe_encrypt.php';

  if(!$user_ok || !$log_username) {
    exit();
  }

  $parid = "";
  if(isset($_POST["arid"]) && $_POST["arid"] != ""){
    $parid = mysqli_real_escape_string($conn, $_POST["arid"]);
  }else if(isset($_SESSION['id']) && !empty($_SESSION['id'])){
    $parid = $_SESSION["id"];
  }

  if(isset($_POST['type']) && isset($_POST['id'])){
    $artLike = new LikeGeneral($conn, preg_replace('#[^0-9]#i', '', $_POST['id']), NULL);

    // Make sure user exists in db
    userExists($conn, $log_username);

    if (!$parid) {
      // Fired from index.php like so get the photo file name from status id
      $sql = "SELECT artid FROM article_status WHERE id=? LIMIT 1";
      $parid = indFire($conn, $sql, $artLike->p1);
    }

    if($_POST['type'] == "like"){
      $sql = "SELECT COUNT(id) FROM art_reply_likes WHERE username=? AND reply=? AND artid=?
        LIMIT 1";
      $row_count1 = $artLike->checkIfLiked($conn, $sql, 'sii', $log_username, $artLike->p1,
        $parid);

      if($row_count1){
        echo "You have already liked it";
        exit();
      }else{
        $sql = "INSERT INTO art_reply_likes(username, reply, artid, like_time)
            VALUES (?,?,?,NOW())";
        $artLike->manageDb($conn, $sql, 'sii', $log_username, $artLike->p1, $parid);

        // Insert notifications to all friends of the post author
        $sendNotif = new SendToFols($conn, $log_username, $log_username);

        $sql = "SELECT post_time FROM articles WHERE id = ? LIMIT 1";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $parid);
        $stmt->execute();
        $stmt->bind_result($ptime);
        $stmt->fetch();
        $stmt->close();

        $app = "Article Reply Like <img src='/images/reply.png' class='notfimg'>";
        $note = $log_username.' liked a reply on an article: <br />
          <a href="/articles/'.$ptime.'/'.$log_username.'#status_'.$artLike->p1.'">
            Check it now</a>';

        $sendNotif->sendNotif($log_username, $app, $note, $conn);

        mysqli_close($conn);
        echo "like_reply_success";
        exit();
      }
    }else if($_POST['type'] == "unlike"){
      $sql = "SELECT COUNT(id) FROM art_reply_likes WHERE username=? AND reply=? AND artid=?
        LIMIT 1";
      $row_count1 = $artLike->checkIfLiked($conn, $sql, 'sii', $log_username, $artLike->p1,
        $parid);

      if($row_count1){
        // Del from db
        $sql = "DELETE FROM art_reply_likes WHERE username=? AND reply=? AND artid=? LIMIT 1";
        $artLike->manageDb($conn, $sql, 'sii', $log_username, $artLike->p1, $parid);

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
