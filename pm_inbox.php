<?php
    include_once("php_includes/check_login_statues.php");
    require_once 'timeelapsedstring.php';
    require_once 'headers.php';
    require_once 'elist.php';
    // Initialize any variables that the page might echo
    $u = "";
    $mail = "";
    $one = "1";
    $x = "x";
    $zero = "0";
    // Make sure the _GET username is set, and sanitize it
    if(isset($_GET["u"])){
        $u = mysqli_real_escape_string($conn, $_GET["u"]);
    } else {
        header("location: /index");
        exit(); 
    }
    
    if(!isset($_SESSION["username"]) || $user_ok != true || $log_username == "" || $u != $log_username || $_SESSION["username"] == ""){
        header('location: /index');
    }
    
    $otype = "";
    if(isset($_GET["otype"])){
        $otype = mysqli_real_escape_string($conn, $_GET["otype"]);
    }else{
        $otype = "nto";
    }
    
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
    
    $sql = "UPDATE pm SET rread = ?, sread = ? WHERE receiver=? OR sender=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssss",$one,$one,$log_username,$log_username);
    $stmt->execute();
    $stmt->close();

    // This first query is just to get the total count of rows
    $sql = "SELECT * FROM pm WHERE (receiver=? OR sender=?) AND parent=? AND rdelete = ? AND sdelete = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssss",$log_username,$log_username,$x,$zero,$zero);
    $stmt->execute();
    $stmt->bind_result($rows);
    $stmt->fetch();
    $stmt->close();
    echo $rows;
    // Here we have the total row count
    // This is the number of results we want displayed per page
    $page_rows = 10;
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
            $paginationCtrls .= '<a href="/private_messages/'.$u.'&pn='.$previous.'">Previous</a> &nbsp; &nbsp; ';
            // Render clickable number links that should appear on the left of the target page number
            for($i = $pagenum-4; $i < $pagenum; $i++){
                if($i > 0){
                    $paginationCtrls .= '<a href="/private_messages/'.$u.'&pn='.$i.'#">'.$i.'</a> &nbsp; ';
                }
            }
        }
        // Render the target page number, but without it being a link
        $paginationCtrls .= ''.$pagenum.' &nbsp; ';
        // Render clickable number links that should appear on the right of the target page number
        for($i = $pagenum+1; $i <= $last; $i++){
            $paginationCtrls .= '<a href="/private_messages/'.$u.'&pn='.$i.'">'.$i.'</a> &nbsp; ';
            if($i >= $pagenum+4){
                break;
            }
        }
        // This does the same as above, only checking if we are on the last page, and then generating the "Next"
        if ($pagenum != $last) {
            $next = $pagenum + 1;
            $paginationCtrls .= ' &nbsp; &nbsp; <a href="/private_messages/'.$u.'&pn='.$next.'">Next</a> ';
        }
    }

    // Check to see if the viewer is the account owner
    $isOwner = "no";
    if($u == $log_username && $user_ok == true){
        $isOwner = "yes";
    }
    
    if($isOwner != "yes"){
        header("location: /index");
        exit();
    }
    
    $countMsgs = 0;
    $clause = "ORDER BY senttime DESC";
    if(isset($_GET["otype"]) || $clause != ""){
        if(isset($_GET["otype"])){
            $otype = mysqli_real_escape_string($conn, $_GET["otype"]);
        }
        if($otype == "sort_0"){
            $clause = "ORDER BY senttime DESC";
        }else if($otype == "sort_1"){
            $clause = "ORDER BY senttime ASC";
        }else if($otype == "sort_2"){
            $clause = "ORDER BY sender";
        }else if($otype == "sort_3"){
            $clause = "ORDER BY sender DESC";
        }else if($otype == "sort_4"){
            $clause = "ORDER BY mread DESC";
        }else if($otype == "sort_5"){
            $clause = "ORDER BY mread ASC";
        }else if($otype == "sort_6"){
            $clause = "ORDER BY RAND()";
        }

        $sql = "SELECT * FROM pm WHERE (receiver=? OR sender=?) AND parent=? AND rdelete = ? AND sdelete = ? $clause $limit";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssss",$log_username,$log_username,$x,$zero,$zero);
        $stmt->execute();
        $result = $stmt->get_result();
        // Gather data about parent pm's
        if($result->num_rows > 0){
            while ($row = $result->fetch_assoc()){
                $pmid = $row["id"];
                //div naming
                $pmid2 = 'pm_'.$pmid;
                $wrap = 'pm_wrap_'.$pmid;
                //button naming
                $btid2 = 'bt_'.$pmid;
                //textarea naming
                $rt = 'replytext_'.$pmid;
                //button naming
                $rb = 'replyBtn_'.$pmid;
                $receiver = $row["receiver"];
                $sender = $row["sender"];
                $subject = $row["subject"];
                $message = $row["message"];
                $time_ = $row["senttime"];
                $rread = $row["rread"];
                $sread = $row["sread"];
                $mread = $row["mread"];
                $read_string = "";
                if($mread == 1){
                    $read_string = 'style="border: 2px solid red;"';
                }
                $time = strftime("%R, %b %d, %Y", strtotime($time_));
                $subject_new = $subject;

                if(strlen($subject) > 22){
                    $subject = mb_substr($subject, 0, 17, "utf-8");
                    $subject .= "...";
                }
                $message_old = $row["message"];
                $message_old = nl2br($message_old);
                $message_old = str_replace("&amp;","&",$message_old);
                $message_old = stripslashes($message_old);
                if(strlen($message) > 1000){
                    $message = mb_substr($message, 0,1000, "utf-8");
                    $message .= " ...";
                    $message .= '&nbsp;<a id="toggle_'.$pmid.'" onclick="opentext(\''.$pmid.'\')">See More</a>';
                    $message_old = '<p id="lessmore_'.$pmid.'" class="vmit lmml">'.$message_old.'&nbsp;<a id="toggle_'.$pmid.'" onclick="opentext(\''.$pmid.'\')">See Less</a></p>';
                }else{
                    $message_old = "";
                }
                $message = nl2br($message);
                $message = str_replace("&amp;","&",$message);
                $message = stripslashes($message);
                $sql = "SELECT avatar FROM users WHERE username=? LIMIT 1";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("s",$sender);
                $stmt->execute();
                $stmt->bind_result($userpicture);
                $stmt->fetch();
                $stmt->close();
                if($userpicture != NULL){
                    $pcurlk = "/user/".$sender."/".$userpicture;
                }else{
                    $pcurlk = "/images/avdef.png";
                }
                
                $style = $stylef = 'background-repeat: no-repeat; background-position: center; background-size: cover; width: 50px; height: 50px; border-radius: 50%;';
                $stylef .= 'float: left;';
                
                $sourceURL = "";
                $sourceURLFrom = "";
    	        if($otype == 'nto'){
    	            $sourceURL = "data-src=\"" . $pcurlk . "\" class='lazy-bg' style='".$style."'";
    	            $sourceURLFrom = "data-src=\"" . $pcurlk . "\" class='lazy-bg' style='".$stylef."'";
    	        }else{
    	            $sourceURL = "style='background-image: url(\"$pcurlk\"); ".$style."'";
    	            $sourceURLFrom = "style='background-image: url(\"$pcurlk\"); ".$stylek."'";
    	        }
                
                $senderpic = "<a href='/user/".$sender."/'><div ".$sourceURL."></div></a>";
                $senderpic_from = "<a href='/user/".$sender."'><div ".$sourceURLFrom."></div></a>";

                $pmids = strval($pmid);
                $sql3 = "SELECT message, sender, senttime FROM pm WHERE parent = ? ORDER BY senttime DESC LIMIT 1";
                $stmt = $conn->prepare($sql3);
                $stmt->bind_param("s",$pmids);
                $stmt->execute();
                $stmt->bind_result($lastMsg, $lastSender, $lastTime);
                $stmt->fetch();
                $stmt->close();

                $lastTime = strftime("%R, %b %d", strtotime($lastTime));

                if($lastMsg == ""){
                    $lastMsg = $message;
                }

                if($lastTime == "01:00, Jan 01"){
                    $lastTime = strftime("%R, %b %d", strtotime($time_));
                }

                if(preg_match("/<img.+>/i", $lastMsg)){
                    $lastMsg = "A photo was sent";
                }

                if(strlen($lastMsg) > 200){
                    $lastMsg = substr($lastMsg, 0, 196);
                    $lastMsg .= " ...";
                }

                // Start to build our list of parent pm's
                $mail .= '<div class="showMessage" '.$read_string.'>';
                $mail .= '<div id="show_in_div"><b>Subject: </b>'.$subject.'</div>';
                $mail .= '<div class="show_pic_div">'.$senderpic.'</div>';
                $mail .= '<button id="show_'.$pmid.'" class="fixRed main_btn_fill" style="margin-top: 5px; float: left; font-size: 12px;" onclick="showMessage(\''.$pmid.'\')">Show message</button>';
                $mail .= '<div class="sendtime">';
                    $mail .= '<div class="innerSend">'.$lastMsg.'</div><span class="keepDate">'.$lastTime.'</span>';
                $mail .= '</div><div class="clear"></div>';
                $mail .= '</div>';
                $mail .= '<div id="pm_wrap_'.$pmid.'" class="pm_wrap">';
                $mail .= '<div class="pm_header">';
                // Add button for mark as read
                $mail .= '<span style="display: block; text-align: center;"><button onclick="markRead(\''.$pmid.'\',\''.$sender.'\')" id="mark_as_read" class="fixRed main_btn_fill">Important</button>';
                // Add delete button
                $mail .= '<button id="'.$btid2.'" onclick="deletePm(\''.$pmid.'\',\''.$sender.'\',\''.$log_username.'\')" class="delete_pm fixRed main_btn_fill">Delete</button>';
                // Add quick link
                $mail .= '<a href="#pmtexta_'.$pmid.'" id="godown" class="main_btn_fill fixRed" style="color: white; font-size: 12px; margin-left: 10px;">Jump to bottom</a></span></div>';
                $mail .= '<div id="'.$pmid2.'">';//start expanding area
                $mail .= '<div class="pm_post"><b class="pm_time">'.$time.'</b><a href="/user/'.$sender.'">'.$senderpic_from.'</a><p class="vmit"><b class="sdata" id="hide_'.$pmid.'">'.$message.''.$message_old.'</b></p></div><div class="clear"></div><hr class="dim">';

                $stmt->close();
                
                // Gather up any replies to the parent pm's
                $sql2 = "SELECT * FROM pm WHERE parent=? ORDER BY senttime ASC";
                $stmt = $conn->prepare($sql2);
                $stmt->bind_param("i",$pmid);
                $stmt->execute();
                $result2 = $stmt->get_result();
                if($result2->num_rows > 0){
                    while ($row2 = $result2->fetch_assoc()) {
                        $countMsgs++;
                        $rplyid = $row2["id"];
                        $rsender = $row2["sender"];
                        $reply = $row2["message"];
                        $time2_ = $row2["senttime"];
                        $time2 = strftime("%R, %b %d, %Y", strtotime($time2_));
                        $sql = "SELECT avatar FROM users WHERE username=? LIMIT 1";
                        $stmt = $conn->prepare($sql);
                        $stmt->bind_param("s",$rsender);
                        $stmt->execute();
                        $stmt->bind_result($userpicture_reply);
                        $stmt->fetch();
                        $pcurlkk = "";
                        if($userpicture_reply != NULL){
                            $pcurlkk = "/user/".$rsender."/".$userpicture_reply;
                        }else{
                            $pcurlkk = "/images/avdef.png";
                        }
                        
                        $style_r = 'background-repeat: no-repeat; background-position: center; background-size: cover; width: 50px; height: 50px; float: left; border-radius: 50%;';
                        
                        $sourceURL_r = "";
            	        if($otype == 'nto'){
            	            $sourceURL_r = "data-src=\"" . $pcurlkk . "\" class='lazy-bg' style='".$style_r."'";
            	        }else{
            	            $sourceURL_r = "style='background-image: url(\"$pcurlkk\"); ".$style_r."'";
            	        }
                        
                        $senderpic_from_reply = "<a href='/user/".$rsender."'><div ".$sourceURL_r."></div></a>";

                        $reply_old = $row2["message"];
                        $reply_old = nl2br($reply_old);
                        $reply_old = str_replace("&amp;","&",$reply_old);
                        $reply_old = stripslashes($reply_old);
                        if(strlen($reply) > 1000){
                            $reply = mb_substr($reply, 0,1000, "utf-8");
                            $reply .= " ...";
                            $reply .= '&nbsp;<a id="toggle_reply_'.$rplyid.'" onclick="opentext_reply(\''.$rplyid.'\')">See More</a>';
                            $reply_old = '<p id="lessmore_reply_'.$rplyid.'" class="vmit lmml">'.$reply_old.'&nbsp;<a id="toggle_reply_'.$rplyid.'" onclick="opentext_reply(\''.$rplyid.'\')">See Less</a></p>';
                        }else{
                            $reply_old = "";
                        }
                        $reply = nl2br($reply);
                        $reply = str_replace("&amp;","&",$reply);
                        $reply = stripslashes($reply);
                        if($log_username == $rsender){
                            $deletebutton = '<button onclick="deleteMessage(\''.$rplyid.'\',\''.$rsender.'\',\''.$time2_.'\')" class="delete_s" title="Delete message">X</button>';
                        }else{
                            $deletebutton = "";
                        }
                        $mail .= '<div class="pm_post" id="whole_'.$rplyid.'"><b class="pm_time">'.$deletebutton.''.$time2.'</b><a href="/user/'.$rsender.'">'.$senderpic_from_reply.'</a><p class="vmit"><b class="sdata" id="hide_reply_'.$rplyid.'">'.$reply.''.$reply_old.'</b></p></div><div class="clear"></div><hr class="dim" id="wholle_'.$rplyid.'">';
                        $stmt->close();
                    }
                }
                // Each parent and child is now listed
                $mail .= '</div>';
                // Add reply textbox
                $mail .= '<textarea id="pmtexta_'.$pmid.'" class="pmtexta" onfocus="showBtnDiv_pm(\''.$pmid.'\')" placeholder="What&#39;s in your mind '.$log_username.'?" onkeyup="statusMax(this,65000)"></textarea>';
                $mail .= '<div id="uploadDisplay_SP_msg_'.$pmid.'"></div>';
                $mail .= '<div id="btns_SP_'.$pmid.'" class="hiddenStuff" style="width: auto;">';
                $mail .= '<span id="swithidbr_msg_'.$pmid.'"><button id="pmsendBtn" class="btn_rply" onclick="postPmMsg(\'pm_reply\',\''.$u.'\',\'pmtexta_'.$pmid.'\',\''.$sender.'\',\''.$pmid.'\', \''.$time2_.'\')">Post</button></span>';
                $mail .= '<img src="/images/camera.png" id="triggerBtn_SP_'.$pmid.'" class="triggerBtnreply" onclick="triggerUpload(event, \'fu_SP\')" width="22" height="22" title="Upload A Photo" />';
                $mail .= '<img src="/images/emoji.png" class="triggerBtn" width="22" height="22" title="Send emoticons" id="emoji" onclick="openEmojiBox_pm(\''.$pmid.'\')">';
                $mail .= '<div class="clear"></div>';
                $mail .= generateEList($pmid, 'emojiBox_pm_'.$pmid.'', 'pmtexta_'.$pmid.'');
            $mail .= '</div>';
            $mail .= '</div>';
            $mail .= '<div id="standardUpload" class="hiddenStuff">';
                $mail .= '<form id="image_SP_reply" enctype="multipart/form-data" method="POST">';
                    $mail .= '<input type="file" name="FileUpload" id="fu_SP" onchange="doUpload(\''.$pmid.'\',\'fu_SP\')">';
                $mail .= '</form>';
            $mail .= '</div>';
            $mail .= '<div class="clear"></div>';
            }
            if(isset($_GET["otype"])){
                echo $mail;
                exit();
            }
        }else{
            $mail = '<p style="text-align: center; color: #999;">It seems that you have no incoming or sent private messages right now ...</p>';
        }
    }

    $sql = "SELECT COUNT(id) FROM pm WHERE (receiver=? OR sender=?) AND parent=? AND rdelete = ? AND sdelete = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssss",$log_username,$log_username,$x,$zero,$zero);
    $stmt->execute();
    $stmt->bind_result($countConvs);
    $stmt->fetch();
    $stmt->close();

    $countMsgs += $countConvs;
