<?php
  /*
    Comment section of an article. Implement status posts, replies, likes etc.
  */

  require_once 'php_includes/check_login_statues.php';
  require_once 'php_includes/status_common.php';
  require_once 'php_includes/perform_checks.php';
  require_once 'php_includes/wrapText.php';
  require_once 'php_includes/isfriend.php';
  require_once 'timeelapsedstring.php';
  require_once 'safe_encrypt.php';
  require_once 'php_includes/pagination.php';
  require_once 'headers.php';
  require_once 'elist.php';
  require_once 'php_includes/dist.php';

  // Select user's lat and lon
  function getUserLatLon($conn, $log_username) {
    $sql = "SELECT lat, lon FROM users WHERE username = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $log_username);
    $stmt->execute();
    $stmt->bind_result($lat, $lon);
    $stmt->fetch();
    $stmt->close();
    return [$lat, $lon];
  }

  list($lat, $lon) = getUserLatLon($conn, $log_username);

  $status_ui = "";
  $statuslist = "";
  $statusid = "";
  $one = "1";
  $zero = "0";
  $a = "a";
  $b = "b";
  $c = "c";
  $ar = $_SESSION["id"];
  $p_en = base64url_encode($p, $hshkey);

  // Handle pagination
  $sql_s = "SELECT COUNT(id) FROM article_status WHERE account_name=? AND artid=?";
  $url_n = "/articles/{$p_en}/{$u}";
  list($paginationCtrls, $limit) = pagination($conn, $sql_s, 'si', $url_n, $u, $ar); 
 
  // Check if users are friends
  $isFriend = isFriend($u, $log_username, $user_ok, $conn);
  
  $txtMsg = "";
  if($isOwner == "No"){
    $txtMsg = "What is your opinion about this article?";
  }else if($isOwner == "Yes"){
    $txtMsg = "Say something about your article";
  }

  // If user is logged in allow them to comment 
  if($_SESSION["username"] != ""){
    $status_ui = '
      <textarea id="statustext_" onfocus="showBtnDiv()" placeholder="'.$txtMsg.'" 
      class="user_status"></textarea>
      <div id="uploadDisplay_SP"></div>
      <div id="pbc">
        <div id="progressBar"></div>
        <div id="pbt"></div>
      </div>
      <div id="btns_SP" class="hiddenStuff" style="width: 90%;">
      <span id="swithspan">
        <button id="statusBtn" class="btn_rply"
          onclick="postToStatus(\'status_post\',\'a\',\''.$u.'\',\'statustext_\')">Post</button>      </span>
      <img src="/images/camera.png" id="triggerBtn_SP_" 
        onclick="triggerUpload(event, \'fu_SP\')" width="22" height="22"
        title="Upload A Photo" class="triggerBtnreply" />
      <img src="/images/emoji.png" width="22" class="triggerBtn" height="22"
        title="Send emoticons" id="emoji" onclick="openEmojiBox()">
      <div class="clear"></div>
      ';
    $status_ui .= generateEList($statusid, 'emojiBox_art', 'statustext_');
    $status_ui .= '
      </div>
      <div id="standardUpload" class="hiddenStuff">
      <form id="image_SP" enctype="multipart/form-data" method="post">
        <input type="file" name="FileUpload" id="fu_SP" onchange="doUpload(\'fu_SP\')"
          accept="image/*" />
      </form>
      </div>
      <div class="clear"></div>
      ';
  }else{
    $status_ui = "
      <p class='txtc' style='color: #999;'>Please <a href='/login'>log in</a>
        in order to leave a comment</p>";    
  }

  // Get status posts & data about the authors
  $sql = "SELECT s.*, u.avatar, u.lat, u.lon, u.online, u.country
    FROM article_status AS s
    LEFT JOIN users AS u ON u.username = s.author
    WHERE s.artid = ? AND (s.account_name=? AND s.type=?)
    OR (s.account_name=? AND s.type=?)
    ORDER BY s.postdate DESC $limit";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param("issss", $ar, $u, $a, $u, $c);
  $stmt->execute();
  $result = $stmt->get_result();
  while ($row = $result->fetch_assoc()) {
    $statusid = $row["id"];
    $account_name = $row["account_name"];
    $author = $row["author"];
    $postdate_ = $row["postdate"];
    $postdate = strftime("%R, %b %d, %Y", strtotime($postdate_));
    $agoform = time_elapsed_string($postdate_);
    $avatar = $row["avatar"];
    $fuco = $row["country"];
    $ison = $row["online"];
    $flat = $row["lat"];
    $flon = $row["lon"];

    // Get the distance between 2 users based on their lat and lon coords
    $dist = vincentyGreatCircleDistance($lat, $lon, $flat, $flon);

    // Check if user is online
    $isonimg = isOn($ison);

    // Avatar pic
    $friend_pic = avatarImg($author, $avatar);

    $funames = $author;
    $fuco = wrapText($fuco, 20);  
    
    $numoffs = numOfFriends($conn, $author);

    // On-hover user info box
    $user_image = genUserImage($author, $friend_pic, $funames, $isonimg, $fuco, $dist,
      $numoffs, true);
    $data = $row["data"];
    $data_old = $row["data"];
    $data_old = sanitizeData($data_old);
    $pos = strpos($data_old, 
      '<br /><br /><i style="font-size: 14px;">Shared via <a href="/user/');
    $sec_data = "";
    $first_data = "";

    /*
      TODO: instead of saving uploaded images in the same context as other data & text
      save the img in a separate db field -> no need for regex & efficient
    */

    $isex = clearImg($data_old);    
    
    // Wrap post if longer than 1000 char
    list($data, $data_old) = seeHideWrap($data, $data_old, $statusid, $pos, $isex);

    $data = sanitizeData($data);
    $statusDeleteButton = '';

    // Status delete button
    $statusDeleteButton = genDelBtn($author, $log_username, $account_name, $statusid); 

    // Add share button
    $shareButton = genShareBtn($log_username, $author, $statusid);  

    // Check if user liked the post or not
    $isLike = userLiked($user_ok, $conn, $statusid, $log_username);    

    // Add status like button
    list($likeButton, $likeText) = genStatLikeBtn($isLike, $statusid);  

    // Gather status replies 
    $status_replies = "";
    $sql2 = "SELECT s.*, u.avatar
        FROM article_status AS s
        LEFT JOIN users AS u ON u.username = s.author
        WHERE s.artid = ? AND s.osid = ?
        AND s.type = ?
        ORDER BY postdate DESC";

    $stmt = $conn->prepare($sql2);
    $stmt->bind_param("iis", $ar, $statusid, $b);
    $stmt->execute();
    $result2 = $stmt->get_result();
    if($result2->num_rows > 0){
      while ($row2 = $result2->fetch_assoc()) {
        $statusreplyid = $row2["id"];
        $replyauthor = $row2["author"];
        $replydata = $row2["data"];
        $avatar2 = $row2["avatar"];
        $fuco = $row["country"];
        $ison = $row["online"];
        $flat = $row["lat"];
        $flon = $row["lon"];

        // Get distance between users
        $dist = vincentyGreatCircleDistance($lat, $lon, $flat, $flon);
        $isonimg = '';

        // Is online or not
        $isonimg = isOn($ison);
        $friend_pic = avatarImg($replyauthor, $avatar2);
        $funames = wrapText($replyauthor, 20);
        $fuco = wrapText($fuco, 20);

        $numoffs = numOfFriends($conn, $replyauthor);

        // On-hover reply user info box
        $user_image2 = genUserImage($replyauthor, $friend_pic, $funames, $isonimg, $fuco,
          $dist, $numoffs, true);
        $replypostdate_ = $row2["postdate"];
        $replypostdate = strftime("%R, %b %d, %Y", strtotime($replypostdate_));
        $agoform_reply = time_elapsed_string($replypostdate_);
        $replyDeleteButton = genDelBtn($replyauthor, $log_username, $account_name,
          $statusreplyid, false);

        $data_old_reply = $row2["data"];
        $data_old_reply = sanitizeData($data_old_reply);
        $sec_data = "";
        $first_data = "";
        $isex = clearImg($data_old_img); 
        list($replydata, $data_old_reply) = seeHideWrap($replydata, $data_old_reply,
          $statusreplyid, false, false, false);
        
        $replydata = sanitizeData($replydata);  
        
        // Check if user liked the current reply
        $isLike_reply = userLiked($user_ok, $conn, $statusreplyid, $log_username, false);

        // Add reply like button
        list($likeButton_reply, $likeText_reply) = genStatLikeBtn($isLike_reply,
          $statusreplyid, false);

        $rpycl = getAllLikes('art_reply_likes', 'reply', $statusreplyid, $conn);
        
        $replyLog = "";
        $statusLog = "";

        $replyLog = genLog($_SESSION["username"], $statusreplyid, $likeButton_reply,
          $likeText_reply, false);

        $status_replies .= '
          <div id="reply_'.$statusreplyid.'" class="reply_boxes">
            <div>
              '.$replyDeleteButton.'
              <p id="float">
                <b class="sreply">Reply: </b>
                <b class="rdate">
                  <span class="tooLong">'.$replypostdate.'</span>
                  ('.$agoform_reply.' ago)
                </b>
              </p>

              '.$user_image2.'
              <p id="reply_text">
                <b class="sdata" id="hide_reply_'.$statusreplyid.'">
                  '.$replydata.''.$data_old_reply.'
                </b>
              </p>

            <hr class="dim">

            '.$replyLog.'
            <div style="float: left; padding: 0px 10px 0px 10px;">
              <b class="ispan" id="ipanr_' . $statusreplyid . '">' . $rpycl . ' likes</b>
            </div>
            <div class="clear"></div>
            </div>
          </div>';
        }
      }

      // Count the replies
      $crply = countReplies($u, $statusid, $ar, $conn);

      $showmore = genShowMore($crply, $statusid);

      // Count likes
      $cl = getAllLikes('art_stat_likes', 'status', $statusid, $conn);
        
      $statusLog = genLog($_SESSION["username"], $statusid, $likeButton, $likeText,
        true, $shareButton);

      $statuslist .= '
        <div id="status_'.$statusid.'" class="status_boxes">
          <div>
            '.$statusDeleteButton.'
            <p id="status_date">
              <b class="status_title">Post: </b>
              <b class="pdate">
                <span class="tooLong">'.$postdate.'</span> ('.$agoform.' ago)
              </b>
            </p>

            '.$user_image.'

            <div id="sdata_'.$statusid.'">
              <p id="status_text">
                <b class="sdata" id="hide_'.$statusid.'">
                  '.$data.''.$data_old.'
                </b>
              </p>
            </div>

            <hr class="dim">

            '.$statusLog.'

            <div style="float: left; padding: 0px 10px 0px 10px;">
              <b class="ispan" id="ipanf_' . $statusid . '">
                ' . $cl . ' likes
              </b>
            </div>
            <div class="clear"></div>
          </div>
          '.$showmore.'
          <span id="allrply_'.$statusid.'" class="hiderply">
            '.$status_replies.'
          </span>
        </div>';

    if($isFriend == true || $log_username == $u){
      $statuslist .= '
        <textarea id="replytext_'.$statusid.'" class="replytext"
          onfocus="showBtnDiv_reply('.$statusid.')"
          placeholder="Write a comment..."></textarea>
        <div id="uploadDisplay_SP_reply_'.$statusid.'"></div>
        <div id="btns_SP_reply_'.$statusid.'" class="hiddenStuff rply_joiner">
          <span id="swithidbr_'.$statusid.'">
            <button id="replyBtn_'.$statusid.'" class="btn_rply"
              onclick="replyToStatus('.$statusid.',\''.$u.'\',\'replytext_'.$statusid.'\',this)">Reply</button>
            </span>
            <img src="/images/camera.png" id="triggerBtn_SP_reply_" class="triggerBtnreply"
              onclick="triggerUpload_reply(event, \'fu_SP_reply\')" width="22" height="22"
              title="Upload a photo" />
            <img src="/images/emoji.png" class="triggerBtn" width="22" height="22"
              title="Send emoticons" id="emoji" onclick="openEmojiBox_reply('.$statusid.')">
            <div class="clear"></div>
        ';
        $statuslist .= generateEList($statusid, 'emojiBox_reply_' . $statusid,
          'replytext_'.$statusid);
      $statuslist .= '</div>';
      $statuslist .= '
        <div id="standardUpload_reply" class="hiddenStuff">
          <form id="image_SP_reply" enctype="multipart/form-data" method="post">
            <input type="file" name="FileUpload" id="fu_SP_reply"
              onchange="doUpload_reply(\'fu_SP_reply\', '.$statusid.')" accept="image/*"/>
          </form>
        </div>
        <div class="clear"></div>
      ';
      }
    }
