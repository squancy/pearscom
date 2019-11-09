<?php
    include_once("php_includes/check_login_statues.php");
    require_once 'timeelapsedstring.php';
    require_once 'safe_encrypt.php';
    require_once 'durc.php';
    require_once 'phpmobc.php';
    require_once 'headers.php';

    // Make sure the _GET "u" is set, and sanitize it
    $u = "";
    $info_vid_user = "";
    if(isset($_GET['u'])){
        $u = $_GET["u"];
    }else{
        header('Location: /index');
    }
    $one = "1";
    if(isset($_SESSION["username"]) && $_SESSION["username"] != ""){
        // Select the member from the users table
        $one = "1";
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
    }
    $otype = "def";
    $ismobile = mobc();
    
    $id = "";
    $video_form = "";
    $echo_videos_short = "";
    $id_number = "";
    // Check to see if the viewer is the account owner
    $isOwner = "no";
    if($u == $log_username && $user_ok == true){
        $isOwner = "yes";
        $video_form  = '<form id="video_form_div" style="width: 100%;" enctype="multipart/form-data" method="post" class="styleform">';
        $video_form .=   '<p style="font-size: 18px; margin-top: 0px;" class="txtc">Upload a new video</p>';
        $video_form .=   '<input type="text" name="videoname" style="margin-left: 0px; display: inline-block;" id="videoname" placeholder="Title or name of your video" onkeyup="statusMax(this,150)">';
        $video_form .=   '<input type="file" name="video" id="file" class="inputfile" required onchange="showfile()">';
        $video_form .=   '<label for="file" id="choose_file" class="ltmarg">Choose video</label><span id="sel_f">&nbsp; No files selected</span>';
        $video_form .=   '<textarea id="description" style="margin-left: 0px;" name="description" class="longerv" placeholder="Description of the video in a few words" onkeyup="statusMax(this,1000)"></textarea>';
        $video_form .=   '<input type="file" name="poster" id="asd" class="inputfile" accept="image/*" onchange="showfile2()">';
        $video_form .=   '<label for="asd" id="as">Choose poster</label><span id="sel_f2">&nbsp;&nbsp;No files selected</span><br />';
        $video_form .=   '<p><input type="button" value="Upload Video" onclick="uploadVideo()" id="vupload" class="main_btn_fill fixRed" style="display: block; margin: 0 auto;"><div id="txt_holder"></div><div class="collection vInfo" style="border-top: 1px solid rgba(0, 0, 0, 0.1);" id="ccSu">
      <p style="font-size: 18px;" id="signup">How can I upload my video?</p>
      <img src="/images/alldd.png">
    </div>
    <div class="slideInfo vInfoRev" id="suDD"><p style="font-size: 14px;" class="txtc">Make sure the video size is below 10MB and the poster image size is below 5MB. The allowed file extensions for posters are jpg, jpeg, png and gif, for videos it is MP4, WebM and Ogg (may vary between browsers).</p></p><p style="font-size: 14px;" class="txtc">A poster will function as a showcase image for the video until that is started</p><p style="font-size: 14px;" class="txtc">For further information may consider visiting the <a href="/help">help &amp; support</a> page.</p></div>';
        $video_form .=   '<div id="rolling"></div>';
        $video_form .= '<div id="pbc">
                            <div id="progressBar"></div>
                        <div id="pbt"></div>
                       </div>';
        $video_form .= '</form>';
    }else if(isset($_SESSION["username"]) && $_SESSION["username"] != "" && $log_username != $u){
        $info_vid_user = '<a href="/videos/'.$log_username.'" class="txtc">Upload video on my profile</a>';
    }

    // Pagination
    // This first query is just to get the total count of rows
    $sql = "SELECT COUNT(id) FROM videos WHERE user=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s",$u);
    $stmt->execute();
    $stmt->bind_result($rows);
    $stmt->fetch();
    $stmt->close();
    // Here we have the total row count
    // This is the number of results we want displayed per page
    $page_rows = 25;
    // This tells us the page number of our last page
    $last = ceil($rows/$page_rows);
    // This makes sure $last cannot be less than 1
    if($last < 1){
        $last = 1;
    }
    // Establish the $pagenum variable
    $pagenum = 1;
    // Get pagenum from URL vars if it is present, else it is = 1
    if(isset($_GET['pn'])){
        $pagenum = preg_replace('#[^0-9]#', '', $_GET['pn']);
    }
    // This makes sure the page number isn't below 1, or more than our $last page
    if ($pagenum < 1) { 
        $pagenum = 1; 
    } else if ($pagenum > $last) { 
        $pagenum = $last; 
    }
    // This sets the range of rows to query for the chosen $pagenum
    $limit = 'LIMIT ' .($pagenum - 1) * $page_rows .',' .$page_rows;
    // Establish the $paginationCtrls variable
    $paginationCtrls = '';
    // If there is more than 1 page worth of results
    if($last != 1){
        /* First we check if we are on page one. If we are then we don't need a link to 
           the previous page or the first page so we do nothing. If we aren't then we
           generate links to the first page, and to the previous page. */
        if ($pagenum > 1) {
            $previous = $pagenum - 1;
            $paginationCtrls .= '<a href="/videos/'.$u.'&pn='.$previous.'">Previous</a> &nbsp; &nbsp; ';
            // Render clickable number links that should appear on the left of the target page number
            for($i = $pagenum-4; $i < $pagenum; $i++){
                if($i > 0){
                    $paginationCtrls .= '<a href="/videos/'.$u.'&pn='.$i.'">'.$i.'</a> &nbsp; ';
                }
            }
        }
        // Render the target page number, but without it being a link
        $paginationCtrls .= ''.$pagenum.' &nbsp; ';
        // Render clickable number links that should appear on the right of the target page number
        for($i = $pagenum+1; $i <= $last; $i++){
            $paginationCtrls .= '<a href="/videos/'.$u.'&pn='.$i.'">'.$i.'</a> &nbsp; ';
            if($i >= $pagenum+4){
                break;
            }
        }
        // This does the same as above, only checking if we are on the last page, and then generating the "Next"
        if ($pagenum != $last) {
            $next = $pagenum + 1;
            $paginationCtrls .= ' &nbsp; &nbsp; <a href="/videos/'.$u.'&pn='.$next.'">Next</a> ';
        }
    }

    // Check if the user has uploaded any videos
    $echo_videos = "";
    $sql = "SELECT * FROM videos WHERE user=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s",$u);
    $stmt->execute();
    $stmt->store_result();
    $stmt->fetch();
    $numrows = $stmt->num_rows;
    $cvids = $numrows;
    if($numrows < 1){
        if($isOwner == "yes"){
            $echo_videos = "<p style='font-size: 14px;'>You have not uploaded any videos yet</p>";
        }else{
            $echo_videos = "<p style='text-align: center; font-size: 14px;'>".$u." has not uploaded any videos yet</p>";
        }
    }
    $stmt->close();

    if(isset($_GET["otype"]) || $otype != ""){
        $clause = "";
        $typeExists = false;
        if(isset($_GET["otype"])){
            $otype = mysqli_real_escape_string($conn, $_GET["otype"]);
            $typeExists = true;
        }

        if($otype == "sort_0"){
            $clause = "ORDER BY video_upload DESC";
        }else if($otype == "sort_1"){
            $clause = "ORDER BY video_upload ASC";
        }else if($otype == "sort_4"){
            $clause = "ORDER BY video_name";
        }else if($otype == "sort_5"){
            $clause = "ORDER BY video_name DESC";
        }else if($otype == "sort_2"){
            $clause = "ORDER BY video_description";
        }else if($otype == "sort_3"){
            $clause = "ORDER BY video_description DESC";
        }else if($otype == "sort_6"){
            $clause = "ORDER BY dur";
        }else if($otype == "sort_7"){
            $clause = "ORDER BY dur DESC";
        }else{
            $clause = "ORDER BY video_upload DESC";
        }

        // Get users videos

        $items = "";
        $url = array();
        $sql = "SELECT * FROM videos WHERE user=? $clause $limit";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s",$u);
        $stmt->execute();
        $result = $stmt->get_result();
        while($row = $result->fetch_assoc()){
            $id = $row["id"];
            $id_number = $row["id"];
            $id_number = preg_replace('/\D/', '', $id);
            $id = base64url_encode($id,$hshkey);
            $vf = $row["video_file"];
            $vuploader = $row["user"];
            $description = $row["video_description"];
            $video_name = $row["video_name"];
            $video_upload = $row["video_upload"];
            $pr = $row["video_poster"];
            $dur = $row["dur"];
            $dur = convDur($dur);
            $video_upload_ = strftime("%r, %b %d, %Y", strtotime($video_upload));
            if($video_name == ""){
                $video_name = "Untitled";
            }
            if($description == ""){
                $description = "No description";
            }
            if($pr != ""){
                $pr = '/user/'.$u.'/videos/'.$pr.'';
            }else{
                $pr = '/images/defaultimage.png';
            }

            // Get uploader's avatar
            $sql = "SELECT avatar FROM users WHERE username = ? LIMIT 1";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("s",$vuploader);
            $stmt->execute();
            $stmt->bind_result($userpic);
            $stmt->fetch();
            $stmt->close();

            // Get real avatar from directory
            $upic = "";
            if($userpic != NULL){
                $upic = "/user/".$vuploader."/".$userpic;
            }else{
                $upic = '/images/avdef.png';
            }

            // Get number of likes
            $sql = "SELECT COUNT(id) FROM video_likes WHERE video=?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i",$id_number);
            $stmt->execute();
            $stmt->bind_result($like_count);
            $stmt->fetch();
            $stmt->close();
            $vc = "".$like_count;
            
            // Check the likes on every video
            $isLike = false;
            if($user_ok == true){
                $like_check = "SELECT id FROM video_likes WHERE video = ? LIMIT 1";
                $stmt = $conn->prepare($like_check);
                $stmt->bind_param("i",$id_number);
                $stmt->execute();
                $stmt->store_result();
                $stmt->fetch();
                $numrows = $stmt->num_rows;
            if($numrows > 0){
                    $isLike = true;
                }

            }
            $like_count = settype($like_count, "integer");
            // Add like button
            $likeButton = "";
            $likeText = "";

            // Add a 'new' video text
            //$sql = "SELECT DATEDIFF()";
            $curdate = date("Y-m-d");
            $ud = mb_substr($video_upload, 0,10, "utf-8");

            $sql = "SELECT DATEDIFF(?,?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ss",$curdate,$ud);
            $stmt->execute();
            $stmt->bind_result($isnew);
            $stmt->fetch();
            $stmt->close();
            $isnewornot = "";
            if($isnew <= 1){
                $isnewornot = "<div class='pcjti' style='width: auto; border-radius: 3px; margin-left: 45px; position: absolute; bottom: 25px;'>New</div>";
            }

            $like_count = settype($like_count, "integer");
            
            $hshid = base64url_encode($id_number,$hshkey);
            if($isLike == true){
                $likeButton = '<a href="#" onclick="return false;" onmousedown="likeVideo(\'unlike\',\''.$hshid.'\',\'likeBtnv_'.$hshid.'\',\'ion_'.$hshid.'\')"><img src="/images/fillthumb.png" width="18" height="18" class="like_unlike" style="vertical-align: middle;"></a>';
                $likeText = '<span style="vertical-align: middle;">Dislike</span>';
            }else{
                $likeButton = '<a href="#" onclick="return false;"  onmousedown="likeVideo(\'like\',\''.$hshid.'\',\'likeBtnv_'.$hshid.'\',\'ion_'.$hshid.'\')"><img src="/images/nf.png" width="18" height="18" class="like_unlike" style="vertical-align: middle;"></a>';
                $likeText = '<span style="vertical-align: middle;">Like</span>';
            }
            $ec = $id;
            
            $sourceURL = "";
	        if($typeExists){
	            $sourceURL = "style='background-image: url(\"$pr\");' id='pcgetc' class='mainVids lazy-bg'";
	        }else{
	            $sourceURL = "data-src=\"" . $pr . "\" id='pcgetc' class='mainVids lazy-bg'";
	        }
            
            $echo_videos .= "<a href='/video_zoom/" . $hshid . "'><div class='nfrelv' style='white-space: nowrap;'><div ".$sourceURL."></div><div class='pcjti'>" . $video_name . "</div><div class='pcjti' style='width: auto; border-radius: 3px; margin-left: 2px; position: absolute; bottom: 25px;'>" . $dur . "</div>".$isnewornot."</div></a>";
        }

        $echo_videos .= '<div class="clear"></div><div id="pagination_controls">'.$paginationCtrls.'</div>';
        if(isset($_GET["otype"])){
            echo $echo_videos;
            exit();
        }
    }

    $cntRels = 0;

    // Get related videos
    // FIRST GET USERS'S FRIENDS
    $all_friends = array();
    $sql = "SELECT user1, user2 FROM friends WHERE user2 = ? AND accepted=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss",$log_username,$one);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        array_push($all_friends, $row["user1"]);
    }
    $stmt->close();

    $sql = "SELECT user1, user2 FROM friends WHERE user1 = ? AND accepted=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss",$log_username,$one);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        array_push($all_friends, $row["user2"]);
    }
    $stmt->close();
    // Implode all friends array into a string
    $nof = false;
    $allfmy = join("','", $all_friends);
    $related_vids = "";
    $sql = "SELECT * FROM videos WHERE user IN ('$allfmy') ORDER BY RAND() LIMIT 30";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $result = $stmt->get_result();
    while($row = $result->fetch_assoc()){
        $cntRels++;
        $vid = $row["id"];
        $vid = base64url_encode($vid,$hshkey);
        $vuser = $row["user"];
        $vvname = $row["video_name"];
        $vdescription = $row["video_description"];
        $vposter = $row["video_poster"];
        $vfile = $row["video_file"];
        $vdate_ = $row["video_upload"];
        $vdate = strftime("%b %d, %Y", strtotime($vdate_));
        $dur = convDur($row["dur"]);
 
        if($vvname == NULL){
            $vvname = "Untitiled";
        }
        if($vdescription == NULL){
            $vdescription = "No description";
        }

        $pcurl = '/user/'.$vuser.'/videos/'.$vposter.'';
        if($vposter == NULL){
            $pcurl = '/images/defaultimage.png';
        }

        $curdate = date("Y-m-d");
        $ud = mb_substr($vdate_, 0,10, "utf-8");

        $sql = "SELECT DATEDIFF(?,?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss",$curdate,$ud);
        $stmt->execute();
        $stmt->bind_result($isnew);
        $stmt->fetch();
        $stmt->close();
        $isnewornot = "";
        if($isnew <= 1){
            $isnewornot = "<div class='pcjti' style='width: auto; border-radius: 3px; margin-left: 45px; position: absolute; bottom: 25px;'>New</div>";
        }

        $uds = time_elapsed_string($vdate_);

        $related_vids .= "<a href='/video_zoom/" . $vid . "'><div class='nfrelv' style='white-space: nowrap;'><div data-src=\"".$pcurl."\" id='pcgetc' class='mainVids lazy-bg'></div><div class='pcjti'>" . $vvname . "</div><div class='pcjti' style='width: auto; border-radius: 3px; margin-left: 2px; position: absolute; bottom: 25px;'>" . $dur . "</div>".$isnewornot."</div></a>";
    }
    $stmt->close();
    if($allfmy == ""){
        $nof = true;
        $sql = "SELECT * FROM videos ORDER BY RAND() LIMIT 30";
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        $result = $stmt->get_result();
        while($row = $result->fetch_assoc()){
            $cntRels++;
            $vid = $row["id"];
            $vid = base64url_encode($vid,$hshkey);
            $vuser = $row["user"];
            $vvname = $row["video_name"];
            $vdescription = $row["video_description"];
            $vposter = $row["video_poster"];
            $vfile = $row["video_file"];
            $vdate_ = $row["video_upload"];
            $vdate = strftime("%b %d, %Y", strtotime($vdate_));
            $dur = convDur($row["dur"]);

            if($vvname == NULL){
                $vvname = "Untitiled";
            }
            if($vdescription == NULL){
                $vdescription = "No description";
            }

            $pcurl = '/user/'.$vuser.'/videos/'.$vposter.'';
            if($vposter == NULL){
                $pcurl = '/images/defaultimage.png';
            }

            $curdate = date("Y-m-d");
            $ud = mb_substr($vdate_, 0,10, "utf-8");

            $sql = "SELECT DATEDIFF(?,?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ss",$curdate,$ud);
            $stmt->execute();
            $stmt->bind_result($isnew);
            $stmt->fetch();
            $stmt->close();
            $isnewornot = "";
            if($isnew <= 1){
                $isnewornot = "<div class='pcjti' style='width: auto; border-radius: 3px; margin-left: 45px; position: absolute; bottom: 25px;'>New</div>";
            }

            $uds = time_elapsed_string($vdate_);

            $related_vids .= "<a href='/video_zoom/" . $vid . "'><div class='nfrelv' style='white-space: nowrap;'><div data-src=\"".$pcurl."\" id='pcgetc' class='mainVids lazy-bg'></div><div class='pcjti'>" . $vvname . "</div><div class='pcjti' style='width: auto; border-radius: 3px; margin-left: 2px; position: absolute; bottom: 25px;'>" . $dur . "</div>".$isnewornot."</div></a>";
        }
        $stmt->close();
    }
    
    $isrel = false;
    if($related_vids == ""){
        $related_vids = '<p style="color: #999;" class="txtc">It seems that we could not list any related videos for you</p>';
        $isrel = true;
    }

    $sql = "SELECT COUNT(id) FROM videos WHERE user = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $u);
    $stmt->execute();
    $stmt->bind_result($cntMyvids);
    $stmt->fetch();
    $stmt->close();

    $sql = "SELECT COUNT(l.id) FROM video_likes AS l LEFT JOIN videos AS v ON v.id = l.video WHERE l.username = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s",$u);
    $stmt->execute();
    $stmt->bind_result($cntLikesGot);
    $stmt->fetch();
    $stmt->close();
