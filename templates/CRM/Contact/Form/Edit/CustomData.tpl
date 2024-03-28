{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}

{foreach from=$groupTree item=cd_edit key=group_id}
  {if $cd_edit.is_multiple eq 1}
    <div></div>
    <details class="crm-accordion-bold crm-custom-accordion" {if $cd_edit.collapse_display and !$skipTitle}{else}open{/if}>
  {else}
    <details id="{$cd_edit.name}" class="crm-accordion-bold crm-custom-accordion" {if $cd_edit.collapse_display}{else}open{/if}>
  {/if}
    <summary>
      {$cd_edit.title}
    </summary>
    <div id="customData_{$contactType}{$group_id}" class="crm-accordion-body crm-customData-block">
      {include file="CRM/Custom/Form/Edit/CustomData.tpl" customDataEntity=''}
      {include file="CRM/Form/attachmentjs.tpl"}
    </div>
    <!-- crm-accordion-body-->
  </details>
  <div id="custom_group_{$group_id}_{$cgCount}"></div>
  {/foreach}

  {include file="CRM/common/customData.tpl"}

  {include file="CRM/Form/attachmentjs.tpl"}
