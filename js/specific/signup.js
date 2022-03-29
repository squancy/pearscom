function getLocation() {
  var e = _("coords");
  _("mapholder").innerHTML = '<img src="/images/rolling.gif" width="30" height="30">';
  _("vupload").style.display = "none";
  if (navigator.geolocation) {
    navigator.geolocation.getCurrentPosition(showPosition, showError);
  } else {
    e.innerHTML = "<p>Geolocation is not supported by this browser</p>";
    _("mapholder").innerHTML = "";
    _("vupload").style.display = "block";
  }
}

function showPosition(e) {
  var n = e.coords.latitude,
      t = e.coords.longitude,
      o = n + "," + t;
  _("lat").innerHTML = n;
  _("lon").innerHTML = t;
  var a = "https://maps.googleapis.com/maps/api/staticmap?center=" + o +
    "&zoom=14&size=400x300&key=AIzaSyCr5_w0vZzk39VbnJ8GWZcoZycl_gvr5w8";
  _("mapholder").innerHTML = "<img src='" + a + "' id='googmh'>";
}

function showError(e) {
  var n = _("geo_err");
  switch (e.code) {
    case e.PERMISSION_DENIED:
      n.innerHTML = "<p>User denied the request for Geolocation</p>";
      break;
    case e.POSITION_UNAVAILABLE:
      n.innerHTML = "<p>Location information is unavailable</p>";
      break;
    case e.TIMEOUT:
      n.innerHTML = "<p>The request to get user location timed ou</p>.";
      break;
    case e.UNKNOWN_ERROR:
      n.innerHTML = "<p>An unknown error occurred</p>"
  }
}

function signup() {
    var e = _("username").value;
    e = encodeURI(e);
    var n = _("email").value,
        t = _("pass1").value,
        o = _("pass2").value,
        a = _("country").value,
        i = _("gender").value,
        s = _("status"),
        r = _("birthday").value,
        c = _("lat").innerHTML,
        u = _("lon").innerHTML,
        d = _("timezone").value,
        p = t.match(/[a-z]/) ? 1 : 0,
        l = t.match(/[A-Z]/) ? 1 : 0,
        h = t.match(/[0-9]/) ? 1 : 0;
    if ("" == e || "" == n || "" == t || "" == o || "" == a || "" == i || "" == r ||
      "not located yet" == c || "not located yet" == u || "" == c || "" == u || "" == d) {
      s.innerHTML = "<p>Fill out all of the form data and accept geolocation</p>";
      return false;
    } else if (t != o) {
      s.innerHTML = "<p>Your password fields do not match</p>";
      return false;
    } else if (p && l && h) {
      _("signupbtn").disabled = true;
      s.innerHTML = '<img src="/images/rolling.gif" width="30" height="30">';
      var m = ajaxObj("POST", "signup.php");
      m.onreadystatechange = function() {
        if (ajaxReturn(m)) {
          if (m.responseText != "signup_success") {
            s.innerHTML = m.responseText;
            _("signupbtn").style.display = "block";
          } else {
            window.scrollTo(0, 0);
            _('sutxt').style.display = 'none';
            _("loginform").innerHTML = `
              <p class="font24 gothamNormal" style="margin-top: 75px;">
                Good job, ${decodeURIComponent(e)}!
              </p>
              <p class="lh">
                You have done the signing up part, however you have to activate your
                account in order to be able to log in. We have sent an account activation
                email to <span style='color: red;'>${n}</span>
                (please note that this might take some time).
              </p>
            `;
        }
      } else {
        s.innerHTML = "<p>Password requires at least 1 uppercase, 1 lowercase and 1 number</p>";
      }
    }
  }
  m.send("u=" + e + "&e=" + n + "&p=" + t + "&c=" + a + "&g=" + i + "&bd=" + r +
    "&lat=" + c + "&lon=" + u + "&tz=" + d);
}
