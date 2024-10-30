{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{* this template is used for adding/editing/deleting financial type  *}
{include file="CRM/Core/Form/EntityForm.tpl"}
{if $action eq 2 or $action eq 4} {* Update or View*}
  <div class="crm-submit-buttons">
    <a href="{crmURL p='civicrm/admin/financial/financialType/accounts' q="action=browse&reset=1&aid=$aid"}" class="button"><span>{ts}View or Edit Financial Accounts{/ts}</a></span>
  </div>
{/if}
