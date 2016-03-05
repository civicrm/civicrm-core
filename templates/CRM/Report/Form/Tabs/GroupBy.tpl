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
  <div id="report-tab-group-by-elements" class="civireport-criteria">
    {assign  var="count" value="0"}
    <table class="report-layout">
      <tr class="crm-report crm-report-criteria-groupby">
        {foreach from=$groupByElements item=gbElem key=dnc}
        {assign var="count" value=`$count+1`}
        <td width="25%" {if $form.fields.$gbElem}"{/if}>
        {$form.group_bys[$gbElem].html}
        {if $form.group_bys_freq[$gbElem].html}:<br>
          &nbsp;&nbsp;{$form.group_bys_freq[$gbElem].label}&nbsp;{$form.group_bys_freq[$gbElem].html}
        {/if}
        </td>
        {if $count is div by 4}
      </tr><tr class="crm-report crm-report-criteria-groupby">
        {/if}
        {/foreach}
        {if $count is not div by 4}
          <td colspan="4 - ($count % 4)"></td>
        {/if}
      </tr>
    </table>
  </div>
