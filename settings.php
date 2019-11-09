<?php
	require_once 'php_includes/check_login_statues.php';
	require_once 'timeelapsedstring.php';
	require_once 'headers.php';
	// Initialize some variables
	$email = "";
	$password = "";
	$country = "";
	$one = "1";

	// Make sure the user logged in
	if($log_username == "" || $_SESSION["username"] == "" || !isset($_SESSION["username"])){
		header('Location: /index');
		exit();
	}

	// Select the member from the users table
    $sql = "SELECT * FROM users WHERE username=? AND activated=? LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss",$log_username,$one);
    $stmt->execute();
    $stmt->store_result();
    $stmt->fetch();
    $numrows = $stmt->num_rows;
    // Now make sure the user exists in the table
    if($numrows < 1){
    	header('location: /usernotexist');
    	exit();
    }
    $stmt->close();

	// User information query
	$sql = "SELECT * FROM users WHERE username=? AND activated=? LIMIT 1";
	$stmt = $conn->prepare($sql);
	$stmt->bind_param("ss",$log_username,$one);
	$stmt->execute();
	$result = $stmt->get_result();
	while($row = $result->fetch_assoc()){
		$email = $row["email"];
		$password = $row["password"];
		$country = $row["country"];
	}

	$chunk_email = mb_substr($email, 0, 3, "utf-8");
	$chuck_password = mb_substr($password, 0, 3, "utf-8");
	$chuck_country = mb_substr($country, 0, 3, "utf-8");

	$chuck_string = $chuck_password."/=%!".$chuck_country."l()=/".$chunk_email."..!+";

	$hashed_value = hash("gost",$chuck_string);

	$stmt->close();
?>
<?php
	// Ajax calls this code to execute (password)
	if(isset($_POST["cpasscheck"])){
		// CONNECT TO THE DATABASE
		include_once("php_includes/conn.php");
		// GATHER THE POSTED DATA INTO LOCAL VARIABLES
		$cp = $_POST['cpasscheck'];

		if(!password_verify($cp,$password)){
			echo '<img src="/images/wrong.png" width="13" height="13"><span class="tooltiptext">Current password field does not match</span>';
			exit();
		}else{
	    	echo "";
	    	exit();
	    }
	}
?>
<?php
	// Ajax calls this code to execute
	if(isset($_POST["npasscheck"]) && isset($_POST["cp"])){
		// CONNECT TO THE DATABASE
		include_once("php_includes/conn.php");
		// GATHER THE POSTED DATA INTO LOCAL VARIABLES
		$np = $_POST["npasscheck"];
		$cp = $_POST["cp"];
		$uc = preg_match('@[A-Z]@', $np);
		$lc = preg_match('@[a-z]@', $np);
		$nm = preg_match('@[0-9]@', $np);

		if(!$uc || !$lc || !$nm){
			echo '<img src="/images/wrong.png" width="13" height="13"><span class="tooltiptext">Your password requires at least 1 lowercase, 1 uppercase letter and 1 number</span>';
			exit();
		}else if(strlen($np) < 6){
			echo '<img src="/images/wrong.png" width="13" height="13"><span class="tooltiptext">Password needs to be at least 6 characters long</span>';
			exit();
		}else if($np == $log_username){
			echo '<img src="/images/wrong.png" width="13" height="13"><span class="tooltiptext">Cannot give username as password</span>';
			exit();
		}else if($np == $email){
			echo '<img src="/images/wrong.png" width="13" height="13"><span class="tooltiptext">Cannot give email as password</span>';
			exit();
		}else if($np == $cp){
			echo '<img src="/images/wrong.png" width="13" height="13"><span class="tooltiptext">This is your current password</span>';
			exit();
		}else{
	    	echo "";
	    	exit();
	    }
	}
?>
<?php 
	// Ajax calls this code to execute (password)
	if(isset($_POST["cnp"]) && isset($_POST["np"])){
		// CONNECT TO THE DATABASE
		include_once("php_includes/conn.php");
		// GATHER THE POSTED DATA INTO LOCAL VARIABLES
		$cnp = $_POST["cnp"];
		$np = $_POST["np"];

		if($cnp != $np){
			echo '<img src="/images/wrong.png" width="13" height="13"><span class="tooltiptext">New password field does not match</span>';
			exit();
		}else{
			echo "";
			exit();
		}
	}
?>
<?php 
	// Ajax calls this code to execute (password)
	if(isset($_POST["curp"]) && isset($_POST["newp"]) && isset($_POST["cnewp"])){
		// CONNECT TO THE DATABASE
		include_once("php_includes/conn.php");
		// GATHER THE POSTED DATA INTO LOCAL VARIABLES
		$curp = $_POST["curp"];
		$newp = $_POST["newp"];
		$cnewp = $_POST["cnewp"];
		$uc = preg_match('@[A-Z]@', $newp);
		$lc = preg_match('@[a-z]@', $newp);
		$nm = preg_match('@[0-9]@', $newp);

		if(!password_verify($curp,$password)){
			echo 'Current password field does not match!';
			exit();
		}else if(strlen($newp) < 6){
			echo 'Your password has to be at least 6 characters long!';
			exit();
		}else if($newp == $log_username){
			echo 'Do not give your username as your password!';
			exit();
		}else if($newp == $email){
			echo 'Do not give your email as your password!';
			exit();
		}else if($newp == $curp){
			echo 'This is your current password!';
			exit();
		}else if($cnewp != $newp){
			echo 'New password field does not match!';
			exit();
		}else if(!$uc || !$lc || !$nm){
			echo 'Your password requires at least 1 lowercase, 1 uppercase letter and 1 number!';
			exit();
		}else if($curp == "" || $newp == "" || $cnewp == ""){
			echo 'Please fill in all fields!';
			exit();
		}else{
			$p_hash = password_hash($newp, PASSWORD_DEFAULT);
			$sql = "UPDATE users SET password = ? WHERE username = ? AND email = ? LIMIT 1";
			$stmt = $conn->prepare($sql);
			$stmt->bind_param("sss",$p_hash,$log_username,$email);
			$stmt->execute();
			$stmt->close();

			// Log out user
			if(!isset($_SESSION)) 
		    {
		        session_start(); 
		    }
			// Set Session data to an empty array
			$_SESSION = array();
			// Expire their cookie files
			if(isset($_COOKIE["id"]) && isset($_COOKIE["user"]) && isset($_COOKIE["pass"])) {
				setcookie("id", '', strtotime( '-5 days' ), '/');
			    setcookie("user", '', strtotime( '-5 days' ), '/');
				setcookie("pass", '', strtotime( '-5 days' ), '/');
			}
			// Destroy the session variables
			session_destroy();
			// Double check to see if their sessions exists
			if(isset($_SESSION['username'])){
				header("location: logoutfail.php");
			}
			
			echo "cpass_success";
			exit();
		}
	}
?>
<?php
	// Ajax calls this code to execute (country)
	if(isset($_POST["ncountry"])){
		// CONNECT TO THE DATABASE
		include_once("php_includes/conn.php");
		// GATHER THE POSTED DATA INTO LOCAL VARIABLES
		$ncountry = preg_replace('#[^a-z0-9 .-]#i', '', $_POST['ncountry']);
		if($ncountry == $country){
			echo '<img src="/images/wrong.png" width="13" height="13"><span class="tooltiptext">This is your current country</span>';
			exit();
		}else{
	    	echo "";
	    	exit();
	    }
	}
?>
<?php
	// Ajax calls this code to execute (country)
	if(isset($_POST["confc"]) && isset($_POST["pwd"])){
		// CONNECT TO THE DATABASE
		include_once("php_includes/conn.php");
		// GATHER THE POSTED DATA INTO LOCAL VARIABLES
		$confc = preg_replace('#[^a-z0-9 .-]#i', '', $_POST['confc']);
		$pwd = $_POST["pwd"];
		if(!password_verify($pwd,$password)){
			echo 'Your current password field does not match!';
			exit();
		}else if($confc == $country){
			echo 'This is your current country!';
			exit();
		}else{
			$sql = "UPDATE users SET country = ? WHERE username = ? AND email = ? LIMIT 1";
			$stmt = $conn->prepare($sql);
			$stmt->bind_param("sss",$confc,$log_username,$email);
			$stmt->execute();
			$stmt->close();
		}

		echo "country_success";
		exit();
	}
