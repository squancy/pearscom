<?php
  require_once '../php_includes/check_login_statues.php';
  require_once '../php_includes/insertImage.php';
  require_once '../php_includes/perform_checks.php';
  require_once '../php_includes/sentToFols.php';
  require_once '../php_includes/like_common.php';
  require_once '../safe_encrypt.php';
  require_once '../tupl.php';

  function checkVidExists($conn, $id) {
    $sql = "SELECT * FROM videos WHERE id=? LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $id);
    $stmt->execute();
    $stmt->store_result();
    $stmt->fetch();
    $numrows = $stmt->num_rows;
    $stmt->close();
    if($numrows < 1){
      echo "Video does not exist";
      exit();
    }
  }


  function getVidId($conn, $db_file_name, $log_username) {
    global $hshkey;
    $sql = "SELECT * FROM videos WHERE video_file = ? AND user = ? LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $db_file_name, $log_username);
    $stmt->execute();
    $res = $stmt->get_result();
    if($row = $res->fetch_assoc()){
      $vidid = $row["id"];
    }
    return base64url_encode($vidid, $hshkey);
  }

  class DeleteVid {
    public function __construct($id, $hshkey) {
      $this->id = preg_replace('/\D/', '', $id);
    }

    public function deleteVideo($conn) {
      $sql = "DELETE FROM videos WHERE id=? LIMIT 1";
      $stmt = $conn->prepare($sql);
      $stmt->bind_param("s", $this->id);
      $stmt->execute();
      $stmt->close();
    }
  }

  if(isset($_FILES["stPic_video"]["name"]) && $_FILES["stPic_video"]["tmp_name"]){
    $videoUpload = new ManageImage("stPic_video", $log_username);

    // Check duration
    $dur = $_POST["stVideo_dur"];
    if(!$dur){
      echo "Could not get video duration";
      exit();
    }

    $description = NULL;
    $videoname = NULL;
    if($_POST["stVideo_des"]){
      $description = mysqli_real_escape_string($conn, $_POST["stVideo_des"]);
    }

    if($_POST["stVideo_name"]){
      $videoname = mysqli_real_escape_string($conn, $_POST["stVideo_name"]);
    }
  
    // Check if there is any thumbnail image
    if(isset($_FILES["stPic_poster"]["name"]) && $_FILES["stPic_poster"]["tmp_name"] != ""){
      $posterUpload = new ManageImage("stPic_poster", $log_username);
      $posterUpload->checkErrors();
    }

    // Create videos dir if not present
    if (!file_exists("../user/$log_username/videos")) {
      mkdir("../user/$log_username/videos", 0755);
    }

    $videoUpload->moveRes("../user/$log_username/videos/$videoUpload->db_file_name");

    if($posterUpload->db_file_name){
      $loc = "../user/$log_username/videos/$posterUpload->db_file_name";
      $posterUpload->moveRes($loc);
      $posterUpload->resizeImg(1920, 1080, $loc, $loc);
    }

    $sql = "INSERT INTO videos(user, video_name, video_description, video_poster, video_file,
      video_upload, dur) VALUES (?,?,?,?,?,NOW(),?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssss", $log_username, $videoname, $description,
      $posterUpload->db_file_name, $videoUpload->db_file_name, $dur);
    $stmt->execute();
    $stmt->close();
  
    // Insert notifications to all friends of the post author
    $sendPost = new SendToFols($conn, $log_username, $log_username);

    $vidid = getVidId($conn, $videoUpload->db_file_name, $log_username);

    $app = "New Video Uploaded <img src='/images/nvideo.png' class='notfimg'>";
    $note = $log_username.' uploaded a new video: <br />
      <a href="/video_zoom/'.$vidid.'">Check it now</a>';

    $sendPost->sendNotif($log_username, $app, $note, $conn);

    mysqli_close($conn);
    echo "upload_complete|";
    exit();
  }

  // Video delete
  if(isset($_POST["id"]) && $_POST["id"] && $_POST["type"] == "delete"){
    $delVid = new DeleteVid($_POST['id'], $hshkey);

    // Check to see if the video exists in the database
    checkVidExists($conn, $delVid->id);

    // Delete video
    $delVid->deleteVideo($conn);

    echo "delete_success";
    exit();
  }

  if(isset($_POST['type']) && isset($_POST['id'])){
    $vidLike = new LikeGeneral($conn, base64url_decode($_POST['id'], $hshkey), NULL);

    // Make sure user exists in db
    userExists($conn, $log_username);

    // Make sure vid exists
    checkVidExists($conn, $vidLike->p1);

    if($_POST['type'] == "like"){
      // Check if already liked
      $sql = "SELECT COUNT(id) FROM video_likes WHERE username=? AND video=? LIMIT 1";
      $row_count1 = $vidLike->checkIfLiked($conn, $sql, 'ss', $log_username, $vidLike->p1);

      if($row_count1){
        echo "You have already liked it";
        exit();
      }else{
        $sql = "INSERT INTO video_likes(username, video, like_time)
            VALUES (?,?,NOW())";
        $vidLike->manageDb($conn, $sql, 'ss', $log_username, $vidLike->p1);

        // Insert notifications to all friends of the post author
        $sendPost = new SendToFols($conn, $log_username, $log_username);

        $app = "Video Like <img src='/images/likeb.png' class='notfimg'>";
        $note = $log_username.' liked a video: <br />
          <a href="/video_zoom/'.$vidLike->p1.'">Check it now</a>';

        $sendPost->sendNotif($log_username, $app, $note, $conn);

        mysqli_close($conn);
        echo "like_success";
        exit();
      }
    }else if($_POST['type'] == "unlike"){
      // Make sure already liked
      $sql = "SELECT COUNT(id) FROM video_likes WHERE username=? AND video=? LIMIT 1";
      $row_count1 = $vidLike->checkIfLiked($conn, $sql, 'ss', $log_username, $vidLike->p1);

      if($row_count1){
        // Delete like
        $sql = "DELETE FROM video_likes WHERE username=? AND video=? LIMIT 1";
        $vidLike->manageDb($conn, $sql, 'ss', $log_username, $vidLike->p1);

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
