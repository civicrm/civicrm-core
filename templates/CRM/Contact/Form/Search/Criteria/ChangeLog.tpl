{*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.4                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2013                                |
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
        <td>
          {$form.changed_by.label}<br />
          {$form.changed_by.html}
        </td>
  <td width="100%">
    {$form.log_date.html}<span class="crm-clear-link">(<a href="#" title="unselect" onclick="unselectRadio('log_date', '{$form.formName}'); return false;" >{ts}clear{/ts}</a>)</span><br />
        </td>
     </tr>
     <tr>
  <td>
     <span id="modbet"><label>{ts}Modified Between{/ts}</label></span>
     <span id="addbet" class="hiddenElement"><label>{ts}Added Between{/ts}</label></span>
  </td>
     </tr>
     <tr>
  {include file="CRM/Core/DateRange.tpl" fieldName="log_date" from='_low' to='_high'}
     </tr>
   </table>
 </div>

{literal}
  <script type="text/javascript">
    cj(function () {
      var changeType = cj('input[name=log_date]:checked').val();
      if (changeType == 2) {
        cj('#addbet').hide();
        cj('#modbet').show();
      }
      else if (changeType == 1) {
        cj('#addbet').show();
        cj('#modbet').hide();
      }
    });

    cj('[name=log_date]:input').change(function () {
      var changeType = cj('input[name=log_date]:checked').val();
      if (changeType == 2) {
        cj('#addbet').hide();
        cj('#modbet').show();
      }
      else if (changeType == 1) {
        cj('#addbet').show();
        cj('#modbet').hide();
      }
    }).change();
  </script>
{/literal}
