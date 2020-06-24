{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}

{foreach from=$address_groupTree.$blockId item=cd_edit key=group_id}
<div id="{$cd_edit.name}_{$group_id}_{$blockId}" class="form-item">
    <div class="crm-collapsible crm-{$cd_edit.name}_{$group_id}_{$blockId}-accordion {if $cd_edit.collapse_display}collapsed{/if}">
        <div class="collapsible-title">
            {$cd_edit.title}
        </div>
        <div>
        {include file="CRM/Custom/Form/Edit/CustomData.tpl" customDataEntity='address'}
        </div>
    </div>

    <div id="custom_group_{$group_id}_{$blockId}"></div>
</div>
{/foreach}
