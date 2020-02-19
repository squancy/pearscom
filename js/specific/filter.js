function addListener(onw, w, cont = 'userFlexArts', serverSide, sHandler){
  _(onw).addEventListener("click", function(){
    _(cont).innerHTML = '<img src="/images/rolling.gif" width="30" height="30">';
    filterArts(w, serverSide, sHandler);
  });
}

function rejectionHandler() {
  // do something
}

function filterArts(otype, serverSide, sHandler){
  changeStyle(otype, BOXES);
  let req = new XMLHttpRequest();
  serverSide += otype;
  req.open("GET", serverSide, false);
  req.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
  req.onreadystatechange = function(){
    if(req.readyState == 4 && req.status == 200){
      sHandler(req);
    } else {
      rejectionHandler();
    }
  }
  req.send();
}

function changeStyle(otype, boxes){
  _(otype).style.color = "red";
  for (let box of boxes) {
    if(otype != box) {
      _(box).style.color = "black";
    }
  }
}
