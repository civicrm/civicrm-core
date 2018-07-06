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
<div class="crm-block crm-form-block crm-recurcontrib-form-block">
  {if $changeHelpText}
    <div class="help">
      {$changeHelpText}
      {if $recurMembership}
        <br/><strong> {ts}'WARNING: This recurring contribution is linked to membership:{/ts}
        <a class="crm-hover-button" href='{crmURL p="civicrm/contact/view/membership" q="action=view&reset=1&cid=`$contactId`&id=`$recurMembership.membership_id`&context=membership&selectedChild=member"}'>{$recurMembership.membership_name}</a>
        </strong>
      {/if}
    </div>
  {/if}
  <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="top"}</div>
  <table class="form-layout">
    <tr>
      <td class="label">{$form.amount.label}</td>
      <td>{$form.currency.html|crmAddClass:eight}&nbsp;{$form.amount.html|crmAddClass:eight} ({ts}every{/ts} {$frequency_interval} {$frequency_unit})</td>
    </tr>
    <tr><td class="label">{$form.installments.label}</td><td>{$form.installments.html}<br />
          <span class="description">{ts}Total number of payments to be made. Set this to 0 if this is an open-ended commitment i.e. no set end date.{/ts}</span></td></tr>
    {foreach from=$editableScheduleFields item='field'}
      <tr><td class="label">{$form.$field.label}</td><td>{$form.$field.html}</td></tr>
    {/foreach}
    {if !$self_service}
    <tr><td class="label">{$form.is_notify.label}</td><td>{$form.is_notify.html}</td></tr>
    <tr><td class="label">{$form.campaign_id.label}</td><td>{$form.campaign_id.html}</td></tr>
    <tr><td class="label">{$form.financial_type_id.label}</td><td>{$form.financial_type_id.html}</td></tr>
    {/if}
  </table>

  <div id="customData"></div>
  {*include custom data js file*}
  {include file="CRM/common/customData.tpl"}
  {literal}
    <script type="text/javascript">
      CRM.$(function($) {
        {/literal}
        CRM.buildCustomData( '{$customDataType}' );
        {literal}
      });
    </script>
  {/literal}

  <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
</div>
