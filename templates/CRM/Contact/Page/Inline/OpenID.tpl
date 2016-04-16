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
{* template for building OpenID block*}
<div id="crm-openid-content" {if $permission EQ 'edit'} class="crm-inline-edit" data-edit-params='{ldelim}"cid": "{$contactId}", "class_name": "CRM_Contact_Form_Inline_OpenID"{rdelim}'{/if}>
  <div class="crm-clear crm-inline-block-content" {if $permission EQ 'edit'}title="{ts}Add or edit OpenID{/ts}"{/if}>
    {if $permission EQ 'edit'}
      <div class="crm-edit-help">
        <span class="crm-i fa-pencil"></span> {if empty($openid)}{ts}Add OpenID{/ts}{else}{ts}Add or edit OpenID{/ts}{/if}
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
      {/if}
    {/foreach}
   </div>
</div>
