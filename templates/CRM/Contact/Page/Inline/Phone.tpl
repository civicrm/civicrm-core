{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{* template for building phone block*}
<div id="crm-phone-content" {if $permission EQ 'edit'} class="crm-inline-edit" data-edit-params='{ldelim}"cid": "{$contactId}", "class_name": "CRM_Contact_Form_Inline_Phone"{rdelim}' data-dependent-fields='["#crm-contact-actions-wrapper"]'{/if}>
  <div class="crm-clear crm-inline-block-content" {if $permission EQ 'edit'}title="{ts}Add or edit phone{/ts}"{/if}>
    {if $permission EQ 'edit'}
      <div class="crm-edit-help">
        <span class="crm-i fa-pencil"></span> {if empty($phone)}{ts}Add phone{/ts}{else}{ts}Add or edit phone{/ts}{/if}
      </div>
    {/if}
    {if empty($phone)}
      <div class="crm-summary-row">
        <div class="crm-label">
          {ts}Phone{/ts}
          {if $privacy.do_not_phone}<span class="icon privacy-flag do-not-phone" title="{ts}Privacy flag: Do Not Phone{/ts}"></span>{/if}
        </div>
        <div class="crm-content"></div>
      </div>
    {/if}
    {foreach from=$phone item=item}
      {if $item.phone || $item.phone_ext}
        <div class="crm-summary-row {if $item.is_primary eq 1}primary{/if}">
          <div class="crm-label">
            {if $privacy.do_not_phone}<span class="icon privacy-flag do-not-phone" title="{ts}Privacy flag: Do Not Phone{/ts}"></span>{/if}
            {$item.location_type} {$item.phone_type}
          </div>
          <div class="crm-content crm-contact_phone">
            {$item.phone}{if $item.phone_ext}&nbsp;&nbsp;{ts}ext.{/ts} {$item.phone_ext}{/if}
          </div>
        </div>
      {/if}
    {/foreach}
   </div>
</div>
