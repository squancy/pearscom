<?php
	require_once 'sec_session_start.php';
	require_once 'c_array.php';
	require_once 'headers.php';
	if(!isset($_SESSION))
    { 
        sec_session_start();
    } 
	// If user is logged in, header them away
	if(isset($_SESSION["username"])){
		header("location: /index");
	    exit();
	}
	function validateDate($date, $format = 'Y-m-d')
    {
        $d = DateTime::createFromFormat($format, $date);
        return $d && $d->format($format) == $date;
    }
?><?php
	// Ajax calls this NAME CHECK code to execute
	if(isset($_POST["usernamecheck"])){
		include_once("php_includes/conn.php");
		$backs = "'\'";
		$username = mysqli_real_escape_string($conn, $_POST["usernamecheck"]);
		$sql = "SELECT id FROM users WHERE username=? LIMIT 1";
	    $stmt = $conn->prepare($sql);
	    $stmt->bind_param("s",$username);
	    $stmt->execute();
	    $stmt->store_result();
	    $stmt->fetch();
	    $uname_check = $stmt->num_rows;
	    $stmt->close();
	    if(strpos($username, '?') !== false || strpos($username, '#') !== false || strpos($username, '&') !== false || strpos($username, '+') !== false || strpos($username, '/') !== false || strpos($username, '\\') !== false){
	        echo '<img src="/images/wrong.png" width="13" height="13"><span class="tooltiptext"The current username contains at least one of the forbidden characters (?; #; &; +; /; \)</span>';
		    exit();
	    } else if (strlen($username) < 3 || strlen($username) > 100) {
		    echo '<img src="/images/wrong.png" width="13" height="13"><span class="tooltiptext">Your username must be between 3 and 100 characters!</span>';
		    exit();
	    } else if (is_numeric($username[0])) {
		    echo '<img src="/images/wrong.png" width="13" height="13" title="Your username must begin with a letter!"><span class="tooltiptext"></span>';
		    exit();
	    } else if ($uname_check > 0) {
		    echo '<img src="/images/wrong.png" width="13" height="13"><span class="tooltiptext">This username is taken!</span>';
		    exit();
	    }else{
	    	echo "";
	    	exit();
	    }
	}
?>
<?php
	// Ajax calls this EMAIL CHECK code to execute
	if(isset($_POST["emailcheck"])){
		include_once("php_includes/conn.php");
		$email = $_POST['emailcheck'];
		$sql = "SELECT id FROM users WHERE email=? LIMIT 1";
	    $stmt = $conn->prepare($sql);
	    $stmt->bind_param("s",$email);
	    $stmt->execute();
	    $stmt->store_result();
	    $stmt->fetch();
	    $email_check = $stmt->num_rows;
	    if (!strpos($email, '@')) {
		    echo '<img src="/images/wrong.png" width="13" height="13"><span class="tooltiptext">Your email address is not valid!</span>';
		    exit();
		}else if($email_check > 0){
			echo '<img src="/images/wrong.png" width="13" height="13"><span class="tooltiptext">This email address is taken!</span>';
		    exit();
	    }else if(!filter_var($email, FILTER_VALIDATE_EMAIL)){
		    echo '<img src="/images/wrong.png" width="13" height="13"><span class="tooltiptext">Your email is not valid!</span>';
		    exit();
	    }else{
	    	echo "";
	    	exit();
	    }
	    $stmt->close();
	}
?>
<?php
	// Ajax calls this PASSWORD CHECK code to execute
	if(isset($_POST["passwordcheck"])){
		include_once("php_includes/conn.php");
		$password = $_POST['passwordcheck'];
	    if (!preg_match('/[a-z]/', $password)) {
		    echo '<img src="/images/wrong.png" width="13" height="13"><span class="tooltiptext">Your password must contain at least 1 lowercase letter!</span>';
		    exit();
	    } else if(!preg_match('/[A-Z]/', $password)){
		    echo '<img src="/images/wrong.png" width="13" height="13"><span class="tooltiptext">Your password must contain at least 1 uppercase letter!</span>';
		    exit();
	    }else if(!preg_match('/[0-9]/', $password)){
	    	echo '<img src="/images/wrong.png" width="13" height="13"><span class="tooltiptext">Your password must contain at least 1 number!</span>';
		    exit();
	    }else if(strlen($password) < 6){
	    	echo '<img src="/images/wrong.png" width="13" height="13"><span class="tooltiptext">Your password must be at least 6 characters long!</span>';
		    exit();
	    }else{
	    	echo "";
	    	exit();
	    }
	}
