/*
  Independent functions for opening and closing emoji boxes.
  TODO: merge the two functions and add more logic
*/

function openEmojiBox() {
  var cancel = _("emojiBox_art");
  if ("block" == cancel.style.display) {
    cancel.style.display = "none";
  } else {
    cancel.style.display = "block";
  }
}

function openEmojiBox_reply(name) {
  var cancel = _("emojiBox_reply_" + name);
  if ("block" == cancel.style.display) {
    cancel.style.display = "none";
  } else {
    cancel.style.display = "block";
  }
}
