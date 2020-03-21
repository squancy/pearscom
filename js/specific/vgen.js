/*
  The following functions are used on videos.php
*/

// Show uploaded video/thumbnail filename
function showfile(f, box) {
  var e = _(f).value.substr(12);
  _(box).innerHTML = "&nbsp;" + e;
}

// Upload video to server
function uploadVideo() {
  function uploadFile(uid) {
    // Create new form data and append video info
    var data = new FormData;
    data.append("stPic_video", blob);
    if (_("asd").files.length != 0) {
      data.append("stPic_poster", inputblob);
    }

    data.append("stVideo_name", i);
    data.append("stVideo_des", e);
    data.append("stVideo_dur", uid);

    var request = new XMLHttpRequest;

    // Register upload handlers

    /*
      TODO: progressHandler is not fired for some unknown reasons; fix it
      request.upload.addEventListener("progress", progressHandler, false);
    */

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
  if (!blob || !blob.name) {
    _("txt_holder").innerHTML = `
      <p style='color: red;' class='txtc'>Please add a video</p>
    `;
    return false;
  }

  // Check for video file type
  if ("video/webm" != blob.type && "video/mp4" != blob.type && "video/ogg" != blob.type &&
    "audio/mp3" != blob.type && "video/mov" != blob.type) {
    prepareDialog();
    _("dialogbox").innerHTML = `
      <p style="font-size: 18px; margin: 0px;">File type is not supported</p>
      <p>
        The video that you want to upload has an unvalid extension given that we do
        not support. The allowed file extensions are: MP4, WebM and Ogg.
        For further information please visit the help page.
      </p>
      <button id="vupload" style="position: absolute; right: 3px; bottom: 3px;"
        onclick="closeDialog()">Close</button>`;
    return false;
  }

  // Check for thumbnail file type
  if (_("asd").files.length != 0 && "image/jpg" != inputblob.type &&
    "image/jpeg" != inputblob.type && "image/png" != inputblob.type &&
    "image/gif" != inputblob.type) {
    genDialogBox();
    return false;
  }

  if ("video/webm" == blob.type || "video/mp4" == blob.type || "video/ogg" == blob.type 
    || "video/mov" == blob.type) {
    // Create a new video element and load its metadata to get video duration
    (player = document.createElement("video")).preload = "metadata";
    window.URL.revokeObjectURL(player.src);
    player.addEventListener("durationchange", function() {
      uploadFile(player.duration);
    });
  } else {
    // If file is an audio get duration too
    var player = document.createElement("audio");
    window.URL.revokeObjectURL(player.src);
    player.addEventListener("durationchange", function() {
      uploadFile(player.duration);
    });
  }
  player.src = URL.createObjectURL(blob);
  _('rolling').innerHTML = `
    <img src="/images/rolling.gif" width="30" height="30"
      style="display: block; margin: 0 auto;">
  `;
  // _("pbc").style.display = "block";
}

// Update progress bar during upload
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
    _("txt_holder").innerHTML = `
      <p style='color: red;' class='txtc'>Video has been successfully uploaded</p>`;
  } else {
    _("txt_holder").innerHTML = `
      <p style='color: red;' class='txtc'>
        Oops... It seems that an error occurred during the uploading: ${t[0]}
      </p>
    `;
  }
}

function errHandler(msg) {
  _("txt_holder").innerHTML = msg;
  _("asd").style.display = "block";
  _("as").style.display = "block";
  _("file").style.display = "block";
  _("choose_file").style.display = "block";
}

function errorHandler(callback) {
  errHandler("Upload Failed");
}

function abortHandler(canCreateDiscussions) {
  errHandler("Upload Aborted");
}

var isb = "not_set";

// Delete video
// TODO: move delete video to video_bigger.php
/*function deleteVideo(styles, id) {
  if (!confirm("Are you sure you want to delete this video?")) {
    return false;
  }
  if (!styles) {
    genErrorDialog();
    return false;
  } else {
    var xhr = ajaxObj("POST", "/php_parsers/video_parser.php");
    xhr.onreadystatechange = function() {
      if (ajaxReturn(xhr)) {
        if ("delete_success" == xhr.responseText) {
          location.reload();
          window.scrollTo(0, 0);
        } else {
          genErrorDialog();
        }
      }
    }
  }
  xhr.send("id=" + styles + "&type=" + id);
}*/

function getLVideos() {
  var value = _("searchArt").value;
  if (!value) {
    _("vidSearchResults").style.display = "none";
    return false;
  }

  var result = encodeURI(value);
  window.location = "/search_videos/" + result + "&uU=" + VUNAME;
}

function getVideos(txt) {
  if (!txt) {
    _("vidSearchResults").style.display = "none"
    return false;
  }

  _("vidSearchResults").style.display = "block";
  if (!_("vidSearchResults").innerHTML) {
    _("vidSearchResults").innerHTML = '<img src="/images/rolling.gif" width="30" height="30">';
  }

  var decdata = encodeURI(txt);
  var xhr = new XMLHttpRequest;
  xhr.open("POST", "/video_exec.php", true);
  xhr.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
  xhr.onreadystatechange = function() {
    if (xhr.readyState == 4 && xhr.status == 200) {
      var response = xhr.responseText;
      if (response) {
        _("vidSearchResults").innerHTML = response;
      }
    }
  }
  xhr.send("a=" + decdata + "&u=" + VUNAME);
}
