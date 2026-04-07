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
            {capture assign='helpTitle'}{ts}Group Search{/ts}{/capture}
            {help id="smog-criteria" title=$helpTitle group_title=$group.title}
        {elseif $context eq 'amtg'}
          {capture assign='helpTitle'}{ts}Add to Group{/ts}{/capture}
            {help id="amtg-criteria" title=$helpTitle group_title=$group.title}
        {else}
            {capture assign='helpTitle'}{ts}Search{/ts}{/capture}
            {help id="basic-criteria" title=$helpTitle}
        {/if}
    {elseif $action eq 8192}
        <a href="{$advSearchURL}">{ts}Advanced Search{/ts}</a><br />
    {/if}
  </div>
  <a href="#" class="crm-selection-reset crm-hover-button float-right"><i class="crm-i fa-times-circle-o" role="img" aria-hidden="true"></i> {ts}Reset all selections{/ts}</a>
  <table class="form-layout-compressed">
    {if !empty($savedSearch.name)}
      <tr>
        <td colspan="2">{$savedSearch.name} ({ts}Smart Group{/ts})</td>
      </tr>
    {/if}
    {* Search criteria are passed to tpl in the $qill array *}
   {if $qill}
     <tr>
       <td>{include file="CRM/common/displaySearchCriteria.tpl"}</td>
     </tr>
   {/if}
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
