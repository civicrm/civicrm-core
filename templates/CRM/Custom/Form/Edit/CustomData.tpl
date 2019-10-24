{if !$isSingleRecordEdit && $cd_edit.is_multiple eq 1 and $cd_edit.table_id and $contactId and !$skipTitle and $cd_edit.style eq 'Inline'}
  {assign var=tableID value=$cd_edit.table_id}
  <a href="#" class="crm-hover-button crm-custom-value-del" title="{ts 1=$cd_edit.title}Delete %1{/ts}"
     data-post='{ldelim}"valueID": "{$tableID}", "groupID": "{$group_id}", "contactId": "{$contactId}", "key": "{crmKey name='civicrm/ajax/customvalue'}"{rdelim}'>
    <span class="icon delete-icon"></span> {ts}Delete{/ts}
  </a>
{/if}

{if $cd_edit.help_pre}
  <div class="messages help">{$cd_edit.help_pre}</div>
{/if}
<table {if !$isSingleRecordEdit}class="form-layout-compressed"{/if}>
  {foreach from=$cd_edit.fields item=element key=field_id}
    {if $customDataEntity && $blockId}
      {* custom data entity combined with blockId tells us we have an entity with mutliple blocks
      such as address. Some risk of leakage on blockId so only set customDataEntity when using blocks*}
      {assign var="element_name" value=$element.element_custom_name}
      {assign var="formElement" value=$form.$customDataEntity.$blockId.$element_name}
    {else}
      {assign var="element_name" value=$element.element_name}
      {assign var="formElement" value=$form.$element_name}
    {/if}
    {include file="CRM/Custom/Form/Edit/CustomField.tpl"}
  {/foreach}
</table>
<div class="spacer"></div>
{if $cd_edit.help_post}<div class="messages help">{$cd_edit.help_post}</div>{/if}
{if !$isSingleRecordEdit && $cd_edit.is_multiple and ( ( $cd_edit.max_multiple eq '' )  or ( $cd_edit.max_multiple > 0 and $cd_edit.max_multiple > $cgCount ) ) }
  {if $skipTitle}
    {* We don't yet support adding new records in inline-edit forms *}
    <div class="messages help">
      <em>{ts 1=$cd_edit.title}Click "Edit Contact" to add more %1 records{/ts}</em>
    </div>
  {else}
    <div id="add-more-link-{$cgCount}" class="add-more-link-{$group_id} add-more-link-{$group_id}-{$cgCount}">
      <a href="#" class="crm-hover-button" onclick="CRM.buildCustomData('{$cd_edit.extends}',{if $cd_edit.subtype}'{$cd_edit.subtype}'{else}'{$cd_edit.extends_entity_column_id}'{/if}, '', {$cgCount}, {$group_id}, true ); return false;">
        <i class="crm-i fa-plus-circle"></i>
        {ts 1=$cd_edit.title}Another %1 record{/ts}
      </a>
    </div>
  {/if}
{/if}

{*set customDataEntity to null to prevent leakage if this is called more than once*}
{assign var='customDataEntity' value=''}
