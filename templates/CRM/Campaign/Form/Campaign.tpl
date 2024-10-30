{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
<div class="crm-block crm-form-block crm-campaign-form-block">
{if $action eq 8}
  <div class="messages status no-popup">
    {icon icon="fa-info-circle"}{/icon}
    {ts}Are you sure you want to delete this Campaign?{/ts}
  </div>
{else}
  <table class="form-layout-compressed">
  <tr class="crm-campaign-form-block-title">
      <td class="label">{$form.title.label}</td>
      <td class="view-value">{$form.title.html}</td>
  </tr>
  <tr class="crm-campaign-form-block-campaign_type_id">
      <td class="label">{$form.campaign_type_id.label}</td>
      <td class="view-value">{$form.campaign_type_id.html}</td>
  </tr>
  <tr class="crm-campaign-form-block-description">
      <td class="label">{$form.description.label}</td>
      <td class="view-value">{$form.description.html}</td>
  </tr>
  <tr class="crm-campaign-form-block-includeGroups">
      <td class="label">{$form.includeGroups.label}</td>
      <td>{$form.includeGroups.html}</td>
  </tr>
  <tr class="crm-campaign-form-block-start_date">
      <td class="label">{$form.start_date.label}</td>
      <td class="view-value">{$form.start_date.html}</td>
  </tr>
  <tr class="crm-campaign-form-block-end_date">
      <td class="label">{$form.end_date.label}</td>
      <td class="view-value">{$form.end_date.html}</td>
  </tr>
  <tr class="crm-campaign-form-block-status_id">
      <td class="label">{$form.status_id.label}</td>
      <td class="view-value">{$form.status_id.html}</td>
  </tr>
  <tr class="crm-campaign-form-block-goal_general">
      <td class="label">{$form.goal_general.label}</td>
      <td class="view-value">{$form.goal_general.html}</td>
  </tr>
  <tr class="crm-campaign-form-block-goal_revenue">
      <td class="label">{$form.goal_revenue.label}</td>
      <td class="view-value">{$form.goal_revenue.html}</td>
  </tr>
  <tr class="crm-campaign-form-block-external_identifier">
      <td class="label">{$form.external_identifier.label}</td>
      <td class="view-value">{$form.external_identifier.html}</td>
  </tr>

  {* Suppress parent-child feature for now. dgg *}
  {*
  <tr class="crm-campaign-form-block-parent_id">
      <td class="label">{$form.parent_id.label}</td>
      <td class="view-value">{$form.parent_id.html}</td>
  </tr> *}

  <tr class="crm-campaign-form-block-is_active">
      <td class="label">{$form.is_active.label}</td>
      <td class="view-value">{$form.is_active.html}</td>
  </tr>
  </table>

  {include file="CRM/common/customDataBlock.tpl" groupID='' customDataType='Campaign' cid=false}

{/if}
  <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
</div>
