{*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.3                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2013                                |
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
{* this template is used for adding/editing/deleting grant *}

{if $cdType}
  {include file="CRM/Custom/Form/CustomData.tpl"}
{else}

{if $action eq 1 and $context ne 'standalone'}
   <h3>{ts}New Grant{/ts}</h3>
{elseif $action eq 2}
   <h3>{ts}Edit Grant{/ts}</h3>
{elseif $action eq 8}
   <h3>{ts}Delete Grant{/ts}</h3>
{/if}

<div class="crm-block crm-form-block crm-grant-form-block">
  {if $action eq 8} 
     <div class="messages status">
         <p><div class="icon inform-icon"></div>&nbsp;
         {ts}Are you sure you want to delete this Grant?{/ts} {ts}This operation cannot be undone.{/ts}</p>
         <p>{include file="CRM/Grant/Form/Task.tpl"}</p>
     </div>
  {else}
     <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="top"}</div> 	
     	 <table class="form-layout-compressed">  
	    {if $context eq 'standalone'}
              {include file="CRM/Contact/Form/NewContact.tpl"}
            {/if}
	    <tr class="crm-grant-form-block-status_id">
	       <td class="label">{$form.status_id.label}</td>
	       <td>{$form.status_id.html}</td>
	    </tr>   
	    <tr class="crm-grant-form-block-grant_type_id">
	       <td class="label">{$form.grant_type_id.label}</td>
	       <td>{$form.grant_type_id.html}</td>
	    </tr>   
	    <tr class="crm-grant-form-block-amount_total">
	       <td class="label">{$form.amount_total.label}</td>
	       <td>{$form.amount_total.html}</td>
	    </tr>
	    <tr class="crm-grant-form-block-amount_requested">
	       <td class="label">{$form.amount_requested.label}</td>
	       <td>{$form.amount_requested.html}<br /><span class="description">{ts}Amount requested for grant in original currency (if different).{/ts}</span></td>
	    </tr>
	    <tr class="crm-grant-form-block-amount_granted">
	       <td class="label">{$form.amount_granted.label}</td>
	       <td>{$form.amount_granted.html}</td>
	    </tr>
	    <tr class="crm-grant-form-block-application_received_date">
	       <td class="label">{$form.application_received_date.label}</td>
	       <td>{if $hideCalendar neq true}
                      {include file="CRM/common/jcalendar.tpl" elementName=application_received_date}
                   {else}
                      {$form.application_received_date.html|crmDate}
                   {/if}</td>
	    </tr>
	    <tr class="crm-grant-form-block-decision_date">
	       <td class="label">{$form.decision_date.label}</td>
	       <td>{if $hideCalendar neq true}
                      {include file="CRM/common/jcalendar.tpl" elementName=decision_date}
                   {else}
                      {$form.decision_date.html|crmDate}
                   {/if}<br />
                   <span class="description">{ts}Date on which the grant decision was finalized.{/ts}</span></td>
	    </tr>
	    <tr class="crm-grant-form-block-money_transfer_date"><td class="label">{$form.money_transfer_date.label}</td>
	    	<td>{if $hideCalendar neq true}
                       {include file="CRM/common/jcalendar.tpl" elementName=money_transfer_date}
                    {else}
                       {$form.money_transfer_date.html|crmDate}
                    {/if}<br /><span class="description">{ts}Date on which the grant money was transferred.{/ts}</span></td>
	    </tr>
	    <tr class="crm-grant-form-block-grant_due_date"><td class="label">{$form.grant_due_date.label}</td>
		<td>{if $hideCalendar neq true}
                       {include file="CRM/common/jcalendar.tpl" elementName=grant_due_date}
                    {else}
                       {$form.grant_due_date.html|crmDate}
                    {/if}</td>
	    </tr>
	    <tr class="crm-grant-form-block-grant_report_received">
	      	<td class="label">{$form.grant_report_received.label}</td>
		<td>{$form.grant_report_received.html}</td>
	    </tr>
	    <tr class="crm-grant-form-block-rationale">
	    	<td class="label">{$form.rationale.label}</td>
		<td>{$form.rationale.html}</td>
	    </tr>
	    <tr class="crm-grant-form-block-note">
		<td class="label">{$form.note.label}</td>
		<td>{$form.note.html}</td>
	    </tr>
	</table>
		
        <div id="customData" class="crm-grant-form-block-custom_data"></div>
        {*include custom data js file*}
        {include file="CRM/common/customData.tpl"}

{literal}
<script type="text/javascript">
    cj( function( ) {
        {/literal}
        CRM.buildCustomData( '{$customDataType}' );
        {if $customDataSubType}
        CRM.buildCustomData( '{$customDataType}', {$customDataSubType} );
        {/if}
        {literal}
    });

    cj(function() {
       cj().crmAccordions(); 
    });
</script>
{/literal}

 <div class="crm-grant-form-block-attachment">
     {include file="CRM/Form/attachment.tpl"}
 </div>

   {/if}
 <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
</div>

{/if} {* closing of main custom data if *} 
