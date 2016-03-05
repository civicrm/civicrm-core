
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
<div class="crm-block crm-form-block crm-contribution-contributionpage-settings-form-block">
<div class="help">
    {if $action eq 0}
        <p>{ts}This is the first step in creating a new online Contribution Page. You can create one or more different Contribution Pages for different purposes, audiences, campaigns, etc. Each page can have it's own introductory message, pre-configured contribution amounts, custom data collection fields, etc.{/ts}</p>
        <p>{ts}In this step, you will configure the page title, financial type (donation, campaign contribution, etc.), goal amount, and introductory message. You will be able to go back and modify all aspects of this page at any time after completing the setup wizard.{/ts}</p>
    {else}
        {ts}Use this form to edit the page title, financial type (e.g. donation, campaign contribution, etc.), goal amount, introduction, and status (active/inactive) for this online contribution page.{/ts}
    {/if}
</div>
    <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="top"}</div>
  <table class="form-layout-compressed">
  <tr class="crm-contribution-contributionpage-settings-form-block-title"><td class="label">{$form.title.label} {if $action == 2}{include file='CRM/Core/I18n/Dialog.tpl' table='civicrm_contribution_page' field='title' id=$contributionPageID}{/if}</td><td>{$form.title.html}<br/>
            <span class="description">{ts}This title will be displayed at the top of the page.<br />Please use only alphanumeric, spaces, hyphens and dashes for Title.{/ts}</td>
  </tr>
  <tr class="crm-contribution-contributionpage-settings-form-block-financial_type_id"><td class="label">{$form.financial_type_id.label}</td><td>{$form.financial_type_id.html}<br />
            <span class="description">{ts}Select the corresponding financial type for contributions made using this page.{/ts}</span> {help id="id-financial_type"}</td>
  </tr>

  {* CRM-7362 --add campaign to contribution page *}
  {include file="CRM/Campaign/Form/addCampaignToComponent.tpl"
  campaignTrClass="crm-contribution-contributionpage-settings-form-block-campaign_id"}

  <tr class="crm-contribution-contributionpage-settings-form-block-is_organization"><td>&nbsp;</td><td>{$form.is_organization.html} {$form.is_organization.label} {help id="id-is_organization"}</td></tr>
  <tr id="for_org_option" class="crm-contribution-form-block-is_organization">
        <td>&nbsp;</td>
        <td>
            <table class="form-layout-compressed">
            <tr class="crm-contribution-for_organization_help">
                <td class="description" colspan="2">
                    {capture assign="profileURL"}{crmURL p='civicrm/admin/uf/group' q='reset=1'}{/capture}
                    {if $invalidProfiles}
                      {ts 1=$profileURL}You must <a href="%1">configure a valid organization profile</a> in order to allow individuals to contribute on behalf of an organization. Valid profiles include Contact and / or Organization fields, and may include Contribution and Membership fields.{/ts}
                    {else}
                      {ts 1=$profileURL}To change the organization data collected use the "On Behalf Of Organization" profile (<a href="%1">Administer > Customize Data and Screens > Profiles</a>).{/ts}
                    {/if}
                </td>
            </tr>
            {if !$invalidProfiles}
              <tr class="crm-contribution-onbehalf_profile_id">
                <td class="label">{$form.onbehalf_profile_id.label}</td>
                <td>{$form.onbehalf_profile_id.html}</td>
              </tr>
            {/if}
            <tr id="for_org_text" class="crm-contribution-contributionpage-settings-form-block-for_organization">
                <td class="label">{$form.for_organization.label}</td>
                <td>{$form.for_organization.html}<br />
                    <span class="description">{ts}Text displayed next to the checkbox on the contribution form.{/ts}</span>
                </td>
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
      <td class ="label">{$form.intro_text.label}<br /> {if $action == 2}{include file='CRM/Core/I18n/Dialog.tpl' table='civicrm_contribution_page' field='intro_text' id=$contributionPageID}{/if} {help id="id-intro_msg"}</td><td>{$form.intro_text.html}</td>
  </tr>
  <tr class="crm-contribution-contributionpage-settings-form-block-footer_text">
      <td class ="label">{$form.footer_text.label}<br /> {if $action == 2}{include file='CRM/Core/I18n/Dialog.tpl' table='civicrm_contribution_page' field='footer_text' id=$contributionPageID}{/if} {help id="id-footer_msg"}</td><td>{$form.footer_text.html}</td>
  </tr>
  <tr class="crm-contribution-contributionpage-settings-form-block-goal_amount">
      <td class ="label">{$form.goal_amount.label}</td><td>{$form.goal_amount.html} {help id="id-goal_amount"}</td>
  </tr>
  <tr class="crm-contribution-contributionpage-settings-form-block-start_date">
      <td class ="label">{$form.start_date.label} {help id="id-start_date"}</td>
      <td>
          {include file="CRM/common/jcalendar.tpl" elementName=start_date}
      </td>
    </tr>
  <tr class="crm-contribution-contributionpage-settings-form-block-end_date">
      <td class ="label">{$form.end_date.label}</td>
      <td>
          {include file="CRM/common/jcalendar.tpl" elementName=end_date}
      </td>
    </tr>
  <tr class="crm-contribution-contributionpage-settings-form-block-honor_block_is_active">
      <td>&nbsp;</td><td>{$form.honor_block_is_active.html}{$form.honor_block_is_active.label} {help id="id-honoree_section"}</td>
  </tr>
