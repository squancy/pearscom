function forgotpass() {
  var e = _("email").value;
  if (e == "") {
    _("status").innerHTML = `
      <p style='font-size: 14px; color: red;'>
        Type in your email address
      </p>
    `;
    } else {
      _("forgotpassbtn").style.display = "none";
      _("status").innerHTML = '<img src="/images/rolling.gif" width="30" height="30">';
      var s = ajaxObj("POST", "forgot_pass.php");
      s.onreadystatechange = function() {
        if (ajaxReturn(s)) {
          var response = s.responseText;
          _("forgotpassbtn").style.display = "block";
          if (response == "success") {
            _("loginform").innerHTML = `
              <p class="gothamNormal align" style="color: red;"">
                Check your email inbox in a few minutes
              </p>
              <p class="align">
                You can close this window or tab if you like, every detail will be readable
                in the email.
              </p>
            `;
          } else if (response == "no_exists") {
            _("status").innerHTML = `
              <p style='font-size: 14px; color: red;'>
                Sorry that email address is not in our system
              </p>
            `;
          } else if (response == "email_send_failed") {
            _("status").innerHTML = `
            <p style='font-size: 14px; color: red;'>
              Unfortunately we could not send your email
            </p>
          `;
          } else {
            _("status").innerHTML = `<p style='font-size: 14px; color: red;'>${response}</p>`;
          }
        }
      }
    }
  s.send("e=" + e);
}
