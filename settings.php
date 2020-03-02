<?php
  require_once 'php_includes/check_login_statues.php';
  require_once 'php_includes/perform_checks.php';
  require_once 'php_includes/settings_common.php';
  require_once 'timeelapsedstring.php';
  require_once 'headers.php';

  // Initialize some variables
  $email = "";
  $password = "";
  $country = "";
  $one = "1";

  // Make sure the user logged in
  if($log_username == "" || $_SESSION["username"] == "" || !isset($_SESSION["username"])){
    header('Location: /index');
    exit();
  }

  // Check if the user exists in db
  userExists($conn, $log_username);

  // User information query
  list($email, $password, $country) = getUserInfo($conn, $log_username);

  // Ajax calls this code to execute (password)
  if(isset($_POST["cpasscheck"])){
    $cp = $_POST['cpasscheck'];
  
    // Check if user entered password correctly
    validatePass($cp, $password, false);
  }

  if(isset($_POST["npasscheck"]) && isset($_POST["cp"])){
    $np = $_POST["npasscheck"];
    $cp = $_POST["cp"];

    // Check if password contains at least 1 uppercase, 1 lowercase char and 1 number
    list($uc, $lc, $nm) = atLeastChars($np);

    // Error checking
    checkPass($uc, $lc, $nm, $np, $log_username, $email, $cp, false);
  }

  if(isset($_POST["cnp"]) && isset($_POST["np"])){
    $cnp = $_POST["cnp"];
    $np = $_POST["np"];

    // Check if user has successfully confirmed their password
    passFieldMatch($cnp, $np, false);
  }

  if(isset($_POST["curp"]) && isset($_POST["newp"]) && isset($_POST["cnewp"])){
    $curp = $_POST["curp"];
    $newp = $_POST["newp"];
    $cnewp = $_POST["cnewp"];
    list($uc, $lc, $nm) = atLeastChars($newp);

    // Validate and check password as before but now on the server side
    validatePass($curp, $password, true, false);
    checkPass($uc, $lc, $nm, $newp, $log_username, $email, $curp, true, false);
    passFieldMatch($cnewp, $newp, true, false);

    // If no errors change password in db; let's hash it first
    if($curp == "" || $newp == "" || $cnewp == ""){
      echo 'Please fill in all fields!';
      exit();
    }else{
      $p_hash = password_hash($newp, PASSWORD_DEFAULT);
      $sql = "UPDATE users SET password = ? WHERE username = ? AND email = ? LIMIT 1";
      $stmt = $conn->prepare($sql);
      $stmt->bind_param("sss", $p_hash, $log_username, $email);
      $stmt->execute();
      $stmt->close();

      // Log out user
      if(!isset($_SESSION)) {
        session_start(); 
      }

      // Set Session data to an empty array
      $_SESSION = array();

      // Expire their cookie files
      if(isset($_COOKIE["id"]) && isset($_COOKIE["user"]) && isset($_COOKIE["pass"])) {
        setcookie("id", '', strtotime( '-5 days' ), '/');
        setcookie("user", '', strtotime( '-5 days' ), '/');
        setcookie("pass", '', strtotime( '-5 days' ), '/');
      }

      // Destroy the session variables
      session_destroy();

      // Double check to see if their sessions exists
      if(isset($_SESSION['username'])){
        header("location: logoutfail.php");
      }
      
      echo "cpass_success";
      exit();
    }
  }

  // User changes country
  if(isset($_POST["ncountry"])){
    $ncountry = preg_replace('#[^a-z0-9 .-]#i', '', $_POST['ncountry']);
    checkCountry($ncountry, $country, false);  
  }

  if(isset($_POST["confc"]) && isset($_POST["pwd"])){
    $confc = preg_replace('#[^a-z0-9 .-]#i', '', $_POST['confc']);
    $pwd = $_POST["pwd"];

    // Verify password and country on the server side
    validatePass($pwd, $password, true, false);
    checkCountry($confc, $country, true, false);

    $sql = "UPDATE users SET country = ? WHERE username = ? AND email = ? LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sss", $confc, $log_username, $email);
    $stmt->execute();
    $stmt->close();

    echo "country_success";
    exit();
  }

  // User changes email
  if(isset($_POST["nemail"])){
    $nemail = $_POST["nemail"];
    $email_check = emailRows($conn, $nemail);  

    checkEmail($email_check, $nemail, false);
  }

  if(isset($_POST["email"]) && isset($_POST["pass"])){
    $nemail = $_POST["email"];
    $pass = $_POST["pass"];
  
    // Valide email now on the server side
    $email_check = emailRows($conn, $nemail);
    checkEmail($email_check, $nemail, true, false);
    validatePass($pass, $password, true, false);

    if($nemail == "" || $pass == ""){
      echo 'Fill out all the form data!';
      exit();
    }else{
      $sql = "UPDATE users SET email = ? WHERE username = ? AND email = ? LIMIT 1";
      $stmt = $conn->prepare($sql);
      $stmt->bind_param("sss",$nemail,$log_username,$email);
      $stmt->execute();
      $stmt->close();
    }
    echo "email_success";
    exit();
  }

  // User deletes account
  if(isset($_POST["whyda"])){
    $ta = mysqli_real_escape_string($conn, $_POST["whyda"]);
    charLimit($ta, false);
  }

  if(isset($_POST["whyda_"]) && isset($_POST["dacpass"])){
    $ta = mysqli_real_escape_string($conn, $_POST["whyda_"]);
    $pass = $_POST["dacpass"];
    charLimit($ta, true, false);
    validatePass($pass, $password, true, false);

    if($pass == ""){
      echo 'Fill in all fields!';
      exit();
    }else{
      // Save the username and reason in db
      if($ta == ""){
        $sql = "INSERT INTO deleted_accs (username,delete_date) VALUES (?,NOW())";
      }else{
        $sql = "INSERT INTO deleted_accs (username,reason,delete_date) VALUES (?,?,NOW())";
      }
      $stmt = $conn->prepare($sql);
      if ($ta == "") {
        $stmt->bind_param("s",$log_username);
      } else {
        $stmt->bind_param("ss",$log_username,$ta);
      }
      $stmt->execute();
      $stmt->close();

      // Delete user's folder, images, background, videos etc
      $files = glob('/user/'.$log_username.'/*'); 

      $filenameb = '/user/'.$log_username.'/background';
      if(file_exists($filenameb)){
        $files = glob('/user/'.$log_username.'/background/*'); 
        unlinkFiles($files);
      }

      $filenamev = '/user/'.$log_username.'/videos';
      if(file_exists($filenamev)){
        $files = glob('/user/'.$log_username.'/videos/*'); 
        unlinkFiles($files);
      }

      // Remove directories
      remDir('/user/'.$log_username.'/background');
      remDir('/user/'.$log_username.'/videos');
      remDir('/user/'.$log_username);
 
      // Delete every record in the db that is connected to the user
      $sql = "DELETE FROM users WHERE username = ? AND email = ?";
      $stmt = $conn->prepare($sql);
      $stmt->bind_param("ss",$log_username,$email);
      $stmt->execute();
      $stmt->close();

      $sql = "DELETE FROM useroptions WHERE username = ?";
      $stmt = $conn->prepare($sql);
      $stmt->bind_param("s",$log_username);
      $stmt->execute();
      $stmt->close();

      // Then remove every clue of the person from the database
      $sql = "DELETE FROM articles WHERE written_by = ?";
      $stmt = $conn->prepare($sql);
      $stmt->bind_param("s",$log_username);
      $stmt->execute();
      $stmt->close();

      $sql = "DELETE FROM article_status WHERE account_name = ? OR author = ?";
      $stmt = $conn->prepare($sql);
      $stmt->bind_param("ss",$log_username,$log_username);
      $stmt->execute();
      $stmt->close();

      $sql = "DELETE FROM art_reply_likes WHERE username = ?";
      $stmt = $conn->prepare($sql);
      $stmt->bind_param("s",$log_username);
      $stmt->execute();
      $stmt->close();

      $sql = "DELETE FROM blockedusers WHERE blocker = ? OR blockee = ?";
      $stmt = $conn->prepare($sql);
      $stmt->bind_param("ss",$log_username,$log_username);
      $stmt->execute();
      $stmt->close();

      $sql = "DELETE FROM edit WHERE username = ?";
      $stmt = $conn->prepare($sql);
      $stmt->bind_param("s",$log_username);
      $stmt->execute();
      $stmt->close();

      $sql = "DELETE FROM fav_art WHERE username = ?";
      $stmt = $conn->prepare($sql);
      $stmt->bind_param("s",$log_username);
      $stmt->execute();
      $stmt->close();

      $sql = "DELETE FROM follow WHERE follower = ? OR following = ?";
      $stmt = $conn->prepare($sql);
      $stmt->bind_param("ss",$log_username,$log_username);
      $stmt->execute();
      $stmt->close();

      $sql = "DELETE FROM friends WHERE user1 = ? OR user2 = ?";
      $stmt = $conn->prepare($sql);
      $stmt->bind_param("ss",$log_username,$log_username);
      $stmt->execute();
      $stmt->close();

      $sql = "DELETE FROM gmembers WHERE mname = ?";
      $stmt = $conn->prepare($sql);
      $stmt->bind_param("s",$log_username);
      $stmt->execute();
      $stmt->close();

      $sql = "DELETE FROM grouppost WHERE author = ?";
      $stmt = $conn->prepare($sql);
      $stmt->bind_param("s",$log_username);
      $stmt->execute();
      $stmt->close();

      $sql = "DELETE FROM groups WHERE creator = ?";
      $stmt = $conn->prepare($sql);
      $stmt->bind_param("s",$log_username);
      $stmt->execute();
      $stmt->close();

      $sql = "DELETE FROM group_reply_likes WHERE username = ?";
      $stmt = $conn->prepare($sql);
      $stmt->bind_param("s",$log_username);
      $stmt->execute();
      $stmt->close();

      $sql = "DELETE FROM group_status_likes WHERE username = ?";
      $stmt = $conn->prepare($sql);
      $stmt->bind_param("s",$log_username);
      $stmt->execute();
      $stmt->close();

      $sql = "DELETE FROM heart_likes WHERE username = ?";
      $stmt = $conn->prepare($sql);
      $stmt->bind_param("s",$log_username);
      $stmt->execute();
      $stmt->close();

      $sql = "DELETE FROM notifications WHERE username = ? OR initiator = ?";
      $stmt = $conn->prepare($sql);
      $stmt->bind_param("ss",$log_username,$log_username);
      $stmt->execute();
      $stmt->close();

      $sql = "DELETE FROM photos WHERE user = ?";
      $stmt = $conn->prepare($sql);
      $stmt->bind_param("s",$log_username);
      $stmt->execute();
      $stmt->close();

      $sql = "DELETE FROM photos_status WHERE author = ? OR account_name = ?";
      $stmt = $conn->prepare($sql);
      $stmt->bind_param("ss",$log_username,$log_username);
      $stmt->execute();
      $stmt->close();

      $sql = "DELETE FROM photo_reply_likes WHERE username = ?";
      $stmt = $conn->prepare($sql);
      $stmt->bind_param("s",$log_username);
      $stmt->execute();
      $stmt->close();

      $sql = "DELETE FROM photo_stat_likes WHERE username = ?";
      $stmt = $conn->prepare($sql);
      $stmt->bind_param("s",$log_username);
      $stmt->execute();
      $stmt->close();

      $sql = "DELETE FROM pm WHERE sender = ? OR receiver = ?";
      $stmt = $conn->prepare($sql);
      $stmt->bind_param("ss",$log_username,$log_username);
      $stmt->execute();
      $stmt->close();

      $sql = "DELETE FROM photo_stat_likes WHERE username = ?";
      $stmt = $conn->prepare($sql);
      $stmt->bind_param("s",$log_username);
      $stmt->execute();
      $stmt->close();

      $sql = "DELETE FROM problem_report WHERE username = ?";
      $stmt = $conn->prepare($sql);
      $stmt->bind_param("s",$log_username);
      $stmt->execute();
      $stmt->close();

      $sql = "DELETE FROM reply_likes WHERE username = ?";
      $stmt = $conn->prepare($sql);
      $stmt->bind_param("s",$log_username);
      $stmt->execute();
      $stmt->close();

      $sql = "DELETE FROM status WHERE author = ? OR account_name = ?";
      $stmt = $conn->prepare($sql);
      $stmt->bind_param("ss",$log_username,$log_username);
      $stmt->execute();
      $stmt->close();

      $sql = "DELETE FROM status_likes WHERE username = ?";
      $stmt = $conn->prepare($sql);
      $stmt->bind_param("s",$log_username);
      $stmt->execute();
      $stmt->close();

      $sql = "DELETE FROM videos WHERE user = ?";
      $stmt = $conn->prepare($sql);
      $stmt->bind_param("s",$log_username);
      $stmt->execute();
      $stmt->close();

      $sql = "DELETE FROM video_likes WHERE username = ?";
      $stmt = $conn->prepare($sql);
      $stmt->bind_param("s",$log_username);
      $stmt->execute();
      $stmt->close();

      $sql = "DELETE FROM video_reply_likes WHERE user = ?";
      $stmt = $conn->prepare($sql);
      $stmt->bind_param("s",$log_username);
      $stmt->execute();
      $stmt->close();

      $sql = "DELETE FROM video_status WHERE author = ? OR account_name = ?";
      $stmt = $conn->prepare($sql);
      $stmt->bind_param("ss",$log_username,$log_username);
      $stmt->execute();
      $stmt->close();

      $sql = "DELETE FROM video_status_likes WHERE username = ?";
      $stmt = $conn->prepare($sql);
      $stmt->bind_param("s",$log_username);
      $stmt->execute();
      $stmt->close();
      echo "delete_success";
      exit();
    }
  }

  // User changes gender
  if(isset($_POST["pwd"]) && isset($_POST["gender"])){
    $pwd = $_POST["pwd"];
    $gender = mysqli_real_escape_string($conn, $_POST["gender"]);

    // Get current gender
    $curg = currentGender($conn, $log_username, $email);

    if($gender == "Male"){
      $gender = "m";
    }else if($gender == "Female"){
      $gender = "f";
    }else{
      echo 'Invalid gender given!';
      exit();
    }

    validatePass($pwd, $password, true, false);

    // Validate gender
    if($gender == "" || $pwd == ""){
      echo 'Please fill in all fields!';
      exit();
    }else if($gender != "m" && $gender != "f"){
      echo 'Invalid gender given!';
      exit();
    }else if($gender == $curg){
      echo 'This is your current gender!';
      exit();
    }else{
      $sql = "UPDATE users SET gender = ? WHERE username = ? AND email = ? LIMIT 1";
      $stmt = $conn->prepare($sql);
      $stmt->bind_param("sss",$gender,$log_username,$email);
      $stmt->execute();
      $stmt->close();

      echo "gender_success";
      exit();
    }
  }

  if(isset($_POST["cgend"])){
    $g = mysqli_real_escape_string($conn, $_POST["cgend"]);
    if($g == ""){
      genErrMsg('Fill in all fields', false);
      exit();
    }else if($g != "Female" && $g != "Male"){
      genErrMsg('Invalid gender given', false);
      exit();
    }else{
      echo "";
      exit();
    }
  }

  // User changes timezone
  if(isset($_POST["timezone"])){
    $tz = $_POST["timezone"];
    validateTz($tz, false);
  }

  if(isset($_POST["pwd"]) && isset($_POST["tz"])){
    $pwd = $_POST["pwd"];
    $tz = $_POST["tz"];
    $tz = mysqli_real_escape_string($conn, $tz);

    // Validate password and timezone on the server side
    validatePass($pwd, $password, true, false);
    validateTz($tz, true, false);

    // Update to new tz in db
    $sql = "UPDATE users SET tz = ? WHERE username = ? AND email = ? LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sss", $tz, $log_username, $email);
    $stmt->execute();
    $stmt->close();

    echo "tz_success";
    exit();
  }

  if(isset($_POST["bd"]) && isset($_POST["pwd"])){
    $pwd = $_POST["pwd"];
    $bd = mysqli_real_escape_string($conn, $_POST["bd"]);

    // Validate birthday on the server side
    validatePass($pwd, $password, true, false);
    validateBd($bd, true, false);

    // Insert new bd to db
    $sql = "UPDATE users SET bday = ? WHERE username = ? AND email = ? LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sss", $bd, $log_username, $email);
    $stmt->execute();
    $stmt->close();

    echo "bd_success";
    exit();
  }

  if(isset($_POST["cbirthd"])){
    $bd = mysqli_real_escape_string($conn, $_POST["cbirthd"]);
    validateBd($bd, false);
  }

  // User changes username
  if(isset($_POST["cusern"])){
    $un = $_POST["cusern"];

    $taken = takenUname($conn, $un);
    validateUname($un, $taken, false);
  }

  if(isset($_POST["pwd"]) && isset($_POST["cun"])){
    $un = $_POST["cun"];
    $pwd = $_POST["pwd"];
  
    // Validate username on the server side
    $taken = takenUname($conn, $un);
    validatePass($pwd, $password, true, false);
    validateUname($un, $taken, true, false);

    $un = mysqli_real_escape_string($conn, $un);

    // Rename folders
    mkdir('user/'.$un.'/',0755);
    $fname = 'user/'.$log_username.'/background/';
    $fname2 = 'user/'.$log_username.'/videos/';
    mkdir('user/'.$un.'/background/',0755);
    mkdir('user/'.$un.'/videos/',0755);
    
    $len = strlen($log_username);
    $lena = $len + 6;
    $lenb = $len + 17;
    $lenv = $len + 13;
   
    // Rename file names
    $files = glob('user/'.$log_username.'/*'); 
    renameFiles($files, '');
    
    $files = glob('user/'.$log_username.'/background/*');
    renameFiles($files, 'background/');

    $files = glob('user/'.$log_username.'/videos/*');
    renameFiles($files, 'videos/');
          
    // Remove old directories
    rmdir($fname);
    rmdir($fname2);
    rmdir('user/'.$log_username.'/');

    // Database renames
    $sql = "UPDATE articles SET written_by = ? WHERE written_by = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss",$un,$log_username);
    $stmt->execute();
    $stmt->close();

    $sql = "UPDATE article_status SET author = ? WHERE author = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss",$un,$log_username);
    $stmt->execute();
    $stmt->close();

    $sql = "UPDATE article_status SET account_name = ? WHERE account_name = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss",$un,$log_username);
    $stmt->execute();
    $stmt->close();

    $sql = "UPDATE art_reply_likes SET username = ? WHERE username = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss",$un,$log_username);
    $stmt->execute();
    $stmt->close();

    $sql = "UPDATE art_stat_likes SET username = ? WHERE username = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss",$un,$log_username);
    $stmt->execute();
    $stmt->close();

    $sql = "UPDATE blockedusers SET blockee = ? WHERE blockee = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss",$un,$log_username);
    $stmt->execute();
    $stmt->close();

    $sql = "UPDATE blockedusers SET blocker = ? WHERE blocker = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss",$un,$log_username);
    $stmt->execute();
    $stmt->close();

    $sql = "UPDATE deleted_accs SET username = ? WHERE username = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss",$un,$log_username);
    $stmt->execute();
    $stmt->close();

    $sql = "UPDATE edit SET username = ? WHERE username = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss",$un,$log_username);
    $stmt->execute();
    $stmt->close();

    $sql = "UPDATE fav_art SET username = ? WHERE username = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss",$un,$log_username);
    $stmt->execute();
    $stmt->close();

    $sql = "UPDATE friends SET user1 = ? WHERE user1 = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss",$un,$log_username);
    $stmt->execute();
    $stmt->close();
    
    $sql = "UPDATE friends SET user2 = ? WHERE user2 = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss",$un,$log_username);
    $stmt->execute();
    $stmt->close();

    $sql = "UPDATE gmembers SET mname = ? WHERE mname = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss",$un,$log_username);
    $stmt->execute();
    $stmt->close();

    $sql = "UPDATE grouppost SET author = ? WHERE author = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss",$un,$log_username);
    $stmt->execute();
    $stmt->close();

    $sql = "UPDATE groups SET creator = ? WHERE creator = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss",$un,$log_username);
    $stmt->execute();
    $stmt->close();

    $sql = "UPDATE group_reply_likes SET username = ? WHERE username = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss",$un,$log_username);
    $stmt->execute();
    $stmt->close();

    $sql = "UPDATE group_status_likes SET username = ? WHERE username = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss",$un,$log_username);
    $stmt->execute();
    $stmt->close();

    $sql = "UPDATE heart_likes SET username = ? WHERE username = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss",$un,$log_username);
    $stmt->execute();
    $stmt->close();

    $sql = "UPDATE invite SET inviter = ? WHERE inviter = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss",$un,$log_username);
    $stmt->execute();
    $stmt->close();

    $sql = "UPDATE notifications SET initiator = ? WHERE initiator = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss",$un,$log_username);
    $stmt->execute();
    $stmt->close();

    $sql = "UPDATE notifications SET username = ? WHERE username = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss",$un,$log_username);
    $stmt->execute();
    $stmt->close();

    $sql = "UPDATE photos SET user = ? WHERE user = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss",$un,$log_username);
    $stmt->execute();
    $stmt->close();

    $sql = "UPDATE photos_status SET author = ? WHERE author = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss",$un,$log_username);
    $stmt->execute();
    $stmt->close();

    $sql = "UPDATE photos_status SET account_name = ? WHERE account_name = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss",$un,$log_username);
    $stmt->execute();
    $stmt->close();

    $sql = "UPDATE photo_reply_likes SET username = ? WHERE username = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss",$un,$log_username);
    $stmt->execute();
    $stmt->close();

    $sql = "UPDATE photo_stat_likes SET username = ? WHERE username = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss",$un,$log_username);
    $stmt->execute();
    $stmt->close();

    $sql = "UPDATE pm SET sender = ? WHERE sender = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss",$un,$log_username);
    $stmt->execute();
    $stmt->close();

    $sql = "UPDATE pm SET receiver = ? WHERE receiver = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss",$un,$log_username);
    $stmt->execute();
    $stmt->close();

    $sql = "UPDATE problem_report SET username = ? WHERE username = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss",$un,$log_username);
    $stmt->execute();
    $stmt->close();

    $sql = "UPDATE reply_likes SET username = ? WHERE username = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss",$un,$log_username);
    $stmt->execute();
    $stmt->close();

    $sql = "UPDATE status SET author = ? WHERE author = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss",$un,$log_username);
    $stmt->execute();
    $stmt->close();

    $sql = "UPDATE status SET account_name = ? WHERE account_name = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss",$un,$log_username);
    $stmt->execute();
    $stmt->close();

    $sql = "UPDATE useroptions SET username = ? WHERE username = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss",$un,$log_username);
    $stmt->execute();
    $stmt->close();

    $sql = "UPDATE users SET username = ? WHERE username = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss",$un,$log_username);
    $stmt->execute();
    $stmt->close();

    $sql = "UPDATE videos SET user = ? WHERE user = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss",$un,$log_username);
    $stmt->execute();
    $stmt->close();

    $sql = "UPDATE video_likes SET username = ? WHERE username = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss",$un,$log_username);
    $stmt->execute();
    $stmt->close();

    $sql = "UPDATE video_reply_likes SET user = ? WHERE user = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss",$un,$log_username);
    $stmt->execute();
    $stmt->close();

    $sql = "UPDATE video_status SET author = ? WHERE author = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss",$un,$log_username);
    $stmt->execute();
    $stmt->close();

    $sql = "UPDATE video_status SET account_name = ? WHERE account_name = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss",$un,$log_username);
    $stmt->execute();
    $stmt->close();

    $sql = "UPDATE video_status_likes SET username = ? WHERE username = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss",$un,$log_username);
    $stmt->execute();
    $stmt->close();

    echo "un_success";
    exit();
  }
