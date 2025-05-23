{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{* Displays current and upcoming public Events Listing as an HTML page. *}
{include file="CRM/common/jsortable.tpl"}
<div class="crm-section crm-event-list">
  {crmRegion name="crm-event-list-pre"}
  {/crmRegion}

  <table id="options" class="display">
    <thead>
    <tr>
      <th>{ts}Event{/ts}</th>
      <th></th>
      <th>{ts}When{/ts}</th>
      <th>{ts}Location{/ts}</th>
      <th>{ts}Category{/ts}</th>
      <th>{ts}Email{/ts}</th>
      {if $registration_links}<th>{ts}Register{/ts}</th>{/if}
    </tr>
    </thead>
    {foreach from=$events key=uid item=event}
      <tr class="{cycle values="odd-row,even-row"}{if !empty($row.class)} {$row.class}{/if}">
        <td><a href="{crmURL p='civicrm/event/info' q="reset=1&id=`$event.event_id`"}" title="{ts escape='htmlattribute'}read more{/ts}"><strong>{$event.title}</strong></a></td>
        <td>{if $event.summary}{$event.summary|purify} (<a href="{crmURL p='civicrm/event/info' q="reset=1&id=`$event.event_id`"}" title="{ts escape='htmlattribute'}details...{/ts}">{ts}read more{/ts}...</a>){else}&nbsp;{/if}</td>
        <td class="nowrap" data-order="{$event.start_date|crmDate:'%Y-%m-%d'}">
          {if $event.start_date}{$event.start_date|crmDate}{if $event.end_date}<br /><em>{ts}through{/ts}</em><br />{strip}
            {* Only show end time if end date = start date *}
            {if $event.end_date|crmDate:"%Y%m%d" == $event.start_date|crmDate:"%Y%m%d"}
              {$event.end_date|crmDate:0:1}
            {else}
              {$event.end_date|crmDate}
            {/if}{/strip}{/if}
          {else}{ts}(not available){/ts}{/if}
        </td>
        <td>{if $event.is_show_location EQ 1 AND $event.location}{$event.location}{else}{ts}(not available){/ts}{/if}</td>
        <td>{if $event.event_type}{$event.event_type}{else}&nbsp;{/if}</td>
        <td>{if $event.contact_email}<a href="mailto:{$event.contact_email}">{$event.contact_email}</a>{else}&nbsp;{/if}</td>
      </tr>
    {/foreach}
  </table>

  {crmRegion name="crm-event-list-post"}
  {/crmRegion}
</div>
