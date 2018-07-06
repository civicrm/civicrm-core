{*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2018                                |
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
{* Template for Full-text search component. *}
<div class="crm-block crm-form-block crm-search-form-block">
  <div id="searchForm">
    <div class="form-item">
      <table class="form-layout-compressed">
        <tr>
          <td class="label">{$form.text.label}</td>
          <td>{$form.text.html}</td>
          <td class="label">{ts}in...{/ts}</td>
          <td>{$form.table.html}</td>
          <td>{$form.buttons.html} {help id="id-fullText"}</td>
        </tr>
      </table>
    </div>
  </div>
</div>
<div class="crm-block crm-content-block">
{if !$table}{include file="CRM/common/pager.tpl" location="top"}{/if}
{include file="CRM/common/jsortable.tpl"}
{if $rowsEmpty}
  {include file="CRM/Contact/Form/Search/Custom/EmptyResults.tpl"}
{/if}

{assign var=table value=$form.table.value.0}
{assign var=text  value=$form.text.value}
{if !empty($summary.Contact) }
  <div class="section">
    {* Search request has returned 1 or more matching rows. Display results. *}
    <h3>{ts}Contacts{/ts}
      : {if !$table}{if $summary.Count.Contact <= $limit}{$summary.Count.Contact}{else}{ts 1=$limit}%1 or more{/ts}{/if}{else}{$summary.Count.Contact}{/if}</h3>
    {if $table}{include file="CRM/common/pager.tpl" location="top"}{/if}
    {* This section displays the rows along and includes the paging controls *}
      <table id="contact_listing" class="display" class="selector" summary="{ts}Contact listings.{/ts}">
        <thead>
        <tr>
          <th class='link'>{ts}Name{/ts}</th>
          {if $allowFileSearch}<th>{ts}File{/ts}</th>{/if}
          <th></th>
        </tr>
        </thead>
        {foreach from=$summary.Contact item=row}
          <tr class="{cycle values="odd-row,even-row"}">
            <td><a
                href="{crmURL p='civicrm/contact/view' q="reset=1&cid=`$row.contact_id`&context=fulltext&key=`$qfKey`"}"
                title="{ts}View Contact Details{/ts}">{$row.sort_name}</a></td>
            {if $allowFileSearch}<td>{$row.fileHtml}</td>{/if}
            <td><a
                href="{crmURL p='civicrm/contact/view' q="reset=1&cid=`$row.contact_id`&context=fulltext&key=`$qfKey`"}">{ts}View{/ts}</a>
            </td>
          </tr>
        {/foreach}
      </table>
    {if !$table and $summary.addShowAllLink.Contact}
      <div class="crm-section full-text-view-all-section">
        <a href="{crmURL p='civicrm/contact/search/custom' q="csid=`$csID`&reset=1&force=1&table=Contact&text=$text"}"
        title="{ts}View all results for contacts{/ts}">&raquo;&nbsp;{ts}View all results for contacts{/ts}</a>
      </div>{/if}
    {* note we using location="below" because we don't want to use rows per page for now. And therefore don't put location="bottom" for now. *}
    {if $table}{include file="CRM/common/pager.tpl" location="below"}{/if}
    {* END Actions/Results section *}
  </div>
{/if}

{if !empty($summary.Activity) }
  <div class="section">
    {* Search request has returned 1 or more matching rows. Display results. *}

    <h3>{ts}Activities{/ts}
      : {if !$table}{if $summary.Count.Activity <= $limit}{$summary.Count.Activity}{else}{ts 1=$limit}%1 or more{/ts}{/if}{else}{$summary.Count.Activity}{/if}</h3>
    {if $table}{include file="CRM/common/pager.tpl" location="top"}{/if}
    {* This section displays the rows along and includes the paging controls *}
      <table id="activity_listing" class="display" summary="{ts}Activity listings.{/ts}">
        <thead>
        <tr>
          <th>{ts}Type{/ts}</th>
          <th>{ts}Subject{/ts}</th>
          <th>{ts}Details{/ts}</th>
          <th class='link'>{ts}Added By{/ts}</th>
          <th class='link'>{ts}With{/ts}</th>
          <th class='link'>{ts}Assignee{/ts}</th>
          {if $allowFileSearch}<th>{ts}File{/ts}</th>{/if}
          <th></th>
        </tr>
        </thead>
        {foreach from=$summary.Activity item=row}
          <tr class="{cycle values="odd-row,even-row"}">
            <td>{$row.activity_type}</td>
            <td>{$row.subject|mb_truncate:40}</td>
            <td>{$row.details|escape}</td>
            <td>
              <a href="{crmURL p='civicrm/contact/view' q="reset=1&cid=`$row.contact_id`&context=fulltext&key=`$qfKey`"}"
                title="{ts}View Contact Details{/ts}">{$row.sort_name}</a>
            </td>
            <td>
              <a href="{crmURL p='civicrm/contact/view' q="reset=1&cid=`$row.target_contact_id`&context=fulltext&key=`$qfKey`"}"
                title="{ts}View Contact Details{/ts}">{$row.target_sort_name}</a>
            </td>
            <td>
              <a href="{crmURL p='civicrm/contact/view' q="reset=1&cid=`$row.assignee_contact_id`&context=fulltext&key=`$qfKey`"}"
                title="{ts}View Contact Details{/ts}">{$row.assignee_sort_name}</a>
            </td>
            {if $allowFileSearch}<td>{$row.fileHtml}</td>{/if}
            <td>
              {if $row.case_id }
                <a href="{crmURL p='civicrm/case/activity/view'
                q="reset=1&aid=`$row.activity_id`&cid=`$row.client_id`&caseID=`$row.case_id`&context=fulltext&key=`$qfKey`"}">
              {else}
                <a href="{crmURL p='civicrm/contact/view/activity'
                q="atype=`$row.activity_type_id`&action=view&reset=1&id=`$row.activity_id`&cid=`$row.contact_id`&context=fulltext&key=`$qfKey`"}">
              {/if}
              {ts}View{/ts}</a>
            </td>
          </tr>
        {/foreach}
      </table>
    {if !$table and $summary.addShowAllLink.Activity}
      <div class="crm-section full-text-view-all-section">
        <a href="{crmURL p='civicrm/contact/search/custom' q="csid=`$csID`&reset=1&force=1&table=Activity&text=$text"}"
        title="{ts}View all results for activities{/ts}">&raquo;&nbsp;{ts}View all results for activities{/ts}</a>
      </div>
    {/if}
    {if $table}{include file="CRM/common/pager.tpl" location="below"}{/if}
    {* END Actions/Results section *}
  </div>
{/if}

