function friendReqHandler(e, r, n, s) {
  _(s).innerHTML = '<img src="/images/rolling.gif" width="30" height="30">';
  var t = ajaxObj("POST", "php_parsers/friend_system.php");
  t.onreadystatechange = function() {
    if (ajaxReturn(t)) {
      if (t.responseText == "accept_ok") {
        _(s).innerHTML = "<b>Request Accepted!</b><br />Your are now friends"
      } else if (t.responseText == "reject_ok") {
        _(s).innerHTML = `
          <b>Request Rejected</b><br />
          You chose to reject friendship with this user`;
      } else {
        _(s).innerHTML = t.responseText;
      }
    }
  }
  t.send("action=" + e + "&reqid=" + r + "&user1=" + n);
}
