var startTime = (new Date).valueOf();
      
function emptyElement(e) {
    _(e).innerHTML = ""
}

/*
  Hinder brute force attacks: after ~ 5 mins request the user to refresh the page and do not
  allow them to log in
*/
function login() {
  var e = (new Date).valueOf();

  // Check time limit
  if (Math.ceil((e - startTime) / 1e3) > 295) {
    _("loginbtn").style.display = "none";
    _("email").style.display = "none";
    _("password").style.display = "none";
    _("status").innerHTML = `
      <strong class='error_red'>
        You have timed out please refresh your browser!
      </strong>
    `;
    return false;
  }

  var n = _("email").value,
      a = _("password").value,
      s = _("rme").value;
  if (n == "" || a == "") {
    _("status").innerHTML = "<i style='font-size: 14px;'>Fill out all of the form data</i>";
    return false;
  } else {
      _("loginbtn").style.display = "none";
      _("status").innerHTML = '<img src="/images/rolling.gif" width="30" height="30">';

      var t = ajaxObj("POST", "/php_parsers/login_parse.php");
      t.onreadystatechange = function() {
        if (ajaxReturn(t)) {
          if (t.responseText == "login_failed") {
            _("status").innerHTML = "Login unsuccessful, please try again.";
            _("loginbtn").style.display = "block";
          } else {
            window.location = "/index";
          }
      }
    }
    t.send("e=" + n + "&p=" + a + "&t=" + TOKEN + "&rme=" + s);
  }
}	

