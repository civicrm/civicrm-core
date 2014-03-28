{*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.5                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2014                                |
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
{$form.oplock_ts.html}
<div class="crm-inline-edit-form">
  <div class="crm-inline-button">
    {include file="CRM/common/formButtons.tpl"}
  </div>

  <div class="crm-clear">  
    {if $contactType eq 'Individual'}
    <div class="crm-summary-row">
      <div class="crm-label">{$form.employer_id.label}&nbsp;{help id="id-current-employer" file="CRM/Contact/Form/Contact.hlp"}</div>
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
  </div> <!-- end of main -->
</div>
