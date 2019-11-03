{*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2019                                |
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
<div id="changelog" class="form-item">
  <table class="form-layout">
    <tr>
      {include file="CRM/Core/DatePickerRangeWrapper.tpl" fieldName="modified_date"}
      <td>
        <span class="modifiedBy"><label>{ts}Modified By{/ts}</label></span></br>
        {$form.changed_by.html}<br><span class="description">{ts}Note this field just filters on who made a change no matter when that change happened, It doesn't have any link to the modified date field.{/ts}</span>
      </td>
    </tr>
    <tr>
      {include file="CRM/Core/DatePickerRangeWrapper.tpl" fieldName="created_date"}
    </tr>
  </table>
</div>
