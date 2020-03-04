<?php
  require_once '../php_includes/check_login_statues.php';
  require_once '../php_includes/perform_checks.php';

  class DelPhot {
    public function __construct($id) {
      $this->id = preg_replace('#[^0-9]#', '', $id);
    }

    public function photoExists($conn, $log_username) {
      $sql = "SELECT id FROM photos WHERE id = ? AND user = ? LIMIT 1";
      $stmt = $conn->prepare($sql);
      $stmt->bind_param("is", $this->id, $log_username);
      $stmt->execute();
      $res = $stmt->get_result();
      if($res->num_rows < 1){
        echo "video does not exist";
        exit();
      }
      $stmt->close();
    }

    public function deletePhoto($conn, $log_username) {
      $sql = "DELETE FROM photos WHERE id = ? AND user = ? LIMIT 1";
      $stmt = $conn->prepare($sql);
      $stmt->bind_param("is", $this->id, $log_username);
      $stmt->execute();
      $stmt->close();
    }

    public function makeSure($conn, $log_username) {
      $sql = "SELECT id FROM photos WHERE id = ? AND user = ? LIMIT 1";
      $stmt = $conn->prepare($sql);
      $stmt->bind_param("is", $this->id, $log_username);
      $stmt->execute();
      $res = $stmt->get_result();
      if($res->num_rows < 1){
        echo "delete_photo_success";
        exit();
      }else{
        echo "Unfortunately an unknown error has occured. Please try again later!";
        exit();
      }
      $stmt->close();
    }
  }

  if(isset($_POST["id"]) && $_POST["id"] != ""){
    $delP = new DelPhot($_POST['id']);

    // Make sure user exists in db
    userExists($conn, $log_username);

    // Make sure photo exists
    $delP->photoExists($conn, $log_username);

    // Delete photo
    $delP->deletePhoto($conn, $log_username);

    // Make sure it is deleted
    $delP->makeSure($conn, $log_username);
    
    exit();
  }
?>
