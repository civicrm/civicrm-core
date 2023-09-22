{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{* this template is used for confirmation of delete for a group *}
<fieldset><legend>{ts}Delete Campaign Page {/ts}</legend>
<div class="messages status no-popup">
   {icon icon="fa-info-circle"}{/icon}
  {ts 1=$title}Are you sure you want to delete Campaign Page '%1'?{/ts}<br />
  {ts}This action cannot be undone.{/ts}
</div>

<div class="form-item">{$form.buttons.html}</div>
</fieldset>
