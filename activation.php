<?php
  /*
    Handle the activation of a user with their id, username, email and password (encrypted)
    Check for potential activation errors and update the database in regard to this
  */

  require_once 'headers.php';

  function escapeURLParams($id_g, $u, $e, $p) {
    global $conn;
    $id = preg_replace('#[^0-9]#i', '', $id_g); 
    $uname = mysqli_real_escape_string($conn, $u);
    $email = mysqli_real_escape_string($conn, $e);
    $pwd = mysqli_real_escape_string($conn, $p);
    return [$id, $uname, $email, $pwd];
  }

  function resetPwd(&$p) {
    if(strpos($p, '__slash__')){
      $p = str_replace('__slash__', '/', $p);
    }
    if(strpos($p, '__dollar__')){
      $p = str_replace('__dollar__', '$', $p);
    }
    if(strpos($p, '__dot__')){
      $p = str_replace('__dot__', '.', $p);
    }
  }

  function checkCredentials($id, $u, $e, $p) {
    global $conn, $error;
    $sql = "SELECT * FROM users WHERE id=? AND username=? AND email=? AND password=? LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("isss", $id, $u, $e, $p);
    $stmt->execute();
    $stmt->store_result();
    $stmt->fetch();
    $numrows = $stmt->num_rows;

    if($numrows == 0){
      $error = "Your credentials are not matching anything in our system";
    }
    $stmt->close();
  }

  function activateUser($id) {
    global $conn, $one;
    $sql = "UPDATE users SET activated=? WHERE id=? LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $one, $id);
    $stmt->execute();
    $stmt->close();
  }
  
  $one = "1";
  $error = "";
  if(isset($_GET['id']) && isset($_GET['u']) && isset($_GET['e']) && isset($_GET['p'])) {
    include_once("php_includes/conn.php");

    // Escape URL parameters
    list($id, $u, $e, $p) = escapeURLParams($_GET['id'], $_GET['u'], $_GET['e'], $_GET['p']);

    // Reset password to its original state (it was URL-friendly)
    resetPwd($p);

    if($id == "" || strlen($u) < 3 || strlen($e) < 5 || $p == ""){
      $error = "Activation string length issues";
    }

    // Check their credentials against the database
    checkCredentials($id, $u, $e, $p);

    // No errors; update db
    if (!$error) {
      activateUser($id);
    } else {
      $error = "Activation failure";
    }
    
    // Optional double check to see if activated in fact now = 1
    $sql = "SELECT * FROM users WHERE id=? AND activated=? LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("is",$id,$one);
    $stmt->execute();
    $stmt->store_result();
    $stmt->fetch();
    $numrows = $stmt->num_rows;

    // Potential activation errors
    if($numrows == 0){
      $error = "Activation failure";
    } else if($numrows == 1) {
      $error = "Activation success";
    } else {
      $error = "Unknown error occurred";
    }
  } else {
    header('Location: /index');
  }
?>
<!DOCTYPE html>
<html>
<head>
  <title>Pearscom - Activate Account</title>
  <meta charset="utf-8">
  <link rel="icon" type="image/x-icon" href="/images/newfav.png">
  <link rel="stylesheet" type="text/css" href="/style/style.css">
  <link rel="stylesheet" href="font-awesome-4.7.0/css/font-awesome.min.css">
  <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1"/>
  <script src="/js/jjs.js"></script>
  <script src="/js/main.js"></script>
  <script src="/js/uijs.js"></script>
  <link rel="manifest" href="/manifest.json">
  <meta name="apple-mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-status-bar-style" content="#282828">
  <meta name="apple-mobile-web-app-title" content="Pearscom">
  <link rel="apple-touch-icon" href="/images/icons/icon-152x152.png">
  <meta name="theme-color" content="#282828" />
  <style type='text/css'>
    .wrong {
      text-align: center;
      font-size: 24px;
      color: #999;
      margin-top: 0;
    }
  </style>
</head>
<body>
  <div id="pageMiddle_2" style='margin-top: 20px; width: 90%;'>
    <?php if($error != "Activation success") { ?>
      <p class='font30 align gotham'>Failure</p>
    <?php } else { ?>
      <p class='font30 align gotham'>Congratulations!</p>
    <?php } ?>

    <div id="divf1" style="margin: 0 auto; max-width: 400px;">
      <?php if($error == "Activation success"){ ?>
        <img src="/images/checked.png" style="width: 100px; display: block; margin: 0 auto;">
      <?php }else{ ?>
        <img src="/images/error.png" style="width: 100px; display: block; margin: 0 auto;">
      <?php } ?>
    </div>
    <div id="divf2" style='text-align: center;'>
      <p class="gothamNormal">
        <?php echo $error; ?>
      </p>

      <?php if($error == "Activation success"){ ?>
        <p class="lh">
          Great! You have successfully verified your email and activated your account so now
          you are ready to <a href="/login">log in</a> to your account.
          <br><br>
          If you wish to ask any questions feel free to <a href="/help">do it.</a>
          <br><br>
          We hope you will enjoy being part of an amazing community!
        </p>
      <?php }else{ ?>
        <p class="lh">
          We're sorry...
          Unfortunately an error has occured during your signing up. 
        </p>
        <p class="lh">
          Don&#39;t worry! We might help you to solve this problem if you send us a
          <a href="/help">problem report.</a>
        </p>
      <?php } ?>
    </div>
    <div class="clear"></div>
    <div class="align">
      <button class="btnCommon redBtnFill" id="loginbtn" style="margin: 5px;">Log In</button>
      <button class="btnCommon redBtnFill" id="suppbtn"
        style="margin: 5px;">Help &amp; Support</button>
    </div>
  </div>
  <script type="text/javascript">
    function redirect(url) {
      window.location.href = url;
    }
    _('loginbtn').addEventListener('click', e => redirect('/login')); 
    _('suppbtn').addEventListener('click', e => redirect('/help')); 
  </script>
  <?php require_once 'template_pageBottom.php'; ?>
</body>
</html>
