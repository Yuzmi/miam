app.sidebar = {
	init: function() {
		// Indentation des catégories
		$(".sidebar .row").each(function() {
			$(this).children(".name").css('padding-left', $(this).parents(".rowChildren").length+"em");
		});

		// Sélection d'un flux ou d'une catégorie
		$(".sidebar .row").click(function(e) {
			app.items.type = $(this).data('type');

			switch(app.items.type) {
				case 'feed':
					app.items.feed = $(this).data('feed'); break;
				case 'category':
				    app.items.category = $(this).data('category'); break;
			}

			app.items.refresh();

			$(".sidebar .row").removeClass("selected");
			$(this).addClass("selected");
		});

		// Subcategories toggle
		$(".sidebar .row .toggle").click(function(e) {
			var row = $(this).closest(".row");
			var rowChildren = $(".sidebar .rowChildren[data-parent="+$(this).closest(".row").data("category")+"]");

			if(row.hasClass("expanded")) {
				row.removeClass("expanded");
				rowChildren.removeClass("expanded");
			} else {
				row.addClass("expanded");
				rowChildren.addClass("expanded");
			}

			e.stopPropagation();
		});

		// Sidebar toggle
		$(".sidebar_toggle").click(function() {
			$(".body_home").toggleClass("hide_sidebar");
		});

		// Menu contextuel d'un flux ou d'une catégorie
		if(app.items.markable) {
			$(".sidebar .row").contextmenu(function(e) {
				e.preventDefault();

				$(".sidebarRowMenu").remove();

				var type = $(this).data('type');
				
				var menu = $("<div>")
					.addClass("sidebarRowMenu")
					.css({
						left: e.clientX, 
						top: e.clientY
					})
					.attr('data-type', type)
				;

				if(type == 'feed') {
					menu.attr('data-feed', $(this).data('feed'));
				} else if(type == 'category') {
					menu.attr('data-category', $(this).data('category'));
				}

				var menuOption = $("<div>").addClass("option").text("Mark as read").attr('data-action', 'read');
				menu.append(menuOption);

				$(".body_home").append(menu);

				$(".sidebarRowMenu .option").click(function(e) {
					var action = $(this).data("action");

					var category = $(this).closest(".sidebarRowMenu").data("category");
					var feed = $(this).closest(".sidebarRowMenu").data("feed");
					var type = $(this).closest(".sidebarRowMenu").data("type");

					if(action == 'read') {
						app.items.read({
							type: type,
							feed: feed,
							category: category
						}, function() {
							app.sidebar.refreshUnreadCounts();
						});
					}
				});
			});
			
			$(document).click(function() {
				$(".sidebarRowMenu").remove();
			});
		}

		this.countUnread();
		this.toggleUnreadCounts();
	},

	countUnread: function() {
		// Calculate unread counts for categories
		$(".sidebar .row[data-type='category']").each(function() {
			var category = $(this).data("category");
			var unreadCount = 0;

			$(".sidebar .rowChildren[data-parent="+category+"] .row[data-type='feed'] .unreadCount").each(function() {
				var count = parseInt($(this).text());
				if(!isNaN(count)) {
					unreadCount += count;
				}
			});

			$(".sidebar .row[data-category="+category+"] .unreadCount").text(unreadCount);
		});
	},

	toggleUnreadCounts: function() {
		// Show or hide unread counts
		$(".sidebar .row .unreadCount").each(function() {
			var unreadCount = parseInt($(this).text());
			if(!isNaN(unreadCount) && unreadCount > 0) {
				$(this).show();
			} else {
				$(this).hide();
			}
		});
	},

	refreshUnreadCounts: function() {
		$.ajax({
			type: "POST",
			url: Routing.generate('ajax_unread_get'),
			data: {
				subscriber: app.items.subscriber
			},
			dataType: "json"
		}).done(function(result) {
			if(result.success) {
				$(".sidebar .row .unreadCount").each(function() {
					$(this).text(0);

					var feedId = $(this).closest(".row").data("feed");
					if(feedId && result.unreadCounts) {
						for(var i=0; i<result.unreadCounts.length; i++) {
							if(result.unreadCounts[i].feedId == feedId) {
								$(this).text(result.unreadCounts[i].count);
								break;
							}
						}
					}
				})

				app.sidebar.countUnread();
				app.sidebar.toggleUnreadCounts();
			}
		});
	},

	decrementUnreadCountForFeed: function(feed) {
		$(".sidebar .row[data-feed="+feed+"] .unreadCount").each(function() {
			var count = parseInt($(this).text());
			if(!isNaN(count)) {
				$(this).text(count - 1);
			}
		});

		this.countUnread();
		this.toggleUnreadCounts();
	}
};