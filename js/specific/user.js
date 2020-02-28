/*
  TODO: merge toggle funcs
*/
let editArray = ["edu", "pro_", "city_", "me", "con", "geoLoc"];

function loopDisplay(val, o) {
  if(o != "geoLoc"){
    _("appendLoc").style.display = val;
    _("geolocBtn").style.display = val;
  }
  for (let j = 0; j < editArray.length; j++) {
    if (editArray[j] != o) {
      _(editArray[j]).style.display = val;
    }
  }
}

function openDD(el) {
  if (_(el).style.display == "flex") {
    _(el).style.display = "none";
    if(el != "geoLoc") {
      _("editbtn").style.display = "none";
    } else if (el == "geoLoc"){ 
      _("geolocBtn").style.display = "none";
      _("editbtn").style.display = "flex";
    }
    _("showHr").style.display = "none";
    if (el == "geoLoc") {
      _("appendLoc").style.display = "none";
    }
  } else {
    _(el).style.display = "flex";
    if (el != "geoLoc") {
      _("editbtn").style.display = "flex";
    } else if(el == "geoLoc"){ 
      _("geolocBtn").style.display = "flex";
      _("editbtn").style.display = "none";
    }
    _("showHr").style.display = "block";
    if(el == "geoLoc") {
      _("appendLoc").style.display = "block";
    }
    loopDisplay("none", el);
  }
}

function hgoArt() {
  window.location = "/user/" + luname + "&wart=yes";
}

function openPP(uri, treeish) {
  if ("avdef.png" == uri) {
    prepareDialog();
    _("dialogbox_art").innerHTML = `
      <img src="/images/avdef.png" width="100%">
      <button id="vupload" style="float: right; margin: 3px;" onclick="closeDialog_a()">
        Close
      </button>
    `;
  } else {
    _("dialogbox_art").innerHTML = `
      <img src="/user/${treeish}/${uri}" width="100%">
      <button id="vupload" style="float: right; margin: 3px;" onclick="closeDialog_a()">
        Close
      </button>
    `;
  }
}

var mobilecheck = mobilecheck();

function appArt(name) {
  _("artnum_" + name).style.display = 0 == mobilecheck ? "inline-block" : "none";
}

function disArt(name) {
  _("artnum_" + name).style.display = "none";
}

function appPho(name) {
  _("phonum_" + name).style.display = 0 == mobilecheck ? "inline-block" : "none";
}

function disPho(name) {
  _("phonum_" + name).style.display = "none";
}

function openURL(url) {
  window.location = url;
}

function blockToggle(_wid_attr, data, template) {
  var template = document.getElementById(template);
  template.innerHTML = '<img src="/images/rolling.gif" width="30" height="30">';
  var xhr = ajaxObj("POST", "/php_parsers/block_system.php");
  xhr.onreadystatechange = function() {
    if (ajaxReturn(xhr)) {
      if ("blocked_ok" == xhr.responseText) {
        template.innerHTML = `
          <button onclick="blockToggle('unblock', '${PUNAME}', 'blockBtn')"
            class='main_btn_fill fixRed'>Unblock User</button>`;
      } else {
        if ("unblocked_ok" == xhr.responseText) {
          template.innerHTML = `
            <button onclick="blockToggle('block', '${PUNAME}', 'blockBtn')"
              class='main_btn_fill fixRed'>Block User</button>`;
        } else {
          genErrorDialog();
          template.innerHTML = `
            <p style='font-size: 14px; margin: 0; color: #999;'>Try again later!</p>`;
        }
      }
    }
  }
  xhr.send("type=" + _wid_attr + "&blockee=" + data);
}

function showfile() {
  var e = _("bfile").value;
  e.substr(12);
  _("sel_f").innerHTML = "&nbsp;" + e.substr(12);
}

function emptyElement(id) {
  _(id).innerHTML = "";
}

