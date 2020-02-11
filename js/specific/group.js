/*
  Functions associated with group pages (mostly group.php)
*/

function joinGroup(address) {
  var request = ajaxObj("POST", "/php_parsers/group_parser2.php");
  request.onreadystatechange = function() {
    if (ajaxReturn(request)) {
      var doctypeContent = request.responseText;
      if (doctypeContent == "pending_approval") {
        _("joinBtn").style.display = "none";
        prepareDialog();
        _("dialogbox").innerHTML = `
          <p style="font-size: 18px; margin: 0px;">
            Awaiting approval
          </p>

          <p>
            We have successfully sent your request to the group.
            Now you have to wait until a moderator approves your request to join this group.
          </p>
          <br />
          <button id="vupload" style="position: absolute; right: 3px; bottom: 3px;"
            onclick="closeDialog()">Close</button>
        `;
      } else if ("refresh_now" == doctypeContent) {
        _("joinBtn").style.display = "none";
        prepareDialog();
        _("dialogbox").innerHTML = `
          <p style="font-size: 18px; margin: 0px;">
            Success
          </p>

          <p>
            You have successfully joined to this group.
            Refresh the page to actually join in.
          </p>
          <br />
          <button id="vupload" style="position: absolute; right: 3px; bottom: 3px;"
            onclick="closeDialog()">Close</button>`;
      } else {
        genErrorDialog();
      }
    }
  }
  request.send("action=join_group&g=" + address);
}

function approveMember(pollProfileId, userId) {
  var request = ajaxObj("POST", "/php_parsers/group_parser2.php");
  request.onreadystatechange = function() {
    if (ajaxReturn(request)) {
      if ("member_approved" == request.responseText) {
        prepareDialog();
        _("dialogbox").innerHTML = `
          <p style="font-size: 18px; margin: 0px;">
            Member approved
          </p>

          <p>
            You have successfully approved ${pollProfileId}.
          </p>
          <br />
          <button id="vupload" style="position: absolute; right: 3px; bottom: 3px;"
            onclick="closeDialog()">Close</button>`;
      } else {
        genErrorDialog();
      }
    }
  }
  request.send("action=approve_member&u=" + pollProfileId + "&g=" + userId);
}

function declineMember(pollProfileId, userId) {
  var request = ajaxObj("POST", "/php_parsers/group_parser2.php");
  request.onreadystatechange = function() {
    if (ajaxReturn(request)) {
      if ("member_declined" == request.responseText) {
        prepareDialog();
        _("dialogbox").innerHTML = `
          <p style="font-size: 18px; margin: 0px;">
            Member declined
          </p>

          <p>
            You declined ${pollProfileId}.
          </p>
          <br />
          <button id="vupload" style="position: absolute; right: 3px; bottom: 3px;"
            onclick="closeDialog()">Close</button>
        `;
      }
    }else{
      genErrorDialog();
    }
  }
  request.send("action=decline_member&u=" + pollProfileId + "&g=" + userId);
}

function addAdmin(i) {
  var vulnData = _("new_admin").value;
  var xhr = ajaxObj("POST", "/php_parsers/group_parser2.php");
  xhr.onreadystatechange = function() {
    if (ajaxReturn(xhr)) {
      if ("admin_added" == xhr.responseText) {
        prepareDialog();
        _("dialogbox").innerHTML = `
          <p style="font-size: 18px; margin: 0px;">
            Moderator added
          </p>

          <p>
            You have successfully added ${vulnData} as a moderator.
          </p>
          <br />
          <button id="vupload" style="position: absolute; right: 3px; bottom: 3px;"
            onclick="closeDialog()">Close</button>`;
      }else{
        genErrorDialog();
      }
    }
  }
  xhr.send("action=add_admin&n=" + vulnData + "&g=" + i);
}

function saveDesGr() {
  var vulnData = _("desgivegr").value;
  var xhr = ajaxObj("POST", "/php_parsers/group_parser2.php");
  _("grdes_holder").innerHTML = '<img src="/images/rolling.gif" width="30" height="30">';
  xhr.onreadystatechange = function() {
    if (ajaxReturn(xhr)) {
      var e = xhr.responseText.split("|");
      var linkedsceneitem = (e[1]).toString();

      linkedsceneitem = linkedsceneitem.replace(/\\n/g, "<br>");
      if ("des_save_success" == e[0]) {
        _("grdes_holder").innerHTML = '<p style="font-size: 14px;">' + linkedsceneitem + "</p>";
      } else {
        genErrorDialog();
      }
    }
  }
  xhr.send("text=" + vulnData + "&gr=" + GNAME);
}

var stat_gr = "less";
function opentext_gr(name) {
  if ("less" == stat_gr) {
    _("lessmore_gr_" + name).style.display = "block";
    _("toggle_gr_" + name).innerText = "See Less";
    _("hide_" + name).style.display = "none";
    stat_gr = "more";
  } else {
    if ("more" == stat_gr) {
      _("lessmore_gr_" + name).style.display = "none";
      _("toggle_gr_" + name).innerText = "See More";
      _("hide_" + name).style.display = "block";
      stat_gr = "less";
    }
  }
}

function quitGroup(id) {
  if (1 != confirm("Are you sure you want to quit from this group?")) {
    return false;
  }

  var xhr = ajaxObj("POST", "/php_parsers/group_parser2.php");
  xhr.onreadystatechange = function() {
    if (ajaxReturn(xhr) && xhr.responseText == "was_removed") {
      window.location.href = "user.php?u=" + UNAME;
    }
  }
  xhr.send("action=quit_group&g=" + id);
}

  if(GRDES_OLD == "") GRDES_OLD = GRDES;

  GRDES_OLD = GRDES_OLD.replace(/<br \/>/g, "\n");

  function changeDesGr(){
    _("grdes_holder").innerHTML = `
      <p style="font-size: 16px;">New description</p>
      <textarea id="desgivegr" style="width: 100%; margin-top: 0;" class="ssel"
        placeholder="Give a description about the group"
        onkeyup="statusMax(this, 3000)">${GRDES_OLD}</textarea>
      <button id="des_save_btn" class="main_btn_fill fixRed" onclick="saveDesGr()">
        Save description
      </button>
    `;
  }
