 $(document).ready(function(){	
	
	$("#OpenID").OpenIDProviders({
		providers: {
			AOL        : "openid.aol.com/yourname",
			Blogspot   : "yourname.blogspot.com",
			ClaimID    : "claimid.com/yourname",
			Flickr     : "flickr.com",
			Google     : "google.com/accounts/o8/id",
			LiveJournal: "yourname.livejournal.com",
			MyOpenID   : "yourname.myopenid.com",
			Technorati : "technorati.com/people/technorati/yourname",
			Verisign   : "yourname.pip.verisignlabs.com",
			Vidoop     : "yourname.myvidoop.com",
			Vox        : "yourname.vox.com",
			Wordpress  : "yourname.wordpress.com",
			Yahoo      : "yahoo.com"
		}
	});

});
(function($){



    $.fn.extend({
 		OpenIDProviders: function(options) {
		    
		    var defaults = { 
			providers: {}
		    };
			
		    var options = $.extend(defaults, options);
		    
		    /* get providers */
		    var output = "<li id='first'>Select a provider (<a href='http://'>Other OpenID</a>).</li>";
		    var colorChange = 1;
		    for (provider in options.providers) {
			var color = "odd";
			if( colorChange % 2 == 0 ) {
			    var color = "even";   
			}
			output += "<li id='"+ color +"' class='"+ provider +"'><a href='http://"+options.providers[provider] +"'>" + provider +"</a></li>";
			colorChange++;
		    }
		    
		    /* Select a portion of text */
		    
		    function createSelection(start, end, target) {
			
			var field = target;
			/* The IE way - http://msdn.microsoft.com/en-us/library/ms535872(VS.85).aspx */
			
			if ( field.createTextRange ){
			    /* 
			       IE calculates the end of selection range based from the starting point.
			       Most other browsers will calculate end of selection from the beginning
			       of given text node.
			    */
			    
			    var newend   = end - start;
			    var selRange = field.createTextRange();
			    selRange.collapse(true);
			    selRange.moveStart("character", start);
			    selRange.moveEnd("character", newend);
			    selRange.select();
			} else if ( field.setSelectionRange ) {
			    /* For the rest of the browsers (Firefox, Safari, Opera) */
			    field.setSelectionRange(start, end);
			} 
			/* Give the target field focus so selection can be edited by user */
			field.focus();
		    }
		    
		    /* Insert list of providers */
		    
		    return this.each(function() {
			
			var obj = $(this);
			
			obj.after("<ul id='openid-providers'>" + output + "</ul>");
			
			/* Show and hide the provider list */
			$("#openid-providers").hide();
			obj.click(function() {
			    
			    $("#openid-providers").slideDown("fast");	
			});
			
			/* Event delegation for provider links */
			$("#openid-providers a").click(function(e){
			    
			    e.preventDefault();
			    $("#openid-providers").hide();
			    
			    url = $(this).attr("href");

			    start = url.indexOf('yourname');
			    end = start + 8;
			    obj.val(url);
			    
			    /*Select yourname text if it is present */
			    if (start > 0) {
				target = obj[0];
				createSelection(start, end, target);
			    }
			});
			/* Event delegation for other provider links */
			$("#first").click(function(e){
			    e.preventDefault();
			    $("#OpenID").focus();
			});

			$(document.body).click(function(e){
			    var target = $(e.target);
			    if (!target.is("#openid_form")|| target.is("#all_form")){
				$("#openid-providers").hide();
			    }
			    
			}); 

		    });
		}
    });
})(jQuery);
