function doAnim(clickEl, box) {
  $(clickEl).click(function() {
    $(box).slideToggle( 200, function() {
      // Animation complete.
    });
  });
}
