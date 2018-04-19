{*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2018                                |
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
