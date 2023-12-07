{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{* Confirmation of contact deletes  *}
<div class="messages status no-popup">
{icon icon="fa-info-circle"}{/icon}
        <p>{ts  1=$displayName}Are you sure you want to delete the contact record and all related information for <strong>%1</strong>?{/ts}</p>
        <p>{ts}This action cannot be undone.{/ts}</p>
</div>
<p>
<div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location=''}</div>
