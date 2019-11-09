<?php
	require_once 'php_includes/conn.php';

	$tbl_edit = "CREATE TABLE IF NOT EXISTS edit (
					id INT(11) NOT NULL,
					username VARCHAR(16) NOT NULL,
					job VARCHAR(255) NULL,
					schools VARCHAR(255) NULL,
					about TEXT(255) NULL,
					profession ENUM('s','w','0') NOT NULL DEFAULT '0',
					changemade DATETIME NOT NULL,
					PRIMARY KEY (id),
					UNIQUE KEY username (username)
				)";

	$query = mysqli_query($conn, $tbl_edit);
	if($query === TRUE){
		echo '<h3>edit table created OK :)</h3>';
	}else{
		echo '<h3>edit table NOT created :(</h3>';
	}
?>
