{*
+--------------------------------------------------------------------+
| CiviCRM version 5                                                  |
+--------------------------------------------------------------------+
| Copyright CiviCRM LLC (c) 2004-2018                                |
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
{if $otherOptions}
  <div id="report-tab-other-options" class="civireport-criteria">
    <table class="report-layout">
      {assign var="optionCount" value=0}
      <tr class="crm-report crm-report-criteria-field">
        {foreach from=$otherOptions item=optionField key=optionName}
        {assign var="optionCount" value=`$optionCount+1`}
        <td>{if $form.$optionName.label}{$form.$optionName.label}&nbsp;{/if}{$form.$optionName.html}</td>
        {if $optionCount is div by 2}
      </tr><tr class="crm-report crm-report-criteria-field">
        {/if}
        {/foreach}
        {if $optionCount is not div by 2}
          <td colspan="2 - ($count % 2)"></td>
        {/if}
      </tr>
    </table>
  </div>
{/if}
