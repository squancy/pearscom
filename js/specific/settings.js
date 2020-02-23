function emptyElement(e) {
    _(e).innerHTML = ""
}

function clientFbOne(e, n, sText, serverSide = 'settings.php') {
  var a = _(n).value;
  if (a != "") {
    var t = ajaxObj("POST", serverSide);
    t.onreadystatechange = function () {
      if (ajaxReturn(t)) {
        _(e).innerHTML = t.responseText;
      }
    }
    t.send(sText + "=" + a);
  }
}

function checkFbTwo(input1, input2, stat, send1, send2, serverSide = 'settings.php') {
  var e = _(input1).value,
      n = _(input2).value;
  if (e != "") {
    var a = ajaxObj("POST", serverSide);
    a.onreadystatechange = function () {
      if (ajaxReturn(a)) {
        _(stat).innerHTML = a.responseText;
      }
    }
    a.send(send1 + "=" + e + "&" + send2 + "=" + n);
  }
}

function changePass() {
  var e = _("cpass").value,
      n = _("npass").value,
      a = _("cnpass").value,
      t = _("pass_status"),
      s = n.match(/[a-z]/) ? 1 : 0,
      o = n.match(/[A-Z]/) ? 1 : 0,
      i = n.match(/[0-9]/) ? 1 : 0;
  if (e == "" || n == "" || a == "") {
    t.innerHTML = "Fill out all the form data";
  } else if (n != a) {
    t.innerHTML = "Your new password fileds do not match";
  } else if (s && o && i) {
    _("confirmpass").style.display = "none";
    t.innerHTML = '<img src="/images/rolling.gif" width="30" height="30">';

    var c = ajaxObj("POST", "/settings");
    c.onreadystatechange = function () {
      if (ajaxReturn(c)) {
        if (c.responseText != "cpass_success") {
          t.innerHTML = c.responseText;
          _("confirmpass").style.display = "block";
        } else {
          t.innerHTML = 'You have successfully changed your password!';
          window.location.href = "/login";
        }
      }
    }
  } else {
    t.innerHTML = "Password requires at least 1 uppercase, 1 lowercase, and 1 number";
  }
  c.send("curp=" + e + "&newp=" + n + "&cnewp=" + a)
}

function confirmChange(input1, input2, input3, box, msg, fmsg, send1, send2) {
  var e = _(input1).value,
      n = _(input2).value,
      a = _(input3);
  if (e == "" || n == "") {
    a.innerHTML = "Fill out all the form data";
  } else {
    _(box).style.display = "none";
    a.innerHTML = '<img src="/images/rolling.gif" width="30" height="30">';
    var t = ajaxObj("POST", "/settings");
    t.onreadystatechange = function () {
      if (input3 == 'delete_status') {
        if (!confirm("Are you sure you want to delete your account?")) {
          return false;
        }
      }
      if (ajaxReturn(t)) {
        if (t.responseText != msg) {
          a.innerHTML = t.responseText;
          _(box).style.display = "block";
        } else {
          if (fmsg != null) {
            _(fmsg).innerHTML = 'Changes applied successfully';
          } else if (input3 != 'delete_status') {
            window.location.href = '/login'; 
          } else {
            window.location.href = '/logout'; 
          }
        }
      }
    }
  }
  t.send(send1 + "=" + e + "&" + send2 + "=" + n)
}
