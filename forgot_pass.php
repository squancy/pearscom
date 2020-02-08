<?php
	include_once("php_includes/check_login_statues.php");
	require_once 'timeelapsedstring.php';
	require_once 'headers.php';

	// If user is logged in header them away 
	if($user_ok == true){
		header("Location: /user/".$_SESSION["username"]."/");
	    exit();
	}

	if(isset($_POST["e"])){
    function getUser($e, $conn, $one = '1') {
      $sql = "SELECT id, username FROM users WHERE email=? AND activated=? LIMIT 1";
      $stmt = $conn->prepare($sql);
      $stmt->bind_param("ss", $e, $one);
      $stmt->execute();
      $stmt->bind_result($id, $u);
      $stmt->fetch();
      $stmt->close();
      return [$id, $u];
    }

    function genTmpPass($e) {
      $emailcut = substr($e, 0, 5);
      $randNum = rand(10000, 99999);
      $tempPass = "$emailcut$randNum";
      $tempPass = ucfirst($tempPass);
      $tempPass = str_shuffle($tempPass);
      $hashTempPass = password_hash($tempPass, PASSWORD_DEFAULT);
      return [$tempPass, $hashTempPass];
    }

    function updateUserPwd($conn, $p, $u) {
      $sql = "UPDATE useroptions SET temp_pass=? WHERE username=? LIMIT 1";
      $stmt = $conn->prepare($sql);
      $stmt->bind_param("ss", $p, $u);
      $stmt->execute();
      $stmt->close();
    }

    $one = "1";
    $p_nohash = "";
    $id = "";
    $u = "";
    $e = mysqli_real_escape_string($conn, $_POST['e']);
    if($e == ""){
      echo "Email address is unvalid";
      exit();
    }
    
    // Get user from the db
    list($id, $u) = getUser($e, $conn);
    if ($id != "" && $u != "") {
      // Generate a random tmp password for user (both hashed and plain)
      list($tempPass, $p_nohash) = genTmpPass($e);
      
      // Update password for user in db
      updateUserPwd($conn, $p_nohash, $u);

      // A hash (/) might break the URL
      if(strpos($p_nohash,"/")){
          $p_nohash = str_replace("/","__slash__",$p_nohash);
      }
      
      $to = "$e";
      $from = "Pearscom <auto_responder@pearscom.com>";
      $headers ="From: $from\n";
      $headers .= "MIME-Version: 1.0\n";
      $headers .= "Content-type: text/html; charset=iso-8859-1 \n";
      $subject ="Pearscom Temporary Password";

      /*
        TODO: move inline style to CSS
      */

      $msg = '
        <!DOCTYPE html>
          <html>
            <head>
              <meta charset="UTF-8">
              <title>Pearscom - Temporary Password</title>
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
            <body style="font-family: Arial, sans-serif; background-color: #fafafa;
              box-sizing: border-box; margin: 0 auto; margin-top: 10px; max-width: 800px;">
              <div style="padding:10px; background-color: #282828; margin: 0 auto;
                border-radius: 20px 20px 0px 0px;">
                <a href="https://www.pearscom.com">
                  <img src="https://www.pearscom.com/images/newfav.png" width="49" height="49"
                    alt="pearscom.com" style="border:none; display: block; margin: 0 auto;">
                </a>
                &nbsp;
              </div>
              <div style="padding:24px; font-size:14px; border-left: 1px solid #e6e6e6;
                border-bottom: 1px solid #e6e6e6; border-right: 1px solid #e6e6e6;
                text-align: center;">
              <p style="font-size: 18px; color: #999; margin-top: 0px; text-align: left;">
                Hello '.$u.',
              </p>
              You got this email because you claimed a temporary password from Pearscom.
              If not, please ignore this message. 
              We generated a temporary password for you to log it with, then if you wish you can
              change the password to your custom one or anything you like.
              <br>
              <p>
                Your temporary password (please make sure you copy it in a safe place until you
                log in):
                <span style="color: #999;">'.$tempPass.'</span>
              </p>
              <a href="https://www.pearscom.com/forgot_pass.php?u='.$u.'&p='.$p_nohash.'"
                style="color: white;" id="link">
                <div style="background-color: red; color: white; padding: 5px; text-align: center;
                  width: 200px; border-radius: 10px; margin: 0 auto;" id="atp">
                    Apply temporary password
                </div>
              </a>
              <br>
              After you clicked on the link you will be redirected to the log in page if
              everything is fine.
              If it not the case there was an error.
              Please note that all of these changes will be applied only if you click on the
              link, otherwise nothing will happen!
              <br><br>
            </div>
            <div style="background: #282828; padding: 2px; border-radius: 0px 0px 20px 20px;
              color: #c1c1c1; font-size: 14px;">
              <p style="text-align: center;">
                For further information consider visiting our
                <a href="https://www.pearscom.com/help" style="color: red;">
                  help &amp; support
                </a>
                page
                <br><br>
                &copy; Pearscom <?php echo date("Y"); ?> <i>&#34;Connect us,
                  connect the world&#34;</i>
              </p>
            </div>
          </body>
        </html>
      ';

      if(mail($to, $subject, $msg, $headers)) {
        echo "success";
        exit();
      } else {
        echo "email_send_failed";
        exit();
      }
      
    } else {
      echo "no_exist";
      exit();
    }
  }

  // User is redirected here when clicked on link in email
	if(isset($_GET['u']) && isset($_GET['p'])){
		$u = mysqli_real_escape_string($conn, $_GET["u"]);
		$temppasshash = $_GET['p'];
		if(strlen($temppasshash) < 10){
			exit();
		}

		$p_nohash = $temppasshash;
		if(strpos($p_nohash, "__slash__")){
      $p_nohash = str_replace("__slash__", "/", $p_nohash);
    }

    function checkUser($conn, $u, $p_nohash) {
      $sql = "SELECT id FROM useroptions WHERE username=? AND temp_pass=? LIMIT 1";
      $stmt = $conn->prepare($sql);
      $stmt->bind_param("ss", $u, $p_nohash);
      $stmt->execute();
      $stmt->store_result();
      $stmt->fetch();
      $numrows = $stmt->num_rows;
	    $stmt->close();
      return $numrows;
    }

    function selectEmail($conn, $u) {
      $sql = "SELECT email FROM users WHERE username = ? LIMIT 1";
			$stmt = $conn->prepare($sql);
			$stmt->bind_param("s", $u);
			$stmt->execute();
			$stmt->bind_result($email);
			$stmt->fetch();
			$stmt->close();
      return $email;
    }

    function resetTmp($conn, $p_nohash, $email, $u) {
      $sql = "UPDATE users SET password=? WHERE email=? AND username=? LIMIT 1";
      $stmt = $conn->prepare($sql);
      $stmt->bind_param("sis", $p_nohash, $email, $u);
      $stmt->execute();
      $stmt->close();
      $n = NULL;

			$sql = "UPDATE useroptions SET temp_pass=? WHERE username=? LIMIT 1";
      $stmt = $conn->prepare($sql);
      $stmt->bind_param("ss", $n, $u);
      $stmt->execute();
      $stmt->close();
    }

    // Check if there is a user with this temp pass
    $numrows = checkUser($conn, $u, $p_nohash);

		if ($numrows == 0){
			header("location: /usernotexist");
	    exit();
		} else {
      $email = selectEmail($conn, $u);

			if($email == "" || $email == NULL){
			    exit();
			    header("location: /index");
			}

      resetTmp($conn, $p_nohash, $email, $u);
		  header("location: /login");
      exit();
    }
	}
