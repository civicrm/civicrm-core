/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.3                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2013                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
*/

jQuery(function($) {
  $('#crm-wp-insert-shortcode').on('click', function() {
	var form_id = $("#add_civicomponent_id").val();
	if (form_id == ""){
	  alert ('Please select a frontend element.');
	  return;
	}

	var component = $("#add_civicomponent_id").val( );
	var shortcode = '[civicrm component="' + component + '"';

	switch (component) {
	  case 'contribution':
		shortcode += ' id="' + $("#add_contributepage_id").val() + '"';
		shortcode += ' mode="' + $("input[name='component_mode']:checked").val() + '"';
		break;
	  case 'event':
		shortcode += ' id="' + $("#add_eventpage_id").val() + '"';
		shortcode += ' action="' + $("input[name='event_action']:checked").val() + '"';
		shortcode += ' mode="' + $("input[name='component_mode']:checked").val() + '"';
		break;
	  case 'profile':
		shortcode += ' gid="' + $("#add_profilepage_id").val() + '"';
		shortcode += ' mode="' + $("input[name='profile_mode']:checked").val() + '"';
		break;
	  case 'user-dashboard':
		break;
	}
	shortcode += ']';
	window.send_to_editor( shortcode );
  });

  $('#add_civicomponent_id').on('change', function() {
	switch ($(this).val()) {
	  case 'contribution':
		$('#contribution-section, #component-section').show();
		$('#profile-section, #profile-mode-section').hide();
		$('#event-section, #action-section-event').hide();
		break;
	  case 'event':
		$('#contribution-section').hide();
		$('#profile-section, #profile-mode-section').hide();
		$('#event-section, #component-section, #action-section-event').show();
		break;
	  case 'profile':
		$('#contribution-section, #component-section').hide();
		$('#profile-section, #profile-mode-section').show();
		$('#event-section, #action-section-event').hide();
		break;
	  default:
		$('#contribution-section, #event-section, #component-section, #action-section-event').hide();
		$('#profile-section, #profile-mode-section').hide();
		break;
	}
  });
});
