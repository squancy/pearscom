<?php
  require_once '../php_includes/check_login_statues.php';
  require_once '../php_includes/perform_checks.php';
  require_once '../php_includes/sentToFols.php';

  if(!$user_ok || !$log_username) {
    exit();
  }

  class LikeGeneral {
    public function __construct($conn, $p1, $p2) {
      $this->p1 = preg_replace('#[^0-9]#i', '', $p1);
      $this->p2 = mysqli_real_escape_string($conn, $p2);
    }

    public function checkIfLiked($conn, $sql, $param1, ...$values) {
      $stmt = $conn->prepare($sql);
      $stmt->bind_param($param1, ...$values);
      $stmt->execute();
      $stmt->bind_result($row_count1);
      $stmt->fetch();
      return $row_count1;
    }

    public function manageDb($conn, $sql, $param1, ...$values) {
      $stmt = $conn->prepare($sql);
      $stmt->bind_param($param1, ...$values);
      $stmt->execute();
      $stmt->close();
    }
  }

  if(isset($_POST['type']) && isset($_POST['id']) && isset($_POST['group'])){
    $grLike = new LikeGeneral($conn, $_POST['id'], $_POST['group']);

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
