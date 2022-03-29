<?php
  /*
    TODO: notification insert function & p_nohash replace func
  */

  require_once 'sec_session_start.php';
  require_once 'c_array.php';
  require_once 'headers.php';
  require_once 'php_includes/conn.php';
  require_once 'php_includes/settings_common.php';

  if(!isset($_SESSION)) { 
    sec_session_start();
  } 

  // If user is logged in, header them away
  if(isset($_SESSION["username"])){
    header("location: /index");
    exit();
  }

  // Check username with client side feedback
  if(isset($_POST["usernamecheck"])){
    $backs = "'\'";
    $username = mysqli_real_escape_string($conn, $_POST["usernamecheck"]);

    // Check if username is taken
    $uname_check = takenUname($conn, $username);
    validateUname($username, $uname_check, false);
  }

  // Check email
  if(isset($_POST["emailcheck"])){
    $email = $_POST['emailcheck'];

    // Check if email addr is already in db
    $email_check = emailRows($conn, $email);
    checkEmail($email_check, $email, false);
  }

  // Check password
  if(isset($_POST["passwordcheck"])){
    $password = $_POST['passwordcheck'];

    // Check if password contains at least 1 lowercase, 1 uppercase char and 1 num
    list($uc, $lc, $nm) = atLeastChars($password);
    checkPass($uc, $lc, $nm, $password, NULL, NULL, NULL, false);
  }

  // Check password confirmation
  if(isset($_POST["confrimcheck"]) && isset($_POST["password_original"])){
    $password2 = $_POST['confrimcheck'];
    $password_original = $_POST['password_original'];
    passFieldMatch($password2, $password_original, false);
  }

  // Check gender
  if(isset($_POST["gendercheck"])){
    $gender_original = $_POST['gendercheck'];
    validateGender($gender_original, false); 
  }

  // Check country
  if(isset($_POST["countrycheck"])){
    $country_original = $_POST['countrycheck'];
    validateCountry($country_original, false); 
  }

  // Check birthday
  if(isset($_POST["checkbd"])){
    $bd = $_POST['checkbd'];
    $bd = mysqli_real_escape_string($conn, $bd);
    validateBd($bd, false); 
  }

  // Check timezone
  if(isset($_POST["tzcheck"])){
    $tz = $_POST['tzcheck'];
    $tz = mysqli_real_escape_string($conn, $tz);
    validateTz($tz, false);
  }

  // Now check everything on the server side and registrate user
  if(isset($_POST["u"])){
    // Escape posted vars
    $u = mysqli_real_escape_string($conn, $_POST['u']);
    $e = mysqli_real_escape_string($conn, $_POST['e']);
    $p = $_POST['p'];
    $g = preg_replace('#[^a-z]#', '', $_POST['g']);
    $c = mysqli_real_escape_string($conn, $_POST["c"]);
    list($uc, $lc, $nm) = atLeastChars($p);

    // Get user IP addr
    $ip = preg_replace('#[^0-9.]#', '', getenv('REMOTE_ADDR'));

    // Check birthday
    $bd = preg_replace('#[^0-9.-]#', '', $_POST['bd']);
    $check = validateDate($bd);

    // Gather lat and lon coordinates for geolocation
    $lat = preg_replace('#[^0-9.,]#', '', $_POST["lat"]);
    $lon = preg_replace('#[^0-9.,]#', '', $_POST["lon"]);
    
    // Set timezone for proper dates
    $tz = mysqli_real_escape_string($conn, $_POST["tz"]);

    // Check if username and email is taken
    $taken = takenUname($conn, $u);
    $email_check = emailRows($conn, $e);

    // Validate and error check every field
    validateUname($u, $taken, true, false);  
    checkEmail($email_check, $e, true, false);
    validateBd($bd, true, false); 
    checkPass($uc, $lc, $nm, $p, NULL, NULL, NULL, true, false);
    validateCountry($c, true, false);
    validateGender($g, true, false);
    validateTz($tz, true, false);
  
    // Hash the password
    $p_hash = password_hash($p, PASSWORD_DEFAULT);

    // Add user info into the database table for the main site table
    $sql = "INSERT INTO users (username, email, password, gender, country, ip, signup,
      lastlogin, notescheck, bday, lat, lon, tz)       
      VALUES(?,?,?,?,?,?,NOW(),NOW(),NOW(),?,?,?,?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssssssss", $u, $e, $p_hash, $g, $c, $ip, $bd, $lat, $lon, $tz);
    $stmt->execute();
    $stmt->close();
    $uid = mysqli_insert_id($conn);

    // Establish their row in the useroptions table
    $sql = "INSERT INTO useroptions (id, username) VALUES (?,?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $uid, $u);
    $stmt->execute();
    $stmt->close();

    // Create directory to hold each user's files
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
    $note = 'Most social media sites have a great welcome message, but we are not most...
      Anyway, we hope that you will spend a great time with your friends and join to this
      amazing community.';
    $sql = "INSERT INTO notifications(username, initiator, app, note, date_time)
      VALUES(?,?,?,?,NOW())";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssss", $u, $u, $app, $note);
    $stmt->execute();
    $stmt->close();

    // Email the user their activation link
    $to = "$e";
    $from = "Pearscom <auto_responder@pearscom.com>";
    $subject = 'Pearscom Account Activation';
    $message = '
      <!DOCTYPE html>
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

          <body style="font-family: Arial, sans-serif; background-color: #fafafa;
            box-sizing: border-box; margin: 0 auto; margin-top: 10px; max-width: 800px;">
            <div style="padding:10px; background-color: #282828; margin: 0 auto;
              border-radius: 20px 20px 0px 0px;">
              <a href="https://www.pearscom.com">
                <img src="https://www.pearscom.com/images/newfav.png" width="49"
                  height="49" alt="pearscom.com" style="border:none; display: block;
                  margin: 0 auto;">
              </a>
              &nbsp;
            </div>
            <div style="padding:24px; font-size:14px; border-left: 1px solid #e6e6e6;
              border-bottom: 1px solid #e6e6e6; border-right: 1px solid #e6e6e6;
              text-align: center;">
              <p style="font-size: 18px; color: #999; margin-top: 0px; text-align: left;">
                Welcome to Pearscom '.$u.',
              </p>
              We are glad that your signing up to Pearscom was successful and now you just
              need to activate your account in order to log in. After the account activation
              you will be able to log in and use your account immediately.
              <br>
              <p>
                When logging in you will need your password and email given during the sign up
                part and if you forget it anytime feel free to visit the
                <a href="https://www.pearscom.com/help" style="color: red;">
                  help &amp; support
                </a> page.
              </p>
               <a href="https://www.pearscom.com/activation.php?id='.$uid.'&u='.urlencode($u).'
                &p='.$p_nohash.'&e='.$e.'" style="color: white;" id="link">
                 <div style="background-color: red; color: white; padding: 5px;
                  text-align: center; width: 200px; border-radius: 10px; margin: 0 auto;"
                  id="atp">
                  Activate Account
                </div>
               </a>
               <br>
               Again, thank you for signing up to Pearscom and hope you will enjoy being part
               of an amazing community!<br><br>
            </div>
            <div style="background: #282828; padding: 2px; border-radius: 0px 0px 20px 20px;
              color: #c1c1c1; font-size: 14px;">
              <p style="text-align: center;">
                For further information consider visiting our
                <a href="https://www.pearscom.com/help" style="color: red;">
                  help &amp; support
                </a>
                page<br><br>&copy; Pearscom <?php echo date("Y"); ?>
                <i>&#34;Connect us, connect the world&#34;</i>
              </p>
            </div>
           </body>
        </html>
      ';
      $headers = "From: $from\n";
      $headers .= "MIME-Version: 1.0\n";
      $headers .= "Content-type: text/html; charset=iso-8859-1\n";
          
      mail($to, $subject, $message, $headers);
      echo "signup_success";
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
  <meta name="keywords" content="pearscom sign up, signup, pearscom signup, register,
    pearscom register, create account pearscom">
  <script src="/js/jjs.js"></script>
  <meta name="author" content="Pearscom">
  <link rel="icon" type="image/x-icon" href="/images/newfav.png">
  <link rel="stylesheet" type="text/css" href="/style/style.css">
  <script src="/js/main.js"></script>
  <script src="/js/specific/settings.js"></script>
  <script src="/js/specific/signup.js"></script>
  <script src="/js/ajax.js" async></script>
  <link rel="manifest" href="/manifest.json">

  <meta name="apple-mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-status-bar-style" content="#282828">
  <meta name="apple-mobile-web-app-title" content="Pearscom">
  <link rel="apple-touch-icon" href="/images/icons/icon-152x152.png">
  <meta name="theme-color" content="#282828" />
  <script type="text/javascript">
  </script>
