function genErrorDialog() {
  prepareDialog();
  _("dialogbox").innerHTML = `
    <p style="font-size: 18px; margin: 0px;">
      Error
    </p>

    <p>
      Unfortunately, an unknown error occurred. Please try again later.
    </p>
    <br />
    <button id="vupload" style="position: absolute; right: 3px; bottom: 3px;"
      onclick="closeDialog()">Close</button>`;
}
