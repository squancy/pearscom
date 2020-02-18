/*
  Implement endless news feed for index page & text changing when not logged in
*/

/*
  Endless news feed scrolling: make an AJAX request when reached bottom of page and increase
  the limits in the SQL query on the server side by 6 at every fetch
*/
let lowerLimit = 6;

// Check for new feed load in every 0.5 sec
if (isf) {
  var CheckIfScrollBottom = debouncer(function() {
    if(getDocHeight() < (getScrollXY()[1] + window.innerHeight + 100)) {
      if(isn) {
        _("pcload").innerHTML = `<img src="/images/rolling.gif" width="30" height="30"
          style='display: block; margin: 0 auto; margin-top: 5px; margin-bottom: 5px;'>`;
    
        // Make AJAX req to server
        request = new ajaxObj("POST", "index.php");
        request.onreadystatechange = function() {
          if (ajaxReturn(request)) {
            if (request.responseText != "") {
              _("newsfeed").innerHTML += request.responseText;
              startLazy(true);
              lowerLimit += 6;
            } else {
              _("newsfeed").innerHTML += `
                <p style="font-size: 16px; color: #999; text-align: center;">
                  This is the end of your news feed. Come back later.
                </p>
              `;
              _("pcload").innerHTML = '';
              
              // Do not make superflous requests
              document.removeEventListener('scroll', CheckIfScrollBottom);
            }
          }
        }
        request.send("limit_min=" + lowerLimit);
      }
    }
  }, 500);
  
  document.addEventListener('scroll', CheckIfScrollBottom);
}

/*
  TODO: deobfuscate the following 3 JS functions
*/

// Implement constant checking of page scrolled down
function debouncer(a, b, c) {
  var d;
  return function() {
    var e = this,
        f = arguments,
        g = function() {
            d = null, c || a.apply(e, f)
        },
        h = c && !d;
    clearTimeout(d), d = setTimeout(g, b), h && a.apply(e, f)
  }
}

// Get scroll pos
function getScrollXY() {
  var a = 0,
      b = 0;
  return "number" == typeof window.pageYOffset ? (b = window.pageYOffset,
    a = window.pageXOffset) : document.body && (document.body.scrollLeft ||
    document.body.scrollTop) ? (b = document.body.scrollTop, a = document.body.scrollLeft)
    : document.documentElement && (document.documentElement.scrollLeft ||
    document.documentElement.scrollTop) && (b = document.documentElement.scrollTop,
    a = document.documentElement.scrollLeft), [a, b]
}

// Get document height; used in calculations of checking page end
function getDocHeight() {
  var a = document;
  return Math.max(a.body.scrollHeight, a.documentElement.scrollHeight, a.body.offsetHeight,
    a.documentElement.offsetHeight, a.body.clientHeight, a.documentElement.clientHeight)
}

// END TODO

if (isf) {
    var inc = 0,
        num = 0,
        isn = !0;
    if (isn) {
      /*
      $(window).scroll(function() {

      });*/
      if (window.innerWidth > 808) {
        _("cp").style.width = "300px";
        _("cp").style.right = "0px";
      }
      if(!mobilecheck) {
        _("cp").addEventListener("mouseover", function() {
          _("cp").style.overflowY = "auto";
          document.body.style.overflowY = "auto";
        });

        _("cp").addEventListener("mouseout", function() {
          _("cp").style.overflowY = "hidden";
          document.body.style.overflowY = "auto";
        });
      }

      var w = window,
          d = document,
          e = d.documentElement,
          g = d.getElementsByTagName("body")[0],
          x = w.innerWidth || e.clientWidth || g.clientWidth,
          y = w.innerHeight || e.clientHeight || g.clientHeight;
      _("pageMiddle_index").style.overflow = "auto";
      /*
      for (var cut = 0, is = "<?php echo $imgs; ?>", isa = is.split("|"), j = (inc = 0, 0);
        j < isa.length - 1; j++) {
          ++inc, cut = "" != isa[j] ? 90 : 400;
          var t = _("pcs_" + inc).innerText;
          if (t.length > 90) {
              var xt = t.substr(0, cut);
              _("pcs_" + inc).innerText = xt + " ..."
          }
      }
      */
    }
}

// If user is not logged in display an some constantly altering text
if(!isf){
  let keepLoop = 0;

  // The texts that will be displayed
  let testArr = ["Upload videos and photos", "Create unique content",
    "Keep contact with your friends", "Chat with other people", "Write and read articles",
    "Talk in groups", "Search for people nearby", "Share your ideas"];
  let contDiv = document.getElementById("changingWords");

  // Change word in every 2 secs
  setInterval(getWord, 2000);

  // Call this function for word change
  function getWord(){
    if(keepLoop >= testArr.length) keepLoop = 0;
    let newDiv = document.createElement("div");
    let newSpan = document.createElement("span");
    newDiv.appendChild(newSpan);
    newSpan.className = "wordsBg";
    newSpan.innerHTML = testArr[keepLoop];
    newDiv.className = "wordsStyle";
    newSpan.id = 'fadeSpan';
    newSpan.style.display = "none";
    contDiv.replaceChild(newDiv, contDiv.childNodes[0]);
    $("#fadeSpan").fadeIn(500)
    keepLoop++;
  }
}

