/*var scrollY = 0;
var distance = 40;
var speed = 24;
function autoScrollTo(el) {
	var currentY = window.pageYOffset;
	var targetY = document.getElementById(el).offsetTop;
	var bodyHeight = document.body.offsetHeight;
	var yPos = currentY + window.innerHeight;
	var animator = setTimeout('autoScrollTo(\''+el+'\')',24);
	if(yPos > bodyHeight){
		clearTimeout(animator);
	} else {
		if(currentY < targetY-distance){
		    scrollY = currentY+distance;
		    window.scroll(0, scrollY);
	    } else {
		    clearTimeout(animator);
	    }
	}
}
function resetScroller(el){
	var currentY = window.pageYOffset;
    var targetY = document.getElementById(el).offsetTop;
	var animator = setTimeout('resetScroller(\''+el+'\')',speed);
	if(currentY > targetY){
		scrollY = currentY-distance;
		window.scroll(0, scrollY);
	} else {
		clearTimeout(animator);
	}
}*/
var _0xe11e=["\x70\x61\x67\x65\x59\x4F\x66\x66\x73\x65\x74","\x6F\x66\x66\x73\x65\x74\x54\x6F\x70","\x67\x65\x74\x45\x6C\x65\x6D\x65\x6E\x74\x42\x79\x49\x64","\x6F\x66\x66\x73\x65\x74\x48\x65\x69\x67\x68\x74","\x62\x6F\x64\x79","\x69\x6E\x6E\x65\x72\x48\x65\x69\x67\x68\x74","\x61\x75\x74\x6F\x53\x63\x72\x6F\x6C\x6C\x54\x6F\x28\x27","\x27\x29","\x73\x63\x72\x6F\x6C\x6C","\x72\x65\x73\x65\x74\x53\x63\x72\x6F\x6C\x6C\x65\x72\x28\x27"];var scrollY=0;var distance=40;var speed=24;function autoScrollTo(_0xd95fx5){var _0xd95fx6=window[_0xe11e[0]];var _0xd95fx7=document[_0xe11e[2]](_0xd95fx5)[_0xe11e[1]];var _0xd95fx8=document[_0xe11e[4]][_0xe11e[3]];var _0xd95fx9=_0xd95fx6+ window[_0xe11e[5]];var _0xd95fxa=setTimeout(_0xe11e[6]+ _0xd95fx5+ _0xe11e[7],24);if(_0xd95fx9> _0xd95fx8){clearTimeout(_0xd95fxa)}else {if(_0xd95fx6< _0xd95fx7- distance){scrollY= _0xd95fx6+ distance;window[_0xe11e[8]](0,scrollY)}else {clearTimeout(_0xd95fxa)}}}function resetScroller(_0xd95fx5){var _0xd95fx6=window[_0xe11e[0]];var _0xd95fx7=document[_0xe11e[2]](_0xd95fx5)[_0xe11e[1]];var _0xd95fxa=setTimeout(_0xe11e[9]+ _0xd95fx5+ _0xe11e[7],speed);if(_0xd95fx6> _0xd95fx7){scrollY= _0xd95fx6- distance;window[_0xe11e[8]](0,scrollY)}else {clearTimeout(_0xd95fxa)}}