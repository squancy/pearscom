<?php
  require_once '../php_includes/check_login_statues.php';

  class UpdateGeoloc {
    public function __construct($ulat, $ulon) {
      $this->ulat = preg_replace('#[^0-9.,-]#', '', $ulat);
      $this->ulon = preg_replace('#[^0-9.,-]#', '', $ulon);
    }

    public function checkErrors() {
      if(!$this->ulat || !$this->ulon){
        echo "Longitude or latitude is missing";
        exit();
      }
    }

    public function updateDb($conn, $log_username) {
      $sql = "UPDATE users SET lat = ?, lon = ? WHERE username = ?";
      $stmt = $conn->prepare($sql);
      $stmt->bind_param("sss", $this->ulat, $this->ulon, $log_username);
      $stmt->execute();
      $stmt->close();
    }
  }

  if(isset($_POST["updateLat"]) && isset($_POST["updateLon"])){
    $geo = new UpdateGeoloc($_POST['updateLat'], $_POST['updateLon']);
    $geo->checkErrors();
    $geo->updateDb($conn, $log_username);

    echo "update_geo_success";
  }
?>
