app.shit = {
	init: function() {
		this.sidebar.init();
		this.items.init();
	},

	sidebar: {
		init: function() {
			// Indentation des catégories
			$(".sidebar .row").each(function() {
				$(this).children(".name").css('padding-left', $(this).parents(".rowChildren").length+"em");
			});

			// Sélection d'un flux ou d'une catégorie
			$(".sidebar .row").click(function(e) {
				app.shit.items.type = $(this).data('type');

				switch(app.shit.items.type) {
					case 'feed':
						app.shit.items.feed = $(this).data('feed'); break;
					case 'category':
					    app.shit.items.category = $(this).data('category'); break;
				}

				app.shit.items.refresh();

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
				$(".body_shit").toggleClass("hide_sidebar");
			});

			if(app.user && app.shit.items.subscriber && app.user.id == app.shit.items.subscriber) {
				// Context menu for feeds and categories
				$(".sidebar .row").contextmenu(function(e) {
					e.preventDefault();

					$(".contextMenu").remove();

					var type = $(this).data('type');
					
					var menu = $("<div>")
						.addClass("contextMenu")
						.addClass("sidebarRowMenu")
						.css({
							left: e.clientX, 
							top: e.clientY + 10
						})
						.attr('data-type', type)
					;

					if(type == 'feed') {
						menu.attr('data-feed', $(this).data('feed'));
					} else if(type == 'category') {
						menu.attr('data-category', $(this).data('category'));
					}

					var countOptions = 0;

					if(type == "feed" || type == "category" || type == "all" || type == "unread") {
						var menuOption = $("<div>").addClass("option").text("Mark as read").attr('data-action', 'read');
						menu.append(menuOption);

						countOptions++;
					}

					if(countOptions > 0) {
						$(".body_shit").append(menu);

						$(".sidebarRowMenu .option").click(function(e) {
							var action = $(this).data("action");
							var type = $(this).closest(".sidebarRowMenu").data("type");

							if(action == 'read') {
								if(type == "feed") {
									app.shit.items.readFeed($(this).closest(".sidebarRowMenu").data("feed"));
								} else if(type == "category") {
									app.shit.items.readCategory($(this).closest(".sidebarRowMenu").data("category"));
								} else if(type == "all" || type == "unread") {
									app.shit.items.readAll();
								}
							}

							$(".contextMenu").remove();
						});
					}
				});

				$(".item").contextmenu(function(e) {
					e.preventDefault();

					$(".contextMenu").remove();

					var menu = $("<div>")
						.addClass("contextMenu")
						.addClass("itemMenu")
						.css({
							left: e.clientX,
							top: e.clientY + 10
						})
						.data("item", $(this).data("item"))
					;

					if($(this).hasClass("read")) {
						var menuOption = $("<div>").addClass("option").text("Mark as unread").attr("data-action", 'unread');
					} else {
						var menuOption = $("<div>").addClass("option").text("Mark as read").attr("data-action", 'read');
					}
					menu.append(menuOption);

					$(".body_shit").append(menu);

					$(".itemMenu .option").click(function(e) {
						var action = $(this).data("action");

						var item = $(".item[data-item="+$(this).closest(".itemMenu").data("item")+"]");

						if(action == 'read') {
							app.shit.items.readItem(item.data("item"));
						} else if(action == 'unread') {
							app.shit.items.unreadItem(item.data("item"));
						}

						$(".contextMenu").remove();
					});
				});
				
				// Hide the menu
				$(document).click(function(e) {
					if(e.which != 3) {
						$(".contextMenu").remove();
					}
				});

				// Refresh unread counts every 5 minutes
				setInterval(function() {
					app.shit.sidebar.refreshUnreadCounts();
				}, 300000);
			}

			// Get last items every 5 minutes
			setInterval(function() {
				app.shit.items.loadNew();
			}, 300000);

			this.countUnread();
			this.toggleUnreadCounts();
			this.toggleStarredCount();
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

			// Calculate total unread counts
			var totalCount = 0;
			var feeds = [];
			
			$(".sidebar .row[data-type='feed'] .unreadCount").each(function() {
				var feed = $(this).closest(".row[data-type='feed']").data("feed");
				if($.inArray(feed, feeds) == -1) {
					var count = parseInt($(this).text());
					if(!isNaN(count)) {
						totalCount += count;
					}

					feeds.push(feed);
				}
			});

			$(".sidebar .row[data-type='unread'] .unreadCount").text(totalCount);
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
				url: Routing.generate('ajax_shit_get_unread_counts'),
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

					app.shit.sidebar.countUnread();
					app.shit.sidebar.toggleUnreadCounts();
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
		},

		incrementUnreadCountForFeed: function(feed) {
			$(".sidebar .row[data-feed="+feed+"] .unreadCount").each(function() {
				var count = parseInt($(this).text());
				if(!isNaN(count)) {
					$(this).text(count + 1);
				}
			});

			this.countUnread();
			this.toggleUnreadCounts();
		},

		decrementStarredCount: function() {
			var count = parseInt($(".sidebar .row[data-type='starred'] .count").text());
			$(".sidebar .row[data-type='starred'] .count").text(count - 1);
			this.toggleStarredCount();
		},

		incrementStarredCount: function() {
			var count = parseInt($(".sidebar .row[data-type='starred'] .count").text());
			$(".sidebar .row[data-type='starred'] .count").text(count + 1);
			this.toggleStarredCount();
		},

		toggleStarredCount: function() {
			var count = parseInt($(".sidebar .row[data-type='starred'] .count").text());
			if(!isNaN(count) && count > 0) {
				$(".sidebar .row[data-type='starred'] .count").show();
			} else {
				$(".sidebar .row[data-type='starred'] .count").hide();
			}
		}
	},

	items: {
		category: null,
		feed: null,
		subscriber: null,
		type: null,

		init: function() {
			app.items.onExpand = function(item) {
				if(!item.hasClass("read")) {
					app.shit.items.readItem(item.data("item"));
				}
			}

			$(".item .star").click(function(e) {
				e.preventDefault();

				var item = $(this).closest(".item");
				
				if(item.hasClass("starred")) {
					app.shit.items.unstarItem(item.data("item"));
				} else {
					app.shit.items.starItem(item.data("item"));
				}

				e.stopPropagation();
			});

			$(".items .loadMore").click(function() {
				app.shit.items.loadMore();
			});
		},

		get: function(data, callback) {
			$.ajax({
				type: "POST",
				url: Routing.generate('ajax_shit_get_items'),
				data: data,
				dataType: "json"
			}).done(function(result) {
				callback(result);
			});
		},

		refresh: function() {
			this.get({
				category: app.shit.items.category,
				feed: app.shit.items.feed,
				loadMore: true,
				subscriber: app.shit.items.subscriber,
				type: app.shit.items.type
			}, function(result) {
				if(result.success) {
					$(".items").html(result.items);

					app.items.init();
					app.shit.items.init();

					app.items.countNewAdded = 0;
					app.items.dateRefresh = result.dateRefresh;
					app.items.page = 1;
				}
			});
		},

		loadNew: function() {
			this.get({
				createdAfter: app.items.dateRefresh,
				category: app.shit.items.category,
				feed: app.shit.items.feed,
				subscriber: app.shit.items.subscriber,
				type: app.shit.items.type
			}, function(result) {
				if(result.success) {
					$(".items").prepend(result.items);

					app.items.init();
					app.shit.items.init();

					app.items.countNewAdded += result.count;
					app.items.dateRefresh = result.dateRefresh;
				}
			});
		},

		loadMore: function() {
			$(".items .loadMore").addClass("loading");
			this.get({
				category: app.shit.items.category,
				feed: app.shit.items.feed,
				loadMore: true,
				offset: app.items.countNewAdded,
				page: app.items.page + 1,
				subscriber: app.shit.items.subscriber,
				type: app.shit.items.type
			}, function(result) {
				if(result.success && result.page == app.items.page + 1) {
					$(".items .loadMore").replaceWith(result.items);

					app.items.init();
					app.shit.items.init();

					app.items.page += 1;
				}
			});
		},

		readItem: function(itemId) {
			$.ajax({
				type: "POST",
				url: Routing.generate('ajax_shit_read_item', {id: itemId}),
				dataType: "json"
			}).done(function(result) {
				if(result.success) {
					$(".item[data-item="+itemId+"]").addClass("read");
					app.shit.sidebar.decrementUnreadCountForFeed(result.feed);
				}
			});
		},

		unreadItem: function(itemId) {
			$.ajax({
				type: "POST",
				url: Routing.generate('ajax_shit_unread_item', {id: itemId}),
				dataType: "json"
			}).done(function(result) {
				if(result.success) {
					$(".item[data-item="+itemId+"]").removeClass("read");
					app.shit.sidebar.incrementUnreadCountForFeed(result.feed);
				}
			});
		},

		readFeed: function(feedId) {
			$.ajax({
				type: "POST",
				url: Routing.generate('ajax_shit_read_feed_items', {id: feedId}),
				dataType: "json"
			}).done(function(result) {
				if(result.success) {
					$(".item[data-feed="+feedId+"]").addClass("read");
					app.shit.sidebar.refreshUnreadCounts();
				}
			});
		},

		readCategory: function(categoryId) {
			$.ajax({
				type: "POST",
				url: Routing.generate('ajax_shit_read_category_items', {id: categoryId}),
				dataType: "json"
			}).done(function(result) {
				if(result.success) {
					$(".sidebar .rowChildren[data-parent="+categoryId+"] .row[data-type='feed']").each(function() {
						$(".item[data-feed="+$(this).data("feed")+"]").addClass("read");
					});
					app.shit.sidebar.refreshUnreadCounts();
				}
			});
		},

		readAll: function() {
			var userId = app.shit.items.subscriber;
			if(userId) {
				$.ajax({
					type: "POST",
					url: Routing.generate('ajax_shit_read_user_items', {id: userId}),
					dataType: "json"
				}).done(function(result) {
					if(result.success) {
						$(".item").addClass("read");
						app.shit.sidebar.refreshUnreadCounts();
					}
				});
			}
		},

		starItem: function(itemId) {
			$.ajax({
				type: "POST",
				url: Routing.generate('ajax_shit_star_item', {'id': itemId}),
				dataType: "json"
			}).done(function(result) {
				if(result.success) {
					$(".item[data-item="+itemId+"]").addClass("starred");
					app.shit.sidebar.incrementStarredCount();
				}
			});
		},

		unstarItem: function(itemId) {
			$.ajax({
				type: "POST",
				url: Routing.generate('ajax_shit_unstar_item', {'id': itemId}),
				dataType: "json"
			}).done(function(result) {
				if(result.success) {
					$(".item[data-item="+itemId+"]").removeClass("starred");
					app.shit.sidebar.decrementStarredCount();
				}
			});
		}
	}
}