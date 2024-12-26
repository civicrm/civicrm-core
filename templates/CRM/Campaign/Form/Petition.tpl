{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}

<div class="crm-block crm-form-block crm-campaign-survey-form-block">
  {if $action eq 8}
    <div class="messages status no-popup">
      {icon icon="fa-info-circle"}{/icon}
      {ts}Are you sure you want to delete this Petition?{/ts}
    </div>
  {else}
    {if $action  eq 1}
      <div class="help">{ts}Use this form to Add new Survey. You can create a new Activity type, specific to this Survey or select an existing activity type for this Survey.{/ts}</div>
    {/if}
    <table class="form-layout">
      <tr class="crm-campaign-survey-form-block-title">
        <td class="label">{$form.title.label}</td>
        <td>{$form.title.html}
      </tr>
      <tr class="crm-campaign-survey-form-block-instructions">
        <td class="label">{$form.instructions.label}{if $action == 2}{include file='CRM/Core/I18n/Dialog.tpl' table='civicrm_survey' field='instructions' id=$surveyId}{/if}</td>
        <td class="view-value">{$form.instructions.html}
      </tr>
      <tr class="crm-campaign-survey-form-block-campaign_id">
        <td class="label">{$form.campaign_id.label}</td>
        <td>{$form.campaign_id.html}
      </tr>
      {if array_key_exists('activity_type_id', $form)}
      <tr class="crm-campaign-survey-form-block-activity_type_id">
        <td class="label">{$form.activity_type_id.label}</td>
        <td>{$form.activity_type_id.html}
      </tr>
      {/if}
      <tr class="crm-campaign-survey-form-block-profile_id">
        <td class="label">{$form.contact_profile_id.label}</td>
        <td>{$form.contact_profile_id.html}&nbsp;<span class="profile-links"></span>
          <div class="description">{ts}Fields about the contact you want to collect.{/ts}</div>
        </td>
      </tr>
      <tr class="crm-campaign-survey-form-block-profile_id">
        <td class="label">{$form.profile_id.label}</td>
        <td>{$form.profile_id.html}&nbsp;<span class="profile-links"></span>
          <div class="description">{ts}Fields about the petition.{/ts}</div>
          <div class="profile-create">
            <a href="{crmURL p='civicrm/admin/uf/group/add' q='reset=1&action=add'}"
               target="_blank">{ts}Click here for new profile{/ts}
          </div>
        </td>
      </tr>
      <tr class="crm-campaign-survey-form-block-thankyou_title">
        <td
          class="label">{$form.thankyou_title.label}{if $action == 2}{include file='CRM/Core/I18n/Dialog.tpl' table='civicrm_survey' field='thankyou_title' id=$surveyId}{/if}</td>
        <td>{$form.thankyou_title.html}</td>
      </tr>
      <tr class="crm-campaign-survey-form-block-thankyou_text">
        <td
          class="label">{$form.thankyou_text.label}{if $action == 2}{include file='CRM/Core/I18n/Dialog.tpl' table='civicrm_survey' field='thankyou_text' id=$surveyId}{/if}</td>
        <td>{$form.thankyou_text.html}</td>
      </tr>
      <tr class="crm-campaign-survey-form-block-bypass_confirm">
        <td class="label">{$form.bypass_confirm.label}</td>
        <td>{$form.bypass_confirm.html}
          <div class="description">{ts}Disable the email confirmation for unverified contacts?{/ts}</div>
        </td>
      </tr>
      <tr class="crm-campaign-survey-form-block-is_share">
        <td class="label">{$form.is_share.label}</td>
        <td>{$form.is_share.html}
      </tr>
      <tr class="crm-campaign-survey-form-block-is_active">
        <td class="label">{$form.is_active.label}</td>
        <td>{$form.is_active.html}</td>
      </tr>
      <tr class="crm-campaign-survey-form-block-is_default">
        <td class="label">{$form.is_default.label}</td>
        <td>{$form.is_default.html}</td>
      </tr>
      <tr class="crm-campaign-survey-form-block-links">
        <td class="label"><label>{ts}Links to sign this petition{/ts}</label></td>
        <td>
          {if $surveyId}
            {ts}Public{/ts}: <pre>{$config->userFrameworkBaseURL}civicrm/petition/sign?sid={$surveyId}&amp;reset=1</pre><br/>
            {ts}CiviMail{/ts}: <pre>{$config->userFrameworkBaseURL}civicrm/petition/sign?sid={$surveyId}&amp;reset=1&amp;&#123;contact.checksum&#125;&amp;cid=&#123;contact.contact_id&#125;</pre></br/>
            <div class="description">{ts}Copy and paste the public link anywhere on the Internet, including social media. The CiviMail link should only be copied into a CiviMail message. It will pre-populate the profile with existing information for the person who receives the email.{/ts}</div>
          {else}
            <div class="description">{ts}The links will be visible after you save the petition.{/ts}</div>
          {/if}
        </td>
      </tr>
    </table>
    {include file="CRM/common/customDataBlock.tpl"  groupID='' customDataType='Survey' customDataSubType=false entityID=$surveyId cid=false}
  {/if}
  <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
</div>

{*include profile link function*}
{include file="CRM/common/buildProfileLink.tpl"}

{literal}
  <script type="text/javascript">
    //show edit profile field links
    CRM.$(function($) {
      // show edit for both contact and activity profile
      $('select[id$="profile_id"]').change(function () {
        buildLinks($(this), $(this).val());
      });

      // make sure we set edit links for both profiles when form loads
      $('select[id$="profile_id"]').each(function (e) {
        buildLinks($(this), $(this).val());
      });
    });
  </script>
{/literal}
