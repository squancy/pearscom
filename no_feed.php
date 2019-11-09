<?php
	include_once("php_includes/check_login_statues.php");
	if(isset($_SESSION['username'])){
		$u = $_SESSION['username'];
	} else {
	   	header("Location: /need_to_be_logged_in");
	    exit();	
	}
?>
<!DOCTYPE html>
<html>
<head>
	<meta charset="UTF-8">
	<link rel="stylesheet" href="/style/style.css">
	<link rel="icon" type="image/x-icon" href="/images/webicon.png">
	<title><?php echo $u; ?> -  My feed</title>
	<script src="/js/main.js"></script>
	<script src="/js/ajax.js"></script>
</head>
<body>
	<?php require_once 'template_pageTop.php'; ?>
	<div id="pageMiddle_2">
		<p id="no_feed">No feed avilable.</p>
		<p id="no_feed_2">You have no friends at the moment. Click <a href="/friend_suggestions">here</a> to find yours.</p>
		<div id="error_pear"><img src="/images/error_pear" width="500" height="500"></div>
	</div>
	<?php require_once 'template_pageBottom.php'; ?>
</body>
</html>