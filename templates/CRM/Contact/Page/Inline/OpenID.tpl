{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{* template for building OpenID block*}
<div id="crm-openid-content" {if $permission EQ 'edit'} class="crm-inline-edit" data-edit-params='{ldelim}"cid": "{$contactId}", "class_name": "CRM_Contact_Form_Inline_OpenID"{rdelim}'{/if}>
  <div class="crm-clear crm-inline-block-content" {if $permission EQ 'edit'}title="{ts escape='htmlattribute'}Add or edit OpenID{/ts}"{/if}>
    {if $permission EQ 'edit'}
      <div class="crm-edit-help">
        <span class="crm-i fa-pencil" aria-hidden="true"></span> {if empty($openid)}{ts}Add OpenID{/ts}{else}{ts}Add or edit OpenID{/ts}{/if}
      </div>
    {/if}
    {if empty($openid)}
      <div class="crm-summary-row">
        <div class="crm-label">{ts}OpenID{/ts}</div>
        <div class="crm-content"></div>
      </div>
    {/if}
    {foreach from=$openid item=item}
      {if $item.openid}
      <div class="crm-summary-row {if $item.is_primary eq 1} primary{/if}">
        <div class="crm-label">{$item.location_type}&nbsp;{ts}OpenID{/ts}</div>
        <div class="crm-content crm-contact_openid">
          <a href="{$item.openid}">{$item.openid|mb_truncate:40}</a>
        </div>
      </div>
        {include file="CRM/Contact/Page/Inline/BlockCustomData.tpl" entity='openid' customGroups=$item.custom identifier=$blockId}
      {/if}
    {/foreach}
   </div>
</div>
