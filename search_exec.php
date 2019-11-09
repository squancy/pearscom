<?php
	require_once 'php_includes/conn.php';
	require_once 'php_includes/check_login_statues.php';
	require_once 'headers.php';
	$output = "";
	$u = "";
	$bstr = "";
	$one = "1";
	if(isset($_POST['u'])){
		$u = mysqli_real_escape_string($conn, $_POST["u"]);
		$bstr = $_POST["u"];
		if ($u == ""){
			// They tried to defeat our security
			echo $output;
			exit();		
		}
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
				if(strlen($uname) > 36){
					$uname = mb_substr($uname, 0, 33, "utf-8");
					$uname .= ' ...';
				}
                if(strlen($country) > 33){
					$country = mb_substr($country, 0, 29, "utf-8");
					$country .= ' ...';
				}
				$uname = preg_replace("/$bstr/i", '<b>$0</b>', $uname);
				$pcurl = "";
				if($avatar == NULL){
				    $pcurl = '/images/avdef.png';
				}else{
				    $pcurl = '/user/'.$unameim.'/'.$avatar;
				}
				$output .= '<div class="srchDivs" onclick="javascript:location.href=\'/user/'.$uname_original.'/\'">
                    <div style="background-image: url(\''.$pcurl.'\'); background-size: cover; width: 30px; height: 30px; float: left; border-radius: 50%; margin-top: -5px; margin-right: 5px;"></div>
                    <span style="vertical-align: middle;">'.$uname.'</span>
                  </div>';
			}
			echo $output;
			exit();
		} else {
			// No results from search
			echo "<p style='font-size: 14px; text-align: center; margin: 0; padding: 10px; color: #999;'>Unfortunately, there are no results found</p>";
			exit();
		}
	}
?>