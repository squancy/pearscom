<?php
	include_once("php_includes/check_login_statues.php");
	require_once 'timeelapsedstring.php';
	require_once 'headers.php';
	$one = "1";
	// If user is already logged in, header that weenis away
	if($user_ok == true){
		header("Location: /user/".$_SESSION["username"]."/");
	    exit();
	}
?><?php
	// AJAX CALLS THIS CODE TO EXECUTE
	if(isset($_POST["e"])){
	    $p_nohash = "";
	    $id = "";
		$u = "";
		$e = mysqli_real_escape_string($conn, $_POST['e']);
		if($e == ""){
		    echo "Email address is unvalid";
		    exit();
		}
		$sql = "SELECT * FROM users WHERE email=? AND activated=? LIMIT 1";
		$stmt = $conn->prepare($sql);
		$stmt->bind_param("ss",$e,$one);
		$stmt->execute();
		$result = $stmt->get_result();
		if($result->num_rows > 0){
			while($row = $result->fetch_assoc()){
				$id = $row["id"];
				$u = $row["username"];
			}
			$stmt->close();
			$emailcut = substr($e, 0, 5);
			$randNum = rand(10000,99999);
			$tempPass = "$emailcut$randNum";
			$tempPass = ucfirst($tempPass);
			$tempPass = str_shuffle($tempPass);
			$hashTempPass = password_hash($tempPass, PASSWORD_DEFAULT);
			$p_nohash = $hashTempPass;
			$sql = "UPDATE useroptions SET temp_pass=? WHERE username=? LIMIT 1";
		    $stmt = $conn->prepare($sql);
		    $stmt->bind_param("ss",$p_nohash,$u);
		    $stmt->execute();
		    $stmt->close();
		    if(strpos($p_nohash,"/")){
                $p_nohash = str_replace("/","__slash__",$p_nohash);
            }
            if(strpos($p_nohash,"$")){
                $p_nohash = str_replace("$","__dollar__",$p_nohash);
            }
            if(strpos($p_nohash,".")){
                $p_nohash = str_replace(".","__dot__",$p_nohash);
            }
            if(strpos($u,"/")){
                $u = str_replace("/","__slash__",$u);
            }
            if(strpos($u,"^")){
                $u = str_replace("^","__arru__",$u);
            }
            if(strpos($u,"£")){
                $u = str_replace("£","__font__",$u);
            }
            if(strpos($u,"%")){
                $u = str_replace("%","__pcent__",$u);
            }
            if(strpos($u,"&")){
                $u = str_replace("&","__and__",$u);
            }
            if(strpos($u,"}")){
                $u = str_replace("}","__rcb__",$u);
            }
            if(strpos($u,"{")){
                $u = str_replace("{","__lcb__",$u);
            }
            if(strpos($u,"@")){
                $u = str_replace("@","__at__",$u);
            }
            if(strpos($u,"#")){
                $u = str_replace("#","__htag__",$u);
            }
            if(strpos($u,"~")){
                $u = str_replace("~","__kerek__",$u);
            }
            if(strpos($u,"?")){
                $u = str_replace("?","__qm__",$u);
            }
            if(strpos($u,">")){
                $u = str_replace(">","__lkcs__",$u);
            }
            if(strpos($u,"<")){
                $u = str_replace("<","__rkcs__",$u);
            }
            if(strpos($u,"|")){
                $u = str_replace("|","__sline__",$u);
            }
            if(strpos($u,"=")){
                $u = str_replace("=","__equal__",$u);
            }
            if(strpos($u,"¬")){
                $u = str_replace("¬","__ssqign__",$u);
            }
            if(strpos($u,"/")){
                $u = str_replace("/","__slash__",$u);
            }
            //$u = mysqli_real_escape_string($conn,$u);
			$to = "$e";
			$from = "auto_responder@pearscom.com";
			$headers ="From: $from\n";
			$headers .= "MIME-Version: 1.0\n";
			$headers .= "Content-type: text/html; charset=iso-8859-1 \n";
			$subject ="Pearscom Temporary Password";
			$msg = '<!DOCTYPE html>
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
       <body style="font-family: Arial, sans-serif; background-color: #fafafa; box-sizing: border-box; margin: 0 auto; margin-top: 10px; max-width: 800px;">
              <div style="padding:10px; background-color: #282828; margin: 0 auto; border-radius: 20px 20px 0px 0px;">
                     <a href="https://www.pearscom.com"><img src="https://www.pearscom.com/images/newfav.png" width="49" height="49" alt="pearscom.com" style="border:none; display: block; margin: 0 auto;"></a>&nbsp;
              </div>
                     <div style="padding:24px; font-size:14px; border-left: 1px solid #e6e6e6; border-bottom: 1px solid #e6e6e6; border-right: 1px solid #e6e6e6; text-align: center;">
                            <p style="font-size: 18px; color: #999; margin-top: 0px; text-align: left;">Hello '.$u.',</p>You got this email because you claimed a temporary password from Pearscom. If not, please ignore this message. We generated a temporary password for you to log it with, then if you wish you can change the password to your custom one or anything you like.<br>
                            <p>Your temporary password (please make sure you copy it in a safe place until you log in): <span style="color: #999;">'.$tempPass.'</span></p>
                                   <a href="https://www.pearscom.com/forgot_pass.php?u='.$u.'&p='.$p_nohash.'" style="color: white;" id="link">
                                   <div style="background-color: red; color: white; padding: 5px; text-align: center; width: 200px; border-radius: 10px; margin: 0
                                   auto;" id="atp">Apply temporary password</div>
                                   </a>
                                   <br>After you clicked on the link you will be redirected to the log in page if everything is fine. If it not the case there was an error. Please note that all of these changes will be applied only if you click on the link, otherwise nothing will happen!<br><br>
              </div>
              <div style="background: #282828; padding: 2px; border-radius: 0px 0px 20px 20px; color: #c1c1c1; font-size: 14px;"><p style="text-align: center;">For further information consider visiting our <a href="https://www.pearscom.com/help" style="color: red;">help &amp; support</a> page<br><br>&copy; Pearscom <?php echo date("Y"); ?> <i>&#34;Connect us, connect the world&#34;</i></p></div>
       </body>
</html>       ';
			if(mail($to,$subject,$msg,$headers)) {
				echo "success";
				exit();
			} else {
				echo "email_send_failed";
				exit();
			}
	    } else {
	        echo "no_exist";
	    }
	    exit();
	}
