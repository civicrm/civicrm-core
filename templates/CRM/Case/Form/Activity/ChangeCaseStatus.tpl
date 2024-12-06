{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{* Template for "Change Case Status" activities. *}
  <div class="crm-block crm-form-block crm-case-changecasestatus-form-block">
    <tr class="crm-case-changecasestatus-form-block-case_status_id">
      <td class="label">{$form.case_status_id.label}</td>
      <td>{$form.case_status_id.html}</td>
    </tr>
    {if count($linkedCases) > 0}
      <tr>
        <td rowspan="2">{ts}Update Linked Cases Status?{/ts}</td>
        <td>{$form.updateLinkedCases.html}</td>
      </tr>
      <tr>
        <td>
          <table>
            <tr>
              <th>{ts}ID{/ts}</th>
              <th>{ts}Case Client{/ts}</th>
              <th>{ts}Case Type{/ts}</th>
              <th>{ts}Status{/ts}</th>
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
