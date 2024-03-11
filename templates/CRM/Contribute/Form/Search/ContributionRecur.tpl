{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}

<details class="crm-accordion-bold crm-contactDetails-accordion" id="contribution_recur" {if empty($contribution_recur_pane_open)}{else}open{/if}>
  <summary>
    {ts}Recurring Contributions{/ts}
  </summary>
  <div class="crm-accordion-body">
    <table class="form-layout-compressed">
      <tr>
        <td colspan="4">{$form.contribution_recur_payment_made.html}</td>
      </tr>
      <tr>
        <td><label for="contribution_recur_start_date_relative">{$form.contribution_recur_start_date_relative.label}</label></td>
        {include file="CRM/Core/DatePickerRangeWrapper.tpl" fieldName="contribution_recur_start_date" to='' from='' colspan="2" hideRelativeLabel=1 class =''}
      </tr>
      <tr>
        <td><label for="contribution_recur_end_date_relative">{$form.contribution_recur_end_date_relative.label}</label></td>
        {include file="CRM/Core/DatePickerRangeWrapper.tpl" fieldName="contribution_recur_end_date" to='' from='' colspan="2" hideRelativeLabel=1 class =''}
      </tr>
      <tr>
        <td><label for="contribution_recur_modified_date_relative">{$form.contribution_recur_modified_date_relative.label}</label></td>
        {include file="CRM/Core/DatePickerRangeWrapper.tpl" fieldName="contribution_recur_modified_date" to='' from='' colspan="2" class ='' hideRelativeLabel=1}
      </tr>
      <tr>
        <td><label for="contribution_recur_next_sched_contribution_date_relative">{$form.contribution_recur_next_sched_contribution_date_relative.label}</label></td>
        {include file="CRM/Core/DatePickerRangeWrapper.tpl" fieldName="contribution_recur_next_sched_contribution_date" to='' from='' colspan="2" hideRelativeLabel=1 class=''}
      </tr>
      <tr>
        <td><label for="contribution_recur_failure_rety_date_relative">{$form.contribution_recur_failure_retry_date_relative.label}</label></td>
        {include file="CRM/Core/DatePickerRangeWrapper.tpl" fieldName="contribution_recur_failure_retry_date" to='' from='' colspan="2" hideRelativeLabel=1 class=''}
      </tr>
      <tr>
        <td><label for="contribution_recur_cancel_date_relative">{$form.contribution_recur_cancel_date_relative.label}</label></td>
        {include file="CRM/Core/DatePickerRangeWrapper.tpl" fieldName="contribution_recur_cancel_date" to='' from='' colspan="2" hideRelativeLabel=1 class=''}
      </tr>
      <tr>
        <td>{ts}Status{/ts}</td>
        <td></td>
        <td col='span2'>
          {$form.contribution_recur_contribution_status_id.html|crmAddClass:twenty}
        </td>
      </tr>
      <tr>
        <td>{ts}Payment Processor{/ts}</td>
        <td></td>
        <td col='span2'>
          {$form.contribution_recur_payment_processor_id.html}
        </td>
      </tr>
      <tr>
        <td>{ts}Processor ID{/ts} {help id="processor-id" file="CRM/Contact/Form/Search/Advanced"}</td>
        <td></td>
        <td col='span2'>
          {$form.contribution_recur_processor_id.html}
        </td>
      </tr>
      <tr>
        <td>{ts}Transaction ID{/ts} {help id="transaction-id" file="CRM/Contact/Form/Search/Advanced"}</td>
        <td></td>
        <td col='span2'>
          {$form.contribution_recur_trxn_id.html}
        </td>
      </tr>
      {if !empty($contributionRecurGroupTree)}
        <tr>
          <td colspan="4">
            {include file="CRM/Custom/Form/Search.tpl" groupTree=$contributionRecurGroupTree showHideLinks=false}
          </td>
        </tr>
      {/if}
    </table>
  </div>
</details>


