<?php
  // Check to see if the user is not logged in
  require_once '../php_includes/check_login_statues.php';
  require_once '../php_includes/perform_checks.php';
  require_once '../php_includes/conn.php';
  require_once '../php_includes/insertImage.php';
  require_once '../php_includes/sentToFols.php';

  if(!$user_ok || !$log_username) {
    exit();
  }

  $one = "1";

  class EditHandle {
    public function __construct($conn, $p, $u, $texta, $title) {
      $this->p = mysqli_real_escape_string($conn, $p);
      $this->u = mysqli_real_escape_string($conn, $u);
      $this->texta = mysqli_real_escape_string($conn, $texta);
      $this->title = mysqli_real_escape_string($conn, $title);
      $this->title = htmlentities($this->title);

      $nameArr = ['img1', 'img2', 'img3', 'img4', 'img5'];
      foreach ($nameArr as $imgName) {
        $this->{$imgName} = mysqli_real_escape_string($conn, $_POST[$imgName]);
      }
    }

    public function checkErrors() {
      if(!$this->p){
        echo "This article does not exist";
        exit();
      }else if(!$this->texta){
        echo "Please type in something first";
        exit();
      }else if(strlen($this->title) > 100){
        echo "Maximum character limit for title is 100".$this->title;
        exit();
      }
    }

    public function updateArt($conn) {
      $sql = "UPDATE articles SET content=?, title=? WHERE written_by=? AND post_time=?
        LIMIT 1";
      $stmt = $conn->prepare($sql);
      $stmt->bind_param("ssss", $this->texta, $this->title, $this->u, $this->p);
      $stmt->execute();
      $stmt->close();
    }

    public function updateImg($conn, $img) {
      // Upload image
      $uImg = new InImage();
      $uImg->doInsert($img);

      // Insert to db
      $sql = "UPDATE articles SET img1=? WHERE written_by=? AND post_time=? LIMIT 1";
      $stmt = $conn->prepare($sql);
      $stmt->bind_param("sss", $img, $this->u, $this->p);
      $stmt->execute();
      $stmt->close();
    }
  }

  if(isset($_POST["u"]) && isset($_POST["p"]) && isset($_POST["texta"])){
    $updateArt = new EditHandle($conn, $_POST['p'], $_POST['u'], $_POST['texta'],
      $_POST['title']);

    // Make sure user exists in db
    userExists($conn, $updateArt->u);

    // Check for potential errors
    $updateArt->checkErrors();

    // Save art in db
    $updateArt->updateArt($conn);

    // Update images
    $imgArr = [$updateArt->img1, $updateArt->img2, $updateArt->img3, $updateArt->img4,
      $updateArt->img5];
    foreach ($imgArr as $img) {
      if ($img && $img != "no") {
        $updateArt->updateImg($conn, $img);
      }
    }

    // Send a notif about art edit
    $app = "Edited Article";
    $note = $log_username.' edited his/her article: <br />
      <a href="/articles/'.$updateArt->p.'/'.$log_username.'">Check it now</a>';

    $sendNotif = new SendToFols($conn, $log_username, $log_username);
    $sendNotif->sendNotif($log_username, $app, $note, $conn);

    mysqli_close($conn);
    echo "save_success";
    exit();
  }
?>
