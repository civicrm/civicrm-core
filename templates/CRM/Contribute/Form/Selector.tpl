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
{if $context EQ 'Search'}
    {include file="CRM/common/pager.tpl" location="top"}
{/if}

{strip}
<table class="selector">
  <thead class="sticky">
  <tr>
    {if !$single and $context eq 'Search' }
        <th scope="col" title="Select Rows">{$form.toggleSelect.html}</th>
    {/if}
    {foreach from=$columnHeaders item=header}
        <th scope="col">
        {if $header.sort}
          {assign var='key' value=$header.sort}
          {$sort->_response.$key.link}
        {else}
          {$header.name}
        {/if}
        </th>
    {/foreach}
  </tr>
  </thead>

  {counter start=0 skip=1 print=false}
  {foreach from=$rows item=row}
  <tr id="rowid{$row.contribution_id}" class="{cycle values="odd-row,even-row"}{if $row.cancel_date} cancelled{/if} crm-contribution_{$row.contribution_id}">
    {if !$single }
        {if $context eq 'Search' }
          {assign var=cbName value=$row.checkbox}
          <td>{$form.$cbName.html}</td>
   {/if}
    <td>{$row.contact_type}</td>
      <td><a href="{crmURL p='civicrm/contact/view' q="reset=1&cid=`$row.contact_id`"}">{$row.sort_name}</a></td>
    {/if}
    <td class="right bold crm-contribution-amount"><span class="nowrap">{$row.total_amount|crmMoney:$row.currency}</span> {if $row.amount_level }<br /> ({$row.amount_level}){/if}
    {if $row.contribution_recur_id}
     <br /> {ts}(Recurring Contribution){/ts}
    {/if}
    </td>
    <td class="crm-contribution-type crm-contribution-type_{$row.financial_type_id} crm-financial-type crm-financial-type_{$row.financial_type_id}">{$row.financial_type}</td>
    <td class="crm-contribution-source">{$row.contribution_source}</td>
    <td class="crm-contribution-receive_date">{$row.receive_date|crmDate}</td>
    <td class="crm-contribution-thankyou_date">{$row.thankyou_date|crmDate}</td>
    <td class="crm-contribution-status">
        {$row.contribution_status}<br />
        {if $row.cancel_date}
        {$row.cancel_date|crmDate}
        {/if}
    </td>
    <td class="crm-contribution-product_name">{$row.product_name}</td>
    <td>{$row.action|replace:'xx':$row.contribution_id}</td>
  </tr>
  {/foreach}

{* Link to "View all contributions" for Contact Summary selector display *}
{if $limit and $pager->_totalItems GT $limit }
  {if $context eq 'dashboard' }
      <tr class="even-row">
      <td colspan="10"><a href="{crmURL p='civicrm/contribute/search' q='reset=1'}">&raquo; {ts}Find more contributions{/ts}... </a></td>
      </tr>
  {elseif $context eq 'contribution' }
      <tr class="even-row">
      <td colspan="8"><a href="{crmURL p='civicrm/contact/view' q="reset=1&force=1&selectedChild=contribute&cid=$contactId"}">&raquo; {ts}View all contributions from this contact{/ts}... </a></td>
      </tr>
  {/if}
{/if}
</table>
{/strip}

{if $context EQ 'Search'}
 <script type="text/javascript">
 {* this function is called to change the color of selected row(s) *}
    var fname = "{$form.formName}";
    on_load_init_checkboxes(fname);
 </script>
{/if}

{if $context EQ 'Search'}
    {include file="CRM/common/pager.tpl" location="bottom"}
{/if}