{if !empty($summary.Case) }
  <div class="section">
    {* Search request has returned 1 or more matching rows. Display results. *}
    <h3>{ts}Cases{/ts}
      : {if !$table}{if $summary.Count.Case <= $limit}{$summary.Count.Case}{else}{ts 1=$limit}%1 or more{/ts}{/if}{else}{$summary.Count.Case}{/if}</h3>
    {if $table}{include file="CRM/common/pager.tpl" location="top"}{/if}
    {* This section displays the rows along and includes the paging controls *}
      <table id="case_listing" class="display" summary="{ts}Case listings.{/ts}">
        <thead>
        <tr>
          <th class='link'>{ts}Client Name{/ts}</th>
          <th class="start_date">{ts}Start Date{/ts}</th>
          <th class="end_date">{ts}End Date{/ts}</th>
          <th>{ts}Case ID{/ts}</th>
          {if $allowFileSearch}<th>{ts}File{/ts}</th>{/if}
          <th></th>
          <th class="hiddenElement"></th>
          <th class="hiddenElement"></th>
        </tr>
        </thead>
        {foreach from=$summary.Case item=row}
          <tr class="{cycle values="odd-row,even-row"}">
            <td>
              <a href="{crmURL p='civicrm/contact/view' q="reset=1&cid=`$row.contact_id`&context=fulltext&key=`$qfKey`"}"
                title="{ts}View Contact Details{/ts}">{$row.sort_name}</a>
            </td>
            <td>{$row.case_start_date|crmDate:"%b %d, %Y %l:%M %P"}</td>
            <td>{$row.case_end_date|crmDate:"%b %d, %Y %l:%M %P"}</td>
            <td>{$row.case_id}</td>
            {if $allowFileSearch}<td>{$row.fileHtml}</td>{/if}
            {if $row.case_is_deleted}
              <td>
                <a href="{crmURL p='civicrm/contact/view/case'
                q="reset=1&id=`$row.case_id`&cid=`$row.contact_id`&action=renew&context=fulltext&key=`$qfKey`"}">{ts}Restore Case{/ts}</a>
              </td>
            {else}
              <td>
                <a href="{crmURL p='civicrm/contact/view/case'
                q="reset=1&id=`$row.case_id`&cid=`$row.contact_id`&action=view&context=fulltext&key=`$qfKey`"}">{ts}Manage{/ts}</a>
              </td>
            {/if}
            <td class="start_date hiddenElement">{$row.case_start_date|crmDate}</td>
            <td class="end_date hiddenElement">{$row.case_end_date|crmDate}</td>
          </tr>
        {/foreach}
      </table>
    {if !$table and $summary.addShowAllLink.Case}
      <div class="crm-section full-text-view-all-section">
        <a href="{crmURL p='civicrm/contact/search/custom' q="csid=`$csID`&reset=1&force=1&table=Case&text=$text"}"
        title="{ts}View all results for cases{/ts}">&raquo;&nbsp;{ts}View all results for cases{/ts}</a>
      </div>
    {/if}
    {if $table}{include file="CRM/common/pager.tpl" location="below"}{/if}
    {* END Actions/Results section *}
  </div>
{/if}

