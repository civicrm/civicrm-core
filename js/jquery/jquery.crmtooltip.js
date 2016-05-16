/*
* +--------------------------------------------------------------------+
* | CiviCRM version 4.2                                                |
* +--------------------------------------------------------------------+
* | Copyright CiviCRM LLC (c) 2004-2012                                |
* +--------------------------------------------------------------------+
* | This file is a part of CiviCRM.                                    |
* |                                                                    |
* | CiviCRM is free software; you can copy, modify, and distribute it  |
* | under the terms of the GNU Affero General Public License           |
* | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
* |                                                                    |
* | CiviCRM is distributed in the hope that it will be useful, but     |
* | WITHOUT ANY WARRANTY; without even the implied warranty of         |
* | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
* | See the GNU Affero General Public License for more details.        |
* |                                                                    |
* | You should have received a copy of the GNU Affero General Public   |
* | License and the CiviCRM Licensing Exception along                  |
* | with this program; if not, contact CiviCRM LLC                     |
* | at info[AT]civicrm[DOT]org. If you have questions about the        |
* | GNU Affero General Public License or the licensing of CiviCRM,     |
* | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
* +--------------------------------------------------------------------+
*/ 
(function($){ $.fn.crmtooltip = function(){
	$('a.crm-summary-link')
	.addClass('crm-processed')
	.live('mouseover',
		function(e)  {
		    $(this).addClass('crm-tooltip-active');
		    topDistance = e.pageY - $(window).scrollTop();
		    if (topDistance < 300 | topDistance < $(this).children('.crm-tooltip-wrapper').height()) {
		          $(this).addClass('crm-tooltip-down');
		      }
			if ($(this).children('.crm-tooltip-wrapper').length == '') {
				$(this).append('<div class="crm-tooltip-wrapper"><div class="crm-tooltip"></div></div>');
				$(this).children().children('.crm-tooltip')
					.html('<div class="crm-loading-element"></div>')
					.load(this.href);
			}
		})
    .live('mouseout',
		function(){
		  $(this).removeClass('crm-tooltip-active');
		  $(this).removeClass('crm-tooltip-down');
		  }
		)
	.live('click',
		function(){
		  return false;
		  }
		);	
	
	};
	})(jQuery);

