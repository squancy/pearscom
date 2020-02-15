<?php
	require_once 'php_includes/check_login_statues.php';
	require_once 'php_includes/status_common.php';
	require_once 'php_includes/pagination.php';
	require_once 'php_includes/isfriend.php';
	require_once 'php_includes/perform_checks.php';
	require_once 'php_includes/wrapText.php';
	require_once 'safe_encrypt.php';
	require_once 'headers.php';
	require_once 'elist.php';
	require_once 'php_includes/dist.php';

  list($lat, $lon) = getLatLon($conn, $log_username);

	$status_ui = "";
	$statuslist = "";
	$a = "a";
	$b = "b";
	$c = "c";

	$vi = $_SESSION["id"];

	$isOwner = isOwner($u, $log_username, $user_ok); 
	
	$vi = base64url_decode($vi, $hshkey);

  // Handle pagination
  $sql_s = "SELECT COUNT(id) FROM video_status WHERE account_name=? AND vidid = ?";
  $url_n = "/video_zoom/{$vi_en}";
  list($paginationCtrls, $limit) = pagination($conn, $sql_s, 'si', $url_n, $u, $vi);

	$isFriend = isFriend($u, $log_username, $user_ok, $conn); 

	if($isOwner == "Yes"){
		$wtext = "Post something about your video!";
	} else {
		$wtext = "What do you think about this video?";
	}

  // Count num of posts
	$sql = "SELECT COUNT(id) FROM video_status WHERE account_name = ? AND vidid = ?";
	$stmt = $conn->prepare($sql);
	$stmt->bind_param("ss", $u, $vi);
	$stmt->execute();
	$stmt->bind_result($countRs);
	$stmt->fetch();
	$stmt->close();
	$toDis = "";
	if($countRs > 0){
		$toDis = '<p style="color: #999; text-align: center;">'.$countRs.' comments recorded</p>';
	}

  // Build user input
  if($_SESSION["username"] != ""){
    $status_ui = $toDis.'
      <textarea id="statustext" class="user_status" onfocus="showBtnDiv()"
        placeholder="'.$wtext.'"></textarea>
      <div id="uploadDisplay_SP"></div>
      <div id="pbc">
        <div id="progressBar"></div>
        <div id="pbt"></div>
      </div>
      <div id="btns_SP" class="hiddenStuff" style="width: 90%;">
        <span id="swithspan">
          <button id="statusBtn"
            onclick="postToStatus(\'status_post\',\'a\',\''.$u.'\',\'statustext\')"
            class="btn_rply">Post</button>
        </span>
        <img src="/images/camera.png" id="triggerBtn_SP" class="triggerBtnreply"
          onclick="triggerUpload(event, \'fu_SP\')" width="22" height="22"
          title="Upload A Photo" />
        <img src="/images/emoji.png" class="triggerBtn" width="22" height="22"
          title="Send emoticons" id="emoji" onclick="openEmojiBox()">
        <div class="clear"></div>
    ';
    $status_ui .= generateEList($statusid, 'emojiBox', 'statustext');
    $status_ui .= '</div>';
    $status_ui .= '
      <div id="standardUpload" class="hiddenStuff">
        <form id="image_SP" enctype="multipart/form-data" method="post">
          <input type="file" name="FileUpload" id="fu_SP" onchange="doUpload(\'fu_SP\')"
            accept="image/*">
        </form>
      </div>
      <div class="clear"></div>
    ';  
  }else{
    $status_ui = "
      <p class='txtc' style='color: #999;'>
        Please <a href='/login'>log in</a> in order to leave a comment
      </p>
    ";
  }

  $sql = "SELECT s.*, u.avatar, u.lat, u.lon, u.country, u.online
		FROM video_status AS s 
		LEFT JOIN users AS u ON u.username = s.author
		WHERE s.vidid = ? AND (s.account_name=? AND s.type=?) 
		OR (s.account_name=? AND s.type=?) 
		ORDER BY s.postdate DESC $limit";
	$stmt = $conn->prepare($sql);
	$stmt->bind_param("issss", $vi, $u, $a, $u, $c);
	$stmt->execute();
	$result = $stmt->get_result();
	if($result->num_rows > 0){
	  require_once 'video_fetch.php';	
	}else{
		echo "<p style='color: #999;' class='txtc'>Be the first one commeting on this video!</p>";
	}
