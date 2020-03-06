<?php
  require_once '../php_includes/check_login_statues.php';
  require_once '../php_includes/perform_checks.php';
  require_once '../php_includes/sentToFols.php';
  require_once '../php_includes/like_common.php';

  if(!$user_ok || !$log_username) {
    exit();
  }

  if(isset($_POST['type']) && isset($_POST['id']) && isset($_POST['group'])){
    $grLike = new LikeGeneral($conn, preg_replace('#[^0-9]#i', '', $_POST['id']),
      mysqli_real_escape_string($conn, $_POST['group']));

    // Make sure user exists in db
    userExists($conn, $log_username);

    if ($_POST['type'] == 'like') {
      // Check if user liked the post
      $sql = "SELECT COUNT(id) FROM group_status_likes WHERE username=? AND gpost=? AND
        gname = ? LIMIT 1";
      $row_count1 = $grLike->checkIfLiked($conn, $sql, 'sis', $log_username, $grLike->p1,
        $grLike->p2);

      if($row_count1){
        echo "You have already liked it";
        exit();
      }else{
        // Insert to db
        $sql = "INSERT INTO group_status_likes(username, gpost, gname, like_time)
          VALUES (?,?,?,NOW())";
        $grLike->manageDb($conn, $sql, 'sis', $log_username, $grLike->p1, $grLike->p2);

        // Insert notifications to all friends of the post author
        $sendNotif = new SendToFols($conn, $log_username, $log_username);
        
        $app = "Group Status Like <img src='/images/likeb.png' class='notfimg'>";
        $note = $log_username.' liked a post on '.$grLike->p2.' group: <br />
          <a href="/group/'.$grLike->p2.'#status_'.$grLike->p1.'">Check it now</a>';

        $sendNotif->sendNotif($log_username, $app, $note, $conn);

        mysqli_close($conn);
        echo "like_success";
        exit();
      }
    }else if($_POST['type'] == "unlike"){
      // Check if already liked
      $sql = "SELECT COUNT(id) FROM group_status_likes WHERE username=? AND gpost=?
        AND gname = ? LIMIT 1";
      $row_count1 = $grLike->checkIfLiked($conn, $sql, 'sis', $log_username, $grLike->p1,
        $grLike->p2);

      if($row_count1){
        // Delete from db
        $sql = "DELETE FROM group_status_likes WHERE username=? AND gpost=? AND gname = ?
          LIMIT 1";
        $grLike->manageDb($conn, $sql, 'sis', $log_username, $grLike->p1, $grLike->p2);

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
