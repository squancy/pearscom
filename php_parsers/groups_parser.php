<?php
	// Check group name
	if(isset($_POST["gnamecheck"])){
		$gname = mysqli_real_escape_string($conn, $_POST["gnamecheck"]);
		$sql = "SELECT id FROM groups WHERE name=? LIMIT 1";
		$stmt = $conn->prepare($sql);
		$stmt->bind_param("s",$gname);
		$stmt->execute();
		$stmt->store_result();
		$stmt->fetch();
		$gname_check = $stmt->num_rows;
	    // Length error
	    if (strlen($gname) < 3 || strlen($gname) > 50) {
		    echo '<p class="error_red" style="font-weight: normal;">3 - 50 characters please</p>';
		    exit();
	    }
	    // Begin error
		if (is_numeric($gname[0])) {
		    echo '<p class="error_red">Group names must begin with a letter</p>';
		    exit();
	    }
	    // Group name is OK
	    if ($gname_check < 1) {
		    echo '<img src="/images/correct.png" width="21" height="21" margin-left: -20px;>';
		    exit();
	    } else {
	    	// Group name is taken
		    echo '<p class="error_red"">' . $gname . ' is taken</p>';
		    exit();
	    }
	    $stmt->close();
	}
?>