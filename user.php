<?php
    require_once 'php_includes/check_login_statues.php';
    require_once 'timeelapsedstring.php';
    require_once 'safe_encrypt.php';
    require_once 'durc.php';
    require_once 'phpmobc.php';
    require_once 'headers.php';
    require_once 'ccov.php';
	require_once 'php_includes/dist.php';    
   
    $ismobile = mobc();

    // Initialize any variables that the page might echo
    $u = "";
    $sex = "Male";
    $userlevel = "";
    $country = "";
    $joindate = "";
    $lastsession = "";
    $profile_pic = "";
    $profile_pic_btn = "";
    $avatar_form = "";
    $background_form = "";
    $one = "1";
    $memberfor = "";
    $lastlin = "";
    $comma_sep = "";
    $c = "c";
    $a = "a";
    $b = "b";
    $gender_icon = "";
    $max = 14;
    $lat = "";
    $lon = "";
    $hc = "";
    // Make sure the _GET username is set and sanitize it
    if(isset($_GET["u"])){
        $u = mysqli_real_escape_string($conn, $_GET["u"]);
    }else{
        header('Location: /index');
        exit();
    }
    
    $wart = "";
    if(isset($_GET["wart"])){
        $wart = mysqli_real_escape_string($conn, $_GET["wart"]);
        if($wart != "yes"){
            $wart = "";
        }else{
            $wart = '<script>writeArticle();</script>';
        }
    }
    
    $pmw = "";
    if(isset($_GET["pm"])){
        $pmw = mysqli_real_escape_string($conn, $_GET["pm"]);
        if($pmw != "write"){
            $pmw = "";
        }else{
            $pmw = '<script>showForm();</script>';
        }
    }

    // Select the member from the users table
    $sql = "SELECT * FROM users WHERE username=? AND activated=? LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss",$u,$one);
    $stmt->execute();
    $result = $stmt->get_result();

    // Now make sure the user exists in the table
    if($result->num_rows < 1){
        header('location: /usernotexist');
        exit();
    }
    
    $isBlock = false;
    if($user_ok == true){
        $block_check = "SELECT id FROM blockedusers WHERE blockee=? AND blocker=?";
        $stmt = $conn->prepare($block_check);
        $stmt->bind_param("ss",$log_username,$u);
        $stmt->execute();
        $stmt->store_result();
        $stmt->fetch();
        $numrows = $stmt->num_rows;
    if($numrows > 0){
        $isBlock = true;
    }
    $stmt->close();
    }

    // Check to see if the viewer is the account owner
    $isOwner = "No";
    if($u == $log_username && $user_ok == true){
        $isOwner = "Yes";
        $profile_pic_btn = '<span id="blackbb"><img src="/images/cac.png" onclick="return false;" id="ca" onmousedown="toggleElement(\'avatar_form\')" width="20" height="20"></span>';
        $avatar_form  = '<form id="avatar_form" enctype="multipart/form-data" method="post" action="/php_parsers/photo_system.php">';
        $avatar_form .=   '<div id="add_marg_mob">';
        $avatar_form .=   '<input type="file" name="avatar" id="file" class="inputfile ppChoose" required accept="image/*">';
        $avatar_form .=   '<label for="file" style="font-size: 12px;">Choose a file</label>';
        $avatar_form .=   '<p><input type="submit" value="Upload" class="main_btn_fill fixRed" style="font-size: 12px;"></p>';
        $avatar_form .=   '</div>';
        $avatar_form .= '</form>';

        // Background form
        $background_form  = '<form id="background_form" style="text-align: center;" enctype="multipart/form-data" method="post" action="/php_parsers/photo_system.php">';
        $background_form .=   '<input type="file" name="background" id="bfile" class="inputfile" onchange="showfile()" required accept="image/*">';
        $background_form .=   '<label for="bfile" style="margin-right: 10px;">Choose a file</label>';
        $background_form .=   '<input type="submit" class="main_btn_fill fixRed" value="Upload Background" id="fixFlow"><p style="color: #999; font-size: 14px;" class="txtc"><b>Note: </b>the allowed file extensions are: jpeg, jpg, png and gif and the maximum file size limit is 5MB</p>';
        $background_form .= '</form>';
    }

    // Fetch the user row from the query above
    while($row = $result->fetch_assoc()){
        $profile_id = $row["id"];
        $gender = $row["gender"];
        $country = $row["country"];
        $userlevel = $row["userlevel"];
        $signup = $row["signup"];
        $avatar = $row["avatar"];
        $lastlogin = $row["lastlogin"];
        $lastsession = strftime("%b %d, %Y", strtotime($lastlogin));
        // Get the latlon as user A
        $uBlatlon = $row["latlon"];
        $bdor = $row["bday"];
        $bdate = mb_substr($row["bday"], 5, 9, "utf-8");
        $birthday_ = $row["bday"];
        $birthday = strftime("%b %d, %Y", strtotime($birthday_));
        $birthday_year = mb_substr($row["bday"], 0, 4, "utf-8");
        $onlineornot = $row["online"];
        $joindate = strftime("%b %d, %Y", strtotime($signup));
        $memberfor = time_elapsed_string($signup);
        if($onlineornot == "yes"){
            $lastlin = "online";
        }else{
            $lastlin = time_elapsed_string($lastlogin);
        }
    }
    $is_birthday = "no";
    $today_is = date('m-d');
    if($today_is == $bdate){
        $is_birthday = "yes";
    }
    $leap = date("L");
    if($leap == '0' && $today_is == "02-28" && $bdate == '02-29'){
        $is_birthday = "yes";
    }

    if($gender == "f"){
        $sex = "Female";
    }
    if($gender == "f"){
        $gender_icon = '&nbsp;<img src="/images/female.png" width="15" height="15">';
    }else{
        $gender_icon = '&nbsp;<img src="/images/male.png" width="15" height="15">';
    }
    $profile_pic = '/user/'.$u.'/'.$avatar.'';

    if($avatar == NULL){
        $avdef = "avdef.png";
        $profile_pic = '/images/avdef.png';
    }
    
    // Get logged in user's geolocation
    $sql = "SELECT lat, lon FROM users WHERE username = ? LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s",$log_username);
    $stmt->execute();
    $res = $stmt->get_result();
    while($row = $res->fetch_assoc()){
        $lat = $row["lat"];
        $lon = $row["lon"];
    }
    $stmt->close();
    
    $blat = "";
    $blon = "";
    $sql = "SELECT lat, lon FROM users WHERE username = ? LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s",$u);
    $stmt->execute();
    $res = $stmt->get_result();
    while($row = $res->fetch_assoc()){
        $blat = $row["lat"];
        $blon = $row["lon"];
    }
    $stmt->close();
    
    $distBetween = vincentyGreatCircleDistance($lat, $lon, $blat, $blon);

    $current_year = date("Y");
    $age = floor((time() - strtotime($bdor)) / 31556926);
    $agestring = "";
    if($age < 18){
        $agestring = ' (underage)';
    }else{
        $agestring = ' (adult)';
    }
    /*// Get the latlon as user B
    $uBlatlon = "";
    if(isset($log_username)){
        $sql = "SELECT latlon FROM users WHERE username=? LIMIT 1";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s",$log_username);
        $stmt->execute();
        $result = $stmt->get_result();
        while($row = $result->fetch_assoc()){
            $uBlatlon = $row["latlon"];
        }

        $stmt->close();
    }*/
    /*
    $uBlatlon = "";
    if(isset($log_username)){
        $result = mysqli_query($conn, "SELECT latlon FROM users WHERE username='$log_username' LIMIT 1");
        while($row = mysqli_fetch_row($result)){
            $uBlatlon = $row[0];
        }
    }
    */

    if($userlevel == "a"){
        $userlevel = "Verified";
    }else if($userlevel == "b"){
        $userlevel = "Not Verified";
    }else{
        $userlevel = "Unauthorized";
    }
    
    $grbtn = "";
    if($isOwner == "Yes"){
        $grbtn = '<button onclick="hreftogr()" id="vupload">View Groups <img src="/images/vgr.png" class="notfimg" style="margin-bottom: -2px;"></button>';
    }
?>
<?php
    $isFriend = false;
    $ownerBlockViewer = false;
    $viewerBlockOwner = false;
    if($u != $log_username && $user_ok == true){
        $friend_check = "SELECT id FROM friends WHERE user1=? AND user2=? AND accepted=? OR user1=? AND user2=? AND accepted=? LIMIT 1";
        $stmt = $conn->prepare($friend_check);
        $stmt->bind_param("ssssss",$log_username,$u,$one,$u,$log_username,$one);
        $stmt->execute();
        $stmt->store_result();
        $stmt->fetch();
        $numrows = $stmt->num_rows;
        if($numrows > 0){
            $isFriend = true;
        }
        $stmt->close();

        $block_check1 = "SELECT id FROM blockedusers WHERE blocker=? AND blockee=? LIMIT 1";
        $stmt = $conn->prepare($block_check1);
        $stmt->bind_param("ss",$u,$log_username);
        $stmt->execute();
        $stmt->store_result();
        $stmt->fetch();
        $numrows2 = $stmt->num_rows;
        if($numrows2 > 0){
            $ownerBlockViewer = true;
        }
        $stmt->close();

        $block_check2 = "SELECT id FROM blockedusers WHERE blocker=? AND blockee=? LIMIT 1";
        $stmt = $conn->prepare($block_check2);
        $stmt->bind_param("ss",$log_username,$u);
        $stmt->execute();
        $stmt->store_result();
        $stmt->fetch();
        $numrows3 = $stmt->num_rows;
        if($numrows3 > 0){
            $viewerBlockOwner = true;
        }
        $stmt->close();
    }
?>
<?php
    $ftext = "";
    if(isset($_SESSION["username"]) && $_SESSION["username"] != ""){
        $ftext = '';
    }else{
        $ftext = '';
    }
    $friend_button = '<button style="opacity: 0.6; cursor: not-allowed;" class="main_btn_fill fixRed">Request as friend</button>'.$ftext.'';
    $block_button = '<button style="opacity: 0.6; cursor: not-allowed;" class="main_btn_fill fixRed">Block User</button>';
    $fbWatch = '';

    // LOGIC FOR FRIEND BUTTON
    if($isFriend == true){
        $friend_button = '<button onclick="friendToggle(\'unfriend\',\''.$u.'\',\'friendBtn\')" class="main_btn_fill fixRed">Unfriend</button>';
    } else if($user_ok == true && $u != $log_username && $ownerBlockViewer == false){
        $friend_button = '<button onclick="friendToggle(\'friend\',\''.$u.'\',\'friendBtn\')" class="main_btn_fill fixRed">Request as friend</button>';
        $fbWatch = '<button onclick="friendToggle(\'friend\',\''.$u.'\',\'friendBtn\')" id="vupload" class="main_btn_fill fixRed">Request as friend</button>';
    }

    $sql = "SELECT accepted FROM friends WHERE user1 = ? AND user2 = ? OR user1 = ? AND user2 = ? LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssss",$log_username,$u,$u,$log_username);
    $stmt->execute();
    $stmt->bind_result($zeroone);
    $stmt->fetch();
    $stmt->close();
    if($zeroone == "0"){
        $friend_button = '<p style="font-size: 14px; color: #999; margin: 0;">Friend request is waiting for approval</p>';
        $fbWatch = '';
    }
    $stmt->close();

    // LOGIC FOR BLOCK BUTTON
    if($viewerBlockOwner == true){
        $block_button = '<button onclick="blockToggle(\'unblock\',\''.$u.'\',\'blockBtn\')" class="main_btn_fill fixRed">Unblock user</button>';
    } else if($user_ok == true && $u != $log_username){
        $block_button = '<button onclick="blockToggle(\'block\',\''.$u.'\',\'blockBtn\')" class="main_btn_fill fixRed">Block user</button>';
    }
?>
<?php
    $isFollow = false;
    if($u != $log_username && $user_ok == true){
        $follow_check = "SELECT id FROM follow WHERE follower=? AND following=? LIMIT 1";
        $stmt = $conn->prepare($follow_check);
        $stmt->bind_param("ss",$log_username,$u);
        $stmt->execute();
        $stmt->store_result();
        $stmt->fetch();
        $numrows = $stmt->num_rows;
    if($numrows > 0){
            $isFollow = true;
        }
        $stmt->close();
    }
?>
<?php
    $follow_button = "";
    $isFollowOrNot = "";
    $gs = "him";
    // Set $gender_sex if the user is male or female
    $sql = "SELECT * FROM users WHERE username=? LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s",$u);
    $stmt->execute();
    $result = $stmt->get_result();
    while($row = $result->fetch_assoc()){
        $gender_sex = $row["gender"];
    }
    $stmt->close();
    // LOGIC FOR FOLLOW BUTTON
    if($isFollow == true){
        $follow_button = '<button class="main_btn_fill fixRed" onclick="followToggle(\'unfollow\',\''.$u.'\',\'followBtn\', \'isFol\')">Unfollow</button>';
        if($gender_sex == "f"){
            $gs = "her";
        }

        $isFollowOrNot = "<p style='color: #999;' id='isFol'>You're following ".$gs."</p>";
    }else{
        $follow_button = '<button class="main_btn_fill fixRed" onclick="followToggle(\'follow\',\''.$u.'\',\'followBtn\', \'isFol\')">Follow</button>';
    }
