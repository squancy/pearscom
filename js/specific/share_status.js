/*
  Share a post, used on status pages.
  Note: prepareDialog() is defined in p_dialog.js
*/

function shareStatus(type) {
  var request = ajaxObj("POST", "/php_parsers/article_status_system.php");
  request.onreadystatechange = function() {
    if (1 == ajaxReturn(request)) {
      if ("share_ok" == request.responseText) {
        prepareDialog();
        _("dialogbox").innerHTML = `
          <p style="font-size: 18px; margin: 0px;">
            Shared post
          </p>

          <p>
            You have successfully shared this post which will be visible on your main profile
            page.
          </p>
          <br />
          <button id="vupload" style="position: absolute; right: 3px; bottom: 3px;"
            onclick="closeDialog()">Close</button>`;
      } else {
        prepareDialog();
        _("dialogbox").innerHTML = `
          <p style="font-size: 18px; margin: 0px;">
            An error occured
          </p>

          <p>
            Unfortunately an unknown error has occured with your post sharing.
            Please try again later and check everything is proper.
          </p>

          <br />
          <button id="vupload" style="position: absolute; right: 3px; bottom: 3px;"
            onclick="closeDialog()">Close</button>`;
      }
    }
  }
  request.send("action=share&id=" + type);
}
