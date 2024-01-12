{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{* Overloaded template for viewing, editing and deleting a relationship *}
{if $action eq 4} {* action = view *}
  {include file="CRM/Contact/Page/View/ViewRelationship.tpl"}
{else} {* add, update, delete *}
  {include file="CRM/Contact/Form/Relationship.tpl"}
{/if}
