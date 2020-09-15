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
{if $formEdit}
  {include file="CRM/Custom/Form/Edit/CustomData.tpl" customDataEntity=''}
{else}
  {foreach from=$groupTree item=cd_edit key=group_id name=custom_sets}
    {if $cd_edit.is_multiple and $multiRecordDisplay eq 'single'}
      {assign var="isSingleRecordEdit" value=TRUE}
    {else}
      {* always assign to prevent leakage*}
      {assign var="isSingleRecordEdit" value=''}
    {/if}
    {if $isSingleRecordEdit}
      <div class="custom-group custom-group-{$cd_edit.name}">
        {include file="CRM/Custom/Form/Edit/CustomData.tpl" customDataEntity=''}
      </div>
    {else}
     <div class="custom-group custom-group-{$cd_edit.name} crm-accordion-wrapper crm-custom-accordion {if $cd_edit.collapse_display and !$skipTitle}collapsed{/if}">
      {if !$skipTitle}
      <div class="crm-accordion-header">
        {$cd_edit.title}
       </div><!-- /.crm-accordion-header -->
      {/if}
      <div class="crm-accordion-body">
        {include file="CRM/Custom/Form/Edit/CustomData.tpl" customDataEntity=''}
      </div>
     </div>
    {/if}
    <div id="custom_group_{$group_id}_{$cgCount}"></div>
  {/foreach}

{/if}

{include file="CRM/Form/attachmentjs.tpl"}