</head>
<body>
  <?php require_once 'template_pageTop.php'; ?>
  <div id="pageMiddle_2">
    <p style="margin-top: 75px;" class="align gotham font30" id="sutxt">Sign Up</p>
    <form name="signupform" id="loginform" class="align formContainer" onsubmit="return false;">
      <input id="username" type="text"  maxlength="100"
        placeholder="Username or real name" class="formField">
      <input id="email" type="text" placeholder="Email" class="formField">
      <input id="pass1" type="password" autocomplete="true"
        placeholder="Password" class="formField">
      <input id="pass2" type="password" autocomplete="true"
        placeholder="Confirm password" class="formField">
      <select id="gender" class="specSelect">
        <option disabled="true" value="" selected="true">Choose gender</option>
        <option value="m">Male</option>
        <option value="f">Female</option>
      </select>

      <span class="signupStats" id="genderstatus"></span>
      <select id="country" class="specSelect">
        <option disabled="true" value="" selected="true">Choose country</option>
        <?php require_once 'template_country_list.php'; ?>
      </select>

      <div class="clear"></div>

      <input type="date" id="birthday" min="1899-01-01" max="<?php echo date("Y-m-d"); ?>"
        data-placeholder="Date of birth" 
        class="specSelect">

      <select id="timezone" class="specSelect">
        <option disabled="true" value="" selected="true">Choose timezone</option>
        <?php require_once 'template_timezone_list.php'; ?>
      </select>

      <div class="clear"></div>
      <button id="acc_geo" class="btnCommon redBtnFill">Geolocation</button>
      <button id="signupbtn" class="redBtnFill btnCommon" onclick="signup()">Sign Up</button>
      <div class="clear"></div>

      <div id="wrapping" style="display: none;">
        <p style="margin-top: 0px;">
          While using Pearscom, we may look up for your geolocation, process, and use it for
          demographic and security purposes. We all do this for the benefit of our users,
          in order serve local, valid and trusted content, to count the distance between
          different locations and for several other features. We may store it in our system &
          database, use it in our algorithms and make analysis with the help of your
          geolocation. Please keep in mind that we do not abuse with your personal geolocation
          datas, nor do we publish it but we keep it in private.
        </p>
        <button onclick="getLocation()" id="vupload">Agree</button>
      </div>
      <div style="display: none;">
        <span style="font-size: 12px;">Latitude: </span>
        <span id="lat" style="font-size: 12px;">not located yet</span><br />
        <span style="font-size: 12px;">Longitude: </span>
        <span id="lon" style="font-size: 12px;">not located yet</span>
      </div>
      <div id="status" class="gothamNormal" style="margin-top: 20px;"></div>
      <div id="mapholder"></div>
      <div id="geo_err" class="gothamNormal"></div>
      <p class="font14">
        By signing up you agree our <a href="/policies" class="rlink">Privacy and Policy</a>,
        the way we collect and use your data and accept the use of <a href="policies"
        class="rlink">cookies</a> on the site.
      </p>
      <p class="font14">
        Already have an account? <a href="/login" class="rlink">Log In</a>
      </p>
    </form>
  </div>
  <?php include_once("template_pageBottom.php"); ?>
  <script type="text/javascript">
    _("acc_geo").addEventListener("click", function showmap(){
      getLocation();
    });
  </script>
</body>
</html>
