{*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.6                                                |
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

{* this template is used for editing recurring contribution record*}
{if $action eq 8}
<div class="crm-block crm-form-block crm-contribution-form-block">
<div class="messages status no-popup">
    <div class="icon inform-icon"></div>
    {ts}WARNING: Deleting this recurring contribution will result in the loss of the associated contributions (if any).{/ts}<br />
    {if $contributionCount}{ts}There are {$contributionCount} contribution(s) linked with this recurring contribution record .{/ts}<br />{/if}
    {ts}Do you want to continue?{/ts}
    </div>
  <div class="crm-submit-buttons">
    {include file="CRM/common/formButtons.tpl"}
  </div>
</div>  
{else}

    <div class="crm-block crm-form-block crm-contribution-form-block">
        <div class="crm-submit-buttons">
            {include file="CRM/common/formButtons.tpl"}
        </div>
        <table class="form-layout-compressed">
            <tr class="crm-contribution-form-block-payment_processor_id">
                <td class="label nowrap">{$form.payment_processor_id.label}</td>
                <td>{$form.payment_processor_id.html}</td>
            </tr>
            <tr class="crm-contribution-form-block-processor_id">
                <td class="label">{$form.processor_id.label}</td>
                <td>{$form.processor_id.html}&nbsp;{help id="id-processor_id" file="CRM/Contribute/Page/Tab.hlp"}</td>
            </tr>
            <tr class="crm-contribution-form-block-trxn_id">
                <td class="label">{$form.trxn_id.label}</td>
                <td>{$form.trxn_id.html}</td>
            </tr>  
            <tr class="crm-contribution-form-block-contribution_type_id crm-contribution-form-block-financial_type_id">
                <td class="label">{$form.financial_type_id.label}</td>
                <td{$valueStyle}>{$form.financial_type_id.html}
                </td>
            </tr>
            <tr class="crm-contribution-form-block-contribution_type_id crm-contribution-form-block-financial_type_id">
                <td class="label">{$form.contribution_status_id.label}</td>
                <td{$valueStyle}>{$form.contribution_status_id.html}
                </td>
            </tr>
            {if $action eq 1}
            <tr class="crm-contribution-form-block-contribution_type_id crm-contribution-form-block-financial_type_id">
                <td class="label">{$form.membership_id.label}</td>
                <td{$valueStyle}>{$form.membership_id.html}
                </td>
            </tr>
            {/if}
            <tr  class="crm-contribution-form-block-total_amount">
                <td class="label">{$form.amount.label}</td>
                <td {$valueStyle}>
                    <span id='totalAmount'>{$form.currency.html|crmAddClass:eight}&nbsp;{$form.amount.html|crmAddClass:eight}</span>
                </td>
            </tr>
            <tr class="crm-contribution-form-block-payment_instrument_id">
                <td class="label nowrap">{$form.payment_instrument_id.label}</td>
                <td>{$form.payment_instrument_id.html}</td>
            </tr>
            <tr class="crm-contribution-form-block-frequency_interval">
                <td class="label">{$form.frequency_interval.label}</td>
                <td>{$form.frequency_interval.html}&nbsp;&nbsp;&nbsp;{$form.frequency_unit.html}</td>
            </tr>
            <tr id="startDate" class="crm-contribution-form-block-start_date">
              <td class="label">{$form.start_date.label}</td>
              <td>{include file="CRM/common/jcalendar.tpl" elementName=start_date}</td>
            </tr>
            {if $action eq 2}
            <tr id="endDate" class="crm-contribution-form-block-end_date">
              <td class="label">{$form.end_date.label}</td>
              <td>{include file="CRM/common/jcalendar.tpl" elementName=end_date}</td>
            </tr>
            <tr id="cancelDate" class="crm-contribution-form-block-cancel_date">
              <td class="label">{$form.cancel_date.label}</td>
              <td>{include file="CRM/common/jcalendar.tpl" elementName=cancel_date}</td>
            </tr>
            <tr id="nextSchedContributionDate" class="crm-contribution-form-block-next_sched_contribution_date">
              <td class="label">{$form.next_sched_contribution_date.label}</td>
              <td>{include file="CRM/common/jcalendar.tpl" elementName=next_sched_contribution_date}</td>
            </tr>
            {/if}
            <tr class="crm-contribution-form-block-cycle_day">
                <td class="label">{$form.cycle_day.label}</td>
                <td>{$form.cycle_day.html}</td>
            </tr>  
        </table>    
    </div>

    <div id="customData" class="crm-contribution-recur-form-block-customData"></div>

    {*include custom data js file*}
    {include file="CRM/common/customData.tpl"}

    {literal}
        <script type="text/javascript">
          CRM.$(function($) {
            {/literal}
            CRM.buildCustomData( '{$customDataType}' );
            {literal}
          });  
        </script>
    {/literal}    
            
    {* Show the below sections only for edit/update action *}
    {if $action eq 2}
    {* Display existing contributions of the recur record *}
    {if $associatedContributions}
    <div class="crm-accordion-wrapper crm-contributionDetails-accordion collapsed">
        <div class="crm-accordion-header active">
          Existing Contributions
        </div><!-- /.crm-accordion-header -->
        <div class="crm-accordion-body" id="body-contributionDetails">
          <div id="contributionDetails">
            <div class="crm-section contribution-list">
            <table>
                <tr>
                    <th>{ts}Amount{/ts}</th>
                    <th>{ts}Type{/ts}</th>
                    <th>{ts}Source{/ts}</th>
                    <th>{ts}Received{/ts}</th>
                    <th>{ts}Status{/ts}</th>
                </tr>    
                {foreach from=$associatedContributions item=ContributionDetails}
                <tr>
                    <td>{$ContributionDetails.total_amount|crmMoney}</td>
                    <td>{$ContributionDetails.financial_type}</td>
                    <td>{$ContributionDetails.contribution_source}</td>
                    <td>{$ContributionDetails.receive_date|crmDate}</td>
                    <td>{$ContributionDetails.contribution_status}</td>
                </tr>
            {/foreach}
            </table>
            </div>   
          </div>    
        </div>     
    </div> 
    {/if}
            
    {* Move recur record to new contact *}
    <div class="crm-accordion-wrapper crm-moveRecur-accordion collapsed">
        <div class="crm-accordion-header active">
          Move
        </div><!-- /.crm-accordion-header -->
        <div class="crm-accordion-body" id="body-moveRecur">
        <div id="help">
            You can move the recurring record to another contact/membership. 
            {if $existingContributionsAvailable eq 1}
                You can also choose to move the existing contributions to selected contact or retain with the existing contact.
            {/if}    
        </div>
          <div id="moveRecur">
            <div class="crm-section">
                &nbsp;&nbsp;{$form.move_recurring_record.html}&nbsp;{$form.move_recurring_record.label}
                <br /><br />
                <table class="form-layout" id="move-recurring-table">
                  <tr>
                    <td class="label">{$form.contact_id.label}</td>
                    <td>{$form.contact_id.html}{$form.selected_cid.html}</td>
                  </tr>
                  {if $show_move_membership_field eq 1}
                  <tr>
                    <td class="label">{$form.membership_record.label}</td>
                    <td>{$form.membership_record.html}<br />
                        <sub>( Membership Type / Membership Status / Start Date / End Date )</sub>
                    </td>
                  </tr>
                  {/if}
                  <tr>
                    <td class="label">{$form.move_existing_contributions.label}</td>
                    <td>{$form.move_existing_contributions.html}</td>
                  </tr>
                </table>      
            </div>   
          </div>    
        </div>     
    </div>
    {/if}

    <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>


{if !$customDataType}

{literal}
<script type="text/javascript">
CRM.$(function() {
    
    var $form = cj("form.{/literal}{$form.formClass}{literal}");
    cj("#contact_id", $form).change(displayMemberships);

    var contactID = {/literal}{$contactID}{literal};
    var currentMembershipID = '';

    {/literal}{if $membershipID}{literal}
        currentMembershipID = {/literal}{$membershipID}{literal};
    {/literal}{/if}{literal}
    
    function displayMemberships( ) {
        var data = cj("#contact_id", $form).select2('data');
        cj('input[name=selected_cid]').val(data.id);
        var selectedContactId = data.id;
        CRM.api('Membership', 'get', {'contact_id': data.id},
        {success: function(data) {

            cj('#membership_record').find('option').remove();    
            cj('#membership_record').append(cj('<option>', { 
                value: '0',
                text : '- select -'
            }));
            cj.each(data.values, function(key, value) {

                // Remove if current membership is in the list
                if (contactID == selectedContactId && currentMembershipID != ''){
                    //data.values[currentMembershipID].remove();
                    data.values.splice(key, 1);
                }
            
                // Get membership status label
                var membershipStatusId = value.status_id;
                var membershipStatuslabel = '';
                CRM.api('MembershipStatus', 'getsingle', {'id': membershipStatusId},
                  {success: function(memstatus_data) {
                        cj('#membership_record').append(cj('<option>', { 
                            value: value.id,
                            text : value.membership_name + ' / ' + memstatus_data.label + ' / ' + value.start_date + ' / ' + value.end_date
                        }));
                   }
                });
            });
            cj('#membership_record').parents('tr').show();
          },
        }
      );
    }    
});    
  
cj(document).ready(function(){  
    
    cj('#move-recurring-table').hide();
    
    cj('#move_recurring_record').change(function(){
        if (cj('#move_recurring_record').is(':checked')) {
            cj('#move-recurring-table').show();
            {/literal}{if $existingContributionsAvailable eq 1}{literal}
                cj('#move_existing_contributions').parent().parent().show();
            {/literal}{/if}{literal}
        } else {
            cj('#move-recurring-table').hide();
            cj('#move_existing_contributions').prop('checked', false);
            cj('#move_existing_contributions').parent().parent().hide();
        }
    });
    
    // Hide 'Move Existing Contributions?' field is no existing contributions available
    {/literal}{if $existingContributionsAvailable eq 0}{literal}
        cj('#move_existing_contributions').prop('checked', false);
        cj('#move_existing_contributions').parent().parent().hide();
    {/literal}{/if}{literal}

});

</script>
{/literal}

{/if}
{/if}