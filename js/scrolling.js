var forward = _('slide2');
forward.onmousedown = function () {
    var container = _('imgcontsc');
    sideScroll(container,'right',15,100,10);
};

var back = _('slide1');
back.onmousedown = function () {
    var container = _('imgcontsc');
    sideScroll(container,'left',15,100,10);
};

function sideScroll(element,direction,speed,distance,step){
    scrollAmount = 0;
    var slideTimer = setInterval(function(){
        if(direction == 'left'){
            element.scrollLeft -= step;
        } else {
            element.scrollLeft += step;
        }
        scrollAmount += step;
        if(scrollAmount >= distance){
            window.clearInterval(slideTimer);
        }
    }, speed);
}