?>
<?php
    $friendsHTML = '';
    $friends_view_all_link = '';
    $bdfusres = "";
    $all_friends = array();
    $allfusr = array();
    $sql = "SELECT COUNT(id) FROM friends WHERE user1=? AND accepted=? OR user2=? AND accepted=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssss",$u,$one,$u,$one);
    $stmt->execute();
    $stmt->bind_result($friend_count);
    $stmt->fetch();
    $stmt->close();

    $yes = "yes";
    $sql = "SELECT COUNT(f.id) FROM friends AS f LEFT JOIN users AS u ON u.username = f.user1 WHERE (f.user1=? AND f.accepted=?) OR (f.user2=? AND f.accepted=?) AND u.online = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssss",$u,$one,$u,$one,$yes);
    $stmt->execute();
    $stmt->bind_result($online_count);
    $stmt->fetch();

    $stmt->close();
    if($friend_count < 1){
        if($isOwner == "Yes"){
            $friendsHTML = '<p style="color: #999;" class="txtc">You have no friends yet</p>';
        }else{
            $friendsHTML = '<p style="color: #999;" class="txtc">'.$u.' has no friends yet</p>';
        }
    } else {
        $allf = array();
        $sql = "SELECT user1, user2 FROM friends WHERE user2 = ? AND accepted=? ORDER BY RAND() LIMIT $max";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss",$u,$one);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            array_push($all_friends, $row["user1"]);
            array_push($allfusr, $row["user1"]);
        }
        $stmt->close();

        $sql = "SELECT user1, user2 FROM friends WHERE user1 = ? AND accepted=? ORDER BY RAND() LIMIT $max";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss",$u,$one);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            array_push($all_friends, $row["user2"]);
            array_push($allfusr, $row["user2"]);
        }
        $stmt->close();
        
        $friendArrayCount = count($all_friends);
        if($friendArrayCount > $max){
            array_splice($all_friends, $max);
        }
        if($friend_count > $max){
            $friends_view_all_link = '<a href="/view_friends/'.$u.'">View all</a>';
        }
        $orLogic = '';
        foreach($all_friends as $key => $user){
            $orLogic .= "username='$user' OR ";
        }
        $nocomma = array();
        $orLogic = chop($orLogic, "OR ");
        $sql = "SELECT * FROM users WHERE $orLogic";
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        $result3 = $stmt->get_result();
        while($row = $result3->fetch_assoc()) {
            $friend_username = $row["username"];
            $friend_avatar = $row["avatar"];
            $fuco = $row["country"];
            $ison = $row["online"];
            $flat = $row["lat"];
            $flon = $row["lon"];
            $dist = vincentyGreatCircleDistance($lat, $lon, $flat, $flon);
            $dist = round($dist * 1.609344);
            $isonimg = '';
            if($ison == "yes"){
                $isonimg = "<img src='/images/wgreen.png' width='12' height='12'>";
            }else{
                $isonimg = "<img src='/images/wgrey.png' width='12' height='12'>";
            }
            if($friend_avatar != ""){
                $friend_pic = '/user/'.$friend_username.'/'.$friend_avatar.'';
            } else {
                $friend_pic = '/images/avdef.png';
            }
            $funames = $friend_username;
            if(strlen($funames) > 20){
                $funames = mb_substr($funames, 0, 16, "utf-8");
                $funames .= " ...";
            }
            if(strlen($fuco) > 20){
                $fuco = mb_substr($fuco, 0, 16, "utf-8");
                $fuco .= " ...";
            }
            $sql = "SELECT COUNT(id) FROM friends WHERE (user1 = ? OR user2 = ?) AND accepted = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sss",$friend_username,$friend_username,$one);
            $stmt->execute();
            $stmt->bind_result($numoffs);
            $stmt->fetch();
            $stmt->close();
            $friendsHTML .= '<a href="/user/'.$friend_username.'/"><div data-src=\''.$friend_pic.'\' style="background-repeat: no-repeat; background-size: cover; background-position: center; width: 50px; height: 50px; display: inline-block;" class="friendpics lazy-bg"></div><div class="infousrdiv"><div data-src=\''.$friend_pic.'\' style="background-repeat: no-repeat; background-size: cover; background-position: center; width: 60px; height: 60px; display: inline-block; float: left; border-radius: 50%;" class="lazy-bg"></div><span style="float: left; margin-left: 2px;"><u>'.$funames.'</u>&nbsp;'.$isonimg.'<br><img src="/images/pcountry.png" width="12" height="12">&nbsp;'.$fuco.'<br><img src="/images/udist.png" width="12" height="12">&nbsp;Distance: '.$dist.' km<br><img src="/images/fus.png" width="12" height="12">&nbsp;Friends: '.$numoffs.'</span></div></a>';
            $friends_string = '<a href="/user/'.$friend_username.'/">'.$friend_username.'</a>';
            array_push($nocomma, $friends_string);
            $comma_sep = implode(", ",$nocomma);
            if(count($nocomma) == 1){
                $comma_sep = $nocomma[0];
            }
        }
        $stmt->close();
    }

    // Followers count
    $followersHTML = "";
    $sql = "SELECT COUNT(id) FROM follow WHERE following=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s",$u);
    $stmt->execute();
    $stmt->bind_result($follower_count);
    $stmt->fetch();
    if($follower_count < 1){
        $followersHTML = '<b>'.$u." has no followers yet.</b>";
    }

    $stmt->close();

    $sql = "SELECT COUNT(id) FROM follow WHERE follower=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s",$u);
    $stmt->execute();
    $stmt->bind_result($following_count);
    $stmt->fetch();
    $stmt->close();

    $fc_sep = "";
    $fnocom = array();
    $sql = "SELECT * FROM follow WHERE following=? LIMIT 15";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s",$u);
    $stmt->execute();
    $result = $stmt->get_result();
    while($row = $result->fetch_assoc()){
        $fwer = $row["follower"];
        $fwer_string = '<a href="/user/'.$fwer.'/">'.$fwer.'</a>';
        array_push($fnocom, $fwer_string);
    }
    $fc_sep = implode(", ", $fnocom);

    if($result->num_rows > 15){
        $fc_sep .= ' ...';
    }

    $stmt->close();
    // Followers profile pic
    $following_div = "";
    $sql = "SELECT u.*, f.follower
            FROM users AS u
            LEFT JOIN follow AS f ON u.username = f.follower
            WHERE f.following = ? ORDER BY RAND() LIMIT 15";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s",$u);
    $stmt->execute();
    $result = $stmt->get_result();
    while($row = $result->fetch_assoc()){
        $flw_pic = $row["avatar"];
        $fname = $row["follower"];
        $fuco = $row["country"];
        $ison = $row["online"];
        $flat = $row["lat"];
        $flon = $row["lon"];
        $dist = vincentyGreatCircleDistance($lat, $lon, $flat, $flon);
        $isonimg = '';
        if($ison == "yes"){
            $isonimg = "<img src='/images/wgreen.png' width='12' height='12'>";
        }else{
            $isonimg = "<img src='/images/wgrey.png' width='12' height='12'>";
        }
        if($friend_avatar != ""){
            $friend_pic = '/user/'.$friend_username.'/'.$friend_avatar.'';
        } else {
            $friend_pic = '/images/avdef.png';
        }
        $funames = $fname;
        if(strlen($funames) > 20){
            $funames = mb_substr($funames, 0, 16, "utf-8");
            $funames .= " ...";
        }
        if(strlen($fuco) > 20){
            $fuco = mb_substr($fuco, 0, 16, "utf-8");
            $fuco .= " ...";
        }
        $sql = "SELECT COUNT(id) FROM friends WHERE (user1 = ? OR user2 = ?) AND accepted = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sss",$friend_username,$friend_username,$one);
        $stmt->execute();
        $stmt->bind_result($numoffs);
        $stmt->fetch();
        $stmt->close();
        if($flw_pic == NULL || $flw_pic == ""){
            $pp = '/images/avdef.png';
        }else{
            $pp = '/user/'.$fname.'/'.$flw_pic;
        }
        $following_div .= '<a href="/user/'.$fname.'/"><div data-src=\''.$pp.'\' style="background-repeat: no-repeat; background-size: cover; background-position: center; width: 50px; height: 50px; display: inline-block;" class="flowmob lazy-bg"></div><div class="infofoldiv"><div data-src=\''.$pp.'\' style="background-repeat: no-repeat; background-size: cover; background-position: center; border-radius: 50%; width: 60px; height: 60px; float: left; display: inline-block;" class="flowmob lazy-bg"></div><span style="float: left; margin-left: 2px;"><u>'.$funames.'</u>&nbsp;'.$isonimg.'<br><img src="/images/pcountry.png" width="12" height="12">&nbsp;'.$fuco.'<br><img src="/images/udist.png" width="12" height="12">&nbsp;Distance: '.$dist.' km<br><img src="/images/fus.png" width="12" height="12">&nbsp;Friends: '.$numoffs.'</span></div></a>';
    }

    $stmt->close();

    // Following profile pic
    $other_div = "";
    $sql = "SELECT u.*, f.* 
            FROM users AS u
            LEFT JOIN follow AS f ON u.username = f.following
            WHERE f.follower = ? ORDER BY RAND() LIMIT 15";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s",$u);
    $stmt->execute();
    $result = $stmt->get_result();
    while($row = $result->fetch_assoc()){
        $flw_pic = $row["avatar"];
        $fname = $row["username"];
        $fuco = $row["country"];
        $ison = $row["online"];
        $flat = $row["lat"];
        $flon = $row["lon"];
        $dist = vincentyGreatCircleDistance($lat, $lon, $flat, $flon);
        $isonimg = '';
        if($ison == "yes"){
            $isonimg = "<img src='/images/wgreen.png' width='12' height='12'>";
        }else{
            $isonimg = "<img src='/images/wgrey.png' width='12' height='12'>";
        }
        if($friend_avatar != ""){
            $friend_pic = '/user/'.$friend_username.'/'.$friend_avatar.'';
        } else {
            $friend_pic = '/images/avdef.png';
        }
        $funames = $friend_username;
        if(strlen($funames) > 20){
            $funames = mb_substr($funames, 0, 16, "utf-8");
            $funames .= " ...";
        }
        if(strlen($fuco) > 20){
            $fuco = mb_substr($fuco, 0, 16, "utf-8");
            $fuco .= " ...";
        }
        $sql = "SELECT COUNT(id) FROM friends WHERE (user1 = ? OR user2 = ?) AND accepted = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sss",$friend_username,$friend_username,$one);
        $stmt->execute();
        $stmt->bind_result($numoffs);
        $stmt->fetch();
        $stmt->close();
        if($flw_pic == NULL){
            $pp = '/images/avdef.png';
        }else{
            $pp = '/user/'.$fname.'/'.$flw_pic;
        }
        if(strlen($fname) > 18){
            $fname = mb_substr($fname, 0, 14, "utf-8");
            $fname .= " ...";
            
        }
        $other_div .= '<a href="/user/'.$fname.'/"><div data-src=\''.$pp.'\' style="background-repeat: no-repeat; background-size: cover; background-position: center; width: 50px; height: 50px; display: inline-block;" class="flowmob lazy-bg"></div><div class="infofoldiv"><div data-src=\''.$pp.'\' style="background-repeat: no-repeat; background-size: cover; background-position: center; border-radius: 50%; width: 60px; height: 60px; float: left; display: inline-block;" class="flowmob lazy-bg"></div><span style="float: left; margin-left: 2px;"><u>'.$fname.'</u>&nbsp;'.$isonimg.'<br><img src="/images/pcountry.png" width="12" height="12">&nbsp;'.$fuco.'<br><img src="/images/udist.png" width="12" height="12">&nbsp;Distance: '.$dist.' km<br><img src="/images/fus.png" width="12" height="12">&nbsp;Friends: '.$numoffs.'</span></div></a>';
    }

    $stmt->close();

    $fwing_str = "";
    $fling_array = array();
    $sql = "SELECT * FROM follow WHERE follower=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s",$u);
    $stmt->execute();
    $result = $stmt->get_result();
    while($row = $result->fetch_assoc()){
        $fling = $row["following"];
        $fling_string = '<a href="/user/'.$fling.'/">'.$fling."</a>";
        array_push($fling_array, $fling_string);
    }
    $fwing_str = implode(", ",$fling_array);

    if($result->num_rows > 15){
        $fwing_str .= ' ...';
    }

    if($fwing_str == ""){
        $fwing_str = "none";
    }

    $stmt->close();

    $meFollow = "";
    $sql = "SELECT COUNT(id) FROM follow WHERE follower=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s",$u);
    $stmt->execute();
    $stmt->bind_result($mecount);
    $stmt->fetch();
    if($mecount < 1 && $isOwner == "Yes"){
        $meFollow = 'You are not following anyone at the moment ('.$fwing_str.')';
    }else if($mecount < 1 && $isOwner == "No"){
        $meFollow = ''.$u.' is not following anyone at the moment ('.$fwing_str.')';
    }else if($mecount == 1 && $isOwner == "Yes"){
        $meFollow = 'You are following '.$mecount.' person at the moment ('.$fwing_str.')';
    }else if($mecount > 1 && $isOwner == "Yes"){
        $meFollow = 'You are following '.$mecount.' people at the moment ('.$fwing_str.')';
    }else if($mecount > 1 && $isOwner == "No"){
        $meFollow = ''.$u.' is following '.$mecount.' people at the moment ('.$fwing_str.')';
    }else if($mecount == 1 && $isOwner == "No"){
        $meFollow = ''.$u.' is following '.$mecount.' person at the moment ('.$fwing_str.')';
    }
    $stmt->close();

    // Create the photos button
    $photos_btn = "<button onclick='window.location = '/photos/<?php echo $u; ?>View Photos</button>";
?>
<?php
    $job = "";
    $about = "";
    $works = "";
    $profession = "";
    $city = "";
    $state = "";
    $mobile = "";
    $hometown = "";
    $fmovie = "";
    $fmusic = "";
    $pstatus = "";
    $quotes = "";
    $elemen = "";
    $high = "";
    $uni = "";
    $politics = "";
    $religion = "";
    $nd_day = "";
    $nd_month = "";
    $interest = "";
    $notemail = "";
    $website = "";
    $language = "";
    $ndtogether = "";
    $address = "";
    $degree = "";
    $profselw = "";
    $profselr = "";
    $profselu = "";
    $profselo = "";
    $profsels = "";
    $ndmonja = "";
    $ndmonfe = "";
    $ndmonrh = "";
    $ndmonap = "";
    $ndmonma = "";
    $ndmonju = "";
    $ndmonjul = "";
    $ndmona = "";
    $ndmons = "";
    $ndmono = "";
    $ndmonn = "";
    $ndmond = "";
    $nd1 = "";
    $nd2 = "";
    $nd3 = "";
    $nd4 = "";
    $nd5 = "";
    $nd6 = "";
    $nd7 = "";
    $nd8 = "";
    $nd9 = "";
    $nd10 = "";
    $nd11 = "";
    $nd12 = "";
    $nd13 = "";
    $nd14 = "";
    $nd15 = "";
    $nd16 = "";
    $nd17 = "";
    $nd18 = "";
    $nd19 = "";
    $nd20 = "";
    $nd21 = "";
    $nd22 = "";
    $nd23 = "";
    $nd24 = "";
    $nd25 = "";
    $nd26 = "";
    $nd27 = "";
    $nd28 = "";
    $nd29 = "";
    $nd30 = "";
    $nd31 = "";
    
    function mysqli_decode($string) {
        $characters = array('x00', 'n', 'r', '\\', '\'', '"','x1a');
        $o_chars = array("\x00", "\n", "\r", "\\", "'", "\"", "\x1a");
        for ($i = 0; $i < strlen($string); $i++) {
            if (substr($string, $i, 1) == '\\') {
                foreach ($characters as $index => $char) {
                    if ($i <= strlen($string) - strlen($char) && substr($string, $i + 1, strlen($char)) == $char) {
                        $string = substr_replace($string, $o_chars[$index], $i, strlen($char) + 1);
                        break;
                    }
                }
            }
        }
        return $string;
    }
    
    // Gather more information about user
    $sql = "SELECT * FROM edit WHERE username=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s",$u);
    $stmt->execute();
    $result = $stmt->get_result();
    if($result->num_rows > 0){
        while($row = $result->fetch_assoc()){
            $job = mysqli_decode($row["job"]);
            $about = mysqli_decode($row["about"]);
            $profession = mysqli_decode($row["profession"]);
            $state = mysqli_decode($row["state"]);
            $city = mysqli_decode($row["city"]);
            $mobile = mysqli_decode($row["mobile"]);
            $hometown = mysqli_decode($row["hometown"]);
            $fmusic = mysqli_decode($row["fav_music"]);
            $fmovie = mysqli_decode($row["fav_movie"]);
            $pstatus = mysqli_decode($row["par_status"]);
            $elemen = mysqli_decode($row["elemen"]);
            $high = mysqli_decode($row["high"]);
            $uni = mysqli_decode($row["uni"]);
            $politics = mysqli_decode($row["politics"]);
            $religion = mysqli_decode($row["religion"]);
            $nd_day = mysqli_decode($row["nd_day"]);
            $nd_month = mysqli_decode($row["nd_month"]);
            $ndtonum = strftime("%m", strtotime($nd_month));
            $ndtogether = "2018-".$ndtonum."-".$nd_day;
            $interest = mysqli_decode($row["interest"]);
            $notemail = mysqli_decode($row["notemail"]);
            $website = mysqli_decode($row["website"]);
            $language = mysqli_decode($row["language"]);
            $address = mysqli_decode($row["address"]);
            $degree = mysqli_decode($row["degree"]);
            $quotes = mysqli_decode($row["quotes"]);
            $cleanqu = mysqli_decode($quotes);
            $cleanqu = str_replace("”",'',$cleanqu);
            
            if(!(substr($website, 0, 7) === "http://")){
                $website = "http://".$website;
            }
            
            $emailURL = $notemail;
            if(!(substr($notemail, 0, 7) === "mailto:")){
                $emailURL = "mailto:".$notemail;
            }
        }
        if($profession == "w"){
            $works = "Working";
            $profselw = "selected";
        }else if($profession == "r"){
            $works = "Retired";
            $profselr = "selected";
        }else if($profession == "u"){
            $works = "Unemployed";
            $profselu = "selected";
        }else if($profession == "o"){
            $works = "Other";
            $profselo = "selected";
        }else{
            $works = "Student";
            $profsels = "selected";
        }
        
        if($nd_month == 'January'){
            $ndmonja = "selected";
        }else if($nd_month == 'February'){
            $ndmonfe = "selected";
        }else if($nd_month == 'March'){
            $ndmonrh = "selected";
        }else if($nd_month == 'April'){
            $ndmonap = "selected";
        }else if($nd_month == 'May'){
            $ndmonma = "selected";
        }else if($nd_month == 'June'){
            $ndmonju = "selected";
        }else if($nd_month == 'July'){
            $ndmonjul = "selected";
        }else if($nd_month == 'August'){
            $ndmona = "selected";
        }else if($nd_month == 'September'){
            $ndmons = "selected";
        }else if($nd_month == 'October'){
            $ndmono = "selected";
        }else if($nd_month == 'November'){
            $ndmonn = "selected";
        }else{
            $ndmond = "selected";
        }
        
        if($nd_day == 1){
            $nd1 = "selected";
        }else if($nd_day == 2){
            $nd2 = "selected";
        }else if($nd_day == 3){
            $nd3 = "selected";
        }else if($nd_day == 4){
            $nd4 = "selected";
        }else if($nd_day == 5){
            $nd5 = "selected";
        }else if($nd_day == 6){
            $nd6 = "selected";
        }else if($nd_day == 7){
            $nd7 = "selected";
        }else if($nd_day == 8){
            $nd8 = "selected";
        }else if($nd_day == 9){
            $nd9 = "selected";
        }else if($nd_day == 10){
            $nd10 = "selected";
        }else if($nd_day == 11){
            $nd11 = "selected";
        }else if($nd_day == 12){
            $nd12 = "selected";
        }else if($nd_day == 13){
            $nd13 = "selected";
        }else if($nd_day == 14){
            $nd14 = "selected";
        }else if($nd_day == 15){
            $nd15 = "selected";
        }else if($nd_day == 16){
            $nd16 = "selected";
        }else if($nd_day == 17){
            $nd17 = "selected";
        }else if($nd_day == 18){
            $nd18 = "selected";
        }else if($nd_day == 19){
            $nd19 = "selected";
        }else if($nd_day == 20){
            $nd20 = "selected";
        }else if($nd_day == 21){
            $nd21 = "selected";
        }else if($nd_day == 22){
            $nd22 = "selected";
        }else if($nd_day == 23){
            $nd23 = "selected";
        }else if($nd_day == 24){
            $nd24 = "selected";
        }else if($nd_day == 25){
            $nd25 = "selected";
        }else if($nd_day == 26){
            $nd26 = "selected";
        }else if($nd_day == 27){
            $nd27 = "selected";
        }else if($nd_day == 28){
            $nd28 = "selected";
        }else if($nd_day == 29){
            $nd29 = "selected";
        }else if($nd_day == 30){
            $nd30 = "selected";
        }else{
            $nd31 = "selected";
        }
        $stmt->close();
    }
