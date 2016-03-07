app.items = {
	init: function() {
		$(".item .header").on("click", function(e) {
			var item = $(this).closest(".item");

			if(!item.hasClass("expanded")) {
				e.preventDefault();
				app.items.expand(item);
			}
		});

		$(".item .hide").on("click", function(e) {
			e.preventDefault();
			$(this).closest(".item").removeClass("expanded");
			e.stopPropagation();
		});

		$(".item .enclosure").on("click", function(e) {
			window.open($(this).data("url"));
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