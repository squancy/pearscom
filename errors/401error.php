<?php
  require_once 'php_includes/check_login_statues.php';
  require_once 'headers.php';
?>
<!DOCTYPE html>
<html>
  <head>
    <title>401 - Unauthorized</title>
    <meta charset="utf-8">
    <link rel="icon" type="image/x-icon" href="/images/newfav.png">
    <link rel="stylesheet" type="text/css" href="/style/style.css">
    <link rel="stylesheet" href="/font-awesome-4.7.0/css/font-awesome.min.css">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="/jquery_in.js" async></script>
    <script src="/js/main.js" async></script>
    <script src="/js/ajax.js" async></script>
    <script src="/js/lload.js"></script>
    <link rel="manifest" href="/manifest.json">

    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="red">
    <meta name="apple-mobile-web-app-title" content="Pearscom">
    <link rel="apple-touch-icon" href="/images/icons/icon-152x152.png">
    <meta name="theme-color" content="#282828" />
  </head>
  <body style="background: white;">
    <div>
      <p style="color: red; font-weight: 600; font-size: 100px; margin: 0;" class="txtc">401</p>
      <p class="txtc" style="color: #999; font-size: 20px;">
        Oops... it seems that an error occurred during the processing of your request
      </p>
      <div style="width: 100%; background-color: black;">
        <img src="/images/404pear.png" style="max-height: 400px; display: block;
          margin: 0 auto; max-width: 100%;">
      </div>
    </div>
  </body>
</html>