?>
<?php
	// Ajax calls this CONFRIM PASSWORD code to execute
	if(isset($_POST["confrimcheck"]) && isset($_POST["password_original"])){
		include_once("php_includes/conn.php");
		$password2 = $_POST['confrimcheck'];
		$password_original = $_POST['password_original'];
	    if ($password2 != $password_original) {
		    echo '<img src="/images/wrong.png" width="13" height="13"><span class="tooltiptext">Your password fields do not match!</span>';
		    exit();
	    }else{
	    	echo "";
	    	exit();
	    }
	}
?>
<?php
	// Ajax calls this CHECK GENDER code to execute
	if(isset($_POST["gendercheck"])){
		include_once("php_includes/conn.php");
		$gender_original = $_POST['gendercheck'];
	    if ($gender_original == "") {
		    echo '<img src="/images/wrong.png" width="13" height="13"><span class="tooltiptext">Please choose your gender!</span>';
		    exit();
	    }else if($gender_original != "m" && $gender_original != "f"){
	        echo '<img src="/images/wrong.png" width="13" height="13"><span class="tooltiptext">Please give a valid gender!</span>';
		    exit();
	    }else{
	    	echo "";
	    	exit();
	    }
	}
?>
<?php
	// Ajax calls this CHECK COUNTRY code to execute
	if(isset($_POST["countrycheck"])){
		include_once("php_includes/conn.php");
		$country_original = $_POST['countrycheck'];
	    if ($country_original == "") {
		    echo '<img src="/images/wrong.png" width="13" height="13"><span class="tooltiptext">Please choose your country!</span>';
		    exit();
	    }else if(!in_array($country_original, $countries)){
	        echo '<img src="/images/wrong.png" width="13" height="13"><span class="tooltiptext">Please give a valid country!</span>';
		    exit();
	    }else{
	    	echo "";
	    	exit();
	    }
	}
?>
<?php
	// Ajax calls this CHECK BIRTHDAY code to execute
	if(isset($_POST["checkbd"])){
		include_once("php_includes/conn.php");
		$bd = $_POST['checkbd'];
		$bd = mysqli_real_escape_string($conn, $bd);
		$check = validateDate($bd);
	    if ($bd > date("Y-m-d") || $bd < 1899) {
		    echo '<img src="/images/wrong.png" width="13" height="13"><span class="tooltiptext">Please give a valid birthday!</span>';
		    exit();
	    }else if($check == false){
	        echo '<img src="/images/wrong.png" width="13" height="13"><span class="tooltiptext">Please give a valid birthday!</span>';
	        echo $check;
		    exit();
	    }else{
	    	echo "";
	    	exit();
	    }
	}
?>
<?php
	// Ajax calls this CHECK TIMEZONE code to execute
	if(isset($_POST["tzcheck"])){
		include_once("php_includes/conn.php");
		$tz = $_POST['tzcheck'];
		$tz = mysqli_real_escape_string($conn, $tz);
	    if ($tz == "") {
		    echo '<img src="/images/wrong.png" width="13" height="13"><span class="tooltiptext">Please give your current timezone!</span>';
		    exit();
	    }else if(!in_array($tz, timezone_identifiers_list())){
	        echo '<img src="/images/wrong.png" width="13" height="13"><span class="tooltiptext">Please give a valid timezone!</span>';
		    exit();
	    }else{
	    	echo "";
	    	exit();
	    }
	}
