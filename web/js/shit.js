app.shit = {
	init: function() {
		this.sidebar.init();
		this.topbar.init();
		this.items.init();

		// Hide context menus
		$(document).on("click", function(e) {
			if(e.which != 3) {
				$(".contextMenu").hide();
			}
		});
	},

	sidebar: {
		intervalRefreshUnreadCounts: null,

		init: function() {
			// Indentation des catégories
			$(".sidebar .row").each(function() {
				$(this).children(".name").css('padding-left', ($(this).parents(".rowChildren").length * 1.5)+"rem");
			});

			// Sélection d'un flux ou d'une catégorie
			$(".sidebar .row").off("click");
			$(".sidebar .row").on("click", function(e) {
				app.shit.selectNode(
					$(this).data('type'),
					$(this).data('id')
				);
			});

			// Subcategories toggle
			$(".sidebar .row .toggle").off("click");
			$(".sidebar .row .toggle").on("click", function(e) {
				var row = $(this).closest(".row");
				var rowChildren = $(".sidebar .rowChildren[data-parent="+$(this).closest(".row").data("id")+"]");

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
			$(".sidebar_toggle").off("click");
			$(".sidebar_toggle").on("click", function() {
				$("body").toggleClass("hide_sidebar");
			});

			if(app.user && app.shit.items.subscriber && app.user.id == app.shit.items.subscriber) {
				// Context menu for feeds and categories
				$(".sidebar .row").contextmenu(function(e) {
					e.preventDefault();

					$(".contextMenu").hide();
					$(".sidebarRowMenu .option").hide();

					var type = $(this).data('type');
					
					var menu = $(".sidebarRowMenu");
					menu.css({
							left: e.clientX, 
							top: e.clientY + 10
						})
						.attr('data-type', type)
						.removeAttr('data-id');

					if(type == 'feed' || type == 'category') {
						menu.attr('data-id', $(this).data('id'));
					}

					var countOptions = 0;

					// Read option
					if(type == "feed" || type == "category" || type == "all" || type == "unread") {
						menu.children('.option[data-action="read"]').show();
						countOptions++;
					}

					if(countOptions > 0) {
						menu.show();

						$(".sidebarRowMenu .option").off("click");
						$(".sidebarRowMenu .option").on("click", function(e) {
							var action = $(this).data("action");
							var type = $(this).closest(".sidebarRowMenu").data("type");

							if(action == 'read') {
								if(type == "feed") {
									app.shit.items.readFeed($(this).closest(".sidebarRowMenu").data("id"));
								} else if(type == "category") {
									app.shit.items.readCategory($(this).closest(".sidebarRowMenu").data("id"));
								} else if(type == "all" || type == "unread") {
									app.shit.items.readAll();
								}
							}

							$(".contextMenu").hide();
						});
					}
				});

				// Refresh unread counts every minute
				clearInterval(app.shit.sidebar.intervalRefreshUnreadCounts);
				this.intervalRefreshUnreadCounts = setInterval(function() {
					app.shit.sidebar.refreshUnreadCounts();
				}, 60000);
			}

			this.countUnread();
			this.toggleUnreadCounts();
			this.toggleStarredCount();
		},

		countUnread: function() {
			// Calculate unread counts for categories
			$(".sidebar .row[data-type='category']").each(function() {
				var id = $(this).data("id");
				var unreadCount = 0;

				$(".sidebar .rowChildren[data-parent="+id+"] .row[data-type='feed'] .unreadCount").each(function() {
					var count = parseInt($(this).text());
					if(!isNaN(count)) {
						unreadCount += count;
					}
				});

				$(".sidebar .row[data-type='category'][data-id="+id+"] .unreadCount").text(unreadCount);
			});

			// Calculate total unread counts
			var totalCount = 0;
			var feeds = [];
			
			$(".sidebar .row[data-type='feed'] .unreadCount").each(function() {
				var feed = $(this).closest(".row[data-type='feed']").data("id");
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

						var feedId = $(this).closest(".row[data-type='feed']").data("id");
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

		decrementUnreadCountForFeed: function(feedId) {
			$(".sidebar .row[data-type='feed'][data-id="+feedId+"] .unreadCount").each(function() {
				var count = parseInt($(this).text());
				if(!isNaN(count)) {
					$(this).text(count - 1);
				}
			});

			this.countUnread();
			this.toggleUnreadCounts();
		},

		incrementUnreadCountForFeed: function(feedId) {
			$(".sidebar .row[data-type='feed'][data-id="+feedId+"] .unreadCount").each(function() {
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

	topbar: {
		init: function() {
			$(".topbar .catsubs option:first").prop('selected', true);

			$(".topbar .catsubs").off("change");
			$(".topbar .catsubs").on("change", function(e) {
				var option = $("option:selected", this);
				if(option) {
					app.shit.selectNode(
						$(option).data('type'),
						$(option).data('id')
					);
				}
			});
		}
	},

	selectNode: function(type, id) {
		app.shit.items.type = type;

		$(".sidebar .row").removeClass("selected");
		$(".topbar .catsubs option").prop('selected', false);

		if(type == 'feed') {
			app.shit.items.feed = id;
			$(".sidebar .row[data-type='feed'][data-id="+id+"]").addClass("selected");
			$(".topbar .catsubs option[data-type='feed'][data-id="+id+"]").prop('selected', true);
		} else if(type == 'category') {
			app.shit.items.category = id;
			$(".sidebar .row[data-type='category'][data-id="+id+"]").addClass("selected");
			$(".topbar .catsubs option[data-type='category'][data-id="+id+"]").prop('selected', true);
		} else {
			$(".sidebar .row[data-type="+type+"]").addClass("selected");
			$(".topbar .catsubs option[data-type="+type+"]").prop('selected', true);
		}

		app.shit.items.refresh();
	},

	items: {
		category: null,
		countNewAdded: 0,
		dateRefresh: null,
		feed: null,
		page: 1,
		subscriber: null,
		type: null,

		intervalLoadNew: null,

		init: function() {
			app.items.onExpand = function(item) {
				if(!item.hasClass("read")) {
					app.shit.items.readItem(item.data("item"));
				}
			}

			if(app.shit.items.dateRefresh == null) {
				app.shit.items.dateRefresh = app.dateLoaded;
			}

			$(".item .star").off("click");
			$(".item .star").on("click", function(e) {
				e.preventDefault();

				var item = $(this).closest(".item");
				
				if(item.hasClass("starred")) {
					app.shit.items.unstarItem(item.data("item"));
				} else {
					app.shit.items.starItem(item.data("item"));
				}

				e.stopPropagation();
			});

			$(".items .loadMore").off("click");
			$(".items .loadMore").on("click", function() {
				app.shit.items.loadMore();
			});

			if(app.user && app.shit.items.subscriber && app.user.id == app.shit.items.subscriber) {
				$(".item .header").contextmenu(function(e) {
					e.preventDefault();

					$(".contextMenu").hide();
					$(".itemMenu .option").hide();

					var item = $(this).closest(".item");

					var menu = $(".itemMenu");
					menu.css({
							left: e.clientX,
							top: e.clientY + 10
						})
						.data("item", item.data("item"));

					if(item.hasClass("read")) {
						menu.children('.option[data-action="unread"]').show();
					} else {
						menu.children('.option[data-action="read"]').show();
					}

					menu.show();

					$(".itemMenu .option").off("click");
					$(".itemMenu .option").on("click", function(e) {
						var action = $(this).data("action");

						var item = $(".item[data-item="+$(this).closest(".itemMenu").data("item")+"]");

						if(action == 'read') {
							app.shit.items.readItem(item.data("item"));
						} else if(action == 'unread') {
							app.shit.items.unreadItem(item.data("item"));
						}

						$(".contextMenu").hide();
					});
				});
			}

			// Get last items every minute
			clearInterval(app.shit.items.intervalLoadNew);
			this.intervalLoadNew = setInterval(function() {
				app.shit.items.loadNew();
			}, 60000);
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

					app.shit.items.countNewAdded = 0;
					app.shit.items.dateRefresh = result.dateRefresh;
					app.shit.items.page = 1;
				}
			});
		},

		loadNew: function() {
			this.get({
				createdAfter: app.shit.items.dateRefresh,
				category: app.shit.items.category,
				feed: app.shit.items.feed,
				subscriber: app.shit.items.subscriber,
				type: app.shit.items.type
			}, function(result) {
				if(result.success) {
					$(".items").prepend(result.items);

					app.items.init();
					app.shit.items.init();

					app.shit.items.countNewAdded += result.count;
					app.shit.items.dateRefresh = result.dateRefresh;
				}
			});
		},

		loadMore: function() {
			$(".items .loadMore").addClass("loading");
			this.get({
				category: app.shit.items.category,
				feed: app.shit.items.feed,
				loadMore: true,
				offset: app.shit.items.countNewAdded,
				page: app.shit.items.page + 1,
				subscriber: app.shit.items.subscriber,
				type: app.shit.items.type
			}, function(result) {
				if(result.success && result.page == app.shit.items.page + 1) {
					$(".items .loadMore").replaceWith(result.items);

					app.items.init();
					app.shit.items.init();

					app.shit.items.page += 1;
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