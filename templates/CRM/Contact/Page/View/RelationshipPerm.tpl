{*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2018                                |
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
{* Partial for displaying permissions associated with a relationship *}

{if $permType eq 1}
{include file="CRM/Contact/Page/View/RelationshipPerm.tpl" permType=2}
{/if}

{capture assign="permText"}
{if $permType eq 1}
{ts 1=$permDisplayName 2=$otherDisplayName}%2 can be edited by %1{/ts}
{else}
{ts 1=$permDisplayName 2=$otherDisplayName}%2 can be viewed by %1{/ts}
{/if}
{/capture}

<span class="fa-stack" title="{$permText}">
  <i class="crm-i fa-square fa-stack-2x"></i>
  <i class="crm-i {if $permType eq 1}fa-pencil{else}fa-eye{/if} fa-inverse fa-stack-1x"></i>
</span>
