/**
 *  jQuery Fixed Position Plugin
 *  @requires jQuery v1.3
 *  http://www.socialembedded.com/labs
 *
 *  Copyright (c)  Hernan Amiune (hernan.amiune.com)
 *  Dual licensed under the MIT and GPL licenses:
 *  http://www.opensource.org/licenses/mit-license.php
 *  http://www.gnu.org/licenses/gpl.html
 * 
 *  Version: 1.0
 */
 
(function($){ $.fn.fixedPosition = function(options){

    var defaults = {
	  vpos: null,    // posible values: "top", "middle", "bottom". if it is null the original position is taken
	  hpos: null     // posible values: "left", "center", "right". if it is null the original position is taken
	};
	
    var options = $.extend(defaults, options);
	
	return this.each(function(index) {
		
		
		var $this = $(this);
		$this.css("position","absolute");
		
		if(options.vpos === "top"){
		    $this.css("top","0");
		}
		else if(options.vpos === "middle"){
		    $this.css("top",((parseInt($(window).height())/2)-($(this).height()/2))+"px");
		}
		else if(options.vpos === "bottom"){
		    $this.css("bottom","0");
		}
		
		if(options.hpos === "left"){
		    $this.css("left","0");
		}
		else if(options.hpos === "center"){
		    $this.css("left",((parseInt($(window).width())/2)-($(this).width()/2))+"px");
		}
		else if(options.hpos === "right"){
		    $this.css("right","0");
		}
		
		var top = parseInt($this.offset().top);
		var left = parseInt($this.offset().left);
		$(window).scroll(function () {
			$this.css("top",top+$(document).scrollTop()).css("left",left+$(document).scrollLeft());
		});
	});

  
}})(jQuery);