?>
<?php
	// Ajax calls this REGISTRATION code to execute
	if(isset($_POST["u"])){
		// CONNECT TO THE DATABASE
		include_once("php_includes/conn.php");
		// GATHER THE POSTED DATA INTO LOCAL VARIABLES
		$u = mysqli_real_escape_string($conn, $_POST['u']);
		$e = mysqli_real_escape_string($conn, $_POST['e']);
		$p = $_POST['p'];
		$g = preg_replace('#[^a-z]#', '', $_POST['g']);
		$c = mysqli_real_escape_string($conn, $_POST["c"]);
		$uc = preg_match('@[A-Z]@', $p);
		$lc = preg_match('@[a-z]@', $p);
		$nm = preg_match('@[0-9]@', $p);
		// GET USER IP ADDRESS
	    $ip = preg_replace('#[^0-9.]#', '', getenv('REMOTE_ADDR'));
	    // BIRTHDAY POSTS
	    $bd = preg_replace('#[^0-9.-]#', '', $_POST['bd']);
	    $check = validateDate($bd);
	    // LONGITUDE AND LATITUDE FOR GEOLOCATION
	    $lat = preg_replace('#[^0-9.,]#', '', $_POST["lat"]);
	    $lon = preg_replace('#[^0-9.,]#', '', $_POST["lon"]);
	    // TIMEZONE FOR ELAPSED TIME
	    $tz = mysqli_real_escape_string($conn, $_POST["tz"]);
		// DUPLICATE DATA CHECKS FOR USERNAME AND EMAIL
		$sql = "SELECT id FROM users WHERE username=? LIMIT 1";
		$stmt = $conn->prepare($sql);
		$stmt->bind_param("s",$u);
	    $stmt->execute();
	    $stmt->store_result();
	    $stmt->fetch();
	    $u_check = $stmt->num_rows;
	    $stmt->close();
		// -------------------------------------------
		$sql = "SELECT id FROM users WHERE email=? LIMIT 1";
	    $stmt = $conn->prepare($sql);
	    $stmt->bind_param("s",$e);
	    $stmt->execute();
	    $stmt->store_result();
	    $stmt->fetch();
	    $e_check = $stmt->num_rows; 
	    $stmt->close();
		// FORM DATA ERROR HANDLING
		if(strpos($u, '?') !== false || strpos($u, '#') !== false || strpos($u, '&') !== false || strpos($u, '+') !== false || strpos($u, '/') !== false || strpos($u, '\\') !== false){
		    echo "Current username contains at least one of the forbidden special characters</i>";
	        exit();
		} else if($u == "" || $e == "" || $p == "" || $g == "" || $c == "" || $bd == "" || $lat == "not located yet" || $lat == "" || $lon == "not located yet" || $lon == "" || $tz == ""){
			echo "The form submission is missing values</i>";
	        exit();
		} else if ($u_check > 0){ 
	        echo "The username you entered is alreay taken</i>";
	        exit();
		} else if ($e_check > 0){ 
	        echo "Your current email address is already in use in the system</i>";
	        exit();
		} else if (strlen($u) < 3 || strlen($u) > 100) {
	        echo "Username must be between 3 and 100 characters</i>";
	        exit(); 
	    } else if (is_numeric($u[0])) {
	        echo 'Username cannot begin with a number';
	        exit();
	    } else if (!$lc || !$uc || !$nm){
	    	echo 'Password requires at least 1 uppercase, 1 lowercase and 1 number';
	    	exit();
	    } else if ($bd > date("Y-m-d") || $bd < 1906){
	    	echo 'Invaild birthday added';
	    	exit();
	    } else if (strlen($p) < 6){
	    	echo 'Password must be at least 6 characters long';
	    	exit();
	    } else if ($tz == "" || !in_array($tz, timezone_identifiers_list())){
	        echo "Invalid timezone given";
	        exit();
	    } else if (!in_array($c, $countries)){
	        echo "Invalid country given";
	        exit();
	    } else if ($g != "m" && $g != "f"){
	        echo "Invalid gender given";
	        exit();
	    } else if (!filter_var($e, FILTER_VALIDATE_EMAIL)){
	        echo "Invalid email address given";
	        exit();
	    } else if($check == false){
	        echo "Invalid birthday given";
	        exit();
	    } else {
		// END FORM DATA ERROR HANDLING
		    // Begin Insertion of data into the database
			// Hash the password
			$p_hash = password_hash($p, PASSWORD_DEFAULT);
			// Add user info into the database table for the main site table
			$sql = "INSERT INTO users (username, email, password, gender, country, ip, signup, lastlogin, notescheck, bday, lat, lon, tz)       
			        VALUES(?,?,?,?,?,?,NOW(),NOW(),NOW(),?,?,?,?)";
			$stmt = $conn->prepare($sql);
			$stmt->bind_param("ssssssssss",$u,$e,$p_hash,$g,$c,$ip,$bd,$lat,$lon,$tz);
			$stmt->execute();
			$stmt->close();
			$uid = mysqli_insert_id($conn);
			// Establish their row in the useroptions table
			$sql = "INSERT INTO useroptions (id, username) VALUES (?,?)";
			$stmt = $conn->prepare($sql);
			$stmt->bind_param("ss",$uid,$u);
			$stmt->execute();
			$stmt->close();
			// Create directory(folder) to hold each user's files(pics, MP3s, etc.)
			if (!file_exists("user/$u")) {
				mkdir("user/$u", 0755);
			}
			$p_nohash = $p_hash;
            if(strpos($p_nohash,"/")){
                $p_nohash = str_replace("/","__slash__",$p_nohash);
            }
            if(strpos($p_nohash,"$")){
                $p_nohash = str_replace("$","__dollar__",$p_nohash);
            }
            if(strpos($p_nohash,".")){
                $p_nohash = str_replace(".","__dot__",$p_nohash);
            }
			$app = "Welcome to Pearscom";
			$note = 'Most social media sites have a great welcome message, but we are not most ... Anyway, we hope that you will spend a wonderful time with your friends and join to this amazing community.';
			$sql = "INSERT INTO notifications(username, initiator, app, note, date_time) VALUES(?,?,?,?,NOW())";
			$stmt = $conn->prepare($sql);
			$stmt->bind_param("ssss",$u,$u,$app,$note);
			$stmt->execute();
			$stmt->close();
		    /*
			$avatar = "images/avdef.png";
			$avatar2 = "user/$u/avdef.png";
			if(!copy($avatar, $avatar2)){
				echo "failed to create avatar";
			}*/
			// Email the user their activation link
			$to = "$e";
			$from = "Pearscom <auto_responder@pearscom.com>";
			$subject = 'Pearscom Account Activation';
			$message = '<!DOCTYPE html>
<html>
       <head>
              <meta charset="UTF-8">
              <title>Pearscom Account Activation</title>
       </head>
       <style type="text/css">
              div > a:hover, a{
                     text-decoration: none;
              }

              #link:hover{
                     background-color: #ab0000;
              }

              @media only screen and (max-width: 768px){
                     #atp{
                            width: 100% !important;
                     }
              }
       </style>
       <body style="font-family: Arial, sans-serif; background-color: #fafafa; box-sizing: border-box; margin: 0 auto; margin-top: 10px; max-width: 800px;">
              <div style="padding:10px; background-color: #282828; margin: 0 auto; border-radius: 20px 20px 0px 0px;">
                     <a href="https://www.pearscom.com"><img src="https://www.pearscom.com/images/newfav.png" width="49" height="49" alt="pearscom.com" style="border:none; display: block; margin: 0 auto;"></a>&nbsp;
              </div>
                     <div style="padding:24px; font-size:14px; border-left: 1px solid #e6e6e6; border-bottom: 1px solid #e6e6e6; border-right: 1px solid #e6e6e6; text-align: center;">
                            <p style="font-size: 18px; color: #999; margin-top: 0px; text-align: left;">Welcome to Pearscom '.$u.',</p>We are glad that your signing up to Pearscom was successful and now you just need to activate your account in order to log in. After the account activation you will be able to log in and use your account immediately.<br>
                            <p>When logging in you will need your password and email given during the sign up part and if you forget it anytime feel free to visit the <a href="https://www.pearscom.com/help" style="color: red;">help &amp; support</a> page.</p>
                                   <a href="https://www.pearscom.com/activation.php?id='.$uid.'&u='.urlencode($u).'&p='.$p_nohash.'&e='.$e.'" style="color: white;" id="link">
                                   <div style="background-color: red; color: white; padding: 5px; text-align: center; width: 200px; border-radius: 10px; margin: 0
                                   auto;" id="atp">Activate Account</div>
                                   </a>
                                   <br>Again, thank you for signing up to Pearscom and hope you will enjoy being part of an amazing community!<br><br>
              </div>
              <div style="background: #282828; padding: 2px; border-radius: 0px 0px 20px 20px; color: #c1c1c1; font-size: 14px;"><p style="text-align: center;">For further information consider visiting our <a href="https://www.pearscom.com/help" style="color: red;">help &amp; support</a> page<br><br>&copy; Pearscom <?php echo date("Y"); ?> <i>&#34;Connect us, connect the world&#34;</i></p></div>
       </body>
