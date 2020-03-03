<?php
  require_once '../php_includes/check_login_statues.php';

  class PerformArtDel {
    function __construct($u, $p, $conn) {
      $this->u = mysqli_real_escape_string($conn, $u);
      $this->p = mysqli_real_escape_string($conn, $p);
    }

    public function isEmpty() {
      if(!$this->u || !$this->p){
        echo "Please fill out all the form data";
        exit();
      }  
    }

    public function deleteArticle($conn) {
      $sql = "DELETE FROM articles WHERE written_by=? AND post_time=? LIMIT 1";
      $stmt = $conn->prepare($sql);
      $stmt->bind_param('ss', $this->u, $this->p);
      $stmt->execute();
      $stmt->close();
    }
  }

  if(isset($_POST["u"])){
    $delArt = new PerformArtDel($_POST['u'], $_POST['p'], $conn);

    // Check if posted vars are empty
    $delArt->isEmpty();

    // If not, delete art from db
    $delArt->deleteArticle($conn);

    echo "delete_success";
    exit();
  }
?>
