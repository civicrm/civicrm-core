{*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.5                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2014                                |
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
{if $sms}
  {assign var='newMassUrl' value='civicrm/sms/send'}
  {assign var='qVal' value='&sms=1'}
  {assign var='linkTitle' value='New SMS'}
  {assign var='componentName' value='Mass SMS'}
{else}
  {assign var='newMassUrl' value='civicrm/mailing/send'}
  {assign var='qVal' value=''}
  {assign var='linkTitle' value='New Mailing'}
  {assign var='componentName' value='Mailings'}
{/if}

{if $showLinks}
    <div class="action-link">
      <a accesskey="N" href="{crmURL p=$newMassUrl q='reset=1'}" class="button"><span><div class="icon email-icon"></div>{ts}{$linkTitle}{/ts}</span></a><br/><br/>
    </div>
{/if}
{include file="CRM/Mailing/Form/Search.tpl"}

{if $rows}
    {include file="CRM/common/pager.tpl" location="top"}
    {include file="CRM/common/pagerAToZ.tpl"}

    {strip}
    <table class="selector row-highlight">
      <thead class="sticky">
      {foreach from=$columnHeaders item=header}
        <th>
          {if $header.sort}
            {assign var='key' value=$header.sort}
            {$sort->_response.$key.link}
          {else}
            {$header.name}
          {/if}
        </th>
      {/foreach}
      </thead>

      {counter start=0 skip=1 print=false}
      {foreach from=$rows item=row}
      <tr id="crm-mailing_{$row.id}" class="{cycle values="odd-row,even-row"} crm-mailing crm-mailing_status-{$row.status}">
        <td class="crm-mailing-name">{$row.name}</td>
        <td class="crm-mailing-status crm-mailing_status-{$row.status}">{$row.status}</td>
        <td class="crm-mailing-created_by"><a href ={crmURL p='civicrm/contact/view' q="reset=1&cid="}{$row.created_id}>{$row.created_by}</a></td>
        <td class="crm-mailing-created_date">{$row.created_date}</td>
        <td class="crm-mailing-scheduled_by"><a href ={crmURL p='civicrm/contact/view' q="reset=1&cid="}{$row.scheduled_id}>{$row.scheduled_by}</a></td>
        <td class="crm-mailing-scheduled">{$row.scheduled}</td>
        <td class="crm-mailing-start">{$row.start}</td>
        <td class="crm-mailing-end">{$row.end}</td>
       {if call_user_func(array('CRM_Campaign_BAO_Campaign','isCampaignEnable'))}
          <td class="crm-mailing-campaign">{$row.campaign}</td>
      {/if}
        <td>{$row.action|replace:'xx':$row.id}</td>
      </tr>
      {/foreach}
    </table>
    {/strip}

    {include file="CRM/common/pager.tpl" location="bottom"}
    {if $showLinks}
      <div class="action-link">
            <a accesskey="N" href="{crmURL p=$newMassUrl q='reset=1'}" class="button"><span><div class="icon email-icon"></div>{ts}{$linkTitle}{/ts}</span></a><br/>
      </div>
    {/if}

{* No mailings to list. Check isSearch flag to see if we're in a search or not. *}
{elseif $isSearch eq 1}
    {if $archived}
        {capture assign=browseURL}{crmURL p='civicrm/mailing/browse/archived' q="reset=1"}{$qVal}{/capture}
        {assign var="browseType" value="Archived"}
    {elseif $unscheduled}
        {capture assign=browseURL}{crmURL p='civicrm/mailing/browse/unscheduled' q="scheduled=false&reset=1"}{$qVal}{/capture}
        {assign var="browseType" value="Draft and Unscheduled"}
    {else}
        {capture assign=browseURL}{crmURL p='civicrm/mailing/browse/scheduled' q="scheduled=true&reset=1"}{$qVal}{/capture}
        {assign var="browseType" value="Scheduled and Sent"}
    {/if}
    <div class="status messages">
        <table class="form-layout">
            <tr><div class="icon inform-icon"></div>
               {ts 1=$componentName}No %1 match your search criteria. Suggestions:{/ts}
      </tr>
                <div class="spacer"></div>
                <ul>
                <li>{ts}Check your spelling.{/ts}</li>
                <li>{ts}Try a different spelling or use fewer letters.{/ts}</li>
                </ul>
            <tr>{ts 1=$browseURL 2=$browseType 3=$componentName}Or you can <a href='%1'>browse all %2 %3</a>.{/ts}</tr>
        </table>
    </div>
{elseif $unscheduled}

    <div class="messages status no-popup">
            <div class="icon inform-icon"></div>&nbsp;
            {capture assign=crmURL}{crmURL p=$newMassUrl q='reset=1'}{/capture}
            {ts 1=$componentName}There are no Unscheduled %1.{/ts}
      {if $showLinks}{ts 1=$crmURL}You can <a href='%1'>create and send one</a>.{/ts}{/if}
   </div>

{elseif $archived}
    <div class="messages status no-popup">
            <div class="icon inform-icon"></div>&nbsp
            {capture assign=crmURL}{crmURL p='civicrm/mailing/browse/scheduled' q='scheduled=true&reset=1'}{$qVal}{/capture}
            {ts 1=$crmURL 2=$componentName}There are no Archived %2. You can archive %2 from <a href='%1'>Scheduled or Sent %2</a>.{/ts}
   </div>
{else}
    <div class="messages status no-popup">
            <div class="icon inform-icon"></div>&nbsp;
            {capture assign=crmURL}{crmURL p=$newMassUrl q='reset=1'}{/capture}
            {capture assign=archiveURL}{crmURL p='civicrm/mailing/browse/archived' q='reset=1'}{$qVal}{/capture}
            {ts 1=$componentName}There are no Scheduled or Sent %1.{/ts}
      {if $showLinks}{ts 1=$crmURL}You can <a href='%1'>create and send one</a>{/ts}{/if}{if $archiveLinks}{ts 1=$archiveURL 2=$componentName} OR you can search the <a href='%1'>Archived %2</a>{/ts}{/if}.
   </div>
{/if}
