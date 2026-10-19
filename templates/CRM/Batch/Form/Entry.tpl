{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
<div class="batch-entry form-item">
  <div class="help">
    {ts}Click Validate & Process below when you've entered all items for the batch. You can also Save & Continue Later at any time.{/ts}
    {crmPermission has='administer CiviCRM'}
      {capture assign=batchEntryProfileURL}{crmURL p="civicrm/admin/uf/group" q="reset=1&selectedChild=reserved-profiles"}{/capture}
      {ts 1=$batchEntryProfileURL}Add, remove or change the order of columns by editing the corresponding <a href="%1" target="_blank">Bulk Entry profile</a>.{/ts}
      {if $batchType EQ 1}
        {ts}Custom fields and a Personal Campaign Page field can be added if needed.{/ts}
      {/if}
    {/crmPermission}
  </div>
  {if $batchAmountMismatch}
    <div class="status message status-warning">
      <i class="crm-i fa-exclamation-triangle" role="img" aria-hidden="true"></i> {ts}Total for amounts entered below does not match the expected batch total.{/ts}
    </div>
    {$form._qf_Entry_upload_force.html}
    <div class="clear"></div>
  {/if}
  <table class="form-layout-compressed batch-totals">
    <tr>
      <td class="label">
        <label>{ts}Total amount expected{/ts}</label>
      </td>
      <td class="right"><span class="batch-expected-total">{$batchTotal|crmMoney}</span></td>
    </tr>
    <tr>
      <td class="label">
        <label>{ts}Total amount entered{/ts}</label>
      </td>
      <td class="right">{$defaultCurrencySymbol} <span class="batch-actual-total"></span></td>
    </tr>
  </table>

  <div class="crm-copy-fields crm-grid-table" id="crm-batch-entry-table">
    <div class="crm-grid-header">
      <div class="crm-grid-cell">&nbsp;</div>
      <div class="crm-grid-cell">{ts}Contact{/ts}</div>
      {if $batchType eq 2}
        <div class="crm-grid-cell">&nbsp;</div>
      {/if}
      {if $batchType eq 3}
        <div class="crm-grid-cell">{ts}Open Pledges (Due Date - Amount){/ts}</div>
      {/if}
      {foreach from=$fields item=field key=fieldName}
        <div class="crm-grid-cell">
          {* do not copy down for trxn_id as that would be invalid.
          The reason for the other 2 fields to be missing copy functionality
          is undocumented / lost in the mists of time.
          It may be because the amount was seen as unlikely to be a copy-down candidate
           - either that or it was left out of scope because these fields do not follow the
           naming convention in batchCopy.tpl, making it easier to exclude than address,
           or because the intention for future support for multiple credits per row was
           incompatible.
           https://issues.civicrm.org/jira/browse/CRM-12653
           *}
          {if $field.name ne 'trxn_id' && $field.name ne 'soft_credit_amount' && $field.name ne 'soft_credit'}
          {copyIcon name=$field.name title=$field.title}
          {/if}{$field.title}
        </div>
      {/foreach}
    </div>

    {section name='i' start=1 loop=$rowCount}
      {assign var='rowNumber' value=$smarty.section.i.index}
      <div class="{cycle values="odd-row,even-row"} selector-rows crm-grid-row" entity_id="{$rowNumber}">
        <div class="compressed crm-grid-cell"><span class="batch-edit"></span></div>
        {* contact select/create option*}
        <div class="compressed crm-grid-cell">
          {$form.primary_contact_id.$rowNumber.html|crmAddClass:big}
        </div>

        {if $batchType eq 2}
          {$form.member_option.$rowNumber.html}
        {/if}
        {if $batchType eq 3}
          {$form.open_pledges.$rowNumber.html}
        {/if}
        {foreach from=$fields item=field key=fieldName}
          {assign var=n value=$field.name}
          {if $n eq 'soft_credit'}
            <div class="compressed crm-grid-cell">
              {$form.soft_credit_contact_id.$rowNumber.html|crmAddClass:big}
              {$form.soft_credit_amount.$rowNumber.label}&nbsp;{$form.soft_credit_amount.$rowNumber.html|crmAddClass:eight}
            </div>
          {elseif $n eq 'soft_credit_type'}
            <div class="compressed crm-grid-cell">{$form.soft_credit_type.$rowNumber.html}</div>
          {elseif $n eq 'contribution_soft_credit_pcp_id'}
            <div class="compressed crm-grid-cell">
              <div>{$form.pcp_made_through_id.$rowNumber.html}{$form.pcp_made_through.$rowNumber.html}</div>
              <div>{$form.pcp_display_in_roll.$rowNumber.label}&nbsp;{$form.pcp_display_in_roll.$rowNumber.html}</div>
              <div class="pcp_roll_display">{$form.pcp_roll_nickname.$rowNumber.label}&nbsp;{$form.pcp_roll_nickname.$rowNumber.html}</div>
              <div class="pcp_roll_display">{$form.pcp_personal_note.$rowNumber.label}&nbsp;{$form.pcp_personal_note.$rowNumber.html}</div>
            </div>
          {elseif in_array( $fields.$n.html_type, array('Radio', 'CheckBox'))}
            <div class="compressed crm-grid-cell">&nbsp;{$form.field.$rowNumber.$n.html}</div>
          {elseif $n eq 'total_amount'}
             <div class="compressed crm-grid-cell">
               {$form.field.$rowNumber.$n.html}
               {if $batchType eq 3}
		 {ts}<span id={$rowNumber} class="pledge-adjust-option"><a href='#'>adjust payment amount</a></span>{/ts}
                 <span id="adjust-select-{$rowNumber}" class="adjust-selectbox">{$form.option_type.$rowNumber.html}</span>
               {/if}
             </div>
          {else}
            <div class="compressed crm-grid-cell">
              {$form.field.$rowNumber.$n.html|smarty:nodefaults}
              {if $fields.$n.html_type eq 'File' && !empty($form.field.$rowNumber.$fieldName.value.size)}
                {ts}Attached{/ts}: {$form.field.$rowNumber.$fieldName.value.name}
              {/if}
            </div>
          {/if}
        {/foreach}
      </div>
    {/section}
  </div>
  <div class="crm-submit-buttons">{if $fields && array_key_exists('_qf_Batch_refresh', $form) && is_array($form._qf_Batch_refresh)}{$form._qf_Batch_refresh.html}{/if} &nbsp; {$form.buttons.html}</div>
</div>
{*include batch copy js js file*}
{include file="CRM/common/batchCopy.tpl"}
