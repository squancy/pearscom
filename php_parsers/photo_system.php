<?php
  require_once '../php_includes/check_login_statues.php';
  require_once '../safe_encrypt.php';
  require_once '../tupl.php';
  require_once '../timeelapsedstring.php';
  require_once '../php_includes/file_handlers.php';
  require_once '../php_includes/insertImage.php';
  require_once '../php_includes/sentToFols.php';

  // Change orientation of img if rotated
  function change_orientation($path){
    $exif = exif_read_data($path);
    if(isset($exif['Orientation']) && $exif['Orientation'] != 1){
      $position = $exif['Orientation'];
      $degrees = "";
      if($position == "8"){
        $degrees = "90";
      }else if($position == "3"){
        $degrees = "180";
      }else if($position == "6"){
        $degrees = "-90";
      }

      if($degrees == "90" || $degrees == "180" || $degrees == "-90"){
        $source = imagecreatefromjpeg($path);
        $rotate = imagerotate($source, $degrees, 0);
        imagejpeg($rotate, realpath($path));
        imagedestroy($source);
        imagedestroy($rotate);
      }
    }
  }

  /*
    TODO: Whenever a new background or user avatar is uploaded also delete the old one from the
    server saving space
  */

  class AvatarImage extends ManageImage {
    public function delOldImg($conn, $sql, $log_username, $pcurl) {
      $stmt = $conn->prepare($sql);
      $stmt->bind_param("s", $log_username);
      $stmt->execute();
      $stmt->bind_result($img);
      $stmt->fetch();
      if ($avatar) {
        $pcurl .= $img; 
        if (file_exists($picurl)) { 
          unlink($picurl);
        }
      }
      $stmt->close();
    }  

    public function updateDb($conn, $log_username) {
      $sql = "UPDATE users SET avatar=? WHERE username=? LIMIT 1";
      $stmt = $conn->prepare($sql);
      $stmt->bind_param("ss", $this->db_file_name, $log_username);
      $stmt->execute();
      $stmt->close();
    }
  }

  class BgImage extends AvatarImage {
    public function updateDb($conn, $log_username) {
      $sql = "UPDATE useroptions SET background=? WHERE username=? LIMIT 1";
      $stmt = $conn->prepare($sql);
      $stmt->bind_param("ss", $this->db_file_name, $log_username);
      $stmt->execute();
      $stmt->close();
    }
  }

  class BgDefImage extends BgImage {
    public function __construct($imgtype, $log_username) {
      if($imgtype == "universe"){
        $this->fileTmpLoc = "../images/universebi.jpg";
        $this->fileName = "universebi.jpg";
      }else if($imgtype == "flowers"){
        $this->fileTmpLoc = "../images/flowersbi.jpeg";
        $this->fileName = "flowersbi.jpeg";
      }else if($imgtype == "forest"){
        $this->fileTmpLoc = "../images/forestbi.jpg";
        $this->fileName = "forestbi.jpg";
      }else if($imgtype == "bubbles"){
        $this->fileTmpLoc = "../images/bubblesbi.jpg";
        $this->fileName = "bubblesbi.jpg";
      }else if($imgtype == "mountains"){
        $this->fileTmpLoc = "../images/mountainsbi.jpeg";
        $this->fileName = "mountainsbi.jpeg";
      }else if($imgtype == "waves"){
        $this->fileTmpLoc = "../images/wavesbi.jpg";
        $this->fileName = "wavesbi.jpg";
      }else if($imgtype == "stones"){
        $this->fileTmpLoc = "../images/stonesbi.jpg";
        $this->fileName = "stonesbi.jpg";
      }else if($imgtype == "simple"){
        $this->fileTmpLoc = "../images/simplebi.jpg";
        $this->fileName = "simplebi.jpg";
      }
      $kaboom = explode(".",  $this->fileName);
      $this->fileExt = end($kaboom);
      $this->db_file_name = imgHash($log_username, $this->fileExt);
    }
  }

  class UploadImg extends ManageImage {
    public function __construct($cgal, $des) {
      $this->gallery = preg_replace('#[^a-z0-9 .-_]#i', '', $cgal);
      $this->description = $des;
    }

    public function checkDes() {
      if(strlen($this->description) > 1000){
        echo "You overstepped the maximum 1000 characters limit!";
        exit();
      }
    }

    public function checkGal() {
      $gals = ["Myself", "Friends", "Family", "Pets", "Friends", "Games", "Freetime", "Games",
        "Sports", "Knowledge", "Hobbies", "Working", "Relations", "Other"];
      if (!in_array($this->gallery, $gals)){
        echo "Please give a valid category|fail";
        exit();
      }
    }

    public function pushToDb($conn, $sql, $param1, ...$values) {
      $stmt = $conn->prepare($sql);
      $stmt->bind_param($param1, ...$values);
      $stmt->execute();
      $stmt->close();
    }
  }
  
  class DelPhoto {
    public function __construct($id) {
      $this->id = preg_replace('#[^0-9]#', '', $id);
    }

    public function getInfo($conn) {
      $sql = "SELECT user, filename FROM photos WHERE id=? LIMIT 1";
      $stmt = $conn->prepare($sql);
      $stmt->bind_param("i", $this->id);
      $stmt->execute();
      $result = $stmt->get_result();
      if($row = $result->fetch_assoc()){
        $this->user = $row["user"];
        $this->filename = $row["filename"];
      }
      $stmt->close();
    }

    public function deletePhoto($conn) {
      $sql = "DELETE FROM photos WHERE id=? LIMIT 1";
      $stmt = $conn->prepare($sql);
      $stmt->bind_param("i", $this->id);
      $stmt->execute();
      $stmt->close();
    }
  }

  function createBgDir($loc) {
    if (!file_exists($loc)) {
      mkdir($loc, 0755);
    }
  }

  // Upload user avatar
  if (isset($_FILES["avatar"]["name"]) && $_FILES["avatar"]["tmp_name"]){
    $avImg = new AvatarImage("avatar", $log_username);

    // Check for potential errors
    $avImg->checkErrors();

    // Delete old avatar img
    $sql = "SELECT avatar FROM users WHERE username=? LIMIT 1";
    $avImg->delOldImg($conn, $sql, $log_username, "../user/$log_username/");

    // Move new avatar to user's dir
    $loc = "../user/$log_username/$avImg->db_file_name";
    $avImg->moveRes($loc);

    // Resize uploaded img
    $avImg->resizeImg(650, 650, $loc, $loc);

    // Update to new avatar in db
    $avImg->updateDb($conn, $log_username);

    // Insert notifications to all friends of the post author
    $sendPost = new SendToFols($conn, $log_username, $log_username);

    $app = "New Profile Picture <img src='/images/ppc.png' class='notfimg'>";
    $note = $log_username.' changed his/her profile picture: <br />
      <a href="/user/'.$log_username.'/">Check it now</a>';

    $sendPost->sendNotif($log_username, $app, $note, $conn);

    mysqli_close($conn);
    header("location: ../user/$log_username/");
    exit();
  }

  // Upload custom user background
  if (isset($_FILES["background"]["name"]) && $_FILES["background"]["tmp_name"]){
    $bgImg = new BgImage("background", $log_username);

    // Check for errors
    $bgImg->checkErrors();

    // Create bg dir if not exists
    createBgDir("../user/$log_username/background");

    // Del old bg
    $sql = "SELECT background FROM useroptions WHERE username=? LIMIT 1";
    $bgImg->delOldImg($conn, $sql, $log_username,
      "../user/$log_username/background/");
    
    // Move new bg to permanent loc
    $loc = "../user/$log_username/background/$bgImg->db_file_name";
    $bgImg->moveRes($loc);

    // Resize uploaded img
    $bgImg->resizeImg(1980, 1050, $loc, $loc);

    // Update bg in db
    $bgImg->updateDb($conn, $log_username);

    // Insert notifications to all friends of the post author
    $sendPost = new SendToFols($conn, $log_username, $log_username);

    $app = "New Background <img src='/images/bgc.png' class='notfimg'>";
    $note = $log_username.' changed their background: <br />
      <a href="/user/'.$log_username.'/">Check it now</a>';

    $sendPost->sendNotif($log_username, $app, $note, $conn);

    mysqli_close($conn);
    header("location: ../user/$log_username/");
    exit();
  }

  if(isset($_POST["imgtype"]) && $_POST["imgtype"] != ""){
    $imgtype = $_POST['imgtype'];
    $defBg = new BgDefImage($imgtype, $log_username);

    // Create a bg dir if not present
    $loc = "../user/$log_username/background";
    createBgDir($loc);

    // Del old bg img
    $sql = "SELECT background FROM useroptions WHERE username=? LIMIT 1";
    $defBg->delOldImg($conn, $sql, $log_username, "../user/background/");

    $newFileLoc = "../user/$log_username/background/$defBg->db_file_name";

    // Copy bg image to permanent loc
    $copied = copy($defBg->fileTmpLoc, $newFileLoc);
    if((!$copied)){
      echo "Error: not copied";
      exit();
    }

    // Update db to new bg
    $defBg->updateDb($conn, $log_username);
    
    // Insert notifications to all friends of the post author
    $sendPost = new SendToFols($conn, $log_username, $log_username);

    $app = "New Background <img src='/images/bgc.png' class='notfimg'>";
    $note = $log_username.' changed his/her background: <br />
      <a href="/user/'.$log_username.'/">Check it now</a>';

    $sendPost->sendNotif($log_username, $app, $note, $conn);

    mysqli_close($conn);
    echo "bibg_success";
    exit();
  }  

  if (isset($_FILES["stPic_photo"]["name"]) && $_FILES["stPic_photo"]["tmp_name"]
    && isset($_POST["cgal"])){
    $imgUpload = new UploadImg($_POST['cgal'], $_POST['des']);

    // Check des length
    $imgUpload->checkDes();

    $upload2 = new ManageImage("stPic_photo", $log_username);

    // Check for errors
    $upload2->checkErrors();

    // Check gallery
    $imgUpload->checkGal();
    $loc = "../user/$log_username/$upload2->db_file_name";
    $upload2->moveRes($loc);

    // Resize img
    $upload2->resizeImg(1920, 1080, $loc, $loc);

    if(!$imgUpload->description){
      $sql = "INSERT INTO photos(user, gallery, filename, uploaddate) VALUES (?,?,?,NOW())";
      $imgUpload->pushToDb($conn, $sql, 'sss', $log_username, $imgUpload->gallery,
        $upload2->db_file_name);
    }else{
      $sql = "INSERT INTO photos(user, gallery, filename, description, uploaddate) VALUES
        (?,?,?,?,NOW())";
     $imgUpload->pushToDb($conn, $sql, 'ssss', $log_username, $imgUpload->gallery,
      $upload2->db_file_name, $imgUpload->description);
    }
    
    // Insert notifications to all friends of the post author
    $sendPost = new SendToFols($conn, $log_username, $log_username);

    $app = "Newly Uploaded Photo <img src='/images/picture.png' class='notfimg'>";
    $note = $log_username.' changed uploaded a new photo into one of his/her galleries: <br />
      <a href="/user/'.$log_username.'/">Check it now</a>';

    $sendPost->sendNotif($log_username, $app, $note, $conn);
   
    mysqli_close($conn);
    echo "upload_complete|";
    exit();
  }

  if (isset($_POST["delete"]) && $_POST["id"] != ""){
    $delP = new DelPhoto($_POST['id']);
    $delP->getInfo($conn);

    if($delP->user == $log_username){
      $picurl = "../user/$log_username/$delP->filename"; 
        if (file_exists($picurl)) {
        unlink($picurl);
        $delP->deletePhoto($conn);
      }
    }
    mysqli_close($conn);
    echo "deleted_ok";
    exit();
  }

  if((isset($_FILES["stPic"]["name"]) && $_FILES["stPic"]["tmp_name"]) ||
    (isset($_FILES["stPic_reply"]["name"]) && $_FILES["stPic_reply"]["tmp_name"]) ||
    isset($_FILES["stPic_pm"]["name"]) && $_FILES["stPic_pm"]["tmp_name"] ||
    isset($_FILES["stPic_msg"]["name"]) && $_FILES["stPic_msg"]["tmp_name"]){
  
    if (isset($_FILES["stPic_reply"]["tmp_name"])) {
      $stPic = new ManageImage("stPic_reply", $log_username);
      $resText = "upload_complete_reply|$stPic->db_file_name";
    } else if (isset($_FILES["stPic_pm"]["name"])) {
      $stPic = new ManageImage("stPic_pm", $log_username);
      $resText = "upload_complete_pm|$stPic->db_file_name";
    } else if (isset($_FILES["stPic_msg"]["name"])) {
      $stPic = new ManageImage("stPic_msg", $log_username);
      $sid = $_POST['sid'];
      $resText = "upload_complete_msg|$stPic->db_file_name|$sid";
    } else {
      $stPic = new ManageImage("stPic", $log_username);
      $resText = "upload_complete|$stPic->db_file_name";
    }

    // Check for errors
    $stPic->checkErrors();

    // Move to tmp loc on server
    $stPic->moveRes("../tempUploads/$stPic->db_file_name");
    echo $resText;
  }
?>
