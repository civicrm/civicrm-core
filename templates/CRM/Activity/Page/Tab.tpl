{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}


{* Include links to enter Activities if session has 'edit' permission *}
{if $action EQ 16 and $permission EQ 'edit' and !$addAssigneeContact and !$addTargetContact}
    <div class="action-link crm-activityLinks" style="text-align: left">{include file="CRM/Activity/Form/ActivityLinks.tpl" as_select=true}</div>
{/if}

{if $action eq 1 or $action eq 2 or $action eq 8 or $action eq 4 or $action eq 32768} {* add, edit, delete or view or detach*}
    {include file="CRM/Activity/Form/Activity.tpl"}
{else}
    {*include file="CRM/Activity/Selector/Activity.tpl"*}
    {include file="CRM/Activity/Selector/Selector.tpl"}
{/if}
