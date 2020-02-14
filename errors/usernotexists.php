<?php
	require_once '../php_includes/check_login_statues.php';
	require_once '../headers.php';
?>
<!DOCTYPE html>
<html>
  <head>
    <title>Pearscom - User Does Not Exist</title>
    <meta charset="UTF-8">
    <link rel="icon" type="image/x-icon" href="/images/webicon.png">
    <link rel="stylesheet" type="text/css" href="/style/style.css">
    <script src="/js/main.js" async></script>
    <script src="/js/ajax.js" async></script>
    <link rel="manifest" href="/manifest.json">

    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="#282828">
    <meta name="apple-mobile-web-app-title" content="Pearscom">
    <link rel="apple-touch-icon" href="/images/icons/icon-152x152.png">
    <meta name="theme-color" content="#282828" />
  </head>
  <body>
    <?php require_once 'template_pageTop.php'; ?>
    <div id="pageMiddle_2">
      <p id="center">This user does not exist or not activated yet, please go back!</p>
      <p class="center_2">You can log in <a href="/login">here.</a></p>
      <p class="center_2">Sign up <a href="/signup">here.</a></p>
      <p class="center_2">If you need any help please click <a href="/help">here.</a></p>
      <div id="error_pear"><img src="/images/error_pear" width="500" height="500"></div>
    </div>
    <?php require_once 'template_pageBottom.php'; ?>
  </body>
</html>
