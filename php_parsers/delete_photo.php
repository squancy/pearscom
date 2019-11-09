<?php
	require_once '../php_includes/check_login_statues.php';

	if(isset($_POST["id"]) && $_POST["id"] != ""){
		$id = preg_replace('#[^0-9]#', '', $_POST['id']);
		$sql = "SELECT id FROM users WHERE username = ? LIMIT 1";
		$stmt = $conn->prepare($sql);
		$stmt->bind_param("s",$log_username);
		$stmt->execute();
		$res = $stmt->get_result();
		if($res->num_rows < 1){
			header('../index');
			exit();
		}
		$stmt->close();

		$sql = "SELECT * FROM photos WHERE id = ? AND user = ? LIMIT 1";
		$stmt = $conn->prepare($sql);
		$stmt->bind_param("is",$id,$log_username);
		$stmt->execute();
		$res = $stmt->get_result();
		if($res->num_rows < 1){
			header('../index');
			exit();
		}
		$stmt->close();

		$sql = "DELETE FROM photos WHERE id = ? AND user = ? LIMIT 1";
		$stmt = $conn->prepare($sql);
		$stmt->bind_param("is",$id,$log_username);
		$stmt->execute();
		$stmt->close();

		// Make sure it is deleted
		$sql = "SELECT id FROM photos WHERE id = ? AND user = ? LIMIT 1";
		$stmt = $conn->prepare($sql);
		$stmt->bind_param("is",$id,$log_username);
		$stmt->execute();
		$res = $stmt->get_result();
		if($res->num_rows < 1){
			echo "delete_photo_success";
			exit();
		}else{
			echo "Unfortunately an unknown error has occured. Please try again later!";
			exit();
		}
		exit();
	}
?>