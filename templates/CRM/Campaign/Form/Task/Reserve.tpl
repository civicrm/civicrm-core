{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
<h3>
{ts}Add Respondent Reservation(s){/ts}
</h3>
<div class="crm-form-block crm-block crm-campaign-task-reserve-form-block">
<div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="top"}</div>
<table class="form-layout-compressed">
  <tr class="crm-campaign-task-reserve-form-block-surveytitle">
    <td colspan=2>
      <div class="status">
        {icon icon="fa-info-circle"}{/icon}{ts 1=$surveyTitle}Do you want to reserve respondents for '%1' ?{/ts}
      </div>
    </td>
  </tr>
  <tr><td colspan=2>{include file="CRM/Contact/Form/Task.tpl"}</td></tr>
</table>

{* Group options *}
 {* New Group *}
 <div id="new-group" class="crm-accordion-wrapper collapsed">
 <div class="crm-accordion-header">
 {ts}Add respondent(s) to a new group{/ts}
 </div><!-- /.crm-accordion-header -->
 <div class="crm-accordion-body">
            <table class="form-layout-compressed">
             <tr>
               <td class="description label">{$form.newGroupName.label}</td>
               <td>{$form.newGroupName.html}</td>
             </tr>
             <tr>
               <td class="description label">{$form.newGroupDesc.label}</td>
               <td>{$form.newGroupDesc.html}</td>
             </tr>
            </table>
 </div><!-- /.crm-accordion-body -->
 </div><!-- /.crm-accordion-wrapper -->


 {* Existing Group *}
 <div class="crm-accordion-wrapper crm-existing_group-accordion {if $hasExistingGroups} {else}collapsed{/if}">
 <div class="crm-accordion-header">
  {ts}Add respondent(s) to existing group(s){/ts}
 </div><!-- /.crm-accordion-header -->
 <div class="crm-accordion-body">

        <div class="form-item">
        <table><tr><td style="width: 14em;"></td><td>{$form.groups.html}</td></tr></table>
        </div>
 </div><!-- /.crm-accordion-body -->
 </div><!-- /.crm-accordion-wrapper -->
{* End of group options *}


<div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
</div>

{literal}
<script type="text/javascript">

 CRM.$(function($) {
   setDefaultGroup( );
 });

 function setDefaultGroup( )
 {
    var invalidGroupName = {/literal}'{$invalidGroupName}'{literal};
    if ( invalidGroupName ) {
       cj("#new-group.collapsed").crmAccordionToggle();
    } else {
       cj("#newGroupName").val( '' );
       cj("#newGroupDesc").val( '' );
    }
 }
</script>
{/literal}