?>
<?php
    // Add article button
    $article = "";
    if($log_username != "" && $user_ok == true && $isOwner == "Yes"){
        $article = '<button class="main_btn_fill fixRed" onclick="hgoArt()">Write article</button>';
    }
?>
<?php
    // Echo articles
    $echo_articles = "";
    $post_time = "";
    $written_by = "";
    $numnum = 0;
    $sql = "SELECT * FROM articles WHERE written_by=? ORDER BY RAND() LIMIT 6";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s",$u);
    $stmt->execute();
    $result = $stmt->get_result();
    if($result->num_rows > 0){
        while($row = $result->fetch_assoc()){
            $written_by = $row["written_by"];
            $wbinfo = $written_by;
            $title = html_entity_decode($row["title"]);
            $tags = $row["tags"];
            $post_time_ = $row["post_time"];
            $pt = base64url_encode($post_time_,$hshkey);
            $post_time = strftime("%b %d, %Y", strtotime($post_time_));
            $cat = $row["category"];
            $title_new = $title;
            $written_by_original = urlencode($written_by);

            $cover = chooseCover($cat);
            $sql = "SELECT COUNT(id) FROM heart_likes WHERE art_time=? AND art_uname=?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ss",$post_time_,$wbinfo);
            $stmt->execute();
            $stmt->bind_result($heart_count);
            $stmt->fetch();
            $stmt->close();
            $sql = "SELECT COUNT(id) FROM fav_art WHERE art_time=? AND art_uname=?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ss",$post_time_,$wbinfo);
            $stmt->execute();
            $stmt->bind_result($fav_count);
            $stmt->fetch();
            $stmt->close();
            $numnum++;
            $echo_articles .= '<a href="/articles/'.$pt.'/'.$written_by_original.'"><div class="article_echo_2" style="width: 100%;">'.$cover.'<div><p class="title_"><b>Author: </b>'.$written_by.'</p>';
            $echo_articles .= '<p class="title_"><b>Title: </b>'.$title.'</p>';
            $echo_articles .= '<p class="title_"><b>Posted: </b>'.$post_time.'</p>';
            $echo_articles .= '<p class="title_"><p class="title_"><b>Tags: </b>'.$tags.'</p>';
            $echo_articles .= '<p class="title_"><b>Category: </b>'.$cat.'</p></div></div></a>';
        }
    }

    // Article view all link logic
    $article_link = "";
    $sql = "SELECT * FROM articles WHERE written_by=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s",$u);
    $stmt->execute();
    $stmt->store_result();
    $stmt->fetch();
    $numrows = $stmt->num_rows;
    if($numrows > 0){
        if($isOwner == "No"){
            $article_link = '<a href="/user_articles/'.$written_by.'">View All</a>';
        }
    }

    $stmt->close();

    // Count posts and replies
    $sql = "SELECT COUNT(id) FROM status WHERE author=? OR account_name=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss",$u,$u);
    $stmt->execute();
    $stmt->bind_result($status_count);
    $stmt->fetch();
    $stmt->close();

    // Get background
    $attribute = "";
    $sql = "SELECT * FROM useroptions WHERE username=? LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s",$u);
    $stmt->execute();
    $result = $stmt->get_result();
    while($row = $result->fetch_assoc()){
        $bg = $row["background"];
    }

    $stmt->close();

    $attribute = '/user/'.$u.'/background/'.$bg;
    if($bg == NULL || $bg == "original"){
        $attribute = '/images/backgrounddefault.png';
    }

    // Get how many users online
    // Get friends arrays
    $u2 = array();
    $u1 = array();
    $sql = "SELECT user2 FROM friends WHERE user1=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s",$log_username);
    $stmt->execute();
    $result = $stmt->get_result();
    while($row = $result->fetch_assoc()){
        $u2 = $row["user2"];
    }

    $stmt->close();

    $sql = "SELECT user1 FROM friends WHERE user2=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s",$log_username);
    $stmt->execute();
    while($row = $result->fetch_assoc()){
        $u1 = $row["user1"];
    }

    $stmt->close();

    // Get user photos
    $numnum = 0;
    $userallf = array();
    $sql = "SELECT user1, user2 FROM friends WHERE (user2=? OR user1=?) AND accepted=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sss",$log_username,$log_username,$one);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        if ($row["user1"] != $u && $row["user1"] != $log_username){array_push($userallf, $row["user1"]);}
        if ($row["user2"] != $u && $row["user2"] != $log_username){array_push($userallf, $row["user2"]);}
    }
    $stmt->close();
    $uallf = join("','",$userallf);
    $echo_photos = "";
    if($ismobile == false){
        $lmit = 10;
    }else{
        $lmit = 6;
    }
    $sql = "SELECT * FROM photos WHERE user=? ORDER BY uploaddate DESC LIMIT 12";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s",$u);
    $stmt->execute();
    $result = $stmt->get_result();
    while($row = $result->fetch_assoc()){
        $filename_photo = $row["filename"];
        $gallery_photo = $row["gallery"];
        $description = $row["description"];
        $uploader = $row["user"];
        $udate = strftime("%b %d, %Y", strtotime($row["uploaddate"]));;
        $ud = time_elapsed_string($udate);
        if(strlen($description) > 16){
            $description = mb_substr($description, 0, 12, "utf-8");
            $description .= " ...";
        }
        if($description == ""){
            $description = "No description ...";
        }
        $stmt->close();
        $sql = "SELECT author FROM photos_status WHERE photo = ? AND author IN ('$uallf') LIMIT 1";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s",$filename_photo);
        $stmt->execute();
        $stmt->bind_result($fwhoc);
        $stmt->fetch();
        $stmt->close();
        if($fwhoc == ""){
            $fwhoc = "none of your friends has posted yet";
        }
        if(strlen($uploader) > 35){
            $uploader = mb_substr($uploader, 0, 31, "utf-8");
            $uploader .= " ...";
        }
        if(strlen($fwhoc) > 35){
            $fwhoc = mb_substr($fwhoc, 0, 31, "utf-8");
            $fwhoc .= " ...";
        }
        $numnum++;
        $pcurl = '/user/'.$u.'/'.$filename_photo.'';
        $openURL = '/photo_zoom/'.$u.'/'.$filename_photo.'';
        list($width,$height) = getimagesize('user/'.$u.'/'.$filename_photo.'');
        $echo_photos .= '<div class="pccanvas userPhots" onmouseover="appPho(\''.$numnum.'\')" onmouseleave="disPho(\''.$numnum.'\')" onclick="openURL(\''.$openURL.'\')"><div class="pcnpdiv lazy-bg" data-src=\''.$pcurl.'\'><div id="photo_heading" style="width: auto !important; margin-top: 0px; position: static;">'.$width.' x '.$height.'</div></div><div class="infoimgdiv" id="phonum_'.$numnum.'" style="width: auto; height: auto;"><div data-src=\''.$pcurl.'\' style="background-repeat: no-repeat; background-position: center; background-size: cover; width: 120px; height: 103px; float: left; border-radius: 10px;" class="lazy-bg"></div><span><img src="/images/picture.png" width="12" height="12">&nbsp;Gallery: '.$gallery_photo.'<br><img src="/images/desc.png" width="12" height="12">&nbsp;Description: '.$description.'<br><img src="/images/nddayico.png" width="12" height="12">&nbsp;Pusblished: '.$udate.' ('.$ud.' ago)<br><img src="/images/puname.png" width="12" height="12">&nbsp;Uploader: '.$uploader.'<br><img src="/images/fus.png" width="12" height="12">&nbsp;Friends who posted below the photo: '.$fwhoc.'</span></div></div>';
    }

    // Get user's videos
    $videos = "";
    $sql = "SELECT * FROM videos WHERE user=? ORDER BY RAND() LIMIT 3";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s",$u);
    $stmt->execute();
    $result = $stmt->get_result();
    while($row = $result->fetch_assoc()){
        $id = $row["id"];
        $vf = $row["video_file"];
        $description = $row["video_description"];
        $video_name = $row["video_name"];
        $video_upload = $row["video_upload"];
        $pr = $row["video_poster"];
        $dur = $row["dur"];
        $dur = convDur($dur);
        $video_upload_ = strftime("%b %d, %Y", strtotime($video_upload));
        if($video_name == ""){
            $video_name = "Untitled";
        }
        if($description == ""){
            $description = "No description";
        }
        if($pr == ""){
            $pr = "/images/uservid.png";
        }else{
            $pr = '/user/'.$u.'/videos/'.$pr.'';
        }

        if(strlen($description) > $num){
            $description = mb_substr($description, 0, 22, "utf-8");
            $description .= " ...";
        }
        if(strlen($video_name) > $numnum){
            $video_name = mb_substr($video_name, 0, 22, "utf-8");
            $video_name .= " ...";
        }
        $ec = base64url_encode($id,$hshkey);
        $videos .= "<a href='/video_zoom/" . $ec . "' style='height: 150px;'><div class='nfrelv' style='width: 100%;'><div data-src=\"".$pr."\" class='lazy-bg' style='height: 150px;' id='pcgetc'></div><div class='pcjti'>" . $video_name . "</div><div class='pcjti' style='width: auto; border-radius: 3px; margin-left: 2px; position: absolute; bottom: 15px;'>" . $dur . "</div></div></a>";


    }
    $stmt->close();
    $sql = "SELECT * FROM videos WHERE user=? ORDER BY RAND() LIMIT 3";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s",$u);
    $stmt->execute();
    $stmt->store_result();
    $stmt->fetch();
    $numrows = $stmt->num_rows;
    if($numrows < 1){
        if($isOwner == "No"){
            $videos = "<p style='color: #999;' class='txtc'>It seems that ".$u." has not uploaded any videos yet</p>";
        }else{
            $videos = "<p style='color: #999;' class='txtc'>It seems that you have not uploaded any videos yet</p>";
        }
    }
    $stmt->close();

    // Get friends in common
    // Get my friends
    $myf = array();
    $sql = "SELECT user1, user2 FROM friends WHERE user2 = ? AND user1 != ? AND accepted=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sss",$log_username,$u,$one);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        array_push($myf, $row["user1"]);
    }
    $stmt->close();

    $sql = "SELECT user1, user2 FROM friends WHERE user1 = ? AND user2 != ? AND accepted=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sss",$log_username,$u,$one);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        array_push($myf, $row["user2"]);
    }
    $stmt->close();

    //print_r($myf);

    // Get current user's friends
    $theirf = array();
    $sql = "SELECT user1, user2 FROM friends WHERE user2 = ? AND user1 != ? AND accepted=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sss",$u,$log_username,$one);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        array_push($theirf, $row["user1"]);
    }
    $stmt->close();

    $sql = "SELECT user1, user2 FROM friends WHERE user1 = ? AND user2 != ? AND accepted=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sss",$u,$log_username,$one);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        array_push($theirf, $row["user2"]);
    }
    $stmt->close();

    $incomm = array_intersect($myf, $theirf);
    $resincomm = count($incomm);

    // Get number of all photos
    $sql = "SELECT COUNT(id) FROM photos WHERE user=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s",$u);
    $stmt->execute();
    $stmt->bind_result($count_all);
    $stmt->fetch();
    $stmt->close();

    // Get names
    $count_inc = 0;
    $incommnames = "";
    $namesarr = join("','", $incomm);
    $sql = "SELECT * FROM users WHERE username IN ('$namesarr') LIMIT 15";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $result = $stmt->get_result();
    while($row = $result->fetch_assoc()){
        $uname = $row["username"];
        $incommnames .= '<a href="/user/'.$uname.'/">'.$uname.'</a>';
        $count_inc++;
    }
    $stmt->close();
    
    if($count_inc >= 15){
        $incommnames .= " ...";
    }

    if(!isset($_SESSION["username"]) || $_SESSION["username"] == "" || $incommnames == ""){
        $incommnames = "none";
    }

    $sql = "SELECT COUNT(id) FROM fav_art WHERE art_uname = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s",$u);
    $stmt->execute();
    $stmt->bind_result($count_favs);
    $stmt->fetch();
    $stmt->close();

    $sql = "SELECT COUNT(id) FROM videos WHERE user = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s",$u);
    $stmt->execute();
    $stmt->bind_result($count_vids);
    $stmt->fetch();
    $stmt->close();

    $sql = "SELECT COUNT(l.id) FROM video_likes AS l LEFT JOIN videos AS v ON v.id = l.video WHERE l.username = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s",$u);
    $stmt->execute();
    $stmt->bind_result($count_vid_likes);
    $stmt->fetch();
    $stmt->close();
    
    $sql = "SELECT COUNT(id) FROM heart_likes WHERE art_uname = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s",$u);
    $stmt->execute();
    $stmt->bind_result($count_likes);
    $stmt->fetch();
    $stmt->close();

    $sql = "SELECT COUNT(id) FROM articles WHERE written_by = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s",$u);
    $stmt->execute();
    $stmt->bind_result($count_arts);
    $stmt->fetch();
    $stmt->close();

    // Current date
    $curdate = date("Y-m-d");
    $bddate = "2018-".$bdate;
    $sql = "SELECT DATEDIFF(?,?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss",$bddate,$curdate);
    $stmt->execute();
    $stmt->bind_result($untilbd);
    $stmt->fetch();
    $stmt->close();
    $days = "";

    if($leap == "1"){
        $days = "366";
    }else{
        $days = "365";
    }

    if($untilbd < 0){
        $untilbd = $days + $untilbd;
    }

    $daysubd = $untilbd." days until birthday";
    if($daysubd == 0){
        $daysubd = "happy birthday!";
    }

    $try = date("Y-m-d");
    $sql = "SELECT DATEDIFF(?,?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss",$ndtogether,$try);
    $stmt->execute();
    $stmt->bind_result($untilnd);
    $stmt->fetch();
    $stmt->close();

    if($untilnd < 0){
        $untilnd = $days + $untilnd;
    }

    $untilndday = $untilnd." days until name day";
    if($untilndday == 0){
        $untilndday = "happy name day!";
    }

    // Get the number of comments and posts
    $sql = "SELECT COUNT(id) FROM status WHERE account_name = ? AND type = ? OR type = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sss",$u,$a,$c);
    $stmt->execute();
    $stmt->bind_result($countposts);
    $stmt->fetch();
    $stmt->close();

    // Comments
    $sql = "SELECT COUNT(id) FROM status WHERE account_name = ? AND type = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss",$u,$b);
    $stmt->execute();
    $stmt->bind_result($countrply);
    $stmt->fetch();
    $stmt->close();

    // Get groups
    $echo_groups = "";
    $sql = "SELECT gm.*, gp.*
        FROM gmembers AS gm
        LEFT JOIN groups AS gp ON gp.name = gm.gname
        WHERE gm.mname = ? ORDER BY gp.creation DESC LIMIT 7";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s",$u);
    $stmt->execute();
    $result = $stmt->get_result();
    while($row = $result->fetch_assoc()){
        $gname = $row["gname"];
        $gorim = $gname;
        $gori = urlencode($gname);
        $logo = $row["logo"];
        $cdate_ = $row["creation"];
        $crtorgr = $row["creator"];
        $categ = $row["cat"];
        $des = $row["des"];

        $cdate = strftime("%b %d, %Y", strtotime($cdate_));
        $agoform = time_elapsed_string($cdate_);
        $invrule = $row["invrule"];
        if($invrule == 0){
            $invrule = "private group";
        }else{
            $invrule = "public group";
        }

        if($logo != NULL && $logo != "gdef.png"){
            $logo = '/groups/'.$gorim.'/'.$logo;
        }else if($logo == NULL || $logo == "gdef.png"){
            $logo = '/images/gdef.png';
        }
        if($categ == 1){
            $categ = "Animals";
        }else if($categ == 2){
            $categ = "Relationships";
        }else if($categ == 3){
            $categ = "Friends & Family";
        }else if($categ == 4){
            $categ = "Freetime";
        }else if($categ == 5){
            $categ = "Sports";
        }else if($categ == 6){
            $categ = "Games";
        }else if($categ == 7){
            $categ = "Knowledge";
        }else{
            $categ = "Other";
        }
        if($des == NULL){
            $des = "No description";
        }

        $echo_groups .= '<a href="/group/'.$gori.'"><div class="article_echo_2" style="width: 100%;"><div data-src=\''.$logo.'\' style="background-repeat: no-repeat; background-position: center; background-size: cover; width: 80px; height: 80px; float: right; border-radius: 50%;" class="lazy-bg"></div><div><p class="title_"><b>Name: </b>'.$gname.'</p>';
      $echo_groups .= '<p class="title_"><b>Creator: </b>'.$crtorgr.'</p>';
      $echo_groups .= '<p class="title_"><b>Established: </b>'.$agoform.' ago</p>';
      $echo_groups .= '<p class="title_"><b>Description: </b>'.$des.'</p>';
      $echo_groups .= '<p class="title_"><b>Category: </b>'.$categ.'</p></div></div></a>';
    }
    $echo_groups .= '<div class="clear"></div>';
    $stmt->close();

    $sql = "SELECT COUNT(id) FROM gmembers WHERE mname = ? AND approved = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss",$u,$one);
    $stmt->execute();
    $stmt->bind_result($countmyg);
    $stmt->fetch();
    $stmt->close();
    
    // Get followed users
    $followed_link = "";
    $sql = "SELECT COUNT(id) FROM follow WHERE follower = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s",$u);
    $stmt->execute();
    $stmt->bind_result($followed_count);
    $stmt->fetch();
    $stmt->close();
    
    $followed_array = "";
    $sql = "SELECT f.*, u.* FROM follow AS f LEFT JOIN users AS u ON u.username = f.following WHERE follower = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s",$u);
    $stmt->execute();
    $res = $stmt->get_result();
    while($row = $res->fetch_assoc()){
        $pp = "";
        $avatar = $row["avatar"];
        $username = $row["following"];
        $follow_date = $row["follow_time"];
        if($avatar != NULL){
            $pp = '<img src="/user/'.$username.'/'.$avatar.'" width="50" height="50" style="border: 1px solid #336b87; margin-right: 5px;" class="flowmob"/>';
        }else{
            $pp = '<img src="/images/avdef.png" width="50" height="50" style="border: 1px solid #336b87; margin-right: 5px;" class="flowmob"/>';
        }
        $try = '<a href="/user/'.$username.'/">'.$pp.'</a>';
        $followed_array .= $try;
    }

    if($other_div == ""){
        if($isOwner == "Yes"){
            $other_div = "<p style='font-size: 14px; color: #999;' class='txtc'>It seems that you do not follow anyone right now</p>";
        }else{
            $other_div = "<p style='font-size: 14px; color: #999;' class='txtc'>It seems that ".$u." do not follow anyone right now</p>";
        }
    }

    $sql = "SELECT COUNT(id) FROM gmembers WHERE mname = ?";
      $stmt = $conn->prepare($sql);
      $stmt->bind_param("s", $u);
      $stmt->execute();
      $stmt->bind_result($member_count);
      $stmt->fetch();
      $stmt->close();

      $sql = "SELECT COUNT(id) FROM groups WHERE creator = ?";
      $stmt = $conn->prepare($sql);
      $stmt->bind_param("s", $u);
      $stmt->execute();
      $stmt->bind_result($creator_count);
      $stmt->fetch();
      $stmt->close();
