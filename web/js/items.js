app.items = {
	init: function() {
		$(".itemContent img.clickToShow").off("click");
		$(".itemContent img.clickToShow").on("click", function(e) {
			if($(this).hasClass("clickToShow")) {
				e.preventDefault();
				$(this).prop('src', $(this).data("src"));
				$(this).prop('srcset', $(this).data("srcset"));
				$(this).removeClass("clickToShow");
			}
		});

		$(".itemContent .showDetails .link").off();
		$(".itemContent .showDetails .link").on("click", function() {
			$(".itemContent .details").addClass('expanded');
			$(".itemContent .showDetails").remove();
		});
	}
}