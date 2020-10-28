{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{if $confirm}
<div class="messages status no-popup">
      {icon icon="fa-info-circle"}{/icon}
      <label>{$display_name} ({$email})</label> {ts}has been successfully resubscribed.{/ts}
</div>
{else}
<div>
<form action="{$confirmURL}" method="post">
{ts 1=$display_name 2=$email}Are you sure you want to resubscribe: %1 (%2){/ts}
<br/>
<center>
<button type="submit" name="_qf_resubscribe_next" class="crm-button crm-form-submit">{ts}Resubscribe{/ts}</button>
&nbsp;&nbsp;&nbsp;
<button type="submit" name="_qf_resubscribe_cancel" class="crm-button crm-form-submit">{ts}Cancel{/ts}</button>
</center>
</form>
</div>
{/if}
