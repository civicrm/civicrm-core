 {*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.5                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2014                                |
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
*}// http://civicrm.org/licensing
// <script> Generated {$smarty.now|date_format:'%d %b %Y %H:%M:%S'}
{* This file should only contain strings and settings which rarely change *}
(function($) {ldelim}
  // Config settings
  CRM.config.userFramework = {$config->userFramework|@json_encode};
  CRM.config.resourceBase = {$config->resourceBase|@json_encode};
  CRM.config.lcMessages = {$config->lcMessages|@json_encode};

  // Contact create links
  if (CRM.profileCreate !== false) CRM.profileCreate = {$contactCreate|@json_encode};

  // Initialize CRM.url and CRM.formatMoney
  CRM.url({ldelim}back: '{crmURL p="*path*" q="*query*" h=0 fb=1}', front: '{crmURL p="*path*" q="*query*" h=0 fe=1}'{rdelim});
  CRM.formatMoney('init', {$moneyFormat});

  // Localize select2
  $.fn.select2.defaults.formatNoMatches = "{ts escape='js'}None found.{/ts}";
  $.fn.select2.defaults.formatLoadMore = "{ts escape='js'}Loading...{/ts}";
  $.fn.select2.defaults.formatSearching = "{ts escape='js'}Searching...{/ts}";
  $.fn.select2.defaults.formatInputTooShort = function() {ldelim}
    return ($(this).data('api-entity') === 'contact' || $(this).data('api-entity') === 'Contact') ? {$contactSearch} : {$otherSearch};
  {rdelim};

  // Localize jQuery UI
  $.ui.dialog.prototype.options.closeText = "{ts escape='js'}Close{/ts}";

  // Localize strings for jQuery.validate
  var messages = {ldelim}
    required: "{ts escape='js'}This field is required.{/ts}",
    remote: "{ts escape='js'}Please fix this field.{/ts}",
    email: "{ts escape='js'}Please enter a valid email address.{/ts}",
    url: "{ts escape='js'}Please enter a valid URL.{/ts}",
    date: "{ts escape='js'}Please enter a valid date.{/ts}",
    dateISO: "{ts escape='js'}Please enter a valid date (YYYY-MM-DD).{/ts}",
    number: "{ts escape='js'}Please enter a valid number.{/ts}",
    digits: "{ts escape='js'}Please enter only digits.{/ts}",
    creditcard: "{ts escape='js'}Please enter a valid credit card number.{/ts}",
    equalTo: "{ts escape='js'}Please enter the same value again.{/ts}",
    accept: "{ts escape='js'}Please enter a value with a valid extension.{/ts}",
    maxlength: $.validator.format("{ts escape='js'}Please enter no more than {ldelim}0{rdelim} characters.{/ts}"),
    minlength: $.validator.format("{ts escape='js'}Please enter at least {ldelim}0{rdelim} characters.{/ts}"),
    rangelength: $.validator.format("{ts escape='js'}Please enter a value between {ldelim}0{rdelim} and {ldelim}1{rdelim} characters long.{/ts}"),
    range: $.validator.format("{ts escape='js'}Please enter a value between {ldelim}0{rdelim} and {ldelim}1{rdelim}.{/ts}"),
    max: $.validator.format("{ts escape='js'}Please enter a value less than or equal to {ldelim}0{rdelim}.{/ts}"),
    min: $.validator.format("{ts escape='js'}Please enter a value greater than or equal to {ldelim}0{rdelim}.{/ts}")
  };
  $.extend($.validator.messages, messages);
  {literal}

  var params = {
    errorClass: 'crm-inline-error',
    messages: {},
    // TODO: remove after resolution of https://github.com/jzaefferer/jquery-validation/pull/1261
    ignore: ":hidden, [readonly]"
  };

  // use civicrm notifications when there are errors
  params.invalidHandler = function(form, validator) {
    if ($('#crm-notification-container').length) {
      $.each(validator.errorList, function(k, error) {
        $(error.element).crmError(error.message);
      });
    } else {
      alert({/literal}"{ts escape='js'}Please review and correct the highlighted fields before continuing.{/ts}"{literal});
    }
  };

  CRM.validate = {
    _defaults: params,
    params: {},
    functions: []
  };
})(jQuery);
{/literal}
