{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
<div class="crm-block crm-form-block crm-contribution-search_contribution-form-block">
  <details class="crm-accordion-bold crm-block crm-form-block crm-contribution-search_contribution-form-block" open="">
    <summary>{ts}Find Contribution Pages{/ts}</summary>
    <div class="crm-accordion-body">
      <div class="float-right">
        {include file="CRM/common/formButtons.tpl" location=''}
      </div>
      <div class="advanced-search-fields form-layout" style="max-width: 90%;">
        <div class="search-field">
          {$form.title.label}
          {$form.title.html|crmAddClass:twenty}
        </div>
        <div class="search-field">
          {$form.financial_type_id.label}
          {$form.financial_type_id.html}
        </div>
        <div class="search-field">
          {include file="CRM/Campaign/Form/addCampaignToSearch.tpl" campaignTrClass='' campaignTdClass=''}
        </div>
      </div>
    </div>
  </details>
</div>