</html>       ';
			$headers = "From: $from\n";
	        $headers .= "MIME-Version: 1.0\n";
	        $headers .= "Content-type: text/html; charset=iso-8859-1\n";
	        
			mail($to, $subject, $message, $headers);
			echo "signup_success";
			exit();
		}
		exit();
	}
?>
<!DOCTYPE html>
<html>
<head>
	<title>Sign Up to Pearscom</title>
	<meta charset="utf-8">
	<meta lang="en">
	<meta name="description" content="Join to Pearscom now and be part of an amazing community!">
    <meta name="keywords" content="pearscom sign up, signup, pearscom signup, register, pearscom register, create account pearscom">
    <script src="/js/jjs.js"></script>
    <meta name="author" content="Pearscom">
	<link rel="icon" type="image/x-icon" href="/images/newfav.png">
	<link rel="stylesheet" type="text/css" href="/style/style.css">
	<script src="/js/main.js"></script>
	<script src="/js/ajax.js" async></script>
		  <link rel="manifest" href="/manifest.json">
  <meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="#282828">
<meta name="apple-mobile-web-app-title" content="Pearscom">
<link rel="apple-touch-icon" href="/images/icons/icon-152x152.png">
<meta name="theme-color" content="#282828" />
	<script type="text/javascript">
		function checkusername() {
    var e = _("username").value;
    if ("" != e) {
        var n = ajaxObj("POST", "signup.php");
        n.onreadystatechange = function() {
            1 == ajaxReturn(n) && (_("unamestatus").innerHTML = n.responseText)
        }, n.send("usernamecheck=" + e)
    }
}

