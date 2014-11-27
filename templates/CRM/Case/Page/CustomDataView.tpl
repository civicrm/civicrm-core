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
{* include wysiwyg related files*}
{if !$includeWysiwygEditor}
  {include file="CRM/common/wysiwyg.tpl" includeWysiwygEditor=true}
{/if}
{* Custom Data view mode*}
{foreach from=$viewCustomData item=customValues key=customGroupId}
  {foreach from=$customValues item=cd_edit key=cvID}
    {assign var='index' value=$groupId|cat:"_$cvID"}
  <div id="{$cd_edit.name}" class="crm-accordion-wrapper {if $cd_edit.collapse_display neq 0}collapsed{/if}">
    <div class="crm-accordion-header">
      {$cd_edit.title}
    </div>
    <div class="crm-accordion-body">
      {foreach from=$cd_edit.fields item=element key=field_id}
        <table class="crm-info-panel">
          <tr>
            {if $element.options_per_line != 0}
              <td class="label">{$element.field_title}</td>
              <td class="html-adjust">
              {* sort by fails for option per line. Added a variable to iterate through the element array*}
                {foreach from=$element.field_value item=val}
                  {$val}<br/>
                {/foreach}
              </td>
              {else}
              <td class="label">{$element.field_title}</td>
              {if $element.field_type == 'File'}
                {if $element.field_value.displayURL}
                  <td class="html-adjust">
                    <a href="{$element.field_value.displayURL}" class='crm-image-popup'>
                      <img src="{$element.field_value.displayURL}" height = "100" width="100">
                    </a>
                  </td>
                  {else}
                  <td class="html-adjust">
                    <a href="{$element.field_value.fileURL}">{$element.field_value.fileName}</a>
                  </td>
                {/if}
                {else}
                <td class="html-adjust">{$element.field_value}</td>
              {/if}
            {/if}
          </tr>
        </table>
      {/foreach}
      <div>
        <a href="{crmURL p="civicrm/case/cd/edit" q="cgcount=1&action=update&reset=1&type=Case&entityID=$caseID&groupID=$customGroupId&cid=$contactID&subType=$caseTypeID"}" class="button">
          <span><div class="icon edit-icon"></div>{ts}Edit{/ts}</span>
        </a>
      </div>
      <br/>
      <div class="clear"></div>
    </div>
  </div>

  {/foreach}
{/foreach}
<div id="case_custom_edit"></div>
