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
{* Template for "Change Case Status" activities. *}
  <div class="crm-block crm-form-block crm-case-changecasestatus-form-block">
    <tr class="crm-case-changecasestatus-form-block-case_status_id">
      <td class="label">{$form.case_status_id.label}</td>
      <td>{$form.case_status_id.html}</td>
    </tr>
    {if sizeof($linkedCases) > 0}
      <tr>
        <td rowspan="2">{ts}Update Linked Cases Status?{/ts}</td>
        <td>{$form.updateLinkedCases.html}</td>
      </tr>
      <tr>
        <td>
          <table>
            <tr>
              <th>ID</th>
              <th>Case Client</th>
              <th>Case Type</th>
              <th>Status</th>
            </tr>
            {foreach from=$linkedCases item="linkedCase"}
              <tr>
                <td>{$linkedCase.case_id}</td>
                <td>{$linkedCase.client_name}</td>
                <td>{$linkedCase.case_type}</td>
                <td>{$linkedCase.case_status}</td>
              </tr>
            {/foreach}
          </table>
        </td>
      </tr>
    {/if}
    {if $groupTree}
        <tr>
            <td colspan="2">{include file="CRM/Custom/Form/CustomData.tpl" noPostCustomButton=1}</td>
        </tr>
    {/if}
  </div>
