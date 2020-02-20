function getLSearchArt(box, result, url){
  var u = _(box).value;
  if(u == ""){
    _(result).style.display = "none";
    return false;
  }
  var x = encodeURI(u);
  window.location = url;
}
