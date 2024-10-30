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
       {if !$single and $context eq 'Search'}
          <th scope="col" title="{ts}Select rows{/ts}">{$form.toggleSelect.html}</th>
       {/if}
       {foreach from=$columnHeaders item=header}
          <th scope="col">
          {if $header.sort}
            {assign var='key' value=$header.sort}
            {if !empty($sort)}
              {$sort->_response.$key.link}
            {/if}
          {elseif $header.name}
            {$header.name}
          {/if}
          </th>
       {/foreach}
     </tr>
   </thead>


  {counter start=0 skip=1 print=false}
  {foreach from=$rows item=row}
  <tr id='rowid{$row.activity_id}' class="{cycle values="odd-row,even-row"}{if !empty($row.class)} {$row.class}{/if}">
  {if !$single}
        {if $context eq 'Search'}
          {assign var=cbName value=$row.checkbox}
          <td>{$form.$cbName.html}</td>
     {/if}

    {/if}

    <td>
      {$row.activity_type}
      {if $row.repeat}
        <br/>
        <span><b>{$row.repeat}</b></span>
      {/if}
    </td>

  <td>{$row.activity_subject|purify}</td>

    <td>
    {if !$row.source_contact_id}
      <em>n/a</em>
    {else}
      <a href="{crmURL p='civicrm/contact/view' q="reset=1&cid=`$row.source_contact_id`"}" title="{ts}View contact{/ts}">{$row.source_contact_name|purify}</a>
    {/if}
    </td>

    <td>
    {if $row.mailingId}
      <a href="{$row.mailingId}" title="{ts}View Mailing Report{/ts}">{$row.recipients}</a>
    {elseif $row.recipients}
      {$row.recipients}
    {elseif !$row.target_contact_name}
      <em>n/a</em>
    {elseif $row.target_contact_name}
        {assign var="showTarget" value=0}
        {foreach from=$row.target_contact_name item=targetName key=targetID}
            {if $showTarget < 5}
                {if $showTarget};&nbsp;{/if}<a href="{crmURL p='civicrm/contact/view' q="reset=1&cid=`$targetID`"}" title="{ts}View contact{/ts}">{$targetName}</a>
                {assign var="showTarget" value=$showTarget+1}
            {/if}
        {/foreach}
        {if count($row.target_contact_name) > 5}({ts}more{/ts}){/if}
    {/if}
    </td>

    <td>
    {if !$row.assignee_contact_name}
        <em>n/a</em>
    {elseif $row.assignee_contact_name}
        {assign var="showAssignee" value=0}
        {foreach from=$row.assignee_contact_name item=assigneeName key=assigneeID}
            {if $showAssignee < 5}
                {if $showAssignee};&nbsp;{/if}<a href="{crmURL p='civicrm/contact/view' q="reset=1&cid=`$assigneeID`"}" title="{ts}View contact{/ts}">{$assigneeName}</a>
                {assign var="showAssignee" value=$showAssignee+1}
            {/if}
        {/foreach}
        {if count($row.assignee_contact_name) > 5}({ts}more{/ts}){/if}
    {/if}
    </td>

    <td>{$row.activity_date_time|crmDate}</td>

    <td>{$row.activity_status}</td>

    <td>
      {if (!empty($row.id))}
        {$row.action|smarty:nodefaults|replace:'xx':$row.id}
      {else}
        {$row.action}
      {/if}
    </td>

  </tr>
  {/foreach}

</table>
{/strip}
{include file="CRM/Case/Form/ActivityToCase.tpl"}


{if $context EQ 'Search'}
    {include file="CRM/common/pager.tpl" location="bottom"}
{/if}