?>
<?php
	// Ajax calls this code to execute (country)
	if(isset($_POST["nemail"])){
		// CONNECT TO THE DATABASE
		include_once("php_includes/conn.php");
		$nemail = $_POST["nemail"];
		$sql = "SELECT id FROM users WHERE email=? LIMIT 1";
	    $stmt = $conn->prepare($sql);
	    $stmt->bind_param("s",$nemail);
	    $stmt->execute();
	    $stmt->store_result();
	    $stmt->fetch();
	    $email_check = $stmt->num_rows;
		// GATHER THE POSTED DATA INTO LOCAL VARIABLE
		if($email_check > 0){
			echo '<img src="/images/wrong.png" width="13" height="13"><span class="tooltiptext">Email address is taken</span>';
			exit();
		}else if(!filter_var($nemail, FILTER_VALIDATE_EMAIL)){
			echo '<img src="/images/wrong.png" width="13" height="13"><span class="tooltiptext">Email is not valid</span>';
			exit();
		}else{
	    	echo "";
	    	exit();
	    }
	}
?>
<?php
	// Ajax calls this code to execute (country)
	if(isset($_POST["email"]) && isset($_POST["pass"])){
		// CONNECT TO THE DATABASE
		include_once("php_includes/conn.php");
		$nemail = $_POST["email"];
		$pass = $_POST["pass"];
		$sql = "SELECT id FROM users WHERE email=? LIMIT 1";
	    $stmt = $conn->prepare($sql);
	    $stmt->bind_param("s",$nemail);
	    $stmt->execute();
	    $stmt->store_result();
	    $stmt->fetch();
	    $email_check = $stmt->num_rows;
		// GATHER THE POSTED DATA INTO LOCAL VARIABLE
		if($email_check > 0){
			echo 'This email address is taken!';
			exit();
		}else if(!filter_var($nemail, FILTER_VALIDATE_EMAIL)){
			echo 'This email is not valid!';
			exit();
		}else if(!password_verify($pass,$password)){
			echo 'Current password field does not match!';
			exit();
		}else if($nemail == "" || $pass == ""){
			echo 'Fill out all the form data!';
			exit();
		}else{
			$sql = "UPDATE users SET email = ? WHERE username = ? AND email = ? LIMIT 1";
			$stmt = $conn->prepare($sql);
			$stmt->bind_param("sss",$nemail,$log_username,$email);
			$stmt->execute();
			$stmt->close();
		}
		echo "email_success";
		exit();
	}
?>
<?php
	if(isset($_POST["whyda"])){
		// CONNECT TO THE DATABASE
		include_once("php_includes/conn.php");
		$ta = mysqli_real_escape_string($conn, $_POST["whyda"]);
		if(strlen($ta) > 1000){
			echo '<img src="/images/wrong.png" width="13" height="13"><span class="tooltiptext">Maximum character limit reached</span>';
			exit();
		}else{
	    	echo "";
	    	exit();
	    }
	}
?>
<?php
	if(isset($_POST["whyda_"]) && isset($_POST["dacpass"])){
		// CONNECT TO THE DATABASE
		include_once("php_includes/conn.php");
		$ta = mysqli_real_escape_string($conn, $_POST["whyda_"]);
		$pass = $_POST["dacpass"];
		if(strlen($ta) > 1000){
			echo 'Maximum character limit reached in textarea!';
			exit();
		}else if(!password_verify($pass,$password)){
			echo 'Current password field does not match!';
			exit();
		}else if($pass == ""){
			echo 'Fill in all fields!';
			exit();
        }else{
			if($ta == ""){
				$sql = "INSERT INTO deleted_accs (username,delete_date) VALUES (?,NOW())";
				$stmt = $conn->prepare($sql);
				$stmt->bind_param("s",$log_username);
				$stmt->execute();
				$stmt->close();
			}else{
				$sql = "INSERT INTO deleted_accs (username,reason,delete_date) VALUES (?,?,NOW())";
				$stmt = $conn->prepare($sql);
				$stmt->bind_param("ss",$log_username,$ta);
				$stmt->execute();
				$stmt->close();
			}

			$sql = "DELETE FROM users WHERE username = ? AND email = ?";
			$stmt = $conn->prepare($sql);
			$stmt->bind_param("ss",$log_username,$email);
			$stmt->execute();
			$stmt->close();

			$sql = "DELETE FROM useroptions WHERE username = ?";
			$stmt = $conn->prepare($sql);
			$stmt->bind_param("s",$log_username);
			$stmt->execute();
			$stmt->close();

			$files = glob('/user/'.$log_username.'/*'); //get all file names
			foreach($files as $file){
			    if(is_file($file))
			    unlink($file); //delete file
			}

			$filenameb = '/user/'.$log_username.'/background';
			if(file_exists($filenameb)){
				$files2 = glob('/user/'.$log_username.'/background/*'); //get all file names
				foreach($files2 as $file2){
				    if(is_file($file2))
				    unlink($file2); //delete file
				}
			}

			$filenamev = '/user/'.$log_username.'/videos';
			if(file_exists($filenamev)){
				$files2 = glob('/user/'.$log_username.'/videos/*'); //get all file names
				foreach($files2 as $file3){
				    if(is_file($file3))
				    unlink($file3); //delete file
				}
			}
			if(is_dir('/user/'.$log_username.'/background')){
				rmdir('/user/'.$log_username.'/background');
			}

			if(is_dir('/user/'.$log_username.'/videos')){
				rmdir('/user/'.$log_username.'/videos');
			}

			if(is_dir('/user/'.$log_username)){
				rmdir('/user/'.$log_username);
			}

			// Then remove every clue of the person from the database
			$sql = "DELETE FROM articles WHERE written_by = ?";
			$stmt = $conn->prepare($sql);
			$stmt->bind_param("s",$log_username);
			$stmt->execute();
			$stmt->close();

			$sql = "DELETE FROM article_status WHERE account_name = ? OR author = ?";
			$stmt = $conn->prepare($sql);
			$stmt->bind_param("ss",$log_username,$log_username);
			$stmt->execute();
			$stmt->close();

			$sql = "DELETE FROM art_reply_likes WHERE username = ?";
			$stmt = $conn->prepare($sql);
			$stmt->bind_param("s",$log_username);
			$stmt->execute();
			$stmt->close();

			$sql = "DELETE FROM blockedusers WHERE blocker = ? OR blockee = ?";
			$stmt = $conn->prepare($sql);
			$stmt->bind_param("ss",$log_username,$log_username);
			$stmt->execute();
			$stmt->close();

			$sql = "DELETE FROM edit WHERE username = ?";
			$stmt = $conn->prepare($sql);
			$stmt->bind_param("s",$log_username);
			$stmt->execute();
			$stmt->close();

			$sql = "DELETE FROM fav_art WHERE username = ?";
			$stmt = $conn->prepare($sql);
			$stmt->bind_param("s",$log_username);
			$stmt->execute();
			$stmt->close();

			$sql = "DELETE FROM follow WHERE follower = ? OR following = ?";
			$stmt = $conn->prepare($sql);
			$stmt->bind_param("ss",$log_username,$log_username);
			$stmt->execute();
			$stmt->close();

			$sql = "DELETE FROM friends WHERE user1 = ? OR user2 = ?";
			$stmt = $conn->prepare($sql);
			$stmt->bind_param("ss",$log_username,$log_username);
			$stmt->execute();
			$stmt->close();

			$sql = "DELETE FROM gmembers WHERE mname = ?";
			$stmt = $conn->prepare($sql);
			$stmt->bind_param("s",$log_username);
			$stmt->execute();
			$stmt->close();

			$sql = "DELETE FROM grouppost WHERE author = ?";
			$stmt = $conn->prepare($sql);
			$stmt->bind_param("s",$log_username);
			$stmt->execute();
			$stmt->close();

			$sql = "DELETE FROM groups WHERE creator = ?";
			$stmt = $conn->prepare($sql);
			$stmt->bind_param("s",$log_username);
			$stmt->execute();
			$stmt->close();

			$sql = "DELETE FROM group_reply_likes WHERE username = ?";
			$stmt = $conn->prepare($sql);
			$stmt->bind_param("s",$log_username);
			$stmt->execute();
			$stmt->close();

			$sql = "DELETE FROM group_status_likes WHERE username = ?";
			$stmt = $conn->prepare($sql);
			$stmt->bind_param("s",$log_username);
			$stmt->execute();
			$stmt->close();

			$sql = "DELETE FROM heart_likes WHERE username = ?";
			$stmt = $conn->prepare($sql);
			$stmt->bind_param("s",$log_username);
			$stmt->execute();
			$stmt->close();

			$sql = "DELETE FROM notifications WHERE username = ? OR initiator = ?";
			$stmt = $conn->prepare($sql);
			$stmt->bind_param("ss",$log_username,$log_username);
			$stmt->execute();
			$stmt->close();

			$sql = "DELETE FROM photos WHERE user = ?";
			$stmt = $conn->prepare($sql);
			$stmt->bind_param("s",$log_username);
			$stmt->execute();
			$stmt->close();

			$sql = "DELETE FROM photos_status WHERE author = ? OR account_name = ?";
			$stmt = $conn->prepare($sql);
			$stmt->bind_param("ss",$log_username,$log_username);
			$stmt->execute();
			$stmt->close();

			$sql = "DELETE FROM photo_reply_likes WHERE username = ?";
			$stmt = $conn->prepare($sql);
			$stmt->bind_param("s",$log_username);
			$stmt->execute();
			$stmt->close();

			$sql = "DELETE FROM photo_stat_likes WHERE username = ?";
			$stmt = $conn->prepare($sql);
			$stmt->bind_param("s",$log_username);
			$stmt->execute();
			$stmt->close();

			$sql = "DELETE FROM pm WHERE sender = ? OR receiver = ?";
			$stmt = $conn->prepare($sql);
			$stmt->bind_param("s",$log_username);
			$stmt->execute();
			$stmt->close();

			$sql = "DELETE FROM photo_stat_likes WHERE username = ?";
			$stmt = $conn->prepare($sql);
			$stmt->bind_param("s",$log_username);
			$stmt->execute();
			$stmt->close();

			$sql = "DELETE FROM problem_report WHERE username = ?";
			$stmt = $conn->prepare($sql);
			$stmt->bind_param("s",$log_username);
			$stmt->execute();
			$stmt->close();

			$sql = "DELETE FROM reply_likes WHERE username = ?";
			$stmt = $conn->prepare($sql);
			$stmt->bind_param("s",$log_username);
			$stmt->execute();
			$stmt->close();

			$sql = "DELETE FROM status WHERE author = ? OR account_name = ?";
			$stmt = $conn->prepare($sql);
			$stmt->bind_param("ss",$log_username,$log_username);
			$stmt->execute();
			$stmt->close();

			$sql = "DELETE FROM status_likes WHERE username = ?";
			$stmt = $conn->prepare($sql);
			$stmt->bind_param("s",$log_username);
			$stmt->execute();
			$stmt->close();

			$sql = "DELETE FROM vidoes WHERE user = ?";
			$stmt = $conn->prepare($sql);
			$stmt->bind_param("s",$log_username);
			$stmt->execute();
			$stmt->close();

			$sql = "DELETE FROM video_likes WHERE username = ?";
			$stmt = $conn->prepare($sql);
			$stmt->bind_param("s",$log_username);
			$stmt->execute();
			$stmt->close();

			$sql = "DELETE FROM video_reply_likes WHERE user = ?";
			$stmt = $conn->prepare($sql);
			$stmt->bind_param("s",$log_username);
			$stmt->execute();
			$stmt->close();

			$sql = "DELETE FROM video_status WHERE author = ? OR account_name = ?";
			$stmt = $conn->prepare($sql);
			$stmt->bind_param("ss",$log_username,$log_username);
			$stmt->execute();
			$stmt->close();

			$sql = "DELETE FROM video_status_likes WHERE username = ?";
			$stmt = $conn->prepare($sql);
			$stmt->bind_param("s",$log_username);
			$stmt->execute();
			$stmt->close();
			
		}
		echo "delete_success";
		exit();
	}
