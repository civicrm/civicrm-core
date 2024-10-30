{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{if $softCreditRows}
{strip}
{if $context neq 'membership'}
    <table class="form-layout-compressed">
        {include file="CRM/Contribute/Page/ContributionSoftTotals.tpl"}
    </table>
    <p></p>
{/if}
<table class="crm-softcredit-selector crm-ajax-table">
  <thead>
    <tr>
      <th data-data="contributor_name">{ts}Contributor{/ts}</th>
      <th data-data="amount">{ts}Amount{/ts}</th>
      <th data-data="sct_label">{ts}Type{/ts}</th>
      <th data-data="financial_type">{ts}Financial Type{/ts}</th>
      <th data-data="receive_date" class="sorting_desc">{ts}Contribution Date{/ts}</th>
      <th data-data="contribution_status">{ts}Status{/ts}</th>
      <th data-data="pcp_title">{ts}Personal Campaign Page?{/ts}</th>
      <th data-data="links" data-orderable="false">&nbsp;</th>
    </tr>
  </thead>
</table>
{/strip}
{/if}

{if !empty($membership_id) && $context eq 'membership'}
  {assign var="entityID" value=$membership_id}
{/if}

{literal}
<script type="text/javascript">
  (function($) {
    CRM.$('table.crm-softcredit-selector').data({
      "ajax": {
        "url": {/literal}'{crmURL p="civicrm/ajax/softcontributionlist" h=0 q="snippet=4&cid=`$contactId`&context=`$context`&entityID=`$entityID`&isTest=`$isTest`"}'{literal},
      }
    });
  })(CRM.$);
</script>
{/literal}
