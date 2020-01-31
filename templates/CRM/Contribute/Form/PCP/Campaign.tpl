{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
<div class="help">
    {ts}Personalize the contents and appearance of your fundraising page here. You will be able to return to this page and make changes at any time.{/ts}
</div>
<fieldset>
<div class="crm-block crm-contribution-campaign-form-block">
<table class="form-layout-compressed" width="100%">
  <tr class="crm-contribution-form-block-title">
    <td class="label">{$form.title.label}</td>
    <td>{$form.title.html|crmAddClass:big}</td>
  </tr>
  <tr class="crm-contribution-form-block-intro_text">
    <td class="label">{$form.intro_text.label}</td>
    <td>
            {$form.intro_text.html|crmAddClass:big}<br />
            <span class="description">{ts}Introduce the campaign and why you're supporting it. This text will appear at the top of your personal page AND at the top of the main campaign contribution page when people make a contribution through your page.{/ts}</span>
        </td>
  </tr>
  <tr class="crm-contribution-form-block-goal_amount">
    <td class="label">{$form.goal_amount.label}</td>
    <td>{$form.goal_amount.html|crmAddClass:six}<br />
            <span class="description">{ts}Total amount you would like to raise for this campaign.{/ts}</span>
    </td>
  </tr>
  <tr class="crm-contribution-form-block-is_thermometer">
    <td class="label">{$form.is_thermometer.label}</td>
    <td>{$form.is_thermometer.html}
            <span class="description">{ts}If this option is checked, a "thermometer" showing progress toward your goal will be included on the page.{/ts}</span>
        </td>
  </tr>
  <tr class="crm-contribution-form-block-donate_link_text">
    <td class="label">{$form.donate_link_text.label}</td>
    <td>{$form.donate_link_text.html}<br />
            <span class="description">{ts}The text for the contribute button.{/ts}</span>
    </td>
  </tr>
  <tr class="crm-contribution-form-block-page_text">
    <td class="label" width="15%">{$form.page_text.label}</td>
    <td width="85%">
            <span class="description">{ts}Tell people why this campaign is important to you.{/ts}</span><br />
            {$form.page_text.html|crmAddClass:huge}
        </td>
  </tr>
</table>
{include file="CRM/Form/attachment.tpl" context="pcpCampaign"}
<table class="form-layout-compressed">
  <tr class="crm-contribution-form-block-is_honor_roll">
    <td class="label">{$form.is_honor_roll.label}</td>
    <td>{$form.is_honor_roll.html}
    <span class="description">{ts}If this option is checked, an "honor roll" will be displayed with the names (or nicknames) of the people who donated through your fundraising page. (Donors will have the option to remain anonymous. Their names will NOT be listed.){/ts}</span></td>
  </tr>

  <tr class="crm-contribution-form-block-is_active">
    <td class="label">{$form.is_active.label}</td>
    <td>{$form.is_active.html}
            <span class="description">{ts}Is your Personal Campaign Page active? You can activate/de-activate it any time during it's lifecycle.{/ts}</span></td>
  </tr>
</table>
</div>
<div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
</fieldset>
<script type="text/javascript">
    // Always open attachment div by default for this form
    cj('#attachments').show();
</script>
