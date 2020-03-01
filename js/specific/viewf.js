function getMyFLArr() {
  var a = _('searchArt').value;
  if (a == '') {
    return false;
  }
  var r = encodeURI(a);
  if(origin == "no") {
    window.location = '/search_friends/' + encodeURI(a) + "&origin=" + VUNAME;
  } else {
    window.location = '/search_friends/' + encodeURI(a);
  }
}

function getFriends(e) {
  if (!e) {
    _("frSearchResult").style.display = "none";
    return false;
  }

  _("frSearchResult").style.display = "block";
  if (!_("frSearchResult").innerHTML) {
    _("frSearchResult").innerHTML = '<img src="/images/rolling.gif" width="30" height="30">';
  }

  var str = encodeURI(e);
  var mypostrequest = new XMLHttpRequest;
  mypostrequest.open("POST", "/searchn_friends.php", true);
  mypostrequest.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
  mypostrequest.onreadystatechange = function() {
    if (mypostrequest.readyState == 4 && mypostrequest.status == 200) {
      var iconValue = mypostrequest.responseText;
      if (iconValue) {
        _("frSearchResult").innerHTML = iconValue;
      }
    }
  }
  mypostrequest.send("u=" + str + "&imp=" + IMP);
}

