app.catalog = {
	init: function() {
		this.items.init();
	},

	items: {
		feed: null,
		page: 1,

		init: function() {
			$(".items .loadMore").off("click");
			$(".items .loadMore").on("click", function() {
				app.catalog.items.loadMore();
			});
		},

		loadMore: function() {
			$(".items .loadMore").addClass("loading");

			if(app.catalog.items.feed) {
				var url = Routing.generate('ajax_catalog_feed_get_items', {
					id: app.catalog.items.feed,
					page: app.catalog.items.page + 1
				});
			} else {
				var url = Routing.generate('ajax_catalog_get_items', {
					page: app.catalog.items.page + 1
				});
			}

			$.ajax({
				type: "GET",
				url: url,
				dataType: "json"
			}).done(function(result) {
				if(result.success && result.page == app.catalog.items.page + 1) {
					$(".items .loadMore").replaceWith(result.items);

					app.items.init();
					app.catalog.items.init();

					app.catalog.items.page += 1;
				}
			});
		}
	}
};