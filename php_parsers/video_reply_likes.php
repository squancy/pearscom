<?php
  require_once "../php_includes/check_login_statues.php";
  require_once '../php_includes/perform_checks.php';
  require_once '../php_includes/index_fire.php';
  require_once '../php_includes/sentToFols.php';
  require_once '../php_includes/like_common.php';
  require_once '../safe_encrypt.php';

  if(!$user_ok || !$log_username) {
    exit();
  }
    
  $vi = "";
  if(isset($_POST["vi"]) && $_POST["vi"] != ""){
    $vi = mysqli_real_escape_string($conn, $_POST["vi"]);
  }else if(isset($_SESSION["id"]) && !empty($_SESSION["id"])){
    $vi = $_SESSION["id"];
    $vi = base64url_decode($vi, $hshkey);
  }

  if(isset($_POST['type']) && isset($_POST['id'])){
    $vLike = new LikeGeneral($conn, preg_replace('#[^0-9]#i', '', $_POST['id']), NULL);

    if (!$vi) {
      // Fired from index.php like so get the photo file name from status id
      $sql = "SELECT vidid FROM video_status WHERE id=? LIMIT 1";
      $vi = indFire($conn, $sql, $vLike->p1);
    }

    // Make sure user exists in db
    userExists($conn, $log_username);

    if($_POST['type'] == "like"){
      $sql = "SELECT COUNT(id) FROM video_reply_likes WHERE user=? AND video=? AND reply=?
        LIMIT 1";
      $row_count1 = $vLike->checkIfLiked($conn, $sql, 'sii', $log_username, $vLike->p1,
        $vi);

      if($row_count1){
        echo "You have already liked it";
        exit();
      }else{
        // Insert to db
        $sql = "INSERT INTO video_reply_likes(user, video, reply, like_time)
            VALUES (?,?,?,NOW())";
        $vLike->manageDb($conn, $sql, 'sii', $log_username, $vLike->p1, $vi);

        // Insert notifications to all friends of the post author
        $sendNotif = new SendToFols($conn, $log_username, $log_username);
        
        $vii = base64url_encode($vi, $hshkey);
        $app = "Video Reply Like <img src='/images/likeb.png' class='notfimg'>";
        $note = $log_username.' liked a video reply: <br />
          <a href="/video_zoom/'.$vii.'">Check it now</a>';

        $sendNotif->sendNotif($log_username, $app, $note, $conn);

        mysqli_close($conn);
        echo "like_reply_success";
        exit();
      }
    }else if($_POST['type'] == "unlike"){
      $sql = "SELECT COUNT(id) FROM video_reply_likes WHERE user=? AND video=? AND reply = ?
        LIMIT 1";
      $row_count1 = $vLike->checkIfLiked($conn, $sql, 'sii', $log_username, $vLike->p1, $vi);

      if($row_count1){
        $sql = "DELETE FROM video_reply_likes WHERE user=? AND video=? AND reply = ? LIMIT 1";
        $vLike->manageDb($conn, $sql, 'sii', $log_username, $vLike->p1, $vi);

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
