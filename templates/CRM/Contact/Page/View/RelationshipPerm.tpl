{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{* Partial for displaying permissions associated with a relationship *}

{if $permType eq 1}
{include file="CRM/Contact/Page/View/RelationshipPerm.tpl" permType=2 displayText=false}
{/if}

{if $permDisplayName and $otherDisplayName}
{capture assign="permText"}
{if $permType eq 1}
{ts 1=$permDisplayName 2=$otherDisplayName}%2 can be edited by %1.{/ts}
{else}
{ts 1=$permDisplayName 2=$otherDisplayName}%2 can be viewed by %1.{/ts}
{/if}
{/capture}
{/if}

<span class="fa-stack" title="{$permText}">
  <i class="crm-i fa-square fa-stack-2x {if $permType eq 1}crm-i-blue{else}crm-i-green{/if}" aria-hidden="true"></i>
  <i class="crm-i {if $permType eq 1}fa-pencil{else}fa-eye{/if} fa-inverse fa-stack-1x" aria-hidden="true"></i>
</span>
{if !$displayText}
<span class="sr-only">{$permText}</span>
{/if}

{* Used for viewing a relationship *}
{if $displayText}
{if $permType eq 1}
{ts 1=$permDisplayName 2=$otherDisplayName}<strong>%1</strong> can view and update information about <strong>%2</strong>.{/ts}
{else}
{ts 1=$permDisplayName 2=$otherDisplayName}<strong>%1</strong> can view information about <strong>%2</strong>.{/ts}
{/if}
{/if}
