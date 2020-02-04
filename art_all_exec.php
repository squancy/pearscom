<?php
  /*
    Search in all articles.
  */

	require_once 'php_includes/conn.php';
	require_once 'timeelapsedstring.php';
	require_once 'safe_encrypt.php';
	require_once 'php_includes/check_login_statues.php';
	require_once 'ccov.php';
	require_once 'headers.php';
	
	$output = "";
	$a = "";
	if(isset($_POST['a']) && isset($_POST["phpu"])){
		$a = mysqli_real_escape_string($conn, $_POST["a"]);
		$phpu = mysqli_real_escape_string($conn, $_POST["phpu"]);	
		if ($a == ""){
			echo $output;
			exit();		
		}
		$a_search = "$a%";
		include("php_includes/conn.php");

		$sql = "SELECT a.*, u.*
				FROM articles AS a
				LEFT JOIN users AS u
				ON a.written_by = u.username
		    WHERE a.written_by = ? AND (a.title LIKE ? OR a.tags LIKE ? OR a.category LIKE ?)
				ORDER BY a.title ASC LIMIT 100";
		$stmt = $conn->prepare($sql);
		$stmt->bind_param("ssss",$phpu,$a_search,$a_search,$a_search);
		$stmt->execute();
		$result = $stmt->get_result();

		if($result->num_rows > 0){
			$output .= '';
			while ($row = $result->fetch_assoc()){
				$written_by = $row["written_by"];
				$title = stripslashes($row["title"]);
			  $title = str_replace('\'', '&#39;', $title);
		    $title = str_replace('\'', '&#34;', $title);
				$avatar = $row["avatar"];
				$tags = $row["tags"];
				$posttime = $row["post_time"];
				$cat = $row["category"];
				$pt = strftime("%b %d, %Y", strtotime($posttime));
				$written_by_original = urlencode($written_by);

				$agoform = time_elapsed_string($posttime);
				$posttime = base64url_encode($posttime,$hshkey);
				$cover = chooseCover($cat);
				
				$output .= "
          <a href='/articles/".$posttime."/".$written_by_original."' style='color: #000;'>
            <div id='nev_rel_holder_ph_e' class='pcmacsm'>
              ".$cover."
              <div id='new_inner_div_'>
                <p style='margin-top: 10px; font-size: 14px;'>
                  <b>Author: </b>".$written_by."<br>
                  <b>Title: </b>".$title."
                </p>
              </div>
            </div>
          </a>";
			}
			echo $output;
			exit();
		} else {
			echo "
      <p style='text-align: center; margin: 0; padding: 10px; color: #999;'>
        Unfortunately, there are no results found
      </p>";
			exit();
		}
	}
?>