{if !empty($summary.Contribution) }
  <div class="section">
    {* Search request has returned 1 or more matching rows. Display results. *}

    <h3>{ts}Contributions{/ts}
      : {if !$table}{if $summary.Count.Contribution <= $limit}{$summary.Count.Contribution}{else}{ts 1=$limit}%1 or more{/ts}{/if}{else}{$summary.Count.Contribution}{/if}</h3>
    {if $table}{include file="CRM/common/pager.tpl" location="top"}{/if}
    {* This section displays the rows along and includes the paging controls *}
      <table id="contribute_listing" class="display" summary="{ts}Contribution listings.{/ts}">
        <thead>
        <tr>
          <th class='link'>{ts}Contributor's Name{/ts}</th>
          <th class="currency">{ts}Amount{/ts}</th>
          <th>{ts}Financial Type{/ts}</th>
          <th>{ts}Source{/ts}</th>
          <th class="received_date">{ts}Received{/ts}</th>
          <th>{ts}Status{/ts}</th>
          {if $allowFileSearch}<th>{ts}File{/ts}</th>{/if}
          <th></th>
          <th class="hiddenElement"></th>
        </tr>
        </thead>
        {foreach from=$summary.Contribution item=row}
          <tr class="{cycle values="odd-row,even-row"}">
            <td>
              <a href="{crmURL p='civicrm/contact/view'
              q="reset=1&cid=`$row.contact_id`&context=fulltext&key=`$qfKey`"}"
                title="{ts}View Contact Details{/ts}">{$row.sort_name}</a>
            </td>
            <td>{$row.contribution_total_amount|crmMoney}</td>
            <td>{$row.financial_type}</td>
            <td>{$row.contribution_source}</td>
            <td>{$row.contribution_receive_date|crmDate:"%b %d, %Y %l:%M %P"}</td>
            <td>{$row.contribution_status}</td>
            {if $allowFileSearch}<td>{$row.fileHtml}</td>{/if}
            <td>
              <a href="{crmURL p='civicrm/contact/view/contribution'
              q="reset=1&id=`$row.contribution_id`&cid=`$row.contact_id`&action=view&context=fulltext&key=`$qfKey`"}">{ts}View{/ts}</a>
            </td>
            <td class="received_date hiddenElement">{$row.contribution_receive_date|crmDate}</td>
          </tr>
        {/foreach}
      </table>
    {if !$table and $summary.addShowAllLink.Contribution}
      <div class="crm-section full-text-view-all-section">
        <a href="{crmURL p='civicrm/contact/search/custom' q="csid=`$csID`&reset=1&force=1&table=Contribution&text=$text"}"
        title="{ts}View all results for contributions{/ts}">&raquo;&nbsp;{ts}View all results for contributions{/ts}</a>
      </div>
    {/if}
    {if $table}{include file="CRM/common/pager.tpl" location="below"}{/if}
    {* END Actions/Results section *}
  </div>
{/if}

