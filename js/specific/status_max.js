/*
  Do not let the user to write more than N chars in an input field
*/

function statusMax(match, i) {
  if (match.value.length > i) {
    prepareDialog();
    _("dialogbox").innerHTML = `
      <p style="font-size: 18px; margin: 0px;">
        Maximum character limit reached
      </p>

      <p>
        For some reasons we limited the number of characters that you can write at the same
        time. Now you have reached this limit.
      </p>
      <br />
      <button id="vupload" style="position: absolute; right: 3px; bottom: 3px;"
        onclick="closeDialog()">Close</button>`;
    match.value = match.value.substring(0, i);
  }
}
