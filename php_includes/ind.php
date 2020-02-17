<?php
  function indexId($conn, $id, $db, $what) {
    // Fired from index.php like so get the photo file name from status id
    $sql = "SELECT ".$what." FROM ".$db." WHERE id=? LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->bind_result($x);
    $stmt->fetch();
    $stmt->close();
    return $x;
  }
?>
