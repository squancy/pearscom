function doDD(first, second){
  $( "#" + first ).click(function() {
    $( "#" + second ).slideToggle( "fast", function() {
      
    });
  });
}


