{*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2016                                |
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
{* Custom Data form*}
{if $formEdit}
  {if $cd_edit.help_pre}
    <div class="messages help">{$cd_edit.help_pre}</div>
  {/if}
  <table class="form-layout-compressed">
    {foreach from=$cd_edit.fields item=element key=field_id}
      {include file="CRM/Custom/Form/CustomField.tpl"}
    {/foreach}
  </table>
  <div class="spacer"></div>
  {if $cd_edit.help_post}
    <div class="messages help">{$cd_edit.help_post}</div>
  {/if}
  {if $cd_edit.is_multiple and ( ( $cd_edit.max_multiple eq '' )  or ( $cd_edit.max_multiple > 0 and $cd_edit.max_multiple > $cgCount ) ) }
    <div id="add-more-link-{$cgCount}" class="add-more-link-{$group_id} add-more-link-{$group_id}-{$cgCount}">
      <a href="#" class="crm-hover-button" onclick="CRM.buildCustomData('{$cd_edit.extends}',{if $cd_edit.subtype}'{$cd_edit.subtype}'{else}'{$cd_edit.extends_entity_column_id}'{/if}, '', {$cgCount}, {$group_id}, true ); return false;">
        <i class="crm-i fa-plus-circle"></i>
        {ts 1=$cd_edit.title}Another %1 record{/ts}
      </a>
    </div>
  {/if}
{else}
  {foreach from=$groupTree item=cd_edit key=group_id name=custom_sets}
    {if $cd_edit.is_multiple and $multiRecordDisplay eq 'single'}
      <div class="custom-group custom-group-{$cd_edit.name}">
        {if $cd_edit.help_pre}
          <div class="messages help">{$cd_edit.help_pre}</div>
        {/if}
        <table>
          {foreach from=$cd_edit.fields item=element key=field_id}
            {include file="CRM/Custom/Form/CustomField.tpl"}
          {/foreach}
        </table>
        <div class="spacer"></div>
        {if $cd_edit.help_post}
          <div class="messages help">{$cd_edit.help_post}</div>
        {/if}
      </div>
    {else}
     <div class="custom-group custom-group-{$cd_edit.name} crm-accordion-wrapper crm-custom-accordion {if $cd_edit.collapse_display and !$skipTitle}collapsed{/if}">
      {if !$skipTitle}
      <div class="crm-accordion-header">
        {$cd_edit.title}
       </div><!-- /.crm-accordion-header -->
      {/if}
      <div class="crm-accordion-body">
        {if $cd_edit.is_multiple eq 1 and $cd_edit.table_id and $contactId and !$skipTitle and $cd_edit.style eq 'Inline'}
          {assign var=tableID value=$cd_edit.table_id}
          <a href="#" class="crm-hover-button crm-custom-value-del" title="{ts 1=$cd_edit.title}Delete %1{/ts}"
           data-post='{ldelim}"valueID": "{$tableID}", "groupID": "{$group_id}", "contactId": "{$contactId}", "key": "{crmKey name='civicrm/ajax/customvalue'}"{rdelim}'>
            <span class="icon delete-icon"></span> {ts}Delete{/ts}
          </a>
        {/if}
        {if $cd_edit.help_pre}
          <div class="messages help">{$cd_edit.help_pre}</div>
        {/if}
        <table class="form-layout-compressed">
          {foreach from=$cd_edit.fields item=element key=field_id}
            {include file="CRM/Custom/Form/CustomField.tpl"}
          {/foreach}
        </table>
        <div class="spacer"></div>
        {if $cd_edit.help_post}
          <div class="messages help">{$cd_edit.help_post}</div>
        {/if}
      </div>
     </div>
     {if $cd_edit.is_multiple and ( ( $cd_edit.max_multiple eq '' )  or ( $cd_edit.max_multiple > 0 and $cd_edit.max_multiple > $cgCount ) ) }
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
    {/if}
    <div id="custom_group_{$group_id}_{$cgCount}"></div>
  {/foreach}

{/if}

{include file="CRM/Form/attachmentjs.tpl"}
