
{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{crmRegion name="contribute-form-contributionpage-settings-main"}
<div class="help">
  {if $action eq 0}
    <p>{ts}This is the first step in creating a new online Contribution Page. You can create one or more different Contribution Pages for different purposes, audiences, campaigns, etc. Each page can have it's own introductory message, pre-configured contribution amounts, custom data collection fields, etc.{/ts}</p>
    <p>{ts}In this step, you will configure the page title, financial type (donation, campaign contribution, etc.), goal amount, and introductory message. You will be able to go back and modify all aspects of this page at any time after completing the setup wizard.{/ts}</p>
  {else}
    {ts}Use this form to edit the page title, financial type (e.g. donation, campaign contribution, etc.), goal amount, introduction, and status (active/inactive) for this online contribution page.{/ts}
  {/if}
</div>
<div class="crm-block crm-form-block crm-contribution-contributionpage-settings-form-block">
  <table class="form-layout-compressed">
    <tr class="crm-contribution-contributionpage-settings-form-block-frontend-title">
      <td class="label">{$form.frontend_title.label} {if $action == 2}{include file='CRM/Core/I18n/Dialog.tpl' table='civicrm_contribution_page' field='frontend_title' id=$contributionPageID}{/if}</td>
      <td>{$form.frontend_title.html}</td>
    </tr>
    <tr class="crm-contribution-contributionpage-settings-form-block-title">
      <td class="label">{$form.title.label} {if $action == 2}{include file='CRM/Core/I18n/Dialog.tpl' table='civicrm_contribution_page' field='title' id=$contributionPageID}{/if}</td>
      <td>{$form.title.html}</td>
    </tr>
    <tr class="crm-contribution-contributionpage-settings-form-block-financial_type_id">
      <td class="label">{$form.financial_type_id.label} {help id="financial_type_id"}</td>
      <td>{$form.financial_type_id.html}</td>
    </tr>

    {* CRM-7362 --add campaign to contribution page *}
    {include file="CRM/Campaign/Form/addCampaignToComponent.tpl"
    campaignTrClass="crm-contribution-contributionpage-settings-form-block-campaign_id"}

    <tr class="crm-contribution-contributionpage-settings-form-block-is_organization">
      <td>&nbsp;</td>
      <td>{$form.is_organization.html} {$form.is_organization.label} {help id="is_organization"}</td>
    </tr>
    <tr id="for_org_option" class="crm-contribution-form-block-is_organization">
      <td>&nbsp;</td>
      <td>
        <table class="form-layout-compressed">
          <tr class="crm-contribution-onbehalf_profile_id">
            <td class="label">{$form.onbehalf_profile_id.label}</td>
            <td>{$form.onbehalf_profile_id.html}
              <a href="#" class="crm-button crm-popup">{icon icon="fa-list-alt"}{/icon} {ts}Fields{/ts}</a>
            </td>
          </tr>
          <tr id="for_org_text" class="crm-contribution-contributionpage-settings-form-block-for_organization">
            <td class="label">{$form.for_organization.label} {help id="for_organization"}</td>
            <td>{$form.for_organization.html}</td>
          </tr>
          <tr class="crm-contribution-contributionpage-settings-form-block-is_for_organization">
            <td>&nbsp;</td>
            <td>{$form.is_for_organization.html}<br />
                <span class="description">{ts}Check 'Required' to force ALL users to contribute/signup on behalf of an organization.{/ts}</span>
            </td>
          </tr>
        </table>
      </td>
    </tr>
    <tr class="crm-contribution-contributionpage-settings-form-block-intro_text">
      <td class ="label">{$form.intro_text.label}<br /> {if $action == 2}{include file='CRM/Core/I18n/Dialog.tpl' table='civicrm_contribution_page' field='intro_text' id=$contributionPageID}{/if} {help id="intro_text"}</td><td>{$form.intro_text.html}</td>
    </tr>
    <tr class="crm-contribution-contributionpage-settings-form-block-footer_text">
      <td class ="label">{$form.footer_text.label}<br /> {if $action == 2}{include file='CRM/Core/I18n/Dialog.tpl' table='civicrm_contribution_page' field='footer_text' id=$contributionPageID}{/if} {help id="footer_text"}</td><td>{$form.footer_text.html}</td>
    </tr>
    <tr class="crm-contribution-contributionpage-settings-form-block-goal_amount">
      <td class ="label">{$form.goal_amount.label}</td><td>{$form.goal_amount.html} {help id="goal_amount"}</td>
    </tr>
    <tr class="crm-contribution-contributionpage-settings-form-block-start_date">
      <td class ="label">{$form.start_date.label} {help id="start_date"}</td>
      <td>{$form.start_date.html}</td>
    </tr>
    <tr class="crm-contribution-contributionpage-settings-form-block-end_date">
      <td class ="label">{$form.end_date.label}</td>
      <td>{$form.end_date.html}</td>
    </tr>
    <tr class="crm-contribution-contributionpage-settings-form-block-honor_block_is_active">
      <td class ="label">{$form.honor_block_is_active.label} {help id="honor_block_is_active"}</td>
      <td>{$form.honor_block_is_active.html}</td>
    </tr>
  </table>
  <table class="form-layout-compressed" id="honor">
    <tr class="crm-contribution-contributionpage-settings-form-block-honor_block_title">
      <td class="label">{$form.honor_block_title.label}</td>
      <td>{$form.honor_block_title.html}</td>
    </tr>
    <tr class="crm-contribution-contributionpage-settings-form-block-honor_block_text">
      <td class="label">
        {crmAPI var='result' entity='OptionGroup' action='get' sequential=1 name='soft_credit_type'}
        {$form.honor_block_text.label} {help id="honor_block_text"}
      </td>
      <td>{$form.honor_block_text.html}</td>
    </tr>
    <tr class="crm-contribution-contributionpage-settings-form-block-honor_soft_credit_types">
      <td class="label">{$form.soft_credit_types.label}</td>
      <td>{$form.soft_credit_types.html}</td>
    </tr>
    <tr class="crm-contribution-contributionpage-custom-form-block-custom_pre_id">
      <td class="label">{$form.honoree_profile.label}</td>
      <td class="html-adjust">{$form.honoree_profile.html}</td>
   </tr>
  </table>
  <table class="form-layout-compressed">
    <tr class="crm-contribution-contributionpage-settings-form-block-is_confirm_enabled">
      <td>&nbsp;</td>
      <td>{$form.is_confirm_enabled.html} {$form.is_confirm_enabled.label} {help id="is_confirm_enabled"}</td>
    </tr>
      <tr class="crm-contribution-contributionpage-settings-form-block-is_share">
      <td>&nbsp;</td>
      <td>{$form.is_share.html} {$form.is_share.label} {help id="is_share"}</td>
    </tr>
      <tr class="crm-contribution-contributionpage-settings-form-block-is_active">
      <td>&nbsp;</td>
      <td>{$form.is_active.html} {$form.is_active.label}</td>
    </tr>
    {if $contributionPageID}
      <tr class="crm-contribution-contributionpage-settings-form-block-info_link">
        <td>&nbsp;</td>
        <td class="description">
          {if $config->userSystem->is_drupal || $config->userFramework EQ 'WordPress'}
            {ts}When your page is active, you can link people to the page by copying and pasting the following URL:{/ts}<br />
            <strong>{crmURL a=1 fe=1 p='civicrm/contribute/transact' q="reset=1&id=`$contributionPageID`"}</strong>
          {elseif $config->userFramework EQ 'Joomla'}
            {ts 1=$title}When your page is active, create front-end links to the contribution page using the Menu Manager. Select <strong>Administer CiviCRM &raquo; CiviContribute &raquo; Manage Contribution Pages</strong> and select <strong>%1</strong> for the contribution page.{/ts}
          {/if}
        </td>
      </tr>
    {/if}
  </table>
  {include file="CRM/common/customDataBlock.tpl" customDataType='ContributionPage' customDataSubType=$financialTypeId cid=''}
  <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
</div>

{literal}
<script type="text/javascript">
  CRM.$(function($) {
    const $form = $('form.{/literal}{$form.formClass}{literal}');
    $('#financial_type_id', $form).change(function() {
      CRM.buildCustomData('ContributionPage', $(this).val(), false, false, false, false, false, false);
    });
    const $elements = $('input[name=frontend_title], input[name=title]', $form);
    if ($elements.length === 2) {
      CRM.utils.syncFields($elements.first(), $elements.last());
    }
  });
</script>
{/literal}

{include file="CRM/common/showHideByFieldValue.tpl"
  trigger_field_id    ="is_organization"
  trigger_value       = 1
  target_element_id   ="for_org_text"
  target_element_type ="table-row"
  field_type          ="radio"
  invert              = 0
}

{include file="CRM/common/showHideByFieldValue.tpl"
  trigger_field_id    ="is_organization"
  trigger_value       = 1
  target_element_id   ="for_org_option"
  target_element_type ="table-row"
  field_type          ="radio"
  invert              = 0
}
<script type="text/javascript">
 showHonor();
 {literal}
  function showHonor() {
    var checkbox = document.getElementsByName("honor_block_is_active");
    if (checkbox[0].checked) {
      document.getElementById("honor").style.display = "block";
    } else {
      document.getElementById("honor").style.display = "none";
    }
  }
 {/literal}
</script>
{/crmRegion}
{crmRegion name="contribute-form-contributionpage-settings-post"}
{/crmRegion}
