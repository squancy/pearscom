<?php
  require_once 'php_includes/check_login_statues.php';

  // Get the timezone of user
  $sql = "SELECT tz FROM users WHERE username = ? LIMIT 1";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param("s", $log_username);
  $stmt->execute();
  $stmt->bind_result($timezone);
  $stmt->fetch();
  $stmt->close();

  $timezone = "Europe/Budapest";
  
  date_default_timezone_set($timezone);
  
  function time_elapsed_string($datetime2){
    $datetime1 = new DateTime();
    $datetime2 = new DateTime($datetime2);
    $interval = $datetime1->diff($datetime2);
    $year = $interval->format('%y');
    $month = $interval->format('%m');
    $day = $interval->format('%a');
    $hour = $interval->format('%h');
    $minute = $interval->format('%i');
    $second = $interval->format('%s');
    
    if($year != 0){
      if($year == 1){
        $elapsed = $year." year"; 
      }else{
        $elapsed = $year." years"; 
      }
    }else if($month != 0){
      if($month == 1){
        $elapsed = $month." month"; 
      }else{
        $elapsed = $month." months";
      }
    }else if($day != 0){
      if($day == 1){
        $elapsed = $day." day"; 
      }else{
        $elapsed = $day." days"; 
      }
    }else if($hour != 0){
      if($hour == 1){
        $elapsed = $hour." hour"; 
      }else{
        $elapsed = $hour." hours"; 
      }
    }else if($minute != 0){
      if($minute == 1){
        $elapsed = $minute." minute"; 
      }else{
        $elapsed = $minute." minutes"; 
      }
    }else{
      if($second == 1){
        $elapsed = $second." second"; 
      }else{
        $elapsed = $second." seconds"; 
      }
    }
    
    return $elapsed;
  }
?>
