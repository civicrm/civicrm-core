{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{* data-edit-params to reload this info whenever relationship gets updated *}
<div id="crm-contactinfo-content" {if $permission EQ 'edit'} class="crm-inline-edit" {/if} data-edit-params='{ldelim}"cid": "{$contactId}", "class_name": "CRM_Contact_Form_Inline_ContactInfo"{rdelim}'>
  <div class="crm-clear crm-inline-block-content" {if $permission EQ 'edit'}title="{ts escape='htmlattribute'}Edit info{/ts}"{/if}>
    {if $permission EQ 'edit'}
    <div class="crm-edit-help">
      <span class="crm-i fa-pencil" aria-hidden="true"></span> {ts}Edit info{/ts}
    </div>
    {/if}

      {if $contact_type eq 'Individual'}
      <div class="crm-summary-row">
        <div class="crm-label">{ts}Employer{/ts}</div>
        <div class="crm-content crm-contact-current_employer">
          {if !empty($current_employer_id)}
          <a href="{crmURL p='civicrm/contact/view' q="reset=1&cid=`$current_employer_id`"}" title="{ts escape='htmlattribute'}view current employer{/ts}">{$current_employer}</a>
          {/if}
        </div>
      </div>
      <div class="crm-summary-row">
        <div class="crm-label">{ts}Job Title{/ts}</div>
        <div class="crm-content crm-contact-job_title">{$job_title}</div>
      </div>
      {/if}
      <div class="crm-summary-row">
        <div class="crm-label">{ts}Nickname{/ts}</div>
        <div class="crm-content crm-contact-nick_name">{$nick_name}</div>
      </div>

      {if $contact_type eq 'Organization'}
      <div class="crm-summary-row">
        <div class="crm-label">{ts}Legal Name{/ts}</div>
        <div class="crm-content crm-contact-legal_name">{$legal_name}</div>
      </div>
      <div class="crm-summary-row">
        <div class="crm-label">{ts}SIC Code{/ts}</div>
        <div class="crm-content crm-contact-sic_code">{$sic_code}</div>
      </div>
      {/if}
      <div class="crm-summary-row">
        <div class="crm-label">{ts}Contact Source{/ts}</div>
        <div class="crm-content crm-contact_source">{$source}</div>
      </div>

    </div>
</div>