?>
<?php
	if(isset($_POST["pwd"]) && isset($_POST["gender"])){
		$pwd = $_POST["pwd"];
		$gender = mysqli_real_escape_string($conn, $_POST["gender"]);
		$sql = "SELECT gender FROM users WHERE username = ? AND email = ? LIMIT 1";
		$stmt = $conn->prepare($sql);
		$stmt->bind_param("ss",$log_username,$email);
		$stmt->execute();
		$stmt->bind_result($curg);
		$stmt->fetch();
		$stmt->close();
		if($gender == "Male"){
			$gender = "m";
		}else if($gender == "Female"){
			$gender = "f";
		}else{
			echo 'Invalid gender given!';
			exit();
		}
		if(!password_verify($pwd,$password)){
			echo 'Password field does not match!';
			exit();
		}else if($gender == "" || $pwd == ""){
			echo 'Please fill in all fields!';
			exit();
		}else if($gender != "m" && $gender != "f"){
			echo 'Invalid gender given!';
			exit();
		}else if($gender == $curg){
			echo 'This is your current gender!';
			exit();
		}else{
			$sql = "UPDATE users SET gender = ? WHERE username = ? AND email = ? LIMIT 1";
			$stmt = $conn->prepare($sql);
			$stmt->bind_param("sss",$gender,$log_username,$email);
			$stmt->execute();
			$stmt->close();

			echo "gender_success";
			exit();
		}
	}
?>
<?php
	if(isset($_POST["cgend"])){
		$g = mysqli_real_escape_string($conn, $_POST["cgend"]);
		if($g == ""){
			echo '<img src="/images/wrong.png" width="13" height="13"><span class="tooltiptext">Fill in all fields</span>';
			exit();
		}else if($g != "Female" && $g != "Male"){
			echo '<img src="/images/wrong.png" width="13" height="13"><span class="tooltiptext">Invalid gender given</span>';
			exit();
		}else{
	    	echo "";
	    	exit();
	    }
	}
?>
<?php
	if(isset($_POST["timezone"])){
		$tz = $_POST["timezone"];
		if($tz == ""){
			echo '<img src="/images/wrong.png" width="13" height="13"><span class="tooltiptext">Fill in all fields</span>';
			exit();
		}else if(!in_array($tz, timezone_identifiers_list())){
			echo '<img src="/images/wrong.png" width="13" height="13"><span class="tooltiptext">Invalid timezone given</span>';
			exit();
		}else{
	    	echo "";
	    	exit();
	    }
	}
?>
<?php
	if(isset($_POST["pwd"]) && isset($_POST["tz"])){
		$pwd = $_POST["pwd"];
		$tz = $_POST["tz"];
		if(!password_verify($pwd,$password)){
			echo 'Give your current, valid password';
			exit();
		}else if($tz == "" || $pwd == ""){
			echo 'Fill in all fields';
			exit();
		}else if(!in_array($tz, timezone_identifiers_list())){
			echo 'Give a valid timezone';
			exit();
		}else{
			$tz = mysqli_real_escape_string($conn, $tz);
			$sql = "UPDATE users SET tz = ? WHERE username = ? AND email = ? LIMIT 1";
			$stmt = $conn->prepare($sql);
			$stmt->bind_param("sss",$tz,$log_username,$email);
			$stmt->execute();
			$stmt->close();

			echo "tz_success";
			exit();
		}
	}
?>
<?php
	if(isset($_POST["bd"]) && isset($_POST["pwd"])){
		$pwd = $_POST["pwd"];
		$bd = mysqli_real_escape_string($conn, $_POST["bd"]);
		if(!password_verify($pwd,$password)){
			echo 'Give your current, valid password';
			exit();
		}else if($bd == "" || $pwd == ""){
			echo 'Fill in all fields';
			exit();
		}else if($bd > date("Y-m-d") || $bd < date("1900-01-01")){
			echo 'Give a valid birthday';
			exit();
		}else{
			$sql = "UPDATE users SET bday = ? WHERE username = ? AND email = ? LIMIT 1";
			$stmt = $conn->prepare($sql);
			$stmt->bind_param("sss",$bd,$log_username,$email);
			$stmt->execute();
			$stmt->close();

			echo "bd_success";
			exit();
		}
	}
?>
<?php
	if(isset($_POST["cbirthd"])){
		$bd = mysqli_real_escape_string($conn, $_POST["cbirthd"]);
		if($bd == ""){
			echo '<img src="/images/wrong.png" width="13" height="13"><span class="tooltiptext">Fill in all fields</span>';
			exit();
		}else if($bd > date("Y-m-d") || $bd < date("1900-01-01")){
			echo '<img src="/images/wrong.png" width="13" height="13"><span class="tooltiptext">Give a valid birthday</span>';
			exit();
		}else{
	    	echo "";
	    	exit();
	    }
	}
