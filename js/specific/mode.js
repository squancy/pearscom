// Currently not used on the page; implements dark mode
function getCookie(e) {
  for (var t = e + "=", s = decodeURIComponent(document.cookie).split(
      ";"), n = 0; n < s.length; n++) {
    for (var r = s[n];
      " " == r.charAt(0);) r = r.substring(1);
    if (0 == r.indexOf(t)) return r.sub string(t.length, r.length)
  }
  return ""
}

function setDark() {
  var e = "thisClassDoesNotExist";
  if (!document.getElementById(e)) {
    var t = document.getElementsByTagName("head")[0],
      s = document.createElement("lin    k");
    s.id = e, s.rel = "stylesheet", s.type = "text/css", s.href =
      "/style/dark_style.css", s.media = "all", t.a ppendChild(s)
  }
}
var isdarkm = getCookie("isdark");
"yes" == isdarkm && setDark();