?>
<script type="text/javascript">
  var hasImage = "";
  window.onbeforeunload = function() {
    if ("" != hasImage) {
      return "You have not posted your image";
    }
  };
  var statreply = "less";
  function opentext_reply(name) {
    if ("less" == statreply) {
      _("lessmore_reply_" + name).style.display = "block";
      _("toggle_reply_" + name).innerText = "See Less";
      _("hide_reply_" + name).style.display = "none";
      statreply = "more";
    } else {
      if ("more" == statreply) {
        _("lessmore_reply_" + name).style.display = "none";
        _("toggle_reply_" + name).innerText = "See More";
        _("hide_reply_" + name).style.display = "block";
        statreply = "less";
      }
    }
  }
  var stat = "less";
  function opentext(name) {
    if ("less" == stat) {
      _("lessmore_" + name).style.display = "block";
      _("toggle_" + name).innerText = "See Less";
      _("hide_" + name).style.display = "none";
      stat = "more";
    } else {
      if ("more" == stat) {
        _("lessmore_" + name).style.display = "none";
        _("toggle_" + name).innerText = "See More";
        _("hide_" + name).style.display = "block";
        stat = "less";
      }
    }
  }
  var us = "less";
  function showReply(name, index) {
    if ("less" == us) {
      _("showreply_" + name).innerText = "Hide replies (" + index + ")";
      _("allrply_" + name).style.display = "block";
      us = "more";
    } else {
      if ("more" == us) {
        _("showreply_" + name).innerText = "Show replies (" + index + ")";
        _("allrply_" + name).style.display = "none";
        us = "less";
      }
    }
  }
  function openEmojiBox_reply(name) {
    var cancel = _("emojiBox_reply_" + name);
    if ("block" == cancel.style.display) {
      cancel.style.display = "none";
    } else {
      cancel.style.display = "block";
    }
  }
  function deleteStatus(id, status) {
    if (1 != confirm("Press OK to confirm deletion of this status and its replies")) {
      return false;
    }
    var xhr = ajaxObj("POST", "/php_parsers/article_status_system.php");
    xhr.onreadystatechange = function() {
      if (1 == ajaxReturn(xhr)) {
        if ("delete_ok" == xhr.responseText) {
          _(status).style.display = "none";
          _("replytext_" + id).style.display = "none";
          _("replyBtn_" + id).style.display = "none";
        } else {
          alert(xhr.responseText);
        }
      }
    };
    xhr.send("action=delete_status&statusid=" + id);
  }
  function deleteReply(result, data) {
    if (1 != confirm("Press OK to confirm deletion of this reply")) {
      return false;
    }
    var res = ajaxObj("POST", "/php_parsers/article_status_system.php");
    res.onreadystatechange = function() {
      if (1 == ajaxReturn(res)) {
        if ("delete_ok" == res.responseText) {
          _(data).style.display = "none";
        } else {
          alert(res.responseText);
        }
      }
    };
    res.send("action=delete_reply&replyid=" + result);
  }
  function openEmojiBox() {
    var cancel = _("emojiBox_art");
    if ("block" == cancel.style.display) {
      cancel.style.display = "none";
    } else {
      cancel.style.display = "block";
    }
  }
  function insertEmoji(type, value) {
    var node = document.getElementById(type);
    if (node) {
      var newTop = node.scrollTop;
      var pos = 0;
      var undefined = node.selectionStart || "0" == node.selectionStart ? "ff" : !!document.selection && "ie";
      if ("ie" == undefined) {
        node.focus();
        var oSel = document.selection.createRange();
        oSel.moveStart("character", -node.value.length);
        pos = oSel.text.length;
      } else {
        if ("ff" == undefined) {
          pos = node.selectionStart;
        }
      }
      var left = node.value.substring(0, pos);
      var right = node.value.substring(pos, node.value.length);
      if (node.value = left + value + right, pos = pos + value.length, "ie" == undefined) {
        node.focus();
        var range = document.selection.createRange();
        range.moveStart("character", -node.value.length);
        range.moveStart("character", pos);
        range.moveEnd("character", 0);
        range.select();
      } else {
        if ("ff" == undefined) {
          node.selectionStart = pos;
          node.selectionEnd = pos;
          node.focus();
        }
      }
      node.scrollTop = newTop;
    }
  }
  function showBtnDiv() {
    _("btns_SP").style.display = "block";
  }
  function showBtnDiv_reply(name) {
    if (0 == mobilecheck) {
      _("replytext_" + name).style.height = "130px";
    }
    _("btns_SP_reply_" + name).style.display = "block";
  }
  function doUpload(data) {
    var opts = _(data).files[0];
    if ("" == opts.name) {
      return false;
    }
    if ("image/jpeg" != opts.type && "image/gif" != opts.type && "image/png" != opts.type && "image/jpg" != opts.type) {
      return _("overlay").style.display = "block", _("overlay").style.opacity = .5, _("dialogbox").style.display = "block", _("dialogbox").innerHTML = '<p style="font-size: 18px; margin: 0px;">File type is not supported</p><p>The image that you want to upload has an unvalid extension given that we do not support. The allowed file extensions are: jpg, jpeg, png and gif. For further information please visit the help page.</p><button id="vupload" style="position: absolute; right: 3px; bottom: 3px;" onclick="closeDialog()">Close</button>', 
      false;
    }
    _("uploadDisplay_SP").innerHTML = '<img src="/images/rolling.gif" width="30" height="30">';
    _("pbc").style.display = "block";
    var fd = new FormData;
    fd.append("stPic", opts);
    var request = new XMLHttpRequest;
    request.upload.addEventListener("progress", progressHandler, false);
    request.addEventListener("load", completeHandler, false);
    request.addEventListener("error", errorHandler, false);
    request.addEventListener("abort", abortHandler, false);
    request.open("POST", "/php_parsers/photo_system.php");
    request.send(fd);
  }
  function progressHandler(event) {
    var inDays = event.loaded / event.total * 100;
    var percent_progress = Math.round(inDays);
    _("progressBar").style.width = percent_progress + "%";
    _("pbt").innerHTML = percent_progress + "%";
  }
  function completeHandler(event) {
    var formattedDirections = event.target.responseText.split("|");
    _("progressBar").style.width = "0%";
    _("pbc").style.display = "none";
    if ("upload_complete" == formattedDirections[0]) {
      hasImage = formattedDirections[1];
      _("uploadDisplay_SP").innerHTML = '<img src="/tempUploads/' + formattedDirections[1] + '" class="statusImage" />';
    } else {
      _("uploadDisplay_SP").innerHTML = formattedDirections[0];
      _("triggerBtn_SP").style.display = "block";
    }
  }
  function errorHandler(callback) {
    _("uploadDisplay_SP").innerHTML = "Upload Failed";
    _("triggerBtn_SP").style.display = "block";
  }
  function abortHandler(canCreateDiscussions) {
    _("uploadDisplay_SP").innerHTML = "Upload Aborted";
    _("triggerBtn_SP").style.display = "block";
  }
  function doUpload_reply(body, sharpCos) {
    var opts = _(body).files[0];
    if ("" == opts.name) {
      return false;
    }
    if ("image/jpeg" != opts.type && "image/gif" != opts.type && "image/png" != opts.type && "image/jpg" != opts.type) {
      return _("overlay").style.display = "block", _("overlay").style.opacity = .5, _("dialogbox").style.display = "block", _("dialogbox").innerHTML = '<p style="font-size: 18px; margin: 0px;">File type is not supported</p><p>The image that you want to upload has an unvalid extension given that we do not support. The allowed file extensions are: jpg, jpeg, png and gif. For further information please visit the help page.</p><button id="vupload" style="position: absolute; right: 3px; bottom: 3px;" onclick="closeDialog()">Close</button>', 
      false;
    }
    var fd = new FormData;
    fd.append("stPic_reply", opts);
    var xhr = new XMLHttpRequest;
    xhr.upload.addEventListener("progress", progressHandler_reply, false);
    xhr.addEventListener("load", completeHandler_reply, false);
    xhr.addEventListener("error", errorHandler_reply, false);
    xhr.addEventListener("abort", abortHandler_reply, false);
    xhr.open("POST", "/php_parsers/photo_system.php");
    xhr.send(fd);
  }
  function progressHandler_reply(event) {
    var inDays = event.loaded / event.total * 100;
    var o = "<p>" + Math.round(inDays) + "% uploaded please wait ...</p>";
    _("dialogbox").style.display = "block";
    _("overlay").style.display = "block";
    _("overlay").style.opacity = .5;
    _("dialogbox").innerHTML = "<b>Your uploading image status</b><p>" + o + "</p>";
    document.body.style.overflow = "hidden";
  }
  function completeHandler_reply(event) {
    var formattedDirections = event.target.responseText.split("|");
    if ("upload_complete_reply" == formattedDirections[0]) {
      hasImage = formattedDirections[1];
      _("overlay").style.display = "block";
      _("overlay").style.opacity = .5;
      _("dialogbox").style.display = "block";
      _("dialogbox").innerHTML = '<p style="font-size: 18px; margin: 0px;">Your uploading image</p><p>You have successfully uploaded your image. Click on the <i>Close</i> button and now you can post your reply.</p><img src="/tempUploads/' + formattedDirections[1] + '" class="statusImage"><button id="vupload" style="position: absolute; right: 3px; bottom: 3px;" onclick="closeDialog()">Close</button>';
      document.body.style.overflow = "hidden";
    } else {
      _("uploadDisplay_SP_reply_" + e).innerHTML = formattedDirections[0];
      _("triggerBtn_SP_reply").style.display = "block";
    }
  }
  function closeDialog() {
    _("dialogbox").style.display = "none";
    _("overlay").style.display = "none";
    _("overlay").style.opacity = 0;
    document.body.style.overflow = "auto";
  }
  function errorHandler_reply(canCreateDiscussions) {
    _("uploadDisplay_SP_reply_").innerHTML = "Upload Failed";
    _("triggerBtn_SP_reply").style.display = "block";
  }
  function abortHandler_reply(canCreateDiscussions) {
    _("uploadDisplay_SP_reply").innerHTML = "Upload Aborted";
    _("triggerBtn_SP_reply").style.display = "block";
  }
  function triggerUpload(event, file) {
    event.preventDefault();
    _(file).click();
  }
  function triggerUpload_reply(event, t) {
    event.preventDefault();
    _(t).click();
  }
  function postToStatus(cond, thencommands, pollProfileId, userId) {
    var c = _(userId).value;
    if ("" == c && "" == hasImage) {
      return _("overlay").style.display = "block", _("overlay").style.opacity = .5, _("dialogbox").style.display = "block", _("dialogbox").innerHTML = '<p style="font-size: 18px; margin: 0px;">Blank post</p><p>To post your status you have to write or upload something firstly.</p><br /><button id="vupload" style="position: absolute; right: 3px; bottom: 3px;" onclick="closeDialog()">Close</button>', document.body.style.overflow = "hidden", false;
    }
    var line = "";
    if ("" != c) {
      line = c.replace(/\n/g, "<br />").replace(/\r/g, "<br />");
    }
    if ("" == line && "" != hasImage) {
      c = "||na||";
      line = '<img src="/permUploads/' + hasImage + '" />';
    } else {
      if ("" != line && "" != hasImage) {
        line = line + ('<br /><img src="/permUploads/' + hasImage + '" />');
      } else {
        hasImage = "na";
      }
    }
    _("swithspan").innerHTML = '<img src="/images/rolling.gif" width="30" height="30" style="float: left;">';
    var xhr = ajaxObj("POST", "/php_parsers/article_status_system.php");
    xhr.onreadystatechange = function() {
      if (1 == ajaxReturn(xhr)) {
        var tilesToCheck = xhr.responseText.split("|");
        if ("post_ok" == tilesToCheck[0]) {
          var t = tilesToCheck[1];
          var newHTML = _("statusarea").innerHTML;
          _("statusarea").innerHTML = '<div id="status_' + t + '" class="status_boxes"><div><b>Posted by you just now:</b> <span id="sdb_' + t + '"><button onclick="return false;" class="delete_s" onmousedown="deleteStatus(\'' + t + "','status_" + t + '\');" title="Delete Status And Its Replies">X</button></span><br />' + line + "</div></div><br />" + newHTML;
          _("swithspan").innerHTML = "<button id=\"statusBtn\" onclick=\"postToStatus('status_post','a','<?php echo $u; ?>','statustext')\" class=\"btn_rply\">Post</button>";
          _(userId).value = "";
          _("btns_SP").style.display = "none";
          _("uploadDisplay_SP").innerHTML = "";
          _("fu_SP").value = "";
          hasImage = "";
        } else {
          _("overlay").style.display = "block";
          _("overlay").style.opacity = .5;
          _("dialogbox").style.display = "block";
          _("dialogbox").innerHTML = '<p style="font-size: 18px; margin: 0px;">An error occured</p><p>Unfortunately an unknown error has occured with your status post. Please try again later and check everything is proper.</p><br /><button id="vupload" style="position: absolute; right: 3px; bottom: 3px;" onclick="closeDialog()">Close</button>';
          document.body.style.overflow = "hidden";
        }
      }
    };
    xhr.send("action=" + cond + "&type=" + thencommands + "&user=" + pollProfileId + "&data=" + c + "&image=" + hasImage);
  }
  function replyToStatus(id, supr, o, dizhi) {
    var c = _(o).value;
    if ("" == c && "" == hasImage) {
      return _("overlay").style.display = "block", _("overlay").style.opacity = .5, _("dialogbox").style.display = "block", _("dialogbox").innerHTML = '<p style="font-size: 18px; margin: 0px;">Blank post</p><p>To post your status you have to write or upload something firstly.</p><br /><button id="vupload" style="position: absolute; right: 3px; bottom: 3px;" onclick="closeDialog()">Close</button>', document.body.style.overflow = "hidden", false;
    }
    var line = "";
    if ("" != c) {
      line = c.replace(/\n/g, "<br />").replace(/\r/g, "<br />");
    }
    if ("" == line && "" != hasImage) {
      c = "||na||";
      line = '<img src="/permUploads/' + hasImage + '" />';
    } else {
      if ("" != line && "" != hasImage) {
        line = line + ('<br /><img src="/permUploads/' + hasImage + '" />');
      } else {
        hasImage = "na";
      }
    }
    _("swithidbr_" + id).innerHTML = '<img src="/images/rolling.gif" width="30" height="30" style="float: left;">';
    var xhr = ajaxObj("POST", "/php_parsers/article_status_system.php");
    xhr.onreadystatechange = function() {
      if (1 == ajaxReturn(xhr)) {
        var actionsLengthsArray = xhr.responseText.split("|");
        if ("reply_ok" == actionsLengthsArray[0]) {
          var l = actionsLengthsArray[1];
          c = c.replace(/</g, "<").replace(/>/g, ">").replace(/\n/g, "<br />").replace(/\r/g, "<br />");
          _("status_" + id).innerHTML += '<div id="reply_' + l + '" class="reply_boxes"><div><b>Reply by you just now:</b><span id="srdb_' + l + '"><button onclick="return false;" class="delete_s" onmousedown="deleteReply(\'' + l + "','reply_" + l + '\');" title="Delete Comment">X</button></span><br />' + line + "</div></div>";
          _("swithidbr_" + id).innerHTML = '<button id="replyBtn_' + id + '" class="btn_rply" onclick="replyToStatus(\'' + id + "','<?php echo $u; ?>','replytext_" + id + "',this)\">Reply</button>";
          _(o).value = "";
          _("triggerBtn_SP_reply_").style.display = "block";
          _("btns_SP_reply_" + id).style.display = "none";
          _("uploadDisplay_SP_reply_" + id).innerHTML = "";
          _("fu_SP_reply").value = "";
          hasImage = "";
        } else {
          _("overlay").style.display = "block";
          _("overlay").style.opacity = .5;
          _("dialogbox").style.display = "block";
          _("dialogbox").innerHTML = '<p style="font-size: 18px; margin: 0px;">An error occured</p><p>Unfortunately an unknown error has occured with your reply. Please try again later and check everything is proper.</p><br /><button id="vupload" style="position: absolute; right: 3px; bottom: 3px;" onclick="closeDialog()">Close</button>';
          document.body.style.overflow = "hidden";
        }
      }
    };
    xhr.send("action=status_reply&sid=" + id + "&user=" + supr + "&data=" + c + "&image=" + hasImage);
  }
  function shareStatus(type) {
    var request = ajaxObj("POST", "/php_parsers/article_status_system.php");
    request.onreadystatechange = function() {
      if (1 == ajaxReturn(request)) {
        if ("share_ok" == request.responseText) {
          _("overlay").style.display = "block";
          _("overlay").style.opacity = .5;
          _("dialogbox").style.display = "block";
          _("dialogbox").innerHTML = '<p style="font-size: 18px; margin: 0px;">Shared post</p><p>You have successfully shared this post which will be visible on your main profile page.</p><br /><button id="vupload" style="position: absolute; right: 3px; bottom: 3px;" onclick="closeDialog()">Close</button>';
          document.body.style.overflow = "hidden";
        } else {
          _("overlay").style.display = "block";
          _("overlay").style.opacity = .5;
          _("dialogbox").style.display = "block";
          _("dialogbox").innerHTML = '<p style="font-size: 18px; margin: 0px;">An error occured</p><p>Unfortunately an unknown error has occured with your post sharing. Please try again later and check everything is proper.</p><br /><button id="vupload" style="position: absolute; right: 3px; bottom: 3px;" onclick="closeDialog()">Close</button>';
          document.body.style.overflow = "hidden";
        }
      }
    };
    request.send("action=share&id=" + type);
  }
  function toggleLike(e, o, t) {
    var result = ajaxObj("POST", "/php_parsers/like_system_art.php");
    result.onreadystatechange = function() {
      if (1 == ajaxReturn(result)) {
        if ("like_success" == result.responseText) {
          _(t).innerHTML = '<a href="#" onclick="return false;" onmousedown="toggleLike(\'unlike\',\'' + o + "','likeBtn_" + o + '\')"><img src="/images/fillthumb.png" width="18" height="18" class="like_unlike"></a><span style="vertical-align: middle; margin-left: 5px;">Dislike</span>';
          var e = (e = _("ipanf_" + o).innerText.replace("(", "")).replace(")", "").replace("likes", "").replace(" ", "");
            e = Number(e);
            _("ipanf_" + o).innerText = ++e + " likes";
        } else {
          if ("unlike_success" == result.responseText) {
            _(t).innerHTML = '<a href="#" onclick="return false;" onmousedown="toggleLike(\'like\',\'' + o + "','likeBtn_" + o + '\')"><img src="/images/nf.png" width="18" height="18" class="like_unlike"></a><span style="vertical-align: middle; margin-left: 5px;">Like</span>';
              e = (e = (e = _("ipanf_" + o).innerText.replace("(", "")).replace(")", "")).replace("likes", "").replace(" ", "");
              e = Number(e);
              _("ipanf_" + o).innerText = --e + " likes";
          } else {
            _("overlay").style.display = "block";
            _("overlay").style.opacity = .5;
            _("dialogbox").style.display = "block";
            _("dialogbox").innerHTML = '<p style="font-size: 18px; margin: 0px;">An error occured</p><p>Unfortunately an unknown error has occured with your status like. Please try again later and check everything is proper.</p><br /><button id="vupload" style="position: absolute; right: 3px; bottom: 3px;" onclick="closeDialog()">Close</button>';
            document.body.style.overflow = "hidden";
            _(t).innerHTML = "Try again later";
          }
        }
      }
    };
    result.send("type=" + e + "&id=" + o);
  }
  function toggleLike_reply(e, o, t) {
    var result = ajaxObj("POST", "/php_parsers/like_reply_system_art.php");
    result.onreadystatechange = function() {
      if (1 == ajaxReturn(result)) {
        if ("like_reply_success" == result.responseText) {
          _(t).innerHTML = '<a href="#" onclick="return false;" onmousedown="toggleLike_reply(\'unlike\',\'' + o + "','likeBtn_reply_" + o + '\')"><img src="/images/fillthumb.png" width="18" height="18" class="like_unlike"></a><span style="vertical-align: middle; margin-left: 5px;">Dislike</span>';
            var e = (e = _("ipanr_" + o).innerText.replace("(", "")).replace(")", "").replace("likes", "").replace(" ", "");
            e = Number(e);
            _("ipanr_" + o).innerText = ++e + " likes";
        } else {
          if ("unlike_reply_success" == result.responseText) {
            _(t).innerHTML = '<a href="#" onclick="return false;" onmousedown="toggleLike_reply(\'like\',\'' + o + "','likeBtn_reply_" + o + '\')"><img src="/images/nf.png" width="18" height="18" class="like_unlike"></a><span style="vertical-align: middle; margin-left: 5px;">Like</span>';
            e = (e = (e = _("ipanr_" + o).innerText.replace("(", "")).replace(")", "")).replace("likes", "").replace(" ", "");
            e = Number(e);
            _("ipanr_" + o).innerText = --e + " likes";
          } else {
            _("overlay").style.display = "block";
            _("overlay").style.opacity = .5;
            _("dialogbox").style.display = "block";
            _("dialogbox").innerHTML = '<p style="font-size: 18px; margin: 0px;">An error occured</p><p>Unfortunately an unknown error has occured with your reply like. Please try again later and check everything is proper.</p><br /><button id="vupload" style="position: absolute; right: 3px; bottom: 3px;" onclick="closeDialog()">Close</button>';
            document.body.style.overflow = "hidden";
            _(t).innerHTML = "Try again later";
          }
        }
      }
    };
    result.send("type=" + e + "&id=" + o);
  }
</script>
<div id="statusui">
  <?php echo $status_ui; ?>
</div>
<div id="statusarea">
  <?php echo $statuslist; ?>
</div>
<div style="text-align: center; padding: 20px;"><?php echo $paginationCtrls; ?></div>
