{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
<div id="custom-set-content-{$customGroupId}" {if $permission EQ 'edit'} class="crm-inline-edit" data-edit-params='{ldelim}"cid": "{$contactId}", "class_name": "CRM_Contact_Form_Inline_CustomData", "groupID": "{$customGroupId}", "customRecId": "{$customRecId}", "cgcount" : "{$cgcount}"{rdelim}' data-dependent-fields='["#crm-communication-pref-content"]'{/if}>
  <div class="crm-clear crm-inline-block-content" {if $permission EQ 'edit'}title="{ts}Edit{/ts}"{/if}>
    {if $permission EQ 'edit'}
      <div class="crm-edit-help">
        <span class="crm-i fa-pencil"></span> {ts}Edit{/ts}
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
          {if $element.field_data_type EQ 'ContactReference' && $element.contact_ref_id}
            {*Contact ref id passed if user has sufficient permissions - so make a link.*}
            <div class="crm-content crm-custom-data crm-contact-reference">
              <a href="{crmURL p='civicrm/contact/view' q="reset=1&cid=`$element.contact_ref_id`"}" title="view contact">{$element.field_value}</a>
            </div>
          {elseif $element.field_data_type EQ 'Money'}
            <div class="crm-content crm-custom-data">{$element.field_value|crmMoney}</div>
          {else}
            <div class="crm-content crm-custom-data">{$element.field_value}</div>
          {/if}
        {/if}
      </div>
    {/foreach}
  </div>
</div>
