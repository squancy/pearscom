function genDialogBox() {
  // prepareDialog is decalred in p_dialog.js
  prepareDialog();
  _("dialogbox").innerHTML = `
    <p style="font-size: 18px; margin: 0px;">
      File type is not supported
    </p>

    <p>
      The image that you want to upload has an unvalid extension given that we do not
      support. The allowed file extensions are: jpg, jpeg, png and gif. For further
      information please visit the help page.
    </p>

    <button id="vupload" style="position: absolute; right: 3px; bottom: 3px;"
      onclick="closeDialog()">Close</button>
  `;
}