?>
<!DOCTYPE html>
<html>
<head>
  <title>Profile Settings - <?php echo $log_username; ?></title>
  <meta charset="utf-8">
  <link rel="icon" type="image/x-icon" href="/images/newfav.png">
  <link rel="stylesheet" type="text/css" href="/style/style.css">
  <link rel="stylesheet" href="/font-awesome-4.7.0/css/font-awesome.min.css">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <script src="/js/jjs.js"></script>
  <script src="/js/main.js" async></script>
  <script src="/js/ajax.js" async></script>
  <script src="/js/create_down.js" async></script>
  <script src="/js/specific/dd.js"></script>
  <script src="/js/specific/settings.js"></script>
  <script src="/js/specific/status_max.js"></script>
  <link rel="manifest" href="/manifest.json">

  <meta name="apple-mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-status-bar-style" content="#282828">
  <meta name="apple-mobile-web-app-title" content="Pearscom">
  <link rel="apple-touch-icon" href="/images/icons/icon-152x152.png">
  <meta name="theme-color" content="#282828" />
  <style type="text/css">
    #pageMiddle_2{
      padding: 30px; font-size: 14px; margin-bottom: 10px !important;
    }

    @media only screen and (max-width: 768px){
      #pageMiddle_2{
        padding: 20px;
      }
    }
  </style>
  <script type="text/javascript">
  </script>
