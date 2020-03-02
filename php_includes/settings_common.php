<?php
	require_once 'c_array.php';

  function getUserInfo($conn, $log_username) {
    $one = '1';
    $sql = "SELECT email, password, country FROM users WHERE username=? AND activated=?
      LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $log_username, $one);
    $stmt->execute();
    $stmt->bind_result($email, $password, $country);
    $stmt->fetch();
    $stmt->close();
    return [$email, $password, $country];
  } 

  function genErrMsg($msg, $isSimple = false) {
    if (!$isSimple) {
      echo '
        <img src="/images/wrong.png" width="13" height="13">
        <span class="tooltiptext">' . $msg  . '</span>
      ';
    } else {
      echo $msg; 
    }
  }

  function validatePass($cp, $password, $isSimple, $isElse = true) {
    if(!password_verify($cp, $password)){
      genErrMsg('Current password field does not match', $isSimple);
			exit();
		}else if($isElse){
      echo "";
      exit();
    }
  }

  function atLeastChars($np) {
    $uc = preg_match('@[A-Z]@', $np);
    $lc = preg_match('@[a-z]@', $np);
    $nm = preg_match('@[0-9]@', $np);
    return [$uc, $lc, $nm];
  }

  function checkPass($uc, $lc, $nm, $np, $log_username, $email, $cp, $isSimple,
    $isElse = true) {
    if(!$uc || !$lc || !$nm){
      genErrMsg('Your password requires at least 1 lowercase, 1 uppercase letter and 1
        number', $isSimple);
			exit();
		}else if(strlen($np) < 6){
      genErrMsg('Password needs to be at least 6 characters long', $isSimple);
			exit();
		}else if($np == $log_username){
      genErrMsg('Cannot give username as password', $isSimple);
			exit();
		}else if($np == $email){
      genErrMsg('Cannot give email as password', $isSimple);
			exit();
		}else if($np == $cp){
      genErrMsg('This is your current password', $isSimple);
			exit();
		}else if($isElse){
      echo "";
      exit();
    }
  }

  function passFieldMatch($cnp, $np, $isSimple, $isElse = true) {
    if($cnp != $np){
      genErrMsg('New password field does not match', $isSimple);
			exit();
		}else if($isElse){
			echo "";
			exit();
		}
  }

  function checkCountry($ncountry, $country, $isSimple, $isElse = true) {
    if($ncountry == $country){
      genErrMsg('This is your current country', $isSimple);
			exit();
		}else if($isElse){
      echo "";
      exit();
    }
  }

  function emailRows($conn, $nemail) {
    $sql = "SELECT id FROM users WHERE email=? LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s",$nemail);
    $stmt->execute();
    $stmt->store_result();
    $stmt->fetch();
    $email_check = $stmt->num_rows;
    return $email_check;
  }

  function checkEmail($email_check, $nemail, $isSimple, $isElse = true) {
    if($email_check > 0){
      genErrMsg('Email address is taken', $isSimple);
			exit();
		}else if(!filter_var($nemail, FILTER_VALIDATE_EMAIL)){
      genErrMsg('Email is not valid', $isSimple);
			exit();
		}else if($isElse){
      echo "";
      exit();
    }
  }

  function charLimit($ta, $isSimple, $isElse = true) {
    if(strlen($ta) > 1000){
      genErrMsg('Maximum character limit reached', $isSimple);
			exit();
		}else if($isElse){
      echo "";
      exit();
    }
  }

  function currentGender($conn, $log_username, $email) {
    $sql = "SELECT gender FROM users WHERE username = ? AND email = ? LIMIT 1";
		$stmt = $conn->prepare($sql);
		$stmt->bind_param("ss", $log_username, $email);
		$stmt->execute();
		$stmt->bind_result($curg);
		$stmt->fetch();
		$stmt->close();
    return $curg;
  }

  function validateTz($tz, $isSimple, $isElse = true) {
    if($tz == ""){
      genErrMsg('Fill in all fields', $isSimple);
			exit();
		}else if(!in_array($tz, timezone_identifiers_list())){
      genErrMsg('Invalid timezone given', $isSimple);
			exit();
		}else if($isElse){
      echo "";
      exit();
    }
  }

	function validateDate($date, $format = 'Y-m-d') {
    $d = DateTime::createFromFormat($format, $date);
    return $d && $d->format($format) == $date;
  }

  function validateBd($bd, $isSimple, $isElse = true) {
    $check = validateDate($bd);
    if($bd == ""){
      genErrMsg('Fill in all fields', $isSimple);
			exit();
		}else if($bd > date("Y-m-d") || $bd < date("1900-01-01") || !$check){
      genErrMsg('Give a valid birthday', $isSimple);
			exit();
		}else if($isElse){
      echo "";
      exit();
    }
  }

  function takenUname($conn, $un) {
    $sql = "SELECT username FROM users WHERE username = ? LIMIT 1";
		$stmt = $conn->prepare($sql);
		$stmt->bind_param("s", $un);
		$stmt->execute();
		$stmt->bind_result($taken);
		$stmt->fetch();
		$stmt->close();
    return $taken;
  }

  function validateUname($un, $taken, $isSimple, $isElse = true) {
    if(strpos($un, '?') !== false || strpos($un, '#') !== false ||
      strpos($un, '&') !== false || strpos($un, '+') !== false ||
      strpos($un, '/') !== false || strpos($un, '\\') !== false){
      genErrMsg('The current username contains at least one of the forbidden characters
        (?; #; &; +; /; \)', $isSimple);
      exit();
    } else if($un == ""){
      genErrMsg('Fill in all fields', $isSimple);
			exit();
		}else if(strlen($un) < 6 || strlen($un) > 100){
      genErrMsg('Username must be between 6 and 100 characters', $isSimple);
			exit();
		}else if(is_numeric($un[0])){
      genErrMsg('Username must begin with a letter', $isSimple);
			exit();
		}else if($taken != "" && $taken != NULL){
      genErrMsg('This username is taken', $isSimple);
			exit();
		}else if($isElse){
      echo "";
      exit();
    }
  }

  function unlinkFiles($files) {
    foreach($files as $file){
      if(is_file($file)) {
        unlink($file); 
      }
    }
  }

  function remDir($path) {
    if(is_dir($path) && file_exists($path)){
      rmdir($path);
    }
  }

  function renameFiles($files, $addDir) {
    foreach($files as $file){
      if(is_file($file)){
        $file_ori = $file;
        $file = substr($file, $lena);
        $file = "$file";
        $wto = "user/{$un}/{$addDir}{$file}";
        rename($file_ori, $wto);
      }
    }
  }

  // The following functions are used on the signup page for validation and error checking
  function validateGender($gender_original, $isSimple, $isElse = true) {
    if ($gender_original == "") {
      genErrMsg('Please choose your gender!', $isSimple);
      exit();
    }else if($gender_original != "m" && $gender_original != "f"){
      genErrMsg('Please give a valid gender!', $isSimple);
      exit();
    }else if($isElse){
      echo "";
      exit();
    }
  }

  function validateCountry($country_original, $isSimple, $isElse = true) {
    global $countries;
    if ($country_original == "") {
      genErrMsg('Please choose your country!', $isSimple);
      exit();
    }else if(!in_array($country_original, $countries)){
      genErrMsg('Please give a valid country!', $isSimple);
      exit();
    }else if($isElse){
      echo "";
      exit();
    } 
  }
?>
