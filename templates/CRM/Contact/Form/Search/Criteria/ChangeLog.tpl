{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
<div id="changelog" class="form-item">
  <table class="form-layout">
    <tr>
      {include file="CRM/Core/DatePickerRangeWrapper.tpl" fieldName="modified_date" to='' from='' class='' colspan='' hideRelativeLabel=0}
      <td>
        <span class="modifiedBy"><label>{ts}Modified By{/ts}</label></span><br/>
        {$form.changed_by.html}<br><span class="description">{ts}Note this field just filters on who made a change no matter when that change happened, It doesn't have any link to the modified date field.{/ts}</span>
      </td>
    </tr>
    <tr>
      {include file="CRM/Core/DatePickerRangeWrapper.tpl" fieldName="created_date" to='' from='' colspan='' class='' hideRelativeLabel=0}
    </tr>
  </table>
</div>
