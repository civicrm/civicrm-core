{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{* Actions: 1=add, 2=edit, browse=16, delete=8 *}
{if $action ne 1 and $action ne 2 and $action ne 8 and $groupPermission eq 1}
<div class="crm-submit-buttons">
    <a accesskey="N" href="{crmURL p='civicrm/group/add' q='reset=1'}" class="newGroup button"><span><i class="crm-i fa-plus-circle" role="img" aria-hidden="true"></i> {ts}Add Group{/ts}</span></a><br/>
</div>
{/if} {* action ne add or edit *}
<div class="crm-block crm-content-block">
{if $action eq 16}
<div class="help">
    {ts}Use Groups to organize contacts (e.g. these contacts are part of our 'Steering Committee'). You can also create 'smart' groups based on contact characteristics (e.g. this group consists of all people in our database who live in a specific locality).{/ts} {help id="manage_groups"}
</div>
{/if}
{if $action ne 2 AND $action ne 8}
{include file="CRM/Group/Form/Search.tpl"}
{/if}

{if $action eq 1 or $action eq 2 or $action eq 8}
   {include file="CRM/Group/Form/Edit.tpl"}
{/if}

{if $action ne 1 and $action ne 2 and $action ne 8 and $groupPermission eq 1}
<div class="crm-submit-buttons">
        <a accesskey="N" href="{crmURL p='civicrm/group/add' q='reset=1'}" class="newGroup button"><span><i class="crm-i fa-plus-circle" role="img" aria-hidden="true"></i> {ts}Add Group{/ts}</span></a><br/>
</div>
{/if} {* action ne add or edit *}
</div>
