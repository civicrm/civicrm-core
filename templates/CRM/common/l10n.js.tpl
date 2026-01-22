{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}// http://civicrm.org/licensing
// <script> Generated {$smarty.now|crmDate:'%d %b %Y %H:%M:%S'}
{* This file should only contain strings and settings which rarely change *}
(function($) {ldelim}
  // Config settings
  CRM.config.userFramework = {$config->userFramework|@json_encode nofilter};
  {* resourceBase: The URL of `civicrm-core` assets. Ends with "/". *}
  CRM.config.resourceBase = {$config->userFrameworkResourceURL|@json_encode nofilter};
  {* packageseBase: The URL of `civicrm-packages` assets. Ends with "/". *}
  CRM.config.packagesBase = {capture assign=packagesBase}{crmResURL expr='[civicrm.packages]/'}{/capture}{$packagesBase|@json_encode nofilter};
  CRM.config.lcMessages = {$lcMessages|@json_encode nofilter};
  CRM.config.locale = {$locale|@json_encode nofilter};
  CRM.config.cid = {$cid|@json_encode nofilter};
  $.datepicker._defaults.dateFormat = CRM.config.dateInputFormat = {$dateInputFormat|@json_encode nofilter};
  CRM.config.timeIs24Hr = {if $timeInputFormat eq 2}true{else}false{/if};
  CRM.config.ajaxPopupsEnabled = {$ajaxPopupsEnabled|@json_encode nofilter};
  CRM.config.allowAlertAutodismissal = {$allowAlertAutodismissal|@json_encode nofilter};
  CRM.config.resourceCacheCode = {$resourceCacheCode|@json_encode nofilter};
  CRM.config.quickAdd = {$quickAdd|@json_encode nofilter};

  // Merge entityRef settings
  CRM.config.entityRef = $.extend({ldelim}{rdelim}, {$entityRef|@json_encode nofilter}, CRM.config.entityRef || {ldelim}{rdelim});

  // Initialize CRM.url and CRM.formatMoney
  CRM.url({ldelim}back: '{crmURL p="civicrm/crmajax-placeholder-url-path" q="civicrm-placeholder-url-query=1" h=0 fb=1}', front: '{crmURL p="civicrm/crmajax-placeholder-url-path" q="civicrm-placeholder-url-query=1" h=0 fe=1}'{rdelim});
  CRM.formatMoney('init', false, {$moneyFormat|@json_encode nofilter});

  // Localize select2
  $.fn.select2.defaults.formatNoMatches = "{ts escape='js'}None found.{/ts}";
  $.fn.select2.defaults.formatLoadMore = "{ts escape='js'}Loading...{/ts}";
  $.fn.select2.defaults.formatSearching = "{ts escape='js'}Searching...{/ts}";
  $.fn.select2.defaults.formatInputTooShort = function() {ldelim}
    return ($(this).data('api-entity') === 'contact' || $(this).data('api-entity') === 'Contact') ? {$contactSearch nofilter} : {$otherSearch nofilter};
  {rdelim};

  // Localize jQuery UI
  $.ui.dialog.prototype.options.closeText = "{ts escape='js'}Close{/ts}";

  // Localize jQuery DataTables
  // Note the first two defaults set here aren't localization related,
  // but need to be set globally for all DataTables.
  $.extend( $.fn.dataTable.defaults, {ldelim}
    "searching": false,
    "jQueryUI": true,
    "language": {ldelim}
      "emptyTable": "{ts escape='js'}None found.{/ts}",
      "info":  "{ts escape='js' 1=_START_ 2=_END_ 3=_TOTAL_}Showing %1 to %2 of %3 entries{/ts}",
      "infoEmpty": "{ts escape='js'}Showing 0 to 0 of 0 entries{/ts}",
      "infoFiltered": "{ts escape='js' 1=_MAX_}(filtered from %1 total entries){/ts}",
      "infoPostFix": "",
      "thousands": {$config->monetaryThousandSeparator|json_encode nofilter},
      "lengthMenu": "{ts escape='js' 1=_MENU_}Show %1 entries{/ts}",
      "loadingRecords": " ",
      "processing": " ",
      "zeroRecords": "{ts escape='js'}None found.{/ts}",
      "paginate": {ldelim}
        "first": "{ts escape='js'}First{/ts}",
        "last": "{ts escape='js'}Last{/ts}",
        "next": "{ts escape='js'}Next{/ts}",
        "previous": "{ts escape='js'}Previous{/ts}"
      {rdelim}
    {rdelim}
  {rdelim});

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
    errorClass: 'crm-inline-error alert-danger',
    messages: {},
    ignore: '.select2-offscreen, [readonly], :hidden:not(.crm-select2,.crm-ajax-select), .crm-no-validate',
    ignoreTitle: true,
    errorPlacement: function(error, element) {
      if (element.prop('type') === 'radio') {
        error.appendTo(element.parents('div.content')[0]);
      }
      else {
        error.insertAfter(element);
      }
    }
  };

  // use civicrm notifications when there are errors
  params.invalidHandler = function(form, validator) {
    // If there is no container for display then red text will still show next to the invalid fields
    // but there will be no overall message. Currently the container is only available on backoffice pages.
    if ($('#crm-notification-container').length) {
      $.each(validator.errorList, function(k, error) {
        $(error.element).parents('.crm-custom-accordion.collapsed').crmAccordionToggle();
        $(error.element).parents('.crm-custom-accordion').prop('open', true);
        $(error.element).crmError(error.message);
      });
    }
  };

  CRM.validate = {
    _defaults: params,
    params: {},
    functions: []
  };

})(jQuery);
{/literal}
