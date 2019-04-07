fnResize();
var k = null;
window.addEventListener("resize",function(){clearTimeout(k);k = setTimeout(fnResize,300);},false);
// function fnResize(){document.getElementsByTagName('html')[0].style.fontSize = (document.documentElement.clientWidth) / 15 + 'px';}
function fnResize(){
	var w = document.documentElement.clientWidth;
	var ele = document.getElementsByTagName('html')[0]
	if(w >= 750){
		w = 750;
		ele.style.width = '750px';
		ele.style.margin = '0 auto';
	}else{
		ele.style.width = 'auto';
	}
	ele.style.fontSize = w / 15 + 'px';
}