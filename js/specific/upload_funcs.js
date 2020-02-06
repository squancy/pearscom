/*
  File (image) related functions: uploading & progess bar both for posts and replies.
  TODO: merge doUpload() for post and reply & add more logic
*/

function genDialogBox() {
  // prepareDialog is decalred in p_dialog.js
  prepareDialog();
  _("dialogbox").innerHTML = `
    <p style="font-size: 18px; margin: 0px;">
      File type is not supported
    </p>

    <p>
      The image that you want to upload has an unvalid extension given that we do not
      support. The allowed file extensions are: jpg, jpeg, png and gif. For further
      information please visit the help page.
    </p>

    <button id="vupload" style="position: absolute; right: 3px; bottom: 3px;"
      onclick="closeDialog()">Close</button>
  `;
}

function doUpload(data) {
  var opts = _(data).files[0];
  if ("" == opts.name) {
    return false;
  }

  // Check for allowed img extensions
  if ("image/jpeg" != opts.type && "image/gif" != opts.type && "image/png" != opts.type &&
    "image/jpg" != opts.type) {
    genDialogBox(); 
  }

  _("uploadDisplay_SP").innerHTML = '<img src="/images/rolling.gif" width="30" height="30">';
  _("pbc").style.display = "block";

  // Create a form data and attach img
  var fd = new FormData;
  fd.append("stPic", opts);
  var request = new XMLHttpRequest;

  // Register handlers for progress bar & 3 possible outcomes
  request.upload.addEventListener("progress", progressHandler, false);
  request.addEventListener("load", completeHandler, false);
  request.addEventListener("error", errorHandler, false);
  request.addEventListener("abort", abortHandler, false);
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
function completeHandler(event) {
  var formattedDirections = event.target.responseText.split("|");
  _("progressBar").style.width = "0%";
  _("pbc").style.display = "none";
  if ("upload_complete" == formattedDirections[0]) {
    hasImage = formattedDirections[1];
    _("uploadDisplay_SP").innerHTML = `
      <img src="/tempUploads/${formattedDirections[1]}" class="statusImage" />`;
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

function triggerUpload(event, file) {
  event.preventDefault();
  _(file).click();
}

function triggerUpload_reply(event, t) {
  event.preventDefault();
  _(t).click();
}
