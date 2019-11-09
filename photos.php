<?php
    include_once("php_includes/check_login_statues.php");
    require_once 'timeelapsedstring.php';
    require_once 'safe_encrypt.php';
    require_once 'headers.php';
    require_once 'phpmobc.php';
    // Make sure the _GET "u" is set, and sanitize it
    $u = "";
    if(isset($_GET["u"])){
        $u = mysqli_real_escape_string($conn, $_GET["u"]);
    } else {
        header("location: /index");
        exit();
    }
    
    $one = "1";
    if(isset($_SESSION["username"]) && $_SESSION["username"] != ""){
        // Select the member from the users table
        $sql = "SELECT * FROM users WHERE username=? AND activated=? LIMIT 1";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss",$log_username,$one);
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

    $isMob = mobc();

    $gallery_list = "";
    $photo_form = "";
    $myself = "Myself";
    $family = "Family";
    $pets = "Pets";
    $friends = "Friends";
    $games = "Games";
    $freetime = "Freetime";
    $sports = "Sports";
    $knowledge = "Knowledge";
    $hobbies = "Hobbies";
    $working = "Working";
    $relations = "Relations";
    $other = "Other";

    $otype = "sort_0";

    // Check to see if the viewer is the account owner
    $isOwner = "no";
    if($u == $log_username && $user_ok == true){
        $isOwner = "yes";
        $photo_form  = '<form id="photo_form" class="styleform" style="width: 100%; margin-top: 20px;" enctype="multipart/form-data" method="post" class="pcpk">';
        $photo_form .=   '<p style="font-size: 18px; margin-top: 0px; text-align: center;">Upload a new photo</p>';
        $photo_form .=   '<select name="gallery" class="ssel" style="margin-top: 0;" required>';
        $photo_form .=     '<option value="" selected="true" disabled="true">Choose gallery</option>';
        $photo_form .=     '<option value="Myself">Myself</option>';
        $photo_form .=     '<option value="Family">Family</option>';
        $photo_form .=     '<option value="Pets">Pets</option>';
        $photo_form .=     '<option value="Friends">Friends</option>';
        $photo_form .=     '<option value="Games">Games</option>';
        $photo_form .=     '<option value="Freetime">Freetime</option>';
        $photo_form .=     '<option value="Sports">Sports</option>';
        $photo_form .=     '<option value="Knowledge">Knowledge</option>';
        $photo_form .=     '<option value="Hobbies">Hobbies</option>';
        $photo_form .=     '<option value="Working">Working</option>';
        $photo_form .=     '<option value="Relations">Relations</option>';
        $photo_form .=     '<option value="Other">Other</option>';
        $photo_form .=   '</select>';
        $photo_form .=   ' &nbsp;';
        $photo_form .=   '<input type="file" id="file" class="inputfile" accept="image/*" required onchange="showfile()">';
        $photo_form .=   '<label for="file" id="choose_file">Choose a file</label>&nbsp;<span id="sel_f">No files selected</span><br />';
        $photo_form .=   '<textarea id="description" style="height: 60px;" name="description" placeholder="Describe your photo in a few words ..." onkeyup="statusMax(this,1000)" style="height: 40px;"></textarea>';
        $photo_form .=   '<p style="margin-bottom: 0;"><input type="button" style="display: block; margin: 0 auto;" value="Upload photo" class="fixRed main_btn_fill" onclick="uploadPhoto()"><p style="font-size: 14px; text-align: center; ">The maximum file size limit is 5MB. Please make sure your image is below this number</p></p><p style="font-size: 14px; margin-top: 0; text-align: center;" id="locht"><b style="font-size: 14px;">Tip: </b> upload up to 5 photos at the same time by dragging & dropping them into this field. <br>For further information please visit the <a href="/help">help &amp; support</a> page!</p>';
        $photo_form .=   '<div id="pbc"><div id="progressBar"></div><div id="pbt"></div></div>';
        $photo_form .=   '<div id="percentage"></div>';
        $photo_form .=   '<div id="p_status"></div>';
        $photo_form .= '</form>';
    }
    // Pagination
    // This first query is just to get the total count of rows
    $sql = "SELECT COUNT(id) FROM photos WHERE user=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s",$u);
    $stmt->execute();
    $stmt->bind_result($rows);
    $stmt->fetch();
    $stmt->close();
    // Here we have the total row count
    // This is the number of results we want displayed per page
    if($isMob){
        $page_rows = 30;
    }else{
        $page_rows = 45;
    }
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
         /*First we check if we are on page one. If we are then we don't need a link to 
           the previous page or the first page so we do nothing. If we aren't then we
           generate links to the first page, and to the previous page.*/
        if ($pagenum > 1) {
            $previous = $pagenum - 1;
            $paginationCtrls .= '<a href="/photos/'.$u.'&pn='.$previous.'">Previous</a> &nbsp; &nbsp; ';
            // Render clickable number links that should appear on the left of the target page number
            for($i = $pagenum-4; $i < $pagenum; $i++){
                if($i > 0){
                    $paginationCtrls .= '<a href="/photos/'.$u.'&pn='.$i.'">'.$i.'</a> &nbsp; ';
                }
            }
        }
        // Render the target page number, but without it being a link
        $paginationCtrls .= ''.$pagenum.' &nbsp; ';
        // Render clickable number links that should appear on the right of the target page number
        for($i = $pagenum+1; $i <= $last; $i++){
            $paginationCtrls .= '<a href="/photos/'.$u.'&pn='.$i.'">'.$i.'</a> &nbsp; ';
            if($i >= $pagenum+4){
                break;
            }
        }
        // This does the same as above, only checking if we are on the last page, and then generating the "Next"
        if ($pagenum != $last) {
            $next = $pagenum + 1;
            $paginationCtrls .= ' &nbsp; &nbsp; <a href="/photos/'.$u.'&pn='.$next.'">Next</a> ';
        }
    }

    $sql = "SELECT user FROM photos WHERE user=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s",$u);
    $stmt->execute();
    $stmt->store_result();
    $stmt->fetch();
    $numrows = $stmt->num_rows;
    $belong = "";
    if($numrows > 0){
        $belong = '<p style="clear: left; text-align: center; color: #999;">These photos belong to <a href="/user/'.$u.'/">'.$u.'</a></p>';
    }
    $stmt->close();

    // Get number of all photos
    $sql = "SELECT COUNT(id) FROM photos WHERE user=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s",$u);
    $stmt->execute();
    $stmt->bind_result($count_all);
    $stmt->fetch();
    $stmt->close();

    // Count how many posts are there
    $a = "a";
    $sql = "SELECT COUNT(id) FROM photos_status WHERE account_name = ? AND type = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss",$u,$a);
    $stmt->execute();
    $stmt->bind_result($post_count);
    $stmt->fetch();
    $stmt->close();

    // Count how many replies are there
    $b = "b";
    $sql = "SELECT COUNT(id) FROM photos_status WHERE account_name = ? AND type = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss",$u,$b);
    $stmt->execute();
    $stmt->bind_result($reply_count);
    $stmt->fetch();
    $stmt->close();

    // Count how many status are there
    $sql = "SELECT COUNT(id) FROM photos_status WHERE account_name = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s",$u);
    $stmt->execute();
    $stmt->bind_result($all_count);
    $stmt->fetch();
    $stmt->close();

    $sql = "SELECT COUNT(id) FROM photo_stat_likes WHERE username = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s",$u);
    $stmt->execute();
    $stmt->bind_result($out_likes);
    $stmt->fetch();
    $stmt->close();

    $countRels = 0;
    $countMine = 0;
    // Get related photos
    // FIRST GET FRIENDS ARRAY
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
    $allfmy = implode("','", $all_friends);
    $related_p = "";
    $sql = "SELECT * FROM photos WHERE user IN ('$allfmy') ORDER BY RAND() LIMIT 15";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $result = $stmt->get_result();
    while($row = $result->fetch_assoc()){
        $countRels++;
        $photoid = $row["id"];
        $uploader = $row["user"];
        $uploaderori = urlencode($uploader);
        $upim = $uploader;
        $gallery_u = $row["gallery"];
        $filename_u = $row["filename"];
        $description_u = $row["description"];
        $uploaddate_u_ = $row["uploaddate"];
        $uploaddate_u = strftime("%b %d, %Y", strtotime($uploaddate_u_));

        $uds = time_elapsed_string($uploaddate_u_);
        
        $uploaddate_u .= " (".$uds." ago)";
        
        $pcurlk = '/user/'.$upim.'/'.$filename_u.'';

        list($width, $height) = getimagesize('user/' . $upim . '/' . $filename_u . '');
            
        $related_p .= "<a href='/photo_zoom/" . $uploaderori . "/" . $filename_u . "'><div class='pccanvas' style='width: 100%;'><div data-src=\"" . $pcurlk . "\" class='lazy-bg'><div id='photo_heading' style='width: auto !important; margin-top: 0px; position: static;'>" . $width . " x " . $height . "</div></div></div></a>";
    }
    $stmt->close();
    $nof = false;
    if($allfmy == ""){
        $nof = true;
        $sql = "SELECT * FROM photos WHERE user != ? ORDER BY RAND() LIMIT 30";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s",$log_username);
        $stmt->execute();
        $result = $stmt->get_result();
        while($row = $result->fetch_assoc()){
            $countRels++;
            $photoid = $row["id"];
            $uploader = $row["user"];
            $uploaderori = urlencode($uploader);
            $upim = $uploader;
            $gallery_u = $row["gallery"];
            $filename_u = $row["filename"];
            $description_u = $row["description"];
            $uploaddate_u_ = $row["uploaddate"];
            $uploaddate_u = strftime("%R, %b %d, %Y", strtotime($uploaddate_u_));

            list($width, $height) = getimagesize('user/' . $upim . '/' . $filename_u . '');

            $pcurlk = '/user/'.$upim.'/'.$filename_u.'';

            $related_p .= "<a href='/photo_zoom/" . $uploaderori . "/" . $filename_u . "'><div class='pccanvas' style='width: 100%;'><div data-src=\"" . $pcurlk . "\" class='lazy-bg'><div id='photo_heading' style='width: auto !important; margin-top: 0px; position: static;'>" . $width . " x " . $height . "</div></div></div></a>";
        }
        $stmt->close();
    }

        // Initialize gallery vars
        $egalsMyself = "Myself";
        $egalsFamily = "Family";
        $egalsPets = "Pets";
        $egalsFriends = "Friends";
        $egalsGames = "Games";
        $egalsFreetime = "Freetime";
        $egalsSports = "Sports";
        $egalsKnowledge = "Knowledge";
        $egalsHobbies = "Hobbies";
        $egalsWorking = "Working";
        $egalsRelations = "Relations";
        $egalsOther = "Other";
        $egalsDaD = "Drag & Drop";

        if(isset($_GET["otype"]) || $otype != ""){
            $typeExists = false;
            if(isset($_GET["otype"])){
                $otype = mysqli_real_escape_string($conn, $_GET["otype"]);
                $typeExists = true;
            }
            if($otype == "sort_0"){
        	    $sql = "SELECT * FROM photos WHERE user = ? ORDER BY uploaddate DESC $limit";
            }else if($otype == "sort_1"){
                $sql = "SELECT * FROM photos WHERE user = ? ORDER BY uploaddate ASC $limit";
            }else if($otype == "sort_15"){
                $sql = "SELECT * FROM photos WHERE user = ? AND description IS NOT NULL ORDER BY description $limit";
            }else if($otype == "sort_16"){
                $sql = "SELECT * FROM photos WHERE user = ? AND description IS NOT NULL ORDER BY description DESC $limit";
            }else if($otype != "sort_0" && $otype != "sort_1" && $otype != "sort_15" && $otype != "sort_16"){
                $sql = "SELECT * FROM photos WHERE user = ? AND gallery = ? ORDER BY uploaddate DESC $limit";
            }

    	    $stmt = $conn->prepare($sql);
            if($otype == "sort_2"){
                $stmt->bind_param("ss",$u,$egalsMyself);
            }else if($otype == "sort_3"){
                $stmt->bind_param("ss",$u,$egalsFamily);
            }else if($otype == "sort_4"){
                $stmt->bind_param("ss",$u,$egalsPets);
            }else if($otype == "sort_5"){
                $stmt->bind_param("ss",$u,$egalsFriends);
            }else if($otype == "sort_6"){
                $stmt->bind_param("ss",$u,$egalsGames);
            }else if($otype == "sort_7"){
                $stmt->bind_param("ss",$u,$egalsFreetime);
            }else if($otype == "sort_8"){
                $stmt->bind_param("ss",$u,$egalsSports);
            }else if($otype == "sort_9"){
                $stmt->bind_param("ss",$u,$egalsKnowledge);
            }else if($otype == "sort_10"){
                $stmt->bind_param("ss",$u,$egalsHobbies);
            }else if($otype == "sort_11"){
                $stmt->bind_param("ss",$u,$egalsWorking);
            }else if($otype == "sort_12"){
                $stmt->bind_param("ss",$u,$egalsRelations);
            }else if($otype == "sort_13"){
                $stmt->bind_param("ss",$u,$egalsOther);
            }else if($otype == "sort_14"){
                $stmt->bind_param("ss",$u,$egalsDaD);
            }else{
                $stmt->bind_param("s",$u);
            }
    	    $stmt->execute();
    	    $res = $stmt->get_result();
    	    while($row = $res->fetch_assoc()){
                $countMine++;
    	        $gal = $row["gallery"];
    	        $filename = $row["filename"];
    	        $des = $row["description"];
    	        $echo_des = mb_substr($des, 0, 20, "utf-8");
    	        if($echo_des == NULL){
    	            $echo_des = "No description given ...";
    	        }
    	        $uploaddate_as = $row["uploaddate"];
    	        $uploaddate = strftime("%b %d, %Y", strtotime($uploaddate_as));
    	        $agoform = time_elapsed_string($uploaddate_as);
    	        $pcurlk = '/user/'.$u.'/'.$filename;

    	        list($width,$height) = getimagesize('user/'.$u.'/'.$filename.'');
    	        
    	        $sourceURL = "";
    	        if($typeExists){
    	            $sourceURL = "style='background-image: url(\"$pcurlk\");'";
    	        }else{
    	            $sourceURL = "data-src=\"" . $pcurlk . "\" class='lazy-bg'";
    	        }

    	        $gallery_list .= "<a href='/photo_zoom/" . $u . "/" . $filename . "'><div class='pccanvas' style='width: 100%;'><div ".$sourceURL."><div id='photo_heading' style='width: auto !important; margin-top: 0px; position: static;'>" . $width . " x " . $height . "</div></div></div></a>";
    	    }
    	    $stmt->close();
            if(isset($_GET["otype"])){
                if($gallery_list != ""){
                    echo $gallery_list;
                }else{
                    echo "<p style='color: #999; text-align: center;'>There are no such photos fitting this criteria!</p>";
                }
                exit();
            }
        }
	$isP = true;
	if(!isset($_SESSION["username"]) || $_SESSION["username"] == ""){
        $related_p = "<p style='color: #999;' class='txtc'>You need to be <a href='/login'>logged in</a> in order to see related photos</p>";
        $isP = false;
    }