function editChanges() {
  var code = _("status");
  var c = _("job").value;
  var name = _("elemen").value;
  var loca2 = _("high").value;
  var content = _("uni").value;
  var link = _("politics").value;
  var email = _("religion").value;
  var val = _("language").value;
  var node = _("nd_day").value;
  var styles = _("nd_month").value;
  var scalar = _("interest").value;
  var mask = _("notemail").value;
  var h = _("website").value;
  var key = _("address").value;
  var str = _("degree").value;
  var cp = _("ta").value;
  var result = _("profession_sel").value;
  var value = _("city").value;
  var input = _("state").value;
  var keyword = _("mobile").value;
  var text = _("hometown").value;
  var currentValue = _("movies").value;
  var line = _("music").value;
  var hostname = _("pstatus").value;
  var username = _("quotes").value;
  if (!c && !cp && !result && !value && !input && !keyword && !text && !currentValue &&
    !hostname && !line && !name && !loca2 && !content && !link && !email && !val && !node &&
    !styles && !scalar && !mask && !h && !key && !str && !username) {
    code.innerHTML = "Please fill in at least 1 field";
    return false;
  } else {
    _("editbtn").style.display = "none";
    code.innerHTML = '<img src="/images/rolling.gif" width="30" height="30">';
    var xhr = ajaxObj("POST", "/php_parsers/edit_parser.php");
    xhr.onreadystatechange = function() {
      if (ajaxReturn(xhr)) {
        if ("edit_success" != xhr.responseText) {
          code.innerHTML = xhr.responseText;
          _("editbtn").style.display = "block";
        } else {
          code.innerHTML = "";
          _("after_status").innerHTML = `
            <p style='color: #999; text-align: center;'>
              Your changes has been saved successfully
            </p>
          `;
        }
      }
    }
    xhr.send("job=" + c + "&ta=" + cp + "&pro=" + result + "&city=" + value + "&state=" + input
      + "&mobile=" + keyword + "&hometown=" + text + "&fmovie=" + currentValue + "&fmusic=" +
      line + "&pstatus=" + hostname + "&elemen=" + name + "&high=" + loca2 + "&uni=" + content
      + "&politics=" + link + "&religion=" + email + "&language=" + val + "&nd_day=" + node +
      "&nd_month=" + styles + "&interest=" + scalar + "&notemail=" + mask + "&website=" + h +
      "&address=" + key + "&degree=" + str + "&quotes=" + username);
  }
}

function writeArticle() {
  var cancel = _("article_show");
  var header = _("writearticle");
  var input = _("art_btn");
  var tmp = _("hide_it");
  var code = _("userNavbar");
  var t = _("slide1");
  var line = _("slide2");
  if ("block" == cancel.style.display) {
    tmp.style.display = "none";
    cancel.style.display = "block";
    header.style.display = "block";
    input.style.display = "block";
    input.style.opacity = "0.9";
    code.style.display = "block";
    _("menuVer").style.display = "flex";
  } else {
    cancel.style.display = "none";
    header.style.display = "block";
    tmp.style.display = "none";
    code.style.display = "none";
    t.style.display = "none";
    line.style.display = "none";
    _("menuVer").style.display = "none";
    window.scrollTo(0, 0);
  }
}

var _0xc754 = ["use strict"];

