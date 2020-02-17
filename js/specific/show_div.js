/*
  Expand status/reply post area on click.
*/

function showBtnDiv() {
  _("btns_SP").style.display = "block";
}

function showBtnDiv_reply(name, sType) {
  if (!mobilecheck) {
    _("replytext_" + sType + "_" + name).style.height = "130px";
  }
  _("btns_SP_reply_" + sType + "_" + name).style.display = "block";
}
