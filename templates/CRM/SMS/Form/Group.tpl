{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{if $groupCount == 0 and $mailingCount == 0}
  <div class="status">
  {icon icon="fa-info-circle"}{/icon}
        {ts}To send a Mass SMS, you must have a valid group of recipients - either at least one group that's a Mailing List{/ts}
  </div>
{else}
<div class="crm-block crm-form-block crm-mailing-group-form-block">
{include file="CRM/common/WizardHeader.tpl"}

  <table class="form-layout">
   <tr class="crm-mailing-group-form-block-name"><td class="label">{$form.name.label}</td><td>{$form.name.html} {help id="sms-name"}</td></tr>
   <tr class="crm-mailing-upload-form-block-sms_provider_id"><td class="label">{$form.sms_provider_id.label}</td><td>{$form.sms_provider_id.html}  {help id ="id-sms_provider" isAdmin=$isAdmin}</td></tr>
  </table>


<div id="id-additional" class="form-item">
<details class="crm-accordion-bold " open>
 <summary>
{ts}Mailing Recipients{/ts}
 </summary>
 <div class="crm-accordion-body">
  {strip}

  <table>
  {if $groupCount > 0}
    <tr class="crm-mailing-group-form-block-includeGroups"><td class="label">{$form.includeGroups.label} {help id="include-groups"}</td></tr>
    <tr class="crm-mailing-group-form-block-includeGroups"><td>{$form.includeGroups.html}</td></tr>
    <tr class="crm-mailing-group-form-block-excludeGroups"><td class="label">{$form.excludeGroups.label} {help id="exclude-groups"}</td></tr>
    <tr class="crm-mailing-group-form-block-excludeGroups"><td>{$form.excludeGroups.html}</td></tr>
  {/if}
  {if $mailingCount > 0}
  <tr class="crm-mailing-group-form-block-includeMailings"><td class="label">{$form.includeMailings.label} {help id="include-mailings"}</td></tr>
  <tr class="crm-mailing-group-form-block-includeMailings"><td>{$form.includeMailings.html}</td></tr>
  <tr class="crm-mailing-group-form-block-excludeMailings"><td class="label">{$form.excludeMailings.label} {help id="exclude-mailings"}</td></tr>
  <tr class="crm-mailing-group-form-block-excludeMailings"><td>{$form.excludeMailings.html}</td></tr>
  {/if}
  </table>

  {/strip}
 </div>
</details>

  <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location=''}</div>
</div>

</div>
{/if}
