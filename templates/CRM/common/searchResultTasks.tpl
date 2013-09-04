{*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.4                                                |
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
{* Form elements for displaying and running action tasks on search results for all component searches. *}

<div id="search-status">
  <table class="form-layout-compressed">
  <tr>
    <td class="font-size12pt" style="width: 40%;">
    {if $savedSearch.name}{$savedSearch.name} ({ts}smart group{/ts}) - {/if}
    {ts count=$pager->_totalItems plural='%count Results'}%count Result{/ts}{if $selectorLabel}&nbsp;-&nbsp;{$selectorLabel}{/if}
    {if $context == 'Event' && $participantCount && ( $pager->_totalItems ne $participantCount ) }
        <br />{ts}Actual participant count{/ts} : {$participantCount} {help id="id-actual_participant_count" file="CRM/Event/Form/Search/Results.hlp"} &nbsp;
    {/if}
    </td>
    <td>
        {* Search criteria are passed to tpl in the $qill array *}
        {if $qill}
            {include file="CRM/common/displaySearchCriteria.tpl"}
        {/if}
    </td>
  </tr>
{if $context == 'Contribution'}
  <tr>
    <td colspan="2">
{include file="CRM/Contribute/Page/ContributionTotals.tpl"}
    </td>
  </tr>
{/if}
  <tr>
    <td class="font-size11pt"> {ts}Select Records{/ts}:</td>
    <td class="nowrap">
        {$form.radio_ts.ts_all.html} {ts count=$pager->_totalItems plural='All %count records'}The found record{/ts} &nbsp; {if $pager->_totalItems > 1} {$form.radio_ts.ts_sel.html} {ts}Selected records only{/ts} {/if}
    </td>
  </tr>
  <tr>
    <td colspan="2">
    {if $printButtonName}
       {$form.$printButtonName.html} &nbsp; &nbsp;
    {else}
       {$form._qf_Search_next_print.html} &nbsp; &nbsp;
     {/if}
   
      <span id='task-section'>
        {$form.task.html}
        {if $actionButtonName}
          {$form.$actionButtonName.html} &nbsp; &nbsp;
        {else}
          {$form._qf_Search_next_action.html}
        {/if}
      </span>
    </td>
  </tr>
  </table>
</div>
{literal}
<script type="text/javascript">
toggleTaskAction( );
cj(function() {
  if (cj('#task option').length == 1 && !cj('#task option').val()) {
    cj('#task-section').remove();
  }
});
</script>
{/literal}
