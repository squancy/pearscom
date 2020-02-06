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
