/*
  Functions for pages that use sorting
*/

function addListener(onw, w, container, serverSide, box, n){
  _(onw).addEventListener("click", function(){
    _(container).innerHTML = '<img src="/images/rolling.gif" width="30" height="30">';
    filterArts(w, serverSide, box, container, n);
  });
}

function applyListeners(n, box, container, serverSide, cont) {
  for(let i = 0; i < n; i++){
    addListener(box + i, box + i, container, serverSide, box, n);
  }
}

function filterArts(otype, serverSide, box, cont, n){
  changeStyle(otype, box, n);
  let req = new XMLHttpRequest();
  req.open("GET", serverSide + otype, false);
  req.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
  req.onreadystatechange = function(){
    if(req.readyState == 4 && req.status == 200){
      _(cont).innerHTML = req.responseText;
      startLazy();
    }
  }
  req.send();
}

function changeStyle(otype, box, n){
  _(otype).style.color = "red";
  for(let i = 0; i < n; i++){
    if(box + i != otype) _(box + i).style.color = "black";
  }
}
