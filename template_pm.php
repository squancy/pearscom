<?php
	require_once 'php_includes/check_login_statues.php';
	require_once 'elist.php';

	// Protect this script from direct url access
	if ((!isset($isFriend)) || (!isset($isOwner)) || !isset($log_username) || $log_username == ""){
		exit();
	}
	require_once 'headers.php';
	// Initialize our ui
	$pm_ui = "";
	// If visitor to profile is a friend and is not the owner can send you a pm
	// Build ui carry the profile id, vistor name, pm subject and comment to js

	if($isOwner == "No"){
		$npm .= '<div id="pmform"><div id="oall"><p style="margin-top: 0; color: #999;">Send a private message to '.$u.' <button onclick="closePM()" style="float: right; border:0; background-color: transparent; margin-top: -5px; font-size: 12px;">X</button></p><div id="pmf_w"><input id="pmsubject" class="pmInput" onkeyup="statusMax(this,250)" placeholder="Subject of Private Message">';
		$npm .= '<textarea id="pmtext" class="pmInput" onkeyup="statusMax(this,65000)" placeholder="Send '.$u.' a private message"></textarea></div>';
        $npm .= '<div id="uploadDisplay_SP_pm"></div>';
		$npm .= '<div id="pbc">
						<div id="progressBar"></div>
						<div id="pbt"></div>
					   </div>';
		$npm .= '<div id="btnsSP_pm">';
			$npm .= '<button id="pmBtn" class="main_btn_fill fixRed" style="float: left; margin-top: 10px; margin-bottom: 10px;" onclick="postPm(\''.$u.'\',\''.$log_username.'\',\'pmsubject\',\'pmtext\')">Send</button>';
			$npm .= '<img src="/images/camera.png" id="triggerBtn_SP_pm" class="triggerBtnreply" onclick="triggerUpload_pm(event, \'fu_SP_pm\')" width="22" height="22" title="Upload A Photo" />';
			$npm .= '<img src="/images/emoji.png" class="triggerBtn pmem" width="22" height="22" title="Send emoticons" id="emoji" onclick="openEmojiBox_pm()"></div>';
		$npm .= '<div class="clear"></div>';
			$npm .= generateEList("none", 'emojiBox_pm', 'pmtext');
			$npm .= '</div>';
		$npm .= '</div>';
			$npm .= '<div id="standardUpload" class="hiddenStuff">';
				$npm .= '<form id="image_SP" enctype="multipart/form-data" method="POST">';
					$npm .= '<input type="file" name="FileUpload_pm[]" id="fu_SP_pm" multiple="multiple" onchange="doUpload_pm(\'fu_SP_pm\')"/>';
				$npm .= '</form>';
			$npm .= '</div></div>';
	}
