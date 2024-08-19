{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{* CiviGrant DashBoard (launch page) *}
<div class="help">
    {capture assign=findContactURL}{crmURL p="civicrm/contact/search/basic" q="reset=1"}{/capture}
    <p>{ts 1=$findContactURL}CiviGrant allows you to input and track grants to Organizations, Individuals or Households. The grantee must first be entered as a contact in CiviCRM. Use <a href='%1'>Find Contacts</a> to see if there's already a record for the grantee. Once you've located or created the contact record, click <strong>View</strong> to go to their summary page, select the <strong>Grants</strong> tab and click <strong>New Grant</strong>.{/ts}
    </p>
</div>
<h3>{ts}Grants Summary{/ts}</h3>
<div class="description">
    {capture assign=findGrantsURL}{crmURL p="civicrm/grant/search" q="reset=1"}{/capture}
    <p>{ts 1=$findGrantsURL}This table provides a summary of <strong>Grant Totals</strong>, and includes shortcuts to view the Grant details for these commonly used search periods. Click the Grant Status to see a list of Contacts for that grant status. To run your own customized searches - click <a href='%1'>Find Grants</a>. You can search by Contact Name, Amount, Grant type and a variety of other criteria.{/ts}
    </p>
</div>

{if $grantSummary.total_grants}
You have {$grantSummary.total_grants} grant(s) registered in your database.
<table class="report">
<tr class="columnheader-dark">
    <th scope="col">{ts}Grant status{/ts}</th>
    <th scope="col">{ts}Number of grants{/ts}</th>
</tr>

{foreach from=$grantSummary.per_status item=status key=id}
<tr>
    <td><a href="{crmURL p="civicrm/grant/search" q="reset=1&grant_status_id=`$id`&force=1"}">{$status.label}</a></td>
    <td><a href="{crmURL p="civicrm/grant/search" q="reset=1&grant_status_id=`$id`&force=1"}">{$status.total}</a></td>
</tr>
{/foreach}
<tr class="columnfooter">
    <td>{ts}TOTAL{/ts}:</td>
    <td>{$grantSummary.total_grants}</td>
</tr>
</table>
{else}
{ts}You have no Grants registered in your database.{/ts}

{/if}


{if $pager->_totalItems}

    <h3>{ts}Recent Grants{/ts}</h3>
    <div class="form-item">
        {include file="CRM/Grant/Form/Selector.tpl" context="DashBoard"}
    </div>
{/if}
