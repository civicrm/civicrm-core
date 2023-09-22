{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{* Confirmation of contribution deletes  *}
<div class="messages status no-popup">
    {icon icon="fa-info-circle"}{/icon}
        <p>{ts}Are you sure you want to delete the selected contributions? This delete operation cannot be undone and will delete all transactions and activity associated with these contributions.{/ts}</p>
        <p>{include file="CRM/Contribute/Form/Task.tpl"}</p>
</div>
<p>
<div class="form-item">
 {$form.buttons.html}
</div>
