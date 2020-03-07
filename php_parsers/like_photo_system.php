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

  $ph = "";

  if(isset($_POST["phot"]) && $_POST["phot"] != ""){
    $ph = mysqli_real_escape_string($conn, $_POST["phot"]);
  }else if(isset($_SESSION["photo"]) && !empty($_SESSION["photo"])){
    $ph = $_SESSION["photo"];   
  }

  if(isset($_POST['type']) && isset($_POST['id'])){
    $photLike = new LikeGeneral($conn, preg_replace('#[^0-9]#i', '', $_POST['id']), NULL);

    // Make sure user exists in db
    userExists($conn, $log_username);

    if (!$ph) {
      // Fired from index.php like so get the photo file name from status id
      $sql = "SELECT photo FROM photos_status WHERE id=? LIMIT 1";
      $ph = indFire($conn, $sql, $photLike->p1);
    }

    if($_POST['type'] == "like"){
      $sql = "SELECT COUNT(id) FROM photo_stat_likes WHERE username=? AND status=? AND photo=?
        LIMIT 1";
      $row_count1 = $photLike->checkIfLiked($conn, $sql, 'sis', $log_username, $photLike->p1,
        $ph);

      if($row_count1){
        echo "You have already liked it";
        exit();
      }else{
        // Insert to db
        $sql = "INSERT INTO photo_stat_likes(username, status, photo, like_time)
            VALUES (?,?,?,NOW())";
        $photLike->manageDb($conn, $sql, 'sis', $log_username, $photLike->p1, $ph);

        // Insert notifications to all friends of the post author
        $sendNotif = new SendToFols($conn, $log_username, $log_username);
        
        $phh = base64url_encode($ph, $hshkey);
        $app = "Liked Status Photo <img src='/images/likeb.png' class='notfimg'>";
        $note = $log_username.' liked a status: <br />
          <a href="/photo_zoom/'.$phh.'/'.$log_username.'">Below a photo</a>';

        $sendNotif->sendNotif($log_username, $app, $note, $conn);

        mysqli_close($conn);
        echo "like_success";
        exit();
      }
    }else if($_POST['type'] == "unlike"){
      $sql = "SELECT COUNT(id) FROM photo_stat_likes WHERE username=? AND status=? AND photo=?
        LIMIT 1";
      $row_count1 = $photLike->checkIfLiked($conn, $sql, 'sis', $log_username, $photLike->p1,
        $ph);

      if($row_count1){
        $sql = "DELETE FROM photo_stat_likes WHERE username=? AND status=? AND photo=? LIMIT 1";
        $photLike->manageDb($conn, $sql, 'sis', $log_username, $photLike->p1, $ph);

        mysqli_close($conn);
        echo "unlike_success";
        exit();
      }else{
        mysqli_close($conn);
        echo "You do not like this post";
        exit();
      }
    }
  }
?>
