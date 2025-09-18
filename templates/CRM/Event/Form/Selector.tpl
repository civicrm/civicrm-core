{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{if $context EQ 'Search'}
  {include file="CRM/common/pager.tpl" location="top"}
{/if}

{strip}
<table class="selector row-highlight">
<thead class="sticky">
  <tr>
    {if ! $single and $context eq 'Search'}
      <th scope="col" title="{ts escape='htmlattribute'}Select rows{/ts}">{$form.toggleSelect.html}</th>
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
  <tr id='rowid{$row.participant_id}' class="{cycle values="odd-row,even-row"} crm-event crm-event_{$row.event_id}">
    {if ! $single}
      {if $context eq 'Search'}
        {assign var=cbName value=$row.checkbox}
        <td>{$form.$cbName.html}</td>
      {/if}
      <td class="crm-participant-contact_type">{$row.contact_type}</td>
      <td class="crm-participant-sort_name"><a href="{crmURL p='civicrm/contact/view' q="reset=1&cid=`$row.contact_id`"}" title="{ts escape='htmlattribute'}View contact record{/ts}">{$row.sort_name|smarty:nodefaults|purify}</a></td>
    {/if}

    <td class="crm-participant-event_title"><a href="{crmURL p='civicrm/event/info' q="id=`$row.event_id`&reset=1"}" title="{ts escape='htmlattribute'}View event info page{/ts}">{$row.event_title|smarty:nodefaults|purify}</a>
      {if !empty($contactId)}<br /><a href="{crmURL p='civicrm/event/search' q="reset=1&force=1&event=`$row.event_id`"}" title="{ts escape='htmlattribute'}List participants for this event (all statuses){/ts}">({ts}participants{/ts})</a>{/if}
    </td>
    <td class="crm-participant-participant_fee_level">
      {assign var="participant_id" value=$row.participant_id}
      {if !empty($lineItems) && array_key_exists($participant_id, $lineItems)}
        {foreach from=$lineItems.$participant_id item=line name=lineItemsIter}
          {if $line.html_type eq 'Text'}{$line.label}{else}{$line.field_title} - {$line.label}{/if}: {$line.qty}
          {if ! $smarty.foreach.lineItemsIter.last}<br />{/if}
        {/foreach}
      {else}
        {if !$row.paid && (!array_key_exists('participant_fee_level', $row) || !$row.participant_fee_level)} {ts}(no fee){/ts}{elseif array_key_exists('participant_fee_level', $row)}{$row.participant_fee_level}{/if}
      {/if}
    </td>
    <td class="right nowrap crm-participant-participant_fee_amount">{if array_key_exists('participant_fee_amount', $row)}{$row.participant_fee_amount|crmMoney:$row.participant_fee_currency}{/if}</td>
    <td class="crm-participant-participant_register_date">{$row.participant_register_date|truncate:10:''|crmDate}</td>
    <td class="crm-participant-event_start_date">{$row.event_start_date|truncate:10:''|crmDate}
      {if array_key_exists('event_end_date', $row) && $row.event_end_date|crmDate:"%Y%m%d" NEQ $row.event_start_date|crmDate:"%Y%m%d"}
        <br/>- {$row.event_end_date|truncate:10:''|crmDate}
      {/if}
    </td>
    <td class="crm-participant-participant_status crm-participant_status_{$row.participant_status_id}">{$row.participant_status}</td>
    <td class="crm-participant-participant_role">{$row.participant_role_id}</td>
    <td>{$row.action|replace:'xx':$participant_id}</td>
  </tr>
{/foreach}
{* Link to "View all participants" for Dashboard and Contact Summary *}
{if $limit and $pager->_totalItems GT $limit}
  {if $context EQ 'event_dashboard'}
    <tr class="even-row">
    <td colspan="10"><a href="{crmURL p='civicrm/event/search' q='reset=1'}"><i class="crm-i fa-chevron-right" role="img" aria-hidden="true"></i> {ts}Find more event participants{/ts}...</a></td></tr>
    </tr>
  {elseif $context eq 'participant'}
    <tr class="even-row">
    <td colspan="7"><a href="{crmURL p='civicrm/contact/view' q="reset=1&force=1&selectedChild=participant&cid=$contactId"}"><i class="crm-i fa-chevron-right" role="img" aria-hidden="true"></i> {ts}View all events for this contact{/ts}...</a></td></tr>
    </tr>
  {/if}
{/if}
</table>
{/strip}

{if $context EQ 'Search'}
    {include file="CRM/common/pager.tpl" location="bottom"}
{/if}
