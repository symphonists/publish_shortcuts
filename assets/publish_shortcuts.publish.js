(function($) {

	var PublishShortcuts = {
		actions: null,

		init: function() {
			var links = Symphony.Context.get('publish_shortcuts');

			if(links.length) {
				PublishShortcuts.actions = $('#context .actions');
				$.each(links, PublishShortcuts.createButton);
			}
		},

		createButton: function() {
			PublishShortcuts.actions.append(
				'<li><a href="' + PublishShortcuts.processParams(this.link) + '" class="button publish-shortcut">' + this.label + '</a></li>'
			);
		},

		processParams: function(url) {
			return url.replace('{$root}', Symphony.Context.get('root')).replace('{$filter}', location.search.substr(1));
		}
	}

	$(document).on('ready.publishshortcuts', function() {
		PublishShortcuts.init();
	});

})(window.jQuery);
