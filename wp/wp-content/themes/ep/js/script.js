/*************************************************
**  Init
*************************************************/
$(document).ready(function() {
	$("#Searchbar").attr("value", "Jag söker...");

	var text = "Jag söker...";

	$("#Searchbar").focus(function() {
		$(this).addClass("active");
		if($(this).attr("value") === text) $(this).attr("value", "");
	});

	$("#Searchbar").blur(function() {
		$(this).removeClass("active");
		if($(this).attr("value") === "") $(this).attr("value", text);
	});
});

/* vertical rhythm for images    */
$(window).bind('load', function(){
$("img").baselineAlign({container:'.popup'});
});
