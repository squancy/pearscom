<?php
	require_once 'php_includes/check_login_statues.php';
	require_once 'timeelapsedstring.php';
	require_once 'headers.php';
	if($user_ok != true || $log_username == ""){
		header('Location: /index');
	    exit();
	}
	
	$u = $_SESSION['username'];
	// Select the member from the users table
	$one = "1";
	$sql = "SELECT * FROM users WHERE username=? AND activated=? LIMIT 1";
	$stmt = $conn->prepare($sql);
	$stmt->bind_param("ss",$u,$one);
	$stmt->execute();
	$stmt->store_result();
	$stmt->fetch();
	$numrows = $stmt->num_rows;
	// Now make sure the user exists in the table
	if($numrows < 1){
		header('location: /usernotexist');
		exit();
	}
	$all_groups = "";
	if(isset($_SESSION['username'])){
		$sql = "SELECT * FROM groups ORDER BY name";
		$stmt = $conn->prepare($sql);
		$stmt->execute();
		$result = $stmt->get_result();
		if($result->num_rows > 0){
			while($row = $result->fetch_assoc()){
				$name = $row["name"];
				$nameori = urlencode($name);
				$creation = $row["creation"];
				$creation_new = substr($creation, 0, 10);
				$creator = $row["creator"];
				$invrule = $row["invrule"];
				if($invrule == 1){
					$invrule_new = "By simply join";
				}else{
					$invrule_new = "By request to join";
				}
				if(strlen($name) > 20){
				    $name = mb_substr($name, 0, 16, "utf-8");
				    $name .= " ...";
				}
				if(strlen($creator) > 20){
				    $creator = mb_substr($creator, 0, 16, "utf-8");
				    $creator .= " ...";
				}
				$all_groups .= '<div class="all_groups_div"><a href="/group/'.$row["name"].'"><img class="all_groups_margin" src="groups/'.$row["name"].'/'.$row["logo"].'" alt="'.$row["name"].'" title="'.$row["name"].'" width="70" height="70" /></a><div class="all_groups_div2"><p><b>Group Name:</b> '.$name.'</p><p><b>Group Creator:</b> '.$creator.'</p><p><b>Created: </b>'.$creation_new.'</p><p><b>Join: </b>'.$invrule_new.'</p></div></div>';
			}
		}else{
			$all_groups .= "<p>There are no groups</p>";
		}
		$stmt->close();
	}
?>
<!DOCTYPE html>
<html>
<head>
	<title><?php echo $u; ?>&#39;s all groups</title>
	<meta charset="utf-8">
	<link rel="stylesheet" type="text/css" href="/style/style.css">
	<link rel="icon" type="image/x-icon" href="/images/webicon.png">
    <script src="/js/main.js" async></script>
    <meta name="description" content="See <?php echo $u; ?>&#39; all groups.">
    <script src="/js/ajax.js" async></script>
      <link rel="manifest" href="/manifest.json">
  <meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="#282828">
<meta name="apple-mobile-web-app-title" content="Pearscom">
<link rel="apple-touch-icon" href="/images/icons/icon-152x152.png">
<meta name="theme-color" content="#282828" />
</head>
<body>
	<?php require_once 'template_pageTop.php'; ?>
	<div id="pageMiddle_2">
		<p style="font-size: 22px; text-align: center;">My all groups</p>
		<p style="text-align: center;">Listed in an alphabetical order</p>
		<?php echo $all_groups; ?>
	</div>
</body>
</html>