?>
<!DOCTYPE html>
<html>
<head>
<title><?php echo $u ?> - Private Message Inbox</title>
<meta charset="utf-8">
<link rel="icon" type="image/x-icon" href="/images/newfav.png">
<link rel="stylesheet" type="text/css" href="/style/style.css">
<script src="/js/main.js" async></script>
<script src="/js/jjs.js"></script>
<script src="/js/ajax.js" async></script>
<script src="/js/expand_retract.js" async></script>
	  <link rel="manifest" href="/manifest.json">
  <meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="#282828">
<meta name="apple-mobile-web-app-title" content="Pearscom">
<link rel="apple-touch-icon" href="/images/icons/icon-152x152.png">
<meta name="theme-color" content="#282828" />
<script language="javascript" type="text/javascript">
   var hasImage = '';
function statusMax(x, e)
    /*Scope Closed:false | writes:true*/
    {
        x.value.length > e && (_('overlay').style.display = 'block', _('overlay').style.opacity = 0.5, _('dialogbox').style.display = 'block', _('dialogbox').innerHTML = '<p style="font-size: 18px; margin: 0px;">Maximum character limit reached</p><p>For some reasons we limited the number of characters that you can write at the same time. Now you have reached this limit.</p><br /><button id="vupload" style="position: absolute; right: 3px; bottom: 3px;" onclick="closeDialog()">Close</button>', document.body.style.overflow = 'hidden', x.value = x.value.substring(0, e));
    }
