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
    <div class="crm-summary-row">
      <div class="crm-label">{$form.gender_id.label}</div>
      <div class="crm-content">{$form.gender_id.html}</div>
    </div>
    <div class="crm-summary-row">
      <div class="crm-label">{$form.birth_date.label}</div>
      <div class="crm-content">
        {$form.birth_date.html}
      </div>
    </div>
    <div class="crm-summary-row">
      <div class="crm-label">&nbsp;</div>
      <div class="crm-content">
        {$form.is_deceased.html}
        {$form.is_deceased.label}
      </div>
    </div>
    <div class="crm-summary-row" id="showDeceasedDate">
      <div class="crm-label crm-deceased-date">{$form.deceased_date.label}</div>
      <div class="crm-content crm-deceased-date">
        {$form.deceased_date.html}
      </div>
    </div>
  </div>
</div> <!-- end of main -->

{include file="CRM/Contact/Form/ShowDeceasedDate.js.tpl"}