?>
	<script type="text/javascript">
	'use strict';
	function deleteStatus(id, status) {
	  if (1 != confirm("Press OK to confirm deletion of this status and its replies")) {
	    return false;
	  }
	  var xhr = ajaxObj("POST", "/php_parsers/video_status_parser.php");
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
	  var res = ajaxObj("POST", "/php_parsers/video_status_parser.php");
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
	var hasImage = "";
	function showBtnDiv() {
	  _("btns_SP").style.display = "block";
	}
	function showBtnDiv_reply(name) {
	  _("btns_SP_reply_" + name).style.display = "block";
	}
	function openEmojiBox() {
	  var cancel = _("emojiBox");
	  if ("block" == cancel.style.display) {
	    cancel.style.display = "none";
	  } else {
	    cancel.style.display = "block";
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
	function doUpload(data) {
	  var opts = _(data).files[0];
	  if ("" == opts.name) {
	    return false;
	  }
	  if ("image/jpeg" != opts.type && "image/png" != opts.type && "image/gif" != opts.type && "image/jpg" != opts.type) {
	    return _("overlay").style.display = "block", _("overlay").style.opacity = .5, _("dialogbox").style.display = "block", _("dialogbox").innerHTML = '<p style="font-size: 18px; margin: 0px;">File type is not supported</p><p>The image that you want to upload has an unvalid extension given that we do not support. The allowed file extensions are: jpg, jpeg, png and gif. For further information please visit the help page.</p><button id="vupload" style="position: absolute; right: 3px; bottom: 3px;" onclick="closeDialog()">Close</button>', 
	    document.body.style.overflow = "hidden", false;
	  }
	  _("triggerBtn_SP").style.display = "none";
	  _("uploadDisplay_SP").innerHTML = '<img src="/images/rolling.gif" width="30" height="30">';
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
	    document.body.style.overflow = "hidden", false;
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
	  _("overlay").style.display = "block";
	  _("overlay").style.opacity = .5;
	  _("dialogbox").style.display = "block";
	  _("dialogbox").innerHTML = "<b>Your uploading photo status</b><p>" + o + "</p>";
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
	function shareStatus(type) {
	  var request = ajaxObj("POST", "/php_parsers/video_status_parser.php");
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
	function toggleLike(e, o, t, vi) {
	  var result = ajaxObj("POST", "/php_parsers/like_system_video.php");
	  result.onreadystatechange = function() {
	    if (1 == ajaxReturn(result)) {
	      if ("like_success" == result.responseText) {
	        _(t).innerHTML = '<a href="#" onclick="return false;" onmousedown="toggleLike(\'unlike\',\'' + o + "','likeBtn_" + o + '\', \'' + vi + '\')"><img src="/images/fillthumb.png" width="18" height="18" class="like_unlike"></a><span style="vertical-align: middle; margin-left: 5px;">Dislike</span>';
            var e = (e = _("ipanf_" + o).innerText.replace("(", "")).replace(")", "").replace("likes", "").replace(" ", "");
            e = Number(e);
            _("ipanf_" + o).innerText = ++e + " likes";
	      } else {
	        if ("unlike_success" == result.responseText) {
	          _(t).innerHTML = '<a href="#" onclick="return false;" onmousedown="toggleLike(\'like\',\'' + o + "','likeBtn_" + o + '\', \'' + vi + '\')"><img src="/images/nf.png" width="18" height="18" class="like_unlike"></a><span style="vertical-align: middle; margin-left: 5px;">Like</span>';
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
	  result.send("type=" + e + "&id=" + o + "&vi=" + vi);
	}
	function toggleLike_reply(e, o, t, vi) {
	  var result = ajaxObj("POST", "/php_parsers/video_reply_likes.php");
	  result.onreadystatechange = function() {
	    if (1 == ajaxReturn(result)) {
	      if ("like_reply_success" == result.responseText) {
	        _(t).innerHTML = '<a href="#" onclick="return false;" onmousedown="toggleLike_reply(\'unlike\',\'' + o + "','likeBtn_reply_" + o + '\', \'' + vi + '\')"><img src="/images/fillthumb.png" width="18" height="18" class="like_unlike"></a><span style="vertical-align: middle; margin-left: 5px;">Dislike</span>';
            var e = (e = _("ipanr_" + o).innerText.replace("(", "")).replace(")", "").replace("likes", "").replace(" ", "");
            e = Number(e);
            _("ipanr_" + o).innerText = ++e + " likes";
	      } else {
	        if ("unlike_reply_success" == result.responseText) {
	          _(t).innerHTML = '<a href="#" onclick="return false;" onmousedown="toggleLike_reply(\'like\',\'' + o + "','likeBtn_reply_" + o + '\', \'' + vi + '\')"><img src="/images/nf.png" width="18" height="18" class="like_unlike"></a><span style="vertical-align: middle; margin-left: 5px;">Like</span>';
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
	  result.send("type=" + e + "&id=" + o + "&vi=" + vi);
	}
	window.onbeforeunload = function() {
	  if ("" != hasImage) {
	    return "You have not posted your image";
	  }
	};
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
	function closeDialog() {
	  _("dialogbox").style.display = "none";
	  _("overlay").style.display = "none";
	  _("overlay").style.opacity = 0;
	  document.body.style.overflow = "auto";
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
	  var xhr = ajaxObj("POST", "/php_parsers/video_status_parser.php");
	  xhr.onreadystatechange = function() {
	    if (1 == ajaxReturn(xhr)) {
	      var tilesToCheck = xhr.responseText.split("|");
	      if ("post_ok" == tilesToCheck[0]) {
	        var t = tilesToCheck[1];
	        var newHTML = _("statusarea").innerHTML;
	        _("statusarea").innerHTML = '<div id="status_' + t + '" class="status_boxes"><div><b>Posted by you just now:</b> <span id="sdb_' + t + '"><button onclick="return false;" class="delete_s" onmousedown="deleteStatus(\'' + t + "','status_" + t + '\');" title="Delete Status And Its Replies">X</button></span><br />' + line + "</div></div>" + newHTML;
	        _("swithspan").innerHTML = "<button id=\"statusBtn\" onclick=\"postToStatus('status_post','a','<?php echo $u; ?>','statustext')\">Post</button>";
	        _(userId).value = "";
	        _("triggerBtn_SP").style.display = "block";
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
	  var xhr = ajaxObj("POST", "/php_parsers/video_status_parser.php");
	  xhr.onreadystatechange = function() {
	    if (1 == ajaxReturn(xhr)) {
	      var actionsLengthsArray = xhr.responseText.split("|");
	      if ("reply_ok" == actionsLengthsArray[0]) {
	        var l = actionsLengthsArray[1];
	        c = c.replace(/</g, "<").replace(/>/g, ">").replace(/\n/g, "<br />").replace(/\r/g, "<br />");
	        _("status_" + id).innerHTML += '<div id="reply_' + l + '" class="reply_boxes"><div><b>Reply by you just now:</b><span id="srdb_' + l + '"><button onclick="return false;" class="delete_s" onmousedown="deleteReply(\'' + l + "','reply_" + l + '\');" title="Delete Comment">X</button></span><br />' + line + "</div></div><br /><br />";
	        _("swithidbr_" + id).innerHTML = '<button id="replyBtn_' + id + '" class="btn_rply" onclick="replyToStatus(\'' + id + "','<?php echo $u; ?>','replytext_" + id + "',this)\">Reply</button>";
	        _(o).value = "";
	        _("triggerBtn_SP_reply").style.display = "block";
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
</script>

<div id="overlay"></div>
<div id="dialogbox"></div>
<div id="statusui">
  <?php echo $status_ui; ?>
</div>
<div id="statusarea">
  <?php echo $statvidl; ?>
</div>
<div id="pagination_controls"><?php echo $paginationCtrls; ?></div>
