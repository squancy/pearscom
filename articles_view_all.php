<?php
    require_once 'php_includes/check_login_statues.php';
    require_once 'timeelapsedstring.php';
    require_once 'safe_encrypt.php';
    require_once 'phpmobc.php';
    require_once 'ccov.php';

    require_once 'headers.php';
    // Initialize any variables that the page might echo
    $ismobile = mobc();
    $u = "";
    $profile_pic = "";
    $profile_pic_btn = "";
    $avatar_form = "";
    $one = "1";
    if($ismobile != true){
        $max = 12;   
    }else{
        $max = 9;
    }
    $count_it = true;

    if(isset($_GET["u"])){
        $u = mysqli_real_escape_string($conn, $_GET["u"]);
    } else {
        header('Location: /usernotexist');
        exit();
    }

    $isOwner = "No";
    if($u == $log_username && $user_ok == true){
        $isOwner = "Yes";
    }

    // Select the member from the users table
    $sql = "SELECT * FROM users WHERE username=? AND activated=? LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss",$u,$one);
    $stmt->execute();
    $stmt->store_result();
    $stmt->fetch();
    $numrows = $stmt->num_rows;
    // Now make sure the user exists in the table
    if($numrows < 1){
        header('location: /usernotexist');
        exit();
    }
    $stmt->close();
    $sql_ = "SELECT * FROM users WHERE username=? AND activated=? LIMIT 1";
    $stmt = $conn->prepare($sql_);
    $stmt->bind_param("ss",$u,$one);
    $stmt->execute();
    $result = $stmt->get_result();
    while($row = $result->fetch_assoc()){
        $avatar = $row["avatar"];
    }

    $ppcs = "";
    if($avatar == NULL){
        $ppcs = '/images/avdef.png';
    }else{
        $ppcs = '/user/'.$u.'/'.$avatar;
    }
    $stmt->close();
    
    // Echo articles
    $echo_articles = "";
    $j = 0;
    $post_time = "";
    $written_by = "";
    // Create all_my_art array
    $all_my_art = array();
    $sql = "SELECT * FROM articles WHERE written_by=? ORDER BY post_time DESC LIMIT $max";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s",$u);
    $stmt->execute();
    $result = $stmt->get_result();
    if($result->num_rows > 0){
        while($row = $result->fetch_assoc()){
            $written_by = $row["written_by"];
            $title = stripslashes($row["title"]);
            $title = str_replace('\'', '&#39;', $title);
            $title = str_replace('\'', '&#34;', $title);
            $tags = $row["tags"];
            $cat = $row["category"];
            $post_time_ = $row["post_time"];
            $opt = $post_time_;
            $post_time = strftime("%b %d, %Y", strtotime($post_time_));
            $post_time_ = base64url_encode($post_time_,$hshkey);
            $written_by_original = urlencode($written_by);
            
            $sql = "SELECT COUNT(id) FROM fav_art WHERE art_uname = ? AND art_time = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ss",$log_username,$opt);
            $stmt->execute();
            $stmt->bind_result($cnt_fav);
            $stmt->fetch();
            $stmt->close();
            
            $sql = "SELECT COUNT(id) FROM heart_likes WHERE art_uname = ? AND art_time = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ss",$log_username,$opt);
            $stmt->execute();
            $stmt->bind_result($cnt_heart);
            $stmt->fetch();
            $stmt->close();
            
            $cover = chooseCover($cat);
   
            $echo_articles .= '<a href="/articles/'.$post_time_.'/'.$written_by_original.'"><div class="article_echo_2" style="width: 100%;">'.$cover.'<div><p class="title_"><b>Author: </b>'.$written_by.'</p>';
            $echo_articles .= '<p class="title_"><b>Title: </b>'.$title.'</p>';
            $echo_articles .= '<p class="title_"><b>Posted: </b>'.$post_time.'</p>';
            $echo_articles .= '<div id="tag_wrap"><p class="title_"><b>Tags: </b>'.$tags.'</p></div>';
            $echo_articles .= '<p class="title_"><b>Category: </b>'.$cat.'</p></div></div></a>';

            array_push($all_my_art, $row["id"]);
        }
    }else{
        $count_it = false;
        if($isOwner == "Yes"){
            $echo_articles = "<p style='color: #999; text-align: center;'>It seems that you have not written any articles so far</p>";
        }else{
            $echo_articles = "<p style='color: #999; text-align: center;'>It seems that ".$u." has not written any articles so far</p>";
        }
    }
    $stmt->close();

    // Count the user's all articles and set a view all link
    $sql = "SELECT COUNT(id) FROM articles WHERE written_by = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s",$u);
    $stmt->execute();
    $stmt->bind_result($my_all);
    $stmt->fetch();
    $stmt->close();

    $my_art_arr_count = count($all_my_art);
    if($my_art_arr_count > $max){
      array_splice($all_my_art, $max);
    }
    $showmore = "";
    if($my_all > $max){
        if($isOwner == "Yes"){
            $showmore = '/ <a href="/all_articles/'.$log_username.'">Show my all articles</a>';
        }else{
            $showmore = '/ <a href="/all_articles/'.$u.'">Show '.$u.'&#39;s all articles</a>';
        }
    }

    // Get the page viewer's gender
    $sql = "SELECT gender FROM users WHERE username = ? LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s",$u);
    $stmt->execute();
    $stmt->bind_result($gender_viewer);
    $stmt->fetch();
    $stmt->close();

    // Get how many articles has the user written
    $sql = "SELECT COUNT(id) FROM articles WHERE written_by = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s",$u);
    $stmt->execute();
    $stmt->bind_result($count_art);
    $stmt->fetch();
    $stmt->close();

    // Decide who is viewing the page
    $count_text = "";
    if($count_art == 1){
        $count_text = "<span>".$count_art."</span> article";
    }else if($count_art > 1 || $count_art == 0){
        $count_text = "<span>".$count_art."</span> articles";
    }

    // Get how many likes he/she got
    $sql = "SELECT COUNT(id) FROM heart_likes WHERE art_uname = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s",$u);
    $stmt->execute();
    $stmt->bind_result($like_count);
    $stmt->fetch();
    $stmt->close();
    if($like_count == ""){
        $like_count = "0";
    }

    // Decide who is viewing the page
    $like_text = "";
    if($like_count == 1){
        $like_text = "<span>".$like_count."</span> like";
    }else if($like_count > 1 || $like_count == 0){
        $like_text = "<span>".$like_count."</span> likes";
    }

    // Get how many times did he/she likes other articles
    $sql = "SELECT COUNT(id) FROM heart_likes WHERE username = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s",$u);
    $stmt->execute();
    $stmt->bind_result($my_likes);
    $stmt->fetch();
    $stmt->close();
    if($my_likes == ""){
        $my_likes = "0";
    }

    $my_text = "";
    if($my_likes == 1){
        $my_text = "<span>".$my_likes."</span> likes gien";
    }else if($my_likes > 1 || $my_likes == 0){
        $my_text = "<span>".$my_likes."</span> likes given";
    }

    // Get suggested articles
    // First get all friends
    $all_friends = array();
    $sql = "SELECT user1, user2 FROM friends WHERE (user2=? OR user1=?) AND accepted=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sss",$u,$u,$one);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        if ($row["user1"] != $u){
            array_push($all_friends, $row["user1"]);
        }
        if ($row["user2"] != $u){
            array_push($all_friends, $row["user2"]);
        }
    }
    $stmt->close();

    $k = 0;
    $dnsar = array();
    $sugglist = "";
    $friendsGR = join("','", $all_friends);
    $sql = "SELECT * FROM articles WHERE written_by IN ('$friendsGR') AND written_by != ? ORDER BY RAND() LIMIT $max";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s",$u);
    $stmt->execute();
    $result2 = $stmt->get_result();
    while($row = $result2->fetch_assoc()){
        array_push($dnsar, $row["id"]);
        $wb = $row["written_by"];
        $tit = stripslashes($row["title"]);
        $tit = str_replace('\'', '&#39;', $tit);
        $tit = str_replace('\'', '&#34;', $tit);
        $tag = $row["tags"];
        $pt_ = $row["post_time"];
        $opt = $pt_;
        $pt = strftime("%b %d, %Y", strtotime($pt_));
        $pt_ = base64url_encode($pt_,$hshkey);
        $wb_ori = urlencode($wb);
        $cat = $row["category"];
        
        $sql = "SELECT COUNT(id) FROM fav_art WHERE art_uname = ? AND art_time = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss",$log_username,$opt);
        $stmt->execute();
        $stmt->bind_result($cnt_fav);
        $stmt->fetch();
        $stmt->close();
        
        $sql = "SELECT COUNT(id) FROM heart_likes WHERE art_uname = ? AND art_time = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss",$log_username,$opt);
        $stmt->execute();
        $stmt->bind_result($cnt_heart);
        $stmt->fetch();
        $stmt->close();

        $cover = chooseCover($cat);

        $sugglist .= '<a href="/articles/'.$pt_.'/'.$wb_ori.'">
                        <div class="article_echo_2" style="width: 100%">
                            '.$cover.'<div><p class="title_"><b>Author: </b>'.$wb.'
                            </p>';
        $sugglist .= '<p class="title_"><b>Title: </b>'.$tit.'</p>';
        $sugglist .= '<p class="title_"><b>Posted: </b>'.$pt.'</p>';
        $sugglist .= '<p class="title_"><b>Tags: </b>'.$tag.'</p>';
        $sugglist .= '<p class="title_"><b>Category: </b>'.$cat.'</p></div></div></a>';

    }
    $stmt->close();
    $dnsars = join("','",$dnsar);
    $l = 0;
    if($k < 11){
        $lmit = 11 - $k;
        $sql = "SELECT lat, lon FROM users WHERE username = ? LIMIT 1";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s",$log_username);
        $stmt->execute();
        $stmt->bind_result($lat,$lon);
        $stmt->fetch();
        $stmt->close();

        $lat_m2 = $lat-0.7;
        $lat_p2 = $lat+0.7;

        $lon_m2 = $lon-0.7;
        $lon_p2 = $lon+0.7;
        $sql = "SELECT u.lat,u.lon, a.* FROM articles AS a LEFT JOIN users AS u ON u.username = a.written_by WHERE u.lat BETWEEN ? AND ? AND u.lon BETWEEN ? AND ? AND a.written_by != ? AND a.id NOT IN('$dnsars') ORDER BY RAND() LIMIT $lmit";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssss",$lat_m2,$lat_p2,$lon_m2,$lon_p2,$log_username);
        $stmt->execute();
        $result2 = $stmt->get_result();
        while($row = $result2->fetch_assoc()){
            $wb = $row["written_by"];
            $tit = stripslashes($row["title"]);
            $tit = str_replace('\'', '&#39;', $tit);
            $tit = str_replace('\'', '&#34;', $tit);
            $tag = $row["tags"];
            $pt_ = $row["post_time"];
            $opt = $pt_;
            $pt = strftime("%b %d, %Y", strtotime($pt_));
            $pt_ = base64url_encode($pt_,$hshkey);
            $wb_ori = urlencode($wb);
            $cat = $row["category"];
            
            $sql = "SELECT COUNT(id) FROM fav_art WHERE art_uname = ? AND art_time = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ss",$log_username,$opt);
            $stmt->execute();
            $stmt->bind_result($cnt_fav);
            $stmt->fetch();
            $stmt->close();
            
            $sql = "SELECT COUNT(id) FROM heart_likes WHERE art_uname = ? AND art_time = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ss",$log_username,$opt);
            $stmt->execute();
            $stmt->bind_result($cnt_heart);
            $stmt->fetch();
            $stmt->close();
    
            $cover = chooseCover($cat);
    
            $sugglist .= '<a href="/articles/'.$pt_.'/'.$wb_ori.'"><div class="article_echo_2" style="width: 100%">'.$cover.'<div><p class="title_"><b>Author: </b>'.$wb.'</p>';
            $sugglist .= '<p class="title_"><b>Title: </b>'.$tit.'</p>';
            $sugglist .= '<p class="title_"><b>Posted: </b>'.$pt.'</p>';
            $sugglist .= '<p class="title_"><b>Tags: </b>'.$tag.'</p>';
            $sugglist .= '<p class="title_"><b>Category: </b>'.$cat.'</p></div></div></a>';
        }
    }
    
    if($sugglist == ""){
        $sugglist = "<i style='font-size: 14px;'>You have no suggested articles at the moment. This may due to that you have no friedns or they have not written any articles so far.</i>";
    }

    // Get how many times has the user marked an article as favourite
    $sql = "SELECT COUNT(id) FROM fav_art WHERE username = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s",$u);
    $stmt->execute();
    $stmt->bind_result($other_fav);
    $stmt->fetch();
    $stmt->close();

    $other_echo = "";
    if($other_fav == 1){
        $other_echo = "<span>".$other_fav."</span> favourite given";
    }else if($other_fav == 0 || $other_fav > 1){
        $other_echo = "<span>".$other_fav."</span> favourites given";
    }

    // Get how many favourite marks has the user got
    $sql = "SELECT COUNT(id) FROM fav_art WHERE art_uname = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s",$u);
    $stmt->execute();
    $stmt->bind_result($my_fav);
    $stmt->fetch();
    $stmt->close();

    $my_echo = "";
    if($my_fav == 1){
        $my_echo = "<span>".$my_fav."</span> favourite";
    }else if($my_fav > 1 || $my_fav == 0){
        $my_echo = "<span>".$my_fav."</span> favourites";
    }
    
    // Get today's most liked articles
    $best_arts = "";
    $at_array = array();
    $uname_array = array();
    $sql = "SELECT art_uname, art_time, COUNT(*) AS u 
            FROM heart_likes
            WHERE like_time >= DATE_ADD(CURDATE(), INTERVAL -1 DAY)
            GROUP BY art_time
            ORDER BY u DESC
            LIMIT $max";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $res = $stmt->get_result();
    while($row = $res->fetch_assoc()){
        $uname = $row["art_uname"];
        $at = $row["art_time"];
        array_push($uname_array, $uname);
        array_push($at_array, $at);
    }
    $stmt->close();
    $uname_string = join("','", $uname_array);
    $at_string = join("','", $at_array);
    $m = 0;
    $sql = "SELECT * FROM articles WHERE written_by IN ('$uname_string') AND post_time IN ('$at_string')";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $res = $stmt->get_result();
    while($row = $res->fetch_assoc()){
        $wb = $row["written_by"];
        $tit = stripslashes($row["title"]);
        $tit = str_replace('\'', '&#39;', $tit);
        $tit = str_replace('\'', '&#34;', $tit);
        $tag = $row["tags"];
        $pt_ = $row["post_time"];
        $opt = $pt_;
        $pt = strftime("%b %d, %Y", strtotime($pt_));
        $pt_ = base64url_encode($pt_,$hshkey);
        $wb_ori = urlencode($wb);
        $cat = $row["category"];
        
        $sql = "SELECT COUNT(id) FROM fav_art WHERE art_uname = ? AND art_time = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss",$log_username,$opt);
        $stmt->execute();
        $stmt->bind_result($cnt_fav);
        $stmt->fetch();
        $stmt->close();
        
        $sql = "SELECT COUNT(id) FROM heart_likes WHERE art_uname = ? AND art_time = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss",$log_username,$opt);
        $stmt->execute();
        $stmt->bind_result($cnt_heart);
        $stmt->fetch();
        $stmt->close();

        $cover = chooseCover($cat);
        
        $best_arts .= '<a href="/articles/'.$pt_.'/'.$wb_ori.'"><div class="article_echo_2" style="width: 100%;">'.$cover.'<div><p class="title_"><b>Author: </b>'.$wb.'</p>';
        $best_arts .= '<p class="title_"><b>Title: </b>'.$tit.'</p>';
        $best_arts .= '<p class="title_"><b>Posted: </b>'.$pt.'</p>';
        $best_arts .= '<p class="title_"><b>Tags: </b>'.$tag.'</p>';
        $best_arts .= '<p class="title_"><b>Category: </b>'.$cat.'</p></div></div></a>';
    }
    $stmt->close();
    // If nothing, run query without time restriction
    if($uname_string == "" && $at_string == ""){
        $uname_array2 = array();
        $at_array2 = array();
        $sql = "SELECT art_uname, art_time, COUNT(*) AS u 
            FROM heart_likes
            GROUP BY art_time
            ORDER BY u DESC
            LIMIT $max";
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        $res = $stmt->get_result();
        while($row = $res->fetch_assoc()){
            $uname = $row["art_uname"];
            $at = $row["art_time"];
            array_push($uname_array2, $uname);
            array_push($at_array2, $at);
        }
        $stmt->close();
        $uname_string2 = join("','", $uname_array2);
        $at_string2 = join("','", $at_array2);
        $n = 0;
        $sql = "SELECT * FROM articles WHERE written_by IN ('$uname_string2') AND post_time IN ('$at_string2')";
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        $res = $stmt->get_result();
        while($row = $res->fetch_assoc()){
            $wb = $row["written_by"];
            $tit = stripslashes($row["title"]);
            $tit = str_replace('\'', '&#39;', $tit);
            $tit = str_replace('\'', '&#34;', $tit);
            $tag = $row["tags"];
            $pt_ = $row["post_time"];
            $opt = $pt_;
            $pt = strftime("%b %d, %Y", strtotime($pt_));
            $pt_ = base64url_encode($pt_,$hshkey);
            $wb_ori = urlencode($wb);
            $cat = $row["category"];
            
            $sql = "SELECT COUNT(id) FROM fav_art WHERE art_uname = ? AND art_time = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ss",$log_username,$opt);
            $stmt->execute();
            $stmt->bind_result($cnt_fav);
            $stmt->fetch();
            $stmt->close();
            
            $sql = "SELECT COUNT(id) FROM heart_likes WHERE art_uname = ? AND art_time = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ss",$log_username,$opt);
            $stmt->execute();
            $stmt->bind_result($cnt_heart);
            $stmt->fetch();
            $stmt->close();
    
            $cover = chooseCover($cat);

            $best_arts .= '<a href="/articles/'.$pt_.'/'.$wb_ori.'"><div class="article_echo_2" style="width: 100%;">'.$cover.'<div><p class="title_"><b>Author: </b>'.$wb.'</p>';
            $best_arts .= '<p class="title_"><b>Title: </b>'.$tit.'</p>';
            $best_arts .= '<p class="title_"><b>Posted: </b>'.$pt.'</p>';
            $best_arts .= '<p class="title_"><b>Tags: </b>'.$tag.'</p>';
            $best_arts .= '<p class="title_"><b>Category: </b>'.$cat.'</p></div></div></a>';

        }
        $stmt->close();
    }
    
    // Get the best authors
    $bauthors = array();
    $sql = "SELECT art_uname, COUNT(*) AS u 
            FROM heart_likes
            GROUP BY art_uname
            ORDER BY u DESC
            LIMIT 11";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $res = $stmt->get_result();
    while($row = $res->fetch_assoc()){
        $uname = $row["art_uname"];
        array_push($bauthors, $uname);
    }
    $stmt->close();
    $bauthors = array_unique($bauthors);
    $bauthors = join("','",$bauthors);
    $echo_bas = "";
    $sql = "SELECT u.*, COUNT(*) AS b FROM users AS u LEFT JOIN fav_art AS f ON f.art_uname = u.username WHERE u.username IN('$bauthors') GROUP BY f.art_uname ORDER BY b DESC";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $res = $stmt->get_result();
    while($row = $res->fetch_assoc()){
        $buname = $row["username"];
        $bavatar = $row["avatar"];
        $bonline = $row["online"];
        
        $sql = "SELECT COUNT(id) FROM articles WHERE written_by = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s",$buname);
        $stmt->execute();
        $stmt->bind_result($cnt_arts);
        $stmt->fetch();
        $stmt->close();
        
        $sql = "SELECT COUNT(id) FROM fav_art WHERE art_uname = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s",$buname);
        $stmt->execute();
        $stmt->bind_result($cnt_favs);
        $stmt->fetch();
        $stmt->close();
        
        $sql = "SELECT COUNT(id) FROM heart_likes WHERE art_uname = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s",$buname);
        $stmt->execute();
        $stmt->bind_result($cnt_hearts);
        $stmt->fetch();
        $stmt->close();
        
        $sql = "SELECT COUNT(id) FROM articles WHERE written_by = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s",$buname);
        $stmt->execute();
        $stmt->bind_result($cnt_wbs);
        $stmt->fetch();
        $stmt->close();
        
        $sql = "SELECT category,COUNT(category) AS u 
                FROM articles
                WHERE written_by = ?
                GROUP BY category
                ORDER BY u DESC LIMIT 1";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s",$buname);
        $stmt->execute();
        $stmt->bind_result($favcat,$dnt);
        $stmt->fetch();
        $stmt->close();
        
        if($bavatar == NULL){
            $bavatar = '/images/avdef.png';
        }else{
            $bavatar = '/user/'.$buname.'/'.$bavatar;
        }
        $apoints = ($cnt_favs * 2 + $cnt_hearts) / $cnt_wbs;
        $apoints = round($apoints,2);
        $uniid = base64url_encode($buname,$hshkey);
        $echo_bas .= '<div class="bauthors"><a href="/user/'.$buname.'/"><div style="background-image: url(\''.$bavatar.'\'); background-repeat: no-repeat; background-size: cover; background-position: center; width: 50px; height: 50px; display: inline-block;" class="whee" onmouseover="showBas(\''.$uniid.'\')" onmouseleave="hideBas(\''.$uniid.'\')"></div></a><span style="margin-left: 5px;"><img src="/images/star.png" width="18" height="18"> <b>'.$cnt_favs.'</b><br><img src="/images/heart.png" width="17" height="17" style="margin-left: 5px;"> <b>'.$cnt_hearts.'</b></span><div class="infobadiv" id="pc_'.$uniid.'"><span style="float: left;"><b style="font-size: 12px !important;">Username: </b>'.$buname.'<br><b style="font-size: 12px !important;">Articles written: </b>'.$cnt_wbs.'<br><b style="font-size: 12px !important;">Likes got: </b>'.$cnt_hearts.'<br><b style="font-size: 12px !important;">Favourites got: </b>'.$cnt_favs.'<br><b style="font-size: 12px !important;">Points got: </b>'.$apoints.'<br><b style="font-size: 12px !important;">Favourite category: </b>'.$favcat.' </span><div style="background-image: url(\''.$bavatar.'\'); background-repeat: no-repeat; background-size: cover; background-position: center; width: 85px; height: 85px; display: inline-block; float: right; border-radius: 50%;"></div></div></div>';
    }
    $stmt->close();