function saveArticle() {
  var line = _("writearticle");
  var email = _("title").value;
  var message = _("status_art");
  var name = _("keywords").value;
  var cp = _("art_cat").value;
  line.elements.myTextArea.value = window.frames.richTextField.document.body.innerHTML;
  var username = line.elements.myTextArea.value;
  if (username = encodeURIComponent(username), !email || !username || !name || !cp) {
    message.innerHTML = '<p class="error_red">Please fill in all fields!</p>';
    return false;
  } else {
    _("article_btn").style.display = "none";
    message.innerHTML = '<img src="/images/rolling.gif" width="30" height="30">';
    var xhr = ajaxObj("POST", "/php_parsers/article_parser.php");
    xhr.onreadystatechange = function() {
      if (ajaxReturn(xhr)) {
        var appfieldvals = xhr.responseText.split("|");
        appfieldvals[2];
        appfieldvals[3];
        if ("article_success" != appfieldvals[0]) {
          message.innerHTML = `
            <p style='color: red;'>
              Unfortunately, an unknown error has occured. Please try again later.
            </p>
          `;
          _("article_btn").style.display = "block";
        } else {
          window.location = "/articles/" + appfieldvals[3] + "/" + appfieldvals[2];
        }
      }
    }
  }
  xhr.send("title=" + email + "&area=" + encodeURIComponent(username) + "&tags=" + name +
    "&cat=" + cp + "&img1="+hasImageGen1+"&img2="+hasImageGen2+"&img3="+hasImageGen3+"&img4="+
    hasImageGen4+"&img5="+hasImageGen5);
}

function followToggle(ntests, i, name, holder) {
  _(name).innerHTML = '<img src="/images/rolling.gif" width="30" height="30">';
  var xhr = ajaxObj("POST", "/php_parsers/follow_system.php");
  xhr.onreadystatechange = function() {
    if (ajaxReturn(xhr)) {
      if ("follow_success" == xhr.responseText) {
        _(name).innerHTML = `
          <button class='main_btn_fill fixRed' onclick=\"followToggle('unfollow','${PUNAME}',
            'followBtn', 'isFol')\">Unfollow</button>`;
        _(holder).innerHTML = "You are following " + PUNAME;
      } else {
        if ("unfollow_success" == xhr.responseText) {
          _(name).innerHTML = `
            <button class='main_btn_fill fixRed' onclick="followToggle('follow', '${PUNAME}',
              'followBtn', 'isFol')">Follow</button>`;
          _(holder).innerHTML = "You are not a following anymore";
        } else {
          genErrorDialog();
          _(name).innerHTML = `
            <p style="font-size: 14px; color: #999; margin: 0;">Try again later</p>
          `;
        }
      }
    }
  }
  xhr.send("type=" + ntests + "&user=" + i);
}

var vid;
var playbtn;
var seekslider;
var curtimetext;
var durtimetext;
var mutebtn;
var volumeslider;
var fullscrbtn;
var showingSourceCode = false;
var isInEditMode = false;

function enableEditMode() {
  richTextField.document.designMode = "On";
}

function uploadBiBg(type) {
  var message = _("statusbig");
  var request = (type = type, ajaxObj("POST", "/php_parsers/photo_system.php"));
  request.onreadystatechange = function() {
    if (ajaxReturn(request)) {
      if ("bibg_success" == request.responseText) {
        message.innerHTML = `
          <p class="success_green">
            You have successfully changed your background to ${type}
          </p>
        `;
        location.reload();
        window.scrollTo(0, 0);
      } else {
        genErrorDialog();
        message.innerHTML = '<p class="error_red">Try again later</p>';
      }
    }
  }
  request.send("imgtype=" + type);
}

function showBiBg() {
  var cancel = _("statusbig");
  if ("none" == cancel.style.display) {
    cancel.style.display = "block";
  } else {
    cancel.style.display = "none";
  }
}

function articleGuide(canCreateDiscussions) {
  prepareDialog();
  _("dialogbox").innerHTML = `
    <p style="font-size: 18px; margin: 0px;">
      Simple guide how to write an article
    </p>
    <img src="/images/' + canCreateDiscussions + '"><br />
    <button id="vupload" style="position: absolute; right: 3px; bottom: 3px;"
      onclick="closeDialog()">Close</button>`;
}

function triggerUpload(event, file) {
  event.preventDefault();
  _(file).click();
}

// TODO: beautify the following bunch of JS code
function getLocation() {
  var internalFoldl = _("update_coords");
  if (navigator.geolocation) {
    navigator.geolocation.getCurrentPosition(showPosition, showError);
    _("mapholder_update").innerHTML = '<img src="/images/rolling.gif" width="30" height="30">';
  } else {
    internalFoldl.innerHTML = `
      <p style='font-size: 14px;'>Geolocation is not supported by this browser.</p>`;
  }
}