?>
<script type="text/javascript">
	var hasImagePm = "";
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
    window.onbeforeunload = function() {
      if ("" != hasImagePm) {
        return "You have not posted your image";
      }
    };
    var w = window;
    var d = document;
    var e = d.documentElement;
    var g = d.getElementsByTagName("body")[0];
    var x = w.innerWidth || e.clientWidth || g.clientWidth;
    var y = w.innerHeight || e.clientHeight || g.clientHeight;
    function openEmojiBox_pm() {
      var cancel = _("emojiBox_pm");
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
    function doUpload_pm(data) {
      var s = _(data).files[0];
      if ("" == s.name) {
        return false;
      }
      if ("image/jpeg" != s.type && "image/png" != s.type && "image/gif" != s.type && "image/jpg" != s.type) {
        return _("overlay").style.display = "block", _("overlay").style.opacity = .5, _("dialogbox").style.display = "block", _("dialogbox").innerHTML = '<p style="font-size: 18px; margin: 0px;">File type is not supported</p><p>The image that you want to upload has an unvalid extension given that we do not support. The allowed file extensions are: jpg, jpeg, png and gif. For further information please visit the help page.</p><button id="vupload" style="position: absolute; right: 3px; bottom: 3px;" onclick="closeDialog()">Close</button>', 
        document.body.style.overflow = "hidden", false;
      }
      _("triggerBtn_SP_pm").style.display = "none";
      _("uploadDisplay_SP_pm").innerHTML = '<img src="/images/rolling.gif" width="30" height="30">';
      var formData = new FormData;
      formData.append("stPic_pm", s);
      var xhr = new XMLHttpRequest;
      xhr.upload.addEventListener("progress", progressHandler, false);
      xhr.addEventListener("load", completeHandler_pm, false);
      xhr.addEventListener("error", errorHandler_pm, false);
      xhr.addEventListener("abort", abortHandler_pm, false);
      xhr.open("POST", "/php_parsers/photo_system.php");
      xhr.send(formData);
    }
    function progressHandler(event) {
      var inDays = event.loaded / event.total * 100;
      var percent_progress = Math.round(inDays);
      _("progressBar").style.width = percent_progress + "%";
      _("pbt").innerHTML = percent_progress + "%";
    }
    function completeHandler_pm(event) {
      var formattedDirections = event.target.responseText.split("|");
      _("progressBar").style.width = "0%";
      _("pbc").style.display = "none";
      if ("upload_complete_pm" == formattedDirections[0]) {
        hasImagePm = formattedDirections[1];
        _("uploadDisplay_SP_pm").innerHTML = '<img src="/tempUploads/' + formattedDirections[1] + '" class="statusImage" style="margin-top: 10px;" />';
      } else {
        _("uploadDisplay_SP_pm").innerHTML = formattedDirections[0];
        _("triggerBtn_SP_pm").style.display = "block";
      }
    }
    function errorHandler_pm(canCreateDiscussions) {
      _("uploadDisplay_SP_pm").innerHTML = "Upload Failed";
      _("triggerBtn_SP_pm").style.display = "block";
    }
    function abortHandler_pm(canCreateDiscussions) {
      _("uploadDisplay_SP_pm").innerHTML = "Upload Aborted";
      _("triggerBtn_SP_pm").style.display = "block";
    }
    function triggerUpload_pm(event, t) {
      event.preventDefault();
      _(t).click();
    }
    function postPm(cover_photo_to_crop, coords, event, a) {
      var c = _(event).value;
      if ("" == c && "" == hasImagePm) {
        return _("overlay").style.display = "block", _("overlay").style.opacity = .5, _("dialogbox").style.display = "block", _("dialogbox").innerHTML = '<p style="font-size: 18px; margin: 0px;">Blank post</p><p>To post your status you have to write or upload something firstly.</p><br /><button id="vupload" style="position: absolute; right: 3px; bottom: 3px;" onclick="closeDialog()">Close</button>', document.body.style.overflow = "hidden", false;
      }
      var line = "";
      if ("" != c) {
        line = c.replace(/\n/g, "<br />").replace(/\r/g, "<br />");
      }
      if ("" == line && "" != hasImagePm) {
        c = "||na||";
        line = '<img src="/permUploads/' + hasImagePm + '" />';
      } else {
        if ("" != line && "" != hasImagePm) {
          line = line + ('<br /><img src="/permUploads/' + hasImagePm + '" />');
        } else {
          hasImagePm = "na";
        }
      }
      var mask = _(a).value;
      var keyword = _(event).value;
      if ("" == mask || "" == keyword) {
        return _("overlay").style.display = "block", _("overlay").style.opacity = .5, _("dialogbox").style.display = "block", _("dialogbox").innerHTML = '<p style="font-size: 18px; margin: 0px;">Fill in all fields</p><p>In order to send your message without any problems you have to fill in all fields.</p><br /><button id="vupload" style="position: absolute; right: 3px; bottom: 3px;" onclick="closeDialog()">Close</button>', document.body.style.overflow = "hidden", false;
      }
      _("pmBtn").style.display = "none";
      var xhr = ajaxObj("POST", "/php_parsers/ph_system.php");
      xhr.onreadystatechange = function() {
        if (1 == ajaxReturn(xhr)) {
          if (xhr.responseText = "pm_sent") {
            _("overlay").style.display = "block";
            _("overlay").style.opacity = .5;
            _("dialogbox").style.display = "block";
            _("dialogbox").innerHTML = '<p style="font-size: 18px; margin: 0px;">Successfully sent</p><p>Your message has been successfully sent to the certain person who will get a notification about this private message.</p><br /><button id="vupload" style="position: absolute; right: 3px; bottom: 3px;" onclick="closeDialog()">Close</button>';
            document.body.style.overflow = "hidden";
            _(a).value = "";
            _(event).value = "";
            _("pmBtn").style.display = "block";
            _("triggerBtn_SP_pm").style.display = "block";
            _("uploadDisplay_SP_pm").innerHTML = "";
            _("pmtext").style.height = "40px";
            _("fu_SP_pm").value = "";
            _("emojiBox_pm").style.display = "none";
            _("pmform").style.display = "none";
            hasImagePm = "";
          } else {
            _("overlay").style.display = "block";
            _("overlay").style.opacity = .5;
            _("dialogbox").style.display = "block";
            _("dialogbox").innerHTML = '<p style="font-size: 18px; margin: 0px;">An error occured</p><p>Unfortunately an unknown error has occured with your private message. Please try again later and check everything is proper.</p><br /><button id="vupload" style="position: absolute; right: 3px; bottom: 3px;" onclick="closeDialog()">Close</button>';
            document.body.style.overflow = "hidden";
          }
        }
      };
      xhr.send("action=new_pm&fuser=" + coords + "&tuser=" + cover_photo_to_crop + "&data=" + mask + "&data2=" + keyword + "&image=" + hasImagePm);
    }
</script>

<?php echo $pm_ui; ?>