{*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2015                                |
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

{foreach from=$address_groupTree.$blockId item=cd_edit key=group_id}
<div id="{$cd_edit.name}_{$group_id}_{$blockId}" class="form-item">
    <div class="crm-collapsible crm-{$cd_edit.name}_{$group_id}_{$blockId}-accordion {if $cd_edit.collapse_display}collapsed{/if}">
        <div class="collapsible-title">
            {$cd_edit.title}
        </div>
        <div>
            {if $cd_edit.help_pre}
                <div class="messages help">{$cd_edit.help_pre}</div>
            {/if}
            <table class="form-layout-compressed">
                {foreach from=$cd_edit.fields item=element key=field_id}
                    {include file="CRM/Contact/Form/Edit/Address/CustomField.tpl"}
                {/foreach}
            </table>
            <div class="spacer"></div>
            {if $cd_edit.help_post}<div class="messages help">{$cd_edit.help_post}</div>{/if}
        </div>
    </div>

    <div id="custom_group_{$group_id}_{$blockId}"></div>
</div>
{/foreach}