function showPosition(position) {
  var latitude = position.coords.latitude;
  var longitude = position.coords.longitude;
  position.coords.latitude;
  position.coords.longitude;
  _("lat_update").innerHTML = latitude;
  _("lon_update").innerHTML = longitude;
  position.coords.latitude;
  position.coords.longitude;
  _("mapholder_update").innerHTML = `<img
    src='https://maps.googleapis.com/maps/api/staticmap?center=${position.coords.latitude},
    ${position.coords.longitude}
    &zoom=14&size=300x200&key=AIzaSyCr5_w0vZzk39VbnJ8GWZcoZycl_gvr5w8' id='ugoogimg'>`;
}

function showError(error) {
  let x = _('update_coords');
  switch(error.code) {
    case error.PERMISSION_DENIED:
      x.innerHTML = "User denied the request for Geolocation."
      break;
    case error.POSITION_UNAVAILABLE:
      x.innerHTML = "Location information is unavailable."
      break;
    case error.TIMEOUT:
      x.innerHTML = "The request to get user location timed out."
      break;
    case error.UNKNOWN_ERROR:
      x.innerHTML = "An unknown error occurred."
      break;
  }
}

function saveNewGeoLoc() {
  var url = _("lat_update").innerHTML;
  var anchorPart = _("lon_update").innerHTML;
  var t = _("update_coords");
  if (!url || "not set yet" == url || !anchorPart || "not set yet" == anchorPart) {
    t.innerHTML = `
      <p style='color: #999; text-align: center;'>Your longitude and latitude is missing.</p>`;
  } else {
    var result = ajaxObj("POST", "/php_parsers/geo_usr_parser.php");
    result.onreadystatechange = function() {
      if (ajaxReturn(result)) {
        if ("update_geo_success" == result.responseText) {
          t.innerHTML = `
            <p style='color: #999; text-align: center;'>
              You have successfully changed your location! Now you can refresh the page.
            </p>
          `;
        } else {
          t.innerHTML = `
            <p style='color: #999; text-align: center;'>${result.responseText}</p>
          `;
        }
      } else {
        t.innerHTML = "<p style='color: #999; text-align: center;'>An error occurred</p>";
      }
    }
  }
  result.send("updateLat=" + url + "&updateLon=" + anchorPart);
}

if (_("hide_it").style.display == "none") {
  window.onbeforeunload = function() {
    line.elements.myTextArea.value = window.frames.richTextField.document.body.innerHTML;
    let iframeVal = line.elements.myTextArea.value;

    if (!_("title").value || !hasImageGen1 || !hasImageGen2 || !hasImageGen3 || !hasImageGen4
      || !hasImageGen5 || !_("keywords").value || !iframeVal || !_("art_cat").value) {
      return "You have unsaved changes left. If you leave the page without saving your" +
        "article, it will be lost!";
    }
  }
}

let pmsub = _("pmsubject");
let pmtxt = _("pmtext");
if ((pmsub && pmtxt) != undefined) {
  window.onbeforeunload = function() {
    if (!_("pmsubject").value || !_("pmtext").value) {
      return "You have unsaved changes left. If you leave the page without saving your" + 
        "private message, it will be lost!";
    }
  }
}

w = window;
var d = document;
var e = d.documentElement;
var g = d.getElementsByTagName("body")[0];
var y = (x = w.innerWidth || e.clientWidth || g.clientWidth, w.innerHeight || e.clientHeight
  || g.clientHeight);
var h = (h = window.innerHeight) / 2.35;

function showForm() {
  if ("block" == _("pmform").style.display) {
    _("pmform").style.display = "none";
  } else {
    _("pmform").style.display = "block";
    if (0 == mobilecheck) {
      _("pmform").style.height = h + "px";
    } else {
      _("pmform").style.height = y + "px";
    }
  }
}