function checkemail() {
    var e = _("email").value;
    if ("" != e) {
        var n = ajaxObj("POST", "signup.php");
        n.onreadystatechange = function() {
            1 == ajaxReturn(n) && (_("emailstatus").innerHTML = n.responseText)
        }, n.send("emailcheck=" + e)
    }
}

function checkpassword() {
    var e = _("pass1").value;
    if ("" != e) {
        var n = ajaxObj("POST", "signup.php");
        n.onreadystatechange = function() {
            1 == ajaxReturn(n) && (_("passwordstatus").innerHTML = n.responseText)
        }, n.send("passwordcheck=" + e)
    }
}

function confirmpassword() {
    var e = _("pass2").value,
        n = _("pass1").value;
    if ("" != e || "" != n) {
        var t = ajaxObj("POST", "signup.php");
        t.onreadystatechange = function() {
            1 == ajaxReturn(t) && (_("confrimstatus").innerHTML = t.responseText)
        }, t.send("confrimcheck=" + e + "&password_original=" + n)
    }
}

function checkgender() {
    var e = _("gender").value;
    if ("" != e) {
        var n = ajaxObj("POST", "signup.php");
        n.onreadystatechange = function() {
            1 == ajaxReturn(n) && (_("genderstatus").innerHTML = n.responseText)
        }, n.send("gendercheck=" + e)
    }
}

function checkcountry() {
    var e = _("country").value;
    if ("" != e) {
        var n = ajaxObj("POST", "signup.php");
        n.onreadystatechange = function() {
            1 == ajaxReturn(n) && (_("countrystatus").innerHTML = n.responseText)
        }, n.send("countrycheck=" + e)
    }
}

function checktimezone() {
    var e = _("timezone").value;
    if ("" != e) {
        var n = ajaxObj("POST", "signup.php");
        n.onreadystatechange = function() {
            1 == ajaxReturn(n) && (_("timezstatus").innerHTML = n.responseText)
        }, n.send("tzcheck=" + e)
    }
}

