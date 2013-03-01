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
  <tr class="columnheader">
  {if ! $single and $context eq 'Search' }
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
  <tr id='crm-grant_{$row.grant_id}' class="{cycle values="odd-row,even-row"} crm-grant crm-grant_status-{$row.grant_status_id}">

  {if !$single }  
     {if $context eq 'Search' }       
        {assign var=cbName value=$row.checkbox}
        <td>{$form.$cbName.html}</td> 
     {/if} 
    <td>{$row.contact_type}</td>	
    <td><a href="{crmURL p='civicrm/contact/view' q="reset=1&cid=`$row.contact_id`"}">{$row.sort_name}</a></td> 
  {/if}
    <td class="crm-grant-grant_status">{$row.grant_status}</td>
    <td class="crm-grant-grant_type">{$row.grant_type}</td>
    <td class="right crm-grant-grant_amount_total">{$row.grant_amount_total|crmMoney}</td>
    <td class="right crm-grant-grant_amount_granted">{$row.grant_amount_granted|crmMoney}</td>
    <td class="right crm-grant-grant_application_received_date">{$row.grant_application_received_date|truncate:10:''|crmDate}</td>
    <td class="crm-grant-grant_report_received">{if $row.grant_report_received}{ts}Yes{/ts}{else}{ts}No{/ts}{/if}</td>
    <td class="right crm-grant-grant_money_transfer_date">{$row.grant_money_transfer_date|truncate:10:''|crmDate}</td>
    <td>{$row.action|replace:'xx':$row.grant_id}</td>
   </tr>
  {/foreach}

{if ($context EQ 'dashboard') AND $pager->_totalItems GT $limit}
  <tr class="even-row">
    <td colspan="9"><a href="{crmURL p='civicrm/grant/search' q='reset=1&force=1'}">&raquo; {ts}List more Grants{/ts}...</a></td></tr>
  </tr>
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
