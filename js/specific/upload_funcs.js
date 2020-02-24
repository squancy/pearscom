/*
  File (image) related functions: uploading & progess bar both for posts and replies.
  TODO: merge doUpload() for post and reply & add more logic
*/

function doUpload(data, box = 'uploadDisplay_SP', btn = 'triggerBtn_SP', picName = 'stPic',
  msg = 'upload_complete') {
  var opts = _(data).files[0];
  if (opts.name == "") {
    return false;
  }

  // Check for allowed img extensions
  if ("image/jpeg" != opts.type && "image/gif" != opts.type && "image/png" != opts.type &&
    "image/jpg" != opts.type) {
    genDialogBox(); 
    return false;
  }

  _(box).innerHTML = '<img src="/images/rolling.gif" width="30" height="30">';
  _("pbc").style.display = "block";

  // Create a form data and attach img
  var fd = new FormData;
  fd.append(picName, opts);
  var request = new XMLHttpRequest;

  // Register handlers for progress bar & 3 possible outcomes
  request.upload.addEventListener("progress", progressHandler, false);
  request.addEventListener("load", function completeWrapper(event) {
    completeHandler(event, box, btn, msg);
  }, false);
  request.addEventListener("error", function errorWrapper(event) {
    errorHandler(event, box, btn);
  }, false);
  request.addEventListener("abort", function abortWrapper(event) {
    abortHandler(event, box, btn);
  }, false);
  request.open("POST", "/php_parsers/photo_system.php");
  request.send(fd);
}

// Update progress bar
function progressHandler(event) {
  var inDays = event.loaded / event.total * 100;
  var percent_progress = Math.round(inDays);
  _("progressBar").style.width = percent_progress + "%";
  _("pbt").innerHTML = percent_progress + "%";
}

// Fired on successful img upload
function completeHandler(event, box, btn, msg) {
  var formattedDirections = event.target.responseText.split("|");
  _("progressBar").style.width = "0%";
  _("pbc").style.display = "none";
  if (msg == formattedDirections[0]) {
    hasImage = formattedDirections[1];
    _(box).innerHTML = `
      <img src="/tempUploads/${formattedDirections[1]}" class="statusImage" />`;
  } else {
    _(box).innerHTML = formattedDirections[0];
    _(btn).style.display = "block";
  }
}

function errorHandler(event, box, btn) {
  _(box).innerHTML = "Upload Failed";
  _(btn).style.display = "block";
}

function abortHandler(event, box, btn) {
  _(box).innerHTML = "Upload Aborted";
  _(btn).style.display = "block";
}

function doUpload_reply(body, sharpCos) {
  var opts = _(body).files[0];
  if (opts.name == "") {
    return false;
  }

  if ("image/jpeg" != opts.type && "image/gif" != opts.type && "image/png" != opts.type &&
    "image/jpg" != opts.type) {
    genDialogBox();
  }

  // Attach img to form data
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
  prepareDialog();
  _("dialogbox").innerHTML = "<b>Your uploading image status</b><p>" + o + "</p>";
  document.body.style.overflow = "hidden";
}

function completeHandler_reply(event) {
  var formattedDirections = event.target.responseText.split("|");
  if ("upload_complete_reply" == formattedDirections[0]) {
    hasImage = formattedDirections[1];
    prepareDialog();
    _("dialogbox").innerHTML = `
      <p style="font-size: 18px; margin: 0px;">
        Your uploading image
      </p>

      <p>
        You have successfully uploaded your image. Click on the <i>Close</i> button and 
        now you can post your reply.
      </p>

      <img src="/tempUploads/${formattedDirections[1]}" class="statusImage">
      <button id="vupload" style="position: absolute; right: 3px; bottom: 3px;"
        onclick="closeDialog()">Close</button>`;
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

// jeez?
function triggerUpload(event, file) {
  event.preventDefault();
  _(file).click();
}

function triggerUpload_reply(event, t) {
  event.preventDefault();
  _(t).click();
}
