function sendBdTo(e) {
  var o = _("hbtuta").value,
      t = "bd_wish";
  if (o == "") {
    prepareDialog();
    _("dialogbox").innerHTML = `
      <p style="font-size: 18px; margin: 0px;">Blank post</p>
      <p>
        In order to successfully post the birthday wishes to ${e} you have to type in
        something first.
      </p>
      <br />
      <button id="vupload" style="position: absolute; right: 3px; bottom: 3px;"
        onclick="closeDialog()">Close</button>
    `;
    return false;
  }

  var s = ajaxObj("POST", "/php_parsers/status_system.php");
  s.onreadystatechange = function () {
    if (ajaxReturn(s)) {
      if (s.responseText == "bdsent_ok") {
        _("bdstattos").innerHTML = `
          <p style='font-size: 12px; color: #999; margin-bottom: 0;'>
            Birthday message sent!
          </p>
        `;
        document.getElementsByClassName("bdsendtof").value = "";
      } else {
        genErrorDialog();
        _("hbtuta").value = "";
      }
    }
  }
  s.send("action=" + t + "&data=" + o + "&bduser=" + e + "&type=" + t);
}

function getNames(e) {
  if (e == "") {
    _("memSearchResults").style.display = "none";
    return false;
  }

  var x = encodeURI(e),
      t = new XMLHttpRequest;
  t.open("POST", "/search_exec.php", false);
  t.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
  t.onreadystatechange = function () {
    if (t.readyState == 4 && t.status == 200) {
      var e = t.responseText;
      if (e != "") {
        _("memSearchResults").style.display = "block";
        _("memSearchResults").innerHTML = e;
      }
    }
  }
  t.send("u=" + x);
}

// TODO: deobfuscate the following code
var mobilecheck = mobilecheck();
var now = new Date,
    hrs = now.getHours(),
    loguname = TOP_UNAME;
if (loguname != '') {
    ("https://www.pearscom.com/#" && "https://www.pearscom.com/") != (window.location.href ||
      document.URL) && (window.addEventListener("mouseup", function (e) {
        
    }), 0 == mobilecheck && window.addEventListener("mouseup", function (e) {
        var t = _("cp"),
            o = _("user_template_img");
        e.target != t && e.target.parentNode != t && e.target != o && e.target.parentNode != o
        && (t.style.width = "0", t.style.right = "-30px",
        document.body.style.overflowY = "auto")
    }));

    var w = window,
        d = document,
        e = d.documentElement,
        g = d.getElementsByTagName("body")[0],
        x = w.innerWidth || e.clientWidth || g.clientWidth,
        y = w.innerHeight || e.clientHeight || g.clientHeight,
        bool = true;
    "https://www.pearscom.com/" == (window.location.href || document.URL) && x >= 808 &&
      (bool = !1)
}

function toggleCP() {
  if (bool) {
    cp.offsetWidth;
    if (300 == cp.offsetWidth || "100%" == cp.style.width){
      cp.style.width = "0";
      cp.style.right = "-10px";
      document.body.style.overflowY = "auto";
    } else {
      if (!mobilecheck) {
        cp.style.width = '300px';
      } else {
        cp.style.width = '100%';
      }
      cp.style.right = "0";
      document.body.style.overflowY = "hidden";
    }

    if(apiPres){
      cp.style.top = '103px';
    }
  }
}

function toggleMenu(e) {
  if (_(e).style.display == 'block') {
    e.style.display = "none";
    if (mobilecheck) {
      document.body.style.overflow = "hidden";
    }
  } else {
    e.style.display = "block"; 
    if (mobilecheck) {
      document.body.style.overflow = "hidden";
    }
  }
}

function toggleDD(){
  if(document.getElementsByClassName("tddc")[0].style.display == "block"){
    document.getElementsByClassName("tddc")[0].style.display = "none";
  }else{
    document.getElementsByClassName("tddc")[0].style.display = "block";
  }
}

_("sico").addEventListener("click", function showSearch(e){
  document.getElementsByClassName("nineteen")[0].style.display = "none";
  _("dpm3").style.display = "none";
  _("user_template_img").style.display = "none";
  _("search_align").style.display = "block";
  if(window.innerWidth >= 328){
    _("search_align").style.width = "60%";
  }else{
    _("search_align").style.width = "50%";
  }
  _("s_dont").style.display = "block";
  _("icons_align").style.width = "40px";
  _("sico").style.display = "none";
  _("sback").style.display = "block";
  let x = document.getElementsByClassName("supStyle");
  for(let c of Array.from(x)){
    c.style.display = "none";
  }
});
  
let deferredPrompt;
let apiPres = false;
window.addEventListener('beforeinstallprompt', function(e){
  if(localStorage.getItem('expire') <= new Date().getTime()){
    deferredPrompt = e;
    apiPres = true;
    let refNode = _('cp');
    let newNode = document.createElement('div');
    let parentNode = document.getElementsByTagName('header')[0];
    let cp = _('cp');
    newNode.id = 'installProg';
    newNode.innerHTML = `
      <button class="main_btn main_btn_fill" id="installBtn" onclick="installAPI()">
        Install Pearscom
      </button>
      <div>
        <img src="/images/cins.png" onclick="closeAPI()">
      </div>
    `;
    parentNode.insertBefore(newNode, refNode);
    if(_('cp') != null){
      if(parseInt(_('cp').offsetWidth, 10) > 0){
        _('cp').style.top = '103px';
      }
    }

    if(window.location.pathname == '/'){
      if(_('newsfeed') != null) _('newsfeed').style.marginTop = '50px';
      if(_('pearHolder') != null) _('pearHolder').style.marginTop = '100px';
      if(_('startContent') != null) _('startContent').style.marginTop = '120px';
      if(_('changingWords') != null) _('changingWords').style.height = 'calc(50% - 20px)';
    }else{
      _('pageMiddle_2').style.marginTop = '120px';
    }
  }
});
  
function closeAPI(e){
  apiPres = false;
  _('installProg').style.display = 'none';
  let time = new Date().getTime() + 86400 * 1000;
  localStorage.setItem('expire', time);
  if(window.location.pathname == '/'){
    _('newsfeed').style.marginTop = '0px';
  }else{
    _('pageMiddle_2').style.marginTop = '60px';
  }
  _('cp').style.top = '51px';
}
  
function installAPI(e){
  deferredPrompt.prompt();

  // Wait for the user to respond to the prompt
  deferredPrompt.userChoice
    .then((choiceResult) => {
    if (choiceResult.outcome === 'accepted') {
      newNode.innerHTML = '<p>Congratulations! Pearscom has been successfully installed.</p>';
      closeAPI();
    }
    deferredPrompt = null;
  });
}

_("sback").addEventListener("click", function back(e){
  document.getElementsByClassName("nineteen")[0].style.display = "inline-block";
  _("dpm3").style.display = "block";
  _("user_template_img").style.display = "block";
  _("search_align").style.display = "none";
  _("s_dont").style.display = "none";
  _("icons_align").style.width = "80%";
  _("sico").style.display = "block";
  _("sback").style.display = "none";
  let x = document.getElementsByClassName("supStyle");
  for(let c of Array.from(x)){
      c.style.display = "inline-block";
  }
});

