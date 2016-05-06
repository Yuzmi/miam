app.items = {
	countNewAdded: 0,
	dateRefresh: null,
	page: 1,

	init: function() {
		if(app.items.dateRefresh == null) {
			app.items.dateRefresh = app.dateLoaded;
		}

		$(".item .header").off("click");
		$(".item .header").on("click", function(e) {
			var item = $(this).closest(".item");

			if(!item.hasClass("expanded")) {
				e.preventDefault();
				app.items.expand(item);
			}
		});

		$(".item .hide").off("click");
		$(".item .hide").on("click", function(e) {
			e.preventDefault();
			$(this).closest(".item").removeClass("expanded");
			e.stopPropagation();
		});

		$(".item .details_toggle").on("click", function(e) {
			$(this).closest(".item").addClass("expandedDetails");
		});

		$(".item .content img.clickToShow").on("click", function(e) {
			if($(this).hasClass("clickToShow")) {
				e.preventDefault();
				$(this).prop('src', $(this).data("src"));
				$(this).prop('srcset', $(this).data("srcset"));
				$(this).removeClass("clickToShow");
			}
		});
	},

	expand: function(item) {
		$(".item").removeClass("expanded");
		item.addClass("expanded");

		if(app.items.onExpand) {
			app.items.onExpand(item);
		}
	}
}