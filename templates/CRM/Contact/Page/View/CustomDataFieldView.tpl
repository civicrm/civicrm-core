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
<div id="custom-set-content-{$customGroupId}" {if $permission EQ 'edit'} class="crm-inline-edit" data-edit-params='{ldelim}"cid": "{$contactId}", "class_name": "CRM_Contact_Form_Inline_CustomData", "groupID": "{$customGroupId}", "customRecId": "{$customRecId}", "cgcount" : "{$cgcount}"{rdelim}'{/if}>
  <div class="crm-clear crm-inline-block-content" {if $permission EQ 'edit'}title="{ts}Edit{/ts}"{/if}>
    {if $permission EQ 'edit'}
      <div class="crm-edit-help">
        <span class="batch-edit"></span>{ts}Edit{/ts}
      </div>
    {/if}

    {foreach from=$cd_edit.fields item=element key=field_id}
      <div class="crm-summary-row">
        {if $element.options_per_line != 0}
          <div class="crm-label">{$element.field_title}</div>
          <div class="crm-content crm-custom_data">
              {* sort by fails for option per line. Added a variable to iterate through the element array*}
              {foreach from=$element.field_value item=val}
                {$val}
              {/foreach}
          </div>
        {else}
          <div class="crm-label">{$element.field_title}</div>
          {if $element.field_type == 'File'}
            {if $element.field_value.displayURL}
                <div class="crm-content crm-custom_data crm-displayURL">
                  <a href="{$element.field_value.displayURL}" class='crm-image-popup'>
                    <img src="{$element.field_value.displayURL}" height = "{$element.field_value.imageThumbHeight}"
                         width="{$element.field_value.imageThumbWidth}">
                  </a>
                </div>
            {else}
                <div class="crm-content crm-custom_data crm-fileURL">
                  <a href="{$element.field_value.fileURL}">{$element.field_value.fileName}</a>
                </div>
            {/if}
          {elseif $element.field_data_type EQ 'ContactReference' && $element.contact_ref_id}
            {*Contact ref id passed if user has sufficient permissions - so make a link.*}
            <div class="crm-content crm-custom-data crm-contact-reference">
              <a href="{crmURL p='civicrm/contact/view' q="reset=1&cid=`$element.contact_ref_id`"}" title="view contact">{$element.field_value}</a>
            </div>
          {else}
            <div class="crm-content crm-custom-data">{$element.field_value}</div>
          {/if}
        {/if}
      </div>
    {/foreach}
  </div>
</div>
