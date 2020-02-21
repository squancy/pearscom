<?php
	require_once 'php_includes/conn.php';
	require_once 'php_includes/check_login_statues.php';
	require_once 'php_includes/wrapText.php';
	require_once 'php_includes/status_common.php';
	require_once 'headers.php';

	$output = "";
	$u = "";
	$bstr = "";
	$one = "1";

  // AJAX calls this code in req
	if(isset($_POST['u'])){
    // Escape vars
		$u = mysqli_real_escape_string($conn, $_POST["u"]);
		$bstr = $_POST["u"];
		if ($u == ""){
			echo $output;
			exit();		
		}

    // Perform an SQL query for user search
		$u_search = "%$u%";
		$sql = "SELECT * FROM users 
		        WHERE username LIKE ? AND activated = ?
            ORDER BY username ASC LIMIT 8";
		$stmt = $conn->prepare($sql);
		$stmt->bind_param("ss",$u_search,$one);
		$stmt->execute();
		$result = $stmt->get_result();
		if($result->num_rows > 0){
			while ($row = $result->fetch_assoc()){
				$uname = $row["username"];
				$country = $row["country"];
				$avatar = $row["avatar"];
				$uname_original = urlencode($uname);
				$unameim = $uname;

        $uname = wrapText($uname, 36);
        $country = wrapText($country, 33);

        // Make input text bold in username
				$uname = preg_replace("/$bstr/i", '<b>$0</b>', $uname);

        // Get user avatar
        $pcurl = avatarImg($unameim, $avatar);
				$output .= '
          <div class="srchDivs"
            onclick="javascript:location.href=\'/user/'.$uname_original.'/\'">
            <div style="background-image: url(\''.$pcurl.'\'); background-size: cover;
              width: 30px; height: 30px; float: left; border-radius: 50%; margin-top: -5px;
              margin-right: 5px;"></div>
            <span style="vertical-align: middle;">'.$uname.'</span>
          </div>
        ';
			}

			echo $output;
			exit();
		} else {
			// No results from search
			echo "
        <p style='font-size: 14px; text-align: center; margin: 0; padding: 10px; color: #999;'>
          Unfortunately, there are no results found
        </p>
      ";
			exit();
		}
	}
?>
