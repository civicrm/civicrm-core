{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{* This file builds html for Contact Display Name inline edit *}
<div class="crm-inline-edit-form">
  <div class="crm-inline-button">
    {include file="CRM/common/formButtons.tpl" location=''}
  </div>
  {crmRegion name="contact-form-inline-contactname"}
    {if $contactType eq 'Individual'}
      {if !empty($form.prefix_id)}
        <div class="crm-inline-edit-field">
          {$form.prefix_id.label nofilter}<br/>
          {$form.prefix_id.html nofilter}
        </div>
      {/if}
      {if !empty($form.formal_title)}
        <div class="crm-inline-edit-field">
          {$form.formal_title.label nofilter}<br/>
          {$form.formal_title.html nofilter}
        </div>
      {/if}
      {if !empty($form.first_name)}
        <div class="crm-inline-edit-field">
          {$form.first_name.label nofilter}<br />
          {$form.first_name.html nofilter}
        </div>
      {/if}
      {if !empty($form.middle_name)}
        <div class="crm-inline-edit-field">
          {$form.middle_name.label nofilter}<br />
          {$form.middle_name.html nofilter}
        </div>
      {/if}
      {if !empty($form.last_name)}
        <div class="crm-inline-edit-field">
          {$form.last_name.label nofilter}<br />
          {$form.last_name.html nofilter}
        </div>
      {/if}
      {if !empty($form.suffix_id)}
        <div class="crm-inline-edit-field">
          {$form.suffix_id.label nofilter}<br/>
          {$form.suffix_id.html nofilter}
        </div>
      {/if}
    {elseif $contactType eq 'Organization'}
      <div class="crm-inline-edit-field">{$form.organization_name.label nofilter}&nbsp;
      {$form.organization_name.html nofilter}</div>
    {elseif $contactType eq 'Household'}
      <div class="crm-inline-edit-field">{$form.household_name.label nofilter}&nbsp;
      {$form.household_name.html nofilter}</div>
    {/if}
  {/crmRegion}
</div>
<div class="clear"></div>
