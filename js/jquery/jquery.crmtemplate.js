(function( $ ){

  /* applies the tpl (a selector of a template) to the data and puts it as the html of the object
   * it triggers two events
   * assign (to be able to alter the data) 
   * render (after the html has been added to the dom) to allow jquery event initialisations
   * options.method = html (or after or before or any html injection jquery method
   *
   */

  $.fn.crmTemplate = function(tpl,data,options) {

    var settings = $.extend( {
      'method': 'html', 
    }, options);

    var mustacheTpl = $(tpl);
    mustacheTpl.trigger ('assign',data);

    return this.each(function() {
      //$(this).html($.mustache(mustacheTpl.html(),data)).trigger('render',data);
      $(this)[settings.method]($.mustache(mustacheTpl.html(),data)).trigger('render',data);
      //mustacheTpl.trigger ('render',data); 
      
    });
  };
})( jQuery );