?>
<!DOCTYPE html>
<html>
<head>
    <title><?php if($wart == ""){ ?><?php echo $u; ?><?php }else{ ?>Write an article<?php } ?></title>
    <meta charset="utf-8">
    <meta lang="en">
    <meta name="description" content="Check <?php echo $u; ?>'s articles, photos, videos and friends, send them a message and post on their profile!">
    <meta name="keywords" content="<?php echo $u; ?>, <?php echo $u; ?> pearscom, <?php echo $u; ?> profile, user profile <?php echo $u; ?>, user <?php echo $u; ?>">
    <meta name="author" content="Pearscom">
    	  <link rel="manifest" href="/manifest.json">
  <meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="#282828">
<meta name="apple-mobile-web-app-title" content="Pearscom">
<link rel="apple-touch-icon" href="/images/icons/icon-152x152.png">
<meta name="theme-color" content="#282828" />
    <!--[if IE]>
        <link rel="stylesheet" type="text/css" href="style_ie.css" />
    <![endif]-->
    <link rel="icon" type="image/x-icon" href="/images/newfav.png">
    <link rel="stylesheet" type="text/css" href="/style/style.css">
    <link rel="stylesheet" href="/font-awesome-4.7.0/css/font-awesome.min.css">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="/js/jjs.js"></script>
    <script src="/js/main.js" async></script>
    <script src="/js/ajax.js" async></script>f
    <script src="/js/mbc.js"></script>
    <script src="/js/fadeEffects.js" async></script>
    <style type="text/css">
        @media only screen and (max-width: 747px){
            #video_controls_bar{
                width: 50% !important;
            }
        }
    </style>
    <script type="text/javascript">
        var luname = "<?php echo $log_username; ?>";
        let editArray = ["edu", "pro_", "city_", "me", "con", "geoLoc"];
        function loopDisplay(val, o) {
            if(o != "geoLoc"){
                _("appendLoc").style.display = val;
                _("geolocBtn").style.display = val;
            }
          for (let j = 0; j < editArray.length; j++) {
            if (editArray[j] != o) {
              _(editArray[j]).style.display = val;
            }
          }
        }
        function openDD(el) {
          if (_(el).style.display == "flex") {
            _(el).style.display = "none";
            if(el != "geoLoc") _("editbtn").style.display = "none";
            else if(el == "geoLoc"){ 
                _("geolocBtn").style.display = "none";
                _("editbtn").style.display = "flex";
            }
            _("showHr").style.display = "none";
            if(el == "geoLoc") _("appendLoc").style.display = "none";
          } else {
            _(el).style.display = "flex";
            if(el != "geoLoc") _("editbtn").style.display = "flex";
            else if(el == "geoLoc"){ 
                _("geolocBtn").style.display = "flex";
                _("editbtn").style.display = "none";
            }
            _("showHr").style.display = "block";
            if(el == "geoLoc") _("appendLoc").style.display = "block";
            loopDisplay("none", el);
          }
        }
        function hgoArt() {
          window.location = "/user/" + luname + "&wart=yes";
        }
        function openPP(uri, treeish) {
          if ("avdef.png" == uri) {
            _("overlay").style.display = "block";
            _("overlay").style.opacity = .5;
            _("dialogbox_art").style.display = "block";
            _("dialogbox_art").innerHTML = '<img src="/images/avdef.png" width="100%"><button id="vupload" style="float: right; margin: 3px;" onclick="closeDialog_a()">Close</button>';
            document.body.style.overflow = "hidden";
          } else {
            _("overlay").style.display = "block";
            _("overlay").style.opacity = .5;
            _("dialogbox_art").style.display = "block";
            _("dialogbox_art").innerHTML = '<img src="/user/' + treeish + "/" + uri + '" width="100%"><button id="vupload" style="float: right; margin: 3px;" onclick="closeDialog_a()">Close</button>';
            document.body.style.overflow = "hidden";
          }
        }
        var mobilecheck = mobilecheck();
        function appArt(name) {
          _("artnum_" + name).style.display = 0 == mobilecheck ? "inline-block" : "none";
        }
        function disArt(name) {
          _("artnum_" + name).style.display = "none";
        }
        function appPho(name) {
          _("phonum_" + name).style.display = 0 == mobilecheck ? "inline-block" : "none";
        }
        function disPho(name) {
          _("phonum_" + name).style.display = "none";
        }
        function openURL(url) {
          window.location = url;
        }
        function closeDialog_a() {
          _("dialogbox_art").style.display = "none";
          _("overlay").style.display = "none";
          _("overlay").style.opacity = 0;
          document.body.style.overflow = "auto";
        }
        function friendToggle(ntests, i, name) {
          _(name).innerHTML = '<img src="/images/rolling.gif" width="30" height="30">';
          var xhr = ajaxObj("POST", "/php_parsers/friend_system.php");
          xhr.onreadystatechange = function() {
            if (1 == ajaxReturn(xhr)) {
              if ("friend_request_sent" == xhr.responseText) {
                _(name).innerHTML = "<p style='font-size: 14px; margin: 0; color: #999;'>Friend request has been successfully sent</p>";
              } else {
                if ("unfriend_ok" == xhr.responseText) {
                  _(name).innerHTML = "<button onclick=\"friendToggle('friend','<?php echo $u; ?>','friendBtn')\" class='main_btn_fill fixRed'>Request as friend</button>";
                } else {
                  _("overlay").style.display = "block";
                  _("overlay").style.opacity = .5;
                  _("dialogbox").style.display = "block";
                  _("dialogbox").innerHTML = '<p style="font-size: 18px; margin: 0px;">An error occured</p><p>Unfortunately an unknown error has occured during the processing. Please try again later and check everything is proper.</p><br /><button id="vupload" style="position: absolute; right: 3px; bottom: 3px;" onclick="closeDialog()">Close</button>';
                  document.body.style.overflow = "hidden";
                  _(name).innerHTML = "<p style='font-size: 14px; margin: 0; color: #999;'>Try again later</p>";
                }
              }
            }
          };
          xhr.send("type=" + ntests + "&user=" + i);
        }
        function blockToggle(_wid_attr, data, template) {
          (template = document.getElementById(template)).innerHTML = '<img src="/images/rolling.gif" width="30" height="30">';
          var xhr = ajaxObj("POST", "/php_parsers/block_system.php");
          xhr.onreadystatechange = function() {
            if (1 == ajaxReturn(xhr)) {
              if ("blocked_ok" == xhr.responseText) {
                template.innerHTML = "<button onclick=\"blockToggle('unblock','<?php echo $u; ?>','blockBtn')\" class='main_btn_fill fixRed'>Unblock User</button>";
              } else {
                if ("unblocked_ok" == xhr.responseText) {
                  template.innerHTML = "<button onclick=\"blockToggle('block','<?php echo $u; ?>','blockBtn')\" class='main_btn_fill fixRed'>Block User</button>";
                } else {
                  _("overlay").style.display = "block";
                  _("overlay").style.opacity = .5;
                  _("dialogbox").style.display = "block";
                  _("dialogbox").innerHTML = '<p style="font-size: 18px; margin: 0px;">An error occured</p><p>Unfortunately an unknown error has occured during the processing. Please try again later and check everything is proper.</p><br /><button id="vupload" style="position: absolute; right: 3px; bottom: 3px;" onclick="closeDialog()">Close</button>';
                  document.body.style.overflow = "hidden";
                  _(address).innerHTML = "<i style='font-size: 14px;'>Try again later</i>";
                  template.innerHTML = "<i style='font-size: 14px;'>Try again later!</i>";
                }
              }
            }
          };
          xhr.send("type=" + _wid_attr + "&blockee=" + data);
        }
        function showfile() {
          var e = _("bfile").value;
          e.substr(12);
          _("sel_f").innerHTML = "&nbsp;" + e.substr(12);
        }
        function statusMax(limitField, limitNum) {
          if (limitField.value.length > limitNum) {
            _("overlay").style.display = "block";
            _("overlay").style.opacity = .5;
            _("dialogbox").style.display = "block";
            _("dialogbox").innerHTML = '<p style="font-size: 18px; margin: 0px;">Maximum character limit reached</p><p>For some reasons we limited the number of characters that you can write at the same time. Now you have reached this limit.</p><br /><button id="vupload" style="position: absolute; right: 3px; bottom: 3px;" onclick="closeDialog()">Close</button>';
            document.body.style.overflow = "hidden";
            limitField.value = limitField.value.substring(0, limitNum);
          }
        }
        function emptyElement(id) {
          _(id).innerHTML = "";
        }
        function editChanges() {
          var code = _("status");
          var c = _("job").value;
          var name = _("elemen").value;
          var loca2 = _("high").value;
          var content = _("uni").value;
          var link = _("politics").value;
          var email = _("religion").value;
          var val = _("language").value;
          var node = _("nd_day").value;
          var styles = _("nd_month").value;
          var scalar = _("interest").value;
          var mask = _("notemail").value;
          var h = _("website").value;
          var key = _("address").value;
          var str = _("degree").value;
          var cp = _("ta").value;
          var result = _("profession_sel").value;
          var value = _("city").value;
          var input = _("state").value;
          var keyword = _("mobile").value;
          var text = _("hometown").value;
          var currentValue = _("movies").value;
          var line = _("music").value;
          var hostname = _("pstatus").value;
          var username = _("quotes").value;
          if ("" == c && "" == cp && "" == result && "" == value && "" == input && "" == keyword && "" == text && "" == currentValue && "" == hostname && "" == line && "" == name && "" == loca2 && "" == content && "" == link && "" == email && "" == val && "" == node && "" == styles && "" == scalar && "" == mask && "" == h && "" == key && "" == str && "" == username) {
            code.innerHTML = "Please fill in at least 1 field";
            return false;
          } else {
            _("editbtn").style.display = "none";
            code.innerHTML = '<img src="/images/rolling.gif" width="30" height="30">';
            var xhr = ajaxObj("POST", "/php_parsers/edit_parser.php");
            xhr.onreadystatechange = function() {
              if (1 == ajaxReturn(xhr)) {
                if ("edit_success" != xhr.responseText) {
                  code.innerHTML = xhr.responseText;
                  _("editbtn").style.display = "block";
                } else {
                    code.innerHTML = "";
                  _("after_status").innerHTML = "<p style='color: #999; text-align: center;'>Your changes has been saved successfully</p>";
                }
              }
            };
            xhr.send("job=" + c + "&ta=" + cp + "&pro=" + result + "&city=" + value + "&state=" + input + "&mobile=" + keyword + "&hometown=" + text + "&fmovie=" + currentValue + "&fmusic=" + line + "&pstatus=" + hostname + "&elemen=" + name + "&high=" + loca2 + "&uni=" + content + "&politics=" + link + "&religion=" + email + "&language=" + val + "&nd_day=" + node + "&nd_month=" + styles + "&interest=" + scalar + "&notemail=" + mask + "&website=" + h + "&address=" + key + "&degree=" + str + "&quotes=" + 
            username);
          }
        }
        function writeArticle() {
          var cancel = _("article_show");
          var header = _("writearticle");
          var input = _("art_btn");
          var tmp = _("hide_it");
          var code = _("userNavbar");
          var t = _("slide1");
          var line = _("slide2");
          if ("block" == cancel.style.display) {
            tmp.style.display = "none";
            cancel.style.display = "block";
            header.style.display = "block";
            input.style.display = "block";
            input.style.opacity = "0.9";
            code.style.display = "block";
            _("menuVer").style.display = "flex";
          } else {
            cancel.style.display = "none";
            header.style.display = "block";
            tmp.style.display = "none";
            code.style.display = "none";
            t.style.display = "none";
            line.style.display = "none";
            _("menuVer").style.display = "none";
            window.scrollTo(0, 0);
          }
        }

        var hasImageGen1 = "";
        var hasImageGen2 = "";
        var hasImageGen3 = "";
        var hasImageGen4 = "";
        var hasImageGen5 = "";

        function doUploadGen(data, holder, num){
            var s = _(data).files[0];
              if ("" == s.name) {
                return false;
              }
              if ("image/jpeg" != s.type && "image/gif" != s.type && "image/png" != s.type && "image/jpg" != s.type) {
                return _("overlay").style.display = "block", _("overlay").style.opacity = .5, _("dialogbox").style.display = "block", _("dialogbox").innerHTML = '<p style="font-size: 18px; margin: 0px;">File type is not supported</p><p>The image that you want to upload has an unvalid extension given that we do not support. The allowed file extensions are: jpg, jpeg, png and gif. For further information please visit the help page.</p><button id="vupload" style="position: absolute; right: 3px; bottom: 3px;" onclick="closeDialog()">Close</button>', 
                false;
              }
              _(holder).innerHTML = '<img src="/images/whup.jpg" width="100" height="100" class="triggerBtnreply mob_square" style="margin-left: 0px;">';
              var formData = new FormData;
              formData.append("stPic", s);
              var xhr = new XMLHttpRequest;
              xhr.addEventListener("load", function load(event){
                completeHandlerGen(event, holder, num)
              }, false);
              xhr.addEventListener("error", function error(event){
                errorHandlerGen(event, holder, num)
              }, false);
              xhr.addEventListener("abort", function abort(event){
                abortHandlerGen(event, holder, num)
              }, false);
              xhr.open("POST", "/php_parsers/photo_system.php");
              xhr.send(formData);
        }

        function completeHandlerGen(event, holder, num) {
          var t = event.target.responseText.split("|");
          if ("upload_complete" == t[0]) {
            if(num == "1") hasImageGen1 = t[1];
            else if(num == "2") hasImageGen2 = t[1];
            else if(num == "3") hasImageGen3 = t[1];
            else if(num == "4") hasImageGen4 = t[1];
            else if(num == "5") hasImageGen5 = t[1];
            _(holder).innerHTML = '<img src="/tempUploads/' + t[1] + '" class="triggerBtnreply mob_square" style="border-radius: 20px;"/>';
          } else {
            _(holder).innerHTML = "Unfortunately an unknown error has occured";
          }
        }
        function errorHandlerGen(event, holder) {
          _(holder).innerHTML = "Upload Failed";
        }
        function abortHandlerGen(event, holder) {
          _(holder).innerHTML = "Upload Aborted";
        }

        var _0xc754 = ["use strict"];
        function saveArticle() {
          var line = _("writearticle");
          var email = _("title").value;
          var message = _("status_art");
          var name = _("keywords").value;
          var cp = _("art_cat").value;
          line.elements.myTextArea.value = window.frames.richTextField.document.body.innerHTML;
          var username = line.elements.myTextArea.value;
          if (username = encodeURIComponent(username), "" == email || "" == username || "" == name || "" == cp) {
            message.innerHTML = '<p class="error_red">Please fill in all fields!</p>';
            return false;
          } else {
            _("article_btn").style.display = "none";
            message.innerHTML = '<img src="/images/rolling.gif" width="30" height="30">';
            var xhr = ajaxObj("POST", "/php_parsers/article_parser.php");
            xhr.onreadystatechange = function() {
              if (1 == ajaxReturn(xhr)) {
                var appfieldvals = xhr.responseText.split("|");
                appfieldvals[2];
                appfieldvals[3];
                if ("article_success" != appfieldvals[0]) {
                  message.innerHTML = "<p style='color: red;'>Unfortunately, an unknown error has occured. Please try again later ...</p>";
                  _("article_btn").style.display = "block";
                } else {
                  window.location = "/articles/" + appfieldvals[3] + "/" + appfieldvals[2];
                }
              }
            };
          }
          xhr.send("title=" + email + "&area=" + encodeURIComponent(username) + "&tags=" + name + "&cat=" + cp + "&img1="+hasImageGen1+"&img2="+hasImageGen2+"&img3="+hasImageGen3+"&img4="+hasImageGen4+"&img5="+hasImageGen5);
        }
        function followToggle(ntests, i, name, holder) {
          _(name).innerHTML = '<img src="/images/rolling.gif" width="30" height="30">';
          var xhr = ajaxObj("POST", "/php_parsers/follow_system.php");
          xhr.onreadystatechange = function() {
            if (1 == ajaxReturn(xhr)) {
              if ("follow_success" == xhr.responseText) {
                _(name).innerHTML = "<button class='main_btn_fill fixRed' onclick=\"followToggle('unfollow','<?php echo $u; ?>','followBtn', 'isFol')\">Unfollow</button>";
                _(holder).innerHTML = "You are following <?php echo $u; ?>";
              } else {
                if ("unfollow_success" == xhr.responseText) {
                  _(name).innerHTML = "<button class='main_btn_fill fixRed' onclick=\"followToggle('follow','<?php echo $u; ?>','followBtn', 'isFol')\">Follow</button>";
                  _(holder).innerHTML = "You are not a following anymore";
                } else {
                  _("overlay").style.display = "block";
                  _("overlay").style.opacity = .5;
                  _("dialogbox").style.display = "block";
                  _("dialogbox").innerHTML = '<p style="font-size: 18px; margin: 0px;">An error occured</p><p>Unfortunately an unknown error has occured with following. Please try again later and check everything is proper.</p><br /><button id="vupload" style="position: absolute; right: 3px; bottom: 3px;" onclick="closeDialog()">Close</button>';
                  document.body.style.overflow = "hidden";
                  _(name).innerHTML = '<p style="font-size: 14px; color: #999; margin: 0;">Try again later</p>';
                }
              }
            }
          };
          xhr.send("type=" + ntests + "&user=" + i);
        }
        var vid;
        var playbtn;
        var seekslider;
        var curtimetext;
        var durtimetext;
        var mutebtn;
        var volumeslider;
        var fullscrbtn;
        var showingSourceCode = false;
        var isInEditMode = false;
        function enableEditMode() {
          richTextField.document.designMode = "On";
        }
        function execCmd(cmd) {
          richTextField.document.execCommand(cmd, false, null);
        }
        function execCmdWithArg(prop, obj) {
          richTextField.document.execCommand(prop, false, obj);
        }
        function toggleSource() {
          if (showingSourceCode) {
            richTextField.document.getElementsByTagName("body")[0].innerHTML = richTextField.document.getElementsByTagName("body")[0].textContent;
            showingSourceCode = false;
          } else {
            richTextField.document.getElementsByTagName("body")[0].textContent = richTextField.document.getElementsByTagName("body")[0].innerHTML;
            showingSourceCode = true;
          }
        }
        function toggleEdit() {
          if (isInEditMode) {
            richTextField.document.designMode = "Off";
            isInEditMode = false;
          } else {
            richTextField.document.designMode = "On";
            isInEditMode = true;
          }
        }
        function uploadBiBg(type) {
          var message = _("statusbig");
          var request = (type = type, ajaxObj("POST", "/php_parsers/photo_system.php"));
          request.onreadystatechange = function() {
            if (1 == ajaxReturn(request)) {
              if ("bibg_success" == request.responseText) {
                message.innerHTML = '<p class="success_green">You have successfully changed your background to ' + type + "</p>";
                location.reload();
                window.scrollTo(0, 0);
              } else {
                _("overlay").style.display = "block";
                _("overlay").style.opacity = .5;
                _("dialogbox").style.display = "block";
                _("dialogbox").innerHTML = '<p style="font-size: 18px; margin: 0px;">An error occured</p><p>Unfortunately an unknown error has occured with your background uploading. Please try again later and check everything is proper.</p><br /><button id="vupload" style="position: absolute; right: 3px; bottom: 3px;" onclick="closeDialog()">Close</button>';
                document.body.style.overflow = "hidden";
                message.innerHTML = '<p class="error_red">Try again later</p>';
              }
            }
          };
          request.send("imgtype=" + type);
        }
        function showBiBg() {
          var cancel = _("statusbig");
          if ("none" == cancel.style.display) {
            cancel.style.display = "block";
          } else {
            cancel.style.display = "none";
          }
        }
        function initializePlayer(callback) {
          vid = _("my_video_" + callback);
          playbtn = _("playpausebtn_" + callback);
          seekslider = _("seekslider_" + callback);
          curtimetext = _("curtimetext_" + callback);
          durtimetext = _("durtimetext_" + callback);
          mutebtn = _("mutebtn_" + callback);
          volumeslider = _("volumeslider_" + callback);
          fullscrbtn = _("fullscrbtn_" + callback);
          seekslider.addEventListener("change", vidSeek, false);
          vid.addEventListener("timeupdate", seektimeupdate, false);
          mutebtn.addEventListener("click", vidmute, false);
          volumeslider.addEventListener("change", setVolume, false);
        }
        function vidSeek() {
          var seekto = vid.duration * (seekslider.value / 100);
          vid.currentTime = seekto;
        }
        function seektimeupdate() {
          var _startingFret = vid.currentTime * (100 / vid.duration);
          seekslider.value = _startingFret;
          var minutes = Math.floor(vid.currentTime / 60);
          var o = Math.floor(vid.currentTime - 60 * minutes);
          var interval = Math.floor(vid.duration / 60);
          var i = Math.floor(vid.duration - 60 * interval);
          if (o < 10) {
            o = "0" + Math.floor(vid.currentTime - 60 * minutes);
          }
          if (minutes < 10) {
            minutes = "0" + Math.floor(vid.currentTime / 60);
          }
          if (i < 10) {
            i = "0" + Math.floor(vid.duration - 60 * interval);
          }
          if (interval < 10) {
            interval = "0" + Math.floor(vid.duration / 60);
          }
          curtimetext.innerHTML = "0" + Math.floor(vid.currentTime / 60) + ":0" + Math.floor(vid.currentTime - 60 * minutes);
          durtimetext.innerHTML = "0" + Math.floor(vid.duration / 60) + ":0" + Math.floor(vid.duration - 60 * interval);
        }
        function vidmute() {
          if (vid.muted) {
            vid.muted = false;
            mutebtn.innerHTML = "<img src='/images/nmute.png' width='15' height='15'>";
            volumeslider.value = 100;
          } else {
            vid.muted = true;
            mutebtn.innerHTML = "<img src='/images/mute.png' width='19' height='19'>";
            volumeslider.value = 0;
          }
        }
        function setVolume() {
          vid.volume = volumeslider.value / 100;
        }
        function articleGuide(canCreateDiscussions) {
          _("overlay").style.display = "block";
          _("overlay").style.opacity = .5;
          _("dialogbox").style.display = "block";
          _("dialogbox").innerHTML = '<p style="font-size: 18px; margin: 0px;">Simple guide how to write an article</p><img src="/images/' + canCreateDiscussions + '"><br /><button id="vupload" style="position: absolute; right: 3px; bottom: 3px;" onclick="closeDialog()">Close</button>';
          document.body.style.overflow = "hidden";
        }
        function triggerUpload(event, file) {
          event.preventDefault();
          _(file).click();
        }
        function playPause(type) {
          if (vid.paused) {
            vid.play();
            playbtn.innerHTML = "<img src='/images/pausebtn.png' width='15' height='15'>";
            _("text_" + type).style.display = "none";
          } else {
            vid.pause();
            playbtn.innerHTML = "<img src='/images/playbtn.png' width='15' height='15'>";
            _("text_" + type).style.display = "block";
          }
        }
    </script>
