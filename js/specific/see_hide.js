/*
  Two independent functions for expanding and collapsing long status & reply posts.
  One function for showing and hiding status replies.
*/
var statreply = "less";
function opentext_reply(name) {
  if ("less" == statreply) {
    _("lessmore_reply_" + name).style.display = "block";
    _("toggle_reply_" + name).innerText = "See Less";
    _("hide_reply_" + name).style.display = "none";
    statreply = "more";
  } else if ("more" == statreply) {
    _("lessmore_reply_" + name).style.display = "none";
    _("toggle_reply_" + name).innerText = "See More";
    _("hide_reply_" + name).style.display = "block";
    statreply = "less";
  }
}

var stat = "less";
function opentext(name) {
  if ("less" == stat) {
    _("lessmore_" + name).style.display = "block";
    _("toggle_" + name).innerText = "See Less";
    _("hide_" + name).style.display = "none";
    stat = "more";
  } else if ("more" == stat) {
    _("lessmore_" + name).style.display = "none";
    _("toggle_" + name).innerText = "See More";
    _("hide_" + name).style.display = "block";
    stat = "less";
  }
}

var us = "less";
function showReply(name, index) {
  if ("less" == us) {
    _("showreply_" + name).innerText = "Hide replies (" + index + ")";
    _("allrply_" + name).style.display = "block";
    us = "more";
  } else if ("more" == us) {
    _("showreply_" + name).innerText = "Show replies (" + index + ")";
    _("allrply_" + name).style.display = "none";
    us = "less";
  }
}
