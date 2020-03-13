<?php
  require_once '../php_includes/conn.php';

  // This block will delete all accounts that do not activate after 3 days
  $var = 0;
  $sql = "SELECT id, username FROM users WHERE signup < CURRENT_DATE - INTERVAL 3 DAY
    AND activated=?";
  $stmt = $conn->prepare($sql);
  $stmt->bind_result("i",$var);
  $stmt->execute();
  $numrows = $stmt->num_rows;

  // Check to see if there any user
  if($numrows > 0){
    while ($row = mysqli_fetch_array($query, MYSQLI_ASSOC)) {
      $id = $row["id"];
      $username = $row["username"];
      $userFolder = "../user/$username";
      if(is_dir($userFolder)){
        rmdir($userFolder);
      }

      // Delete the user from the database
      $sql_2 = "DELETE FROM users WHERE id=? AND username=? LIMIT 1";
      $stmt = $conn->prepare($sql_2);
      $stmt->bind_param("is", $id, $username);
      $stmt->execute();
      $stmt->close();

      $sql_3 = "DELETE FROM useroptions WHERE username=? LIMIT 1";
      $stmt = $conn->prepare($sql_3);
      $stmt->bind_param("s", $username);
      $stmt->execute();
      $stmt->close();
    }
  }
?>
