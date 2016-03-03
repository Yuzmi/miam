app.items = {
	category: null,
	feed: null,
	markable: false,
	subscriber: null,
	type: null,

	init: function() {
		$(".item .header").on("click", function(e) {
			var item = $(this).closest(".item");

			if(!item.hasClass("expanded")) {
				e.preventDefault();
				app.items.open(item);
			}
		});

		$(".item .hide").on("click", function(e) {
			e.preventDefault();
			$(this).closest(".item").removeClass("expanded");
			e.stopPropagation();
		});

		$(".item .star").on("click", function(e) {
			e.preventDefault();
			if(app.items.markable) {
				var item = $(this).closest(".item");
				
				if(item.hasClass("starred")) {
					app.items.unstar(item, function() {
						item.removeClass("starred");
					});
				} else {
					app.items.star(item, function() {
						item.addClass("starred");
					});
				}
			}
			e.stopPropagation();
		});

		$(".item .enclosure").on("click", function(e) {
			window.open($(this).data("url"));
		});
	},

	open: function(item) {
		$(".item").removeClass("expanded");
		item.addClass("expanded");

		if(app.items.markable && !item.hasClass("read")) {
			app.items.read({
				type: "item",
				item: item.data("item")
			}, function() {
				item.addClass("read");
				if(app.sidebar) {
					app.sidebar.decrementUnreadCountForFeed(item.data('feed'));
				}
			});
		}
	},

	read: function(data, callback) {
		data.subscriber = app.items.subscriber;

		$.ajax({
			type: "POST",
			url: Routing.generate('ajax_items_read'),
			data: data,
			dataType: "json"
		}).done(function(result) {
			if(result.success) {
				if(callback) {
					callback();
				}
			}
		});
	},

	star: function(item, callback) {
		$.ajax({
			type: "POST",
			url: Routing.generate('ajax_item_star', {'id': item.data("item")}),
			dataType: "json"
		}).done(function(result) {
			if(result.success) {
				if(callback) {
					callback();
				}
			}
		});
	},

	unstar: function(item, callback) {
		$.ajax({
			type: "POST",
			url: Routing.generate('ajax_item_unstar', {'id': item.data("item")}),
			dataType: "json"
		}).done(function(result) {
			if(result.success) {
				if(callback) {
					callback();
				}
			}
		});
	},

	get: function(data, callback) {
		$.ajax({
			type: "POST",
			url: Routing.generate('ajax_items_get'),
			data: data,
			dataType: "json"
		}).done(function(result) {
			if(result.success) {
				callback(result.items);
			}
		});
	},

	refresh: function() {
		app.items.get({
			category: app.items.category,
			feed: app.items.feed,
			markable: app.items.markable,
			subscriber: app.items.subscriber,
			type: app.items.type
		}, function(items) {
			$(".items").html(items);
			app.items.init();
		});
	}
}