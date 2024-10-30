{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
<div class="help">
    {ts}You can search all contacts for duplicates or limit the results for better performance.
      If you limit by group then it will look for matches with that group both inside and outside of the group.
      You can also limit the contacts in the group to be matched by specifying the number of contacts to match. This can be done in conjunction with a group or separately and is recommended for performance reasons.
    {/ts}
</div>
<div class="crm-block crm-form-block crm-dedupe-find-form-block">
   <table class="form-layout-compressed">
     <tr class="crm-dedupe-find-form-block-group_id">
       <td class="label">{$form.group_id.label}</td>
       <td>{$form.group_id.html}</td>
     </tr>
     {if $limitShown}
        <tr class="crm-dedupe-find-form-block-limit">
          <td class="label">{$form.limit.label}</td>
          <td>{$form.limit.html}</td>
        </tr>
      {/if}
   </table>
  <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
</div>
