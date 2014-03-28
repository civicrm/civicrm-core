{*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.5                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2014                                |
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
