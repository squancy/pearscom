<?php
  // Database connection
  $conn = mysqli_connect('IP_ADDR', 'USERNAME', 'PASSWORD', 'DATABASE');
  mysqli_set_charset($conn, "utf8mb4");

  // Connection error handling
  if(mysqli_connect_errno()){
    echo mysqli_connect_error();
    exit();
  }
?>