function doUpload(x, e)
    /*Scope Closed:false | writes:true*/
    {
        var t = _(e).files[0];
        if (t.name == '')
            return false;
        if (t.type != 'image/jpeg' && t.type != 'image/png' && t.type != 'image/gif' && t.type != 'image/jpg') {
            _('overlay').style.opacity = 0.5;
            _('dialogbox').style.display = 'block';
            _('dialogbox').innerHTML = '<p style="font-size: 18px; margin: 0px;">File type does not supported</p><p>Unfortunately the image that you want to upload has an unvalid extenstion that we do not support. The allowed file extenstions are: jpg, jpeg, png and gif. For further information please visit the help page.</p><br /><button id="vupload" style="position: absolute; right: 3px; bottom: 3px;" onclick="closeDialog()">Close</button>';
            document.body.style.overflow = 'hidden';
            return false;
        }
        _('triggerBtn_SP_' + x).style.display = 'none';
        var o = new FormData();
                o.append('stPic_msg', t);
        o.append('sid', x);
        var a = new XMLHttpRequest();
                a.upload.addEventListener('progress', progressHandler, false);
        a.addEventListener('load', completeHandler, false);
        a.addEventListener('error', errorHandler, false);
        a.addEventListener('abort', abortHandler, false);
        a.open('POST', '/php_parsers/photo_system.php');
        a.send(o);
    }


