{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
<table class="form-layout">
  <tr class="crm-contribution-contributionpage-pcp-form-block-pcp_active">
    <td class="label">&nbsp;</td>
    <td>{$form.pcp_active.html} {$form.pcp_active.label}</td>
  </tr>
</table>

<div class="spacer"></div>

<div id="pcpFields">
{crmRegion name="pcp-form-pcp-fields"}
  {if !empty($form.target_entity_type)}
  <table class="form-layout">
    <tr  class="crm-contribution-contributionpage-pcp-form-block-target_entity_type">
        <td class="label">{$form.target_entity_type.label} <span class="crm-marker"> *</span></td>
        <td>{$form.target_entity_type.html} {help id="id-target_entity_type"}</td>
    </tr>
  </table>
  <div id="pcpDetailFields" {if $form.target_entity_type.value[0] == 'event'} style="display:none;"{/if}>
    <table class="form-layout">
      <tr class="crm-contribution-contributionpage-pcp-form-block-target_entity_id" id="pcpDetailFields">
          <td class="label">{$form.target_entity_id.label} <span class="crm-marker"> *</span></td>
          <td>{$form.target_entity_id.html} {help id="id-target_entity_id"}</td>
      </tr>
    </table>
  </div>
  {/if}

  <table class="form-layout">
     <tr class="crm-contribution-contributionpage-pcp-form-block-is_approval_needed">
        <td class="label">{$form.is_approval_needed.label}</td>
        <td>{$form.is_approval_needed.html} {help id="id-approval_needed"}</td>
     </tr>
     <tr class="crm-contribution-contributionpage-pcp-form-block-notify_email">
        <td class="label">{$form.notify_email.label}</td>
        <td>{$form.notify_email.html} {help id="id-notify"}</td>
     </tr>
     <tr class="crm-contribution-contributionpage-pcp-form-block-supporter_profile_id">
        <td class="label">{$form.supporter_profile_id.label} <span class="crm-marker"> *</span></td>
        <td>{$form.supporter_profile_id.html} {help id="id-supporter_profile"}</td>
     </tr>
     <tr class="crm-contribution-contributionpage-pcp-form-block-owner_notify_id">
        <td class="label">{$form.owner_notify_id.label}</td>
        <td>{$form.owner_notify_id.html}</td>
     </tr>
     <tr class="crm-contribution-contributionpage-pcp-form-block-is_tellfriend_enabled">
        <td class="label">{$form.is_tellfriend_enabled.label}</td>
        <td>{$form.is_tellfriend_enabled.html} {help id="id-is_tellfriend"}</td>
     </tr>
     <tr id="tflimit" class="crm-contribution-contributionpage-pcp-form-block-tellfriend_limit">
        <td class="label">{$form.tellfriend_limit.label}</td>
        <td>{$form.tellfriend_limit.html|crmAddClass:four} {help id="id-tellfriend_limit"}</td>
     </tr>
     <tr class="crm-contribution-contributionpage-pcp-form-block-link_text">
        <td class="label">{$form.link_text.label}</td>
        <td>
          {$form.link_text.html|crmAddClass:huge} {help id="id-link_text"}<br />
          <span class="description">
            {if $config->userSystem->is_drupal || $config->userFramework EQ 'WordPress'}
              {ts}You can also place additional links (or menu items) allowing constituents to create their own fundraising pages using the following URL:{/ts}<br />
              <em>{crmURL a=1 fe=1 p='civicrm/contribute/campaign' q="action=add&reset=1&pageId=`$pageId`&component=`$context`"}</em>
            {elseif $config->userFramework EQ 'Joomla'}
              {ts}You can also create front-end links (or menu items) allowing constituents to create their own fundraising pages using the Menu Manager. Select <strong>Contributions &raquo; Personal Campaign Pages</strong> and then select this event.{/ts}
            {/if}
          </span>
        </td>
     </tr>
  </table>
{/crmRegion}
</div>
<div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
</div>
{include file="CRM/common/showHideByFieldValue.tpl"
    trigger_field_id    = "pcp_active"
    trigger_value       = "true"
    target_element_id   = "pcpFields"
    target_element_type = "block"
    field_type          = "radio"
    invert              = "false"
}
{include file="CRM/common/showHideByFieldValue.tpl"
    trigger_field_id    = "is_tellfriend_enabled"
    trigger_value       = "true"
    target_element_id   = "tflimit"
    target_element_type = "table-row"
    field_type          = "radio"
    invert              = "false"
}
