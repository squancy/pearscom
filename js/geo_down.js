function main() {
  $("#wrapping").hide();
  $("#acc_geo").on("click", function() {
    $(this).next().slideToggle()
  });
}
$(document).ready(main);
