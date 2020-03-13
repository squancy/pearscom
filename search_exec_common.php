<?php
  $output = "";
  function selectUname($conn, $sql, $u_search, &$unameOrder) {
    $one = "1";
    $stmt = $conn->prepare($sql);
		$stmt->bind_param("ss", $u_search, $one);
		$stmt->execute();
		$result = $stmt->get_result();
    while ($row = $result->fetch_assoc()){
      array_push($unameOrder, $row["username"]);
    }
    $stmt->close();
  }

  function performSearch($conn, $limit) {
    global $u;
    $unameOrder = array();
    $u_search1 = "$u%";
    $u_search2 = "%$u%";
    $sql = "SELECT * FROM users 
            WHERE username LIKE ? AND activated = ?
            ORDER BY username ASC LIMIT $limit";
    selectUname($conn, $sql, $u_search1, $unameOrder);

    $unameJoin = implode("','", $unameOrder);
    $sql = "SELECT * FROM users 
            WHERE username LIKE ? AND activated = ? AND username NOT IN ('$unameJoin')
            ORDER BY username ASC LIMIT $limit";
    selectUname($conn, $sql, $u_search2, $unameOrder);
   
    if (!empty($unameOrder)) {
      foreach ($unameOrder as $uname) {
        $sql = "SELECT * FROM users WHERE username = ?"; 
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $uname);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()){
          $output .= genUserRow($row);
        }
        $stmt->close();
      }
      return $output;
    } else {
      return "
        <p style='font-size: 14px; text-align: center; margin: 0; padding: 10px; color: #999;'>
          Unfortunately, there are no results found
        </p>
      ";
    }
  }
?>
