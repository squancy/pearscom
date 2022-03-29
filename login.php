<?php
  require_once 'php_includes/check_login_statues.php';
  require_once 'headers.php';

  // If user is already logged in header them away
  if($user_ok == true){
    header("location: /user/".$_SESSION["username"]."/");
      exit();
  }

  // Security: add a token to make sure the proper user is logging in
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
  <script src="/js/main.js" async></script>
  <script src="/js/ajax.js" async></script>
  <link rel="manifest" href="/manifest.json">

  <meta name="apple-mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-status-bar-style" content="#282828">
  <meta name="apple-mobile-web-app-title" content="Pearscom">
  <link rel="apple-touch-icon" href="/images/icons/icon-152x152.png">
  <meta name="theme-color" content="#282828" />
  <meta name="description" content="Log in to your Pearscom account and start wrtiting
    articles, share photos and videos with your friends!">
  <meta name="author" content="Pearscom">
  <meta name="keywords" content="Pearscom log in, pearscom log in, pearscom login,
    log in to pearscom, log in pearscom, log pearscom, logged, logged in, account see,
    see account">
  <script src="/js/specific/login.js"></script>
  <script type="text/javascript">
    // Set token
    const TOKEN = '<?php echo $_SESSION['login']['tk']; ?>';
  </script>
</head>
<body>
  <?php require_once 'template_pageTop.php'; ?>
  <div id="pageMiddle_2">
    <p class="align gotham font30" style="margin-top: 75px;">Login</p>
    <form id="loginform" class="formContainer align" onsubmit="return false;">
      <input type="text" id="email" onfocus="emptyElement('status')" maxlength="88"
        placeholder="Email" class="formField">
      <input type="password" id="password" onfocus="emptyElement('status')" maxlength="150"
        autocomplete="true" placeholder="Password" class="formField">
      <br />
      <div class="align gothamNormal">
        <label class="chCont">
          Remember me
          <input type="checkbox" id="rme" name="rme">
          <span class="cbMark"></span>
        </label>
      </div>
      <br>
      <div class="clear"></div>
      <button id="loginbtn" class="redBtnFill btnCommon" onclick="login()">Log In</button>
      <p id="status" class="gothamNormal"></p>
      <span id="error_log"></span>
      <a href="/signup" class="rlink redLink" id="pushRight">Sign up</a>
      <a href="/forgot_password" class="rlink redLink">Forgotten your password?</a>
      <p style="font-size: 14px;">
        If you have any question or problem feel free to visit our
        <a href="/help" class="rlink">help</a> page.
      </p>
    </form>
  </div>
  <?php require_once 'template_pageBottom.php'; ?>
</body>
</html>
