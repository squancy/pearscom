function genDialogBtn(func) {
  return `<button id="vupload" style="position: absolute; right: 3px; bottom: 3px;"
    onclick="${func}()">Close</button>`; 
}
