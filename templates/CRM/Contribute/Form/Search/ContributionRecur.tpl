{*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2019                                |
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

<div class="crm-accordion-wrapper crm-contactDetails-accordion
   {if empty($contribution_recur_pane_open)} collapsed{/if}" id="contribution_recur">
  <div class="crm-accordion-header">
    {ts}Recurring Contributions{/ts}
  </div>
  <div class="crm-accordion-body">
    <table class="form-layout-compressed">
      <tr>
        <td colspan="4">{$form.contribution_recur_payment_made.html}</td>
      </tr>
      <tr>
        <td>{ts}Start Date{/ts}</td>
        <td>
          {include file="CRM/Core/DateRange.tpl" fieldName="contribution_recur_start_date" from='_low' to='_high'}
        </td>
      </tr>
      <tr>
        <td>{ts}End Date{/ts}</td>
        <td>
          {include file="CRM/Core/DateRange.tpl" fieldName="contribution_recur_end_date" from='_low' to='_high'}
        </td>
      </tr>
      <tr>
        <td>{ts}Modified Date{/ts}</td>
        <td>
          {include file="CRM/Core/DateRange.tpl" fieldName="contribution_recur_modified_date" from='_low' to='_high'}
        </td>
      </tr>
      <tr>
        <td>{ts}Next Scheduled{/ts}</td>
        <td>
          {include file="CRM/Core/DateRange.tpl" fieldName="contribution_recur_next_sched_contribution_date" from='_low' to='_high'}
        </td>
      </tr>
      <tr>
        <td>{ts}Retry Date{/ts}</td>
        <td>
          {include file="CRM/Core/DateRange.tpl" fieldName="contribution_recur_failure_retry_date" from='_low' to='_high'}
        </td>
      </tr>
      <tr>
        <td>{ts}Cancel Date{/ts}</td>
        <td>
          {include file="CRM/Core/DateRange.tpl" fieldName="contribution_recur_cancel_date" from='_low' to='_high'}
        </td>
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
      {if $contributionRecurGroupTree}
        <tr>
          <td colspan="4">
            {include file="CRM/Custom/Form/Search.tpl" groupTree=$contributionRecurGroupTree showHideLinks=false}
          </td>
        </tr>
      {/if}
    </table>
  </div>
<!-- /.crm-accordion-body -->
</div><!-- /.crm-accordion-wrapper -->


