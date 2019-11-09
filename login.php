<?php
	include_once("php_includes/check_login_statues.php");
	require_once 'headers.php';
	// If user is already logged in, header that weenis away
	if($user_ok == true){
		header("location: /user/".$_SESSION["username"]."/");
	    exit();
	}
	?><?php
		// Change ajax post url
	    // Add time javascript
	    // Add token
	    $salt = "ndghtukynasdlk5485188770157";
	    $timestamp = time();
	    $tk = str_shuffle(md5(uniqid().md5($salt)));
	    $tk = preg_replace('#[^a-z0-9.-]#i', '', $tk);
	    $ses_array = array("tm" => $timestamp, "tk" => $tk);
	    if(!isset($_SESSION['login'])){
	    	$_SESSION['login'] = $ses_array;
	    }else{
	    	unset($_SESSION['login']);
	    	$_SESSION['login'] = $ses_array;
	    }
	?>
<!DOCTYPE html>
<html>
<head>
	<title>Pearscom - Log In</title>
	<meta charset="utf-8">
	<meta lang="en">
	<link rel="icon" type="image/x-icon" href="/images/newfav.png">
	<link rel="stylesheet" type="text/css" href="/style/style.css">
	<meta name="description" content="Log in to your Pearscom account and be part be of an amazing community. If you do not have an accont please sign up.">
	<script src="/js/main.js" async></script>
	<script src="/js/ajax.js" async></script>
		  <link rel="manifest" href="/manifest.json">
  <meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="#282828">
<meta name="apple-mobile-web-app-title" content="Pearscom">
<link rel="apple-touch-icon" href="/images/icons/icon-152x152.png">
<meta name="theme-color" content="#282828" />
	 <meta name="description" content="Log in to your Pearscom account and start wrtiting articles, share photos and videos with your friends!">
	 <meta name="author" content="Pearscom">
	 <meta name="keywords" content="Pearscom log in, pearscom log in, pearscom login, log in to pearscom, log in pearscom, log pearscom, logged, logged in, account see, see account">
	 <script type="application/ld+json"> { "@context" : "http://schema.org", "@type" : "Article", "name" : "Pearscom log in", "author" : { "@type" : "Person", "name" : "Pearscom, Mark Frankli" }, "image" : "https://www.pearscom.com/images/newfav.png", "articleSection" : "Log in to Pearscom", "articleBody" : "You can log in to your Pearscom account or if you do not have one sign up!", "url" : "http://www.pearscom.com/login", "publisher" : { "@type" : "Organization", "name" : "Pearscom" } } </script>
	<script type="text/javascript">
		var startTime = (new Date).valueOf();
        
        function emptyElement(e) {
            _(e).innerHTML = ""
        }
        
        function login() {
            var e = (new Date).valueOf();
            if (Math.ceil((e - startTime) / 1e3) > 295) return _("loginbtn").style.display = "none", _("email").style.display = "none", _("password").style.display = "none", _("status").innerHTML = "<strong class='error_red'>You have timed out please refresh your browser!</strong>", !1;
            var n = _("email").value,
                a = _("password").value,
                s = _("rme").value;
            if ("" == n || "" == a) _("status").innerHTML = "<i style='font-size: 14px;'>Fill out all of the form data</i>";
            else {
                _("loginbtn").style.display = "none", _("status").innerHTML = '<img src="/images/rolling.gif" width="30" height="30">';
                var t = ajaxObj("POST", "/php_parsers/login_parse.php");
                t.onreadystatechange = function() {
                    1 == ajaxReturn(t) && ("login_failed" == t.responseText ? (_("status").innerHTML = "Login unsuccessful, please try again.", _("loginbtn").style.display = "block") : window.location = "/index")
                }, t.send("e=" + n + "&p=" + a + "&t=<?php echo $_SESSION['login']['tk']; ?>&rme=" + s)
            }
        }	
	</script>
</head>
<body style="background-color: #fafafa;">
	<?php require_once 'template_pageTop.php'; ?>
	<div id="pageMiddle_2" style="background: transparent;">
		<form id="loginform" onsubmit="return false;">
			<p style="font-size: 28px;">Log In</p>
			<input type="text" id="email" onfocus="emptyElement('status')" maxlength="88" placeholder="Email">
			<input type="password" id="password" onfocus="emptyElement('status')" maxlength="150" autocomplete="true" placeholder="Password">
			<br /><div>Remember me:<label class="cntainerr">
                      <input type="checkbox" id="rme" name="rme">
                      <span class="checkmark"></span>
                    </label></div><br>
			<div class="clear"></div>
			<button id="loginbtn" class="main_btn" onclick="login()">Log In</button>
			<p id="status"></p>
			<span id="error_log"></span>
			<a href="/signup" class="rlink" id="pushRight">Sign up</a>
			<a href="/forgot_password" class="rlink">Forgotten your password?</a>
			<p style="font-size: 14px;">If you have any question or problem feel free to visit our <a href="/help" class="rlink">help</a> page.</p>
		</form>
	</div>
	<?php require_once 'template_pageBottom.php'; ?>
	<script type="text/javascript">
function getCookie(e){for(var t=e+"=",s=decodeURIComponent(document.cookie).split(";"),n=0;n<s.length;n++){for(var r=s[n];" "==r.charAt(0);)r=r.substring(1);if(0==r.indexOf(t))return r.substring(t.length,r.length)}return""}function setDark(){var e="thisClassDoesNotExist";if(!document.getElementById(e)){var t=document.getElementsByTagName("head")[0],s=document.createElement("link");s.id=e,s.rel="stylesheet",s.type="text/css",s.href="/style/dark_style.css",s.media="all",t.appendChild(s)}}var isdarkm=getCookie("isdark");"yes"==isdarkm&&setDark();
	</script>
</body>
</html>
