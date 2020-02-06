/*
  Expand status/reply post area on click.
*/

function showBtnDiv() {
  _("btns_SP").style.display = "block";
}

function showBtnDiv_reply(name) {
  if (!mobilecheck) {
    _("replytext_" + name).style.height = "130px";
  }
  _("btns_SP_reply_" + name).style.display = "block";
}