{if !empty($summary.Participant) }
  <div class="section">
    {* Search request has returned 1 or more matching rows. *}

    <h3>{ts}Event Participants{/ts}
      : {if !$table}{if $summary.Count.Participant <= $limit}{$summary.Count.Participant}{else}{ts 1=$limit}%1 or more{/ts}{/if}{else}{$summary.Count.Participant}{/if}</h3>
    {if $table}{include file="CRM/common/pager.tpl" location="top"}{/if}
    {* This section displays the rows along and includes the paging controls *}
      <table id="participant_listing" class="display" summary="{ts}Participant listings.{/ts}">
        <thead>
        <tr>
          <th class='link'>{ts}Participant's Name{/ts}</th>
          <th>{ts}Event{/ts}</th>
          <th>{ts}Fee Level{/ts}</th>
          <th class="currency">{ts}Fee Amount{/ts}</th>
          <th class="register_date">{ts}Register Date{/ts}</th>
          <th>{ts}Source{/ts}</th>
          <th>{ts}Status{/ts}</th>
          <th>{ts}Role{/ts}</th>
          {if $allowFileSearch}<th>{ts}File{/ts}</th>{/if}
          <th></th>
          <th class="hiddenElement"></th>
        </tr>
        </thead>
        {foreach from=$summary.Participant item=row}
          <tr class="{cycle values="odd-row,even-row"}">
            <td>
              <a href="{crmURL p='civicrm/contact/view' q="reset=1&cid=`$row.contact_id`&context=fulltext&key=`$qfKey`"}"
                title="{ts}View Contact Details{/ts}">{$row.sort_name}</a>
            </td>
            <td>{$row.event_title}</td>
            <td>{$row.participant_fee_level}</td>
            <td>{$row.participant_fee_amount|crmMoney}</td>
            <td>{$row.participant_register_date|crmDate:"%b %d, %Y %l:%M %P"}</td>
            <td>{$row.participant_source}</td>
            <td>{$row.participant_status}</td>
            <td>{$row.participant_role}</td>
            {if $allowFileSearch}<td>{$row.fileHtml}</td>{/if}
            <td>
              <a href="{crmURL p='civicrm/contact/view/participant'
              q="reset=1&id=`$row.participant_id`&cid=`$row.contact_id`&action=view&context=fulltext&key=`$qfKey`"}">{ts}View{/ts}</a>
            </td>
            <td class="register_date hiddenElement">{$row.participant_register_date|crmDate}</td>
          </tr>
        {/foreach}
      </table>
    {if !$table and $summary.addShowAllLink.Participant}
      <div class="crm-section full-text-view-all-section"><a
        href="{crmURL p='civicrm/contact/search/custom' q="csid=`$csID`&reset=1&force=1&table=Participant&text=$text"}"
        title="{ts}View all results for participants{/ts}">&raquo;&nbsp;{ts}View all results for participants{/ts}</a>
      </div>{/if}
    {if $table}{include file="CRM/common/pager.tpl" location="below"}{/if}
    {* END Actions/Results section *}
  </div>
{/if}

