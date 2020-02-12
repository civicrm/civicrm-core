{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{$form.oplock_ts.html}
<div class="crm-inline-edit-form">
  <div class="crm-inline-button">
    {include file="CRM/common/formButtons.tpl"}
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
    <div class="crm-summary-row">
      <div class="crm-label crm-deceased-date">{$form.deceased_date.label}</div>
      <div class="crm-content crm-deceased-date">
        {$form.deceased_date.html}
      </div>
    </div>

    {* Begin civicrm_engage customization *}
    {if isset($demographics_groupTree)}
      {foreach from=$demographics_groupTree item=cd_edit key=group_id}
        {foreach from=$cd_edit.fields item=element key=field_id}
          <table class="form-layout-compressed">
            {include file="CRM/Custom/Form/CustomField.tpl"}
          </table>
        {/foreach}
      {/foreach}
    {/if}
    {* End civicrm_engage customization *}

  </div>
</div> <!-- end of main -->

{literal}
<script type="text/javascript">
function showDeceasedDate( ) {
  if ( cj("#is_deceased").is(':checked') ) {
    cj(".crm-deceased-date").show( );
  } else {
    cj(".crm-deceased-date").hide( );
    cj("#deceased_date").val('');
  }
}

CRM.$(function($) {
  showDeceasedDate( );
});
</script>
{/literal}
