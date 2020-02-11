/*
  TODO: unmifiy & more descriptive var names
*/

function createGroup() {
  var e = _("status"),
      a = _("grname").value,
      n = _("invite").value,
      r = _("gcat").value;

  if (a == "" || n == "" || r == "") {
    e.innerHTML = 'Please fill in all fields';
    return false
  }

  e.innerHTML = '<img src="/images/rolling.gif" width="30" height="30">';
  var t = ajaxObj("POST", "/php_parsers/group_parser2.php");
  t.onreadystatechange = function () {
    if (ajaxReturn(t)) {
      var e = t.responseText.split("|");
      if ("group_created" == e[0]) {
        var a = e[1];
        window.location = "/group/" + encodeURI(a);
      } else {
        e.innerHTML = 'Unfortunately, an error occurred during the data processing';
      }
    }
  }
  t.send("action=new_group&name=" + a + "&inv=" + n + "&cat=" + r);
}

function checkField(field, send, status) {
  var e = _(field).value;
  if (e != "") {
    var a = ajaxObj("POST", "/php_parsers/group_parser2.php");
    a.onreadystatechange = function () {
      if (ajaxReturn(a)) {
        _(status).innerHTML = a.responseText;
      }
    }
    a.send(send + "=" + e)
  }
}

function getGroups(e) {
  if (e == "") {
    _("grSearchResult").style.display = "none";
    return false;
  }

  _("grSearchResult").style.display = "block";
  if (_("grSearchResult").innerHTML == "") {
    _("grSearchResult").innerHTML = '<img src="/images/rolling.gif" width="30" height="30">';
  }

  var a = encodeURI(e),
      n = new XMLHttpRequest;
  n.open("POST", "/search_exec_group.php", false);
  n.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
  n.onreadystatechange = function () {
    if (n.readyState == 4 && n.status == 200) {
      var e = n.responseText;
      "" != e && (_("grSearchResult").innerHTML = e)
    }
  }
  n.send("g=" + a);
}

function getLSearchGrs() {
  var e = _("searchArt").value;
  if (e == "") {
    _("grSearchResult").style.display = "none";
    return false;
  }
  var a = encodeURI(e);
  window.location = "/search_groups/" + a;
}

$( "#createme" ).click(function() {
  $( "#downdiv" ).slideToggle( 200, function() {
    // Animation complete.
  });
});
