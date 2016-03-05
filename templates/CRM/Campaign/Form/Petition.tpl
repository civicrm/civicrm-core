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

<div class="crm-block crm-form-block crm-campaign-survey-form-block">
  <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="top"}</div>
  {if $action eq 8}
    <table class="form-layout">
      <tr>
        <td colspan="2">
          <div class="status">
            <div class="icon inform-icon"></div>
            &nbsp;{ts}Are you sure you want to delete this Petition?{/ts}</div>
        </td>
      </tr>
    </table>
  {else}
    {if $action  eq 1}
      <div class="help">
        {ts}Use this form to Add new Survey. You can create a new Activity type, specific to this Survey or select an existing activity type for this Survey.{/ts}
      </div>
    {/if}
    <table class="form-layout">
      <tr class="crm-campaign-survey-form-block-title">
        <td class="label">{$form.title.label}</td>
        <td>{$form.title.html}
      </tr>
      <tr class="crm-campaign-survey-form-block-instructions">
        <td class="label">{$form.instructions.label}</td>
        <td class="view-value">{$form.instructions.html}
      </tr>
      <tr class="crm-campaign-survey-form-block-campaign_id">
        <td class="label">{$form.campaign_id.label}</td>
        <td>{$form.campaign_id.html}
      </tr>
      <tr class="crm-campaign-survey-form-block-activity_type_id">
        <td class="label">{$form.activity_type_id.label}</td>
        <td>{$form.activity_type_id.html}
      </tr>
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
          class="label">{$form.thankyou_title.label}{if $action == 2}{include file='CRM/Core/I18n/Dialog.tpl' table='civicrm_contribution_page' field='thankyou_title' id=$contributionPageID}{/if}</td>
        <td>{$form.thankyou_title.html}<br/>

          <div class="description">{ts}This title will be displayed at the top of the thank-you page.{/ts}</div>
        </td>
      </tr>
      <tr class="crm-campaign-survey-form-block-thankyou_text">
        <td
          class="label">{$form.thankyou_text.label}{if $action == 2}{include file='CRM/Core/I18n/Dialog.tpl' table='civicrm_contribution_page' field='thankyou_text' id=$contributionPageID}{/if}</td>
        <td>{$form.thankyou_text.html}<br/>

          <div
            class="description">{ts}Enter text (and optional HTML layout tags) for the thank-you message that will appear at the top of the thank-you page.{/ts}</div>
        </td>
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
        <td>{$form.is_active.html}
          <div class="description">{ts}Is this petition active?{/ts}</div>
        </td>
      </tr>
      <tr class="crm-campaign-survey-form-block-is_default">
        <td class="label">{$form.is_default.label}</td>
        <td>{$form.is_default.html}
          <div class="description">{ts}Is this the default petition?{/ts}</div>
        </td>
      </tr>
    </table>
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