</table>
<table class="form-layout-compressed" id="honor">
    <tr class="crm-contribution-contributionpage-settings-form-block-honor_block_title">
        <td class="label">
            {$form.honor_block_title.label}
       </td>
       <td>
           {$form.honor_block_title.html}<br />
           <span class="description">{ts}Title for the Honoree section (e.g. &quot;Honoree Information&quot;).{/ts}</span>
       </td>
   </tr>
   <tr class="crm-contribution-contributionpage-settings-form-block-honor_block_text">
       <td class="label">
           {crmAPI var='result' entity='OptionGroup' action='get' sequential=1 name='soft_credit_type'}
           {$form.honor_block_text.label}
       </td>
       <td>
           {$form.honor_block_text.html}<br />
           <span class="description">{ts}Optional explanatory text for the Honoree section (displayed above the Honoree fields).{/ts}</span>
       </td>
  </tr>
  <tr class="crm-contribution-contributionpage-settings-form-block-honor_soft_credit_types">
      <td class="label">
          {$form.soft_credit_types.label}
      </td>
      <td>
        {$form.soft_credit_types.html}
      </td>
  </tr>
  <tr class="crm-contribution-contributionpage-custom-form-block-custom_pre_id">
      <td class="label">
          {$form.honoree_profile.label}
      </td>
      <td class="html-adjust">
          {$form.honoree_profile.html}
          <span class="description">{ts}Profile to be included in the honoree section{/ts}</span>
      </td>
   </tr>
</table>
<table class="form-layout-compressed">
        <tr class="crm-contribution-contributionpage-settings-form-block-is_confirm_enabled">
        <td>&nbsp;</td>
        <td>{$form.is_confirm_enabled.html} {$form.is_confirm_enabled.label}<br />
        <span class="description">{ts}If you disable this contributions will be processed immediately after submitting the contribution form.{/ts}</span></td>
      </tr>
        <tr class="crm-contribution-contributionpage-settings-form-block-is_share">
        <td>&nbsp;</td>
        <td>{$form.is_share.html} {$form.is_share.label} {help id="id-is_share"}</td>
      </tr>
    <tr class="crm-contribution-contributionpage-settings-form-block-is_active"><td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td><td>{$form.is_active.html} {$form.is_active.label}<br />
  {if $contributionPageID}
        <span class="description">
          {if $config->userSystem->is_drupal || $config->userFramework EQ 'WordPress'}
              {ts}When your page is active, you can link people to the page by copying and pasting the following URL:{/ts}<br />
              <strong>{crmURL a=1 fe=1 p='civicrm/contribute/transact' q="reset=1&id=`$contributionPageID`"}</strong>
          {elseif $config->userFramework EQ 'Joomla'}
              {ts 1=$title}When your page is active, create front-end links to the contribution page using the Menu Manager. Select <strong>Administer CiviCRM &raquo; CiviContribute &raquo; Manage Contribution Pages</strong> and select <strong>%1</strong> for the contribution page.{/ts}
          {/if}
    </span>
      {/if}
  </td>
  </tr>
       </table>
   <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
</div>

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
