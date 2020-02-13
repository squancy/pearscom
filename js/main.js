function _(el) {
  return document.getElementById(el);
}

function toggleElement(e) {
  "block" == (e = _(e)).style.display ? e.style.display = "none" : e.style.display = "block"
}