?>

<!DOCTYPE html>
<html>
<head>
    <title><?php echo $u; ?>&#39;s Videos</title>
    <meta charset="utf-8">
    <meta lang="en">
    <link rel="icon" type="image/x-icon" href="/images/newfav.png">
    <link rel="stylesheet" type="text/css" href="/style/style.css">
    <link rel="stylesheet" href="/font-awesome-4.7.0/css/font-awesome.min.css">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="All of <?php echo $u; ?>&#39;s videos is available and you can watch them. Click on the icon in order to see in in a bigger view.">
    <meta name="keywords" content="pearscom videos <?php echo $u; ?>, <?php echo $u; ?> videos, <?php echo $u; ?> all videos, videos of <?php echo $u; ?>, <?php echo $u; ?> videos page">
    <meta name="author" content="Pearscom">
    <script src="/js/jjs.js"></script>
    <script src="/js/main.js" async></script>
    <script src="/js/ajax.js" async></script>
    <script src="/js/mbc.js"></script>
    	  <link rel="manifest" href="/manifest.json">
  <meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="#282828">
<meta name="apple-mobile-web-app-title" content="Pearscom">
<link rel="apple-touch-icon" href="/images/icons/icon-152x152.png">
<meta name="theme-color" content="#282828" />
    <script src=/js/lload.js></script>
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
    <script type="text/javascript">
        var vid;
        var playbtn;
        var seekslider;
        var curtimetext;
        var durtimetext;
        var mutebtn;
        var volumeslider;
        var fullscrbtn;
        var vid_q;
        var playbtn_q;
        var seekslider_q;
        var curtimetext_q;
        var durtimetext_q;
        var mutebtn_q;
        var volumeslider_q;
        var fullscrbtn_q;
        var mobilecheck = mobilecheck();
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
          fullscrbtn.addEventListener("click", toggleFullScr, false);
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
        function vidSeek() {
          var seekto = vid.duration * (seekslider.value / 100);
          vid.currentTime = seekto;
        }
        function seektimeupdate() {
          var _startingFret = vid.currentTime * (100 / vid.duration);
          seekslider.value = _startingFret;
          var minutes = Math.floor(vid.currentTime / 60);
          var seconds = Math.floor(vid.currentTime - 60 * minutes);
          var bname = Math.floor(vid.duration / 60);
          var year = Math.floor(vid.duration - 60 * bname);
          if (seconds < 10) {
            seconds = "0" + seconds;
          }
          if (minutes < 10) {
            minutes = "0" + minutes;
          }
          if (year < 10) {
            year = "0" + year;
          }
          if (bname < 10) {
            bname = "0" + bname;
          }
          curtimetext.innerHTML = minutes + ":" + seconds;
          durtimetext.innerHTML = bname + ":" + year;
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
        function showfile() {
          var e = _("file").value.substr(12);
          _("sel_f").innerHTML = "&nbsp;" + e;
        }
        function showfile2() {
          var e = _("asd").value.substr(12);
          _("sel_f2").innerHTML = "&nbsp;" + e;
        }
        function initializePlayer_q(name) {
          vid_q = _("my_video_" + name);
          playbtn_q = _("playpausebtn_" + name);
          seekslider_q = _("seekslider_" + name);
          curtimetext_q = _("curtimetext_" + name);
          durtimetext_q = _("durtimetext_" + name);
          mutebtn_q = _("mutebtn_" + name);
          volumeslider_q = _("volumeslider_" + name);
          fullscrbtn_q = _("fullscrbtn_" + name);
          seekslider_q.addEventListener("change", vidSeek_q, false);
          vid_q.addEventListener("timeupdate", seektimeupdate_q, false);
          mutebtn_q.addEventListener("click", vidmute_q, false);
          volumeslider_q.addEventListener("change", setVolume_q, false);
        }
        function playPause_q(type) {
          if (vid.paused) {
            vid.play();
            playbtn.innerHTML = "<img src='/images/pausebtn.png' width='15' height='15'>";
            _("text_" + type + "_q").style.display = "none";
          } else {
            vid.pause();
            playbtn.innerHTML = "<img src='/images/playbtn.png' width='15' height='15'>";
            _("text_" + type + "_q").style.display = "block";
          }
        }
        function vidSeek_q() {
          var seekto = vid.duration * (seekslider.value / 100);
          vid.currentTime = seekto;
        }
        function seektimeupdate_q() {
          var _startingFret = vid.currentTime * (100 / vid.duration);
          seekslider.value = _startingFret;
          var minutes = Math.floor(vid.currentTime / 60);
          var seconds = Math.floor(vid.currentTime - 60 * minutes);
          var bname = Math.floor(vid.duration / 60);
          var year = Math.floor(vid.duration - 60 * bname);
          if (seconds < 10) {
            seconds = "0" + seconds;
          }
          if (minutes < 10) {
            minutes = "0" + minutes;
          }
          if (year < 10) {
            year = "0" + year;
          }
          if (bname < 10) {
            bname = "0" + bname;
          }
          curtimetext.innerHTML = minutes + ":" + seconds;
          durtimetext.innerHTML = bname + ":" + year;
        }
        function vidmute_q() {
          if (vid.muted) {
            vid.muted = false;
            mutebtn.innerHTML = "<img src='/images/nmute.png' width='15' height='15' id='mutebigger'>";
            volumeslider.value = 100;
          } else {
            vid.muted = true;
            mutebtn.innerHTML = "<img src='/images/mute.png' width='19' height='19' id='mutebigger'>";
            volumeslider.value = 0;
          }
        }
        function setVolume_q() {
          vid.volume = volumeslider.value / 100;
        }
        function toggleFullScr() {
          if (vid.requestFullScreen) {
            vid.requestFullScreen();
            vid.controls = false;
          } else {
            if (vid.webkitRequestFullScreen) {
              vid.webkitRequestFullScreen();
              vid.controls = false;
            } else {
              if (vid.mozRequestFullScreen) {
                vid.mozRequestFullScreen();
                vid.controls = false;
              }
            }
          }
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
        function uploadVideo() {
          function uploadFile(uid) {
            var data = new FormData;
            data.append("stPic_video", blob);
            if (0 != _("asd").files.length) {
              data.append("stPic_poster", inputblob);
            }
            data.append("stVideo_name", i);
            data.append("stVideo_des", e);
            data.append("stVideo_dur", uid);
            var request = new XMLHttpRequest;
            request.upload.addEventListener("progress", progressHandler, false);
            request.addEventListener("load", completeHandler, false);
            request.addEventListener("error", errorHandler, false);
            request.addEventListener("abort", abortHandler, false);
            request.open("POST", "/php_parsers/video_parser.php");
            request.send(data);
          }
          var inputblob = _("asd").files[0];
          var blob = _("file").files[0];
          var i = _("videoname").value;
          var e = _("description").value;
          if ("" == blob.name) {
            return false;
          }
          if ("video/webm" != blob.type && "video/mp4" != blob.type && "video/ogg" != blob.type && "audio/mp3" != blob.type && "video/mov" != blob.type) {
            return _("overlay").style.display = "block", _("overlay").style.opacity = .5, _("dialogbox").style.display = "block", _("dialogbox").innerHTML = '<p style="font-size: 18px; margin: 0px;">File type is not supported</p><p>The video that you want to upload has an unvalid extension given that we do not support. The allowed file extensions are: MP4, WebM and Ogg. For further information please visit the help page.</p><button id="vupload" style="position: absolute; right: 3px; bottom: 3px;" onclick="closeDialog()">Close</button>', 
            document.body.style.overflow = "hidden", false;
          }
          if (0 != _("asd").files.length && "image/jpg" != inputblob.type && "image/jpeg" != inputblob.type && "image/png" != inputblob.type && "image/gif" != inputblob.type) {
            return _("overlay").style.display = "block", _("overlay").style.opacity = .5, _("dialogbox").style.display = "block", _("dialogbox").innerHTML = '<p style="font-size: 18px; margin: 0px;">File type is not supported</p><p>The image that you want to upload has an unvalid extension given that we do not support. The allowed file extensions are: jpg, jpeg, png and gif. For further information please visit the help page.</p><button id="vupload" style="position: absolute; right: 3px; bottom: 3px;" onclick="closeDialog()">Close</button>', 
            document.body.style.overflow = "hidden", false;
          }
          if ("video/webm" == blob.type || "video/mp4" == blob.type || "video/ogg" == blob.type || "video/mov" == blob.type) {
            (player = document.createElement("video")).preload = "metadata";
            window.URL.revokeObjectURL(player.src);
            player.addEventListener("durationchange", function() {
              console.log("Duration change", player.duration);
              uploadFile(player.duration);
            });
          } else {
            var player = document.createElement("audio");
            window.URL.revokeObjectURL(player.src);
            player.addEventListener("durationchange", function() {
              console.log("Duration change", player.duration);
              uploadFile(player.duration);
            });
          }
          player.src = URL.createObjectURL(blob);
          _("pbc").style.display = "block";
        }
        function progressHandler(event) {
          var inDays = event.loaded / event.total * 100;
          var percent_progress = Math.round(inDays);
          _("progressBar").style.width = percent_progress + "%";
          _("pbt").innerHTML = percent_progress + "%";
        }
        function completeHandler(event) {
          var t = event.target.responseText.split("|");
          _("progressBar").style.width = "0%";
          _("pbc").style.display = "none";
          _("rolling").innerHTML = "";
          if ("upload_complete" == t[0]) {
            _("txt_holder").innerHTML = "<p style='color: red;' class='txtc'>Video has been successfully uploaded</p>";
          } else {
            _("txt_holder").innerHTML = "<p style='color: red;' class='txtc'>Oops... It seems that an error occurred during the uploading: "+t[0]+"</p>";
            _("asd").style.display = "block";
            _("as").style.display = "block";
            _("file").style.display = "block";
            _("choose_file").style.display = "block";
          }
        }
        function errorHandler(callback) {
          _("txt_holder").innerHTML = "Upload Failed";
          _("asd").style.display = "block";
          _("as").style.display = "block";
          _("file").style.display = "block";
          _("choose_file").style.display = "block";
        }
        function abortHandler(canCreateDiscussions) {
          _("txt_holder").innerHTML = "Upload Aborted";
          _("asd").style.display = "block";
          _("as").style.display = "block";
          _("file").style.display = "block";
          _("choose_file").style.display = "block";
        }
        var isb = "not_set";
        function deleteVideo(styles, id) {
          if (1 != confirm("Confirm you want to delete this video. Please note that once you deleted we cannot reset it!")) {
            return false;
          }
          if ("" == styles) {
            _("overlay").style.display = "block";
            _("overlay").style.opacity = .5;
            _("dialogbox").style.display = "block";
            _("dialogbox").innerHTML = '<p style="font-size: 18px; margin: 0px;">An error occured</p><p>Unfortunately an unknown error has occured with deleting your video. Please try again later and check everything is proper.</p><br /><button id="vupload" style="position: absolute; right: 3px; bottom: 3px;" onclick="closeDialog()">Close</button>';
            document.body.style.overflow = "hidden";
          } else {
            var xhr = ajaxObj("POST", "/php_parsers/video_parser.php");
            xhr.onreadystatechange = function() {
              if (1 == ajaxReturn(xhr)) {
                if ("delete_success" == xhr.responseText) {
                  location.reload();
                  window.scrollTo(0, 0);
                } else {
                  _("overlay").style.display = "block";
                  _("overlay").style.opacity = .5;
                  _("dialogbox").style.display = "block";
                  _("dialogbox").innerHTML = '<p style="font-size: 18px; margin: 0px;">An error occured</p><p>Unfortunately an unknown error has occured with deleting your video. Please try again later and check everything is proper.</p><br /><button id="vupload" style="position: absolute; right: 3px; bottom: 3px;" onclick="closeDialog()">Close</button>';
                  document.body.style.overflow = "hidden";
                }
              }
            };
          }
          xhr.send("id=" + styles + "&type=" + id);
        }
        function likeVideo(midiOutObj, name, i, forceOptional) {
          var request = ajaxObj("POST", "/php_parsers/video_parser.php");
          request.onreadystatechange = function() {
            if (1 == ajaxReturn(request)) {
              if ("like_success" == request.responseText) {
                _(i).innerHTML = '<div id="likeBtnv_' + name + '"><a href="#" onclick="return false;" onmousedown="likeVideo(\'unlike\',\'' + name + "','likeBtnv_" + name + '\')" style="float: right;"><img src="/images/fillthumb.png" width="18" height="18" title="Dislike" class="icon_hover_art"></a></div>';
                _("ion_" + name).innerHTML = '<p style="font-size: 12px !important; float: left; margin-top: 0px; margin-bottom: 0px;" id="ion_' + name + '">&#9658; You liked this video</p>';
              } else {
                if ("unlike_success" == request.responseText) {
                  _(i).innerHTML = '<div id="likeBtnv_' + name + '"><a href="#" onclick="return false;" onmousedown="likeVideo(\'like\',\'' + name + "','likeBtnv_" + name + '\')" style="float: right;"><img src="/images/nf.png" width="18" height="18" title="Like" class="icon_hover_art"></a></div>';
                  _("ion_" + name).innerHTML = '<p style="font-size: 12px !important; float: left; margin-top: 0px; margin-bottom: 0px;" id="ion_' + name + '">&#9658; You did not like this video, yet</p>';
                } else {
                  _("overlay").style.display = "block";
                  _("overlay").style.opacity = .5;
                  _("dialogbox").style.display = "block";
                  _("dialogbox").innerHTML = '<p style="font-size: 18px; margin: 0px;">An error occured</p><p>Unfortunately an unknown error has occured with your video like. Please try again later and check everything is proper.</p><br /><button id="vupload" style="position: absolute; right: 3px; bottom: 3px;" onclick="closeDialog()">Close</button>';
                  document.body.style.overflow = "hidden";
                }
              }
            }
          };
          request.send("type=" + midiOutObj + "&id=" + name);
        }
        function verySlow() {
          vid.playbackRate = .25;
        }
        function slow() {
          vid.playbackRate = .5;
        }
        function normal() {
          vid.playbackRate = 1;
        }
        function fast() {
          vid.playbackRate = 1.5;
        }
        function veryFast() {
          vid.playbackRate = 2;
        }
        function startVidW(video, type) {
          if (0 == mobilecheck) {
            if (video.paused) {
              video.play();
              _("text_" + type + "_q").style.display = "none";
              _("text_" + type).style.display = "none";
            } else {
              video.pause();
              _("text_" + type + "_q").style.display = "block";
              _("text_" + type).style.display = "block";
            }
          }
        }
        function showVCB(e, islongclick) {
          e.style.display = "block";
        }
        function hideVCB(e, islongclick) {
          e.style.display = "none";
        }
        function getLVideos() {
          var value = _("searchArt").value;
          if ("" == value) {
            return _("vidSearchResults").style.display = "none", false;
          }
          var result = encodeURI(value);
          window.location = "/search_videos/" + result + "&uU=<?php echo $u; ?>";
        }
        function getVideos(txt) {
          if ("" == txt) {
            return _("vidSearchResults").style.display = "none", false;
          }
          _("vidSearchResults").style.display = "block";
          if ("" == _("vidSearchResults").innerHTML) {
            _("vidSearchResults").innerHTML = '<img src="/images/rolling.gif" width="30" height="30">';
          }
          var decdata = encodeURI(txt);
          var xhr = new XMLHttpRequest;
          xhr.open("POST", "/video_exec.php", true);
          xhr.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
          xhr.onreadystatechange = function() {
            if (4 == xhr.readyState && 200 == xhr.status) {
              var response = xhr.responseText;
              if ("" != response) {
                _("vidSearchResults").innerHTML = response;
              }
            }
          };
          xhr.send("a=" + decdata + "&u=<?php echo $u; ?>");
        }
        function closeDialog() {
          return _("dialogbox").style.display = "none", _("overlay").style.display = "none", _("overlay").style.opacity = 0, document.body.style.overflow = "auto", false;
        }
        function scrollFunction() {
          _("vidSearchResults").style.display = "none";
          _("searchArt").value = "";
        }
        function changeSetts(name) {
          var cancel = _("settings_menu_" + name);
          if ("block" == cancel.style.display) {
            cancel.style.display = "none";
          } else {
            cancel.style.display = "block";
          }
        }
        window.onscroll = scrollFunction;
    </script>
</head>
<body style="overflow-x: hidden;">
    <?php require_once 'template_pageTop.php'; ?>
    <div id="overlay"></div>
    <div id="dialogbox"></div>
    <div id="pageMiddle_2">
        <div id="artSearch">
            <div id="artSearchInput">
                <input id="searchArt" type="text" autocomplete="off" onkeyup="getVideos(this.value)" placeholder="Search among videos by their name or description">
                <div id="artSearchBtn" onclick="getLVideos()"><img src="/images/searchnav.png" width="17" height="17"></div>
            </div>
            <div class="clear"></div>
        </div>
        <div id="vidSearchResults" class="longSearches"></div>
        <br />
        <?php if($isOwner == "yes"){ ?><?php echo $video_form; ?><?php } ?>
        <div id="data_holder">
            <div>
                <div><span><?php echo $cntMyvids; ?></span> videos</div>
                <div><span><?php echo $cntLikesGot; ?></span> likes got</div>
            </div>
        </div>

        <button id="sort" class="main_btn_fill">Filter videos</button>
        <div id="sortTypes">
            <div class="gridDiv">
                <p class="mainHeading">Publish date</p>
                <div id="sort_0">Newest to oldest</div>
                <div id="sort_1">Oldest to newest</div>
            </div>
            <div class="gridDiv">
                <p class="mainHeading">Description</p>
                <div id="sort_2">Alphabetical order</div>
                <div id="sort_3">Reverse alphabetical order</div>
            </div>
            <div class="gridDiv">
                <p class="mainHeading">Title</p>
                <div id="sort_4">Alphabetical order</div>
                <div id="sort_5">Reverse alphabetical order</div>
            </div>
            <div class="gridDiv">
                <p class="mainHeading">Duration</p>
                <div id="sort_6">Longest to shortest</div>
                <div id="sort_7">Shortest to longest</div>
            </div>
            <div class="clear"></div>
        </div>
        <div class="clear"></div>
        <hr class="dim">

        <?php echo $info_vid_user; ?>
        
        <div id="holdit" class="ppForm mvidHolder"><?php echo $echo_videos; ?></div>
        <div class="clear"></div>
        <hr class="dim">
        <div id="data_holder">
            <div>
                <div><span><?php echo $cntRels; ?></span> related videos</div>
            </div>
        </div>
        <div class="vRelHolder ppForm" id="vRelHolder">
            <?php echo $related_vids; ?>
        </div>
        <div class="clear"></div>
    </div>
    <?php require_once 'template_pageBottom.php'; ?>
    <script type="text/javascript">
        var videos = document.querySelectorAll("video");
        var i = 0;
        for (; i < videos.length; i++) {
          videos[i].addEventListener("play", function() {
            pauseAll(this);
          }, true);
        }
        function pauseAll(callback) {
          var i = 0;
          for (; i < videos.length; i++) {
            if (videos[i] != callback && videos[i].played.length > 0 && !videos[i].paused) {
              videos[i].pause();
            }
          }
        }

        var uname = "<?php echo $u; ?>";
        var pn = "<?php echo $pagenum; ?>";

        function getCookie(res) {
          var id = res + "=";
          var hhmmssArr = decodeURIComponent(document.cookie).split(";");
          var i = 0;
          for (; i < hhmmssArr.length; i++) {
            var t = hhmmssArr[i];
            for (; " " == t.charAt(0);) {
              t = t.substring(1);
            }
            if (0 == t.indexOf(id)) {
              return t.substring(id.length, t.length);
            }
          }
          return "";
        }
        function setDark() {
          var mapboxCSS = "thisClassDoesNotExist";
          if (!document.getElementById(mapboxCSS)) {
            var div = document.getElementsByTagName("head")[0];
            var link = document.createElement("link");
            link.id = mapboxCSS;
            link.rel = "stylesheet";
            link.type = "text/css";
            link.href = "/style/dark_style.css";
            link.media = "all";
            div.appendChild(link);
          }
        }
        var isdarkm = getCookie("isdark");
        if ("yes" == isdarkm) {
          setDark();
        }

            $( "#sort" ).click(function() {
              $( "#sortTypes" ).slideToggle( 200, function() {
                // Animation complete.
              });
            });

        for(let i = 0; i < 8; i++){
            addListener("sort_" + i, "sort_" + i);
        }

        function addListener(onw, w){
            _(onw).addEventListener("click", function(){
                _("holdit").innerHTML = '<img src="/images/rolling.gif" width="30" height="30">';
                filterArts(w);
            });
        }

        function filterArts(otype){
            changeStyle(otype);
            let req = new XMLHttpRequest();
            req.open("GET", "/videos.php?u=<?php echo $u; ?>&otype=" + otype, false);
            req.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
            req.onreadystatechange = function(){
                if(req.readyState == 4 && req.status == 200){
                    _("holdit").innerHTML = req.responseText;
                }
            }
            req.send();
        }

        function changeStyle(otype){
            _(otype).style.color = "red";
            for(let i = 0; i < 8; i++){
                if("sort_" + i != otype) _("sort_" + i).style.color = "black";
            }
        }

        changeStyle("sort_0");

         function doDD(first, second){
            $( "#" + first ).click(function() {
              $( "#" + second ).slideToggle( "fast", function() {
                
              });
              _(second).innerHTML += "<hr class='dim'>";
            });
          }

          doDD("ccSu", "suDD");
    </script>
</body>
</html>