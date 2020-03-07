<?php
  require_once '../php_includes/check_login_statues.php';
  require_once '../php_includes/perform_checks.php';
  require_once '../php_includes/sentToFols.php';
  require_once '../php_includes/like_common.php';

  if (!$user_ok || !$log_username) {
    exit();
  }

  if(isset($_POST['type']) && isset($_POST['p']) && isset($_POST['u'])){
    $heartLike = new LikeGeneral($conn, $_POST['p'],
      mysqli_real_escape_string($conn, $_POST["u"]));

    // Make sure user exists in db
    userExists($conn, $log_username);

    if($_POST['type'] == "heart"){
      $sql = "SELECT COUNT(id) FROM heart_likes WHERE username=? AND art_time=? AND art_uname=?
        LIMIT 1";
      $row_count1 = $heartLike->checkIfLiked($conn, $sql, 'sss', $log_username, $heartLike->p1,
        $heartLike->p2);

      if($row_count1){
        echo "You have already liked it";
        exit();
      }else{
        // Insert to db
        $sql = "INSERT INTO heart_likes(username, art_time, art_uname, like_time)
            VALUES (?,?,?,NOW())";
        $heartLike->manageDb($conn, $sql, 'sss', $log_username, $heartLike->p1,
          $heartLike->p2);

        // Insert notifications to all friends of the post author
        $sendNotif = new SendToFols($conn, $log_username, $log_username);
        
        $app = "Liked Article <img src='/images/likeb.png' class='notfimg'>";
        $note = $log_username.' liked an article: <br />
          <a href="/articles/'.$heartLike->p1.'/'.$log_username.'">Check it now</a>';

        $sendNotif->sendNotif($log_username, $app, $note, $conn);

        mysqli_close($conn);
        echo "heart_success";
        exit();
      }
    }else if($_POST['type'] == "unheart"){
      // Check if already liked
      $sql = "SELECT COUNT(id) FROM heart_likes WHERE username=? AND art_time=? AND art_uname=?
        LIMIT 1";
      $row_count1 = $heartLike->checkIfLiked($conn, $sql, 'sss', $log_username, $heartLike->p1,
        $heartLike->p2);

      if($row_count1){
        // Delete from db
        $sql = "DELETE FROM heart_likes WHERE username=? AND art_time=? AND art_uname=? LIMIT 1";
        $heartLike->manageDb($conn, $sql, 'sss', $log_username, $heartLike->p1,
          $heartLike->p2);

        mysqli_close($conn);
        echo "unheart_success";
        exit();
      }else{
        mysqli_close($conn);
        echo "You do not like this post";
        exit();
      }
    }
  }
?>
