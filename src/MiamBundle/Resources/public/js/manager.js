app.manager = {
	init: function() {
		$(".tab .header").click(function() {
			$(this).siblings(".content").toggle();
		});

		var params = app.getUrlParams();
		if(params.tab) {
			$(".tab."+params.tab+" .content").show();
		}

		this.catsubs.init();
	},

	catsubs: {
		init: function() {
			$(".catsubs .category .toggle").click(function(e) {
				var category = $(this).closest('.category').data('category');
				$(".tab.catsubs .rowChildren[data-category="+category+"]").toggle();
			});

			$(".catsubs .createCategory").click(function() {
				$.ajax({
					type: "POST",
					url: Routing.generate("ajax_manager_popup_category_new"),
					dataType: "json"
				}).done(function(result) {
					if(result.success) {
						$("body").append(result.html);
						app.popup.init();
					}
				});
			});

			$(".catsubs .createSubscription").click(function() {
				$.ajax({
					type: "POST",
					url: Routing.generate("ajax_manager_popup_subscription_new"),
					dataType: "json"
				}).done(function(result) {
					if(result.success) {
						$("body").append(result.html);
						app.popup.init();
					}
				});
			});

			$(".catsubs .category .edit").click(function() {
				$.ajax({
					type: "POST",
					url: Routing.generate("ajax_manager_popup_category_edit", {id: $(this).closest(".category").data("category")}),
					dataType: "json"
				}).done(function(result) {
					if(result.success) {
						$("body").append(result.html);
						app.popup.init();
					}
				});
			});

			$(".catsubs .subscription .edit").click(function() {
				$.ajax({
					type: "POST",
					url: Routing.generate("ajax_manager_popup_subscription_edit", {id: $(this).closest(".subscription").data("subscription")}),
					dataType: "json"
				}).done(function(result) {
					if(result.success) {
						$("body").append(result.html);
						app.popup.init();
					}
				});
			});

			$(".catsubs .category .delete").click(function() {
				$.ajax({
					type: "POST",
					url: Routing.generate("ajax_manager_popup_category_delete", {id: $(this).closest(".category").data("category")}),
					dataType: "json"
				}).done(function(result) {
					if(result.success) {
						$("body").append(result.html);
						app.popup.init();
					}
				});
			});

			$(".catsubs .subscription .delete").click(function() {
				$.ajax({
					type: "POST",
					url: Routing.generate("ajax_manager_popup_subscription_delete", {id: $(this).closest(".subscription").data("subscription")}),
					dataType: "json"
				}).done(function(result) {
					if(result.success) {
						$("body").append(result.html);
						app.popup.init();
					}
				});
			});

			this.indent();
		},

		indent: function() {
			$(".catsubs .row").each(function() {
				var indentation = ($(this).parents(".rowChildren").length + 1) * 1.5;
				$(this).css('padding-left', indentation+"rem");
			});
		}
	}
};