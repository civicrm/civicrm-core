{*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2016                                |
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
<div class="help">
    {ts}Personalize the contents and appearance of your personal campaign page here. You will be able to return to this page and make changes at any time.{/ts}
</div>
<fieldset class="crm-pcp-campaign-group">
<div class="crm-block crm-contribution-campaign-form-block">
{crmRegion name="pcp-form-campaign"}
  <div class="crm-section crm-pcp-title-section crm-contribution-form-block-title">
    <div class="label">{$form.pcp_title.label}</div>
    <div class="content">
      {$form.pcp_title.html|crmAddClass:big}
    </div>
    <div class="clear"></div>
  </div>
  <div class="crm-section crm-pcp-intro_text-section crm-contribution-form-block-intro_text">
    <div class="label">{$form.pcp_intro_text.label}</div>
    <div class="content">
      {$form.pcp_intro_text.html|crmAddClass:big}
      <div class="description">{ts}Introduce the campaign and why you're supporting it. This text will appear at the top of your personal page AND at the top of the main contribution or event registration page.{/ts}</div>
    </div>
    <div class="clear"></div>
  </div>
  <div class="crm-section crm-pcp-goal_amount-section crm-contribution-form-block-goal_amount">
    <div class="label">{$form.goal_amount.label}</div>
    <div class="content">
      {$form.goal_amount.html|crmAddClass:six}
      <div class="description">{ts}Total amount you would like to raise for this campaign.{/ts}</div>
    </div>
    <div class="clear"></div>
  </div>
  <div class="crm-section crm-pcp-is_thermometer-section crm-contribution-form-block-is_thermometer">
    <div class="label">{$form.is_thermometer.label}</div>
    <div class="content">
      {$form.is_thermometer.html}
      <div class="description">{ts}If this option is checked, a "thermometer" showing progress toward your goal will be included on the page.{/ts}</div>
    </div>
    <div class="clear"></div>
  </div>
  <div class="crm-section crm-pcp-donate_link_text-section crm-contribution-form-block-donate_link_text">
    <div class="label">{$form.donate_link_text.label}</div>
    <div class="content">
      {$form.donate_link_text.html}
      <div class="description">{ts}The text for the contribute or register button.{/ts}</div>
    </div>
    <div class="clear"></div>
  </div>
  <div class="crm-section crm-pcp-page_text-section crm-contribution-form-block-page_text">
    <div class="label">{$form.page_text.label}</div>
    <div class="content">
      {$form.page_text.html|crmAddClass:huge}
      <div class="description">{ts}Tell people why this campaign is important to you.{/ts}</div>
    </div>
    <div class="clear"></div>
  </div>
{include file="CRM/Form/attachment.tpl" context="pcpCampaign"}
  <div class="crm-section crm-pcp-is_honor_roll-section crm-contribution-form-block-is_honor_roll">
    <div class="label">{$form.is_honor_roll.label}</div>
    <div class="content">
      {$form.is_honor_roll.html}
      <div class="description">{ts}If this option is checked, an "honor roll" will be displayed with the names (or nicknames) of the people who supported you. (Donors will have the option to remain anonymous. Their names will NOT be listed.){/ts}</div>{* [ML] string changed #9704 *}
    </div>
    <div class="clear"></div>
  </div>
  {if $owner_notification_option}
    <div class="crm-section crm-pcp-is_notify-section crm-contribution-form-block-is_notify">
      <div class="label">{$form.is_notify.label}</div>
      <div class="content">
        {$form.is_notify.html}
        <div class="description">{ts}If this option is checked, you will receive an email notification when people contribute to your campaign.{/ts}</div>
      </div>
      <div class="clear"></div>
    </div>
  {/if}
  <div class="crm-section crm-pcp-is_active crm-contribution-form-block-is_active">
    <div class="label">{$form.is_active.label}</div>
    <div class="content">
      {$form.is_active.html}
      <div class="description">{ts}Is your Personal Campaign Page active? You can activate/de-activate it any time during it's lifecycle.{/ts}</div>
    </div>
    <div class="clear"></div>
  </div>
{/crmRegion}
</div>
<div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
</fieldset>
<script type="text/javascript">
    // Always open attachment div by default for this form
    cj('#attachments').show();
</script>
