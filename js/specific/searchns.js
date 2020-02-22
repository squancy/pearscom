function getMyFLArr(box, url, url2 = null, cond = null) {
  var linkForLodLive = _(box).value;
  if (linkForLodLive == "") {
    return false;
  }

  var scopeString = encodeURI(linkForLodLive);
  if (cond) {
    window.location = url2; 
  } else {
    window.location = url;
  }
}