function progressHandler(x)
    /*Scope Closed:false | writes:true*/
    {
        var e = x.loaded / x.total * 100, t = '<p>' + Math.round(e) + '% uploaded please wait ...</p>';
                _('dialogbox').style.display = 'block';
        _('overlay').style.display = 'block';
        _('overlay').style.opacity = 0.5;
        _('dialogbox').innerHTML = '<b>Your uploading image status</b><p>' + ('<p>' + Math.round(e) + '% uploaded please wait ...</p>') + '</p>';
        document.body.style.overflow = 'hidden';
    }
function completeHandler(x)
    /*Scope Closed:false | writes:true*/
    {
        var e = x.target.responseText.split('|');
        if (e[0] == 'upload_complete_msg') {
            hasImage = e[1];
            _('overlay').style.display = 'block';
            _('overlay').style.opacity = 0.5;
            _('dialogbox').style.display = 'block';
            _('dialogbox').innerHTML = '<p style="font-size: 18px; margin: 0px;">Your uploading image</p><p>You have successfully uploaded your image. Click on the <i>Close</i> button and now you can post your reply.</p><img src="/tempUploads/' + e[1] + '" class="statusImage"><button id="vupload" style="position: absolute; right: 3px; bottom: 3px;" onclick="closeDialog()">Close</button>';
            document.body.style.overflow = 'hidden';
        } else
            _('uploadDisplay_SP_msg_' + e[2]).innerHTML = e[0], _('triggerBtn_SP_' + e[2]).style.display = 'block';
    }
