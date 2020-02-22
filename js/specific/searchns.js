function getMyFLArr(box, url) {
  var linkForLodLive = _(box).value;
  if (linkForLodLive == "") {
    return false;
  }

  var scopeString = encodeURI(linkForLodLive);
  window.location = url
}
