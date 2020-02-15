/*
  Delete status posts and replies.
  TODO: merge the functions & add more logic
*/

function deleteStatus(id, status, serverSide = "/php_parsers/article_status_system.php") {
  if (!confirm("Press OK to confirm deletion of this status and its replies")) {
    return false;
  }
  var xhr = ajaxObj("POST", serverSide);
  xhr.onreadystatechange = function() {
    if (ajaxReturn(xhr)) {
      if (xhr.responseText == "delete_ok") {
        _(status).style.display = "none";
        if (_("replytext_" + id) != null) {
          _("replytext_" + id).style.display = "none";
          _("replyBtn_" + id).style.display = "none";
        }
      } else {
        genErrorDialog();
      }
    }
  }
  xhr.send("action=delete_status&statusid=" + id);
}

function deleteReply(result, data, serverSide = "/php_parsers/article_status_system.php") {
  if (!confirm("Press OK to confirm deletion of this reply")) {
    return false;
  }
  var res = ajaxObj("POST", serverSide);
  res.onreadystatechange = function() {
    if (ajaxReturn(res)) {
      if ("delete_ok" == res.responseText) {
        _(data).style.display = "none";
      } else {
        genErrorDialog();
        console.log(res.responseText);
      }
    }
  }
  res.send("action=delete_reply&replyid=" + result);
}
