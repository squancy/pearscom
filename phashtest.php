<?php
	$password = "abcd123";
	$hash = password_hash($password, PASSWORD_DEFAULT);
	echo $hash;

	if (!password_verify($password, $hash)) {
    	echo "<br />not success";
	}else {
	    echo "success";
	}
?>