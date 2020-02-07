function prepareDialog() {
  _("overlay").style.display = "block";
  _("overlay").style.opacity = 0.5;
  _("dialogbox").style.display = "block";
  document.body.style.overflow = "hidden";
}

function closeDialog() {
  _("dialogbox").style.display = "none";
  _("overlay").style.display = "none";
  _("overlay").style.opacity = 0;
  document.body.style.overflow = "auto";
}

function showDialog() {
  _("dialogbox").innerHTML = `
    <p style="font-size: 18px; margin: 0px;">
      An error occured
    </p>
    <p>
      Unfortunately an unknown error has occured during the interaction.
      Please try again later and check everything is proper.
    </p>
    <br />
    <button id="vupload" style="position: absolute; right: 3px; bottom: 3px;"
      onclick="closeDialog()">Close</button>
  `;
}
