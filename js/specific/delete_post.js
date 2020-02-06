/*
  Delete status posts and replies.
  TODO: merge the functions & add more logic
*/

function deleteStatus(id, status) {
  if (1 != confirm("Press OK to confirm deletion of this status and its replies")) {
    return false;
  }
  var xhr = ajaxObj("POST", "/php_parsers/article_status_system.php");
  xhr.onreadystatechange = function() {
    if (1 == ajaxReturn(xhr)) {
      if ("delete_ok" == xhr.responseText) {
        _(status).style.display = "none";
        _("replytext_" + id).style.display = "none";
        _("replyBtn_" + id).style.display = "none";
      } else {
        alert(xhr.responseText);
      }
    }
  };
  xhr.send("action=delete_status&statusid=" + id);
}

function deleteReply(result, data) {
  if (1 != confirm("Press OK to confirm deletion of this reply")) {
    return false;
  }
  var res = ajaxObj("POST", "/php_parsers/article_status_system.php");
  res.onreadystatechange = function() {
    if (1 == ajaxReturn(res)) {
      if ("delete_ok" == res.responseText) {
        _(data).style.display = "none";
      } else {
        alert(res.responseText);
      }
    }
  };
  res.send("action=delete_reply&replyid=" + result);
}