</head>
<body onload="enableEditMode()" style="overflow-x: hidden;">
    <?php include_once("template_pageTop.php"); ?>
    
    <div id="overlay"></div>
    <div id="dialogbox"></div>
    <div id="dialogbox_art"></div>
    <div id="pageMiddle_2">
        <div class="row">
        <div id="name_holder"><?php echo $u; ?><?php if($nd_day != "" && $nd_month != ""){ ?><?php if($untilndday == "happy name day!"){?>&nbsp;<img src="/images/nddayico.png" width="20" height="20" style="margin-bottom: -2px;"><?php } ?><?php } ?><?php if($is_birthday == "yes" && $isOwner == "No"){echo '<img src="/images/bdcake.png" height="25" width="25" style="margin-left: 5px; margin-top: -3px; vertical-align: middle;" title="Today is '.$u.'&#39;s birthday! Wish happy birthday for him/her!">'; }else if($is_birthday == "yes" && $isOwner == "Yes"){echo '<img src="/images/bdcake.png" height="25" width="25" style="margin-left: 5px; margin-top: -3px; vertical-align: middle;" title="Happy birthday '.$log_username.'!">';}
          ?></div>
        <div data-src="<?php echo $attribute; ?>" class="lazy-bg" id="bg_holder_user">
      <div id="profile_pic_box" class="genBg lazy-bg" data-src="<?php echo $profile_pic; ?>" onclick="openPP(<?php echo $avatar; ?>,<?php echo $u; ?>)">
          <?php echo $profile_pic_btn; ?>
          <?php echo $avatar_form; ?>
      </div>
       </div>
       <div class="clear"></div>

       <div class="infoHolder" style="margin-top: 20px; padding: 5px; display: flex;" id="menuVer">
           <img src="/images/usrarr2.png" width="20" height="20" style="margin-top: 16px; margin-right: 5px; float: left; cursor: pointer;" id="slide1">
          <div id="userNavbar">
              <div id="userInfo">Information</div>
              <?php if($u == $log_username){ ?><div id="userEdit">Edit information</div><?php } ?>
              <?php if($log_username != $u && $_SESSION["username"] != ""){ ?><div id="userPm" onclick="showForm();">Messages</div><?php } ?>
              <div id="userFriends">Friends</div>
              <div id="userPhotos">Photos</div>
              <div id="userArticles">Articles</div>
              <div id="userFollowers">Followers</div>
              <div id="userVideos">Videos</div>
              <?php if($u == $log_username){ ?><div id="userBackground">Background</div><?php } ?>
              <div id="userGroups">Groups</div>
          </div>
          <img src="/images/usrarr.png" width="20" height="20" style="margin-top: 16px; margin-left: 5px; float: left; cursor: pointer;" id="slide2">
      </div>
      <div class="clear"></div>
      <div id="hide_it">
      <div id="min_height">
        <div id="aboutInfo">
        
        <div class="infoHolder">
            <div class="overviewInner">
                <div id="genI">General Information</div>
                <div id="perI">Personal Information</div>
                <div id="conI">Contact Information</div>
                <div id="eduI">Education &amp; Jobs</div>
                <div id="aboI">About Me</div>
            </div>
            <div class="contentInner" id="genIDiv">
                <div><span>Gender: </span><?php echo $sex; ?></div>
                <div><span>Country: </span><?php echo $country; ?></div>
                <div><span>User Security: </span> <?php echo $userlevel; ?></div>
                <div><span>Member For: </span> <?php echo $memberfor; ?></div>
                <div><span>Last Seen: </span> <?php echo $lastsession; ?></div>
                <div><span>Birthday: </span> <?php echo $birthday; ?></div>
                <div><span>Age: </span><?php echo $age; ?><?php echo $agestring; ?></div>
                <?php if($state != ""){ ?>
                <div><span>State/Province: </span><?php echo $state; ?></div>
              <?php } ?>
             
              <?php if($city != ""){ ?>
                <div><span>City/Town: </span><?php echo $city; ?></div>
              <?php } ?>
             
             
              <?php if($nd_day != "" && $nd_month != ""){ ?>
                <div><span>Name day: </span><?php echo $nd_day.", ".$nd_month; ?></div>
              <?php } ?>

              <?php if($quotes != ""){ ?>
                <div><span>Favourite quotes: </span><?php echo $quotes; ?></div>
              <?php } ?>
              <?php if($sey == "" && $country =="" && $userlevel == "" && $memberfor == "" && $lastsession == "" && $birthday == "" && $agestring == "" && $state == ""&& $city == "" && $nd_day == "" && $quotes == ""){ ?>
                <p style="text-align: center; color: #999;">This user has not given any information yet</p>
              <?php }?>
            </div>
            <div class="clear"></div>

            <div class="contentInner" id="perIDiv">
                <?php if($hometown != ""){ ?>
                <div><span>Hometown: </span><?php echo $hometown; ?></div>
              <?php } ?>
          
          
              <?php if($fmovie != ""){ ?>
                <div><span>Favourite Movies: </span><?php echo $fmovie; ?> </div>
              <?php } ?>
          
          
              <?php if($fmusic != ""){ ?>
                <div><span>Favourite Songs/Music: </span><?php echo $fmusic; ?> </div>
              <?php } ?>
          
          
              <?php if($pstatus != ""){ ?>
               <div><span>Partnership Status: </span><?php echo $pstatus; ?> </div>
              <?php } ?>
          
          
              <?php if($politics != ""){ ?>
                <div><span>Political Views: </span><?php echo $politics; ?></div>
              <?php } ?>
          
          
              <?php if($religion != ""){ ?>
                <div><span>Religious views: </span><?php echo $religion; ?></div>
              <?php } ?>
          
          
              <?php if($interest != ""){ ?>
                <div><span>I'm interested in: </span><?php echo $interest; ?></div>
              <?php } ?>
          
          
              <?php if($language != ""){ ?>
                <div><span>Language: </span><?php echo $language; ?></div>
              <?php } ?>

              <?php if($hometown == "" && $fmovie == "" && $pstatus == "" && $politics == "" && $religion == "" && $interests == "" && $language == ""){ ?>
                <p style="text-align: center; color: #999;">This user has not given any information yet</p>
              <?php } ?>
            </div>
            <div class="clear"></div>

            <div class="contentInner" id="conIDiv">
                <?php if($mobile != ""){ ?>
                    <div><span>Mobile: </span><?php echo $mobile; ?> </div>
                  <?php } ?>
              
              
                  <?php if($notemail != ""){ ?>
                    <div><span>Email: </span><a href="<?php echo $emailURL; ?>"><?php echo $notemail; ?></a></div>
                  <?php } ?>
              
              
                  <?php if($website != ""){ ?>
                    <div><span>Website: </span><a href="<?php echo $website; ?>"><?php echo $website; ?></a></div>
                  <?php } ?>
              
              
                  <?php if($address != ""){ ?>
                    <div><span>Address: </span><?php echo $address; ?></div>
                  <?php } ?>

                  <?php if($mobile == "" && $notemail =="" && $website == "" && $address == ""){ ?>
                    <p style="text-align: center; color: #999;">This user has not given any information yet</p>
                  <?php }?>
            </div>
            <div class="clear"></div>

            <div class="contentInner" id="eduIDiv">
                <?php if($elemen != ""){ ?>
                    <div><span>Elementary School: </span><?php echo $elemen; ?></div>
                  <?php } ?>
              
              
                  <?php if($high != ""){ ?>
                    <div><span>High School: </span><?php echo $high; ?> </div>
                  <?php } ?>
              
              
                  <?php if($uni != ""){ ?>
                    <div><span>University: </span><?php echo $uni; ?> </div>
                  <?php } ?>
              
              
                  <?php if($profession != ""){ ?>
                    <div><span>Profession: </span><?php echo $works; ?></div>
                  <?php } ?>
              
              
                  <?php if($job != ""){ ?>
                    <div><span>Job: </span><?php echo $job; ?></div>
                  <?php } ?>
              
              
                  <?php if($degree != ""){ ?>
                    <div><span>Degree, certificate: </span><?php echo $degree; ?></div>
                  <?php } ?>

                  <?php if($elemen == "" && $high =="" && $uni == "" && $profession == "" && $job == ""){ ?>
                    <p style="text-align: center; color: #999;">This user has not given any information yet</p>
                  <?php }?>
            </div>
            <div class="clear"></div>

            <div class="contentInner" id="aboIDiv">
                <?php if($about != ""){ ?>
                    <div><?php echo $about; ?></div>
                  <?php } ?>

                  <?php if($about == ""){ ?>
                    <p style="text-align: center; color: #999;">This user has not written anything interesting about them yet</p>
                  <?php }?>
            </div>
            <div class="clear"></div>
        </div>
        </div>
        </div>
      <?php if($log_username == $u && $user_ok == true){ ?>
        <div id="editAbout" class="infoHolder">

        <p class="txtc">Give information about yourself in 5 topics - education, profession, city, about me &amp; personal information, contact - to make your profile more recognizable for your friends and to make sure you are not a fake and unvalid user!<div class="pplongbtn profDDs" id="infodd">Give information about yourself<img src="/images/down-arrow.png" width="16" height="16" style="float: right;"></div><div style="display: none" id="artdd_div">&bull; You can easily edit your information by clicking on the <i>Edit Profile</i> button. There you can choose from 5 separated topics - click on the right ones to make the menu go down - and from over 20 smaller topics. It is highly recommended to fill in as many gaps as you can because we (and other users also) prefer those users who has more information about them. The more information you give the more people will trust you. However do NOT give any private or confidental information like your password, log in email address, credit card number etc. We cannot take any resposibilities for you if you release these infos in public.<br />&bull; If you cannot fill in a gap - for instance you have not graduated yet from a university - just leave it as blank (this time there will be nothing displayed) or you can write something like <i>not graduated yet</i> or <i>none</i> (this time the information will be displayed with your given value). </div></p>
      
      <form name="editprofileform" id="editprofileform" class="ppForm" onsubmit="return false;">

        <!-- EDUCATION -->
        <button class="main_btn_fill fixRed" id="education" onclick="openDD('edu')">Education</button>
        
        <!-- PROFESSION -->
        <button class="main_btn_fill fixRed" id="profession" onclick="openDD('pro_')">Profession</button>

        <!-- CITIES -->
        <button class="main_btn_fill fixRed" id="citydiff" onclick="openDD('city_')">City</button>

        <!-- ABOUT ME -->
        <button class="main_btn_fill fixRed" id="aboutmepi" onclick="openDD('me')">About me &amp; personal information</button>

        <!-- CONTACT -->
        <button class="main_btn_fill fixRed" id="contactf" onclick="openDD('con')">Contact</button>

        <!-- GEOLOCATION -->
        <button class="main_btn_fill fixRed" onclick="openDD('geoLoc')">Geolocation</button>
      </form>

      <hr class="dim" id="showHr" style="display: none;">

      <div class="ppddHolder">
            <div id="edu" style="display: none;">
                <input id="elemen" type="text" placeholder="Elementary School" onfocus="emptyElement('status')" maxlength="150" value="<?php echo $elemen; ?>">

                <input id="high" type="text" placeholder="High School" onfocus="emptyElement('status')" maxlength="150" value="<?php echo $high; ?>">

                <input id="uni" type="text" placeholder="University" onfocus="emptyElement('status')" maxlength="150" value="<?php echo $uni; ?>">

                <input id="degree" type="text" placeholder="Degree" onfocus="emptyElement('status')" maxlength="150" value="<?php echo $degree; ?>">
            </div>

            <div id="pro_" style="display: none;">
                <input id="job" type="text" placeholder="Job" onfocus="emptyElement('status')" maxlength="150" value="<?php echo $job; ?>">

                <select id="profession_sel" onfocus="emptyElement('status')">
                    <option value="" selected="true" disabled="true">Choose profession</option>
                    <option value="s" <?php echo $profsels; ?>>Student</option>
                    <option value="w" <?php echo $profselw; ?>>Working</option>
                    <option value="r" <?php echo $profselr; ?>>Retired</option>
                    <option value="u" <?php echo $profselu; ?>>Unemployed</option>
                    <option value="o" <?php echo $profselo; ?>>Other</option>
                </select>
            </div>

            <div id="city_" style="display: none;">
                <input id="state" type="text" placeholder="State/Province" onfocus="emptyElement('status')" maxlength="150" value="<?php echo $state; ?>">

                <input id="city" type="text" placeholder="City" onfocus="emptyElement('status')" maxlength="150" value="<?php echo $city; ?>">

                <input id="hometown" type="text" placeholder="Hometown" onfocus="emptyElement('status')" maxlength="150" value="<?php echo $hometown; ?>">
            </div>

            <div id="me" style="display: none;">
                <textarea id="ta" onkeyup="statusMax(this,1000)" placeholder="About me" onfocus="emptyElement('status')"><?php echo $about; ?></textarea>
 
                <textarea id="movies" class="movie_music" placeholder="Favourite film" onkeyup="statusMax(this,400)"><?php echo $fmovie; ?></textarea>

                <textarea id="music" class="movie_music" placeholder="Favourite music" onkeyup="statusMax(this,400)"><?php echo $fmusic; ?></textarea>

                <textarea id="quotes" class="movie_music" placeholder="Favourite quotes" onkeyup="statusMax(this,400)"><?php echo $cleanqu; ?></textarea>

                <input id="pstatus" type="text" placeholder="Partnership status" value="<?php echo $pstatus; ?>">

                <input id="politics" type="text" placeholder="Political views" value="<?php echo $politics; ?>">

                <input id="religion" type="text" placeholder="Religion" value="<?php echo $religion; ?>">

                <input id="language" type="text" placeholder="Languages" value="<?php echo $language; ?>">

                <select id="nd_day" onfocus="emptyElement('status')">
                    <option value="" selected="true", disabled="true">Nameday day</option>
                    <?php require_once 'template_day_list.php'; ?>
                </select>
                <select id="nd_month" onfocus="emptyElement('status')">
                        <option value="" selected="true", disabled="true">Nameday month</option>
                        <option value="January" <?php echo $ndmonja; ?>>January</option>
                        <option value="February" <?php echo $ndmonfe; ?>>February</option>
                        <option value="March" <?php echo $ndmonrh; ?>>March</option>
                        <option value="April" <?php echo $ndmonap; ?>>April</option>
                        <option value="May" <?php echo $ndmonma; ?>>May</option>
                        <option value="June" <?php echo $ndmonju; ?>>June</option>
                        <option value="July" <?php echo $ndmonjul; ?>>July</option>
                        <option value="August" <?php echo $ndmona; ?>>August</option>
                        <option value="September" <?php echo $ndmons; ?>>September</option>
                        <option value="October" <?php echo $ndmono; ?>>October</option>
                        <option value="November" <?php echo $ndmonn; ?>>November</option>
                        <option value="December" <?php echo $ndmond; ?>>December</option>
                </select>

                <input id="interest" type="text" placeholder="Interested in ..." value="<?php echo $interest; ?>">
            </div>

            <div id="con" style="display: none;">
                <input id="mobile" type="text" placeholder="Mobile number" onfocus="emptyElement('status')" maxlength="150" value="<?php echo $mobile; ?>">

                <input id="notemail" type="email" placeholder="Email address" onfocus="emptyElement('status')" maxlength="150" value="<?php echo $notemail; ?>">

                <input id="website" type="text" placeholder="Website URL" onfocus="emptyElement('status')" maxlength="150" value="<?php echo $website; ?>">

                <input id="address" type="text" placeholder="Address" onfocus="emptyElement('status')" maxlength="150" value="<?php echo $address; ?>">
            </div>

            <div id="geoLoc" style="display: none;">
                <button class="main_btn_fill fixRed" onclick="getLocation()">Locate me</button>
                <span style="margin: 10px;">Longitude: <span id="lon_update">not set</span></span>
                <span style="margin: 10px;">Latitude: <span id="lat_update">not set</span></span>
            </div>
        </div>
        <div class="clear"></div>
        <div id="status"></div>
      <span id="after_status"></span>

      <div id="appendLoc">
        <span id="mapholder_update" style="margin-top: 7px;"></span>
        <span id="update_coords" style="margin-top: 7px;"></span>
    </div>

      <button id="editbtn" class="main_btn" style="display: none; margin: 0 auto; margin-top: 10px; padding: 7px;" onclick="editChanges()">Save changes</button>
      <button id="geolocBtn" class="main_btn" style="display: none; margin: 0 auto; margin-top: 10px; padding: 7px;" onclick="saveNewGeoLoc()">Save changes</button>
      </div>

      </div>
      <?php } ?>
            <form id="writearticle" name="writearticle" onsubmit="return false;">
                <p style="font-size: 22px; color: #999;" class="txtc">Create an article</p>
                <p style="color: #999; text-align: center; font-size: 14px;">Before writing an article please make sure you read the 'How to write a proper and well-recieved article?' section</p>
                <textarea name="title" id="title" type="text" maxlength="100" placeholder="Article Title"></textarea>
                <div class="toolbar">
                  <a onclick="execCmd('bold')"><i class='fa fa-bold'></i></a>
                  <a onclick="execCmd('italic')"><i class='fa fa-italic'></i></a>
                  <a onclick="execCmd('underline')"><i class='fa fa-underline'></i></a>
                  <a onclick="execCmd('strikeThrough')"><i class='fa fa-strikethrough'></i></a>
                  <a onclick="execCmd('justifyLeft')"><i class='fa fa-align-left'></i></a>
                  <a onclick="execCmd('justifyCenter')"><i class='fa fa-align-center'></i></a>
                  <a onclick="execCmd('justifyRight')"><i class='fa fa-align-right'></i></a>
                  <a onclick="execCmd('justifyFull')"><i class='fa fa-align-justify'></i></a>
                  <a onclick="execCmd('cut')"><i class='fa fa-cut'></i></a>
                  <a onclick="execCmd('copy')"><i class='fa fa-copy'></i></a>
                  <a onclick="execCmd('indent')"><i class='fa fa-indent'></i></a>
                  <a onclick="execCmd('outdent')"><i class='fas fa-outdent'></i></a>
                  <a onclick="execCmd('subscript')"><i class='fa fa-subscript'></i></a>
                  <a onclick="execCmd('superscript')"><i class='fa fa-superscript'></i></a>
                  <a onclick="execCmd('undo')"><i class='fa fa-undo'></i></a>
                  <a onclick="execCmd('redo')"><i class='fas fa-redo'></i></a>
                  <a onclick="execCmd('insertUnorderedList')"><i class='fa fa-list-ul'></i></a>
                  <a onclick="execCmd('insertOrderedList')"><i class='fa fa-list-ol'></i></a>
                  <a onclick="execCmd('insertParagraph')"><i class='fa fa-paragraph'></i></a>
                  <select class="ssel sselArt" style="width: 85px; margin-top: 5px; background-color: #fff;" onchange="execCmdWithArg('formatBlock', this.value)" class="font_all">
                    <option value="" selected="true" disabled="true">Heading</option>
                    <option value="H1">H1</option>
                    <option value="H2">H2</option>
                    <option value="H3">H3</option>
                    <option value="H4">H4</option>
                    <option value="H5">H5</option>
                    <option value="H6">H6</option>
                  </select>
                  <a onclick="execCmd('insertHorizontalRule')">HR</a>
                  <a onclick="execCmd('createLink', prompt('Enter URL', 'https://'))"><i class='fa fa-link'></i></a>
                  <a onclick="execCmd('unlink')"><i class='fa fa-unlink'></i></a>
                  <a onclick="toggleSource()"><i class='fa fa-code'></i></a>
                  <a onclick="toggleEdit()"><i class="fas fa-edit"></i></a>
                  <select class="ssel sselArt" style="width: 85px; margin-top: 5px; background-color: #fff;" onchange="execCmdWithArg('fontName', this.value)" id="font_name">
                    <option value="" selected="true" disabled="true">Font style</option>
                    <option value="Arial">Arial</option>
                    <option value="Comic Sans MS">Comic Sans MS</option>
                    <option value="Courier">Courier</option>
                    <option value="Georgia">Georgia</option>
                    <option value="Helvetica">Helvetica</option>
                    <option value="Thaoma">Thaoma</option>
                    <option value="Palatino Linotype">Palatino Linotype</option>
                    <option value="Arial Black">Arial Black</option>
                    <option value="Lucida Sans Unicode">Lucida Sans Unicode</option>
                    <option value="Trebuchet MS">Trebuchet MS</option>
                    <option value="Courier New">Courier New</option>
                    <option value="Lucida Console">Lucida Console</option>
                    <option value="Times New Roman">Times New Roman</option>
                  </select>
                  <select class="ssel sselArt" style="width: 85px; margin-top: 5px; background-color: #fff;" onchange="execCmdWithArg('formatSize', this.value)" class="font_all">
                    <option value="" selected="true" disabled="true">Font size</option>
                    <option value="1">1</option>
                    <option value="2">2</option>
                    <option value="3">3</option>
                    <option value="4">4</option>
                    <option value="5">5</option>
                    <option value="6">6</option>
                    <option value="7">7</option>
                  </select>
                  <span>Fore Color: <input type="color" onchange="execCmdWithArg('foreColor', this.value)"/ style="vertical-align: middle; margin-top: -4px;"></span>
                  <span>Background Color: <input type="color" onchange="execCmdWithArg('hiliteColor', this.value)"/ style="vertical-align: middle; margin-top: -4px;"></span>
                  <a onclick="execCmd('selectAll')"><i class="fa fa-reply-all"></i></a>
                </div>
                <!-- Hide(but keep) normal textarea and place in the iFrame replacement for it -->
                <textarea style="display:none;" name="myTextArea" id="myTextArea" cols="100" rows="14"></textarea>
                <iframe name="richTextField" id="richTextField"></iframe>
                <!-- End replacing normal textarea -->
                <div id="art_sup_holder">
                <div id="ifmobalign">
                    <input type="text" id="keywords" maxlenght="150" placeholder="Give tags for your article e.g. freetime, food, sport, holdiday etc. separated by a comma" style="width: 100%; background-color: #fff;" class="pmInput">

                    <select id="art_cat" class="ssel" style="width: 100%; background-color: #fff;">
                        <option value="" selected="true" disabled="true">Choose category</option>
                        <option value="School">School</option>
                        <option value="Business">Business</option>
                        <option value="Learning">Learning</option>
                        <option value="My Dreams">My Dreams</option>
                        <option value="Money">Money</option>
                        <option value="Sports">Sports</option>
                        <option value="Technology">Technology</option>
                        <option value="Video Games">Video Games</option>
                        <option value="TV programmes">TV programmes</option>
                        <option value="Hobbies">Hobbies</option>
                        <option value="Music">Music</option>
                        <option value="Freetime">Freetime</option>
                        <option value="Travelling">Travelling</option>
                        <option value="Books">Books</option>
                        <option value="Politics">Politics</option>
                        <option value="Movies">Movies</option>
                        <option value="Lifestyle">Lifestyle</option>
                        <option value="Food">Food</option>
                        <option value="Knowledge">Knowledge</option>
                        <option value="Language">Language</option>
                        <option value="Experiences">Experiences</option>
                        <option value="Love">Love</option>
                        <option value="Recipes">Recipes</option>
                        <option value="Personal Stories">Personal Stories</option>
                        <option value="Product Review">Product Review</option>
                        <option value="History">History</option>
                        <option value="Religion">Religion</option>
                        <option value="Entertainment">Entertainment</option>
                        <option value="News">News</option>
                        <option value="Animals">Animals</option>
                        <option value="Environment">Environment</option>
                        <option value="Issues">Issues</option>
                        <option value="The Future">The Future</option>
                    </select><br /><br />

                    <div class="noMarg">Pick up to 5 images that will appear in your article (optional):</div>

                    <div id="au1" style="border-radius: 20px;"><img src="/images/addimg.png" onclick="triggerUpload(event, 'art_upload1')" class="triggerBtnreply mob_square" /></div>
                    <span id="aimage1"></span>
                    <input type="file" name="file_array" id="art_upload1" onchange="doUploadGen('art_upload1', 'au1', '1')" accept="image/*" style="display: none;" />

                    <div id="au2" style="border-radius: 20px;"><img src="/images/addimg.png" onclick="triggerUpload(event, 'art_upload2')" class="triggerBtnreply mob_square" /></div>
                    <span id="aimage2"></span>
                    <input type="file" name="file_array" id="art_upload2" onchange="doUploadGen('art_upload2', 'au2', '2')" accept="image/*" style="display: none;" />

                    <div id="au3" style="border-radius: 20px;"><img src="/images/addimg.png" onclick="triggerUpload(event, 'art_upload3')" class="triggerBtnreply mob_square" /></div>
                    <span id="aimage3"></span>
                    <input type="file" name="file_array" id="art_upload3" onchange="doUploadGen('art_upload3', 'au3', '3')" accept="image/*" style="display: none;" />

                    <div id="au4" style="border-radius: 20px;"><img src="/images/addimg.png" onclick="triggerUpload(event, 'art_upload4')" class="triggerBtnreply mob_square" /></div>
                    <span id="aimage4"></span>
                    <input type="file" name="file_array" id="art_upload4" onchange="doUploadGen('art_upload4', 'au4', '4')" accept="image/*" style="display: none;" />

                    <div id="au5" style="border-radius: 20px;"><img src="/images/addimg.png" onclick="triggerUpload(event, 'art_upload5')" class="triggerBtnreply mob_square" /></div>
                    <span id="aimage5"></span>
                    <input type="file" name="file_array" id="art_upload5" onchange="doUploadGen('art_upload5', 'au5', '5')" accept="image/*" style="display: none;" />

                    <div class="clear"></div><br>
                    <div class="art_yel_help">
                        <b id="guideArt"><p style="margin-top: 0px;" class="noMarg">How to write a proper and well-received article?</p></b>
                        <div class="fhArt" id="guideArtDD">
                            <p>In order to write a good article you have to keep in mind the following things and instructions:</p><br>
                            <p>1. Once you have choosed a topic do a research of that to get a clear picture and enough knowledge</p>
                            <p>2. Create a strong, unique title that will describe your article in a few words and will grab the readers&#39; attention</p>
                            <p>3. Divide your article into more (at least 3) paragraphs: <i>introducion</i>, <i>main part</i>, <i>conclusion</i></p>
                            <p>4. Write major points</p>
                            <p>5. Write your article first and edit it later</p>
                            <b><p>Structure of a well-written formal article</p></b>
                            <p>The <i>introducion:</i></p>
                            <p style="margin: 0px;">it is one of the most essential part of the article - grab the attention of your readers, hook them in.</p>
                            <p style="font-size: 12px !important; margin-left: 20px;">Use drama, emotion, quotations, rhetorical questions, descriptions, allusions, alliterations and methapors.</p><br>
                            <p> The <i>main part(s):</i></p>
                            <p>this part of the article needs to stick to the ideas or answer any questions raised in the intoducion</p>
                            <p style="font-size: 12px !important; margin-left: 20px;">Try to maintain an "atmosphere" / tone / distinctive voice throughout the writing.</p><br>
                            <p>The <i>conclusion:</i></p>
                            <p>it is should be written to help the reader remember the article. Use a strong punch-line.</p>
                        </div>
                    </div>
                    <div class="art_yel_help">
                        <b id="whatAre"><p style="margin-top: 0px;" class="noMarg">What are tags, categories and attachable images?</p></b>
                        <div class="fhArt" id="wharAreDD">
                            <p>In the interest of creating a unique and "colorful" article you need to give tags and choose a category for it.</p><br>
                            <b><p style="margin-top: 0px;">Tags:</p></b>
                            <p>Tags are short words that describes your article in a fast way. People just read them through and they will immediately know what is it about. For instance if you have an article about computers your tags can be <i>technology, computers, #nerd, motherboard</i> etc.</p><br>
                            <b><p style="margin-top: 0px;">Category:</p></b>
                            <p>The category is just a simple classification that your article has. It tells the readers what is your article about and it will also appear in a picture.</p><br>
                            <b><p style="margin-top: 0px;">Attachable images:</p></b>
                            <p>You can attach up to 5 images to your article in order to make it more visually, helpful and picturesque. It is an optional avalibility but it is highly recommended to attach at least one picture to your article. If you do not attach any images nothing will appear instead of this. <br><b>Important: </b>the rules are the same as with the standard image uploading. The maximum image size is 5MB and the allowed image extenstions are jpg, jpeg, gif and png. For more information please visit the <a href="/help">help</a> page.</p>
                        </div>
                    </div>
                    <p style="color: #999;" class="admitP">I admit that my article will be public, everyone can read it in order to get new information or for entertainment purposes</p>
                    <button id="article_btn" class="main_btn_fill fixRed" onclick="saveArticle()" style="margin-bottom: 10px;">Create Article</button>
                    <span id="status_art"></span>
                    <hr class="dim hideForm">
                </div>
                <?php if($wart != ""){ ?></div><?php } ?>
                <div id="img_holder_a">
                    <br>
                    <div>
                        <div style="margin-bottom: 10px;" class="artImgs">
                            <p>Do research and a plan for your article (<a href="http://www.e-custompapers.com/blog/practical-tips-for-article-reviews.html" target="_blank">source</a>)</p>
                            <img src="/images/howtoart.jpg" onclick="articleGuide('howtoart.jpg')" style="border-radius: 20px; box-sizing: border-box;">
                        </div>
                        <div class="artImgs">
                            <p>The parts of a well-written article (<a href="https://apessay.com/order/?rid=ea55690ca8f7b080" target="_blank">source</a>)</p>
                            <img src="/images/partsa.jpg" onclick="articleGuide('partsa.jpg')" style="border-radius: 20px; box-sizing: border-box;">
                        </div>
                    </div>
                </div>
            </div>
            <div class="clear"></div>
        </form>
        <div id="article_show">
            <div id="friendsAbout" class="infoHolder">

            <div id="data_holder">
                <div style="padding-top: 0;">
                    <div><span><?php echo $friend_count; ?></span> friends</div>
                    <div><span><?php echo $online_count; ?></span> online</div>
                </div>
            </div>

            <div class="contactHolder">
                <?php if($u != $log_username){ ?>
                    <span id="friendBtn"><?php echo $friend_button; ?></span>
                    <span id="blockBtn"><?php echo $block_button; ?></span>
                <?php } ?>

                <?php if($u == $log_username && $user_ok == true){ ?>
                    <button class="main_btn_fill fixRed" onclick="location.href = '/friend_suggestions'">More friends</button>
                    <button class="main_btn_fill fixRed" onclick="location.href = '/invite'">Invite friends</button>
                <?php } ?>

                <button class="main_btn_fill fixRed" onclick="location.href = '/view_friends/<?php echo $u; ?>'">View all friends</button>
            </div>
            <hr class="dim">
            <?php if($isFriend == true && $log_username != $u){ ?><p style="color: #999;" class="txtc">You are friends with <?php echo $u; ?><?php } ?></p>
          <?php if($log_username != $u){ ?><p style="color: #999;" class="txtc">You have <?php echo $resincomm; ?> friend(s) in common with <?php echo $u; ?></p><?php } ?>
            <?php echo $friends_view_all_link; ?>
        
            <?php
                if($isOwner == "Yes"){
                    echo '<p style="color: #999;" class="txtc">My friends</p>';
                }else{
                    echo '<p style="color: #999;" class="txtc">'.$u.'&#39s friends</p>';
                }
            ?>
          <div class="flexibleSol">
            <?php echo $friendsHTML; ?>
        </div>
      </div>
          <div id="photosAbout" class="infoHolder">

        <div id="data_holder">
            <div style="padding-top: 0;">
                <div><span><?php echo $count_all; ?></span> photos</div>
            </div>
        </div>

        <div class="contactHolder">
            <button class="main_btn_fill fixRed" onclick="window.location = '/photos/<?php echo $u; ?>'">View Photos</button>
        </div>
        <br>
          <?php 
            if($isOwner == "Yes"){
                echo '<div class="pplongbtn profDDs" id="imgdd">Information about uploading photos<img src="/images/down-arrow.png" width="16" height="16" style="float: right;"></div><div style="display: none;" id="artdd_div">&bull; You can upload photos by clicking on the <i>See My Photos</i> button in the dropdown menu. Keep in mind that a photo maximum can be 5MB and the website only supports jpg, jpeg, png and gif extensions. You can also give a short description up to 1,000 characters where you can write some important, exciting and/or essential information about the certain photo. Once if you uploaded your photo you can check it that it was uploaded to the right gallery - if not, reupload it to the right one.<br />&bull; If you click on a photo you can see that in a <i>bigger view</i> with more detailed information about it - like description, upload date etc. There you can also check your related videos wich is based on your friends suggestions, random photos and on those ones that has any connections with you and/or your photos. Down below you can write a post, comment, send emojis and attach images, too. We want you to behave as a civilized person and please do not post any harmful or spam messages.<br />&bull; If you want to use someone else&#39;s photo for anything you have to get an agreement from the owner of the photo. Without it you might break some laws and harm someone&#39;s photo privacy!</div><br>';
            }
          ?>

          <div class="flexibleSol"><?php echo $echo_photos; ?></div>
          <?php if($echo_photos == ""){ ?>
            <p style="color: #999;" class="txtc">It seems that there are no uploaded photos found</p>
          <?php } ?>
          <div class="clear"></div>
      </div>
          <div class="clear"></div>

          <div id="articlesAbout" class="infoHolder">

            <div id="data_holder">
                <div style="padding-top: 0;">
                    <div><span><?php echo $count_arts; ?></span> articles</div>
                    <div><span><?php echo $count_likes; ?></span> likes</div>
                    <div><span><?php echo $count_favs; ?></span> favourites</div>
                </div>
            </div>

          <div class="contactHolder">
              <?php if($isOwner == "Yes"){ ?><?php echo $article; ?><?php } ?>
              <button class="main_btn_fill fixRed" onclick="location.href = '/all_articles/<?php echo $u; ?>'">View articles</button>
            </div>
            <br>
              <?php if($isOwner == "Yes"){
                echo "<div class='pplongbtn profDDs' id='artdd'>How can I write an article?<img src='/images/down-arrow.png' width='16' height='16' style='float: right;'></div><div style='display: none;' id='artdd_div'>&bull; When you give a title for your article try to be specific and clean.<br />&bull; If you write an article you are able to edit it as in a text editor where you can attach images, give custom font and text style etc. Despite of the fact that is quite good try NOT to use too much of these features, because this might make your article unreadable! (Examples for text editing: <b>Bold text</b>, <i>Italic text</i>, <u>Underlined text</u>, attach images, change font style etc.)<br />&bull; You completely have the freedom to tell your own opinion, share your ideas and debate with others in a <b>civilized</b> way. We want you to not send harmful messages and/or spam!<br />&bull; You are also able to like articles with the <i>heart</i> icon or add an article as your <i>favourite</i>. By liking or add an article as a favourite you agree that we can send notifications for your friends to keep up to date with you and to show them your interests.<br />&bull; When you edit your own articles - because you can edit your owns - you have a full control over it, therefore you can rewrite some part of the article it that is not actual any more , attach new images and give a new title or just correct the existing one. Nonetheless, you cannot edit the tags and the category or delete the existing images. If you want to change tons of things on your article do NOT do it! Write a new one instead!<br>&bull; Be careful by deleting your articles. Once if you deleted it&#39;s gone and we will be able to bring back it again!<br />&bull; You can also print your articles by clicking on the <i>Print article</i> button at the bottom. These printed articles can freely used to read or learn from it but selling of these can may be illegal without the author&#39;s agreement! If you use these anywhere please link the source and the author&#39;s name.<br />&bull; The <i>Related articles</i> based on your friends recently written or on those articles that has a connection with yours - it can be the same tags, title, similar writing style or the topic you wrote about.</div><br>";
                }
              ?>
              <div class="flexibleSol" id="userFlexArts">
                  <?php echo $echo_articles; ?>
              </div>
              <?php if($echo_articles == ""){ ?>
                <p class="txtc" style="color: #999;">It seems that there are no articles written</p>
              <?php } ?>
          <div class="clear"></div>
      </div>
          <div id="videosAbout" class="infoHolder">

            <div id="data_holder">
                <div style="padding-top: 0;">
                    <div><span><?php echo $count_vids; ?></span> videos</div>
                    <div><span><?php echo $count_vid_likes; ?></span> likes</div>
                </div>
            </div>

            <div class="contactHolder">
                <button class="main_btn_fill fixRed" onclick="location.href = '/videos/<?php echo $u; ?>'">View videos</button>
            </div>
            <br>
            <?php if($isOwner == "Yes"){echo "<div class='pplongbtn profDDs' id='vhelp'>Information for uploading videos<img src='/images/down-arrow.png' width='16' height='16' style='float: right;'></div><div style='display: none;' id='artdd_div'>&bull; You can upload a video by clicking on the <i>See My Videos</i> link in the dropdown menu. Before you upload a video you can give a name, a description and a poster that will be the background for your video (if you upload an auido file like MP3 this image will be seeable in all the video). That was mentioned here - name, description, poster - is not a requirement, it&#39;s optional.<br />&bull; The maxmimum file size that you can upload as a video is 50MB, the file extensions that are supported: mp3, mp4, webm and ogg. For the poster it is the same as for the photos: maximum file size: 5MB, and the supported types are jpg, jpeg, png, and gif. The maximum length of video description is 1,000 characters and 150 for the name.<br />&bull; We also collected <i>Related videos</i> for you that is based on your friends&#39; videos or if you do not have any we display videos that somehow can be connected to you.<br />&bull; You can also comment, post share images and send emojis below your friends&#39; videos in the comment section. Please be faithful to others and do not spam anything there.<br />&bull; If you need any help have a look at our <a href='/help'>help</a> page or ask a question.</div><br />";
            }else{ ?>

            <?php } ?>
            <div class="flexibleSol" id="userFlexArts"><?php echo $videos; ?></div>
            <div class="clear"></div>
      </div>
          <div id="flsAbout" class="infoHolder">

          <div id="data_holder">
              <div style="padding-top: 0;">
                  <div><span><?php echo $follower_count; ?></span> followers</div>
                  <div><span><?php echo $following_count; ?></span> followings</div>
              </div>
          </div>

          <div class="contactHolder">
              <?php if($log_username != $u){ echo $isFollowOrNot; }?>
                <?php if($isOwner == "No" && $log_username != ""){ ?>
                <span id="followBtn"><?php echo $follow_button; ?></span>
              <?php } ?>
          </div>

          <div id="follow_count">
            <p style="color: #999;" class="txtc">Followers</p>
            <div class="flexibleSol"><?php echo $following_div; ?></div>

            <?php if($following_div == "" && $isOwner == "Yes"){ ?>
                <p style="color: #999; font-size: 14px;" class="txtc">It seems that you have no followers at the moment</p>
            <?php }else if($following_div == "" && $isOwner == "No"){ ?>
                <p style="color: #999; font-size: 14px;" class="txtc">It seems that <?php echo $u; ?> has no followers at the moment</p>
            <?php } ?>
            <hr class="dim">

            <p style="color: #999;" class="txtc">Followings</p>

            <div class="flexibleSol"><?php echo $other_div; ?></div>

          </div>

      </div>
          <?php if($user_ok == true && $isOwner == "Yes"){ ?>
            <div id="bcgAbout" class="infoHolder">

          <div class="pplongbtn profDDs" id="bgdd">How to change background?<img src="/images/down-arrow.png" width="16" height="16" style="float: right;"></div>
          <div style="display: none;" id="artdd_div">
            <p style="font-size: 14px; margin: 0px;">&bull; Your background will function like a cover image on your profile that everyone can see when they go to your profile. The maximum file size that you can upload is 5Mb. If your image is larger than this and if it has a png format please try to convert it into jpg or jpeg which reserves less space and memory (in order to avoid any misunderstands you can still upload png formats it is only our request)<br />&bull; The optimal image size for the background is 1200 x 300 pixels otherwise, it will be automaticly resized. The 1200 x 350 image resolution is quite wide and narrow - it is not the typical image format - but we try to resize your image and bring out of the best from it. There also can be problems width the pixel size of the image. If it&#39;s too small - to fill out the 1200 x 300 resolution - we overstate your image which may occur that the pixels will be more visible and it might make the image&#39;s quality worse. On the other hand, if your image is too small we will try to reduce the size - which means that we crop out or try to reduce the size in proportion - and it can also make worse the resolution. If you feel that your image doesn&#39;t look nice and great you can choose from the 9 built-in background which are completlety different from each other and perfectly sized to the background image box.<br />&bull; After these if you couldn&#39;t upload your background feel free to visit our <a href="/help">help</a> page or ask a question.</p>
          </div>
          <br>
          <div class="contactHolder">
            <?php echo $background_form; ?>
        </div>
        <hr class="dim">
          <p style="color: #999;" class="txtc" id="builtInBg" onclick="showBiBg()">Built-in backgrounds</p>
          <div id="statusbig" style="display: none;">
              <div class="bibg genBg lazy-bg" data-src="/images/universebi.jpg" onclick="uploadBiBg('universe')">
                  <p>Universe</p>
              </div>

              <div class="bibg genBg lazy-bg" data-src="/images/flowersbi.jpg" onclick="uploadBiBg('flowers')">
                  <p>Flowers</p>
              </div>

              <div class="bibg genBg lazy-bg" data-src="/images/forestbi.jpg" onclick="uploadBiBg('forest')">
                  <p>Forest</p>
              </div>

              <div class="bibg genBg lazy-bg" data-src="/images/bubblesbi.jpg" onclick="uploadBiBg('bubbles')">
                  <p>Bubbles</p>
              </div>

              <div class="bibg genBg lazy-bg" data-src="/images/mountainsbi.jpg" onclick="uploadBiBg('mountains')">
                  <p>Mountains</p>
              </div>

              <div class="bibg genBg lazy-bg" data-src="/images/wavesbi.jpg" onclick="uploadBiBg('waves')">
                  <p>Beach</p>
              </div>

              <div class="bibg genBg lazy-bg" data-src="/images/stonesbi.jpg" onclick="uploadBiBg('stones')">
                  <p>Stones</p>
              </div>

              <div class="bibg genBg lazy-bg" data-src="/images/simplebi.jpg" onclick="uploadBiBg('simple')">
                <p>Simple Blue</p>
              </div>
          </div>
          <?php } ?>
            <div class="clear"></div>
      </div>
          <div id="grsAbout" class="infoHolder">

            <div id="data_holder">
                <div style="padding-top: 0;">
                    <div><span><?php echo $member_count; ?></span> as member</div>
                    <div><span><?php echo $creator_count; ?></span> groups created</div>
                </div>
            </div>
    
            <?php if($log_username == $u){ ?>
                <div class="contactHolder">
                    <button class="main_btn_fill fixRed" onclick="location.href = '/view_all_groups'">View groups</button>
                </div>
            <?php } ?>
            <br>
            <div id="userFlexArts" class="flexibleSol">
                <?php echo $echo_groups; ?>
            </div>
            <?php if($echo_groups == '<div class="clear"></div>'){ ?>
                <p class="txtc" style="color: #999;">It seems that no groups can be displayed here</p>
            <?php } ?>
          </fieldset>
      </div>
          <div class="clear"></div>
          <?php if($u == $log_username && $user_ok == true){ ?>
          <div id="groupModule"></div>
          <?php } ?>
          <?php if($log_username != "" && $isBlock != true){ ?><?php require_once 'template_pm.php'; ?><?php } ?>
          <hr class="dim">
          <?php if($isBlock == false){ ?><?php require_once 'template_status.php'; ?><?php }else{ ?><p style="color: #006ad8;" class="txtc">Alert: this user blocked you, therefore you cannot post on his/her profile!</p><?php } ?>
       </div>
       </div>
    </div>
    </div>
    <?php echo $npm; ?>
    <?php echo $wart; ?>
    
    <?php require_once 'template_pageBottom.php'; ?>
    <script type="text/javascript">
