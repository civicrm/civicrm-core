{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{* Form elements for displaying and running action tasks on search results *}
{capture assign=advSearchURL}
{if $context EQ 'smog'}{crmURL p='civicrm/group/search/advanced' q="gid=`$group.id`&reset=1&force=1"}
{elseif $context EQ 'amtg'}{crmURL p='civicrm/contact/search/advanced' q="context=amtg&amtgID=`$group.id`&reset=1&force=1"}
{else}{crmURL p='civicrm/contact/search/advanced' q="reset=1"}
{/if}{/capture}

 <div id="search-status">
  <div class="float-right right">
    {if $action eq 256}
        <a href="{$advSearchURL}">{ts}Advanced Search{/ts}</a><br />
        {if $context eq 'smog'}
            {help id="id-smog-criteria" group_title=$group.title}
        {elseif $context eq 'amtg'}
            {help id="id-amtg-criteria" group_title=$group.title}
        {else}
            {help id="id-basic-criteria"}
        {/if}
    {elseif $action eq 8192}
        <a href="{$advSearchURL}">{ts}Advanced Search{/ts}</a><br />
    {/if}
  </div>

  <table class="form-layout-compressed">
  <tr>
    <td style="width: 30%;">
        {if !empty($savedSearch.name)}{$savedSearch.name} ({ts}smart group{/ts}) - {/if}
        {ts count=$pager->_totalItems plural="%count Contacts"}%count Contact{/ts}
    </td>

    {* Search criteria are passed to tpl in the $qill array *}
    <td class="nowrap">
    {if $qill}
      {include file="CRM/common/displaySearchCriteria.tpl"}
    {/if}
    </td>
  </tr>
  <tr>
    <td> {ts}Select Records{/ts}:</td>
    <td class="nowrap">
      {assign var="checked" value=$selectedContactIds|@count}
      {$form.radio_ts.ts_all.html} <label for="{$ts_all_id}">{ts count=$pager->_totalItems plural="All %count records"}The found record{/ts}</label>
      {if $pager->_totalItems > 1}
        &nbsp; {$form.radio_ts.ts_sel.html} <label for="{$ts_sel_id}">{ts 1="<span>$checked</span>"}%1 Selected records only{/ts}</label>
      {/if}
    </td>
  </tr>
  <tr>
    <td colspan="2">
     {* Hide export button in 'Add Members to Group' context. *}
     {if $context NEQ 'amtg'}
        {$form.task.html}
     {/if}
     {if $action eq 512}
       {$form.$actionButtonName.html}
     {elseif $action eq 8192}
       {* todo - just use action button name per above  - test *}
       {$form._qf_Builder_next_action.html}&nbsp;&nbsp;
     {elseif $action eq 16384}
       {* todo - just use action button name per above - test *}
       {$form._qf_Custom_next_action.html}&nbsp;&nbsp;
     {else}
       {* todo - just use action button name per above  - test *}
       {$form._qf_Basic_next_action.html}
     {/if}
     </td>
  </tr>
  </table>
 </div>
