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
{* Actions: 1=add, 2=edit, browse=16, delete=8 *}
{if $action ne 1 and $action ne 2 and $action ne 8 and $groupPermission eq 1}
<div class="crm-submit-buttons">
    <a accesskey="N" href="{crmURL p='civicrm/group/add' q='reset=1'}" class="newGroup button"><span><i class="crm-i fa-plus-circle"></i> {ts}Add Group{/ts}</span></a><br/>
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

{if $action eq 1 or $action eq 2}
   {include file="CRM/Group/Form/Edit.tpl"}
{elseif $action eq 8}
   {include file="CRM/Group/Form/Delete.tpl"}
{/if}

{if $action ne 1 and $action ne 2 and $action ne 8 and $groupPermission eq 1}
<div class="crm-submit-buttons">
        <a accesskey="N" href="{crmURL p='civicrm/group/add' q='reset=1'}" class="newGroup button"><span><i class="crm-i fa-plus-circle"></i> {ts}Add Group{/ts}</span></a><br/>
</div>
{/if} {* action ne add or edit *}
</div>
