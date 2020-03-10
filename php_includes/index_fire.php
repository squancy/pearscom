<?php
  function indFire($conn, $sql, $id) {
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->bind_result($res);
    $stmt->fetch();
    $stmt->close();
    return $res;
  }
?>
