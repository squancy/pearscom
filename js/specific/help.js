/*
  TODO: unminify & descriptive var names
*/

doDD("ccSu", "suDD");
doDD("ccLo", "loDD");
doDD("ccGe", "geDD");
doDD("ccGr", "grDD");
doDD("ccNo", "noDD");
doDD("ccIn", "inDD");
doDD("ccPh", "phDD");
doDD("ccAr", "arDD");
doDD("ccVi", "viDD");
doDD("ccBe", "beDD");
doDD("ccCu", "cuDD");
doDD("ccPp", "ppDD");
doDD("ccBg", "bgDD");
doDD("ccPm", "pmDD");
doDD("ccSe", "seDD");
doDD("ccFo", "foDD");
doDD("ccFl", "flDD");
doDD("ccRe", "reDD");

function emptyElement(e) {
    _(e).innerHTML = ""
}

function sendProb() {
  var e = _("problem_select").value,
      s = _("discuss_problem").value,
      l = _("status");

  if (e == "" || s == "") {
    l.innerHTML = "<span style='color: #999;'>Please fill all the form data</span>";
  } else {
    _("help_submit").style.display = "none";
    l.innerHTML = '<img src="/images/rolling.gif" width="30" height="30">';
    var t = ajaxObj("POST", "/help.php");
    t.onreadystatechange = function() {
      if (ajaxReturn(t)) {
        if (t.responseText != "send_success") {
          l.innerHTML = t.responseText;
          _("help_submit").style.display = "block";
        } else {
          l.innerHTML = `<p style='color: #129c12; text-align: center;'>You have
          successfully send your problem report! We will process and answer it soon!</p>`;
        }
      }
    }
    t.send("p=" + e + "&d=" + s);
  }
}
