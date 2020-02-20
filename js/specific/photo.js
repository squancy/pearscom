function sharePhoto(o) {
  var e = ajaxObj("POST", "/php_parsers/status_system.php");
  e.onreadystatechange = function() {
    if (ajaxReturn(e)) {
      if (e.responseText == "share_photo_ok") {
        prepareDialog();
        _("dialogbox").innerHTML = `
          <p style="font-size: 18px; margin: 0px;">
            Share this photo
          </p>
          <p>
            You have successfully shared this photo which will be visible on your main
            profile page in the comment section.
          </p>
          <button id="vupload" style="float: right;" onclick="closeDialog()">Close</button>`;
      } else {
        genErrorDialog();
      }
    }
  }
  e.send("action=share_photo&id=" + o);
}

function openImgBig(o) {
  _("dialogbox").innerHTML = `
    <img src=${o}">
    <button id="vupload" style="float: right; margin: 3px;" onclick="closeDialog()">
      Close
    </button>
  `;
}

function deletePhoto(o) {
  if (!confirm(`Are you sure you want to delete this photo?`)) {
      return false;
  }

  var e = _("info_stat"),
      t = ajaxObj("POST", "/php_parsers/delete_photo.php");
  t.onreadystatechange = function() {
    if (ajaxReturn(t)) {
      if (t.responseText != "delete_photo_success") {
        e.innerHTML = t.responseText;
      } else {
        e.innerHTML = `
          <p style='font-size: 16px; color: green; text-align: center;'>
            You have successfully deleted this photo!
          </p>
        `;
      }
    }
  }
  t.send("id=" + o);
}