?>
<?php
	if(isset($_POST["cusern"])){
		$un = $_POST["cusern"];
		$sql = "SELECT username FROM users WHERE username = ? LIMIT 1";
		$stmt = $conn->prepare($sql);
		$stmt->bind_param("s",$un);
		$stmt->execute();
		$stmt->bind_result($taken);
		$stmt->fetch();
		$stmt->close();
		
		if($un == ""){
			echo '<img src="/images/wrong.png" width="13" height="13"><span class="tooltiptext">Fill in all fields</span>';
			exit();
		}else if(strlen($un) < 3 || strlen($un) > 100){
			echo '<img src="/images/wrong.png" width="13" height="13"><span class="tooltiptext">Username must be between 6 and 100 characters</span>';
			exit();
		}else if(is_numeric($un[0])){
			echo '<img src="/images/wrong.png" width="13" height="13"><span class="tooltiptext">Username must begin with a letter</span>';
			exit();
		}else if($taken != "" && $taken != NULL){
		    echo '<img src="/images/wrong.png" width="13" height="13"><span class="tooltiptext">This username is taken</span>';
			exit();
		}else{
	    	echo "";
	    	exit();
	    }
	}
?>
<?php
	if(isset($_POST["pwd"]) && isset($_POST["cun"])){
		$un = $_POST["cun"];
		$pwd = $_POST["pwd"];
		$sql = "SELECT username FROM users WHERE username = ? LIMIT 1";
		$stmt = $conn->prepare($sql);
		$stmt->bind_param("s",$un);
		$stmt->execute();
		$stmt->bind_result($taken);
		$stmt->fetch();
		$stmt->close();
		
		if($un == "" || $pwd == ""){
			echo 'Fill in all fields';
			exit();
		}else if(strlen($un) < 3 || strlen($un) > 100){
			echo 'Username must be between 3 and 100 characters';
			exit();
		}else if(is_numeric($un[0])){
			echo 'Username must begin with a letter';
			exit();
		}else if(!password_verify($pwd,$password)){
			echo 'Incorrect password';
			exit();
		}else if($taken != "" && $taken != NULL){
		    echo 'This username is taken';
			exit();
		}else{
			$un = mysqli_real_escape_string($conn, $un);
			// Rename folders
			
			mkdir('user/'.$un.'/',0755);
			$fname = 'user/'.$log_username.'/background/';
			$fname2 = 'user/'.$log_username.'/videos/';
			mkdir('user/'.$un.'/background/',0755);
			mkdir('user/'.$un.'/videos/',0755);
			
			$len = strlen($log_username);
            $lena = $len + 6;
            $lenb = $len + 17;
            $lenv = $len + 13;
			
			$files = glob('user/'.$log_username.'/*'); //get all file names
			foreach($files as $file){
			    if(is_file($file)){
			        $file_ori = $file;
        	        $file = substr($file, $lena);
        	        $file = "$file";
        	        $wto = "user/$un/$file";
        	        rename($file_ori, $wto);
			    }
			}

			
			$files = glob('user/'.$log_username.'/background/*');
			foreach($files as $file){
			    if(is_file($file)){
			        $file_ori = $file;
        	        $file = substr($file, $lenb);
        	        $file = "$file";
        	        $wto = "user/$un/background/$file";
        	        rename($file_ori, $wto);
			    }
			}
	
			$files = glob('user/'.$log_username.'/videos/*');
			foreach($files as $file){
			    if(is_file($file)){
			        $file_ori = $file;
        	        $file = substr($file, $lenv);
        	        $file = "$file";
        	        $wto = "user/$un/videos/$file";
        	        rename($file_ori, $wto);
			    }
			}
    			
    	    // Remove old directories
    	    rmdir($fname);
    	    rmdir($fname2);
    	    rmdir('user/'.$log_username.'/');

			// Database renames
			$sql = "UPDATE articles SET written_by = ? WHERE written_by = ?";
			$stmt = $conn->prepare($sql);
			$stmt->bind_param("ss",$un,$log_username);
			$stmt->execute();
			$stmt->close();

			$sql = "UPDATE article_status SET author = ? WHERE author = ?";
			$stmt = $conn->prepare($sql);
			$stmt->bind_param("ss",$un,$log_username);
			$stmt->execute();
			$stmt->close();

			$sql = "UPDATE article_status SET account_name = ? WHERE account_name = ?";
			$stmt = $conn->prepare($sql);
			$stmt->bind_param("ss",$un,$log_username);
			$stmt->execute();
			$stmt->close();

			$sql = "UPDATE art_reply_likes SET username = ? WHERE username = ?";
			$stmt = $conn->prepare($sql);
			$stmt->bind_param("ss",$un,$log_username);
			$stmt->execute();
			$stmt->close();

			$sql = "UPDATE art_stat_likes SET username = ? WHERE username = ?";
			$stmt = $conn->prepare($sql);
			$stmt->bind_param("ss",$un,$log_username);
			$stmt->execute();
			$stmt->close();

			$sql = "UPDATE blockedusers SET blockee = ? WHERE blockee = ?";
			$stmt = $conn->prepare($sql);
			$stmt->bind_param("ss",$un,$log_username);
			$stmt->execute();
			$stmt->close();

			$sql = "UPDATE blockedusers SET blocker = ? WHERE blocker = ?";
			$stmt = $conn->prepare($sql);
			$stmt->bind_param("ss",$un,$log_username);
			$stmt->execute();
			$stmt->close();

			$sql = "UPDATE deleted_accs SET username = ? WHERE username = ?";
			$stmt = $conn->prepare($sql);
			$stmt->bind_param("ss",$un,$log_username);
			$stmt->execute();
			$stmt->close();

			$sql = "UPDATE edit SET username = ? WHERE username = ?";
			$stmt = $conn->prepare($sql);
			$stmt->bind_param("ss",$un,$log_username);
			$stmt->execute();
			$stmt->close();

			$sql = "UPDATE fav_art SET username = ? WHERE username = ?";
			$stmt = $conn->prepare($sql);
			$stmt->bind_param("ss",$un,$log_username);
			$stmt->execute();
			$stmt->close();

			$sql = "UPDATE friends SET user1 = ? WHERE user1 = ?";
			$stmt = $conn->prepare($sql);
			$stmt->bind_param("ss",$un,$log_username);
			$stmt->execute();
			$stmt->close();
			
			$sql = "UPDATE friends SET user2 = ? WHERE user2 = ?";
			$stmt = $conn->prepare($sql);
			$stmt->bind_param("ss",$un,$log_username);
			$stmt->execute();
			$stmt->close();

			$sql = "UPDATE gmembers SET mname = ? WHERE mname = ?";
			$stmt = $conn->prepare($sql);
			$stmt->bind_param("ss",$un,$log_username);
			$stmt->execute();
			$stmt->close();

			$sql = "UPDATE grouppost SET author = ? WHERE author = ?";
			$stmt = $conn->prepare($sql);
			$stmt->bind_param("ss",$un,$log_username);
			$stmt->execute();
			$stmt->close();

			$sql = "UPDATE groups SET creator = ? WHERE creator = ?";
			$stmt = $conn->prepare($sql);
			$stmt->bind_param("ss",$un,$log_username);
			$stmt->execute();
			$stmt->close();

			$sql = "UPDATE group_reply_likes SET username = ? WHERE username = ?";
			$stmt = $conn->prepare($sql);
			$stmt->bind_param("ss",$un,$log_username);
			$stmt->execute();
			$stmt->close();

			$sql = "UPDATE group_status_likes SET username = ? WHERE username = ?";
			$stmt = $conn->prepare($sql);
			$stmt->bind_param("ss",$un,$log_username);
			$stmt->execute();
			$stmt->close();

			$sql = "UPDATE heart_likes SET username = ? WHERE username = ?";
			$stmt = $conn->prepare($sql);
			$stmt->bind_param("ss",$un,$log_username);
			$stmt->execute();
			$stmt->close();

			$sql = "UPDATE invite SET inviter = ? WHERE inviter = ?";
			$stmt = $conn->prepare($sql);
			$stmt->bind_param("ss",$un,$log_username);
			$stmt->execute();
			$stmt->close();

			$sql = "UPDATE notifications SET initiator = ? WHERE initiator = ?";
			$stmt = $conn->prepare($sql);
			$stmt->bind_param("ss",$un,$log_username);
			$stmt->execute();
			$stmt->close();

			$sql = "UPDATE notifications SET username = ? WHERE username = ?";
			$stmt = $conn->prepare($sql);
			$stmt->bind_param("ss",$un,$log_username);
			$stmt->execute();
			$stmt->close();

			$sql = "UPDATE photos SET user = ? WHERE user = ?";
			$stmt = $conn->prepare($sql);
			$stmt->bind_param("ss",$un,$log_username);
			$stmt->execute();
			$stmt->close();

			$sql = "UPDATE photos_status SET author = ? WHERE author = ?";
			$stmt = $conn->prepare($sql);
			$stmt->bind_param("ss",$un,$log_username);
			$stmt->execute();
			$stmt->close();

			$sql = "UPDATE photos_status SET account_name = ? WHERE account_name = ?";
			$stmt = $conn->prepare($sql);
			$stmt->bind_param("ss",$un,$log_username);
			$stmt->execute();
			$stmt->close();

			$sql = "UPDATE photo_reply_likes SET username = ? WHERE username = ?";
			$stmt = $conn->prepare($sql);
			$stmt->bind_param("ss",$un,$log_username);
			$stmt->execute();
			$stmt->close();

			$sql = "UPDATE photo_stat_likes SET username = ? WHERE username = ?";
			$stmt = $conn->prepare($sql);
			$stmt->bind_param("ss",$un,$log_username);
			$stmt->execute();
			$stmt->close();

			$sql = "UPDATE pm SET sender = ? WHERE sender = ?";
			$stmt = $conn->prepare($sql);
			$stmt->bind_param("ss",$un,$log_username);
			$stmt->execute();
			$stmt->close();

			$sql = "UPDATE pm SET receiver = ? WHERE receiver = ?";
			$stmt = $conn->prepare($sql);
			$stmt->bind_param("ss",$un,$log_username);
			$stmt->execute();
			$stmt->close();

			$sql = "UPDATE problem_report SET username = ? WHERE username = ?";
			$stmt = $conn->prepare($sql);
			$stmt->bind_param("ss",$un,$log_username);
			$stmt->execute();
			$stmt->close();

			$sql = "UPDATE reply_likes SET username = ? WHERE username = ?";
			$stmt = $conn->prepare($sql);
			$stmt->bind_param("ss",$un,$log_username);
			$stmt->execute();
			$stmt->close();

			$sql = "UPDATE status SET author = ? WHERE author = ?";
			$stmt = $conn->prepare($sql);
			$stmt->bind_param("ss",$un,$log_username);
			$stmt->execute();
			$stmt->close();

			$sql = "UPDATE status SET account_name = ? WHERE account_name = ?";
			$stmt = $conn->prepare($sql);
			$stmt->bind_param("ss",$un,$log_username);
			$stmt->execute();
			$stmt->close();

			$sql = "UPDATE useroptions SET username = ? WHERE username = ?";
			$stmt = $conn->prepare($sql);
			$stmt->bind_param("ss",$un,$log_username);
			$stmt->execute();
			$stmt->close();

			$sql = "UPDATE users SET username = ? WHERE username = ?";
			$stmt = $conn->prepare($sql);
			$stmt->bind_param("ss",$un,$log_username);
			$stmt->execute();
			$stmt->close();

			$sql = "UPDATE videos SET user = ? WHERE user = ?";
			$stmt = $conn->prepare($sql);
			$stmt->bind_param("ss",$un,$log_username);
			$stmt->execute();
			$stmt->close();

			$sql = "UPDATE video_likes SET username = ? WHERE username = ?";
			$stmt = $conn->prepare($sql);
			$stmt->bind_param("ss",$un,$log_username);
			$stmt->execute();
			$stmt->close();

			$sql = "UPDATE video_reply_likes SET user = ? WHERE user = ?";
			$stmt = $conn->prepare($sql);
			$stmt->bind_param("ss",$un,$log_username);
			$stmt->execute();
			$stmt->close();

			$sql = "UPDATE video_status SET author = ? WHERE author = ?";
			$stmt = $conn->prepare($sql);
			$stmt->bind_param("ss",$un,$log_username);
			$stmt->execute();
			$stmt->close();

			$sql = "UPDATE video_status SET account_name = ? WHERE account_name = ?";
			$stmt = $conn->prepare($sql);
			$stmt->bind_param("ss",$un,$log_username);
			$stmt->execute();
			$stmt->close();

			$sql = "UPDATE video_status_likes SET username = ? WHERE username = ?";
			$stmt = $conn->prepare($sql);
			$stmt->bind_param("ss",$un,$log_username);
			$stmt->execute();
			$stmt->close();

			echo "un_success";
			exit();
		}
	}
