let uPHP = "<?php echo $u; ?>";

$("#sort").click(function () {
  $("#sortTypes").slideToggle(200, function () {
    // Animation complete.
  });
});


function getLAll() {
  var e = _("fts").value;
  if (e == "") return _("artSearchResults").style.display = "none", !1;
  var a = encodeURI(e);
  window.location = "/search_articles/" + a + "&inmy=yes";
}

function getArt(e) {
  if ("" == e) return _("artSearchResults").style.display = "none", !1;
  _("artSearchResults").style.display = "block", "" == _("artSearchResults").innerHTML && (_("artSearchResults").innerHTML = '<img src="/images/rolling.gif" width="30" height="30">');
  var a = encodeURI(e),
    t = new XMLHttpRequest;
  t.open("POST", "/art_all_exec.php", !0), t.setRequestHeader("Content-type", "application/x-www-form-urlencoded"), t.onreadystatechange = function () {
    if (4 == t.readyState && 200 == t.status) {
      var e = t.responseText;
      "" != e && (_("artSearchResults").innerHTML = e)
    }
  }, t.send("a=" + a + "&phpu=" + uPHP);
}

addListener("date_0", "date_0");
addListener("date_1", "date_1");

for (let i = 0; i < 33; i++) {
  addListener("catgs_" + i, "catgs_" + i);
}

function addListener(onw, w) {
  _(onw).addEventListener("click", function () {
    _("userFlexArts").innerHTML = '<img src="/images/rolling.gif" width="30" height="30">';
    filterArts(w);
  });
}

function filterArts(otype) {
  changeStyle(otype);
  let req = new XMLHttpRequest();
  req.open("GET", "/all_art_my.php?u=<?php echo $u; ?>&otype=" + otype, false);
  req.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
  req.onreadystatechange = function () {
    if (req.readyState == 4 && req.status == 200) {
      _("userFlexArts").innerHTML = req.responseText;
    }
  }
  req.send();
}

function changeStyle(otype) {
  _(otype).style.color = "red";
  for (let i = 0; i < 33; i++) {
    if ("catgs_" + i != otype) _("catgs_" + i).style.color = "black";
  }
  if (otype != "date_0") _("date_0").style.color = "black";
  if (otype != "date_1") _("date_1").style.color = "black";
}

changeStyle("date_0");
