function doUpload(x, e) {
  var t = _(e).files[0];
  if (t.name == '') {
      return false;
  }

  if (t.type != 'image/jpeg' && t.type != 'image/png' && t.type != 'image/gif' &&
    t.type != 'image/jpg') {
    genDialogBox();
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

function progressHandler(x) {
  var e = x.loaded / x.total * 100;
  var t = '<p>' + Math.round(e) + '% uploaded please wait ...</p>';
  prepareDialog();
  _('dialogbox').innerHTML = `
    <b>Your uploading image status</b>
    <p>
      <p>${Math.round(e)} % uploaded please wait...</p>
    </p>
  `;
}

function completeHandler(x) {
  var e = x.target.responseText.split('|');
  if (e[0] == 'upload_complete_msg') {
    hasImage = e[1];
    prepareDialog();
    _('dialogbox').innerHTML = `
      <p style="font-size: 18px; margin: 0px;">
        Your uploading image
      </p>
      <p>
        You have successfully uploaded your image.
        Click on the <b>Close</b> button and now you can post your reply.
      </p>
      <img src="/tempUploads/${e[1]}" class="statusImage">
      <button id="vupload" style="position: absolute; right: 3px; bottom: 3px;" 
        onclick="closeDialog()">Close</button>`;
  } else {
    _('uploadDisplay_SP_msg_' + e[2]).innerHTML = e[0];
    _('triggerBtn_SP_' + e[2]).style.display = 'block';
  }
}

function errorHandler(x) {
  errorDialog(); 
}

function abortHandler(x) {
  errorDialog();
}

function postPmMsg(holder, uname, texta, sender, pmid) {
  var result = _(texta).value;
  if (result == "" && hasImage == "") {
    prepareDialog();
    _("dialogbox").innerHTML = `
      <p style="font-size: 18px; margin: 0px;">Blank post</p>
      <p>To post your status you have to write or upload something firstly.</p>
      <br />
      <button id="vupload" style="position: absolute; right: 3px; bottom: 3px;"
        onclick="closeDialog()">Close</button>
    `;
    return false;
  }

  _("swithidbr_msg_" + pmid).innerHTML = `
    <img src="/images/rolling.gif" width="30" height="30" style="float: left;">
  `;

  var flag = "";
  if (result != "") {
    flag = result;
  }

  if (flag == "" && hasImage != "") {
    result = "||na||";
    flag = '<img src="/permUploads/'+hasImage+'" style="border-radius: 20px;">';
  } else if (flag != "" && hasImage != "") {
    flag += '<br /><img src="/permUploads/'+hasImage+'" style="border-radius: 20px;"/>';
  } else {
    hasImage = "na";
  }

  _("pmsendBtn").disabled = true;
  var request = ajaxObj("POST", "/php_parsers/ph_system.php");
  request.onreadystatechange = function() {
    if (ajaxReturn(request)) {
      var x = request.responseText.split("|");
      if (x[0] == "reply_ok") {
        _("pm_" + pmid).innerHTML += `
          <div id="status_${pmid}" class="status_boxes" style="margin-right: auto;
            margin-left: auto; box-sizing: border-box; width: calc(100% - 20px);">
          <div>
          <b>Posted by you just now:</b>
          <span id="sdb_${pmid}"></span><br/>
          ${flag}
          </div></div>`;
        _("pmsendBtn").disabled = false;
        /*
          <button class="delete_s" onclick="deleteMessage(\'' + pmid + "','" + sender + "','"
          + uname + '\')" title="Delete Status And Its Replies">X</button>
        */
        _(texta).value = "";
        _("triggerBtn_SP_" + pmid).style.display = "block";
        _("btns_SP_" + pmid).style.display = "none";
        _("uploadDisplay_SP_msg_" + pmid).innerHTML = "";
        _("fu_SP_" + pmid).value = "";
        hasImage = "";
        _("swithidbr_msg_" + pmid).innerHTML = `
          <button id="pmsendBtn" class="btn_rply"
            onclick="postPmMsg('${holder}','${uname}','${texta}','${sender}','${pmid}')">
            Post
          </button>
        </span>`;
      } else {
        genErrorDialog();
      }
    }
  }
  request.send("action=" + holder + "&user=" + uname + "&data=" + result + "&image=" +
    hasImage + "&osender=" + sender + "&pmid=" + pmid);
}

function deleteMessage(x, e, t) {
  if (!confirm('Are you sure you want to delete this message?')) {
    return false;
  }
  var o = ajaxObj('POST', '/php_parsers/ph_system.php');
  o.onreadystatechange = function () {
    if (ajaxReturn(o)) {
      if (o.responseText == "deletemessage_ok") {
        if(_("wholle_" + x) != undefined && _("whole_" + x) != undefined){
            _("whole_" + x).style.display = "none";
            _("wholle_" + x).style.display = "none";
        }else{
            _("status_" + x).style.display = "none";
        }
      } else {
        genErrorDialog();
      }
    }
  }
  o.send('action=deletemessage&pmid=' + x + '&stime=' + t + '&uname=' + e);
}

function showMessage(x) {
  var e = _('pm_wrap_' + x);
  if (e.style.display == 'block') {
    e.style.display = 'none';
    _('show_' + x).style.backgroundColor = 'red';
  } else {
    e.style.display = 'block';
    _('show_' + x).style.backgroundColor = '#e60b0b';
  }
}

function deletePm(x, e, t) {
  if (!confirm('Are you sure you want to delete the complete conversation?')) {
    return false;
  }

  var o = ajaxObj('POST', '/php_parsers/ph_system.php');
  o.onreadystatechange = function () {
    if (ajaxReturn(o)) {
      if (o.responseText == 'delete_ok') {
        window.location = '/private_messages/' + t;
      } else {
        genErrorDialog();
      }
    }
  }
  o.send('action=delete_pm&pmid=' + x + '&originator=' + e);
}

function markRead(x, e) {
  var t = ajaxObj('POST', '/php_parsers/ph_system.php');
  t.onreadystatechange = function () {
    if (ajaxReturn(t)) {
      if (t.responseText == 'read_ok') {
        prepareDialog();
        _('dialogbox').innerHTML = `
          <p style="font-size: 18px; margin: 0px;">
            Important conversation
          </p>
          <p>
            You have successfully marked this conversation as important.
          </p>
          <br />
          <button id="vupload" style="position: absolute; right: 3px; bottom: 3px;"
            onclick="closeDialog()">Close</button>`;
      } else {
        genErrorDialog();
      }
    }
  }
  t.send('action=mark_as_read&pmid=' + x + '&originator=' + e);
}

window.onbeforeunload = function () {
  if (hasImage != '') {
    return 'You have not posted your image';
  }
}

function showBtnDiv_pm(x) {
  _('btns_SP_' + x).style.display = 'block';
}
