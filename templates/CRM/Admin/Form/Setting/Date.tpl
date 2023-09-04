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
  {capture assign=crmURL}{crmURL p='civicrm/admin/setting/preferences/date' q='action=reset=1'}{/capture}
    {ts 1=$crmURL}Use this screen to configure default formats for date display and date input fields throughout your site. Settings use standard POSIX specifiers. New installations are preconfigured with standard United States formats. You can override this default setting and define the range of allowed dates for specific field types at <a href="%1">Administer > Customize Data and Screens > Date Preferences</a>{/ts} {help id='date-format'}
</div>
<div class="crm-block crm-form-block crm-date-form-block">
<fieldset><legend>{ts}Date Display{/ts}</legend>
   <table class="form-layout-compressed">
       <tr class="crm-date-form-block-dateformatDatetime">
          <td class="label">{$form.dateformatDatetime.label}</td>
          <td>{$form.dateformatDatetime.html}</td>
       </tr>
       <tr class="crm-date-form-block-dateformatFull">
          <td class="label">{$form.dateformatFull.label}</td>
          <td>{$form.dateformatFull.html}</td>
       </tr>
       <tr class="crm-date-form-block-dateformatPartial">
          <td class="label">{$form.dateformatPartial.label}</td>
          <td>{$form.dateformatPartial.html}</td>
       </tr>
       <tr class="crm-date-form-block-dateformatYear">
          <td class="label">{$form.dateformatYear.label}</td>
          <td>{$form.dateformatYear.html}</td>
       </tr>
       <tr class="crm-date-form-block-dateformatTime">
          <td class="label">{$form.dateformatTime.label}</td>
          <td>{$form.dateformatTime.html}</td>
       </tr>
       <tr class="crm-date-form-block-dateformatTime">
          <td class="label">{$form.dateformatFinancialBatch.label}</td>
          <td>{$form.dateformatFinancialBatch.html}</td>
       </tr>
       <tr class="crm-date-form-block-dateformatTime">
          <td class="label">{$form.dateformatshortdate.label}</td>
          <td>{$form.dateformatshortdate.html}</td>
       </tr>
     </table>
</fieldset>
<fieldset><legend>{ts}Date Input Fields{/ts}</legend>
   <table class="form-layout-compressed">
       <tr class="crm-date-form-block-dateInputFormat">
          <td class="label">{$form.dateInputFormat.label}</td>
          <td>{$form.dateInputFormat.html}</td>
       </tr>
       <tr class="crm-date-form-block-timeInputFormat">
          <td class="label">{$form.timeInputFormat.label}</td>
          <td>{$form.timeInputFormat.html}</td>
       </tr>
   </table>
</fieldset>
<fieldset><legend>{ts}Calendar{/ts}</legend>
   <table class="form-layout-compressed">
       <tr class="crm-date-form-block-weekBegins">
         <td class="label">{$form.weekBegins.label}</td>
         <td>{$form.weekBegins.html}</td>
       </tr>
       <tr class="crm-date-form-block-fiscalYearStart">
          <td class="label">{$form.fiscalYearStart.label}</td>
          <td>{$form.fiscalYearStart.html}</td>
       </tr>
   </table>
</fieldset>
    <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
<div class="spacer"></div>
</div>
