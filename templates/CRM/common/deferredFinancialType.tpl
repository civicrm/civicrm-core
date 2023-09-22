{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{if $deferredFinancialType}
<div id='warningDialog' style="display:none;"></div>
{literal}
<script type="text/javascript">
CRM.$(function($) {
  var more = $('.crm-button.validate').click(function(e) {
    var message = "{/literal} {if $context eq 'Event'}
        {ts escape='js'}Note: Revenue for this event registration will not be deferred as the financial type does not have a deferred revenue account setup for it. If you want the revenue to be deferred, please select a different Financial Type with a Deferred Revenue account setup for it, or setup a Deferred Revenue account for this Financial Type.{/ts}
      {else if $context eq 'MembershipType'}
        {ts escape='js'}Note: Revenue for these types of memberships will not be deferred as the financial type does not have a deferred revenue account setup for it. If you want the revenue to be deferred, please select a different Financial Type with a Deferred Revenue account setup for it, or setup a Deferred Revenue account for this Financial Type.{/ts}
      {/if}
    {literal}";
    var deferredFinancialType = {/literal}{$deferredFinancialType|@json_encode}{literal};
    var financialType = parseInt($('#financial_type_id').val());
    if ($.inArray(financialType, deferredFinancialType) == -1) {
      return confirm(message);
    }
  });
});
</script>
{/literal}
{/if}
