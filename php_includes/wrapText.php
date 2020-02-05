<?php
  /*
    Wrap text at a given char and append ...
  */

  function wrapText($wrappedTxt, $pos) {
    if(strlen($wrappedTxt) > $pos){
		  $wrappedTxt = mb_substr($wrappedTxt, 0, $pos - 3, "utf-8");
		  $wrappedTxt .= "...";
		}
    return $wrappedTxt;
  }
?>