function errorHandler(x)
    /*Scope Closed:false | writes:true*/
    {
                _('overlay').style.opacity = 0.5;
        _('dialogbox').style.display = 'block';
        _('dialogbox').innerHTML = '<p style="font-size: 18px; margin: 0px;">An unknown error has occured</p><p>Unfortunately an unknown error has occured meanwhile your uploading. Please try again later.</p><br /><button id="vupload" style="position: absolute; right: 3px; bottom: 3px;" onclick="closeDialog()">Close</button>';
        document.body.style.overflow = 'hidden';
    }
function abortHandler(x)
    /*Scope Closed:false | writes:true*/
    {
            _('overlay').style.opacity = 0.5;
                _('dialogbox').innerHTML = '<p style="font-size: 18px; margin: 0px;">Your image has been aborted</p><p>Unfortunately your image has been aborted meanwhile uploading. Please try again later.</p><br /><button id="vupload" style="position: absolute; right: 3px; bottom: 3px;" onclick="closeDialog()">Close</button>';
        document.body.style.overflow = 'hidden';
    }


function triggerUpload(x, e)
    /*Scope Closed:false | writes:false*/
    {
                x.preventDefault();
        _(e).click();
    }
'use strict';
function postPmMsg(holder, uname, texta, sender, pmid) {
  var result = _(texta).value;
  if (result == "" && hasImage == "") {
    _("overlay").style.display = "block";
    _("overlay").style.opacity = 0.5;
    _("dialogbox").style.display = "block";
    _("dialogbox").innerHTML = '<p style="font-size: 18px; margin: 0px;">Blank post</p><p>To post your status you have to write or upload something firstly.</p><br /><button id="vupload" style="position: absolute; right: 3px; bottom: 3px;" onclick="closeDialog()">Close</button>';
    document.body.style.overflow = "hidden";
    return false;
  }
  _("swithidbr_msg_" + pmid).innerHTML = '<img src="/images/rolling.gif" width="30" height="30" style="float: left;">';
  var flag = "";
  if (result != "") {
    console.log("stepped in 1")
    flag = result;
  }
  if (flag == "" && hasImage != "") {
    console.log("stepped in 2")
    result = "||na||";
    flag = '<img src="/permUploads/'+hasImage+'" style="border-radius: 20px;">';
  } else {
    if (flag != "" && hasImage != "") {
        console.log("stepped in 3")
      flag += '<br /><img src="/permUploads/'+hasImage+'" style="border-radius: 20px;"/>';
    } else {
        console.log("stepped in 4")
      hasImage = "na";
    }
  }

  console.log("hasImage: " + hasImage + ", flag: " + flag + ", result: " + result);
  _("pmsendBtn").disabled = true;
  var request = ajaxObj("POST", "/php_parsers/ph_system.php");
  request.onreadystatechange = function() {
    if (1 == ajaxReturn(request)) {
      var x = request.responseText.split("|");
      if (x[0] == "reply_ok") {
        _("pm_" + pmid).innerHTML = _("pm_" + pmid).innerHTML + ('<div id="status_' + pmid + '" class="status_boxes" style="margin-right: auto; margin-left: auto; box-sizing: border-box; width: calc(100% - 20px);"><div><b>Posted by you just now:</b> <span id="sdb_' + pmid + '"></span><br />' + flag + "</div></div>");
        _("pmsendBtn").disabled = false;
        /*
            <button class="delete_s" onclick="deleteMessage(\'' + pmid + "','" + sender + "','" + uname + '\')" title="Delete Status And Its Replies">X</button>
        */
        _(texta).value = "";
        _("triggerBtn_SP_" + pmid).style.display = "block";
        _("btns_SP_" + pmid).style.display = "none";
        _("uploadDisplay_SP_msg_" + pmid).innerHTML = "";
        _("fu_SP").value = "";
        hasImage = "";
        _("swithidbr_msg_" + pmid).innerHTML = '<button id="pmsendBtn" class="btn_rply" onclick="postPmMsg(\'' + holder + "','" + uname + "','" + texta + "','" + sender + "','" + pmid + '\')">Post</button></span>';
      } else {
        _("overlay").style.display = "block";
        _("overlay").style.opacity = 0.5;
        _("dialogbox").style.display = "block";
        _("dialogbox").innerHTML = '<p style="font-size: 18px; margin: 0px;">An error has occured</p><p>Unfortunately an unknown error has occured with your status post. Please try again later and make sure everything is right.</p><br /><button id="vupload" style="position: absolute; right: 3px; bottom: 3px;" onclick="closeDialog()">Close</button>';
        document.body.style.overflow = "hidden";
      }
    }
  };
  request.send("action=" + holder + "&user=" + uname + "&data=" + result + "&image=" + hasImage + "&osender=" + sender + "&pmid=" + pmid);
}
function openEmojiBox_pm(x)
    /*Scope Closed:false | writes:true*/
    {
        var e = _('emojiBox_pm_' + x);
        if (e.style.display == 'block')
            e.style.display = 'none'
        else
            e.style.display = 'block';
    }