?>  
<!DOCTYPE html>
<html>
<head>
    <title>Atricles - <?php echo $u; ?></title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/x-icon" href="/images/newfav.png">
    <link rel="stylesheet" type="text/css" href="/style/style.css">
    <link rel="stylesheet" href="/font-awesome-4.7.0/css/font-awesome.min.css">
    <script src="/js/jjs.js" async></script>
    <script src="/text_editor.js" async></script>
    <script src="/js/main.js" async></script>
    <script src="/js/ajax.js" async></script>
    <script src="/js/mbc.js" async></script>
      <link rel="manifest" href="/manifest.json">
  <meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="#282828">
<meta name="apple-mobile-web-app-title" content="Pearscom">
<link rel="apple-touch-icon" href="/images/icons/icon-152x152.png">
<meta name="theme-color" content="#282828" />
    <script type="text/javascript">
    function getLSearchArt()
    /*Scope Closed:false | writes:true*/
    {
        var e = _('searchArt').value;
        if (e == '') {
            _('artSearchResults').style.display = 'none';
            return false;
        }
        var a = encodeURI(e);
        window.location = '/search_articles/' + encodeURI(e);
    }
function getArt(e)
    /*Scope Closed:false | writes:true*/
    {
        if ('' == e) {
            _('artSearchResults').style.display = 'none';
            return false;
        }
                _('artSearchResults').style.display = 'block';
        '' == _('artSearchResults').innerHTML && '<img src="/images/rolling.gif" width="30" height="30">';
        var a = encodeURI(e), t = new XMLHttpRequest();
                t.open('POST', '/art_exec.php', true);
        t.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
        t.onreadystatechange = function ()
            /* Called:undefined | Scope Closed:false| writes:true*/
            {
                if (4 == t.readyState && 200 == t.status) {
                    var e = t.responseText;
                    '' != e && (_('artSearchResults').innerHTML = e);
                }
            };
        t.send('a=' + encodeURI(e));
    }