?><?php
	// EMAIL LINK CLICK CALLS THIS CODE TO EXECUTE
	if(isset($_GET['u']) && isset($_GET['p'])){
		$u = mysqli_real_escape_string($conn, $_GET["u"]);
		$temppasshash = $_GET['p'];
		if(strlen($temppasshash) < 10){
			exit();
		}
		$p_nohash = $temppasshash;
		if(strpos($p_nohash,"__slash__")){
            $p_nohash = str_replace("__slash__","/",$p_nohash);
        }
        if(strpos($p_nohash,"__dollar__")){
            $p_nohash = str_replace("__dollar__","$",$p_nohash);
        }
        if(strpos($p_nohash,"__dot__")){
            $p_nohash = str_replace("__dot__",".",$p_nohash);
        }
        if(strpos($u,"__slash__")){
            $u = str_replace("__slash__","/",$u);
        }
        if(strpos($u,"__arru__")){
            $u = str_replace("__arru__","^",$u);
        }
        if(strpos($u,"__font__")){
            $u = str_replace("__font__","£",$u);
        }
        if(strpos($u,"__pcent__")){
            $u = str_replace("__pcent__","%",$u);
        }
        if(strpos($u,"__and__")){
            $u = str_replace("__and__","&",$u);
        }
        if(strpos($u,"__rcb__")){
            $u = str_replace("__rcb__","}",$u);
        }
        if(strpos($u,"__lcb__")){
            $u = str_replace("__lcb__","{",$u);
        }
        if(strpos($u,"__at__")){
            $u = str_replace("__at__","@",$u);
        }
        if(strpos($u,"__htag__")){
            $u = str_replace("__htag__","#",$u);
        }
        if(strpos($u,"__kerek__")){
            $u = str_replace("__kerek__","~",$u);
        }
        if(strpos($u,"__qm__")){
            $u = str_replace("__qm__","?",$u);
        }
        if(strpos($u,"__lkcs__")){
            $u = str_replace("__lkcs__",">",$u);
        }
        if(strpos($u,"__rkcs__")){
            $u = str_replace("__rkcs__","<",$u);
        }
        if(strpos($u,"__sline__")){
            $u = str_replace("__sline__","|",$u);
        }
        if(strpos($u,"__equal__")){
            $u = str_replace("__equal__","|",$u);
        }
        if(strpos($u,"__ssqign__")){
            $u = str_replace("__ssqign__","¬",$u);
        }
		$sql = "SELECT id FROM useroptions WHERE username=? AND temp_pass=? LIMIT 1";
		$stmt = $conn->prepare($sql);
		$stmt->bind_param("ss",$u,$p_nohash);
		$stmt->execute();
		$stmt->store_result();
		$stmt->fetch();
		$numrows = $stmt->num_rows;
		$stmt->close();
		if($numrows == 0){
			header("location: /usernotexist");
	    	exit();
		} else {
			$sql__ = "SELECT email FROM users WHERE username = ? LIMIT 1";
			$stmt = $conn->prepare($sql__);
			$stmt->bind_param("s",$u);
			$stmt->execute();
			$stmt->bind_result($email);
			$stmt->fetch();
			$stmt->close();
			if($email == "" || $email == NULL){
			    exit();
			    header("location: /index");
			}
			$sql = "UPDATE users SET password=? WHERE email=? AND username=? LIMIT 1";
		    $stmt = $conn->prepare($sql);
		    $stmt->bind_param("sis",$p_nohash,$email,$u);
		    $stmt->execute();
		    $stmt->close();
		    $n = "";
			$sql = "UPDATE useroptions SET temp_pass=? WHERE username=? LIMIT 1";
		    $stmt = $conn->prepare($sql);
		    $stmt->bind_param("ss",$n,$u);
		    $stmt->execute();
		    $stmt->close();
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
	<meta name="description" content="Get a temporary password to your email account if you have forgotten your own one. After that, you can log in to your Pearscom account and change your password.">
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
	<script type="text/javascript">
	function forgotpass() {
        var e = _("email").value;
            if ("" == e) _("status").innerHTML = "<p style='font-size: 14px; color: red;'>Type in your email address</p>";
            else {
                _("forgotpassbtn").style.display = "none", _("status").innerHTML = '<img src="/images/rolling.gif" width="30" height="30">';
                var s = ajaxObj("POST", "forgot_pass.php");
                s.onreadystatechange = function() {
                    if (1 == ajaxReturn(s)) {
                        var e = s.responseText;
                        _("forgotpassbtn").style.display = "block";
                        "success" == e ? _("loginform").innerHTML = "<p style='font-size: 14px; color: #129c12;'>Step 2. Check your email inbox in a few minutes</p><p style='font-size: 14px;'>You can close this window or tab if you like, every detail will be readable in the email ...</p>" : _("status").innerHTML = "no_exist" == e ? "<p style='font-size: 14px; color: red;'>Sorry that email address is not in our system</p>" : "email_send_failed" == e ? "<p style='font-size: 14px; color: red;'>Unfortunately we could not send your email</p>" : "<p style='font-size: 14px; color: red;'>"+e+"</p>"
                    }
                }, s.send("e=" + e)
            }
        }
	</script>
</head>
<body style="background-color: #fafafa;">
	<?php include_once("template_pageTop.php"); ?>
	<div id="pageMiddle_2" style="background-color: transparent;">
      <form id="loginform" onsubmit="return false;">
        <p style="font-size: 20px; margin-top: 0; color: #999;">Generate a temporary log in password</p>
      <p>Here you can get a temporary log in password  if you forgot your current one. Don&#39;t worry, if you wish you can change the temporary password to your custom one in the <span style="color: red;">settings</span> menu when you are logged in!</p>
        <div><span style="color: red;">Step 1:</span> Add your email address so that we can send you a temporary password</div>
        <br>
        <input id="email" type="text" onfocus="_('status').innerHTML='';" placeholder="Enter email address">
        <br /><br />
        <button id="forgotpassbtn" class="main_btn" onclick="forgotpass()">Send me a password</button> 
        <div id="status"></div>
      </form>
    </div>
<?php include_once("template_pageBottom.php"); ?>
</body>
</html>
