<?php
	// Check to see if the user is not logged in
	include_once("../php_includes/check_login_statues.php");
	require_once 'm_array.php';
	if($user_ok != true || $log_username == "") {
		exit();
	}
	$one = "1";
	// Ajax calls this code to execute
	if(isset($_POST["job"]) || isset($_POST["ta"]) || isset($_POST["pro"]) || isset($_POST["city"]) || isset($_POST["state"]) || isset($_POST["mobile"]) || isset($_POST["hometown"]) || isset($_POST["fmovie"]) || isset($_POST["fmusic"]) || isset($_POST["pstatus"]) || isset($_POST["elemen"]) || isset($_POST["high"]) || isset($_POST["uni"]) || isset($_POST["politics"]) || isset($_POST["religion"]) || isset($_POST["language"]) || isset($_POST["nd_day"]) && isset($_POST["nd_month"]) || isset($_POST["interest"]) || isset($_POST["notemail"]) || isset($_POST["website"]) || isset($_POST["address"]) || isset($_POST["degree"]) || isset($_POST["quotes"])){
		// Connect to the database
		require_once '../php_includes/conn.php';
		// Gather up the posted values
		$j = mysqli_real_escape_string($conn, $_POST['job']);
		$ta = mysqli_real_escape_string($conn, $_POST['ta']);
		$pro = mysqli_real_escape_string($conn, $_POST['pro']);
		$city = mysqli_real_escape_string($conn, $_POST['city']);
		$state = mysqli_real_escape_string($conn, $_POST['state']);
		$mobile = mysqli_real_escape_string($conn, $_POST['mobile']);
		$hometown = mysqli_real_escape_string($conn, $_POST['hometown']);
		$fmovie = mysqli_real_escape_string($conn, $_POST['fmovie']);
		$fmusic = mysqli_real_escape_string($conn, $_POST['fmusic']);
		$pstatus = mysqli_real_escape_string($conn, $_POST['pstatus']);

		$elemen = mysqli_real_escape_string($conn, $_POST['elemen']);
		$high = mysqli_real_escape_string($conn, $_POST['high']);
		$uni = mysqli_real_escape_string($conn, $_POST['uni']);
		$politics = mysqli_real_escape_string($conn, $_POST['politics']);
		$religion = mysqli_real_escape_string($conn, $_POST['religion']);
		$language = mysqli_real_escape_string($conn, $_POST['language']);
		$nd_day = mysqli_real_escape_string($conn, $_POST['nd_day']);
		$nd_month = mysqli_real_escape_string($conn, $_POST['nd_month']);
		$interest = mysqli_real_escape_string($conn, $_POST['interest']);
		$notemail = mysqli_real_escape_string($conn, $_POST['notemail']);
		$website = mysqli_real_escape_string($conn, $_POST['website']);
		$address = mysqli_real_escape_string($conn, $_POST['address']);
		$degree = mysqli_real_escape_string($conn, $_POST['degree']);
		$quotes = mysqli_real_escape_string($conn, $_POST['quotes']);

		// Check email for security purposes
		$email_user = "";
		$sql = "SELECT email FROM users WHERE username=? LIMIT 1";
		$stmt = $conn->prepare($sql);
		$stmt->bind_param("s",$log_username);
		$stmt->execute();
		$result = $stmt->get_result();
		while($row = $result->fetch_assoc()){
			$email_user = $row["email"];
		}
		// Error handling
		if($notemail == $email_user){
			echo "Please do not add your log in email address";
			exit();
		}

		// Check name day 
		if($nd_month != "" && $nd_day == ""){
			echo "Please add your full name day";
			exit();
		}else if($nd_day != "" && $nd_month == ""){
			echo "Please add your full name day";
			exit();
		}
		if($nd_day != "" && $nd_day != ""){
    	    if(!is_numeric($nd_day) || !in_array($nd_month, $months)){
    		    echo "Please give a valid name day";
    			exit();
    	    }
		}

		// Form data error handling
		if($j == "" && $ta == "" && $pro == "" && $city == "" && $state == "" && $mobile == "" && $hometown == "" && $fmovie == "" && $fmusic == "" && $pstatus == "" && $elemen == "" && $high == "" && $uni == "" && $politics == "" && $religion == "" && $language == "" && $nd_day == "" && $nd_month == "" && $interest == "" && $notemail == "" && $website == "" && $address == "" && $degree == "" && $quotes == ""){
			echo "Please fill in at least 1 field";
			exit();
		}else if(strlen($j) > 150 || strlen($ta) > 1000 || strlen($pro) > 150 || strlen($city) > 150 || strlen($state) > 150 || strlen($mobile) > 150 || strlen($hometown) > 150 || strlen($fmovie) > 400 || strlen($fmusic) > 400 || strlen($pstatus) > 150 || strlen($elemen) > 150 || strlen($high) > 150 || strlen($uni) > 150 || strlen($politics) > 150 || strlen($religion) > 150 || strlen($language) > 150 || strlen($interest) > 150 || strlen($notemail) > 150 || strlen($website) > 150 || strlen($address) > 150 || strlen($degree) > 150 || strlen($quotes) > 400){
		    echo "You reached the maximum character limit";
		    exit();
		}else if($nd_day != 1 && $nd_day != 2 && $nd_day != 3 && $nd_day != 4 && $nd_day != 5 && $nd_day != 6 && $nd_day != 7 && $nd_day != 8 && $nd_day != 9 && $nd_day != 10 && $nd_day != 11 && $nd_day != 12 && $nd_day != 13 && $nd_day != 14 && $nd_day != 15 && $nd_day != 16 && $nd_day != 17 && $nd_day != 18 && $nd_day != 19 && $nd_day != 20 && $nd_day != 21 && $nd_day != 22 && $nd_day != 23 && $nd_day != 24 && $nd_day != 25 && $nd_day != 26 && $nd_day != 27 && $nd_day != 28 && $nd_day != 29 && $nd_day != 30 && $nd_day != 31){
		    echo "Invalid name day";
		    exit();
		}else{
			// Insert into database
			$sql = "INSERT INTO edit(username, job, about, profession, city, state, mobile, hometown, fav_movie, fav_music, par_status, elemen, high, uni, politics, religion, nd_day, nd_month, interest, notemail, website, language, address, degree, quotes, changemade)
					VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,NOW())";
			$stmt = $conn->prepare($sql);
			$stmt->bind_param("ssssssssssssssssissssssss",$log_username,$j,$ta,$pro,$city,$state,$mobile,$hometown,$fmovie,$fmusic,$pstatus,$elemen,$high,$uni,$politics,$religion,$nd_day,$nd_month,$interest,$notemail,$website,$language,$address,$degree,$quotes);
			$stmt->execute();
			$stmt->close();
			// Update
			if($j != ""){
				$sql = "UPDATE edit SET job=? WHERE username=?";
				$stmt = $conn->prepare($sql);
				$stmt->bind_param("ss",$j,$log_username);
				$stmt->execute();
				$stmt->close();
			}
			if($ta != ""){
				$sql = "UPDATE edit SET about=? WHERE username=?";
				$stmt = $conn->prepare($sql);
				$stmt->bind_param("ss",$ta,$log_username);
				$stmt->execute();
				$stmt->close();
			}
			if($pro != ""){
				$sql = "UPDATE edit SET profession=? WHERE username=?";
				$stmt = $conn->prepare($sql);
				$stmt->bind_param("ss",$pro,$log_username);
				$stmt->execute();
				$stmt->close();
			}
			if($city != ""){
				$sql = "UPDATE edit SET city=? WHERE username=?";
				$stmt = $conn->prepare($sql);
				$stmt->bind_param("ss",$city,$log_username);
				$stmt->execute();
				$stmt->close();
			}
			if($state != ""){
				$sql = "UPDATE edit SET state=? WHERE username=?";
				$stmt = $conn->prepare($sql);
				$stmt->bind_param("ss",$state,$log_username);
				$stmt->execute();
				$stmt->close();
			}
			if($mobile != ""){
				$sql = "UPDATE edit SET mobile=? WHERE username=?";
				$stmt = $conn->prepare($sql);
				$stmt->bind_param("ss",$mobile,$log_username);
				$stmt->execute();
				$stmt->close();
			}
			if($hometown != ""){
				$sql = "UPDATE edit SET hometown=? WHERE username=?";
				$stmt = $conn->prepare($sql);
				$stmt->bind_param("ss",$hometown,$log_username);
				$stmt->execute();
				$stmt->close();
			}
			if($fmovie != ""){
				$sql = "UPDATE edit SET fav_movie=? WHERE username=?";
				$stmt = $conn->prepare($sql);
				$stmt->bind_param("ss",$fmovie,$log_username);
				$stmt->execute();
				$stmt->close();
			}
			if($fmusic != ""){
				$sql = "UPDATE edit SET fav_music=? WHERE username=?";
				$stmt = $conn->prepare($sql);
				$stmt->bind_param("ss",$fmusic,$log_username);
				$stmt->execute();
				$stmt->close();
			}
			if($pstatus != ""){
				$sql = "UPDATE edit SET par_status=? WHERE username=?";
				$stmt = $conn->prepare($sql);
				$stmt->bind_param("ss",$pstatus,$log_username);
				$stmt->execute();
				$stmt->close();
			}
			if($elemen != ""){
				$sql = "UPDATE edit SET elemen=? WHERE username=?";
				$stmt = $conn->prepare($sql);
				$stmt->bind_param("ss",$elemen,$log_username);
				$stmt->execute();
				$stmt->close();
			}
			if($high != ""){
				$sql = "UPDATE edit SET high=? WHERE username=?";
				$stmt = $conn->prepare($sql);
				$stmt->bind_param("ss",$high,$log_username);
				$stmt->execute();
				$stmt->close();
			}
			if($uni != ""){
				$sql = "UPDATE edit SET uni=? WHERE username=?";
				$stmt = $conn->prepare($sql);
				$stmt->bind_param("ss",$uni,$log_username);
				$stmt->execute();
				$stmt->close();
			}
			if($politics != ""){
				$sql = "UPDATE edit SET politics=? WHERE username=?";
				$stmt = $conn->prepare($sql);
				$stmt->bind_param("ss",$politics,$log_username);
				$stmt->execute();
				$stmt->close();
			}
			if($religion != ""){
				$sql = "UPDATE edit SET religion=? WHERE username=?";
				$stmt = $conn->prepare($sql);
				$stmt->bind_param("ss",$religion,$log_username);
				$stmt->execute();
				$stmt->close();
			}
			if($nd_day != "" && $nd_month != ""){
				$sql = "UPDATE edit SET nd_day=?, nd_month=? WHERE username=?";
				$stmt = $conn->prepare($sql);
				$stmt->bind_param("sss",$nd_day,$nd_month,$log_username);
				$stmt->execute();
				$stmt->close();
			}
			if($interest != ""){
				$sql = "UPDATE edit SET interest=? WHERE username=?";
				$stmt = $conn->prepare($sql);
				$stmt->bind_param("ss",$interest,$log_username);
				$stmt->execute();
				$stmt->close();
			}
			if($notemail != ""){
				$sql = "UPDATE edit SET notemail=? WHERE username=?";
				$stmt = $conn->prepare($sql);
				$stmt->bind_param("ss",$notemail,$log_username);
				$stmt->execute();
				$stmt->close();
			}
			if($website != ""){
				$sql = "UPDATE edit SET website=? WHERE username=?";
				$stmt = $conn->prepare($sql);
				$stmt->bind_param("ss",$website,$log_username);
				$stmt->execute();
				$stmt->close();
			}
			if($language != ""){
				$sql = "UPDATE edit SET language=? WHERE username=?";
				$stmt = $conn->prepare($sql);
				$stmt->bind_param("ss",$language,$log_username);
				$stmt->execute();
				$stmt->close();
			}
			if($address != ""){
				$sql = "UPDATE edit SET address=? WHERE username=?";
				$stmt = $conn->prepare($sql);
				$stmt->bind_param("ss",$address,$log_username);
				$stmt->execute();
				$stmt->close();
			}
			if($degree != ""){
				$sql = "UPDATE edit SET degree=? WHERE username=?";
				$stmt = $conn->prepare($sql);
				$stmt->bind_param("ss",$degree,$log_username);
				$stmt->execute();
				$stmt->close();
			}
			if($quotes != ""){
			    $quotes = '”'.$quotes.'”';
				$sql = "UPDATE edit SET quotes=? WHERE username=?";
				$stmt = $conn->prepare($sql);
				$stmt->bind_param("ss",$quotes,$log_username);
				$stmt->execute();
				$stmt->close();
			}

			// Notifications
			$friends = array();
			// Get friends array and insert into database
			$sql = "SELECT user1 FROM friends WHERE user2=? AND accepted=?";
			$stmt = $conn->prepare($sql);
			$stmt->bind_param("ss",$log_username,$one);
			$stmt->execute();
			$result = $stmt->get_result();
			while ($row = $result->fetch_assoc()) { 
				array_push($friends, $row["user1"]); 
			}
			$stmt->close();
			$sql = "SELECT user2 FROM friends WHERE user1=? AND accepted=?";
			$stmt = $conn->prepare($sql);
			$stmt->bind_param("ss",$log_username,$one);
			$stmt->execute();
			$result = $stmt->get_result();
			while ($row = $result->fetch_assoc()) { 
				array_push($friends, $row["user2"]); 
			}
			$stmt->close();
			for($i = 0; $i < count($friends); $i++){
				$friend = $friends[$i];
				$app = "Edited Profile Information <img src='/images/qm.png' class='notfimg'>";
				$note = $log_username.' edited his/her profile on: <br /><a href="/user/'.$log_username.'/">'.$log_username.'&#39;s Profile</a>';
				$sql = "INSERT INTO notifications(username, initiator, app, note, date_time) VALUES(?,?,?,?,NOW())";
				$stmt = $conn->prepare($sql);
				$stmt->bind_param("ssss",$friend,$log_username,$app,$note);
				$stmt->execute();
				$stmt->close();
			}
			mysqli_close($conn);
			echo "edit_success";
			exit();
		}
		exit();
	}
?>