{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
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
      {$form.radio_ts.ts_all.html} <label for="{$ts_all_id}">{ts count=$pager->_totalItems plural='All %count records'}The found record{/ts}</label> &nbsp; {if $pager->_totalItems > 1} {$form.radio_ts.ts_sel.html} <label for="{$ts_sel_id}">{ts 1="<span></span>"}%1 Selected records only{/ts}</label>{/if}
    </td>
  </tr>
  <tr>
    <td colspan="2">
    {* Note print buttons were mostly removed except for Campaign search - the following lines can be removed soon CRM-12872 *}
    {if !empty($printButtonName)}
       {$form.$printButtonName.html} &nbsp; &nbsp;
    {elseif !empty($form._qf_Search_next_print)}
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
