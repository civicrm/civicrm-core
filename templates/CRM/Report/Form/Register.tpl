{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{if $action eq 8}
  <h3>{ts}Delete Report Template{/ts}</h3>
{elseif $action eq 2}
  <h3>{ts}Edit Report Template{/ts}</h3>
{else}
  <h3>{ts}New Report Template{/ts}</h3>
{/if}
<div class="crm-block crm-form-block crm-report-register-form-block">
  {if $action eq 8}
  <table class="form-layout">
    <tr class="buttons">
      <td><div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="top"}</div>
      </td>
      <td></td>
    </tr>
    <tr>
      <td colspan=2>
        <div class="messages status no-popup">
          <div class="icon inform-icon"></div> &nbsp;
          {ts}WARNING: Deleting this option will result in the loss of all Report related records which use the option. This may mean the loss of a substantial amount of data, and the action cannot be undone. Do you want to continue?{/ts}
        </div>
      </td>
    </tr>
    {else}

    <table class="form-layout">
      <tr class="buttons crm-report-register-form-block-buttons">
        <td><div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="top"}</div>
        </td>
        <td></td>
      </tr>
      <tr class="crm-report-register-form-block-label">
        <td class="label">{$form.label.label}</td>
        <td class="view-value">{$form.label.html} <br /><span class="description">{ts}Report title appear in the display screen.{/ts}</span>
        </td>
      </tr>
      <tr class="crm-report-register-form-block-description">
        <td class="label">{$form.description.label}</td>
        <td class="view-value">{$form.description.html} <br /><span class="description">{ts}Report description appear in the display screen.{/ts}</span>
        </td>
      </tr>
      <tr class="crm-report-register-form-block-url">
        <td class="label">{$form.value.label}</td>
        <td class="view-value">{$form.value.html} <br /><span class="description">{ts}Report Url must be like "contribute/summary"{/ts}</span>
        </td>
      </tr>
      <tr class="crm-report-register-form-block-class">
        <td class="label">{$form.name.label}</td>
        <td class="view-value">{$form.name.html} <br /><span class="description">{ts}Report Class must be present before adding the report here, e.g. 'CRM_Report_Form_Contribute_Summary'{/ts}</span>
        </td>
      </tr>
      <tr class="crm-report-register-form-block-weight">
        <td class="label">{$form.weight.label}</td>
        <td class="view-value">{$form.weight.html}</td>
      </tr>
      <tr class="crm-report-register-form-block-component">
        <td class="label">{$form.component_id.label}</td>
        <td class="view-value">{$form.component_id.html} <br /><span class="description">{ts}Specify the Report if it is belongs to any component like "CiviContribute"{/ts}</span>
        </td>
      </tr>
      <tr class="crm-report-register-form-block-is_active">
        <td class="label">{$form.is_active.label}</td>
        <td class="view-value">{$form.is_active.html}</td>
      </tr>
      {/if}
      <tr class="buttons crm-report-register-form-block-buttons">
        <td><div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
        </td>
        <td></td>
      </tr>
    </table>
</div>
