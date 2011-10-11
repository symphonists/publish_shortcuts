var PublishShortcuts = {
	
	render: function() {
		
		var links = Symphony.Context.get('publish_shortcuts');
		var container = jQuery('#contents h2:first .create:first');
		
		for(var i in links) {
			container.after(
				'<a href="' + this.format_url(links[i].link) + '" class="button publish-shortcut">' + links[i].label + '</a>'
			);
		}
	},
	
	format_url: function(url) {
		url = url.replace('{$root}', Symphony.Context.get('root'));
		url = url.replace('{$filter}', location.search.substr(1,location.search.length));
		return url;
	}
	
};

jQuery(document).ready(function() {
	PublishShortcuts.render();
});