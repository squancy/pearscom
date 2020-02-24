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
  prepareDialog();
  _("dialogbox").innerHTML = `
    <img src="${o}">
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

/*
  These functions are used on the main photos page
*/

// Show filename that is being uploaded
function showfile() {
  var e = _('file').value.substr(12);
  _('sel_f').innerHTML = ' ' + _('file').value.substr(12);
}

// Called when user uploads a photo
function uploadPhoto() {
  var e = _('file').files[0];
  var o = _('cgal').value;
  var t = _('description').value;
  var i = _('p_status');
  var n = _('vupload');

  // Check for empty fields
  if (e == '' || o == '') {
    return 'You did not give a gallery or a photo!';
  }

  // Check if image file type is supported
  if (e.type != 'image/jpg' && e.type != 'image/jpeg' && e.type != 'image/png' &&
    e.type != 'image/gif') {
    genDialogBox();
    return false;
  }

  // i.innerHTML = '<img src="/images/rolling.gif" width="30" height="30">';
  n.style.display = 'none';
  _('pbc').style.display = 'block';

  // Create a new form data and append photo, gallery and description to it
  var a = new FormData();
  a.append('stPic_photo', e);
  a.append('cgal', o);
  a.append('des', t);
  
  // Handle progress bar
  var p = new XMLHttpRequest();
  p.upload.addEventListener('progress', progressHandler, false);
  p.addEventListener('load', completeHandler, false);
  p.addEventListener('error', errorHandler, false);
  p.addEventListener('abort', abortHandler, false);
  p.open('POST', '/php_parsers/photo_system.php');
  p.send(a);
}

// Constantly update the status of the progress bar
function progressHandler(e) {
  var o = e.loaded / e.total * 100, t = Math.round(o);
  _('progressBar').style.width = Math.round(o) + '%';
  _('pbt').innerHTML = Math.round(o) + '%';
}

// Called when image has been successfully uploaded
function completeHandler(e) {
  var o = e.target.responseText.split('|');
  //_('progressBar').style.width = '0%';
  //_('progressBar').style.display = 'none';
  if (o[0] != 'upload_complete') {
    _('p_status').innerHTML = `
      <p style='font-size: 14px; margin: 0px; padding: 0px;'>
        An unknown error has occured! Please try again later!
        <img src='/images/wrong.png' width='11' height='11'>
      </p>`;
    _('vupload').style.display = 'block';
    _('progressBar').value = 0;
    _('p_status').innerHTML = '';
  }
}

// Called when image uploading was unsuccessful
function errorHandler(e) {
  _('p_status').innerHTML = 'Upload Failed';
  _('vupload').style.display = 'block';
}

// Called when the uploading process has been aborted
function abortHandler(e) {
  _('p_status').innerHTML = 'Upload Aborted';
  _('vupload').style.display = 'block';
}

function getPhotos(e) {
  if (e == '') {
    _('phoSearchResults').style.display = 'none';
    return false;
  }

  _('phoSearchResults').style.display = 'block';

  if (_('phoSearchResults').innerHTML == '') {
    _('phoSearchResults').innerHTML = '<img src="/images/rolling.gif" width="30" height="30">';
  }

  var o = encodeURI(e), t = new XMLHttpRequest();
  t.open('POST', '/photo_exec.php', true);
  t.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
  t.onreadystatechange = function () {
    if (t.readyState == 4 && t.status == 200) {
      var e = t.responseText;
      if (e != '') {
        _('phoSearchResults').innerHTML = e;
      }
    }
  }
  t.send('a=' + encodeURI(e) + "&u=" + UNAME);
}

// Implement drag & drop functionality on the main photos page
if (iso == "Yes") {
  // If user owns the page implement drag & drop system
  (function() {
    var t = _("photo_form");
    var oldh = _("photo_form").clientHeight;

    // Change the look of the box when user drags images over it
    t.ondragover = function() {
      console.log('a');
      t.innerHTML = `
        <p class='pcblueform'>Drag and drop your images here</p>
        <div id='pcfillin'></div>
      `;

      t.style.border = "8px dashed red";
      t.style.height = oldh + "px";
      t.style.marginTop = "20px"
      return false;
    }

    // When user drops the image upload them
    t.ondrop = function(event) {
      event.preventDefault();
      (function(params) {
        if (params == "") {
          return false;
        }

        // Create a new form data and append the file(s) to be uploaded
        var formData = new FormData;
        var p = 0;
        for (; p < params.length; p++) {
          formData.append("file[]", params[p]);
        }

        _("pcfillin").innerHTML = `
          <img src="/images/rolling.gif" width="20" height="20">`;
        var i = UNAME;

        // AJAX req to server
        var xhr = new XMLHttpRequest;
        xhr.onload = function() {
          var contents = this.responseText.split("|");
          if ("success" == contents[0]) {
            // If upload was successful put the images on display
            _("pcfillin").innerHTML = "";
            var iExternal = 1;
            for (; iExternal < contents.length - 1; iExternal++) {
              var t = "/user/" + i + "/" + contents[iExternal];
              _("pcfillin").innerHTML += `
              <a href='/photo_zoom/${encodeURIComponent(i)}/${contents[iExternal]}'>
                <div class='pccanvas' style='width: calc(20% - 3px); height: 125px;'>
                  <div style='background-image: url("${t}"); background-repeat: no-repeat;
                    background-position: center; background-size: cover; height: 125px;
                    margin-right: 2px;'>
                  </div>
                </div>
              </a>
            `;
            }
          }
        }
        xhr.open("POST", "/php_parsers/ddupload.php");
        xhr.send(formData);
      })(event.dataTransfer.files);
    }

    // If user leaves the drag & drop zone reset its style to default
    t.ondragleave = function() {
      t.innerHTML = beforeInner, 
      t.style.height = "auto";
      t.style.border = "none";
      return false;
    };
  })();
}
