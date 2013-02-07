var PublishShortcuts = {
	
	render: function() {
		
		var links = Symphony.Context.get('publish_shortcuts');
		var container = jQuery('#context .actions');

		for(var i in links) {
			container.append(
				'<li><a href="' + this.format_url(links[i].link) + '" class="button drawer horizontal publish-shortcut">' + links[i].label + '</a></li>'
			);
		}
	},
	
	format_url: function(url) {
		url = url.replace('{$root}', Symphony.Context.get('root'));
		return url;
	}
	
};

jQuery(document).ready(function() {
	PublishShortcuts.render();
});
