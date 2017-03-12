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
					$(this).attr('data-type'),
					$(this).attr('data-id')
				);
			});

			// Subcategories toggle
			$(".sidebar .row .toggle").off("click");
			$(".sidebar .row .toggle").on("click", function(e) {
				var row = $(this).closest(".row");
				var rowChildren = $(".sidebar .rowChildren[data-parent="+$(this).closest(".row").attr("data-id")+"]");

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
				$(".sidebar").toggleClass("hidden");
				$(".topbar").toggleClass("hidden");
			});

			// Context menu for sidebar
			$(".sidebar .row").off("contextmenu");
			$(".sidebar .row").on("contextmenu", function(e) {
				e.preventDefault();

				$(".contextMenu").hide();
				$(".sidebarRowMenu .option").hide();

				var type = $(this).attr('data-type');
				
				var menu = $(".sidebarRowMenu");
				menu.css({
						left: e.clientX, 
						top: e.clientY + 10
					})
					.attr('data-type', type)
					.removeAttr('data-id');
				
				if(type == 'subscription' || type == 'category') {
					menu.attr('data-id', $(this).attr('data-id'));
				}

				var countOptions = 0;

				// Read option
				if(type == "subscription" || type == "category" || type == "all" || type == "unread") {
					menu.children('.option[data-action="read"]').show();
					countOptions++;
				}

				if(countOptions > 0) {
					menu.show();

					$(".sidebarRowMenu .option").off("click");
					$(".sidebarRowMenu .option").on("click", function(e) {
						var action = $(this).attr("data-action");
						var type = $(this).closest(".sidebarRowMenu").attr("data-type");

						if(action == 'read') {
							if(type == "subscription") {
								app.shit.items.readSubscription($(this).closest(".sidebarRowMenu").attr("data-id"));
							} else if(type == "category") {
								app.shit.items.readCategory($(this).closest(".sidebarRowMenu").attr("data-id"));
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

			this.countUnread();
			this.toggleUnreadCounts();
			this.toggleStarredCount();
		},

		countUnread: function() {
			// Calculate unread counts for categories
			$(".sidebar .row[data-type='category']").each(function() {
				var id = $(this).attr("data-id");
				var unreadCount = 0;

				$(".sidebar .rowChildren[data-parent="+id+"] .row[data-type='subscription'] .unreadCount").each(function() {
					var count = parseInt($(this).text());
					if(!isNaN(count)) {
						unreadCount += count;
					}
				});

				$(".sidebar .row[data-type='category'][data-id="+id+"] .unreadCount").text(unreadCount);
			});

			// Calculate total unread counts
			var totalCount = 0;
			var subscriptions = [];
			
			$(".sidebar .row[data-type='subscription'] .unreadCount").each(function() {
				var subscription = $(this).closest(".row[data-type='subscription']").attr("data-id");
				if($.inArray(subscription, subscriptions) == -1) {
					var count = parseInt($(this).text());
					if(!isNaN(count)) {
						totalCount += count;
					}

					subscriptions.push(subscription);
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

						var subscriptionId = $(this).closest(".row[data-type='subscription']").attr("data-id");
						if(subscriptionId && result.unreadCounts) {
							for(var i=0; i<result.unreadCounts.length; i++) {
								if(result.unreadCounts[i].subscriptionId == subscriptionId) {
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

		decrementUnreadCountForSubscription: function(subscriptionId) {
			$(".sidebar .row[data-type='subscription'][data-id="+subscriptionId+"] .unreadCount").each(function() {
				var count = parseInt($(this).text());
				if(!isNaN(count)) {
					$(this).text(count - 1);
				}
			});

			this.countUnread();
			this.toggleUnreadCounts();
		},

		incrementUnreadCountForSubscription: function(subscriptionId) {
			$(".sidebar .row[data-type='subscription'][data-id="+subscriptionId+"] .unreadCount").each(function() {
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
						$(option).attr('data-type'),
						$(option).attr('data-id')
					);
				}
			});
		}
	},

	selectNode: function(type, id) {
		app.shit.items.type = type;

		$(".sidebar .row").removeClass("selected");
		$(".topbar .catsubs option").prop('selected', false);

		if(type == 'subscription') {
			app.shit.items.subscription = id;
			$(".sidebar .row[data-type='subscription'][data-id="+id+"]").addClass("selected");
			$(".topbar .catsubs option[data-type='subscription'][data-id="+id+"]").prop('selected', true);
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
		subscription: null,
		page: 1,
		type: null,

		intervalLoadNew: null,

		init: function() {
			$(".itemRow").off("click");
			$(".itemRow").on("click", function(e) {
				if(!$(this).hasClass("selected")) {
					e.preventDefault();

					$(".itemRow").removeClass("selected");
					$(this).addClass("selected");

					var itemId = $(this).attr("data-item");
					app.shit.itemContent.show(itemId);

					if(!$(this).hasClass("read")) {
						app.shit.items.readItem(itemId);
					}

					e.stopPropagation();
				}
			});

			if(app.shit.items.dateRefresh == null) {
				app.shit.items.dateRefresh = app.dateLoaded;
			}

			$(".itemRow .readIcon").off("click");
			$(".itemRow .readIcon").on("click", function(e) {
				e.preventDefault();

				var item = $(this).closest(".itemRow");

				if(item.hasClass("read")) {
					app.shit.items.unreadItem(item.attr("data-item"));
				} else {
					app.shit.items.readItem(item.attr("data-item"));
				}

				e.stopPropagation();
			});

			$(".itemRow .starIcon").off("click");
			$(".itemRow .starIcon").on("click", function(e) {
				e.preventDefault();

				var item = $(this).closest(".itemRow");
				
				if(item.hasClass("starred")) {
					app.shit.items.unstarItem(item.attr("data-item"));
				} else {
					app.shit.items.starItem(item.attr("data-item"));
				}

				e.stopPropagation();
			});

			$(".itemRowMore").off("click");
			$(".itemRowMore").on("click", function() {
				app.shit.items.loadMore();
			});

			$(".itemContentContainer .toggle-hide").off("click");
			$(".itemContentContainer .toggle-hide").on("click", function() {
				app.shit.itemContent.hide();

				$(".itemRow").removeClass("selected");
			});

			$(".itemContentContainer .toggle-expand").off("click");
			$(".itemContentContainer .toggle-expand").on("click", function() {
				app.shit.itemContent.expand();
			});

			$(".itemRow").off('contextmenu');
			$(".itemRow").on('contextmenu', function(e) {
				e.preventDefault();

				$(".contextMenu").hide();
				$(".itemMenu .option").hide();

				var item = $(this);

				var menu = $(".itemMenu");
				menu.css({
						left: e.clientX,
						top: e.clientY + 2
					})
					.attr("data-item", item.attr("data-item"));

				if(item.hasClass("read")) {
					menu.children('.option[data-action="unread"]').show();
				} else {
					menu.children('.option[data-action="read"]').show();
				}

				menu.show();

				$(".itemMenu .option").off("click");
				$(".itemMenu .option").on("click", function(e) {
					var action = $(this).attr("data-action");

					var item = $(".itemRow[data-item="+$(this).closest(".itemMenu").attr("data-item")+"]");

					if(action == 'read') {
						app.shit.items.readItem(item.attr("data-item"));
					} else if(action == 'unread') {
						app.shit.items.unreadItem(item.attr("data-item"));
					}

					$(".contextMenu").hide();
				});
			});

			// Get last items every minute
			clearInterval(app.shit.items.intervalLoadNew);
			this.intervalLoadNew = setInterval(function() {
				app.shit.items.loadNew();
			}, 60000);
		},

		getItems: function(data, callback) {
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
			this.getItems({
				category: app.shit.items.category,
				subscription: app.shit.items.subscription,
				load_more: true,
				type: app.shit.items.type
			}, function(result) {
				if(result.success) {
					$(".items").html(result.html);

					app.items.init();
					app.shit.items.init();

					app.shit.items.countNewAdded = 0;
					app.shit.items.dateRefresh = result.dateRefresh;
					app.shit.items.page = 1;
				}
			});
		},

		loadNew: function() {
			this.getItems({
				created_after: app.shit.items.dateRefresh,
				category: app.shit.items.category,
				subscription: app.shit.items.subscription,
				type: app.shit.items.type
			}, function(result) {
				if(result.success) {
					$(".items").prepend(result.html);

					app.items.init();
					app.shit.items.init();

					app.shit.items.countNewAdded += result.count;
					app.shit.items.dateRefresh = result.dateRefresh;
				}
			});
		},

		loadMore: function() {
			$(".itemRowMore").addClass("loading");
			this.getItems({
				category: app.shit.items.category,
				subscription: app.shit.items.subscription,
				load_more: true,
				offset: app.shit.items.countNewAdded,
				page: app.shit.items.page + 1,
				type: app.shit.items.type
			}, function(result) {
				if(result.success && result.page == app.shit.items.page + 1) {
					$(".itemRowMore").replaceWith(result.html);

					app.items.init();
					app.shit.items.init();

					app.shit.items.page += 1;
				}
			});
		},

		readItem: function(itemId) {
			$.ajax({
				type: "POST",
				url: Routing.generate('ajax_shit_read_item'),
				dataType: "json",
				data: { item: itemId }
			}).done(function(result) {
				if(result.success) {
					$(".itemRow[data-item="+itemId+"]").addClass("read");
					app.shit.sidebar.decrementUnreadCountForSubscription($(".itemRow[data-item="+itemId+"]").attr('data-subscription'));
				}
			});
		},

		unreadItem: function(itemId) {
			$.ajax({
				type: "POST",
				url: Routing.generate('ajax_shit_unread_item'),
				dataType: "json",
				data: { item: itemId }
			}).done(function(result) {
				if(result.success) {
					$(".itemRow[data-item="+itemId+"]").removeClass("read");
					app.shit.sidebar.incrementUnreadCountForSubscription($(".itemRow[data-item="+itemId+"]").attr('data-subscription'));
				}
			});
		},

		readSubscription: function(subscriptionId) {
			$.ajax({
				type: "POST",
				url: Routing.generate('ajax_shit_read_subscription'),
				dataType: "json",
				data: { subscription: subscriptionId }
			}).done(function(result) {
				if(result.success) {
					$(".itemRow[data-subscription="+subscriptionId+"]").addClass("read");
					app.shit.sidebar.refreshUnreadCounts();
				}
			});
		},

		readCategory: function(categoryId) {
			$.ajax({
				type: "POST",
				url: Routing.generate('ajax_shit_read_category'),
				dataType: "json",
				data: { category: categoryId }
			}).done(function(result) {
				if(result.success) {
					$(".sidebar .rowChildren[data-parent="+categoryId+"] .row[data-type='subscription']").each(function() {
						$(".itemRow[data-subscription="+$(this).attr("data-id")+"]").addClass("read");
					});
					app.shit.sidebar.refreshUnreadCounts();
				}
			});
		},

		readAll: function() {
			$.ajax({
				type: "POST",
				url: Routing.generate('ajax_shit_read_all'),
				dataType: "json"
			}).done(function(result) {
				if(result.success) {
					$(".itemRow").addClass("read");
					app.shit.sidebar.refreshUnreadCounts();
				}
			});
		},

		starItem: function(itemId) {
			$.ajax({
				type: "POST",
				url: Routing.generate('ajax_shit_star_item'),
				dataType: "json",
				data: { item: itemId }
			}).done(function(result) {
				if(result.success) {
					$(".itemRow[data-item="+itemId+"]").addClass("starred");
					app.shit.sidebar.incrementStarredCount();
				}
			});
		},

		unstarItem: function(itemId) {
			$.ajax({
				type: "POST",
				url: Routing.generate('ajax_shit_unstar_item'),
				dataType: "json",
				data: { item: itemId }
			}).done(function(result) {
				if(result.success) {
					$(".itemRow[data-item="+itemId+"]").removeClass("starred");
					app.shit.sidebar.decrementStarredCount();
				}
			});
		}
	},

	itemContent: {
		currentItemId: null,

		show: function(itemId) {
			$(".shitContainer").addClass("showItemContent");

			if(itemId != app.shit.itemContent.currentItemId) {
				app.shit.itemContent.currentItemId = itemId;

				$(".itemContent").html('<span class="loading"><i class="fa fa-spinner fa-spin"></i></span>');
				
				$.ajax({
					type: "POST",
					url: Routing.generate('ajax_shit_get_item'),
					data: {
						item: itemId
					},
					dataType: "json"
				}).done(function(result) {
					if(result.success && itemId == app.shit.itemContent.currentItemId) {
						$(".itemContent")
							.attr("data-item", itemId)
							.html(result.html);
						app.items.init();
					}
				});
			}
		},

		expand: function() {
			$(".shitContainer").removeClass("showItemContent");
			$(".shitContainer").addClass("expandItemContent");
		},

		hide: function() {
			$(".shitContainer").removeClass("showItemContent");
			$(".shitContainer").removeClass("expandItemContent");
		}
	}
}
