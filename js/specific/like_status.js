/*
  Toggle like for posts & replies.
  TODO: get the number of likes from the server-side and not from an HTML tag
  & also merge the 2 like functions to one more dynamic func.
*/

function showError() {
  _("dialogbox").innerHTML = `
    <p style="font-size: 18px; margin: 0px;">An error occured</p>
    <p>
      Unfortunately an unknown error has occured with your status like.
      Please try again later and check everything is proper.
    </p>
    <br />
    <button id="vupload" style="position: absolute; right: 3px; bottom: 3px;"
      onclick="closeDialog()">Close</button>
  `;
  _(t).innerHTML = "Try again later";
}

function toggleLike(e, o, t, isGr = false, serverSide = '/php_parsers/like_system_art.php',
  sType = 'status') {
  var toSend = "type=" + e + "&id=" + o;
  if (isGr) {
    toSend += '&group=' + isGr; 
  }

  let pref = '';
  if (serverSide == '/php_parsers/video_parser.php') {
    pref = 'v';
  }

  var result = ajaxObj("POST", serverSide);
  result.onreadystatechange = function() {
    if (ajaxReturn(result)) {
      if ("like_success" == result.responseText) {
        _(t).innerHTML = `
          <a href="#" onclick="return false;"
            onmousedown="toggleLike('unlike', '${o}','likeBtn${pref}${sType}_${o}', '${isGr}',
            '${serverSide}', '${sType}');">
            <img src="/images/fillthumb.png" width="18" height="18" class="like_unlike">
          </a>
          <span style="vertical-align: middle; margin-left: 5px;">Dislike</span>
        `;

        // Replace parentheses and words from the like count
        let e = _("ipanf" + sType + "_" + o).innerText.replace("(", "")
          .replace(")", "").replace("likes", "").replace(" ", "");
        e = Number(e) + 1;
        _("ipanf" + sType  +  "_" + o).innerText = e + " likes";
      } else {
        if ("unlike_success" == result.responseText) {
          _(t).innerHTML = `
            <a href="#" onclick="return false;"
              onmousedown="toggleLike('like', '${o}', 'likeBtn${pref}${sType}_${o}', '${isGr}',
              '${serverSide}', '${sType}')">
              <img src="/images/nf.png" width="18" height="18" class="like_unlike">
            </a>
            <span style="vertical-align: middle; margin-left: 5px;">Like</span>
          `;
          let e = _("ipanf" + sType  +  "_" + o).innerText.replace("(", "").replace(")", "")
            .replace("likes", "").replace(" ", "");
            e = Number(e) - 1;
            _("ipanf" + sType  +  "_" + o).innerText = e + " likes";
        } else {
          console.log(result.responseText);
          console.log('a');
          prepareDialog();
          showError();
        }
      }
    }
  }
  result.send(toSend);
}

function toggleLike_reply(e, o, t, isGr = false,
  serverSide = '/php_parsers/like_reply_system_art.php', sType = 'status') {
  var toSend = "type=" + e + "&id=" + o;
  if (isGr) {
    toSend += '&group=' + isGr; 
  }

  var result = ajaxObj("POST", serverSide);
  result.onreadystatechange = function() {
    if (ajaxReturn(result)) {
      if ("like_reply_success" == result.responseText) {
        _(t).innerHTML = `
          <a href="#" onclick="return false;"
            onmousedown="toggleLike_reply('unlike', '${o}', 'likeBtn${sType}_reply_${o}',
            '${isGr}', '${serverSide}', '${sType}')">
            <img src="/images/fillthumb.png" width="18" height="18" class="like_unlike">
          </a>
          <span style="vertical-align: middle; margin-left: 5px;">Dislike</span>
        `;
        let e = _("ipanr" + sType  +  "_" + o).innerText.replace("(", "")
          .replace(")", "").replace("likes", "").replace(" ", "");
        e = Number(e) + 1;
        _("ipanr" + sType  +  "_" + o).innerText = e + " likes";
      } else {
        if ("unlike_reply_success" == result.responseText) {
          _(t).innerHTML = `
            <a href="#" onclick="return false;"
              onmousedown="toggleLike_reply('like', '${o}', 'likeBtn${sType}_reply_${o}',
              '${isGr}', '${serverSide}', '${sType}')">
              <img src="/images/nf.png" width="18" height="18" class="like_unlike">
            </a>
            <span style="vertical-align: middle; margin-left: 5px;">Like</span>
          `;
          let e = _("ipanr" + sType  +  "_" + o).innerText.replace("(", "").replace(")", "")
            .replace("likes", "").replace(" ", "");
          e = Number(e) - 1;
          _("ipanr" + sType  + "_" + o).innerText = e + " likes";
        } else {
          prepareDialog();
          showError();
        }
      }
    }
  }
  result.send(toSend);
}
