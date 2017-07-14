$(document).ready(function(){

	$(window).scroll(function(){  
        //下面这句主要是获取网页的总高度，主要是考虑兼容性所以把Ie支持的documentElement也写了，这个方法至少支持IE8  
        var htmlHeight=document.body.scrollHeight||document.documentElement.scrollHeight;  
        //clientHeight是网页在浏览器中的可视高度，  
        var clientHeight=document.body.clientHeight||document.documentElement.clientHeight;  
        //scrollTop是浏览器滚动条的top位置，  
        var scrollTop=document.body.scrollTop||document.documentElement.scrollTop; 
        console.log(scrollTop); 
        //通过判断滚动条的top位置与可视网页之和与整个网页的高度是否相等来决定是否加载内容；  
        if(scrollTop >= 50){
        	$("#header").removeClass('transparent');       
        } else {
        	$("#header").addClass('transparent');     
        } 
    })  
});