function insertEmoji(_, x)
    /*Scope Closed:false | writes:true*/
    {
        var e = document.getElementById(_);
        if (e) {
            var t = e.scrollTop, o = 0, a = e.selectionStart || e.selectionStart == '0' ? 'ff' : !!document.selection && 'ie';
            if (a == 'ie') {
                e.focus();
                var n = document.selection.createRange();
                                n.moveStart('character', -e.value.length);
                o = n.text.length;
            } else
                a == 'ff' && (o = e.selectionStart);
            var s = e.value.substring(0, o), r = e.value.substring(o, e.value.length);
            if (e.value = s + x + e.value.substring(o, e.value.length), o = o + x.length, a == 'ie') {
                e.focus();
                var i = document.selection.createRange();
                                i.moveStart('character', -e.value.length);
                i.moveStart('character', o);
                i.moveEnd('character', 0);
                i.select();
            } else
                a == 'ff' && (e.selectionStart = o, e.selectionEnd = o, e.focus());
            e.scrollTop = t;
        }
    }
function deleteMessage(x, e, t)
    /*Scope Closed:false | writes:true*/
    {
        if (1 != confirm('Are you sure you want to delete this message?'))
            return false;
        var o = ajaxObj('POST', '/php_parsers/ph_system.php');
                o.onreadystatechange = function ()
            /* Called:undefined | Scope Closed:false| writes:true*/
            {
                if (1 == ajaxReturn(o)) {
                  if (o.responseText == "deletemessage_ok") {
                    if(_("wholle_" + x) != undefined && _("whole_" + x) != undefined){
                        _("whole_" + x).style.display = "none";
                        _("wholle_" + x).style.display = "none";
                    }else{
                        _("status_" + x).style.display = "none";
                    }
                  } else {
                    _("overlay").style.display = "block";
                    _("overlay").style.opacity = 0.5;
                    _("dialogbox").style.display = "block";
                    _("dialogbox").innerHTML = '<p style="font-size: 18px; margin: 0px;">An error occured</p><p>Unfortunately an unknown error has occured while deleting the message. Please try again later and check everything is proper.</p><br /><button id="vupload" style="position: absolute; right: 3px; bottom: 3px;" onclick="closeDialog()">Close</button>';
                    document.body.style.overflow = "hidden";
                  }
                }
            }
        o.send('action=deletemessage&pmid=' + x + '&stime=' + t + '&uname=' + e);
    }
