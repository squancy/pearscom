/*
  Shorthand way of making an ajax request to the server
*/

function ajaxObj(type, serverSide) {
  var req = new XMLHttpRequest;
  req.open(type, serverSide, true);
  req.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
  return req;
}

function ajaxReturn(e) {
  if (e.readyState == 4 && e.status == 200) {
    return true;
  }
}
