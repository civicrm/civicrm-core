{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
<details class="crm-accordion-bold crm-demographics-accordion">
 <summary>
    {$title}
  </summary>
  <div id="demographics" class="crm-accordion-body">
  <div class="form-item">
        <span class="label">{$form.gender_id.label}</span>

  <span class="value">
        {$form.gender_id.html}
        </span>
  </div>
  <div class="form-item">
        <span class="label">{$form.birth_date.label}</span>
        <span class="fields">{$form.birth_date.html}</span>
  </div>
  <div class="form-item">
       {$form.is_deceased.html}
       {$form.is_deceased.label}
  </div>
  <div id="showDeceasedDate" class="form-item">
       <span class="label">{$form.deceased_date.label}</span>
       <span class="fields">{$form.deceased_date.html}</span>
  </div>
 </div>
</details>

{include file="CRM/Contact/Form/ShowDeceasedDate.js.tpl"}
