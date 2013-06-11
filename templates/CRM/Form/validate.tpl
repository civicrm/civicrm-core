{*
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
*}

{* Initialize jQuery validate *}
{* Extra params and functions may be added to the CRM.validate object before this template is loaded *}

{literal}
<script type="text/javascript" >
cj(function($) {
  var params = {
    'errorClass': 'crm-inline-error',
    messages: {{/literal}
      required: "{ts escape='js'}This field is required.{/ts}",
      remote: "{ts escape='js'}Please fix this field.{/ts}",
      email: "{ts escape='js'}Please enter a valid email address.{/ts}",
      url: "{ts escape='js'}Please enter a valid URL.{/ts}",
      date: "{ts escape='js'}Please enter a valid date.{/ts}",
      dateISO: "{ts escape='js'}Please enter a valid date (ISO).{/ts}",
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
    {literal}}
  };

  // use civicrm notifications when there are errors
  params.invalidHandler = function(form, validator) {
    var errors = validator.errorList;
    {/literal}{if !$urlIsPublic}{literal}
      for (var i in errors) {
        $(errors[i].element).crmError(errors[i].message);
      }
    {/literal}{else}
      alert("{ts escape='js'}Please review and correct the highlighted fields before continuing.{/ts}");
    {/if}{literal}
  };

  CRM.validate.params = CRM.validate.params || {};
  $.extend(CRM.validate.params, params);

  {/literal}
  {if $form && $form.formName}
    $("#{$form.formName}").validate(params);
    {literal}
    // Call any post-initialization callbacks
    if (CRM.validate && CRM.validate.functions.length) {
      $.each(CRM.validate.functions, function(i, func) {
        func();
      });
    }
    {/literal}
  {/if}
});
</script>
