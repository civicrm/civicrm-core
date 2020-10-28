{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{* template for building IM block*}
<div id="crm-im-content" {if $permission EQ 'edit'} class="crm-inline-edit" data-edit-params='{ldelim}"cid": "{$contactId}", "class_name": "CRM_Contact_Form_Inline_IM"{rdelim}'{/if}>
  <div class="crm-clear crm-inline-block-content" {if $permission EQ 'edit'}title="{ts}Add or edit IM{/ts}"{/if}>
    {if $permission EQ 'edit'}
      <div class="crm-edit-help">
        <span class="crm-i fa-pencil" aria-hidden="true"></span> {if empty($im)}{ts}Add IM{/ts}{else}{ts}Add or edit IM{/ts}{/if}
      </div>
    {/if}
    {if empty($im)}
      <div class="crm-summary-row">
        <div class="crm-label">{ts}IM{/ts}</div>
        <div class="crm-content"></div>
      </div>
    {/if}
    {foreach from=$im item=item}
      {if $item.name or $item.provider}
        {if $item.name}
        <div class="crm-summary-row {if $item.is_primary eq 1} primary{/if}">
          <div class="crm-label">{$item.provider}&nbsp;({$item.location_type})</div>
          <div class="crm-content crm-contact_im">{$item.name}</div>
        </div>
        {/if}
      {/if}
    {/foreach}
   </div>
</div>
