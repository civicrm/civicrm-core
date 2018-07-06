{*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2018                                |
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
{if $softCreditRows}
{strip}
{if $context neq 'membership'}
    <table class="form-layout-compressed">
        <tr>
          {if $softCreditTotals.amount}
            <th class="contriTotalLeft">{ts}Total Soft Credits{/ts} &ndash; {$softCreditTotals.amount|crmMoney:$softCreditTotals.currency}</th>
            <th class="right" width="10px"> &nbsp; </th>
            <th class="right contriTotalRight"> &nbsp; {ts}Avg Soft Credits{/ts} &ndash; {$softCreditTotals.avg|crmMoney:$softCreditTotals.currency}</th>
          {/if}
          {if $softCreditTotals.cancelAmount}
            <th class="right contriTotalRight"> &nbsp; {ts}Total Cancelled Soft Credits{/ts} &ndash; {$softCreditTotals.cancelAmount|crmMoney:$softCreditTotals.currency}</th>
          {/if}
        </tr>
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
      <th data-data="receive_date" class="sorting_desc">{ts}Received{/ts}</th>
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
