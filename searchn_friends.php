<?php
  require_once 'php_includes/conn.php';
  require_once 'php_includes/status_common.php';
  require_once 'timeelapsedstring.php';
  require_once 'headers.php';

  $output = "";

  // Get paramateres from the URL
  if(isset($_POST['u']) && isset($_POST["imp"])){
    $imp = mysqli_real_escape_string($conn, $_POST["imp"]);
    $u = mysqli_real_escape_string($conn, $_POST["u"]);
    if ($u == ""){
      exit();    
    }

    $imp = str_replace("\\", "", $imp);
    $u_search = "$u%";
    $sql = "SELECT * FROM users 
            WHERE username LIKE ? AND username IN('$imp')
            ORDER BY username ASC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $u_search);
    $stmt->execute();
    $result = $stmt->get_result();
    if($result->num_rows > 0){
      while ($row = $result->fetch_assoc()){
        $uname = $row["username"];
        $unameori = urlencode($uname);
        $unameim = $uname;
        $avatar = $row["avatar"];
        $country = $row["country"];
        $bday = $row["bday"];
        
        $pcurl = avatarImg($unameim, $avatar);
        
        $output .= "
          <a href='/user/".$unameori."/' style='color: #000;'>
            <div id='nev_rel_holder_ph_e' class='pcmacsm'>
              <div style='background-image: url(\"".$pcurl."\"); background-repeat: no-repeat;
                background-position: center; background-size: cover; width: 45px;
                height: 45px; float: right; margin-top: 6px; border-radius: 50%;'>
              </div>
              <div id='new_inner_div_'>
                <p style='margin-top: 10px; font-size: 14px;'>
                  <b>Username: </b>".$uname."<br>
                  <b>Country: </b>".$country."<br>
                </p>
              </div>
            </div>
          </a>
        ";
      }
      
      echo $output;
      exit();
    } else {
      // No results from search
      echo "
        <p style='color: #999; text-align: center;'>
          Unfortunately, there are no results found
        </p>
      ";
      echo $output;
      exit();
    }
  }
?>