function checkbd() {
    var e = _("birthday").value;
    if ("" != e) {
        var n = ajaxObj("POST", "signup.php");
        n.onreadystatechange = function() {
            1 == ajaxReturn(n) && (_("bdstatus").innerHTML = n.responseText)
        }, n.send("checkbd=" + e)
    }
}

function getLocation() {
    var e = _("coords");
    _("mapholder").innerHTML = '<img src="/images/rolling.gif" width="30" height="30">', _("vupload").style.display = "none", navigator.geolocation ? navigator.geolocation.getCurrentPosition(showPosition, showError) : (e.innerHTML = "<p>Geolocation is not supported by this browser</p>", _("mapholder").innerHTML = "", _("vupload").style.display = "block")
}

function showPosition(e) {
    var n = e.coords.latitude,
        t = e.coords.longitude,
        o = n + "," + t;
    _("lat").innerHTML = n, _("lon").innerHTML = t;
    var a = "https://maps.googleapis.com/maps/api/staticmap?center=" + o + "&zoom=14&size=400x300&key=AIzaSyCr5_w0vZzk39VbnJ8GWZcoZycl_gvr5w8";
    _("mapholder").innerHTML = "<img src='" + a + "' id='googmh'>"
}

function showError(e) {
    var n = _("geo_err");
    switch (e.code) {
        case e.PERMISSION_DENIED:
            n.innerHTML = "<p>User denied the request for Geolocation</p>";
            break;
        case e.POSITION_UNAVAILABLE:
            n.innerHTML = "<p>Location information is unavailable</p>";
            break;
        case e.TIMEOUT:
            n.innerHTML = "<p>The request to get user location timed ou</p>.";
            break;
        case e.UNKNOWN_ERROR:
            n.innerHTML = "<p>An unknown error occurred</p>"
    }
}

function signup() {
    var e = _("username").value;
    e = encodeURI(e);
    var n = _("email").value,
        t = _("pass1").value,
        o = _("pass2").value,
        a = _("country").value,
        i = _("gender").value,
        s = _("status"),
        r = _("birthday").value,
        c = _("lat").innerHTML,
        u = _("lon").innerHTML,
        d = _("timezone").value,
        p = t.match(/[a-z]/) ? 1 : 0,
        l = t.match(/[A-Z]/) ? 1 : 0,
        h = t.match(/[0-9]/) ? 1 : 0;
    if ("" == e || "" == n || "" == t || "" == o || "" == a || "" == i || "" == r || "not located yet" == c || "not located yet" == u || "" == c || "" == u || "" == d) s.innerHTML = "<p>Fill out all of the form data and accept geolocation</p>";
    else if (t != o) s.innerHTML = "<p>Your password fields do not match</p>";
    else if (p && l && h) {
        _("signupbtn").style.display = "none", s.innerHTML = '<img src="/images/rolling.gif" width="30" height="30">';
        var m = ajaxObj("POST", "signup.php");
        m.onreadystatechange = function() {
            1 == ajaxReturn(m) && ("signup_success" != m.responseText ? (s.innerHTML = m.responseText, _("signupbtn").style.display = "block") : (window.scrollTo(0, 0), _("loginform").innerHTML = "<p><b>Good job, " + decodeURIComponent(e) + "!</b><br><br>You have done the signing up part, however you have to activate your account in order to be able to log in. We have sent an account activation email to <span style='color: red;'>" + n + "</span> (please note that this might take some time).</p>"))
        }, m.send("u=" + e + "&e=" + n + "&p=" + t + "&c=" + a + "&g=" + i + "&bd=" + r + "&lat=" + c + "&lon=" + u + "&tz=" + d)
    } else s.innerHTML = "<p>Password requires at least 1 uppercase, 1 lowercase and 1 number</p>"
}

function showHiddenDiv() {
    var e = _("hideDiv");
    "none" == e.style.display ? e.style.display = "block" : e.style.display = "none"
}

function togglePassword() {
    var e = _("pass1"),
        n = _("pass2"),
        t = _("eye"),
        o = _("eyeoff");
    "password" == e.type ? (e.type = "text", n.type = "text", t.style.display = "none", o.style.display = "block") : (e.type = "password", n.type = "password", t.style.display = "block", o.style.display = "none")
}
	</script>
