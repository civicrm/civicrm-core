{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{* this template is used for adding/editing entities  *}
<div class="crm-block crm-form-block crm-{$entityInClassFormat}-form-block">
  <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="top"}</div>
  {if $action eq 8}
    <div class="messages status no-popup">
      <div class="icon inform-icon"></div>
      {$deleteMessage|escape}
    </div>
  {else}
    <table class="form-layout-compressed">
      {foreach from=$entityFields item=fieldSpec}
        {assign var=fieldName value=$fieldSpec.name}
        <tr class="crm-{$entityInClassFormat}-form-block-{$fieldName}">
          {include file="CRM/Core/Form/Field.tpl"}
        </tr>
      {/foreach}
    </table>
    {include file="CRM/common/customDataBlock.tpl"}
  {/if}
  <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
</div>
