app.items = {
	init: function() {
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

		$(".item .details_toggle").off("click");
		$(".item .details_toggle").on("click", function(e) {
			$(this).closest(".item").addClass("expandedDetails");
		});

		$(".item .content img.clickToShow").off("click");
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
		if(!item.hasClass("loaded")) {
			$.ajax({
				type: "POST",
				url: Routing.generate('ajax_get_item'),
				data: {'id': item.data("item")},
				dataType: "json"
			}).done(function(result) {
				if(result.success && !item.hasClass("loaded")) {
					item.children(".loading").remove();
					item.append(result.htmlData);
					item.addClass("loaded");
					app.items.init();
				}
			});
		}

		$(".item").removeClass("expanded");
		item.addClass("expanded");

		if(app.items.onExpand) {
			app.items.onExpand(item);
		}
	}
}