?>
<!DOCTYPE html>
<html>
<head>
	<title>Profile Settings - <?php echo $log_username; ?></title>
	<meta charset="utf-8">
	<link rel="icon" type="image/x-icon" href="/images/newfav.png">
	<link rel="stylesheet" type="text/css" href="/style/style.css">
	<link rel="stylesheet" href="/font-awesome-4.7.0/css/font-awesome.min.css">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<script src="/js/jjs.js"></script>
	<script src="/js/main.js" async></script>
	<script src="/js/ajax.js" async></script>
	<script src="/js/create_down.js" async></script>
		  <link rel="manifest" href="/manifest.json">
  <meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="#282828">
<meta name="apple-mobile-web-app-title" content="Pearscom">
<link rel="apple-touch-icon" href="/images/icons/icon-152x152.png">
<meta name="theme-color" content="#282828" />
	<style type="text/css">
		#pageMiddle_2{
      padding: 30px; font-size: 14px; margin-bottom: 10px !important;
    }
    @media only screen and (max-width: 768px){
      #pageMiddle_2{
        padding: 20px;
      }
    }
	</style>
	<script type="text/javascript">
		function emptyElement(e) {
		    _(e).innerHTML = ""
		}
		function checkcpassword(e, n) {
		    var a = _(n).value;
		    if ("" != a) {
		        var t = ajaxObj("POST", "settings.php");
		        t.onreadystatechange = function () {
		            1 == ajaxReturn(t) && (_(e).innerHTML = t.responseText)
		        }, t.send("cpasscheck=" + a)
		    }
		}
		function checknpassword() {
		    var e = _("npass").value,
		        n = _("cpass").value;
		    if ("" != e) {
		        var a = ajaxObj("POST", "settings.php");
		        a.onreadystatechange = function () {
		            1 == ajaxReturn(a) && (_("npassstatus").innerHTML = a.responseText)
		        }, a.send("npasscheck=" + e + "&cp=" + n)
		    }
		}
		function checkcnpcheck() {
		    var e = _("cnpass").value,
		        n = _("npass").value;
		    if ("" != e) {
		        var a = ajaxObj("POST", "settings.php");
		        a.onreadystatechange = function () {
		            1 == ajaxReturn(a) && (_("cnpassstatus").innerHTML = a.responseText)
		        }, a.send("cnp=" + e + "&np=" + n)
		    }
		}
		function changePass() {
		    var e = _("cpass").value,
		        n = _("npass").value,
		        a = _("cnpass").value,
		        t = _("pass_status"),
		        s = n.match(/[a-z]/) ? 1 : 0,
		        o = n.match(/[A-Z]/) ? 1 : 0,
		        i = n.match(/[0-9]/) ? 1 : 0;
		    if ("" == e || "" == n || "" == a) t.innerHTML = "Fill out all the form data";
		    else if (n != a) t.innerHTML = "Your new password fileds do not match";
		    else if (s && o && i) {
		        _("confirmpass").style.display = "none", t.innerHTML = '<img src="/images/rolling.gif" width="30" height="30">';
		        var c = ajaxObj("POST", "/settings");
		        c.onreadystatechange = function () {
		            1 == ajaxReturn(c) && ("cpass_success" != c.responseText ? (t.innerHTML = c.responseText, _("confirmpass").style.display = "block") : (t.innerHTML = 'You have successfully changed your password!', window.location.href = "/login"))
		        }
		    } else t.innerHTML = "Password requires at least 1 uppercase, 1 lowercase, and 1 number";
		    c.send("curp=" + e + "&newp=" + n + "&cnewp=" + a)
		}
		function checkncountry() {
		    var e = _("ncountry").value;
		    if ("" != e) {
		        var n = ajaxObj("POST", "settings.php");
		        n.onreadystatechange = function () {
		            1 == ajaxReturn(n) && (_("ncontstatus").innerHTML = n.responseText)
		        }, n.send("ncountry=" + e)
		    }
		}
		function changeCountry() {
		    var e = _("ncountry").value,
		        n = _("curpass").value,
		        a = _("country_span");
		    if ("" == e || "" == n) a.innerHTML = "Fill out all the form data";
		    else {
		        _("confcountry").style.display = "none", a.innerHTML = '<img src="/images/rolling.gif" width="30" height="30">';
		        var t = ajaxObj("POST", "/settings");
		        t.onreadystatechange = function () {
		            1 == ajaxReturn(t) && ("country_success" != t.responseText ? (a.innerHTML = t.responseText, _("confcountry").style.display = "block") : _("country_span").innerHTML = 'You have successfully changed your country')
		        }
		    }
		    t.send("confc=" + e + "&pwd=" + n)
		}
		function changeEmail() {
		    var e = _("nemail").value,
		        n = _("curpassem").value,
		        a = _("email_status");
		    if ("" == e || "" == n) a.innerHTML = "Fill out all the form data";
		    else {
		        _("confemail").style.display = "none", a.innerHTML = '<img src="/images/rolling.gif" width="30" height="30">';
		        var t = ajaxObj("POST", "settings.php");
		        t.onreadystatechange = function () {
		            1 == ajaxReturn(t) && ("email_success" != t.responseText ? (a.innerHTML = t.responseText, _("confemail").style.display = "block") : (_("confemail").innerHTML = 'You have successfully changed your email address', window.location.href = "/login"))
		        }
		    }
		    t.send("email=" + e + "&pass=" + n)
		}
		function deleteAcc() {
		    var e = _("dacpass").value,
		        n = _("delete_status"),
		        a = _("whyda").value;
		    if ("" == e) n.innerHTML = "Fill out all the form data";
		    else {
		        n.innerHTML = '<img src="/images/rolling.gif" width="30" height="30">';
		        var t = ajaxObj("POST", "settings.php");
		        t.onreadystatechange = function () {
		        	if (1 != confirm("Are you sure you want to delete your account?")) return !1;
		            1 == ajaxReturn(t) && ("delete_success" != t.responseText ? (n.innerHTML = t.responseText, _("confda").style.display = "block") : (_("confda").innerHTML = 'You have successfully deleted your account', window.location.href = "/logout"))
		        }, t.send("dacpass=" + e + "&whyda_=" + a)
		    }
		}
		function changeGender() {
		    var e = _("cg_gender").value,
		        n = _("fullcgstat"),
		        a = _("cgpass").value;
		    if ("" == a || "" == e) n.innerHTML = "Fill out all the form data";
		    else {
		        n.innerHTML = '<img src="/images/rolling.gif" width="30" height="30">';
		        var t = ajaxObj("POST", "settings.php");
		        t.onreadystatechange = function () {
		            1 == ajaxReturn(t) && ("gender_success" != t.responseText ? (n.innerHTML = t.responseText, n.style.display = "block") : (n.innerHTML = 'You have successfully changed your gender', window.location.href = "/login"))
		        }, t.send("gender=" + e + "&pwd=" + a)
		    }
		}
		function changeTz() {
		    var e = _("tz_zone").value,
		        n = _("fulltzstat"),
		        a = _("tzpass").value;
		    if ("" == a || "" == e) n.innerHTML = "Fill out all the form data";
		    else {
		        n.innerHTML = '<img src="/images/rolling.gif" width="30" height="30">';
		        var t = ajaxObj("POST", "settings.php");
		        t.onreadystatechange = function () {
		            1 == ajaxReturn(t) && ("tz_success" != t.responseText ? (n.innerHTML = t.responseText, n.style.display = "block") : (_("conftz").innerHTML = 'You have successfully changed your timezone', window.location.href = "/login"))
		        }, t.send("tz=" + e + "&pwd=" + a)
		    }
		}
		function changeBd() {
		    var e = _("bd_day").value,
		        n = _("fullbdqm"),
		        a = _("bdpass").value;
		    if ("" == a || "" == e) n.innerHTML = "Fill out all the form data";
		    else {
		        n.innerHTML = '<img src="/images/rolling.gif" width="30" height="30">';
		        var t = ajaxObj("POST", "settings.php");
		        t.onreadystatechange = function () {
		            1 == ajaxReturn(t) && ("bd_success" != t.responseText ? (n.innerHTML = t.responseText, n.style.display = "block") : (_("confbd").innerHTML = 'You have successfully changed your birthday', window.location.href = "/login"))
		        }, t.send("bd=" + e + "&pwd=" + a)
		    }
		}
		function changeUname() {
		    var e = _("un_name").value,
		        n = _("fullunstat"),
		        a = _("unpass").value;
		    if ("" == a || "" == e) n.innerHTML = "Fill out all the form data";
		    else {
		        n.innerHTML = '<img src="/images/rolling.gif" width="30" height="30">';
		        var t = ajaxObj("POST", "settings.php");
		        t.onreadystatechange = function () {
		            1 == ajaxReturn(t) && ("un_success" != t.responseText ? (n.innerHTML = t.responseText, n.style.display = "block") : (_("confun").innerHTML = 'You have successfully changed your username', window.location.href = "/logout"))
		        }, t.send("cun=" + e + "&pwd=" + a)
		    }
		}
		function checknemail() {
		    var e = _("nemail").value;
		    if ("" != e) {
		        var n = ajaxObj("POST", "settings.php");
		        n.onreadystatechange = function () {
		            1 == ajaxReturn(n) && (_("nemailastatus").innerHTML = n.responseText)
		        }, n.send("nemail=" + e)
		    }
		}
		function checkUname() {
		    var e = _("un_name").value;
		    if ("" != e) {
		        var n = ajaxObj("POST", "settings.php");
		        n.onreadystatechange = function () {
		            1 == ajaxReturn(n) && (_("un_ustatus").innerHTML = n.responseText)
		        }, n.send("cusern=" + e)
		    }
		}
		function checkbd() {
		    var e = _("bd_day").value;
		    if ("" != e) {
		        var n = ajaxObj("POST", "settings.php");
		        n.onreadystatechange = function () {
		            1 == ajaxReturn(n) && (_("bd_bstatus").innerHTML = n.responseText)
		        }, n.send("cbirthd=" + e)
		    }
		}
		function checkwhyda() {
		    var e = _("whyda").value;
		    if ("" != e) {
		        var n = ajaxObj("POST", "settings.php");
		        n.onreadystatechange = function () {
		            1 == ajaxReturn(n) && (_("whydaqmstatus").innerHTML = n.responseText)
		        }, n.send("whyda=" + e)
		    }
		}
		function statusMax(e, n) {
		    e.value.length > n && (_("overlay").style.display = "block", _("overlay").style.opacity = .5, _("dialogbox").style.display = "block", _("dialogbox").innerHTML = '<p style="font-size: 18px; margin: 0px;">Maximum character limit reached</p><p>For some reasons we limited the number of characters that you can write at the same time. Now you have reached this limit.</p><br /><button id="vupload" style="position: absolute; right: 3px; bottom: 3px;" onclick="closeDialog()">Close</button>', document.body.style.overflow = "hidden", e.value = e.value.substring(0, n))
		}
		function checkgender() {
		    var e = _("cg_gender").value;
		    if ("" != e) {
		        var n = ajaxObj("POST", "settings.php");
		        n.onreadystatechange = function () {
		            1 == ajaxReturn(n) && (_("cg_gstatus").innerHTML = n.responseText)
		        }, n.send("cgend=" + e)
		    }
		}
		function checktz() {
		    var e = _("tz_zone").value;
		    if ("" != e) {
		        var n = ajaxObj("POST", "settings.php");
		        n.onreadystatechange = function () {
		            1 == ajaxReturn(n) && (_("tz_tstatus").innerHTML = n.responseText)
		        }, n.send("timezone=" + e)
		    }
		}
	</script>
