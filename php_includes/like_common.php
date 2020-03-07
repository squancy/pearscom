<?php
  class LikeGeneral {
    public function __construct($conn, $p1, $p2) {
      $this->p1 = $p1;
      $this->p2 = $p2;
    }

    public function checkIfLiked($conn, $sql, $param1, ...$values) {
      $stmt = $conn->prepare($sql);
      $stmt->bind_param($param1, ...$values);
      $stmt->execute();
      $stmt->bind_result($row_count1);
      $stmt->fetch();
      return $row_count1;
    }

    public function manageDb($conn, $sql, $param1, ...$values) {
      $stmt = $conn->prepare($sql);
      $stmt->bind_param($param1, ...$values);
      $stmt->execute();
      $stmt->close();
    }
  }
?>
