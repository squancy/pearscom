/*
  Independent functions for opening and closing emoji boxes.
  TODO: merge the two functions and add more logic
*/

function openEmojiBox(box = 'emojiBox_art') {
  var box = _(box);
  if (box.style.display == "block") {
    box.style.display = "none";
  } else {
    box.style.display = "block";
  }
}

function openEmojiBox_reply(name) {
  var box = _("emojiBox_reply_" + name);
  if (box.style.display == "block") {
    box.style.display = "none";
  } else {
    box.style.display = "block";
  }
}
