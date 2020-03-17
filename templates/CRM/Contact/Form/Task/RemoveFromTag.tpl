{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{* template to remove tags from contact  *}
<div class="crm-form-block crm-block crm-contact-task-removefromtag-form-block">
  <h3>
    {ts}Tag Contact(s) (Remove){/ts}
  </h3>
  <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="top"}</div>
  <table class="form-layout-compressed">
    <tr class="crm-contact-task-removefromtag-form-block-tag">
      <td>
        <div class="listing-box">
        {foreach from=$form.tag item="tag_val"}
           <div class="{cycle values="odd-row,even-row"}">
             {$tag_val.html}
          </div>
        {/foreach}
        </div>
      </td>
    </tr>
    <tr>
      <td>
        {include file="CRM/common/Tagset.tpl"}
      </td>
    </tr>

    <tr><td>{include file="CRM/Contact/Form/Task.tpl"}</td></tr>
  </table>
  <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
</div>
