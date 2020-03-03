<?php
  // Check to see if the user is not logged in
  require_once '../php_includes/check_login_statues.php';
  require_once '../php_includes/sentToFols.php';
  require_once '../php_includes/insertImage.php';
  require_once '../php_includes/perform_checks.php';
  require_once '../safe_encrypt.php';
  require_once '../a_array.php';

  // Make sure no explciit access can be performed
  if($user_ok != true || !$log_username) {
    header('../index');
    exit();
  }

  $one = '1';

  class CreateArticle {
    public function __construct($title, $myta, $tags, $cat, $img1, $img2, $img3, $img4, $img5,
      $conn) {
      $this->title = mysqli_real_escape_string($conn, $title);
      $this->myta = mysqli_real_escape_string($conn, $myta);
      $this->tags = mysqli_real_escape_string($conn, $tags);
      $this->cat = mysqli_real_escape_string($conn, $cat);
      $this->img1 = mysqli_real_escape_string($conn, $img1);
      $this->img2 = mysqli_real_escape_string($conn, $img2);
      $this->img3 = mysqli_real_escape_string($conn, $img3);
      $this->img4 = mysqli_real_escape_string($conn, $img4);
      $this->img5 = mysqli_real_escape_string($conn, $img5);
    }

    public function errorHandling($a_cats) {
      if(!$this->title || !$this->myta || !$this->tags || !$this->cat){
        echo "Please fill out all the form data";
        exit();
      }else if(!in_array($this->cat, $a_cats)){
        echo "Please give a valid category";
        exit();
      }else if(strlen($this->title) > 100){
        echo "Maximum character limit for title is 100";
        exit();
      }else if(strlen($this->tags) > 100){
        echo "Maximum character limit for tags is 100";
        exit();
      }
    }

    public function insertToDb($conn, $log_username, $now) {
      $sql = "INSERT INTO articles(written_by, title, content, tags, category, post_time)
        VALUES (?,?,?,?,?,?)";
      $stmt = $conn->prepare($sql);
      $stmt->bind_param("ssssss", $log_username, $this->title, $this->myta, $this->tags,
        $this->cat, $now);
      $stmt->execute();
      $stmt->close(); 
    }

    public function getArtId($conn, $log_username, $now) {
      $sql = "SELECT id FROM articles WHERE written_by = ? AND post_time = ? LIMIT 1";
      $stmt = $conn->prepare($sql);
      $stmt->bind_param("ss", $log_username, $now);
      $stmt->execute();
      $stmt->bind_result($artid);
      $stmt->fetch();
      $stmt->close();
      return $artid;
    }

    private function imgToDb($conn, $img, $now, $log_username, $imgName) {
      $sql = "UPDATE articles SET ".$imgName." = ? WHERE post_time = ? AND written_by = ?
        LIMIT 1";
      $stmt = $conn->prepare($sql);
      $stmt->bind_param("sss", $img, $now, $log_username);
      $stmt->execute();
      $stmt->close();
    }

    public function insertImage($conn, $img, $now, $log_username, $imgName) {
      $performInsert = new InImage;
      $performInsert->doInsert($img);
      $this->imgToDb($conn, $img, $now, $log_username, $imgName);
    }
  }

  // Ajax calls this code to execute
  if(isset($_POST["title"]) && isset($_POST["area"]) && isset($_POST["tags"]) &&
    isset($_POST["cat"]) && isset($_POST["img1"]) && isset($_POST["img2"]) &&
    isset($_POST["img3"]) && isset($_POST["img4"]) && isset($_POST["img5"])){

    $now = date("Y-m-d H:i:s");
    $crArt = new CreateArticle($_POST['title'], $_POST['area'], $_POST['tags'], $_POST['cat'],
      $_POST['img1'], $_POST['img2'], $_POST['img3'], $_POST['img4'], $_POST['img5'], $conn);

    // Check for potential errors in the article
    $crArt->errorHandling($a_cats);

    // If no errors, insert art to db
    $crArt->insertToDb($conn, $log_username, $now);

    // Get the id of the inserted db
    $crArt->getArtId($conn, $log_username, $now);

    // Insert the images into the database
    $imgArr = array(
      $crArt->img1 => 'img1',
      $crArt->img2 => 'img2',
      $crArt->img3 => 'img3',
      $crArt->img4 => 'img4',
      $crArt->img5 => 'img5'
    );

    foreach ($imgArr as $img => $imgName) {
      if ($img != "no" && !empty($img)) {
        $crArt->insertImage($conn, $img, $now, $log_username, $imgName);
      }
    }

    // Encode URL param
    $nown = $now;
    $nown = base64url_encode($nown, $hshkey);

    // Insert notifications to all friends of the post author
    $app = "Recently Created Article <img src='/images/atrim.png' class='notfimg'>";
    $note = $log_username.' created a new article: <br />
      <a href="/articles/'.$nown.'/'.$log_username.'">Check it now</a>';
    $sendNotifs = new SendToFols($conn, $log_username, $log_username);     
    $sendNotifs->sendNotif($log_username, $app, $note, $conn);
    
    $sql = "SELECT avatar FROM users WHERE username = ? AND activated = ? LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss",$log_username,$one);
    $stmt->execute();
    $stmt->bind_result($avatar);
    $stmt->fetch();
    $stmt->close();
    
    $now = base64url_encode($now,$hshkey);
    
    mysqli_close($conn);
    echo "article_success|$avatar|$log_username|$now";
    exit();
  }
?>
