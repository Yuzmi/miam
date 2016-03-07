var app = {
	init: function() {
		app.popup.init();

		$(".showLogin").click(function(e) {
			e.preventDefault();
			$(".popupContainer.login").removeClass("hidden");
		});

		$(".showRegister").click(function(e) {
			e.preventDefault();
			$(".popupContainer.register").removeClass("hidden");
		});
	},

	// http://jquery-howto.blogspot.fr/2009/09/get-url-parameters-values-with-jquery.html
	getUrlParams: function() {
		var vars = [], hash;
	    var hashes = window.location.href.slice(window.location.href.indexOf('?') + 1).split('&');
	    for(var i = 0; i < hashes.length; i++) {
	        hash = hashes[i].split('=');
	        vars.push(hash[0]);
	        vars[hash[0]] = hash[1];
	    }
	    return vars;
	},

	popup: {
		init: function() {
			$(".popupContainer").click(function(e) {
				if(!$(e.target).closest('.popup').length) {
					app.popup.closeAll();
				}
			});

			$(".popupContainer .closePopup").click(function(e) {
				e.preventDefault();
				app.popup.closeAll();
			});
		},

		closeAll: function() {
			$(".popupContainer").each(function() {
				if($(this).hasClass("login") || $(this).hasClass("register")) {
					$(this).addClass("hidden");
				} else {
					$(this).remove();
				}
			});
		}
	}
};

$(document).ready(function() {
	if(app.preInit) {
		app.preInit();
	}
	app.init();
});