?>
<!DOCTYPE html>
<html>
    <head>
    <meta charset="UTF-8">
    <meta lang="en">
    <title><?php echo $u; ?>'s Photos</title>
    <meta name="description" content="Visit <?php echo $u; ?>&#39;s photos in his/her photo gallery. Click on the certain images in order to see it in a bigger view.">
    <meta name="keywords" content="<?php echo $u; ?> photos, photo gallery, all photo galleries, photos of <?php echo $u; ?>, pearscom photos">
    <meta name="author" content="Pearscom">
    <link rel="icon" href="/images/newfav.png" type="image/x-icon">
    <link rel="stylesheet" href="/style/style.css">
    	  <link rel="manifest" href="/manifest.json">
  <meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="#282828">
<meta name="apple-mobile-web-app-title" content="Pearscom">
<link rel="apple-touch-icon" href="/images/icons/icon-152x152.png">
<meta name="theme-color" content="#282828" />
    <script src="/js/main.js"></script>
    <script src="/js/ajax.js"></script>
    <script src="/js/mbc.js"></script>
    <style type="text/css">
        @media only screen and (max-width: 1000px){ 
          #searchArt{
            width: 90% !important;
          }

          #artSearchBtn{
            width: 10% !important;
          }

          .longSearches{
            width: calc(90% - 15px) !important;
            }

          @media only screen and (max-width: 500px){
            #searchArt {
            width: 85% !important;
          }

          #artSearchBtn {
            width: 15% !important;
          }

          .longSearches{
            width: calc(100% - 30px) !important;
            }
        }
    }
    </style>
    <script type="text/javascript">
        function deletePhoto(e)
            /*Scope Closed:false | writes:true*/
            {
                if (1 != confirm('Press OK to confirm the delete action on this photo.'))
                    return false;
                var o = ajaxObj('POST', '/php_parsers/photo_system.php');
                        o.onreadystatechange = function ()
                    /* Called:undefined | Scope Closed:false| writes:true*/
                    {
                        1 == ajaxReturn(o) && o.responseText == 'deleted_ok' && (_('overlay').style.display = 'block', _('overlay').style.opacity = 0.5, _('dialogbox').style.display = 'block', _('dialogbox').innerHTML = '<p style="font-size: 18px; margin: 0px;">Photo deleted successfully</p><p>You have successfully deleted your photo. We will now refresh the page for you.</p><br /><button id="vupload" style="position: absolute; right: 3px; bottom: 3px;" onclick="closeDialog()">Close</button>', document.body.style.overflow = 'hidden', window.location = '/photos/<?php echo $u; ?>');
                    };
                o.send('delete=photo&id=' + e);
            }
        function statusMax(e, o)
            /*Scope Closed:false | writes:true*/
            {
                e.value.length > o && (_('overlay').style.display = 'block', _('overlay').style.opacity = 0.5, _('dialogbox').style.display = 'block', _('dialogbox').innerHTML = '<p style="font-size: 18px; margin: 0px;">Maximum character limit reached</p><p>For some reasons we limited the number of characters that you can write at the same time. Now you have reached this limit.</p><br /><button id="vupload" style="position: absolute; right: 3px; bottom: 3px;" onclick="closeDialog()">Close</button>', document.body.style.overflow = 'hidden', e.value = e.value.substring(0, o));
            }
        function showfile()
            /*Scope Closed:false | writes:true*/
            {
                var e = _('file').value.substr(12);
                _('sel_f').innerHTML = '\xA0' + _('file').value.substr(12);
            }
        function uploadPhoto()
            /*Scope Closed:false | writes:true*/
            {
                var e = _('file').files[0], o = _('cgal').value, t = _('description').value, i = _('p_status'), n = _('vupload');
                if (e != '' && cgal != '' || 'You did not give a gallery or a photo!', e.type != 'image/jpg' && e.type != 'image/jpeg' && e.type != 'image/png' && e.type != 'image/gif') {
                    _('overlay').style.display = 'block';
                    _('overlay').style.opacity = 0.5;
                    _('dialogbox').style.display = 'block';
                    _('dialogbox').innerHTML = '<p style="font-size: 18px; margin: 0px;">File type is not supported</p><p>The image that you want to upload has an unvalid extension given that we do not support. The allowed file extensions are: jpg, jpeg, png and gif. For further information please visit the help page.</p><button id="vupload" style="position: absolute; right: 3px; bottom: 3px;" onclick="closeDialog()">Close</button>';
                    document.body.style.overflow = 'hidden';
                    return false;
                }
                        i.innerHTML = '<img src="/images/rolling.gif" width="30" height="30">';
                n.style.display = 'none';
                _('pbc').style.display = 'block';
                var a = new FormData();
                        a.append('stPic_photo', e);
                a.append('cgal', o);
                a.append('des', t);
                var p = new XMLHttpRequest();
                        p.upload.addEventListener('progress', progressHandler, false);
                p.addEventListener('load', completeHandler, false);
                p.addEventListener('error', errorHandler, false);
                p.addEventListener('abort', abortHandler, false);
                p.open('POST', '/php_parsers/photo_system.php');
                p.send(a);
            }
        function progressHandler(e)
            /*Scope Closed:false | writes:true*/
            {
                var o = e.loaded / e.total * 100, t = Math.round(o);
                        _('progressBar').style.width = Math.round(o) + '%';
                _('pbt').innerHTML = Math.round(o) + '%';
            }
        function completeHandler(e)
            /*Scope Closed:false | writes:true*/
            {
                var o = e.target.responseText.split('|');
                        _('progressBar').style.width = '0%';
                _('progressBar').style.display = 'none';
                o[0] == 'upload_complete' ? _('p_status').innerHTML = '<p style=\'font-size: 14px; margin: 0px; padding: 0px;\'>Photo has been successfully uploaded!<img src=\'/images/correct.png\' width=\'11\' height=\'11\'></p>' : (_('p_status').innerHTML = '<p style=\'font-size: 14px; margin: 0px; padding: 0px;\'>An unknown error has occured! Please try again later!<img src=\'/images/wrong.png\' width=\'11\' height=\'11\'></p>', _('vupload').style.display = 'block', _('progressBar').value = 0, _('p_status').innerHTML = '');
            }
        function errorHandler(e)
            /*Scope Closed:false | writes:true*/
            {
                        _('p_status').innerHTML = 'Upload Failed';
                _('vupload').style.display = 'block';
            }
        function abortHandler(e)
            /*Scope Closed:false | writes:true*/
            {
                        _('p_status').innerHTML = 'Upload Aborted';
                _('vupload').style.display = 'block';
            }
        function getLPhotos()
            /*Scope Closed:false | writes:true*/
            {
                var e = _('searchArt').value;
                if (e == '') {
                    _('phoSearchResults').style.display = 'none';
                    return false;
                }
                var o = encodeURI(e);
                window.location = '/photo_search/' + encodeURI(e) + "&uU=<?php echo $u; ?>";
            }
        function getPhotos(e)
            /*Scope Closed:false | writes:true*/
            {
                if ('' == e) {
                    _('phoSearchResults').style.display = 'none';
                    return false;
                }
                        _('phoSearchResults').style.display = 'block';
                '' == _('phoSearchResults').innerHTML && '<img src="/images/rolling.gif" width="30" height="30">';
                var o = encodeURI(e), t = new XMLHttpRequest();
                        t.open('POST', '/photo_exec.php', true);
                t.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
                t.onreadystatechange = function ()
                    /* Called:undefined | Scope Closed:false| writes:true*/
                    {
                        if (4 == t.readyState && 200 == t.status) {
                            var e = t.responseText;
                            '' != e && (_('phoSearchResults').innerHTML = e);
                        }
                    };
                t.send('a=' + encodeURI(e) + "&u=<?php echo $u; ?>");
            }
    </script>
