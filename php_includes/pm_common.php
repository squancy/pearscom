<?php
  function updateDate($conn, $log_username) {
    $one = '1';
    $sql = "UPDATE pm SET rread = ?, sread = ? WHERE receiver=? OR sender=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssss", $one, $one, $log_username, $log_username);
    $stmt->execute();
    $stmt->close();
  } 

  function selectClause($otype) {
    if($otype == "sort_0"){
      return "ORDER BY senttime DESC";
    }else if($otype == "sort_1"){
      return "ORDER BY senttime ASC";
    }else if($otype == "sort_2"){
      return "ORDER BY sender";
    }else if($otype == "sort_3"){
      return "ORDER BY sender DESC";
    }else if($otype == "sort_4"){
      return "ORDER BY mread DESC";
    }else if($otype == "sort_5"){
      return "ORDER BY mread ASC";
    }else if($otype == "sort_6"){
      return "ORDER BY RAND()";
    }
  }

  function lastMessage($conn, $pmids) {
    $sql3 = "SELECT message, sender, senttime FROM pm WHERE parent = ? ORDER BY
      senttime DESC LIMIT 1";
    $stmt = $conn->prepare($sql3);
    $stmt->bind_param("s", $pmids);
    $stmt->execute();
    $stmt->bind_result($lastMsg, $lastSender, $lastTime);
    $stmt->fetch();
    $stmt->close();
    return [$lastMsg, $lastSender, $lastTime];
  }

  function countConvs($conn, $log_username, $x, $zero) {
    $sql = "SELECT COUNT(id) FROM pm WHERE (receiver=? OR sender=?) AND parent=? AND
        rdelete = ? AND sdelete = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssss", $log_username, $log_username, $x, $zero, $zero);
    $stmt->execute();
    $stmt->bind_result($countConvs);
    $stmt->fetch();
    $stmt->close();
    return $countConvs;
  }
?>