var _0x3b32 = ["video", "querySelectorAll", "length", "play", "addEventListener", "display", "style", "followed_usr", "block", "followed_15", "none", "linking_fw", "played", "paused", "pause", "update_coords", "geolocation", "getCurrentPosition", "innerHTML", "mapholder_update", '<img src="/images/rolling.gif" width="30" height="30"> <span style="font-size: 12px;">Loading your location</span>', "<p style='font-size: 14px;'>Geolocation is not supported by this browser.</p>", "latitude", "coords", 
"longitude", ",", "lat_update", "lon_update", "https://maps.googleapis.com/maps/api/staticmap?center=", "&zoom=14&size=300x200&key=AIzaSyCr5_w0vZzk39VbnJ8GWZcoZycl_gvr5w8", "<img src='", "' id='ugoogimg'>", "User denied the request for Geolocation.", "PERMISSION_DENIED", "Location information is unavailable.", "POSITION_UNAVAILABLE", "The request to get user location timed out.", "TIMEOUT", "An unknown error occurred.", "UNKNOWN_ERROR", "code", "", "not set yet", "<p style='font-size: 14px;'>Your longitude and latitude is missing ...</p>", 
"POST", "/php_parsers/geo_usr_parser.php", "onreadystatechange", "responseText", "update_geo_success", "<p style='font-size: 14px;'>You have successfully changed your location! Now you can refresh the page.</p>", "<p style='font-size: 14px;'>", "</p>", "<p style='font-size: 14px;'>Error</p>", "updateLat=", "&updateLon=", "send", "touchmove", "scale", "preventDefault", "touchend", "getTime", "text_"];
var _0x6c21 = ["video", "querySelectorAll", "length", "play", "addEventListener", "display", "style", "followed_usr", "block", "followed_15", "none", "linking_fw", "played", "paused", "pause", "update_coords", "geolocation", "getCurrentPosition", "innerHTML", "mapholder_update", '<img src="/images/rolling.gif" width="30" height="30"> <span style="font-size: 12px;">Loading your location</span>', "<p style='font-size: 14px;'>Geolocation is not supported by this browser.</p>", "latitude", "coords", 
"longitude", ",", "lat_update", "lon_update", "https://maps.googleapis.com/maps/api/staticmap?center=", "&zoom=14&size=300x200&key=AIzaSyCr5_w0vZzk39VbnJ8GWZcoZycl_gvr5w8", "<img src='", "' id='ugoogimg'>", "User denied the request for Geolocation.", "PERMISSION_DENIED", "Location information is unavailable.", "POSITION_UNAVAILABLE", "The request to get user location timed out.", "TIMEOUT", "An unknown error occurred.", "UNKNOWN_ERROR", "code", "", "not set yet", "<p style='font-size: 14px;'>Your longitude and latitude is missing ...</p>", 
"POST", "/php_parsers/geo_usr_parser.php", "onreadystatechange", "responseText", "update_geo_success", "<p style='font-size: 14px;'>You have successfully changed your location! Now you can refresh the page.</p>", "<p style='font-size: 14px;'>", "</p>", "<p style='font-size: 14px;'>An error occurred</p>", "updateLat=", "&updateLon=", "send"];
var videos = document.querySelectorAll("video");
var i = 0;
for (; i < videos[_0x6c21[2]]; i++) {
  videos[i][_0x6c21[4]](_0x6c21[3], function() {
    pauseAll(this);
  }, true);
}
function showFollowed() {
  _("followed_usr").style.display = "block";
  _("followed_15").style.display = "none";
  _("linking_fw").style.display = "none";
}
function hideFollowed() {
  _("followed_usr").style.display = "none";
  _("followed_15").style.display = "block";
  _("linking_fw").style.display = "block";
}
function pauseAll(callback) {
  var target = 0;
  for (; target < videos[_0x6c21[2]]; target++) {
    if (videos[target] != callback && videos[target][_0x6c21[12]][_0x6c21[2]] > 0 && !videos[target][_0x6c21[13]]) {
      videos[target][_0x6c21[14]]();
    }
  }
}
function getLocation() {
  var internalFoldl = _("update_coords");
  if (navigator.geolocation) {
    navigator.geolocation.getCurrentPosition(showPosition, showError);
    _("mapholder_update").innerHTML = '<img src="/images/rolling.gif" width="30" height="30">';
  } else {
    internalFoldl.innerHTML = "<p style='font-size: 14px;'>Geolocation is not supported by this browser.</p>";
  }
}
function showPosition(position) {
  var latitude = position.coords.latitude;
  var longitude = position.coords.longitude;
  position.coords.latitude;
  position.coords.longitude;
  _("lat_update").innerHTML = latitude;
  _("lon_update").innerHTML = longitude;
  position.coords.latitude;
  position.coords.longitude;
  _("mapholder_update").innerHTML = "<img src='https://maps.googleapis.com/maps/api/staticmap?center=" + position.coords.latitude + "," + position.coords.longitude + "&zoom=14&size=300x200&key=AIzaSyCr5_w0vZzk39VbnJ8GWZcoZycl_gvr5w8' id='ugoogimg'>";
}
function showError(e) {
  var internalFoldl = _("update_coords");
  switch(e[_0x6c21[40]]) {
    case e[_0x6c21[33]]:
      internalFoldl[_0x6c21[18]] = _0x6c21[32];
      break;
    case e[_0x6c21[35]]:
      internalFoldl[_0x6c21[18]] = _0x6c21[34];
      break;
    case e[_0x6c21[37]]:
      internalFoldl[_0x6c21[18]] = _0x6c21[36];
      break;
    case e[_0x6c21[39]]:
      internalFoldl[_0x6c21[18]] = _0x6c21[38];
  }
}
function saveNewGeoLoc() {
  var url = _("lat_update").innerHTML;
  var anchorPart = _("lon_update").innerHTML;
  var t = _("update_coords");
  if ("" == url || "not set yet" == url || "" == anchorPart || "not set yet" == anchorPart) {
    t.innerHTML = "<p style='color: #999; text-align: center;'>Your longitude and latitude is missing ...</p>";
  } else {
    var result = ajaxObj("POST", "/php_parsers/geo_usr_parser.php");
    result.onreadystatechange = function() {
      if (1 == ajaxReturn(result)) {
        if ("update_geo_success" == result.responseText) {
          t.innerHTML = "<p style='color: #999; text-align: center;'>You have successfully changed your location! Now you can refresh the page.</p>";
        } else {
          t.innerHTML = "<p style='color: #999; text-align: center;'>" + result.responseText + "</p>";
        }
      } else {
        t.innerHTML = "<p style='color: #999; text-align: center;'>An error occurred</p>";
      }
    };
  }
  result.send("updateLat=" + url + "&updateLon=" + anchorPart);
}

