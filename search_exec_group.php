<?php
  require_once 'php_includes/conn.php';
  require_once 'php_includes/status_common.php';
  require_once 'timeelapsedstring.php';
  require_once 'headers.php';

  $output = "";
  $name = "";

  // AJAX calls this code
  if(isset($_POST['g'])){
    $g = mysqli_real_escape_string($conn, $_POST["g"]);
    if ($g == ""){
      exit();    
    }

    // Perform search query
    $g_search = "$g%";
    $sql = "SELECT * FROM groups 
            WHERE name LIKE ?
        ORDER BY name ASC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $g_search);
    $stmt->execute();
    $result = $stmt->get_result();
    if($result->num_rows > 0){
      while ($row = $result->fetch_assoc()){
        $gname = $row["name"];
        $gnameori = urlencode($gname);
        $gnameim = $gname;
        $logo = $row["logo"];
        $gdes = $row["des"];
        $creator = $row["creator"];
        $creator_original = urlencode($creator);
        $creation_date = $row["creation"];
        $cd = strftime("%b %d, %Y", strtotime($creation_date));
        
        $uds = time_elapsed_string($creation_date);
        
        if($logo == NULL || $logo == "gdef.png"){
          $pcurl = '/images/gdef.png';
				}else{
          $pcurl = '/groups/'.$gnameim.'/'.$logo;
				}
        
        $output .= "
          <a href='/group/".$gnameori."' style='color: #000;'>
            <div id='nev_rel_holder_ph_e' class='pcmacsm'>
              <div style='background-image: url(\"".$pcurl."\"); background-repeat: no-repeat;
                background-position: center; background-size: cover; width: 56px;
                height: 56px; float: right; margin-top: 8px; border-radius: 50%;'>
              </div>
              <div id='new_inner_div_'>
                <p style='margin-top: 10px; font-size: 14px;'>
                  <b>Name: </b>".$gname."<br>
                  <b>Creator: </b>".$creator."<br>
                  <b>Established: </b>".$cd." (".$uds." ago)
                </p>
              </div>
            </div>
          </a>
        ";
      }
      
      echo $output;
      exit;
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
