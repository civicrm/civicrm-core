{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{* template for building website block *}
<div id="crm-website-content" {if $permission EQ 'edit'} class="crm-inline-edit" data-edit-params='{ldelim}"cid": "{$contactId}", "class_name": "CRM_Contact_Form_Inline_Website"{rdelim}'{/if}>
  <div class="crm-clear crm-inline-block-content" {if $permission EQ 'edit'}title="{ts}Add or edit website{/ts}"{/if}>
    {if $permission EQ 'edit'}
      <div class="crm-edit-help">
        <span class="crm-i fa-pencil" aria-hidden="true"></span> {if empty($website)}{ts}Add website{/ts}{else}{ts}Add or edit website{/ts}{/if}
      </div>
    {/if}
    {if empty($website)}
      <div class="crm-summary-row">
        <div class="crm-label">{ts}Website{/ts}</div>
        <div class="crm-content"></div>
      </div>
    {/if}
    {foreach from=$website item=item}
      {if !empty($item.url)}
      <div class="crm-summary-row">
        <div class="crm-label">{$item.website_type} {ts}Website{/ts}</div>
        <div class="crm-content crm-contact_website"><a href="{$item.url}" target="_blank">{$item.url}</a></div>
      </div>
      {/if}
    {/foreach}
  </div>
</div>
