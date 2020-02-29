// Define prop on <video> to check if media is playing or not
Object.defineProperty(HTMLMediaElement.prototype, 'playing', {
  get: function(){
    return !!(this.currentTime > 0 && !this.paused && !this.ended && this.readyState > 2);
  }
});

let cursorOverVideo = false;

// Video description show more/less toggle
let dToggle = true;
function showDes(old, cur){
  if(dToggle){
    _("shDes").innerHTML = old + " <a onclick='showDes(\""+old+"\", \""+cur+"\")'>Show less</a>";
    dToggle = false;
  }else{
    _("shDes").innerHTML = cur + " <a onclick='showDes(\""+old+"\", \""+cur+"\")'>Show more</a>";
    dToggle = true;
  }
}

function toggleStatus(src, status, txt){
  ppbtn.setAttribute("status", status);
  tgl1.src = "/images/" + src + ".svg";
  _("ppToggle").innerText = txt;
}

// Play/pause toggle button
function togglePP(){
  if(ppbtn.getAttribute("status") == "play"){
    video.play();
    toggleStatus("pausebtn", "pause", "Pause (p)");
    controls.style.opacity = "";
  }else{
    video.pause();
    toggleStatus("playbtn", "play", "Play (p)");
    controls.style.opacity = 1;
  }
}

ppbtn.addEventListener("click", togglePP);

// Update video time on the seek bar
video.addEventListener("timeupdate", function update(){
  if(!isDragging){
    _("testl").style.display = "none";

    let jPos = video.currentTime / video.duration;
    og.style.width = jPos * 100 + "%";
    if(video.ended){
      toggleStatus("replaybtn", "play", "Replay (p)");
    }
    _("curtime").innerText = durationConv(Math.round(video.currentTime)) + " /";

    
    video.oncanplay = function(){
      if(!video.paused){
        _("testl").style.display = "block";
      } 
    }
  }
});

function changeTime(e, el){
  let x = mousePosRel(e);
  let percent = x / el.offsetWidth;;
  video.currentTime = percent * video.duration;
}

// Track mouse pos on desktop
function mousePosRel(e, status){
  let rect = e.target.getBoundingClientRect();
  return e.clientX - rect.left;
}

ob.addEventListener("click", function(event) {
  changeTime(event, ob);
});

// Convert duration in secs to a more user-friendly format
function durationConv(dur){
  let minutes = Math.floor(dur / 60);
  let seconds = dur % 60;
  if(minutes >= 10 && seconds >= 10){
    return minutes + ":" + seconds;
  }else if(minutes < 10 && seconds >= 10){
    return "0" + minutes + ":" + seconds;
  }else if(minutes < 10 && seconds < 10){
    return "0" + minutes + ":0" + seconds;
  }else{
    return minutes + ":0" + seconds;
  }
}

// Change seek bar look
function changeBar(isRes){
  if(isRes){
    og.style.height = "3px";
    oj.style.height = "3px";
    ogrey.style.height = "3px";
  }else{
    og.style.height = "5px";
    oj.style.height = "5px";
    ogrey.style.height = "5px";
  }
}

// If mouse is moved inside the video show controls & mouse 
ob.addEventListener("mousemove", function(e){
  let x = mousePosRel(e);
  let time = vDur * (x / this.offsetWidth);
  _("timeInd").innerHTML = durationConv(Math.round(time));
  _("timeInd").style.visibility = "visible";
  _("timeInd").style.opacity = 1;
  if(x >= 25 && x <= this.offsetWidth - 25){
    _("timeInd").style.marginLeft = (x - 25) + "px";
  }
  changeBar(false);
  let wGrey = mousePosRel(e);
  ogrey.style.width = wGrey + "px";
});

// If mouse leaves, hide controls
ob.addEventListener("mouseleave", function(e){
  _("timeInd").style.visibility = "hidden";
  _("timeInd").style.opacity = 0;
  changeBar(true);
    ogrey.style.width = 0;
});

mutebtn.addEventListener("mouseenter", function(){
  _("volSlider").style.width = "70px";
  _("vChange").style.display = "inline-block";
});

_("volCont").addEventListener("mouseleave", function(){
  _("volSlider").style.width = "0px";
  _("vChange").style.display = "none";
});