</head>
<body>
  <?php include_once("template_pageTop.php"); ?>
  <div id="pageMiddle_2" style="font-size: 14px;">
    <p style="font-size: 22px; color: #999; margin-top: 0;">Profile Settings</p>

    <div class="collection" id="ccSu">
      <p style="font-size: 18px;" id="signup">General Information</p>
      <img src="/images/alldd.png">
    </div>

    <div class="slideInfo" id="suDD">
      <p style="margin-bottom: 0;">
        &bull; We do not recommend you to change your confidental information regulary because
        it can be quite confusing both for you and your friends.<br />
        &bull; Before you start anything visit our <a href="/help">help</a> page to get more
        essential information about your profile settings! We will not be resposnible for any
        mistakes if you did not read and understood it!<br />
        &bull; Do not use this page for account stealing or abusing with other users&#39;
        personal information!<br />
        &bull; Do not give or change your datas into fake ones! If you gave fake a fake email
        address, country or timezone etc. we will not be responsible for any sort of mistakes
        or misunderstoods around your profile and friends!<br />
        &bull; If you choose deleting your account please note that we will not be able to
        bring back any of your passwords, email addresses, photos, videos or any kind of
        personal datas! Once if you deleted that&#39;s gone!<br />
        &bull; If you change your country, timezone, birthday etc. we will send a notification
        for your friends to keep them up to date about the things happenning around you.
        (Obviously we will not send your email address, passwords or any kind of personal or
        confidental information about you that can break your account security!)<br />
        &bull; For security reasons you will need to give your current password for ANY changes
        you want to make on your profile. This method will reduce the number of broken and
        stolen accounts and data abusings.<br />
        &bull; Changing your gender or even your birthday is (almost) impossible, however we
        are still open for it and you have the chance to change it. If you gave false or not
        up-to-date information about you in the signing up you can correct it here.
        <br /><br />
        Thank you for your understanding and patience!
        <br /><br />
      </p>
    </div>

    <div class="collection" id="ccDa">
      <p style="font-size: 18px;" id="signup">Dark mode</p>
      <img src="/images/alldd.png">
    </div>

    <div class="slideInfo" id="daDD">
      <p>
        In a light poor environment the dark mode can be a lot more confident and relaxing for
        your eyes than the normal daylight mode. Turning on dark mode will result a darker
        theme where you do not need to strengthen your eyes even at night and will be applied
        only to the current browser.<br>
        In that case if you delete browser datas, cookies and history from the current browser
        this feature will be automatically turned to normal mode independently from earlier
        settings.
      </p>
      <br>
      <!-- <b style="font-weight: normal;">Turn off/on dark mode</b>-->
      <div class="shorterdiv">
        <!--
          <label class="switch">
            <input type="checkbox" onchange="toggleDark()" id="tdark">
            <span class="slider round" style="height: 21px !important;"></span>
          </label>
        -->
        <b>Under construction</b>
      </div>
    </div>
      
    <div class="collection" id="ccUs">
      <p style="font-size: 18px;" id="signup">Username</p>
      <img src="/images/alldd.png">
    </div>

    <div class="slideInfo" id="usDD">
      <form id="disableit" name="disableit" onsubmit="return false;">
        <p>
          If you changed your real name or you just want to change your username on Pearscom
          you can do it in this section. Please note that your username has to be at least 5
          and it can be maximum 100 characters long. It can contain any characters and signs,
          however we highy encourage everyone to give their real- (John Williams), nick-
          (Willy) or fantasy name (Nutella Pancake) which is recognizable for their friends and
          other people.<br><br><b>Important:</b> when you successfully changed your username
          you will be logged out and you will need to log in again!
        </p>
        <div class="pplongbtn main_btn_fill widen" id="unlongbtn">Change username</div>
        <div class="settingsall styleform" style="margin-top: 10px;">
          <input type="password" name="unpass" id="unpass"
            onblur="clientFbOne('un_status','unpass','cpasscheck')" maxlength="255"
            placeholder="Current password">
          <span class="signupStats" style="right: 10px; margin-top: -26px;" id="un_status">
          </span>

          <input type="text" id="un_name" onblur="clientFbOne('un_ustatus', 'un_name',
            'cusern')" placeholder="New username">

          <span id="un_ustatus" class="signupStats" style="right: 10px; margin-top: -26px;">
          </span>
            
          <br>
          <button id="confun" class="pplongbtn main_btn_fill settBtns"
            onclick="confirmChange('un_name', 'unpass', 'fullunstat', 'fullunstat',
            'un_success', null, 'cun', 'pwd')">
            Confirm
          </button>
          <div id="fullunstat" style="text-align: center; margin-top: 10px;"></div>
        </div>
      </form>
    </div>

    <div class="collection" id="ccPw">
      <p style="font-size: 18px;" id="signup">Password</p>
      <img src="/images/alldd.png">
    </div>

    <div class="slideInfo" id="pwDD">
      <p>
        If you wish to change your current password to a stronger one or for any reason this is
        the right section. For security reasons we have to look up for your current password
        and the new one has to contain at least 1 uppercase, 1 lowercase letter and a number.
        This will make your password a lot more stronger and
        unguessable.<br><br><b>Important:</b> when you successfully changed your password you
        will be logged out and you will need to log in again with your new password!
      </p>
      <div class="pplongbtn main_btn_fill widen" id="chplongbtn">Change password</div>
        <div class="settingsall styleform" style="margin-top: 10px;">
          <input type="password" name="cpass" id="cpass" 
            onblur="clientFbOne('cpassstatus','cpass','cpasscheck')" maxlength="255"
            placeholder="Current password">

            <span id="cpassstatus" class="signupStats"
              style="right: 10px; margin-top: -26px;"></span>

            <input type="password" name="npass" id="npass" onblur="checkFbTwo(
              'npass', 'cnpass', 'npassstatus', 'npasscheck', 'cp')"
              maxlength="255" placeholder="New password">

            <span id="npassstatus" class="signupStats"
              style="right: 10px; margin-top: -26px;"></span>

            <input type="password" name="cnpass" id="cnpass" onblur="checkFbTwo('cnpass',
              'npass', 'cnpassstatus', 'cnp', 'np')"
              maxlength="255" placeholder="Confirm password">

            <span id="cnpassstatus" class="signupStats"
              style="right: 10px; margin-top: -26px;"></span>
            
            <br />
            <button id="confirmpass" class="pplongbtn main_btn_fill settBtns"
              onclick="changePass()">Confirm</button>
            <div id="pass_status" style="text-align: center; margin-top: 10px;"></div>
          </div>
        </div>

        <div class="collection" id="ccCy">
          <p style="font-size: 18px;" id="signup">Country</p>
          <img src="/images/alldd.png">
        </div>

        <div class="slideInfo" id="cyDD">
          <p>
            If you moved to a completely another country you can change it here. Please be
            careful responsible! Changing your location to a fake one will mess up your friends
            and our geolocation system will get back the proper datas. Nonetheless we want you
            to give your current and real country to make sure you are the right user.
          </p>
          <div class="pplongbtn main_btn_fill widen" id="countrylbtn">Change country</div>
          <div class="settingsall styleform" style="margin-top: 10px;">
            <input type="password" name="curpass" id="curpass"
              onblur="clientFbOne('curqmstatus','curpass','cpasscheck')" class="fitSmall"
              maxlength="255" placeholder="Current password">

            <span id="curqmstatus" class="signupStats"
              style="right: 10px; margin-top: -26px;"></span>

          <select id="ncountry" onblur="clientFbOne('ncontstatus', 'ncountry',
            'ncountry')"
            class="fitSmall">
            <option value="" selected="true" disabled="true">Change country</option>
            <?php require_once 'template_country_list.php'; ?>
          </select>

          <span id="ncontstatus" class="signupStats" style="right: 10px; margin-top: -26px;">
          </span>

          <div class="clear"></div>
          <br />
          <button id="confcountry" class="pplongbtn main_btn_fill settBtns"
            onclick="confirmChange('ncountry', 'curpass', 'country_span',
            'confcountry', 'country_success', 'country_span', 'confc', 'pwd')">Confirm</button>
          <div id="country_span" style="text-align: center; margin-top: 10px;"></div>
        </div>
      </div>

      <div class="collection" id="ccEm">
        <p style="font-size: 18px;" id="signup">Email address</p>
        <img src="/images/alldd.png">
      </div>

      <div class="slideInfo" id="emDD">
        <p>
          You have the opportunity to change your email address, too. If your current email
          address is not comfortable or reliable for you we recommend to change it to a valid
          and secure email that you check every day! After you changed your email every sort of
          notifications, password claims or activation messages will be sent to that new
          one.<br /><br><b>Important: </b>when you successfully changed your email you will be
          logged out and you will need to log in again with your new email address!
        </p>
        <div class="pplongbtn main_btn_fill widen" id="emaillongbtn">Change email address</div>
          <div class="settingsall styleform" style="margin-top: 10px;">
            <input type="password" name="curpassem" id="curpassem"
              onblur="clientFbOne('curqmstatusem','curpassem','cpasscheck')" maxlength="255"
              placeholder="Current password">

            <span id="curqmstatusem" class="signupStats"
              style="right: 10px; margin-top: -26px;"></span>

            <input type="text" name="nemail" id="nemail" maxlength="255"
              onblur="clientFbOne('nemailastatus', 'nemail', 'nemail')"
              placeholder="New email address">

            <span id="nemailastatus" class="signupStats"
              style="right: 10px; margin-top: -26px;"></span>

          <br />
          <button id="confemail" class="pplongbtn main_btn_fill settBtns"
            onclick="confirmChange('nemail', 'curpassem', 'email_status',
            'confemail', 'email_success', null, 'email', 'pass')">Confirm</button>
          <div id="email_status" style="text-align: center; margin-top: 10px;"></div>
        </div>
      </div>

      <div class="collection" id="ccGe">
        <p style="font-size: 18px;" id="signup">Gender</p>
        <img src="/images/alldd.png">
      </div>

      <div class="slideInfo" id="geDD">
        <p>
          Gender chaning can sounds quite strange for a lot of people, however we do NOT
          condemn or discriminate against anyone, therefore you can independently change it.
          Please note that your friends and followers will get a notification about the change
          in order to keep them up-to-date with you.
        </p>
        <div class="pplongbtn main_btn_fill widen" id="cglongbtn">Change gender</div>
          <div class="settingsall styleform" style="margin-top: 10px;">
            <input type="password" name="cgpass" id="cgpass"
              onblur="clientFbOne('cg_status','cgpass','cpasscheck')" maxlength="255"
              class="fitSmall" placeholder="Current password">

            <span id="cg_status" class="signupStats"
              style="right: 10px; margin-top: -26px;"></span>

            <select id="cg_gender" onblur="clientFbOne('cg_status', 'cg_gender', 'cgend')"
              class="fitSmall">
              <option value="" disabled="true" selected="true">Change gender</option>
              <option value="Male">Male</option>
              <option value="Female">Female</option>
            </select>

            <span id="cg_gstatus" class="signupStats" style="right: 10px; margin-top: -26px;">
            </span>
            <div class="clear"></div>
          <br>

          <button id="confcg" class="pplongbtn main_btn_fill settBtns"
            onclick="confirmChange('cg_gender', 'cgpass', 'fullcgstat',
            'cg_status', 'gender_success', null, 'gender', 'pwd')">Confirm</button>
          <div id="fullcgstat" style="text-align: center; margin-top: 10px;"></div>
        </div>
      </div>

      <div class="collection" id="ccTz">
        <p style="font-size: 18px;" id="signup">Timezone</p>
        <img src="/images/alldd.png">
      </div>

      <div class="slideInfo" id="tzDD">
        <p>
          In connection with country changing, if you moved to another country you can also
          select the local timezone there. Please give a valid and correct timezone in order to
          provide reliable and local time &amp; dates for you. Otherwise the whole time-system
          will be messed up together with your friends and you!
        </p>
        <div class="pplongbtn main_btn_fill widen" id="tzlongbtn">Change timezone</div>
        <div class="settingsall styleform" style="margin-top: 10px;">
          <input type="password" name="tzpass" id="tzpass"
            onblur="clientFbOne('tz_status','tzpass','cpasscheck')" maxlength="255"
            class="fitSmall"
            placeholder="Current password">

            <span id="tz_status" class="signupStats"
              style="right: 10px; margin-top: -26px;"></span>

            <select id="tz_zone" onblur="clientFbOne('tz_status', 'tz_zone', 'timezone')"
              class="fitSmall">
              <option value="" selected="true" disabled="true">Change timezone</option>
              <?php require_once 'template_timezone_list.php'; ?>
            </select>

            <span id="tz_tstatus" class="signupStats"
              style="right: 10px; margin-top: -26px;"></span>
            <div class="clear"></div>
          <br>
  
          <button id="conftz" class="main_btn_fill pplongbtn settBtns" onclick="confirmChange(
            'tz_zone', 'tzpass', 'fulltzstat', 'tz_tstatus', 'tz_success', null, 'tz',
            'pwd')">
            Confirm
          </button>
          <div id="fulltzstat" style="text-align: center; margin-top: 10px;"></div>
        </div>
      </div>

      <div class="collection" id="ccBd">
        <p style="font-size: 18px;" id="signup">Birthday</p>
        <img src="/images/alldd.png">
      </div>

      <div class="slideInfo" id="bdDD">
        <p>
          Since it is impossible to change your birthday - exept if you are a time traveller -
          we are still open for everyone, therefore we provided a birthday changing feature for
          time travellers and those people who gave a fake birthday when they signed up.
        </p>
        <div class="pplongbtn main_btn_fill widen" id="bdlongbtn">Change birthday</div>
        <div class="settingsall styleform" style="margin-top: 10px;">
          <input type="password" name="bdpass" id="bdpass" 
            onblur="clientFbOne('bd_status','bdpass','cpasscheck')" maxlength="255" 
            placeholder="Current password">

            <span id="bd_status" class="signupStats" 
              style="right: 10px; margin-top: -26px;"></span>

            <input type="date" id="bd_day" onblur="clientFbOne('bd_status', 'bd_day',
              'cbirthd')" placeholder="1988-01-01">

            <span id="bd_bstatus" class="signupStats" 
              style="right: 10px; margin-top: -26px;"></span>
            <br>
            
            <button id="confbd" class="main_btn_fill pplongbtn settBtns" 
              onclick="confirmChange('bd_day', 'bdpass', 'fullbdqm',
                'bd_status', 'bd_success', null, 'bd', 'pwd')">Confirm</button>
          <div id="fullbdqm" style="text-align: center; margin-top: 10px;"></div>
        </div>
      </div>

      <div class="collection" id="ccDe">
        <p style="font-size: 18px;" id="signup">Delete account</p>
        <img src="/images/alldd.png">
      </div>

      <div class="slideInfo" id="deDD">
        <p>
          We do not recommend for anyone to delete their own account! All of your personal
          photos, videos, datas and everything else that your did on Pearscom will be gone! We
          will not be able no bring back your account, do it on your own risk and we will not
          take any resposibility for any mistakes!
        </p>
        <div class="pplongbtn main_btn_fill widen" id="dalongbtn">Delete account</div>
        <div class="settingsall styleform" style="margin-top: 10px;">
          <input type="password" name="dacpass" id="dacpass"
            onblur="clientFbOne('dacpass_status','dacpass','cpasscheck')" maxlength="255"
            placeholder="Current password">

            <span id="dacpass_status" class="signupStats" 
              style="right: 10px; margin-top: -26px;"></span>

            <textarea name="whyda" id="whyda" onblur="clientFbOne('whydaqmstatus',
              'whyda', 'whyda')"
              onkeyup="statusMax(this,1000)"
              placeholder="I was not satisfied with the services... (optional)"></textarea>

            <span id="whydaqmstatus" class="signupStats"
              style="right: 10px; margin-top: -26px;"></span>
          <br>
          <button id="confda" class="main_btn_fill pplongbtn settBtns"
            onclick="confirmChange('dacpass', 'whyda', 'delete_status',
            'delete_status', 'delete_success', null, 'dacpass', 'whyda_')">Confirm</button>
          <div id="delete_status" style="text-align: center; margin-top: 10px;"></div>
        </div>
      </div>
    </div>
  <?php require_once 'template_pageBottom.php'; ?>
  <script type="text/javascript">
    doDD("ccSu", "suDD");
    doDD("ccDa", "daDD");
    doDD("ccUs", "usDD");
    doDD("ccPw", "pwDD");
    doDD("ccCy", "cyDD");
    doDD("ccEm", "emDD");
    doDD("ccGe", "geDD");
    doDD("ccTz", "tzDD");
    doDD("ccBd", "bdDD");
    doDD("ccDe", "deDD");
  </script>
</body>
</html>
