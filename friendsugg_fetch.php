<?php
  // Get Friend Array
  $my_friends = getUsersFriends($conn, $u, $log_username);  

  // Count the number of suggested friends
  $countFs = 0;

  $my_friends = array_diff($my_friends, array($log_username));
  $my_friends = array_values($my_friends);
  $myfs = join("','",$my_friends);

  // Get friends of friends
  foreach ($my_friends as $k => $v) {
    $sql = "SELECT user1, user2 
        FROM friends
        WHERE (user1=? OR user2=?) 
        AND accepted=? 
        AND user1!=? 
        AND user2!=?
        AND user1 NOT IN ('$myfs')
        AND user2 NOT IN ('$myfs')
        ORDER BY RAND()";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssss", $v, $v, $one, $log_username, $log_username);
    $stmt->execute();
    $result = $stmt->get_result();
    while($row = $result->fetch_assoc()){
      array_push($their_friends, $row["user2"]);
      array_push($their_friends, $row["user1"]);
      
      $their_friends = array_unique($their_friends);
      $their_friends = array_diff($their_friends, $my_friends);
      $their_friends = array_values($their_friends);
    }
  }  

  // Suggest users who are friends of my friends but not my friends
  if($_GET["otype"] == "suggf_4" || $otype == "all"){
    $sex = "Male";
    $foff = array();
    if(isset($_GET["otype"])){
      $otype = mysqli_real_escape_string($conn, $_GET["otype"]);
    }

    if (array_key_exists('0', $their_friends)){
      foreach ($their_friends as $k2 => $v2){
        $sql = "SELECT * FROM users WHERE username=? LIMIT 1";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $v2);
        $stmt->execute();
        $result = $stmt->get_result();
        while($row = $result->fetch_assoc()){
          array_push($foff, $row["username"]);
          $countFs++;
          $moMoFriends .= genUserBox($row, $conn);
        }
      }
    }
    isMoMo($_GET['otype'], $moMoFriends);
  }
  
  $myfriends = join("','",$my_friends);
  $foffi = join("','",$foff);

  // Suggest users based on geolocation (users nearby)
  // TODO: check that the lat and lon coordinate values are valid
  $geous = array();
  if($_GET["otype"] == "suggf_0" || $_GET["otype"] == "suggf_1" || $_GET["otype"] == "suggf_2"
    || $_GET["otype"] == "suggf_3" || $otype == "all"){
    if($moMoFriends == "" || $countFs < 100 || isset($_GET["otype"])){
      if(isset($_GET["otype"])){
        $otype = mysqli_real_escape_string($conn, $_GET["otype"]);
      }
      $sql = "SELECT lat, lon FROM users WHERE username = ? LIMIT 1";
      $stmt = $conn->prepare($sql);
      $stmt->bind_param("s", $log_username);
      $stmt->execute();
      $stmt->bind_result($lat, $lon);
      $stmt->fetch();
      $stmt->close();
  
      if($otype == "suggf_0"){ // 5 km
        $lat_m2 = $lat-0.03;
        $lat_p2 = $lat+0.03;
    
        $lon_m2 = $lon-0.03;
        $lon_p2 = $lon+0.03;
      }else if($otype == "suggf_1"){ // 10 km
        $lat_m2 = $lat-0.06;
        $lat_p2 = $lat+0.06;
    
        $lon_m2 = $lon-0.06;
        $lon_p2 = $lon+0.06;
      }else if($otype == "suggf_2"){ // 50 km
        $lat_m2 = $lat-0.3;
        $lat_p2 = $lat+0.3;
    
        $lon_m2 = $lon-0.3;
        $lon_p2 = $lon+0.3;
      }else if($otype == "suggf_3"){ // 100 km
        $lat_m2 = $lat-0.6;
        $lat_p2 = $lat+0.6;
    
        $lon_m2 = $lon-0.6;
        $lon_p2 = $lon+0.6;
      }
        
      $sql = "SELECT * FROM users WHERE username NOT IN ('$myfriends') AND username NOT
        IN ('$foffi') AND lat BETWEEN ? AND ? AND lon BETWEEN ? AND ? AND username != ?
        AND activated = ? $limit";
      $stmt = $conn->prepare($sql);
      $stmt->bind_param("ssssss", $lat_m2, $lat_p2, $lon_m2, $lon_p2, $log_username, $one);
      $stmt->execute();
      $res = $stmt->get_result();
      while($row = $res->fetch_assoc()){
        array_push($geous, $row["username"]);
        $countFs++;
        $moMoFriends .= genUserBox($row, $conn);
      }
    }
    isMoMo($_GET['otype'], $moMoFriends);
  }

  $geos = join("','",$geous);

  // Suggest friends with a similar bio
  $editarray = array();
  if($otype == "all" || $_GET["otype"] == "suggf_5" || $_GET["otype"] == "suggf_6" ||
    $_GET["otype"] == "suggf_7"){
    if(isset($_GET["otype"])){
      $otype = mysqli_real_escape_string($conn, $_GET["otype"]);
    }

    $sql = "SELECT state, city FROM edit WHERE username = ? LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s",$log_username);
    $stmt->execute();
    $stmt->bind_result($province, $city);
    $stmt->fetch();
    $stmt->close();

    $sql = "SELECT country FROM users WHERE username = ? LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s",$log_username);
    $stmt->execute();
    $stmt->bind_result($logCountry);
    $stmt->fetch();
    $stmt->close();

    if($moMoFriends == "" || $countFs < 100 || isset($_GET["otype"])){
      if($otype == "suggf_5" && $city != ""){
        $sql = "SELECT u.username, u.country, u.avatar, u.gender FROM users AS u LEFT JOIN
          edit AS e ON u.username = e.username WHERE e.city = ? AND u.username != ? AND
          u.username NOT IN ('$myfriends') AND u.username NOT IN ('$foffi') AND u.username
          NOT IN ('$geos') AND activated = ? $limit";
      }else if($otype == "suggf_6" && $province != ""){
        $sql = "SELECT u.username, u.country, u.avatar, u.gender FROM users AS u LEFT JOIN
          edit AS e ON u.username = e.username WHERE e.state = ? AND u.username != ? AND
          u.username NOT IN ('$myfriends') AND u.username NOT IN ('$foffi') AND u.username
          NOT IN ('$geos') AND activated = ? $limit";
      }else if(!isset($_GET["otype"])){
        $sql = "SELECT username, country, avatar, gender FROM users WHERE country = ? AND
          username != ? AND username NOT IN ('$myfriends') AND username NOT IN ('$foffi')
          AND username NOT IN ('$geos') AND activated = ? $limit";
      }else if(isset($_GET["otype"])){
        $sql = "SELECT username, country, avatar, gender FROM users WHERE country = ? AND
          username != ? AND username NOT IN ('$myfriends') AND activated = ? $limit";
      }

      $stmt = $conn->prepare($sql);
      if(($otype == "suggf_5" || $otype == "all") && $city != ""){
        $stmt->bind_param("sss", $city, $log_username, $one);
      }else if(($otype == "suggf_6" || $otype == "all") && $province != ""){
        $stmt->bind_param("sss", $province, $log_username, $one);
      }else{
        $stmt->bind_param("sss", $logCountry, $log_username, $one);
      }

      $stmt->execute();
      $res = $stmt->get_result();

      while($row = $res->fetch_assoc()){
        array_push($editarray, $row["username"]);
        $countFs++;
        $moMoFriends .= genUserBox($row, $conn);
      }
    }
    isMoMo($_GET['otype'], $moMoFriends);  
  }
  
  $eaketto = join("','", $editarray);
 
  // Suggest users with a similar age
  $yearsarr = array();
  if($otype == "all" || $_GET["otype"] == "suggf_8" || $_GET["otype"] == "suggf_9" ||
    $_GET["otype"] == "suggf_10" || $_GET["otype"] == "suggf_11"){
    if($moMoFriends == "" || $countFs < 100 || isset($_GET["otype"])){
      if(isset($_GET["otype"])){
        $otype = mysqli_real_escape_string($conn, $_GET["otype"]);
      }
      $sql = "SELECT bday FROM users WHERE username = ? LIMIT 1";
      $stmt = $conn->prepare($sql);
      $stmt->bind_param("s",$log_username);
      $stmt->execute();
      $stmt->bind_result($log_b);
      $stmt->fetch();
      $stmt->close();

      $log_b = mb_substr($log_b, 0, 4, "utf-8");
      if($otype == "suggf_8"){
        $logbp2 = $log_b+2;
        $logbm2 = $log_b-2;
      }else if($otype == "suggf_9"){
        $logbp2 = $log_b+5;
        $logbm2 = $log_b-5;
      }else if($otype == "suggf_10"){
        $logbp2 = $log_b+10;
        $logbm2 = $log_b-10;
      }else{
        $logbp2 = $log_b+20;
        $logbm2 = $log_b-20;
      }

      $logbp2 = $logbp2."-"."01-01";
      $logbm2 = $logbm2."-"."01-01";
      
      $sql = "SELECT * FROM users WHERE (bday BETWEEN ? AND ?) AND username NOT IN
        ('$myfriends') AND username NOT IN ('$foffi') AND username NOT IN ('$geos') AND
        username NOT IN('$eaketto') AND username != ? AND activated = ? $limit";
      $stmt = $conn->prepare($sql);
      $stmt->bind_param("ssss", $logbm2, $logbp2, $log_username, $one);
      $stmt->execute();
      $res = $stmt->get_result();
      while($row = $res->fetch_assoc()){
        array_push($yearsarr, $row["username"]);
        $countFs++;
        $moMoFriends .= genUserBox($row, $conn);
      }    
      $stmt->close();
      isMoMo($_GET['otype'], $moMoFriends);
    }
  }

  $yearsarr = join("','", $yearsarr);

  // Leave it for future purposes
  if($otype == "all"){
    if($moMoFriends == "" || $countFs < 100){
      $sql = "SELECT * FROM users WHERE activated = ? AND username NOT IN ('$myfriends')
        AND username NOT IN ('$foffi') AND username NOT IN ('$geos') AND username
        NOT IN('$eaketto') AND username NOT IN('$yearsarr') AND username != ? ORDER BY RAND()
        $limit";
      $stmt = $conn->prepare($sql);
      $stmt->bind_param("ss", $one, $log_username);
      $stmt->execute();
      $res = $stmt->get_result();
      while($row = $res->fetch_assoc()){
        $moMoFriends .= genUserBox($row, $conn);
        $countFs++;
      }
    }
  }
?>
