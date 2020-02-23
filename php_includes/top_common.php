<?php
  function getNotescheck($conn, $log_username) {
    $sql = "SELECT notescheck FROM users WHERE username=? LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s",$log_username);
    $stmt->execute();
    $stmt->bind_result($notescheck);
    $stmt->fetch();
    $stmt->close();
    return $notescheck;
  } 

  function reqCount($conn, $log_username) {
    $zero = '0';
    $sql = "SELECT COUNT(id) FROM friends WHERE user2=? AND accepted=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $log_username, $zero);
    $stmt->execute();
    $stmt->bind_result($requests_count);
    $stmt->fetch();
    $stmt->close();
    return $requests_count;
  }
?>