var lastTouchEnd = 0;
function startVidW(audioInstance) {
  if (audioInstance.paused) {
    audioInstance.play();
    _("text_" + id).style.display = "none";
  } else {
    audioInstance.pause();
    _("text_" + id).style.display = "block";
  }
}
function showVCB(e) {
  e.style.display = "block";
}
function hideVCB(e) {
  e.style.display = "none";
}
document.addEventListener("touchend", function(event) {
  var o = (new Date).getTime();
  if (o - 0 <= 300) {
    event.preventDefault();
  }
  lastTouchEnd = o;
}, false);
var isSafari = /^((?!chrome|android).)*safari/i.test(navigator.userAgent);
function getCookie(prefix) {
  var match = prefix + "=";
  var moveFromGal = decodeURIComponent(document.cookie).split(";");
  var j = 0;
  for (; j < moveFromGal.length; j++) {
    var def = moveFromGal[j];
    for (; " " == def.charAt(0);) {
      def = def.substring(1);
    }
    if (0 == def.indexOf(match)) {
      return def.substring(match.length, def.length);
    }
  }
  return "";
}
function setDark() {
  var e = "thisClassDoesNotExist";
  if (!document.getElementById(e)) {
    var o = document.getElementsByTagName("head")[0];
    var t = document.createElement("link");
    t.id = e;
    t.rel = "stylesheet";
    t.type = "text/css";
    t.href = "/style/dark_style.css";
    t.media = "all";
    o.appendChild(t);
  }
}
if ("none" == _("hide_it").style.display) {
  window.onbeforeunload = function() {
    line.elements.myTextArea.value = window.frames.richTextField.document.body.innerHTML;
    let iframeVal = line.elements.myTextArea.value;

    if ("" != _("title").value || "" != hasImageGen1 || "" != hasImageGen2 || "" != hasImageGen3 || "" != hasImageGen4 || "" != hasImageGen5 || "" != _("keywords").value || "" != iframeVal || "" != _("art_cat").value) {
      return "You have unsaved changes left. If you leave the page without saving your article, it will be lost!";
    }
  };
}
let pmsub = _("pmsubject");
let pmtxt = _("pmtext");
if ((pmsub && pmtxt) != undefined) {
  window.onbeforeunload = function() {
    if ("" != _("pmsubject").value || "" != _("pmtext").value) {
      return "You have unsaved changes left. If you leave the page without saving your private message, it will be lost!";
    }
  };
}
var isdarkm = getCookie("isdark");
if ("yes" == isdarkm) {
  setDark();
}
w = window;
var d = document;
var e = d.documentElement;
var g = d.getElementsByTagName("body")[0];
var y = (x = w.innerWidth || e.clientWidth || g.clientWidth, w.innerHeight || e.clientHeight || g.clientHeight);
var h = (h = window.innerHeight) / 2.35;
function showForm() {
  if ("block" == _("pmform").style.display) {
    _("pmform").style.display = "none";
  } else {
    _("pmform").style.display = "block";
    if (0 == mobilecheck) {
      _("pmform").style.height = h + "px";
    } else {
      _("pmform").style.height = y + "px";
    }
  }
}
function closePM() {
  _("pmform").style.display = "none";
}
y = y / 2;
let menuArray = ["userFriends", "userPhotos", "userArticles", "userInfo", "userVideos", "userGroups", "userFollowers"];
let contentArray = ["friendsAbout", "photosAbout", "articlesAbout", "aboutInfo", "videosAbout", "grsAbout", "flsAbout"];
if (_("userBackground") != undefined) {
  menuArray.push("userBackground");
  contentArray.push("bcgAbout");
}
if (_("userPm") != undefined) {
  menuArray.push("userPm");
}
if (_("userEdit") != undefined) {
  menuArray.push("userEdit");
  contentArray.push("editAbout");
}
let ui = _("userInfo");
ui.style.borderBottom = "2px solid #999";
for (let i = 0; i < menuArray.length; i++) {
  let openLoginScreenBtn = _(menuArray[i]);
  let THREAD_STARTED = menuArray[i];
  openLoginScreenBtn.addEventListener("click", function(canCreateDiscussions) {
    ufHandler(THREAD_STARTED);
  });
}
window.addEventListener("resize", vidRes);
function vidRes(ctnType) {
  if (ctnType == "userVideos") {
    if (_("nndd") != undefined) {
      var w = _("nndd").offsetWidth;
      if (0 == mobilecheck) {
        w = w - 4;
      } else {
        w = w - 4.5;
      }
    }
    var subMenuObjs = document.getElementsByClassName("pcjti");
    for (let i = 0; i < subMenuObjs.length; i++) {
      subMenuObjs[i].style.width = w + "px";
    }
  }
}
$("#infodd").on("click", function() {
  $(this).next().slideToggle("fast");
});
$("#imgdd").on("click", function() {
  $(this).next().slideToggle("fast");
});
$("#artdd").on("click", function() {
  $(this).next().slideToggle("fast");
});
$("#vhelp").on("click", function() {
  $(this).next().slideToggle("fast");
});
$("#bgdd").on("click", function() {
  $(this).next().slideToggle("fast");
});

