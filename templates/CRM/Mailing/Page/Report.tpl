{*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2015                                |
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
<fieldset>
<legend>{ts}Delivery Summary{/ts}</legend>
{if $report.jobs.0.start_date}
  {strip}
  <table class="crm-info-panel">
    <tr><td class="label"><a href="{$report.event_totals.links.queue}">{ts}Intended Recipients{/ts}</a></td>
        <td>{$report.event_totals.queue}</td>
        <td>{$report.event_totals.actionlinks.queue}</td></tr>
    <tr><td class="label"><a href="{$report.event_totals.links.delivered}">{ts}Successful Deliveries{/ts}</a></td>
        <td>{$report.event_totals.delivered} ({$report.event_totals.delivered_rate|string_format:"%0.2f"}%)</td>
        <td>{$report.event_totals.actionlinks.delivered}</td></tr>
  {if $report.mailing.open_tracking}
    <tr><td class="label"><a href="{$report.event_totals.links.opened}&distinct=1">{ts}Unique Opens{/ts}</a></td>
        <td>{$report.event_totals.opened}</td>
        <td>{$report.event_totals.actionlinks.opened}</td></tr>
    <tr><td class="label"><a href="{$report.event_totals.links.opened}">{ts}Total Opens{/ts}</a></td>
        <td>{$report.event_totals.total_opened}</td>
        <td>{$report.event_totals.actionlinks.opened}</td></tr>
  {/if}
  {if $report.mailing.url_tracking}
    <tr><td class="label"><a href="{$report.event_totals.links.clicks}">{ts}Click-throughs{/ts}</a></td>
        <td>{$report.event_totals.url}</td>
        <td>{$report.event_totals.actionlinks.clicks}</td></tr>
  {/if}
  <tr><td class="label"><a href="{$report.event_totals.links.forward}">{ts}Forwards{/ts}</a></td>
      <td>{$report.event_totals.forward}</td>
      <td>{$report.event_totals.actionlinks.forward}</td></tr>
  <tr><td class="label"><a href="{$report.event_totals.links.reply}">{ts}Replies{/ts}</a></td>
      <td>{$report.event_totals.reply}</td>
      <td>{$report.event_totals.actionlinks.reply}</td></tr>
  <tr><td class="label"><a href="{$report.event_totals.links.bounce}">{ts}Bounces{/ts}</a></td>
      <td>{$report.event_totals.bounce} ({$report.event_totals.bounce_rate|string_format:"%0.2f"}%)</td>
      <td>{$report.event_totals.actionlinks.bounce}</td></tr>
  <tr><td class="label"><a href="{$report.event_totals.links.unsubscribe}">{ts}Unsubscribe Requests{/ts}</a></td>
      <td>{$report.event_totals.unsubscribe} ({$report.event_totals.unsubscribe_rate|string_format:"%0.2f"}%)</td>
      <td>{$report.event_totals.actionlinks.unsubscribe}</td></tr>
  <tr><td class="label"><a href="{$report.event_totals.links.optout}">{ts}Opt-out Requests{/ts}</a></td>
      <td>{$report.event_totals.optout} ({$report.event_totals.optout_rate|string_format:"%0.2f"}%)</td>
      <td>{$report.event_totals.actionlinks.optout}</td></tr>
  <tr><td class="label">{ts}Scheduled Date{/ts}</td>
      <td colspan=2>{$report.jobs.0.scheduled_date}</td></tr>
  <tr><td class="label">{ts}Status{/ts}</td>
      <td colspan=2>{$report.jobs.0.status}</td></tr>
  <tr><td class="label">{ts}Start Date{/ts}</td>
      <td colspan=2>{$report.jobs.0.start_date}</td></tr>
  <tr><td class="label">{ts}End Date{/ts}</td>
      <td colspan=2>{$report.jobs.0.end_date}</td></tr>
  </table>
  {/strip}
{else}
    <div class="messages status no-popup">
        {ts}<strong>Delivery has not yet begun for this mailing.</strong> If the scheduled delivery date and time is past, ask the system administrator or technical support contact for your site to verify that the automated mailer task ('cron job') is running - and how frequently.{/ts} {docURL page="user/advanced-configuration/email-system-configuration"}
    </div>
{/if}
</fieldset>

<fieldset>
<legend>{ts}Recipients{/ts}</legend>
{if $report.group.include|@count}
<span class="label">{ts}Included{/ts}</span>
{strip}
<table class="crm-info-panel">
{foreach from=$report.group.include item=group}
<tr class="{cycle values="odd-row,even-row"}">
<td>
{if $group.mailing}
{ts 1=$group.link 2=$group.name}Recipients of <a href="%1">%2</a>{/ts}
{else}
{ts 1=$group.link 2=$group.name}Members of <a href="%1">%2</a>{/ts}
{/if}
</td>
</tr>
{/foreach}
</table>
{/strip}
{/if}

{if $report.group.exclude|@count}
<span class="label">{ts}Excluded{/ts}</span>
{strip}
<table class="crm-info-panel">
{foreach from=$report.group.exclude item=group}
<tr class="{cycle values="odd-row,even-row"}">
<td>
{if $group.mailing}
{ts 1=$group.link 2=$group.name}Recipients of <a href="%1">%2</a>{/ts}
{else}
{ts 1=$group.link 2=$group.name}Members of <a href="%1">%2</a>{/ts}
{/if}
</td>
</tr>
{/foreach}
</table>
{/strip}
{/if}

{if $report.group.base|@count}
<span class="label">{ts}Unsubscription Groups{/ts}</span>
{strip}
<table class="crm-info-panel">
{foreach from=$report.group.base item=group}
<tr class="{cycle values="odd-row,even-row"}">
<td>
{if $group.mailing}
{ts 1=$group.link 2=$group.name}Recipients of <a href="%1">%2</a>{/ts}
{else}
{ts 1=$group.link 2=$group.name}Members of <a href="%1">%2</a>{/ts}
{/if}
</td>
</tr>
{/foreach}
</table>
{/strip}
{/if}
</fieldset>

{if $report.mailing.url_tracking && $report.click_through|@count > 0}
<fieldset>
<legend>{ts}Click-through Summary{/ts}</legend>
{strip}
<table class="crm-info-panel">
<tr>
<th><a href="{$report.event_totals.links.clicks}">{ts}Clicks{/ts}</a></th>
<th><a href="{$report.event_totals.links.clicks_unique}">{ts}Unique Clicks{/ts}</a></th>
<th>{ts}Success Rate{/ts}</th>
<th>{ts}URL{/ts}</th>
<th>{ts}Report{/ts}</th></tr>
{foreach from=$report.click_through item=row}
<tr class="{cycle values="odd-row,even-row"}">
<td>{if $row.clicks > 0}<a href="{$row.link}">{$row.clicks}</a>{else}{$row.clicks}{/if}</td>
<td>{if $row.unique > 0}<a href="{$row.link_unique}">{$row.unique}</a>{else}{$row.unique}{/if}</td>
<td>{$row.rate|string_format:"%0.2f"}%</td>
<td><a href="{$row.url}">{$row.url}</a></td>
<td><a href="{$row.report}">Report</a></td>
</tr>
{/foreach}
</table>
{/strip}
</fieldset>
{/if}

<fieldset>
<legend>{ts}Content / Components{/ts}</legend>
{strip}
<table class="crm-info-panel">
{if $report.mailing.body_text}
<tr>
  <td class="label nowrap">{ts}Text Message{/ts}</td>
  <td>
    {$report.mailing.body_text|mb_truncate:30|escape|nl2br}
    <br />
    <strong><a class="crm-popup" href='{$textViewURL}'>&raquo; {ts}View complete message{/ts}</a></strong>
  </td>
</tr>
{/if}

{if $report.mailing.body_html}
<tr>
  <td class="label nowrap">{ts}HTML Message{/ts}</td>
  <td>
    {$report.mailing.body_html|mb_truncate:30|escape|nl2br}
    <br/>
    <strong><a class="crm-popup" href='{$htmlViewURL}'>&raquo; {ts}View complete message{/ts}</a></strong>
  </td>
</tr>
{/if}

{if $report.mailing.attachment}
<tr>
  <td class="label nowrap">{ts}Attachments{/ts}</td>
  <td>
    {$report.mailing.attachment}
  </td>
</tr>
{/if}

{foreach from=$report.component item=component}
    <tr><td class="label">{$component.type}</td><td><a href="{$component.link}">{$component.name}</a></td></tr>
{/foreach}
</table>
{/strip}
</fieldset>

<fieldset>
<legend>
    {ts}Mailing Settings{/ts}
</legend>
{strip}
<table class="crm-info-panel">
<tr><td class="label">{ts}Mailing Name{/ts}</td><td>{$report.mailing.name}</td></tr>
<tr><td class="label">{ts}Subject{/ts}</td><td>{$report.mailing.subject}</td></tr>
<tr><td class="label">{ts}From{/ts}</td><td>{$report.mailing.from_name} &lt;{$report.mailing.from_email}&gt;</td></tr>
<tr><td class="label">{ts}Reply-to email{/ts}</td><td>{$report.mailing.replyto_email|htmlentities}</td></tr>

<tr><td class="label">{ts}Forward replies{/ts}</td><td>{if $report.mailing.forward_replies}{ts}On{/ts}{else}{ts}Off{/ts}{/if}</td></tr>
<tr><td class="label">{ts}Auto-respond to replies{/ts}</td><td>{if $report.mailing.auto_responder}{ts}On{/ts}{else}{ts}Off{/ts}{/if}</td></tr>

<tr><td class="label">{ts}Open tracking{/ts}</td><td>{if $report.mailing.open_tracking}{ts}On{/ts}{else}{ts}Off{/ts}{/if}</td></tr>
<tr><td class="label">{ts}URL Click-through tracking{/ts}</td><td>{if $report.mailing.url_tracking}{ts}On{/ts}{else}{ts}Off{/ts}{/if}</td></tr>

{if $report.mailing.campaign}
<tr><td class="label">{ts}Campaign{/ts}</td><td>{$report.mailing.campaign}</td></tr>
{/if}

</table>
{/strip}
</fieldset>
<div class="action-link">
    <a href="{$backUrl}" >&raquo; {$backUrlTitle}</a>
</div>