</head>
<body style="overflow-x: hidden;">
    <?php include_once("template_pageTop.php"); ?>
    <div id="pageMiddle_2">
    <div id="artSearch">
        <div id="artSearchInput">
            <input id="searchArt" type="text" autocomplete="off" onkeyup="getPhotos(this.value)" placeholder="Search among photos by gallery or description">
            <div id="artSearchBtn" onclick="getLPhotos()"><img src="/images/searchnav.png" width="17" height="17"></div>
        </div>
        <div class="clear"></div>
    </div>
    <div id="phoSearchResults" class="longSearches"></div>
    <?php if($isOwner == "yes"){ ?>
    <div id="photo_form">
    <?php echo $photo_form; ?>
            <div class="clear"></div>
            <?php if($isMob){ ?>
                <hr class="dim">
            <?php } ?>
        </div>
        <?php } ?>
        <?php if($isOwner == "no"){ ?>
        <?php if(isset($_SESSION["username"]) && $_SESSION["username"] != ""){ ?>
        <?php }else{ ?>
            <p style="font-size: 16px; color: #999;" class="txtc">In order to upload photos please <a href="/login">log in</a>. Haven&#39;t got an account? <a href="/signup">Sign up</a></p>
            <?php } ?>
                <div id="data_holder">
                    <div>
                        <div><span><?php echo $all_count; ?></span> comments got</div>
                        <div><span><?php echo $out_likes; ?></span> likes given</div>
                    </div>
                </div>
                <?php if(isset($_SESSION["username"]) && $_SESSION["username"] != ""){ ?>
                <a href="/photos/<?php echo $log_username; ?>" style="text-align: center; display: block;">Upload photos to my galleries</a>
                <div class="clear"></div>
                <?php } ?>
                <?php } ?>
                    <div id="data_holder">
                        <div>
                            <div><span><?php echo $count_all; ?></span> photos</div>
                        </div>
                    </div>

                    <button id="sort" class="main_btn_fill">Filter Photos</button>
                    <div id="sortTypes">
                        <div class="gridDiv">
                            <p class="mainHeading">Publish date</p>
                            <div id="sort_0">Newest to oldest</div>
                            <div id="sort_1">Oldest to newest</div>
                        </div>
                        <div class="gridDiv">
                            <p class="mainHeading">Gallery (1)</p>
                            <div id="sort_2">Myself</div>
                            <div id="sort_3">Family</div>
                            <div id="sort_4">Pets</div>
                            <div id="sort_5">Friends</div>
                            <div id="sort_6">Games</div>
                            <div id="sort_7">Freetime</div>
                            <div id="sort_8">Sports</div>
                        </div>
                        <div class="gridDiv">
                            <p class="mainHeading">Description</p>
                            <div id="sort_15">Alphabetical order</div>
                            <div id="sort_16">Reverse alphabetical order</div>
                        </div>
                        <div class="gridDiv">
                            <p class="mainHeading">Gallery (2)</p>
                            <div id="sort_9">Knowledge</div>
                            <div id="sort_10">Hobbies</div>
                            <div id="sort_11">Working</div>
                            <div id="sort_12">Relations</div>
                            <div id="sort_13">Other</div>
                            <div id="sort_14">Drag &amp; Drop</div>
                        </div>
                        <div class="clear"></div>
                    </div>
                    <div class="clear"></div>
                    <hr class="dim">

                    <div class="flexibleSol mainPhotRel" id="userFlexArts"><?php echo $gallery_list; ?></div>
                    <div class="clear"></div>
                    <div id="paginationCtrls" style="text-align: center;">
                        <?php echo $paginationCtrls; ?>
                    </div>
                    <hr class="dim">

                    <div id="data_holder">
                        <div>
                            <div><span><?php echo $countRels; ?></span> related photos</div>
                        </div>
                    </div>
                    <div class="flexibleSol mainPhotRel" id="userFlexArts">
                        <?php echo $related_p; ?>
                    </div>
                    <div class="clear"></div>
                
                    <?php if($count_all == 0 && $isOwner == "yes"){ ?><i style="font-size: 14px;">You have not uploaded any videos yet ...</i>
                        <?php }else if($count_all == 0 && $isOwner == "no"){ ?><i style="font-size: 14px;">Unfortunately, <?php echo $u; ?> has not uploaded any videos yet ...</i>
                            <?php } ?>
                                
                                <div class="clear"></div>
                                
                                <?php echo $belong; ?>
                                    <br />
        </div>
    <?php include_once("template_pageBottom.php"); ?>
    <script type="text/javascript">
        var iso = "<?php echo $isOwner; ?>";
        function getCookie(e) {
          var a = e + "=";
          var sorted_changes = decodeURIComponent(document.cookie).split(";");
          var j = 0;
          for (; j < sorted_changes.length; j++) {
            var r = sorted_changes[j];
            for (; " " == r.charAt(0);) {
              r = r.substring(1);
            }
            if (0 == r.indexOf(a)) {
              return r.substring(a.length, r.length);
            }
          }
          return "";
        }

        let beforeInner;
        if("yes" == iso){
            beforeInner = _("photo_form").innerHTML;
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
        "yes" == iso && function() {
          var t = _("photo_form");
          var oldh = _("photo_form").clientHeight;
          t.ondragover = function() {
            return t.innerHTML = "<p class='pcblueform'>Drag and drop your images here</p><div id='pcfillin'></div>", t.style.border = "8px dashed red", t.style.height = oldh + "px", t.style.marginTop = "20px", false;
          };
          t.ondrop = function(event) {
            event.preventDefault();
            (function(params) {
              if ("" == params) {
                return false;
              }
              var formData = new FormData;
              var p = 0;
              for (; p < params.length; p++) {
                formData.append("file[]", params[p]);
              }
              _("pcfillin").innerHTML = '<img src="/images/rolling.gif" width="20" height="20">';
              var i = "<?php echo $u ?>";
              var xhr = new XMLHttpRequest;
              xhr.onload = function() {
                var contents = this.responseText.split("|");
                if ("success" == contents[0]) {
                  _("pcfillin").innerHTML = "";
                  var iExternal = 1;
                  for (; iExternal < contents.length - 1; iExternal++) {
                    var t = "/user/" + i + "/" + contents[iExternal];
                    _("pcfillin").innerHTML += "<a href='/photo_zoom/" + encodeURIComponent(i) + "/" + contents[iExternal] + "'><div class='pccanvas' style='width: calc(20% - 3px); height: 125px;'><div style='background-image: url(\"" + t + "\"); background-repeat: no-repeat; background-position: center; background-size: cover; height: 125px; margin-right: 2px;'></div></div></a>";
                  }
                }
              };
              xhr.open("POST", "/php_parsers/ddupload.php");
              xhr.send(formData);
            })(event.dataTransfer.files);
          };
          t.ondragleave = function() {
            return t.innerHTML = beforeInner, 
            t.style.height = "auto", t.style.border = "none",
            false;
          };
        }();
        var isdarkm = getCookie("isdark");
        if ("yes" == isdarkm) {
          setDark();
        }

	$( "#sort" ).click(function() {
          $( "#sortTypes" ).slideToggle( 200, function() {
            // Animation complete.
          });
        });

    for(let i = 0; i < 17; i++){
        addListener("sort_" + i, "sort_" + i);
    }

    function addListener(onw, w){
        _(onw).addEventListener("click", function(){
            _("userFlexArts").innerHTML = '<img src="/images/rolling.gif" width="30" height="30">';
            filterArts(w);
        });
    }

    function filterArts(otype){
        changeStyle(otype);
        let req = new XMLHttpRequest();
        req.open("GET", "/photos?u=<?php echo $u; ?>&otype=" + otype, false);
        req.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
        req.onreadystatechange = function(){
            if(req.readyState == 4 && req.status == 200){
                _("userFlexArts").innerHTML = req.responseText;
            }
        }
        req.send();
    }

    function changeStyle(otype){
        _(otype).style.color = "red";
        for(let i = 0; i < 17; i++){
            if("sort_" + i != otype) _("sort_" + i).style.color = "black";
        }
    }

    changeStyle("sort_0");
    </script>
</body>
</html>