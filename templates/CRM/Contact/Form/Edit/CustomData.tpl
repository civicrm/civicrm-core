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

{foreach from=$groupTree item=cd_edit key=group_id}
  {if $cd_edit.is_multiple eq 1}
    {assign var=tableID value=$cd_edit.table_id}
    {assign var=divName value=$group_id|cat:"_$tableID"}
    <div></div>
    <div
     class="crm-accordion-wrapper crm-custom-accordion {if $cd_edit.collapse_display and !$skipTitle}collapsed{/if}">
  {else}
    <div id="{$cd_edit.name}"
       class="crm-accordion-wrapper crm-custom-accordion {if $cd_edit.collapse_display}collapsed{/if}">
  {/if}
    <div class="crm-accordion-header">
      {$cd_edit.title}
    </div>

    <div id="customData{$group_id}" class="crm-accordion-body">
      {if $cd_edit.is_multiple eq 1}
        {if $cd_edit.table_id}
          <table class="no-border">
            <tr>
              <a href="#" class="crm-hover-button crm-custom-value-del" title="{ts 1=$cd_edit.title}Delete %1{/ts}"
               data-post='{ldelim}"valueID": "{$tableID}", "groupID": "{$group_id}", "contactId": "{$contactId}", "key": "{crmKey name='civicrm/ajax/customvalue'}"{rdelim}'>
                <span class="icon delete-icon"></span> {ts}Delete{/ts}
              </a>
              <!-- crm-submit-buttons -->
            </tr>
          </table>
        {/if}
      {/if}
      {include file="CRM/Custom/Form/CustomData.tpl" formEdit=true}
    </div>
    <!-- crm-accordion-body-->
  </div>
  <!-- crm-accordion-wrapper -->
  <div id="custom_group_{$group_id}_{$cgCount}"></div>
  {/foreach}

  {include file="CRM/common/customData.tpl"}

  {include file="CRM/Form/attachmentjs.tpl"}
