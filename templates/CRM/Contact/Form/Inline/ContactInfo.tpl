{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
<div class="crm-inline-edit-form">
  <div class="crm-inline-button">
    {include file="CRM/common/formButtons.tpl" location=''}
  </div>

  <div class="crm-clear">
    {if $contactType eq 'Individual'}
    <div class="crm-summary-row">
      <div class="crm-label">{$form.employer_id.label}&nbsp;{help id="employer_id" file="CRM/Contact/Form/Contact"}</div>
      <div class="crm-content">
        {$form.employer_id.html|crmAddClass:big}
      </div>
    </div>
    <div class="crm-summary-row">
      <div class="crm-label">{$form.job_title.label}</div>
      <div class="crm-content">{$form.job_title.html}</div>
    </div>
    {/if}
    <div class="crm-summary-row">
      <div class="crm-label">{$form.nick_name.label}</div>
      <div class="crm-content">{$form.nick_name.html}</div>
    </div>
    {if $contactType eq 'Organization'}
    <div class="crm-summary-row">
      <div class="crm-label">{$form.legal_name.label}</div>
      <div class="crm-content">{$form.legal_name.html}</div>
    </div>
    <div class="crm-summary-row">
      <div class="crm-label">{$form.sic_code.label}</div>
      <div class="crm-content">{$form.sic_code.html}</div>
    </div>
    {/if}
    <div class="crm-summary-row">
      <div class="crm-label">{$form.contact_source.label}</div>
      <div class="crm-content">{$form.contact_source.html}</div>
    </div>
    {if ($contactType eq 'Organization') OR ($contactType eq 'Household')}
    <div class="crm-summary-row">
      <div class="crm-label"></div>
      <div class="crm-content">{$form.is_deceased.html}{$form.is_deceased.label}</div>
    </div>
    <div class="crm-summary-row" id="showDeceasedDate">
      <div class="crm-label">{$form.deceased_date.label}</div>
      <div class="crm-content">{$form.deceased_date.html}</div>
    </div>
    {/if}
  </div> <!-- end of main -->
</div>

{include file="CRM/Contact/Form/ShowDeceasedDate.js.tpl"}
