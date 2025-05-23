{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{* Custom Data form*}
{foreach from=$groupTree item=cd_edit key=group_id name=custom_sets}
    {* Note this `if` looks like it's needlessly assigning a var but it's also used in the included file Edit/CustomData.tpl *}
    {if $cd_edit.is_multiple and $multiRecordDisplay eq 'single'}
      {assign var="isSingleRecordEdit" value=true}
    {else}
      {* always assign to prevent leakage*}
      {assign var="isSingleRecordEdit" value=false}
    {/if}
    {if $isSingleRecordEdit}
      <div class="custom-group custom-group-{$cd_edit.name}">
        {include file="CRM/Custom/Form/Edit/CustomData.tpl" customDataEntity=''}
      </div>
    {else}
     <details class="custom-group custom-group-{$cd_edit.name} crm-accordion-bold crm-custom-accordion" {if $cd_edit.collapse_display and empty($skipTitle)}{else}open{/if}>
      {if empty($skipTitle)}
      <summary>
        {$cd_edit.title}
       </summary>
      {/if}
      <div class="crm-accordion-body">
        {include file="CRM/Custom/Form/Edit/CustomData.tpl" customDataEntity=''}
      </div>
     </details>
    {/if}
    {if $cgCount}
      <div id="custom_group_{$group_id}_{$cgCount}"></div>
    {else}
      <div id="custom_group_{$group_id}"></div>
    {/if}
  {/foreach}

{include file="CRM/Form/attachmentjs.tpl"}