{if !empty($summary.Membership) }
  <div class="section">
    {* Search request has returned 1 or more matching rows. *}

    <h3>{ts}Memberships{/ts}
      : {if !$table}{if $summary.Count.Membership <= $limit}{$summary.Count.Membership}{else}{ts 1=$limit}%1 or more{/ts}{/if}{else}{$summary.Count.Membership}{/if}</h3>
    {if $table}{include file="CRM/common/pager.tpl" location="top"}{/if}
    {* This section displays the rows along and includes the paging controls *}
      <table id="membership_listing" class="display" summary="{ts}Membership listings.{/ts}">
        <thead>
        <tr>
          <th class='link'>{ts}Member's Name{/ts}</th>
          <th>{ts}Membership Type{/ts}</th>
          <th class="currency">{ts}Membership Fee{/ts}</th>
          <th class="start_date">{ts}Membership Start Date{/ts}</th>
          <th class="end_date">{ts}Membership End Date{/ts}</th>
          <th>{ts}Source{/ts}</th>
          <th>{ts}Status{/ts}</th>
          {if $allowFileSearch}<th>{ts}File{/ts}</th>{/if}
          <th></th>
          <th class="hiddenElement"></th>
          <th class="hiddenElement"></th>
        </tr>
        </thead>
        {foreach from=$summary.Membership item=row}
          <tr class="{cycle values="odd-row,even-row"}">
            <td>
              <a href="{crmURL p='civicrm/contact/view' q="reset=1&cid=`$row.contact_id`&context=fulltext&key=`$qfKey`"}"
                title="{ts}View Contact Details{/ts}">{$row.sort_name}</a>
            </td>
            <td>{$row.membership_type}</td>
            <td>{$row.membership_fee|crmMoney}</td>
            <td>{$row.membership_start_date|crmDate:"%b %d, %Y %l:%M %P"}</td>
            <td>{$row.membership_end_date|crmDate:"%b %d, %Y %l:%M %P"}</td>
            <td>{$row.membership_source}</td>
            <td>{$row.membership_status}</td>
            {if $allowFileSearch}<td>{$row.fileHtml}</td>{/if}
            <td>
              <a href="{crmURL p='civicrm/contact/view/membership'
              q="reset=1&id=`$row.membership_id`&cid=`$row.contact_id`&action=view&context=fulltext&key=`$qfKey`"}">{ts}View{/ts}</a>
            </td>
            <td class="start_date hiddenElement">{$row.membership_start_date|crmDate}</td>
            <td class="end_date hiddenElement">{$row.membership_end_date|crmDate}</td>
          </tr>
        {/foreach}
      </table>
    {if !$table and $summary.addShowAllLink.Membership}
      <div class="crm-section full-text-view-all-section">
        <a href="{crmURL p='civicrm/contact/search/custom' q="csid=`$csID`&reset=1&force=1&table=Membership&text=$text"}"
        title="{ts}View all results for memberships{/ts}">&raquo;&nbsp;{ts}View all results for memberships{/ts}</a>
      </div>
    {/if}
    {if $table}{include file="CRM/common/pager.tpl" location="below"}{/if}
    {* END Actions/Results section *}
  </div>
{/if}

{if !empty($summary.File) }
<div class="section">
{* Search request has returned 1 or more matching rows. *}

  <h3>{ts}Files{/ts}:
      {if !$table}
          {if $summary.Count.File <= $limit}{$summary.Count.File}{else}{ts 1=$limit}%1 or more{/ts}{/if}
      {else}
           {$summary.Count.File}
      {/if}</h3>
  {if $table}{include file="CRM/common/pager.tpl" location="top"}{/if}

  {* This section displays the rows along and includes the paging controls *}
  <table id="file_listing" class="display" summary="{ts}File listings.{/ts}">
    <thead>
    <tr>
      <th class='link'>{ts}File Name{/ts}</th>
      <th>{ts}Type{/ts}</th>
      <th>{ts}Attached To{/ts}</th>
      <th></th>
    </tr>
    </thead>
    <tbody>
      {foreach from=$summary.File item=row}
        <tr class="{cycle values="odd-row,even-row"}">
          <td><a href="{$row.file_url}">{$row.file_name}</a></td>
          <td>{$row.file_mime_type}</td>
          <td>{crmCrudLink action=VIEW table=$row.file_entity_table id=$row.file_entity_id}</td>
          <td>
            <a href="{$row.file_url}">{ts}View{/ts}</a>
          </td>
        </tr>
      {/foreach}
    </tbody>
  </table>
  {if !$table and $summary.addShowAllLink.File}
  <div class="crm-section full-text-view-all-section">
    <a href="{crmURL p='civicrm/contact/search/custom' q="csid=`$csID`&reset=1&force=1&table=File&text=$text"}"
          title="{ts}View all results for files{/ts}">&raquo;&nbsp;{ts}View all results for files{/ts}</a>
  </div>{/if}
  {if $table}{include file="CRM/common/pager.tpl" location="below"}{/if}
{* END Actions/Results section *}
</div>
{/if}

{if !$table}{include file="CRM/common/pager.tpl" location="bottom"}{/if}
</div>