// Mute/unmute toggle button
function toggleSound(dec){
  if(dec == "sound"){
    mutebtn.setAttribute("status", "nosound");
    tgl2.src = "/images/nomute.svg";
    _("muteToggle").innerText = "Unmute (m)";
  }else{
    tgl2.src = "/images/mutebtn.svg";
    mutebtn.setAttribute("status", "sound");
    _("muteToggle").innerText = "Mute (m)";
  }
}

// Change video sound with a bar
_("vChange").addEventListener("input", function(){
  video.volume = this.value / 100;
  if(video.volume == 0) toggleSound("sound");
  else toggleSound("nosound");
});

function muteUnmute(){
  if(muteBtn.getAttribute("status") == "sound"){
    toggleSound("sound")
    video.volume = 0;
    _("vChange").value = 0;
  }else{
    toggleSound("nosound");
    video.volume = 0.5;
    _("vChange").value = 50;
  }
}

mutebtn.addEventListener("click", muteUnmute);
let showControls = true;
controls.addEventListener("mouseenter", () => showControls = true);
controls.addEventListener("mouseleave", () => showControls = false);
video.addEventListener("mouseenter", () => cursorOverVideo = true);
video.addEventListener("mouseleave", () => cursorOverVideo = false);

function changeStyle(){
  if(showControls != true && !video.paused){
    controls.style.display = "none";
    if (cursorOverVideo) document.body.style.cursor = 'none';
  }
}

let tout = null;
if(vcheck != true){
  video.addEventListener("mousemove", function(){
    controls.style.display = "flex";
    document.body.style.cursor = 'default';
  
    clearTimeout(tout);
    tout = setTimeout(changeStyle, 3000);
  });
}

fs.addEventListener('click', handleFullscreen);

// Cross-browser implementation of fullscreen video
function handleFullscreen() {
  if (isFullScreen()) {
    if (document.exitFullscreen) document.exitFullscreen();
    else if (document.mozCancelFullScreen) document.mozCancelFullScreen();
    else if (document.webkitCancelFullScreen) document.webkitCancelFullScreen();
    else if (document.msExitFullscreen) document.msExitFullscreen();
    setFullscreenData(false);
    videoContainer.style.display = "block";
    document.body.style.cursor = 'default';
    controls.style.bottom = "4px";
    document.querySelector(".vidHolderBig > video").style.maxHeight = "540px";
  } else {
    document.querySelector(".vidHolderBig > video").style.maxHeight = "none";
    if (videoContainer.requestFullscreen) videoContainer.requestFullscreen();
    else if (videoContainer.mozRequestFullScreen) videoContainer.mozRequestFullScreen();
    else if (videoContainer.webkitRequestFullScreen) videoContainer.webkitRequestFullScreen();
    else if (videoContainer.msRequestFullscreen) videoContainer.msRequestFullscreen();
    setFullscreenData(true);
    videoContainer.style.display = "flex";
    controls.style.bottom = "0px";
  }
}

function isFullScreen() {
  return !!(document.fullScreen || document.webkitIsFullScreen || document.mozFullScreen ||
    document.msFullscreenElement || document.fullscreenElement);
}

function setFullscreenData(state) {
  videoContainer.setAttribute('data-fullscreen', !!state);
}

document.addEventListener('fullscreenchange', function(e) {
  setFullscreenData(!!(document.fullScreen || document.fullscreenElement));
});

document.addEventListener('webkitfullscreenchange', function() {
  setFullscreenData(!!document.webkitIsFullScreen);
});

document.addEventListener('mozfullscreenchange', function() {
   setFullscreenData(!!document.mozFullScreen);
});

document.addEventListener('msfullscreenchange', function() {
   setFullscreenData(!!document.msFullscreenElement);
});

// Toggle box for changing video speed
function speedToggle(ismob){
  if(ismob != true || isFullScreen()){
    if(ismob != false){
      _("optionsMenu").style.justifyContent = "unset";
      _("optionsMenu").style.top = "-240px";
      _("optionsMenu").style.width = "auto";
      _("optionsMenu").style.left = "unset";
    }
    if(_("optionsMenu").style.display == "block"){
      _("optionsMenu").style.display = "none";
    }else{
      _("optionsMenu").style.display = "block";
    }
  }else if(ismob != false && !isFullScreen()){
    _("optionsMenu").style.justifyContent = "center";
    _("optionsMenu").style.top = "-80px";
    _("optionsMenu").style.width = "calc(100% - 40px)";
    _("optionsMenu").style.left = "20px";
    _("optionsMenu").style.right = "20px";
      _("optionsMenu").style.flexWrap = "wrap";
    if(_("optionsMenu").style.display == "flex"){
      _("optionsMenu").style.display = "none";
    }else{
      _("optionsMenu").style.display = "flex";
    }
  }
}

