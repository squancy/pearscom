<?php
	// Connect to database
	require_once '../php_includes/check_login_statues.php';

	// Ajax calls this code to execute
	if(isset($_POST["u"])){
		// Clean all the variables
		$u = mysqli_real_escape_string($conn, $_POST['u']);
		$p = mysqli_real_escape_string($conn, $_POST['p']);

		// Error handling
		if($p == "" || $u == ""){
			echo "Please fill out all the form data";
			exit();
		}else{
			// Delete from database
			$sql = "DELETE FROM articles WHERE written_by=? AND post_time=? LIMIT 1";
			$stmt = $conn->prepare($sql);
			$stmt->bind_param('ss',$u,$p);
			$stmt->execute();
			$stmt->close();
			echo "delete_success";
			exit();
		}
		exit();
	}
?>