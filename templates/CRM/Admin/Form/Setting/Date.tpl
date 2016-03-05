{*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2015                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
*}
<div class="crm-block crm-form-block crm-date-form-block">
<div class="help">
  {capture assign=crmURL}{crmURL p='civicrm/admin/setting/preferences/date' q='action=reset=1'}{/capture}
    {ts 1=$crmURL}Use this screen to configure default formats for date display and date input fields throughout your site. Settings use standard POSIX specifiers. New installations are preconfigured with standard United States formats. You can override this default setting and define the range of allowed dates for specific field types at <a href="%1">Administer > Customize Data and Screens > Date Preferences</a>{/ts} {help id='date-format'}
</div>
<div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="top"}</div>
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
