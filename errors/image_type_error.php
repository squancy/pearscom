<?php 
	require_once '../php_includes/check_login_statues.php';
	require_once '../headers.php';
?>
<!DOCTYPE html>
<html>
  <head>
    <title>Pearscom - Image type error occured</title>
    <meta charset="utf-8">
    <link rel="icon" type="image/x-icon" href="/images/webicon.png">
    <link rel="stylesheet" type="text/css" href="/style/style.css">
    <link rel="stylesheet" href="font-awesome-4.7.0/css/font-awesome.min.css">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="jquery_in.js" async></script>
    <script src="/js/main.js" async></script>
    <script src="/js/ajax.js" async></script>
    <script src="/js/create_down.js" async></script>
    <link rel="manifest" href="/manifest.json">

    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="#282828">
    <meta name="apple-mobile-web-app-title" content="Pearscom">
    <link rel="apple-touch-icon" href="/images/icons/icon-152x152.png">
    <meta name="theme-color" content="#282828" />
  </head>
  <body style="background: linear-gradient(#ffffe6, #fff);">
    <div id="pageMiddle_2" style="margin-top: -10px; min-height: 700px;
      background: transparent;">
      <p style="text-align: center; font-size: 24px;">
        The image you want to upload does not support none of allowed file extenstions.
      </p>
      <div style="width: 50%; height: auto; float: left; background-color: #763626;
        border-left: 5px solid red;" id="divf1">
        <img src="images/404pear.png" width="100%" height="auto"
          style="display: block; margin: 0 auto; max-width: 500px;">
      </div>
      <div style="float: right; width: 48%;" id="divf2">
        <p style="font-size: 14px; margin: 0px;">
          Sorry, but the image you want to upload has an unsupported extestion.
          The allowed extensions are: jpg, jpeg, png and gif.
        </p>
        <p style="font-size: 14px; margin: 0px;">
          In order to get informed visit the <a href="/help">help</a> page.
        </p>
        <p style="font-size: 14px; margin: 0px;">
          Otherwise here are some links that might be useful for you: 
        </p>
        <a href="/index">Home page</a>
        <p style="font-size: 14px; margin: 0px;">
          <a href="/signup">Sign in</a> - if you do not have an own account
        </p>
        <p style="font-size: 14px; margin: 0px;">
          <a href="/login">Log in</a> - if you do not have an account
        </p>
        <hr style="border: 0; border-bottom: 1px dashed #ccc; background-color: #999;">
        <p style="font-size: 14px; margin: 0px;">
          If you want to report a bug or a broken url you can do it
          <a href="/help#report">here.</a>
        </p>
        <p style="font-size: 14px; margin: 0px;">
          If nothing helped a page refresh can also make wonders
        </p>
      </div>
    </div>
    <?php require_once 'template_pageBottom.php'; ?>
  </body>
</html>
