/*
  Insert an emoji at the current cursor position in a textarea.
*/

function insertEmoji(type, value) {
  var node = document.getElementById(type);
  if (node) {
    var newTop = node.scrollTop;
    var pos = 0;
    var undefined = node.selectionStart || "0" == node.selectionStart ? "ff" : !!document.selection && "ie";
    if ("ie" == undefined) {
      node.focus();
      var oSel = document.selection.createRange();
      oSel.moveStart("character", -node.value.length);
      pos = oSel.text.length;
    } else {
      if ("ff" == undefined) {
        pos = node.selectionStart;
      }
    }
    var left = node.value.substring(0, pos);
    var right = node.value.substring(pos, node.value.length);
    if (node.value = left + value + right, pos = pos + value.length, "ie" == undefined) {
      node.focus();
      var range = document.selection.createRange();
      range.moveStart("character", -node.value.length);
      range.moveStart("character", pos);
      range.moveEnd("character", 0);
      range.select();
    } else {
      if ("ff" == undefined) {
        node.selectionStart = pos;
        node.selectionEnd = pos;
        node.focus();
      }
    }
    node.scrollTop = newTop;
  }
}
