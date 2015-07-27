/* jQuery POST/GET redirect method
   v.0.1
   made by Nemanja Avramovic, www.avramovic.info 
   */

(function( $ ){

	$.fn.redirect = function( target, values, method ) {  

		if (method !== undefined)
		{
			method = method.toUpperCase();
		
			if (method != 'GET')
				method = 'POST';
		}
		else
			method = 'POST';
			
		if (values === undefined || values == false)
		{
			var obj = $().parse_url(target);
			target = obj.url;
			values = obj.params;
		}
					
		var form = $('<form></form');
		form.attr('method', method);
		form.attr('action', target);
		
		for(var i in values)
		{
			var input = $('<input />');
			input.attr('type', 'hidden');
			input.attr('name', i);
			input.attr('value', values[i]);
			input.appendTo(form);
		}
		
		$('body').append(form);
		form.submit();

	};
	
	$.fn.parse_url = function(url)
	{
		if (url.indexOf('?') == -1)
			return { url: url, params: {} }
			
		var parts = url.split('?');
		var url = parts[0];
		var query_string = parts[1];
		
		var return_obj = {};
		var elems = query_string.split('&');
		
		var obj = {};
		
		for(var i in elems)
		{
			var elem = elems[i];
			var pair = elem.split('=');
			obj[pair[0]] = pair[1];
		}
		
		return_obj.url = url;
		return_obj.params = obj;
		
		return return_obj;
		
	}
  
	
})( jQuery );