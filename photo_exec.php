<?php
  require_once 'php_includes/conn.php';
  require_once 'php_includes/check_login_statues.php';
  require_once 'timeelapsedstring.php';
  require_once 'headers.php';

  $output = "";
  $a = "";
  
  // AJAX calls this code to process the request
  if(isset($_POST['a']) && isset($_POST["u"])){
    // Escape vars
    $a = mysqli_real_escape_string($conn, $_POST["a"]);
    $u = mysqli_real_escape_string($conn, $_POST["u"]);
    if ($a == "" || $u == ""){
      echo $output;
      exit();
    }

    // Perform search
    $a_search = "$a%";
    $sql = "SELECT * FROM photos WHERE user = ? AND gallery LIKE ? OR description LIKE ?
      LIMIT 15";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sss", $u, $a_search, $a_search);
    $stmt->execute();
    $result = $stmt->get_result();
    if($result->num_rows > 0){
      while ($row = $result->fetch_assoc()){
        $gallery = $row["gallery"];
        $description = $row["description"];
        $filename = $row["filename"];
        $uploaddate = $row["uploaddate"];
        $ud = strftime("%b %d, %Y", strtotime($uploaddate));
        if($description == NULL){
          $description = "No description";
        }

        $pcurl = '/user/'.$u.'/'.$filename;
        
        $uds = time_elapsed_string($uploaddate);

        // Build output
        $output .= "
          <a href='/photo_zoom/".urlencode($u)."/".$filename."' style='color: #000;'>
            <div id='nev_rel_holder_ph_e' class='pcmacsm'>
              <div style='background-image: url(\"".$pcurl."\"); background-repeat: no-repeat;
                background-position: center; background-size: cover; width: 56px;
                height: 56px; float: right; margin-top: 8px; border-radius: 50%;'>
              </div>
              <div id='new_inner_div_'>
                <p style='margin-top: 10px; font-size: 14px;'>
                  <b>Gallery: </b>".$gallery."<br>
                  <b>Description: </b>".$description."<br>
                  <b>Uploaded: </b>".$uds." ago</p>
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
          Unfortunately, no search results found
        </p>
      ";
      exit();
    }
  }
?>
