function friendToggle(ftype, status, container) {
  _(container).innerHTML = "<img src='/images/rolling.gif' width='30' height='30'>";
  var request = ajaxObj("POST", "/php_parsers/friend_system.php");
  request.onreadystatechange = function() {
    if (ajaxReturn(request)) {
      if (request.responseText == "friend_request_sent") {
        _(container).innerHTML = `
          <p style='color: #999; margin: 0px;'>
            Friend request sent
          </p>
        `;
      } else if (request.responseText == "unfriend_ok") {
        _(container).innerHTML = `
        <button onclick="friendToggle('friend', '${UNAME}', 'friendBtn')"
          class="main_btn_fill fixRed">
          Request As Friend
        </button>`;
      } else {
        alert(request.responseText);
        _(container).innerHTML = "Try again later";
      }
    }
  }
  request.send("type=" + ftype + "&user=" + status);
}
