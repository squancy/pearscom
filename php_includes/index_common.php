<?php
  /*
    Commonly used functions & classes used on the news feed page
  */

  function updateFeedcheck($conn, $u) {
    $sql = "UPDATE users SET feedcheck=NOW() WHERE username = ? LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $u);
    $stmt->execute();
    $stmt->close();
  }

  function getBlockedUsers($conn, $log_username) {
    $blocked_array = array();
    $sql = "SELECT blocker FROM blockedusers WHERE blockee = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $log_username);
    $stmt->execute();
    $result = $stmt->get_result();
    while($row = $result->fetch_assoc()){
        array_push($blocked_array, $row["blocker"]);
    }
  }

  function feedCount($conn, $friendsCSV) {
    $a = 'a';
    $c = 'c';
    $sql = "SELECT COUNT(id)
            FROM status
            WHERE author IN ('$friendsCSV') AND (type=? OR type=?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $a, $c);
    $stmt->execute();
    $stmt->bind_result($feedrcnt);
    $stmt->fetch();
    $stmt->close();
    return $feedrcnt;
  }

  function countNearbyUsers($conn, $lat_m2, $lat_p2, $lon_m2, $lon_p2, $log_username) {
    $sql = "SELECT COUNT(id) FROM users WHERE lat BETWEEN ? AND ? AND lon BETWEEN ?
            AND ? AND username != ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssss", $lat_m2, $lat_p2, $lon_m2, $lon_p2, $log_username);
    $stmt->execute();
    $stmt->bind_result($cnt_near);
    $stmt->fetch();
    $stmt->close();
    return $cnt_near;
  }

  function getUsersCountry($conn, $log_username) {
    $sql = "SELECT country FROM users WHERE username = ? LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $log_username);
    $stmt->execute();
    $stmt->bind_result($ucountry);
    $stmt->fetch();
    $stmt->close();
    return $ucountry;
  }
?>
