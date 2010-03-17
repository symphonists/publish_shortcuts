/* */

var Shortcuts;
(function($) {
  Shortcuts = function()
  {
    if (Shortcuts == this) { return new Shortcuts(); }
    
    var p = {
      section: null
    };
    
    function init() {
      buildShortcuts();
    }
  
    function buildShortcuts(section) {
      var buttons = [];
      $(shortcuts_links).each(function(i)
      {
        var link = this.link.replace('{$root}', Symphony.WEBSITE);
        buttons[i] = '<a class="button shortcuts" href="'+link+'">'+this.label+'</a>';
      });
      $("h2 .create").after(buttons.join(''));
    }
    
    init();
  }
})(jQuery);


jQuery(document).ready(function()
{
  if (shortcuts_links.length > 0) Shortcuts();
});