function showMessage(x)
    /*Scope Closed:false | writes:true*/
    {
        var e = _('pm_wrap_' + x);
        if (e.style.display == 'block')
            (e.style.display = 'none', (_('show_' + x)).style.backgroundColor = 'red')
        else
            (e.style.display = 'block', (_('show_' + x)).style.backgroundColor = '#e60b0b');
    }
function deletePm(x, e, t)
    /*Scope Closed:false | writes:true*/
    {
        if (1 != confirm('By agreeing with this process the whole conversation will be deleted including all the messages, photos and emojis that you sent. Please keep in mind that once you deleted it, this will be lost forever!'))
            return false;
        var o = ajaxObj('POST', '/php_parsers/ph_system.php');
                o.onreadystatechange = function ()
            /* Called:undefined | Scope Closed:false| writes:true*/
            {
                1 == ajaxReturn(o) && (o.responseText == 'delete_ok' ? window.location = '/private_messages/' + t : (_('overlay').style.display = 'block', _('overlay').style.opacity = 0.5, _('dialogbox').style.display = 'block', _('dialogbox').innerHTML = '<p style="font-size: 18px; margin: 0px;">An error occured</p><p>Unfortunately an unknown error has occured while deleting this conversation. Please try again later and check everything is proper.</p><br /><button id="vupload" style="position: absolute; right: 3px; bottom: 3px;" onclick="closeDialog()">Close</button>', document.body.style.overflow = 'hidden'));
            };
        o.send('action=delete_pm&pmid=' + x + '&originator=' + e);
    }
function markRead(x, e)
    /*Scope Closed:false | writes:true*/
    {
        var t = ajaxObj('POST', '/php_parsers/ph_system.php');
                t.onreadystatechange = function ()
            /* Called:undefined | Scope Closed:false| writes:true*/
            {
                1 == ajaxReturn(t) && (t.responseText == 'read_ok' ? (_('overlay').style.display = 'block', _('overlay').style.opacity = 0.5, _('dialogbox').style.display = 'block', _('dialogbox').innerHTML = '<p style="font-size: 18px; margin: 0px;">Important conversation</p><p>You have successfully marked this conversation as important.</p><br /><button id="vupload" style="position: absolute; right: 3px; bottom: 3px;" onclick="closeDialog()">Close</button>', document.body.style.overflow = 'hidden') : (_('overlay').style.display = 'block', _('overlay').style.opacity = 0.5, _('dialogbox').style.display = 'block', _('dialogbox').innerHTML = '<p style="font-size: 18px; margin: 0px;">An error occured</p><p>Unfortunately an unknown error has occured while making this conversation important. Please try again later and check everything is proper.</p><br /><button id="vupload" style="position: absolute; right: 3px; bottom: 3px;" onclick="closeDialog()">Close</button>', document.body.style.overflow = 'hidden'));
            };
        t.send('action=mark_as_read&pmid=' + x + '&originator=' + e);
    }
window.onbeforeunload = function ()
    /* Called:undefined | Scope Closed:false| writes:false*/
    {
        if ('' != '')
            return 'You have not posted your image';
    };
