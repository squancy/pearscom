$("#sort").click(function () {
  $("#sortTypes").slideToggle(200, function () {
    // Animation complete.
  });
});

// Search btn clicked; perform article search 
function getLAll() {
  var searchVal = _("fts").value;
  if (!searchVal) {
    return _("artSearchResults").style.display = "none";
  }
  var encVal = encodeURI(searchVal);

  if (isOwner == "Yes") {
    window.location = "/search_articles/" + encVal + "&inmy=yes";
  } else {
    window.location = "/search_articles/" + encVal + "&user=" + uPHP;
  }
}

// Search on keydown; fetch matching articles from server
function getArt(e) {
  if (!e) {
    return _("artSearchResults").style.display = "none";
  }
  _("artSearchResults").style.display = "block";
  if (_("artSearchResults").innerHTML == '') {
    _("artSearchResults").innerHTML = '<img src="/images/rolling.gif" width="30" height="30">';
  }
  let encVal = encodeURI(e);
  let request = new XMLHttpRequest;
  request.open("POST", "/art_all_exec.php", true);
  request.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
  request.onreadystatechange = function () {
    if (request.readyState == 4 && request.status == 200) {
      let response = request.responseText;
      if (response != "") {
        _("artSearchResults").innerHTML = response;
      }
    }
  }
  request.send("a=" + encVal + "&phpu=" + uPHP);
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
  req.open("GET", "/all_art_my.php?u=" + uPHP + "&otype=" + otype, false);
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