var mobilecheck = mobilecheck();
function showBas(e)
    /*Scope Closed:false | writes:true*/
    {
        0 == mobilecheck && 'block';
    }          
	 </script>
    <style type="text/css">
        @media only screen and (max-width: 1000px){ 
          #searchArt{
            width: 90% !important;
          }

          #artSearchBtn{
            width: 10% !important;
          }

          @media only screen and (max-width: 500px){
            #searchArt {
            width: 85% !important;
          }

          #artSearchBtn {
            width: 15% !important;
          }
        }
    }
    </style>
</head>
<body>
    <?php include_once("template_pageTop.php"); ?>
    <div id="pageMiddle_2">
        <div id="artSearch">
            <div id="artSearchInput">
                <input id="searchArt" type="text" autocomplete="off" onkeyup="getArt(this.value)" placeholder="Search for articles by their author, title, category or tags" class="lsearch">
                <div id="artSearchBtn" onclick="getLSearchArt()"><img src="/images/searchnav.png" width="17" height="17"></div>
            </div>
            <div class="clear"></div>
        </div>
        <div id="artSearchResults" class="longSearches"></div>
          <div id="data_holder">
            <div>
                <div><?php echo $count_text; ?></div>
                <div><?php echo $like_text; ?></div>
                <div><?php echo $my_text; ?></div>
                <div><?php echo $my_echo; ?></div>
                <div><?php echo $other_echo; ?></div>
            </div>
        </div>

    <button id="writeIt" class="main_btn_fill" onclick="getWA()">Write Article</button>
    <div class="clear"></div>
    <hr class='dim'>
      <div id="centetait">
        <?php if($isOwner == "Yes"){
            echo "<p style='font-size: 18px; padding-bottom: 0px; text-align: center;'><a href='/all_articles/".$u."'>My Articles</a> <img src='/images/myone.png' class='notfimg' style='margin-bottom: -2px;'></p>";
        }else{
            echo "<p style='font-size: 18px; text-align: center;'><a href='/all_articles/".$u."'>".$u."&#39;s articles</a> <img src='/images/myone.png' class='notfimg' style='margin-bottom: -2px;'></p>";
        } ?>
        <div class="flexibleSol" id="userFlexArts">
            <?php echo $echo_articles; ?>
        </div>
      <div class="clear"></div>
      <?php if($count_it == true){ ?><hr class='dim'><?php } ?>

        <p style="font-size: 18px; text-align: center;"><a href='/article_suggestions'>Suggested</a> articles from friends &amp; nearby users <img src="/images/morea.png" class="notfimg" style="margin-bottom: -2px;"></p>
        <div class="flexibleSol" id="userFlexArts">
            <?php echo $sugglist; ?>
        </div>
        <div class="clear"></div>
        <hr class="dim">

      <div class="clear"></div>
      <p style="font-size: 18px; text-align: center;">Today&#39;s most liked &amp; favourite articles <img src="/images/likeb.png" class="notfimg" style="margin-bottom: -2px;"></p>
      <div class="flexibleSol" id="userFlexArts">
        <?php echo $best_arts; ?>
        </div>
      <?php if($best_arts == ""){ ?>
        <p style="color: #999; text-align: center;">It seems that there are no articles fitting the requirements</p>
      <?php } ?>
      </div>
      <div class="clear"></div>
      <hr class="dim">
      <p style="font-size: 18px; text-align: center;">Best authors of all time <img src="/images/bestas.png" class="notfimg" style="margin-bottom: -2px;"></p>
      <div class="flexibleSol" id="userFlexArts">
        <?php echo $echo_bas; ?>
    </div>
      <div class="clear"></div>
      <br><br>
    </div>
    <?php require_once 'template_pageBottom.php'; ?>
    <script type="text/javascript">
        var isSafari=/^((?!chrome|android).)*safari/i.test(navigator.userAgent);function getWA(){window.location="/user/<?php echo $log_username; ?>&wart=yes"}function hideBas(e){_("pc_"+e).style.display="none"}function getCookie(e){for(var t=e+"=",s=decodeURIComponent(document.cookie).split(";"),a=0;a<s.length;a++){for(var r=s[a];" "==r.charAt(0);)r=r.substring(1);if(0==r.indexOf(t))return r.substring(t.length,r.length)}return""}function setDark(){var e="thisClassDoesNotExist";if(!document.getElementById(e)){var t=document.getElementsByTagName("head")[0],s=document.createElement("link");s.id=e,s.rel="stylesheet",s.type="text/css",s.href="/style/dark_style.css",s.media="all",t.appendChild(s)}}var isdarkm=getCookie("isdark");"yes"==isdarkm&&setDark();
    </script>
</body>
</html>
