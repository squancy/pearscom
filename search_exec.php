<?php
	require_once 'php_includes/conn.php';
	require_once 'php_includes/check_login_statues.php';
	require_once 'php_includes/wrapText.php';
	require_once 'php_includes/status_common.php';
  require_once 'search_exec_common.php';
	require_once 'headers.php';

  function genUserRow($row) {
    global $bstr;
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
    return '
      <div class="srchDivs"
        onclick="javascript:location.href=\'/user/'.$uname_original.'/\'">
        <div style="background-image: url(\''.$pcurl.'\'); background-size: cover;
          width: 30px; height: 30px; float: left; border-radius: 50%; margin-top: -5px;
          margin-right: 5px;"></div>
        <span style="vertical-align: middle;">'.$uname.'</span>
      </div>
    ';
  }

  // AJAX calls this code in req
	if(isset($_POST['u'])){
    // Escape vars
		$u = mysqli_real_escape_string($conn, $_POST["u"]);
		$bstr = $_POST["u"];
		if (!$u){
			echo $output;
			exit();		
		}

    // Perform an SQL query for user search
    $output = performSearch($conn, 4);
    echo $output;
    exit();
	}
?>
