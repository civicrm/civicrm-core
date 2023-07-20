{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
<div class="crm-form-block crm-block crm-contact-task-addtodonot-form-block">
  <h3>{ts}Communication Preferences{/ts}</h3>
  <table class="form-layout-compressed">
    <tr><td>{$form.actionTypeOption.html}</td></tr>
    <tr class="crm-contact-task-addtodonot-form-block-pref">
        <td>
            <div class="listing-box">
            {foreach from=$form.pref item="pref_val"}
                <div class="{cycle values="odd-row,even-row"}">
                {$pref_val.html}
                </div>
            {/foreach}
            </div>
        </td>
    </tr>
    <tr><td>{include file="CRM/Contact/Form/Task.tpl"}</td></tr>
  </table>
  <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
</div>