</head>
<body style="background-color: #fafafa;">
	<?php require_once 'template_pageTop.php'; ?>
	<div id="pageMiddle_2" style="background: transparent;">
    <form name="signupform" id="loginform" class="mwAlign" onsubmit="return false;">
      <p style="font-size: 28px; text-align: left;">Sign up</p>
      <input id="username" type="text" onblur="checkusername()" maxlength="100" placeholder="Username or real name">

      <span class="signupStats" id="unamestatus"></span>

      <input id="email" type="text" onblur="checkemail()" placeholder="Email">

      <span class="signupStats" id="emailstatus"></span>

      <input id="pass1" type="password" onblur="checkpassword()" autocomplete="true" placeholder="Password">

      <span class="signupStats" id="passwordstatus"></span>

      <input id="pass2" type="password" onblur="confirmpassword()" autocomplete="true" placeholder="Confirm password">
      <span class="signupStats" id="confrimstatus"></span>

      <select id="gender" onblur="checkgender()">
        <option disabled="true" value="" selected="true">Choose gender</option>
        <option value="m">Male</option>
        <option value="f">Female</option>
      </select>

      <span class="signupStats" id="genderstatus"></span>

      <select id="country" onblur="checkcountry()">
        <option disabled="true" value="" selected="true">Choose country</option>
        <?php require_once 'template_country_list.php'; ?>
      </select>

      <span class="signupStats" id="countrystatus"></span>
      <div class="clear"></div>
      <input type="date" id="birthday" min="1899-01-01" max="<?php echo date("Y-m-d"); ?>" required aria-required="true" data-placeholder="Date of birth" style="width: 45%;" onblur="checkbd()"><span class="signupStats" id="bdstatus"></span>

      <select id="timezone" onblur="checktimezone()">
          <option disabled="true" value="" selected="true">Choose timezone</option>
          <?php require_once 'template_timezone_list.php'; ?>
      </select>

      <span id="timezstatus"></span>
      <div class="clear"></div>
      <div id="acc_geo">Geolocation</b></div>
      <button id="signupbtn" onclick="signup()">Sign Up</button>
      <div class="clear"></div>

      <div id="wrapping" style="display: none;">
        <p style="margin-top: 0px;">While using Pearscom, we may look up for your geolocation, process, and use it for demographic and security purposes. We all do this for the benefit of our users, in order serve local, valid and trusted content, to count the distance between different locations and for several other features. We may store it in our system & database, use it in our algorithms and make analysis with the help of your geolocation. Please keep in mind that we do not abuse with your personal geolocation datas, nor do we publish it but we keep it in private.</p>
          <button onclick="getLocation()" id="vupload">Agree</button>
        </div>
        <div style="display: none;">
          <span style="font-size: 12px;">Latitude: </span><span id="lat" style="font-size: 12px;">not located yet</span><br />
          <span style="font-size: 12px;">Longitude: </span><span id="lon" style="font-size: 12px;">not located yet</span>
        </div>
        <div id="mapholder"></div>
        <div id="geo_err"></div>
        <span id="status"></span>
        <p>By signing up you agree our <a href="/policies" class="rlink">Privacy and Policy</a>, how we collect and use your data and accept the use of <a href="policies" class="rlink">cookies</a> on the site.</p>
        <p>Already have an account? <a href="/login" class="rlink">Log In</a></p>
      </form>
  </div>
	<?php include_once("template_pageBottom.php"); ?>
	<script type="text/javascript">
	    function getCookie(e){for(var t=e+"=",s=decodeURIComponent(document.cookie).split(";"),n=0;n<s.length;n++){for(var r=s[n];" "==r.charAt(0);)r=r.substring(1);if(0==r.indexOf(t))return r.substring(t.length,r.length)}return""}function setDark(){var e="thisClassDoesNotExist";if(!document.getElementById(e)){var t=document.getElementsByTagName("head")[0],s=document.createElement("link");s.id=e,s.rel="stylesheet",s.type="text/css",s.href="/style/dark_style.css",s.media="all",t.appendChild(s)}}var isdarkm=getCookie("isdark");"yes"==isdarkm&&setDark();

	    document.getElementById("acc_geo").addEventListener("click", function showmap(){
      getLocation();
    });
	</script>
</body>
</html>
