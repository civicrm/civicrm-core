{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
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
      {crmButton accesskey="N"  p=$newMassUrl q='reset=1' icon="envelope"}{ts}{$linkTitle}{/ts}{/crmButton}<br/><br/>
    </div>
{/if}
{include file="CRM/Mailing/Form/Search.tpl"}

{if $rows}
    {include file="CRM/common/pager.tpl" location="top"}
    {strip}
    <table class="selector row-highlight">
      <thead class="sticky">
      {foreach from=$columnHeaders item=header}
        <th>
          {if !empty($header.sort)}
            {assign var='key' value=$header.sort}
            {$sort->_response.$key.link}
          {elseif !empty($header.name)}
            {$header.name}
          {/if}
        </th>
      {/foreach}
      </thead>

      {counter start=0 skip=1 print=false}
      {foreach from=$rows item=row}
      <tr id="mailing-{$row.id}" class="{cycle values="odd-row,even-row"} crm-mailing crm-mailing_status-{$row.status} crm-entity" data-action="create">
        <td class="crm-mailing-name crm-editable crmf-name">{$row.name}</td>
        {if $multilingual}
          <td class="crm-mailing-language">{$row.language}</td>
        {/if}
        <td class="crm-mailing-status crm-mailing_status-{$row.status}">{$row.status}</td>
        <td class="crm-mailing-created_by">
          <a href ={crmURL p='civicrm/contact/view' q="reset=1&cid="}{$row.created_id} title="{$row.created_by|escape}">
            {$row.created_by|mb_truncate:20:"..."}
          </a>
        </td>
        <td class="crm-mailing-created_date">{$row.created_date}</td>
        <td class="crm-mailing-scheduled_by">
          <a href ={crmURL p='civicrm/contact/view' q="reset=1&cid="}{$row.scheduled_id} title="{$row.scheduled_by|escape}">
            {$row.scheduled_by|mb_truncate:20:"..."}
          </a>
        </td>
        <td class="crm-mailing-scheduled">{$row.scheduled}</td>
        <td class="crm-mailing-start">{$row.start}</td>
        <td class="crm-mailing-end">{$row.end}</td>
       {if call_user_func(array('CRM_Campaign_BAO_Campaign','isComponentEnabled'))}
          <td class="crm-mailing-campaign crm-editable crmf-campaign_id" data-type="select" data-empty-option="{ts escape='htmlattribute'}- none -{/ts}">{$row.campaign}</td>
      {/if}
        <td>{$row.action|smarty:nodefaults|replace:'xx':$row.id}</td>
      </tr>
      {/foreach}
    </table>
    {/strip}
    {include file="CRM/common/pagerAToZ.tpl"}
    {include file="CRM/common/pager.tpl" location="bottom"}
    {if $showLinks}
      <div class="action-link">
            {crmButton accesskey="N"  p=$newMassUrl q='reset=1' icon="envelope"}{ts}{$linkTitle}{/ts}{/crmButton}<br/>
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
            <tr>{icon icon="fa-info-circle"}{/icon}
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
            {icon icon="fa-info-circle"}{/icon}
            {capture assign=crmURL}{crmURL p=$newMassUrl q='reset=1'}{/capture}
            {ts 1=$componentName}There are no Unscheduled %1.{/ts}
      {if $showLinks}{ts 1=$crmURL}You can <a href='%1'>create and send one</a>.{/ts}{/if}
   </div>

{elseif $archived}
    <div class="messages status no-popup">
            {icon icon="fa-info-circle"}{/icon}&nbsp
            {capture assign=crmURL}{crmURL p='civicrm/mailing/browse/scheduled' q='scheduled=true&reset=1'}{$qVal}{/capture}
            {ts 1=$crmURL 2=$componentName}There are no Archived %2. You can archive %2 from <a href='%1'>Scheduled or Sent %2</a>.{/ts}
   </div>
{else}
    <div class="messages status no-popup">
            {icon icon="fa-info-circle"}{/icon}
            {capture assign=crmURL}{crmURL p=$newMassUrl q='reset=1'}{/capture}
            {capture assign=archiveURL}{crmURL p='civicrm/mailing/browse/archived' q='reset=1'}{$qVal}{/capture}
            {ts 1=$componentName}There are no Scheduled or Sent %1.{/ts}
      {if $showLinks}{ts 1=$crmURL}You can <a href='%1'>create and send one</a>{/ts}{/if}{if $archiveLinks}{ts 1=$archiveURL 2=$componentName} OR you can search the <a href='%1'>Archived %2</a>{/ts}{/if}.
   </div>
{/if}
