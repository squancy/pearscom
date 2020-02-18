function sendInv() {
  var e = _("email_invite").value,
      t = _("t").value,
      i = _("status");
  if (e == ""|| t == "") {
    i.innerHTML = "Please fill all the form data";
  } else {
    _("submit_btn").style.display = "none";
    i.innerHTML = '<img src="/images/rolling.gif" width="30" height="30">';
    var s = ajaxObj("POST", "invite.php");
    s.onreadystatechange = function() {
      if (ajaxReturn(s)) {
        if (s.responseText != "invite_success") {
          i.innerHTML = s.responseText;
          _("submit_btn").style.display = "block";
        } else {
          i.innerHTML = `
            <p style='font-size: 16px;'>
              You have successfully send your email to ${e}
            </p>
          `;
          _("t").value = "";
          _("email_invite").value = "";
        }
      }
    }
    s.send("e=" + e + "&t=" + t)
  }
}