</head>
<body>
	<?php include_once("template_pageTop.php"); ?>
	<div id="pageMiddle_2" style="font-size: 14px;">
	  <p style="font-size: 22px; color: #999; margin-top: 0;">Profile Settings</p>

	  	<div class="collection" id="ccSu">
	      <p style="font-size: 18px;" id="signup">General Information</p>
	      <img src="/images/alldd.png">
	    </div>

		<div class="slideInfo" id="suDD">
			<p style="margin-bottom: 0;">
			  	&bull; We do not recommend you to change your confidental information regulary because it can be quite confusing both for you and your friends.<br />
			  	&bull; Before you start anything visit our <a href="/help">help</a> page to get more essential information about your profile settings! We will not be resposnible for any mistakes if you did not read and understood it!<br />
			  	&bull; Do not use this page for account stealing or abusing with other users&#39; personal information!<br />
			  	&bull; Do not give or change your datas into fake ones! If you gave fake a fake email address, country or timezone etc. we will not be responsible for any sort of mistakes or misunderstoods around your profile and friends!<br />
			  	&bull; If you choose deleting your account please note that we will not be able to bring back any of your passwords, email addresses, photos, videos or any kind of personal datas! Once if you deleted that&#39;s gone!<br />
			  	&bull; If you change your country, timezone, birthday etc. we will send a notification for your friends to keep them up to date about the things happenning around you. (Obviously we will not send your email address, passwords or any kind of personal or confidental information about you that can break your account security!)<br />
			  	&bull; For security reasons you will need to give your current password for ANY changes you want to make on your profile. This method will reduce the number of broken and stolen accounts and data abusings.<br />
			  	&bull; Changing your gender or even your birthday is (almost) impossible, however we are still open for it and you have the chance to change it. If you gave false or not up-to-date information about you in the signing up you can correct it here.
			  	<br /><br />
			  	Thank you for your understanding and patience!
			  	<br /><br />
			  </p>
		</div>

		<div class="collection" id="ccDa">
	      <p style="font-size: 18px;" id="signup">Dark mode</p>
	      <img src="/images/alldd.png">
	    </div>

	    <div class="slideInfo" id="daDD">
	  		<p>In a light poor environment the dark mode can be a lot more confident and relaxing for your eyes than the normal daylight mode. Turning on dark mode will result a darker theme where you do not need to strengthen your eyes even at night and will be applied only to the current browser.<br>
	  		In that case if you delete browser datas, cookies and history from the current browser this feature will be automatically turned to normal mode independently from earlier settings.</p>
  		    <br>
			<b style="font-weight: normal;">Turn off/on dark mode</b>
			<div class="shorterdiv">
  				<!--<label class="switch">
                  <input type="checkbox" onchange="toggleDark()" id="tdark">
                  <span class="slider round" style="height: 21px !important;"></span>
                </label>-->
                Under construction
  			</div>
		</div>
	  	
		<div class="collection" id="ccUs">
	      <p style="font-size: 18px;" id="signup">Username</p>
	      <img src="/images/alldd.png">
	    </div>

		<div class="slideInfo" id="usDD">
	  		<form id="disableit" name="disableit" onsubmit="return false;">
		  		<p>If you changed your real name or you just want to change your username on Pearscom you can do it in this section. Please note that your username has to be at least 5 and it can be maximum 100 characters long. It can contain any characters and signs, however we highy encourage everyone to give their real- (John Williams), nick- (Willy) or fantasy name (Nutella Pancake) which is recognizable for their friends and other people.<br><br><b>Important:</b> when you successfully changed your username you will be logged out and you will need to log in again!</p>
		  		<div class="pplongbtn main_btn_fill widen" id="unlongbtn">Change username</div>
		  		<div class="settingsall styleform" style="margin-top: 10px;">
	  				<input type="password" name="unpass" id="unpass" onblur="checkcpassword('un_status','unpass')" maxlength="255" placeholder="Current password">

	  				<span class="signupStats" style="right: 10px; margin-top: -26px;" id="un_status"></span>

	  				<input type="text" id="un_name" onblur="checkUname()" placeholder="New username">

	  				<span id="un_ustatus" class="signupStats" style="right: 10px; margin-top: -26px;"></span>
	  				
	  				<br>
					<button id="confun" class="pplongbtn main_btn_fill settBtns" onclick="changeUname()">Confirm</button>
					<div id="fullunstat" style="text-align: center; margin-top: 10px;"></div>
		  		</div>
		  	</form>
		  </div>


	  		<div class="collection" id="ccPw">
		      <p style="font-size: 18px;" id="signup">Password</p>
		      <img src="/images/alldd.png">
		    </div>

		    <div class="slideInfo" id="pwDD">
		  		<p>If you wish to change your current password to a stronger one or for any reason this is the right section. For security reasons we have to look up for your current password and the new one has to contain at least 1 uppercase, 1 lowercase letter and a number. This will make your password a lot more stronger and unguessable.<br><br><b>Important:</b> when you successfully changed your password you will be logged out and you will need to log in again with your new password!</p>
		  		<div class="pplongbtn main_btn_fill widen" id="chplongbtn">Change password</div>
		  		<div class="settingsall styleform" style="margin-top: 10px;">
		  			<input type="password" name="cpass" id="cpass" onblur="checkcpassword('cpassstatus','cpass')" maxlength="255" placeholder="Current password">

		  			<span id="cpassstatus" class="signupStats" style="right: 10px; margin-top: -26px;"></span>

		  			<input type="password" name="npass" id="npass" onblur="checknpassword()" maxlength="255" placeholder="New password">

		  			<span id="npassstatus" class="signupStats" style="right: 10px; margin-top: -26px;"></span>

		  			<input type="password" name="cnpass" id="cnpass" onblur="checkcnpcheck()" maxlength="255" placeholder="Confirm password">

		  			<span id="cnpassstatus" class="signupStats" style="right: 10px; margin-top: -26px;"></span>
		  			
		  			<br />
		  			<button id="confirmpass" class="pplongbtn main_btn_fill settBtns" onclick="changePass()">Confirm</button>
		  			<div id="pass_status" style="text-align: center; margin-top: 10px;"></div>
		  		</div>
		  	</div>

		  	<div class="collection" id="ccCy">
		      <p style="font-size: 18px;" id="signup">Country</p>
		      <img src="/images/alldd.png">
		    </div>

		    <div class="slideInfo" id="cyDD">
		  		<p>If you moved to a completely another country you can change it here. Please be careful responsible! Changing your location to a fake one will mess up your friends and our geolocation system will get back the proper datas. Nonetheless we want you to give your current and real country to make sure you are the right user.</p>
		  		<div class="pplongbtn main_btn_fill widen" id="countrylbtn">Change country</div>
	  			<div class="settingsall styleform" style="margin-top: 10px;">
	  				<input type="password" name="curpass" id="curpass" onblur="checkcpassword('curqmstatus','curpass')" class="fitSmall" maxlength="255" placeholder="Current password">

	  				<span id="curqmstatus" class="signupStats" style="right: 10px; margin-top: -26px;"></span>

					<select id="ncountry" onblur="checkncountry()" class="fitSmall">
						<option value="" selected="true" disabled="true">Change country</option>
						<?php require_once 'template_country_list.php'; ?>
					</select>

					<span id="ncontstatus" class="signupStats" style="right: 10px; margin-top: -26px;"></span>
					<div class="clear"></div>
					<br />
					<button id="confcountry" class="pplongbtn main_btn_fill settBtns" onclick="changeCountry()">Confirm</button>
					<div id="country_span" style="text-align: center; margin-top: 10px;"></div>
	  			</div>
		  	</div>

		  	<div class="collection" id="ccEm">
		      <p style="font-size: 18px;" id="signup">Email address</p>
		      <img src="/images/alldd.png">
		    </div>

		    <div class="slideInfo" id="emDD">
		  		<p>You have the opportunity to change your email address, too. If your current email address is not comfortable or reliable for you we recommend to change it to a valid and secure email that you check every day! After you changed your email every sort of notifications, password claims or activation messages will be sent to that new one.<br /><br><b>Important: </b>when you successfully changed your email you will be logged out and you will need to log in again with your new email address!</p>
		  		<div class="pplongbtn main_btn_fill widen" id="emaillongbtn">Change email address</div>
		  		<div class="settingsall styleform" style="margin-top: 10px;">
	  				<input type="password" name="curpassem" id="curpassem" onblur="checkcpassword('curqmstatusem','curpassem')" maxlength="255" placeholder="Current password">

	  				<span id="curqmstatusem" class="signupStats" style="right: 10px; margin-top: -26px;"></span>

					<input type="text" name="nemail" id="nemail" maxlength="255" onblur="checknemail()" placeholder="New email address">

					<span id="nemailastatus" class="signupStats" style="right: 10px; margin-top: -26px;"></span>

					<br />
					<button id="confemail" class="pplongbtn main_btn_fill settBtns" onclick="changeEmail()">Confirm</button>
					<div id="email_status" style="text-align: center; margin-top: 10px;"></div>
		  		</div>
		  	</div>

		  	<div class="collection" id="ccGe">
		      <p style="font-size: 18px;" id="signup">Gender</p>
		      <img src="/images/alldd.png">
		    </div>

		    <div class="slideInfo" id="geDD">
		  		<p>Gender chaning can sounds quite strange for a lot of people, however we do NOT condemn or discriminate against anyone, therefore you can independently change it. Please note that your friends and followers will get a notification about the change in order to keep them up-to-date with you.</p>
		  		<div class="pplongbtn main_btn_fill widen" id="cglongbtn">Change gender</div>
		  		<div class="settingsall styleform" style="margin-top: 10px;">
	  				<input type="password" name="cgpass" id="cgpass" onblur="checkcpassword('cg_status','cgpass')" maxlength="255" class="fitSmall" placeholder="Current password">

	  				<span id="cg_status" class="signupStats" style="right: 10px; margin-top: -26px;"></span>

	  				<select id="cg_gender" onblur="checkgender()" class="fitSmall">
	  					<option value="" disabled="true" selected="true">Change gender</option>
	  					<option value="Male">Male</option>
	  					<option value="Female">Female</option>
	  				</select>

	  				<span id="cg_gstatus" class="signupStats" style="right: 10px; margin-top: -26px;"></span>
	  				<div class="clear"></div>
					<br>

					<button id="confcg" class="pplongbtn main_btn_fill settBtns" onclick="changeGender()">Confirm</button>
					<div id="fullcgstat" style="text-align: center; margin-top: 10px;"></div>
		  		</div>
	  		</div>

	  		<div class="collection" id="ccTz">
		      <p style="font-size: 18px;" id="signup">Timezone</p>
		      <img src="/images/alldd.png">
		    </div>

		    <div class="slideInfo" id="tzDD">
		  		<p>In connection with country changing, if you moved to another country you can also select the local timezone there. Please give a valid and correct timezone in order to provide reliable and local time &amp; dates for you. Otherwise the whole time-system will be messed up together with your friends and you!</p>
		  		<div class="pplongbtn main_btn_fill widen" id="tzlongbtn">Change timezone</div>
		  		<div class="settingsall styleform" style="margin-top: 10px;">
	  				<input type="password" name="tzpass" id="tzpass" onblur="checkcpassword('tz_status','tzpass')" maxlength="255" class="fitSmall" placeholder="Current password">

	  				<span id="tz_status" class="signupStats" style="right: 10px; margin-top: -26px;"></span>

	  				<select id="tz_zone" onblur="checktz()" class="fitSmall">
	  					<option value="" selected="true" disabled="true">Change timezone</option>
	  					<?php require_once 'template_timezone_list.php'; ?>
	  				</select>

	  				<span id="tz_tstatus" class="signupStats" style="right: 10px; margin-top: -26px;"></span>
	  				<div class="clear"></div>
					<br>
	
					<button id="conftz" class="main_btn_fill pplongbtn settBtns" onclick="changeTz()">Confirm</button>
					<div id="fulltzstat" style="text-align: center; margin-top: 10px;"></div>
		  		</div>
		  	</div>

		  	<div class="collection" id="ccBd">
		      <p style="font-size: 18px;" id="signup">Birthday</p>
		      <img src="/images/alldd.png">
		    </div>

		    <div class="slideInfo" id="bdDD">
		  		<p>Since it is impossible to change your birthday - exept if you are a time traveller - we are still open for everyone, therefore we provided a birthday changing feature for time travellers and those people who gave a fake birthday when they signed up.</p>
		  		<div class="pplongbtn main_btn_fill widen" id="bdlongbtn">Change birthday</div>
		  		<div class="settingsall styleform" style="margin-top: 10px;">
	  				<input type="password" name="bdpass" id="bdpass" onblur="checkcpassword('bd_status','bdpass')" maxlength="255" placeholder="Current password">

	  				<span id="bd_status" class="signupStats" style="right: 10px; margin-top: -26px;"></span>

	  				<input type="date" id="bd_day" onblur="checkbd()" placeholder="1988-01-01">

	  				<span id="bd_bstatus" class="signupStats" style="right: 10px; margin-top: -26px;"></span>
	  				<br>
	  				
					<button id="confbd" class="main_btn_fill pplongbtn settBtns" onclick="changeBd()">Confirm</button>
					<div id="fullbdqm" style="text-align: center; margin-top: 10px;"></div>
		  		</div>
	  		</div>

	  		<div class="collection" id="ccDe">
		      <p style="font-size: 18px;" id="signup">Delete account</p>
		      <img src="/images/alldd.png">
		    </div>

		    <div class="slideInfo" id="deDD">
		  		<p>We do not recommend for anyone to delete their own account! All of your personal photos, videos, datas and everything else that your did on Pearscom will be gone! We will not be able no bring back your account, do it on your own risk and we will not take any resposibility for any mistakes!</p>
		  		<div class="pplongbtn main_btn_fill widen" id="dalongbtn">Delete account</div>
		  		<div class="settingsall styleform" style="margin-top: 10px;">
	  				<input type="password" name="dacpass" id="dacpass" onblur="checkcpassword('dacpass_status','dacpass')" maxlength="255" placeholder="Current password">

	  				<span id="dacpass_status" class="signupStats" style="right: 10px; margin-top: -26px;"></span>


					<textarea name="whyda" id="whyda" onblur="checkwhyda()" onkeyup="statusMax(this,1000)" placeholder="I was not satisfied with the services ... (optional)"></textarea>

					<span id="whydaqmstatus" class="signupStats" style="right: 10px; margin-top: -26px;"></span>
					<br>
					<button id="confda" class="main_btn_fill pplongbtn settBtns" onclick="deleteAcc()">Confirm</button>
					<div id="delete_status" style="text-align: center; margin-top: 10px;"></div>
		  		</div>
		  	</div>
	</div>
	<?php require_once 'template_pageBottom.php'; ?>
	<script type="text/javascript">
