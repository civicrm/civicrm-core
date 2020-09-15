{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{if $action eq 8 or $action eq 2}
  {include file="CRM/Batch/Form/Batch.tpl"}
{else}
  {include file="CRM/Batch/Form/Search.tpl"}
{/if}