function changeSpeed(speed){
  video.playbackRate = speed;
}

// Implement the same functionality with key bindings
if(vcheck != true){
  video.addEventListener("click", togglePP);
  window.addEventListener("keydown", function arrForward(e){
    if(e.keyCode == 39) video.currentTime += 5;
    else if(e.keyCode == 37) video.currentTime -= 5;
  });

  window.addEventListener("keydown", function keyboardSpeed(e){
    if(e.keyCode == 79) speedToggle();
  });

  window.addEventListener("keydown", function keyboardFullS(e){
    if(e.keyCode == 70) handleFullscreen();
  });

  window.addEventListener("keydown", function keyboardMute(e){
    if(e.keyCode == 77) muteUnmute();
  });

  window.addEventListener("keydown", function keyPlayPause(e){
    if(e.keyCode == 80) togglePP();
    if(video.paused) controls.style.opacity = 1;
  });
  _("optionsGears").addEventListener("click", function wrapper(){
    speedToggle(false);
  });

  function dragFunction(e){
    let x = mousePosRel(event);
    og.style.width = x + "px";
  }

  function dragWhile(e, el){
    video.onmousemove = null;
    ob.onmousemove = null;
    isDragging = false;
    changeTime(e, el);
  }

  ob.addEventListener("mousedown", function(e){
    dragFunction(e); 
    isDragging = true;

    video.onmousemove = function(e) {
      dragFunction(e);
      changeBar(false);
    }
    ob.onmousemove = function(e) {
      dragFunction(e);
      changeBar(false);
    }
  });

  ob.addEventListener("mouseup", function dragCaller1(e){
    if(isDragging) dragWhile(e, ob);
  });

  video.addEventListener("mouseup", function dragCaller2(e){
    if(isDragging) dragWhile(e, ob);
    changeBar(true);
  });
}else{
  _("muteToggle").style.display = "none";
  _("ppToggle").style.display = "none";
  _("fsToggle").style.display = "none";
  _("optionsToggle").style.display = "none";
  _("timeInd").style.display = "none";

  video.addEventListener("touchstart", tapHandler);

  let tapedTwice = false;
  let tout;

  function tapHandler(event) {
    if(isFullScreen()){
      if(!tapedTwice) {
        tapedTwice = true;
        setTimeout( function() { tapedTwice = false; }, 300 );
        return false;
      }
      event.preventDefault();
      let cX = event.touches[0].clientX;
      let wWidth = $(window).width();

      let horHalf = wWidth / 2;

      if(cX >= horHalf) video.currentTime += 5;
      else video.currentTime -= 5;
    }
  }

  _("optionsGears").addEventListener("touchstart", function wrapper(){
    speedToggle(true);
  });

  if(isFullScreen()){
     _("volSlider").style.width = "60px";
     _("vChange").style.display = "inline-block";
  }else{
    _("volSlider").style.width = "0px";
  }

  video.addEventListener("touchend", function showHideControls(){
    if(controls.style.display == "flex") controls.style.display = "none";
    else controls.style.display = "flex";
  });
}

function shareVideo(o) {
  var e = ajaxObj("POST", "/php_parsers/status_system.php");
  e.onreadystatechange = function() {
    if (ajaxReturn(e)) {
      if (e.responseText == "share_video_ok") {
        prepareDialog();
        _("dialogbox").innerHTML = `
          <p style="font-size: 18px; margin: 0px;">
            Share this video
          </p>
          <p>
            You have successfully shared this video which will be visible on your main
            profile page in the comment section.
          </p>
          <button id="vupload" style="position: absolute; right: 3px; bottom: 3px;"
            onclick="closeDialog()">Close</button>`;
      } else {
        showErrorDialog();
      }
    }
  }
  e.send("action=share_video&id=" + o);
}

function changeSetts() {
  var o = _("opdiv");
  "inline-block" == o.style.display ? o.style.display = "none" : o.style.display = "inline-block"
}
