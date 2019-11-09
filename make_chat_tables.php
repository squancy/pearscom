<?php
	require_once 'php_includes/conn.php';

	$chat_table = "CREATE TABLE IF NOT EXISTS chats (
					id INT(11) NOT NULL AUTO_INCREMENT,
					user_ip VARCHAR(255) NOT NULL,
					username VARCHAR(255) NOT NULL,
					chat_body TEXT NULL,
					date_time DATETIME NULL,
					PRIMARY KEY(id)
					)";
	$query = mysqli_query($conn, $chat_table);
	if($query === true){
		echo "table created :)";
	}else{
		echo "table Not created :(";
	}
	mysqli_close($conn);
?>