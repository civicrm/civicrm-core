{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{* Confirmation of participation deletes  *}
<div class="messages status no-popup">
    {icon icon="fa-info-circle"}{/icon}
        <p>{ts}Are you sure you want to delete the selected pledges? This delete operation cannot be undone and will delete all transactions associated with these pledges.{/ts}</p>
        <p>{include file="CRM/Pledge/Form/Task.tpl"}</p>
</div>
<p>
<div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
