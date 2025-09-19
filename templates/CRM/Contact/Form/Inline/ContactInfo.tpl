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
      <div class="crm-label">{$form.employer_id.label nofilter}&nbsp;{help id="employer_id" file="CRM/Contact/Form/Contact"}</div>
      <div class="crm-content">
        {$form.employer_id.html|crmAddClass:big nofilter}
      </div>
    </div>
    <div class="crm-summary-row">
      <div class="crm-label">{$form.job_title.label nofilter}</div>
      <div class="crm-content">{$form.job_title.html nofilter}</div>
    </div>
    {/if}
    <div class="crm-summary-row">
      <div class="crm-label">{$form.nick_name.label nofilter}</div>
      <div class="crm-content">{$form.nick_name.html nofilter}</div>
    </div>
    {if $contactType eq 'Organization'}
    <div class="crm-summary-row">
      <div class="crm-label">{$form.legal_name.label nofilter}</div>
      <div class="crm-content">{$form.legal_name.html nofilter}</div>
    </div>
    <div class="crm-summary-row">
      <div class="crm-label">{$form.sic_code.label nofilter}</div>
      <div class="crm-content">{$form.sic_code.html nofilter}</div>
    </div>
    {/if}
    <div class="crm-summary-row">
      <div class="crm-label">{$form.contact_source.label nofilter}</div>
      <div class="crm-content">{$form.contact_source.html nofilter}</div>
    </div>
    {if ($contactType eq 'Organization') OR ($contactType eq 'Household')}
    <div class="crm-summary-row">
      <div class="crm-label"></div>
      <div class="crm-content">{$form.is_deceased.html nofilter}{$form.is_deceased.label nofilter}</div>
    </div>
    <div class="crm-summary-row" id="showDeceasedDate">
      <div class="crm-label">{$form.deceased_date.label nofilter}</div>
      <div class="crm-content">{$form.deceased_date.html nofilter}</div>
    </div>
    {/if}
  </div> <!-- end of main -->
</div>

{include file="CRM/Contact/Form/ShowDeceasedDate.js.tpl"}