if(window.innerWidth <= 500){
    $("#whatAre").on("click", function() {
      $(this).next().slideToggle("fast");
    });
    $("#guideArt").on("click", function() {
      $(this).next().slideToggle("fast");
    });
}
function ufHandler(type) {
  let undefined = "";
  if (type == "userFriends") {
    undefined = "friendsAbout";
  } else {
    if (type == "userPhotos") {
      undefined = "photosAbout";
    } else {
      if (type == "userArticles") {
        undefined = "articlesAbout";
      } else {
        if (type == "userInfo") {
          undefined = "aboutInfo";
        } else {
          if (type == "userVideos") {
            undefined = "videosAbout";
          } else {
            if (type == "userGroups") {
              undefined = "grsAbout";
            } else {
              if (type == "userBackground") {
                undefined = "bcgAbout";
              } else {
                if (type == "userEdit") {
                  undefined = "editAbout";
                } else {
                  if (type == "userFollowers") {
                    undefined = "flsAbout";
                  }
                }
              }
            }
          }
        }
      }
    }
  }
  if (undefined != "") {
    _(undefined).style.display = "block";
  }
  _(type).style.borderBottom = "2px solid #999";
  for (let i = 0; i < menuArray.length; i++) {
    if (menuArray[i] != type) {
      _(menuArray[i]).style.borderBottom = "0px";
    }
  }
  for (let i = 0; i < contentArray.length; i++) {
    if (contentArray[i] != undefined) {
      _(contentArray[i]).style.display = "none";
    }
  }
  vidRes(type);
}
if (void 0 != _("slide2")) {
  var forward = _("slide2");
  forward.onmousedown = function() {
    sideScroll(_("userNavbar"), "right", 15, 220, 20);
  };
}
if (void 0 != _("slide1")) {
  var back = _("slide1");
  back.onmousedown = function() {
    sideScroll(_("userNavbar"), "left", 15, 220, 20);
  };
}
function sideScroll(left, right, t, i, delta) {
  let scrollAmount = 0;
  var n = setInterval(function() {
    if ("left" == right) {
      left.scrollLeft -= delta;
    } else {
      left.scrollLeft += delta;
    }
    scrollAmount = scrollAmount + delta;
    if (scrollAmount >= i) {
      window.clearInterval(n);
    }
  }, t);
}
let ids = ["genI", "perI", "conI", "eduI", "aboI"];
function changeHeading(id) {
  for (let i = 0; i < ids.length; i++) {
    if (ids[i] == id) {
      _(id).style.borderBottom = "2px solid red";
      _(id).style.color = "#999";
      _(id + "Div").style.display = "flex";
    } else {
      _(ids[i]).style.borderBottom = "none";
      _(ids[i]).style.color = "#000";
      _(ids[i] + "Div").style.display = "none";
    }
  }
}
for (let j = 0; j < ids.length; j++) {
  _(ids[j]).addEventListener("click", function() {
    changeHeading(ids[j]);
  });
}
changeHeading("genI");
    </script>
</body>
<?php echo $pmw; ?>
</html>
