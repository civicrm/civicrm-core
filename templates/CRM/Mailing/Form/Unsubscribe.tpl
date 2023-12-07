{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
<div>
  {if $groupExist}
    <div class="messages status no-popup">
      {ts 1=$name_masked}Are you sure you want to remove %1 from the mailing list(s) shown below:{/ts}<br/>
    </div>
    <table class="selector" style="width: auto; margin-top: 20px;">
      {counter start=0 skip=1 print=false}
      {foreach from=$groups item=group}
        <tr class="{cycle values="odd-row,even-row"}">
          <td><strong>{$group.title}</strong></td>
          <td>&nbsp;&nbsp;{$group.description}&nbsp;</td>
        </tr>
      {/foreach}
    </table>
    <div class="crm-block crm-form-block crm-miscellaneous-form-block">
      <p>{ts 1=$name_masked}You are requesting to unsubscribe <strong>all email addresses for %1</strong> from the above mailing list.{/ts}</p>
      <p>
        {ts}If this is your email address and you <strong>wish to unsubscribe</strong> please click the <strong>Unsubscribe</strong> button to confirm.{/ts}
      </p>
      <div class="crm-submit-buttons">
        {include file="CRM/common/formButtons.tpl" location="bottom"}
      </div>
      <br/>
    </div>
  {/if}
</div>