var stat = "less";
function opentext(name) {
  if (stat == "less") {
    _("lessmore_" + name).style.display = "block";
    _("toggle_" + name).innerText = "See Less";
    _("hide_" + name).style.display = "none";
    stat = "more";
  } else {
    if (stat == "more") {
      _("lessmore_" + name).style.display = "none";
      _("toggle_" + name).innerText = "See More";
      _("hide_" + name).style.display = "block";
      stat = "less";
    }
  }
}
var stat_reply = "less";
function opentext_reply(name) {
  if (stat_reply == "less") {
    _("lessmore_reply_" + name).style.display = "block";
    _("toggle_reply_" + name).innerText = "See Less";
    _("hide_reply_" + name).style.display = "none";
    stat_reply = "more";
  } else {
    if (stat_reply == "more") {
      _("lessmore_reply_" + name).style.display = "none";
      _("toggle_reply_" + name).innerText = "See More";
      _("hide_reply_" + name).style.display = "block";
      stat_reply = "less";
    }
  }
}
function closeDialog()
    /*Scope Closed:false | writes:true*/
    {
                _('dialogbox').style.display = 'none';
        _('overlay').style.display = 'none';
        _('overlay').style.opacity = 0;
        document.body.style.overflow = 'auto';
    }
function showBtnDiv_pm(x)
    /*Scope Closed:false | writes:true*/
    {
                0 == mobilecheck && '130px';
        _('btns_SP_' + x).style.display = 'block';
    }
</script>
</head>
<body>
<?php include_once("template_pageTop.php"); ?>
    <div id="overlay"></div>
    <div id="dialogbox"></div>
    <div id="pageMiddle_2">
        <div id="data_holder">
            <div>
                <div><span><?php echo $countConvs; ?></span> conversations</div>
                <div><span><?php echo $countMsgs; ?></span> messages</div>
            </div>
        </div>
        <button id="sort" class="main_btn_fill">Filter Messages</button>
        <div id="sortTypes">
            <div class="gridDiv">
                <p class="mainHeading">Publish date</p>
                <div id="sort_0">Newest to oldest</div>
                <div id="sort_1">Oldest to newest</div>
            </div>
            <div class="gridDiv">
                <p class="mainHeading">Sender</p>
                <div id="sort_2">Alphabetical order</div>
                <div id="sort_3">Reverse alphabetical order</div>
            </div>
            <div class="gridDiv">
                <p class="mainHeading">Importance</p>
                <div id="sort_4">Importants to top</div>
                <div id="sort_5">Importants to bottom</div>
            </div>
            <div class="gridDiv">
                <p class="mainHeading">Randomly</p>
                <div id="sort_6">Messages by random</div>
            </div>
            <div class="clear"></div>
        </div>
        <div class="clear"></div>
        <hr class="dim">
        <div id="holdit"><?php echo $mail; ?></div>
        <div id="paginationCtrls" style="text-align: center;"><?php echo $paginationCtrls; ?></div>
    </div>
<?php require_once 'template_pageBottom.php'; ?>
<script type="text/javascript">
    var pn = "<?php echo $pagenum; ?>",
        uname = "<?php echo $log_username; ?>";

    function revNto() {
        window.location = "/private_messages/" + uname + "&otype=ntorev&pn=" + pn
    }

    function revTit() {
        window.location = "/private_messages/" + uname + "&otype=titrev&pn=" + pn
    }

    function revImp() {
        window.location = "/private_messages/" + uname + "&otype=imprev&pn=" + pn
    }

    function ftsSearch() {
        var e = _("fts").value;
        if ("" == (e = encodeURIComponent(e))) return !1;
        window.location = "/private_messages/" + uname + "&q=" + e + "&pn=" + pn
    }

    function getCookie(e) {
        for (var n = e + "=", t = decodeURIComponent(document.cookie).split(";"), o = 0; o < t.length; o++) {
            for (var a = t[o];
                " " == a.charAt(0);) a = a.substring(1);
            if (0 == a.indexOf(n)) return a.substring(n.length, a.length)
        }
        return ""
    }

    function setDark() {
        var e = "thisClassDoesNotExist";
        if (!document.getElementById(e)) {
            var n = document.getElementsByTagName("head")[0],
                t = document.createElement("link");
            t.id = e, t.rel = "stylesheet", t.type = "text/css", t.href = "/style/dark_style.css", t.media = "all", n.appendChild(t)
        }
    }
    var isdarkm = getCookie("isdark");
    "yes" == isdarkm && setDark();

    function ftsSearch() {
        var n = _("fts").value;
        if ("" == n) return !1;
        n = encodeURIComponent(n), window.location = "/private_messages/" + uname + "&q=" + n
    }

    $( "#sort" ).click(function() {
          $( "#sortTypes" ).slideToggle( 200, function() {
            // Animation complete.
          });
        });

    for(let i = 0; i < 7; i++){
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
        req.open("GET", "/pm_inbox?u=<?php echo $u; ?>&otype=" + otype, false);
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
        for(let i = 0; i < 7; i++){
            if("sort_" + i != otype) _("sort_" + i).style.color = "black";
        }
    }

    changeStyle("sort_0");
</script>
</body>
</html>