?>
<!DOCTYPE html>
<html>
  <head>
    <title>Pearscom - Forgotten Password</title>
    <meta charset="utf-8">
    <meta name="description" content="Get a temporary password to your email account if you have
      forgotten your own one. After that, you can log in to your Pearscom account and change
      your password.">
    <link rel="icon" type="image/x-icon" href="/images/newfav.png">
    <link rel="stylesheet" type="text/css" href="/style/style.css">
    <script src="/js/main.js" async></script>
    <script src="/js/ajax.js" async></script>
    <link rel="manifest" href="/manifest.json">

    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="#282828">
    <meta name="apple-mobile-web-app-title" content="Pearscom">
    <link rel="apple-touch-icon" href="/images/icons/icon-152x152.png">
    <meta name="theme-color" content="#282828" />
    <script src="/js/specific/forgot.js"></script>
  </head>

  <body style="background-color: #fafafa;">
    <?php include_once("template_pageTop.php"); ?>
    <div id="pageMiddle_2" style="background-color: transparent;">
      <form id="loginform" onsubmit="return false;">
        <p style="font-size: 20px; margin-top: 0; color: #999;">
          Generate a temporary log in password
        </p>
        <p>
          Here you can get a temporary log in password  if you forgot your current one.
          Don&#39;t worry, if you wish you can change the temporary password to your custom
          one in the <span style="color: red;">settings</span> menu when you are logged in!
        </p>
        <div>
          <span style="color: red;">Step 1:</span>
          Add your email address so that we can send you a temporary password
        </div>
        <br>
        <input id="email" type="text" onfocus="_('status').innerHTML='';"
          placeholder="Enter email address">
        <br /><br />
        <button id="forgotpassbtn" class="main_btn" onclick="forgotpass()">
          Send me a password
        </button> 
        <div id="status"></div>
      </form>
    </div>
  <?php include_once("template_pageBottom.php"); ?>
  </body>
</html>
