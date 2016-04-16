{*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2016                                |
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
{* CiviGrant DashBoard (launch page) *}
<div id="help" class="solid-border-bottom">
    {capture assign=findContactURL}{crmURL p="civicrm/contact/search/basic" q="reset=1"}{/capture}
    <p>{ts 1=$findContactURL }CiviGrant allows you to input and track grants to Organizations, Individuals or Households. The grantee must first be entered as a contact in CiviCRM. Use <a href='%1'>Find Contacts</a> to see if there's already a record for the grantee. Once you've located or created the contact record, click <strong>View</strong> to go to their summary page, select the <strong>Grants</strong> tab and click <strong>New Grant</strong>.{/ts}
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
    <td><a href="{crmURL p="civicrm/grant/search" q="reset=1&status=`$id`&force=1"}">{$status.label}</a></td>
    <td><a href="{crmURL p="civicrm/grant/search" q="reset=1&status=`$id`&force=1"}">{$status.total}</a></td>
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