function setCookie(e,t,s){var i=new Date;i.setTime(i.getTime()+24*s*60*60*1e3);var o="expires="+i.toUTCString();document.cookie=e+"="+t+";"+o+";path=/"}function delete_cookie(e){document.cookie=e+"=; expires=Thu, 01 Jan 1970 00:00:01 GMT;"}function getCookie(e){for(var t=e+"=",s=decodeURIComponent(document.cookie).split(";"),i=0;i<s.length;i++){for(var o=s[i];" "==o.charAt(0);)o=o.substring(1);if(0==o.indexOf(t))return o.substring(t.length,o.length)}return""}function setDark(){var e="thisClassDoesNotExist";if(!document.getElementById(e)){var t=document.getElementsByTagName("head")[0],s=document.createElement("link");s.id=e,s.rel="stylesheet",s.type="text/css",s.href="/style/dark_style.css",s.media="all",t.appendChild(s)}}var dec=_("tdark"),isdarkm=getCookie("isdark");function toggleDark(){if(dec.checked){setCookie("isdark","yes",365);setDark()}else $('link[rel=stylesheet][href~="/style/dark_style.css"]').remove(),delete_cookie("isdark")}"yes"==isdarkm&&setDark(),"yes"==isdarkm&&(dec.checked=!0);

function doDD(first, second){
		    $( "#" + first ).click(function() {
		      $( "#" + second ).slideToggle( "fast", function() {
		        
		      });
		    });
		  }

		  doDD("ccSu", "suDD");
		  doDD("ccDa", "daDD");
		  doDD("ccUs", "usDD");
		  doDD("ccPw", "pwDD");
		  doDD("ccCy", "cyDD");
		  doDD("ccEm", "emDD");
		  doDD("ccGe", "geDD");
		  doDD("ccTz", "tzDD");
		  doDD("ccBd", "bdDD");
		  doDD("ccDe", "deDD");
	</script>
</body>
</html>