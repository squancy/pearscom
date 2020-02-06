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

function toggleLike(e, o, t) {
  var result = ajaxObj("POST", "/php_parsers/like_system_art.php");
  result.onreadystatechange = function() {
    if (1 == ajaxReturn(result)) {
      if ("like_success" == result.responseText) {
        _(t).innerHTML = `
          <a href="#" onclick="return false;"
            onmousedown="toggleLike('unlike', '${o}','likeBtn_${o}');">
            <img src="/images/fillthumb.png" width="18" height="18" class="like_unlike">
          </a>
          <span style="vertical-align: middle; margin-left: 5px;">Dislike</span>
        `;

        // Replace parentheses and words from the like count
        let e = _("ipanf_" + o).innerText.replace("(", "")
          .replace(")", "").replace("likes", "").replace(" ", "");
        e = Number(e) + 1;
        _("ipanf_" + o).innerText = e + " likes";
      } else {
        if ("unlike_success" == result.responseText) {
          _(t).innerHTML = `
            <a href="#" onclick="return false;"
              onmousedown="toggleLike('like', '${o}', 'likeBtn_${o}')">
              <img src="/images/nf.png" width="18" height="18" class="like_unlike">
            </a>
            <span style="vertical-align: middle; margin-left: 5px;">Like</span>
          `;
          let e = _("ipanf_" + o).innerText.replace("(", "").replace(")", "")
            .replace("likes", "").replace(" ", "");
            e = Number(e) - 1;
            _("ipanf_" + o).innerText = e + " likes";
        } else {
          prepareDialog();
          showError();
        }
      }
    }
  }
  result.send("type=" + e + "&id=" + o);
}

function toggleLike_reply(e, o, t) {
  var result = ajaxObj("POST", "/php_parsers/like_reply_system_art.php");
  result.onreadystatechange = function() {
    if (1 == ajaxReturn(result)) {
      if ("like_reply_success" == result.responseText) {
        _(t).innerHTML = `
          <a href="#" onclick="return false;"
            onmousedown="toggleLike_reply('unlike', '${o}', 'likeBtn_reply_${o}')">
            <img src="/images/fillthumb.png" width="18" height="18" class="like_unlike">
          </a>
          <span style="vertical-align: middle; margin-left: 5px;">Dislike</span>
        `;
        let e = _("ipanr_" + o).innerText.replace("(", "")
          .replace(")", "").replace("likes", "").replace(" ", "");
        e = Number(e) + 1;
        _("ipanr_" + o).innerText = e + " likes";
      } else {
        if ("unlike_reply_success" == result.responseText) {
          _(t).innerHTML = `
            <a href="#" onclick="return false;"
              onmousedown="toggleLike_reply('like', '${o}', 'likeBtn_reply_${o}')">
              <img src="/images/nf.png" width="18" height="18" class="like_unlike">
            </a>
            <span style="vertical-align: middle; margin-left: 5px;">Like</span>
          `;
          let e = _("ipanr_" + o).innerText.replace("(", "").replace(")", "")
            .replace("likes", "").replace(" ", "");
          e = Number(e) - 1;
          _("ipanr_" + o).innerText = e + " likes";
        } else {
          prepareDialog();
          showError();
        }
      }
    }
  }
  result.send("type=" + e + "&id=" + o);
}