function closePM() {
  _("pmform").style.display = "none";
}

y = y / 2;
let menuArray = ["userFriends", "userPhotos", "userArticles", "userInfo", "userVideos", "userGroups", "userFollowers"];
let contentArray = ["friendsAbout", "photosAbout", "articlesAbout", "aboutInfo", "videosAbout", "grsAbout", "flsAbout"];

if (_("userBackground") != undefined) {
  menuArray.push("userBackground");
  contentArray.push("bcgAbout");
}

if (_("userPm") != undefined) {
  menuArray.push("userPm");
}

if (_("userEdit") != undefined) {
  menuArray.push("userEdit");
  contentArray.push("editAbout");
}

let ui = _("userInfo");
ui.style.borderBottom = "2px solid #999";
for (let i = 0; i < menuArray.length; i++) {
  let openLoginScreenBtn = _(menuArray[i]);
  let THREAD_STARTED = menuArray[i];
  openLoginScreenBtn.addEventListener("click", function(canCreateDiscussions) {
    ufHandler(THREAD_STARTED);
  });
}

function addToggle(id) {
  $("#" + id).on("click", function() {
    $(this).next().slideToggle("fast");
  });  
}

addToggle("infodd");
addToggle("imgdd");
addToggle("artdd");
addToggle("vhelp");
addToggle("bgdd");

if(window.innerWidth <= 500){
  addToggle("whatAre");
  addToggle("guideArt");
}

function ufHandler(type) {
  let box = "";
  if (type == "userFriends") {
    box = "friendsAbout";
  } else if (type == "userPhotos") {
      box = "photosAbout";
  } else if (type == "userArticles") {
      box = "articlesAbout";
  } else if (type == "userInfo") {
      box = "aboutInfo";
  } else if (type == "userVideos") {
    box = "videosAbout";
  } else if (type == "userGroups") {
    box = "grsAbout";
  } else if (type == "userBackground") {
    box = "bcgAbout";
  } else if (type == "userEdit") {
    box = "editAbout";
  } else if (type == "userFollowers") {
    box = "flsAbout";
  }
  
  if (box != "") {
    _(box).style.display = "block";
  }

  _(type).style.borderBottom = "2px solid #999";
  for (let i = 0; i < menuArray.length; i++) {
    if (menuArray[i] != type) {
      _(menuArray[i]).style.borderBottom = "0px";
    }
  }

  for (let i = 0; i < contentArray.length; i++) {
    if (contentArray[i] != box) {
      _(contentArray[i]).style.display = "none";
    }
  }
}

if (undefined != _("slide2")) {
  var forward = _("slide2");
  forward.onmousedown = function() {
    sideScroll(_("userNavbar"), "right", 15, 220, 20);
  }
}
if (undefined != _("slide1")) {
  var back = _("slide1");
  back.onmousedown = function() {
    sideScroll(_("userNavbar"), "left", 15, 220, 20);
  }
}

function sideScroll(left, right, t, i, delta) {
  let scrollAmount = 0;
  var n = setInterval(function() {
    if ("left" == right) {
      left.scrollLeft -= delta;
    } else {
      left.scrollLeft += delta;
    }
    scrollAmount = scrollAmount + delta;
    if (scrollAmount >= i) {
      window.clearInterval(n);
    }
  }, t);
}

let ids = ["genI", "perI", "conI", "eduI", "aboI"];
function changeHeading(id) {
  for (let i = 0; i < ids.length; i++) {
    if (ids[i] == id) {
      _(id).style.borderBottom = "2px solid red";
      _(id).style.color = "#999";
      _(id + "Div").style.display = "flex";
    } else {
      _(ids[i]).style.borderBottom = "none";
      _(ids[i]).style.color = "#000";
      _(ids[i] + "Div").style.display = "none";
    }
  }
}

for (let j = 0; j < ids.length; j++) {
  _(ids[j]).addEventListener("click", function() {
    changeHeading(ids[j]);
  });
}
changeHeading("genI");
