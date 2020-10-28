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
        &nbsp;{ts}Are you sure you want to restore the selected cases? This operation will retrieve the case(s) and all associated activities from Trash.{/ts}</p>
        <p>{include file="CRM/Case/Form/Task.tpl"}</p>
  </div>
<p>
<div class="crm-submit-buttons">
  {include file="CRM/common/formButtons.tpl" location="bottom"}
</div>
