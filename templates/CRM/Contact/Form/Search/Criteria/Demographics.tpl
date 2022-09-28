{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
<div id="demographics" class="form-item">
  <table class="form-layout">
    <tr>
      {include file="CRM/Core/DatePickerRangeWrapper.tpl" fieldName="birth_date" to='' from='' colspan='' class='' hideRelativeLabel=0}
    </tr>
    <tr>
      <td>
        <label>{ts}Age{/ts}</label>
      </td>
    </tr>
    <tr>
      {include file="CRM/Core/AgeRange.tpl" fieldName="age" from='_low' to='_high' date='_asof_date'}
    </tr>
    <tr>
      <td>
        {$form.is_deceased.label}<br />
        {$form.is_deceased.html}
      </td>
    </tr>
    <tr>
      {include file="CRM/Core/DatePickerRangeWrapper.tpl" fieldName="deceased_date"  to='' from='' colspan='' class='' hideRelativeLabel=0}
    </tr>
    <tr>
      <td>
        {$form.gender_id.label}<br />
        {$form.gender_id.html}
      </td>
    </tr>
  </table>
</div>

