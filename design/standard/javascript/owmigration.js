$(document).ready(function() {
	$(".display_log_control").click(function() {
		row = $("#" + $(this).attr("ref"));
		if (row.is(':hidden')) {
			$(this).html( $(this).attr("hide_title") )
			row.show();
		} else {
			$(this).html( $(this).attr("show_title") )
			row.hide();
		}
		return false;
	});
});
