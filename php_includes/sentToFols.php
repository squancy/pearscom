<?php
  /*
    Send notification to followers about an event
  */
  require_once 'perform_checks.php';

  class SendToFols {
    public function __construct($conn, $u, $log_username) {
      $this->friends = getUsersFriends($conn, $u, $log_username);
      $this->followers = getFols($conn, $log_username);
    }

    public function sendNotif($log_username, $app, $note, $conn) {
      $diffarr = array_unique(array_merge($this->friends, $this->followers), SORT_REGULAR);
      foreach ($diffarr as $relUser){
        $sql = "INSERT INTO notifications(username, initiator, app, note, date_time)
          VALUES(?,?,?,?,NOW())";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssss", $relUser, $log_username, $app, $note);
        $stmt->execute();
        $stmt->close();
      }
    }
  }
?>
