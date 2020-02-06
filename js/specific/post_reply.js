/*
  Handle status post and replies on the client-side.
  prepareDialog() and closeDialog() are defined in p_dialog.js
*/

function isEmptyPost(c, hasImage) {
  if (c == "" && hasImage == "") {
    prepareDialog();
    _("dialogbox").innerHTML = `
      <p style="font-size: 18px; margin: 0px;">
        Blank post
      </p>

      <p>To post your status you have to write or upload something firstly.</p>
      <br />
      <button id="vupload" style="position: absolute; right: 3px; bottom: 3px;"
        onclick="closeDialog()">Close</button>
    `;
    return true;
  }
  return false;
}

function attachImage(c, hasImage) {
  let line = '';
  if (c != "") {
    line = c.replace(/\n/g, "<br />").replace(/\r/g, "<br />");
  }

  // Set vars in regard of any image posts
  if (line == "" && hasImage != "") {
    c = "||na||";
    line = '<img src="/permUploads/' + hasImage + '" />';
  } else if ("" != line && "" != hasImage) {
    line = line + ('<br /><img src="/permUploads/' + hasImage + '" />');
  } else {
    hasImage = "na";
  }
  return [line, hasImage]
}

function showError() {
  _("dialogbox").innerHTML = `
    <p style="font-size: 18px; margin: 0px;">
      An error occured
    </p>
    <p>
      Unfortunately an unknown error has occured with your post.
      Please try again later and check everything is proper.
    </p>
    <br />
    <button id="vupload" style="position: absolute; right: 3px; bottom: 3px;"
      onclick="closeDialog()">Close</button>
  `;
}

function postToStatus(cond, thencommands, pollProfileId, userId) {
  var c = _(userId).value;
  if (isEmptyPost(c, hasImage)) return;
  let line = '';
  [line, hasImage] = attachImage(c, hasImage);

  // Loading gif
  _("swithspan").innerHTML = `<img src="/images/rolling.gif" width="30" height="30"
    style="float: left;">`;

  // AJAX request to server side
  var xhr = ajaxObj("POST", "/php_parsers/article_status_system.php");
  xhr.onreadystatechange = function() {
    if (1 == ajaxReturn(xhr)) {
      var tilesToCheck = xhr.responseText.split("|");
      if ("post_ok" == tilesToCheck[0]) {
        var t = tilesToCheck[1];
        var newHTML = _("statusarea").innerHTML;
        _("statusarea").innerHTML = `
          <div id="status_${t}" class="status_boxes">
            <div>
              <b>Posted by you just now:</b>
              <span id="sdb_${t}">
                <button onclick="return false;" class="delete_s"
                  onmousedown="deleteStatus('${t}', 'status_${t}');"
                  title="Delete Status And Its Replies">X</button>
              </span>
              <br />
              ${line}
            </div>
          </div>
          <br />
          ${newHTML}
        `;

        _("swithspan").innerHTML = `
          <button id='statusBtn'
            onclick="postToStatus('status_post', 'a', '${UNAME}, 'statustext')"
          class="btn_rply">Post</button>`;
        _(userId).value = "";
        _("btns_SP").style.display = "none";
        _("uploadDisplay_SP").innerHTML = "";
        _("fu_SP").value = "";
        hasImage = "";
      } else {
        prepareDialog();
        showError();
      }
    }
  }
  xhr.send("action=" + cond + "&type=" + thencommands + "&user=" + pollProfileId + "&data=" + c + "&image=" + hasImage);
}

function replyToStatus(id, supr, o, dizhi) {
  var c = _(o).value;
  if (isEmptyPost(c, hasImage)) return; 
  var line = "";

  // Attach img
  [line, hasImage] = attachImage(c, hasImage);

  // Loading gif
  _("swithidbr_" + id).innerHTML = `<img src="/images/rolling.gif" width="30" height="30"
    style="float: left;">`;

  // Send to server to process the request
  var xhr = ajaxObj("POST", "/php_parsers/article_status_system.php");
  xhr.onreadystatechange = function() {
    if (1 == ajaxReturn(xhr)) {
      var actionsLengthsArray = xhr.responseText.split("|");
      if ("reply_ok" == actionsLengthsArray[0]) {
        var l = actionsLengthsArray[1];
        c = c.replace(/</g, "<").replace(/>/g, ">")
          .replace(/\n/g, "<br />").replace(/\r/g, "<br />");
        _("status_" + id).innerHTML += `
          <div id="reply_${l}" class="reply_boxes">
            <div>
              <b>Reply by you just now:</b>
              <span id="srdb_${l}">
                <button onclick="return false;" class="delete_s"
                  onmousedown="deleteReply('${l}', 'reply_${l}');"
                  title="Delete Comment">X</button>
              </span>
              <br />
              ${line}
            </div>
          </div>
        `;

        _("swithidbr_" + id).innerHTML = `
          <button id="replyBtn_${id}" class="btn_rply"
            onclick="replyToStatus('${id}', '${UNAME}', 'replytext_${id}', this);">
            Reply
          </button>
        `;

        _(o).value = "";
        _("triggerBtn_SP_reply_").style.display = "block";
        _("btns_SP_reply_" + id).style.display = "none";
        _("uploadDisplay_SP_reply_" + id).innerHTML = "";
        _("fu_SP_reply").value = "";
        hasImage = "";
      } else {
        prepareDialog();
        showError();
      }
    }
  }
  xhr.send("action=status_reply&sid=" + id + "&user=" + supr + "&data=" + c + "&image=" + hasImage);
}
