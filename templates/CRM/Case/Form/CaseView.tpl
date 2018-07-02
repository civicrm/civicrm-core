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
{* CiviCase -  view case screen*}

<div class="crm-block crm-form-block crm-case-caseview-form-block">

  {* here we are showing related cases w/ jquery dialog *}
  {if $showRelatedCases}
    {include file="CRM/Case/Form/ViewRelatedCases.tpl"}
  {* Main case view *}
  {else}

  <h3>{ts}Summary{/ts}</h3>
  <table class="report crm-entity case-summary" data-entity="case" data-id="{$caseID}" data-cid="{$contactID}">
    {if $multiClient}
      <tr class="crm-case-caseview-client">
        <td colspan="5" class="label">
          {ts}Clients:{/ts}
          {foreach from=$caseRoles.client item=client name=clients}
            <a href="{crmURL p='civicrm/contact/view' q="action=view&reset=1&cid=`$client.contact_id`"}" title="{ts}View contact record{/ts}">{$client.display_name}</a>{if not $smarty.foreach.clients.last}, &nbsp; {/if}
          {/foreach}
          <a href="#addClientDialog" class="crm-hover-button case-miniform" title="{ts}Add Client{/ts}" data-key="{crmKey name='civicrm/case/ajax/addclient'}">
            <i class="crm-i fa-user-plus"></i>
          </a>
          <div id="addClientDialog" class="hiddenElement">
            <input name="add_client_id" placeholder="{ts}- select contact -{/ts}" class="huge" data-api-params='{ldelim}"params": {ldelim}"contact_type": "{$contactType}"{rdelim}{rdelim}' />
          </div>
          {if $hasRelatedCases}
            <div class="crm-block relatedCases-link"><a class="crm-hover-button crm-popup medium-popup" href="{$relatedCaseUrl}">{$relatedCaseLabel}</a></div>
          {/if}
        </td>
      </tr>
    {/if}
    <tr>
      {if not $multiClient}
        <td>
          <table class="form-layout-compressed">
            {foreach from=$caseRoles.client item=client}
              <tr class="crm-case-caseview-display_name">
                <td class="label-left bold" style="padding: 0px; border: none;">{$client.display_name}</td>
              </tr>
              {if $client.phone}
                <tr class="crm-case-caseview-phone">
                  <td class="label-left description" style="padding: 1px">{$client.phone}</td>
                </tr>
              {/if}
              {if $client.birth_date}
                <tr class="crm-case-caseview-birth_date">
                  <td class="label-left description" style="padding: 1px">{ts}DOB{/ts}: {$client.birth_date|crmDate}</td>
                </tr>
              {/if}
            {/foreach}
          </table>
          {if $hasRelatedCases}
            <div class="crm-block relatedCases-link"><a class="crm-hover-button crm-popup medium-popup" href="{$relatedCaseUrl}">{$relatedCaseLabel}</a></div>
          {/if}
        </td>
      {/if}
      <td class="crm-case-caseview-case_subject label">
        <span class="crm-case-summary-label">{ts}Subject{/ts}:</span>&nbsp;<span class="crm-editable" data-field="subject">{$caseDetails.case_subject}</span>
      </td>
      <td class="crm-case-caseview-case_type label">
        <span class="crm-case-summary-label">{ts}Type{/ts}:</span>&nbsp;{$caseDetails.case_type}&nbsp;<a class="crm-hover-button crm-popup"  href="{crmURL p='civicrm/case/activity' q="action=add&reset=1&cid=`$contactId`&caseid=`$caseId`&selectedChild=activity&atype=`$changeCaseTypeId`"}" title="{ts}Change case type (creates activity record){/ts}"><i class="crm-i fa-pencil"></i></a>
      </td>
      <td class="crm-case-caseview-case_status label">
        <span class="crm-case-summary-label">{ts}Status{/ts}:</span>&nbsp;{$caseDetails.case_status}&nbsp;<a class="crm-hover-button crm-popup"  href="{crmURL p='civicrm/case/activity' q="action=add&reset=1&cid=`$contactId`&caseid=`$caseId`&selectedChild=activity&atype=`$changeCaseStatusId`"}" title="{ts}Change case status (creates activity record){/ts}"><i class="crm-i fa-pencil"></i></a>
      </td>
      <td class="crm-case-caseview-case_start_date label">
        <span class="crm-case-summary-label">{ts}Open Date{/ts}:</span>&nbsp;{$caseDetails.case_start_date|crmDate}&nbsp;<a class="crm-hover-button crm-popup"  href="{crmURL p='civicrm/case/activity' q="action=add&reset=1&cid=`$contactId`&caseid=`$caseId`&selectedChild=activity&atype=`$changeCaseStartDateId`"}" title="{ts}Change case start date (creates activity record){/ts}"><i class="crm-i fa-pencil"></i></a>
      </td>
      <td class="crm-case-caseview-{$caseID} label">
        <span class="crm-case-summary-label">{ts}ID{/ts}:</span>&nbsp;{$caseID}
      </td>
    </tr>
  </table>
  {if $hookCaseSummary}
    <div id="caseSummary" class="crm-clearfix">
      {foreach from=$hookCaseSummary item=val key=div_id}
        <div id="{$div_id}"><label>{$val.label}</label><div class="value">{$val.value}</div></div>
      {/foreach}
    </div>
  {/if}

  <div class="case-control-panel">
    <div>
      <p>
        {$form.add_activity_type_id.html}
        {if $hasAccessToAllCases} &nbsp;
          {$form.timeline_id.html}{$form._qf_CaseView_next.html} &nbsp;
          {$form.report_id.html}
        {/if}
      </p>
    </div>
    <div>
      <p>
        {if $hasAccessToAllCases}
          <a class="crm-hover-button action-item no-popup" href="{crmURL p='civicrm/case/report/print' q="all=1&redact=0&cid=$contactID&caseID=$caseId&asn=standard_timeline"}"><i class="crm-i fa-print"></i> {ts}Print Report{/ts}</a>
        {/if}

        {if !empty($exportDoc)}
          <a class="crm-hover-button action-item" href="{$exportDoc}"><i class="crm-i fa-file-pdf-o"></i> {ts}Export Document{/ts}</a>
        {/if}

        {if $mergeCases}
          <a href="#mergeCasesDialog" class="action-item no-popup crm-hover-button case-miniform"><i class="crm-i fa-compress"></i> {ts}Merge Case{/ts}</a>
          {$form._qf_CaseView_next_merge_case.html}
          <span id="mergeCasesDialog" class="hiddenElement">
            {$form.merge_case_id.html}
          </span>
        {/if}

        {if call_user_func(array('CRM_Core_Permission','giveMeAllACLs'))}
          <a class="action-item crm-hover-button medium-popup" href="{crmURL p='civicrm/contact/view/case/editClient' h=1 q="reset=1&action=update&id=$caseID&cid=$contactID"}"><i class="crm-i fa-user"></i> {ts}Assign to Another Client{/ts}</a>
        {/if}
      </p>
    </div>
  </div>

  <div class="clear"></div>
  {include file="CRM/Case/Page/CustomDataView.tpl"}

  <div class="crm-accordion-wrapper collapsed crm-case-roles-block">
    <div class="crm-accordion-header">
      {ts}Roles{/ts}
    </div><!-- /.crm-accordion-header -->
    <div class="crm-accordion-body">

      {if $hasAccessToAllCases}
        <div class="crm-submit-buttons">
          <a class="button case-miniform" href="#addCaseRoleDialog" data-key="{crmKey name='civicrm/ajax/relation'}" rel="#caseRoles-selector-{$caseID}"><i class="crm-i fa-plus-circle"></i> {ts}Add new role{/ts}</a>
        </div>
        <div id="addCaseRoleDialog" class="hiddenElement">
          <div>{$form.role_type.label}</div>
          <div>{$form.role_type.html}</div><br />
          <div><label for="add_role_contact_id">{ts}Assign To{/ts}:</label></div>
          <div><input name="add_role_contact_id" placeholder="{ts}- first select relationship type -{/ts}" class="huge" /></div>
        </div>
      {/if}

      <div id="editCaseRoleDialog" class="hiddenElement">
        <div><label for="edit_role_contact_id">{ts}Change To{/ts}:</label></div>
        <div><input name="edit_role_contact_id" placeholder="{ts}- select contact -{/ts}" class="huge" /></div>
      </div>

      <table id="caseRoles-selector-{$caseID}"  class="report-layout crm-ajax-table" data-page-length="10">
        <thead>
          <tr>
            <th data-data="relation">{ts}Case Role{/ts}</th>
            <th data-data="name">{ts}Name{/ts}</th>
            <th data-data="phone">{ts}Phone{/ts}</th>
            <th data-data="email">{ts}Email{/ts}</th>
            {if $relId neq 'client' and $hasAccessToAllCases}
              <th data-data="actions" data-orderable="false">{ts}Actions{/ts}</th>
            {/if}
          </tr>
        </thead>
      </table>
      {literal}
        <script type="text/javascript">
          (function($) {
            var caseId = {/literal}{$caseID}{literal};
            CRM.$('table#caseRoles-selector-' + caseId).data({
              "ajax": {
                "url": {/literal}'{crmURL p="civicrm/ajax/caseroles" h=0 q="snippet=4&caseID=$caseId&cid=$contactID&userID=$userID"}'{literal}
              }
            });
          })(CRM.$);
        </script>
      {/literal}

      <div id="deleteCaseRoleDialog" class="hiddenElement">
        {ts}Are you sure you want to end this relationship?{/ts}
      </div>

   </div><!-- /.crm-accordion-body -->
  </div><!-- /.crm-accordion-wrapper -->

  {if $hasAccessToAllCases}
  <div class="crm-accordion-wrapper collapsed crm-case-other-relationships-block">
    <div class="crm-accordion-header">
      {ts}Other Relationships{/ts}
    </div><!-- /.crm-accordion-header -->
    <div class="crm-accordion-body">
      <div class="crm-submit-buttons">
        {crmButton p='civicrm/contact/view/rel' q="action=add&reset=1&cid=`$contactId`&caseID=`$caseID`" icon="plus-circle"}{ts}Add client relationship{/ts}{/crmButton}
      </div>
      <table id="clientRelationships-selector-{$caseID}"  class="report-layout crm-ajax-table" data-page-length="10">
        <thead>
          <tr>
            <th data-data="relation">{ts}Client Relationship{/ts}</th>
            <th data-data="name">{ts}Name{/ts}</th>
            <th data-data="phone">{ts}Phone{/ts}</th>
            <th data-data="email">{ts}Email{/ts}</th>
          </tr>
        </thead>
      </table>
      {literal}
        <script type="text/javascript">
          (function($) {
            var caseId = {/literal}{$caseID}{literal};
            CRM.$('table#clientRelationships-selector-' + caseId).data({
              "ajax": {
                "url": {/literal}'{crmURL p="civicrm/ajax/clientrelationships" h=0 q="snippet=4&caseID=$caseId&cid=$contactID&userID=$userID"}'{literal}
              }
            });
          })(CRM.$);
        </script>
      {/literal}
  <br />
  {if !empty($globalGroupInfo.id)}
    <div class="crm-submit-buttons">
      <a class="button case-miniform" href="#addMembersToGroupDialog" rel="#globalRelationships-selector-{$caseId}" data-group_id="{$globalGroupInfo.id}">
        <i class="crm-i fa-plus-circle"></i> {ts 1=$globalGroupInfo.title}Add members to %1{/ts}
      </a>
    </div>
    <div id="addMembersToGroupDialog" class="hiddenElement">
      <input name="add_member_to_group_contact_id" placeholder="{ts}- select contacts -{/ts}" class="huge" />
    </div>
    <table id="globalRelationships-selector-{$caseId}"  class="report-layout crm-ajax-table" data-page-length="10">
      <thead>
        <tr>
          <th data-data="sort_name">{$globalGroupInfo.title}</th>
          <th data-data="phone">{ts}Phone{/ts}</th>
          <th data-data="email">{ts}Email{/ts}</th>
        </tr>
      </thead>
    </table>
    {literal}
      <script type="text/javascript">
        (function($) {
          var caseId = {/literal}{$caseID}{literal};
          CRM.$('table#globalRelationships-selector-' + caseId).data({
            "ajax": {
              "url": {/literal}'{crmURL p="civicrm/ajax/globalrelationships" h=0 q="snippet=4&caseID=$caseId&cid=$contactID&userID=$userID"}'{literal}
            }
          });
        })(CRM.$);
      </script>
    {/literal}
  {/if}

  </div><!-- /.crm-accordion-body -->
</div><!-- /.crm-accordion-wrapper -->

{/if} {* other relationship section ends *}
{include file="CRM/Case/Form/ActivityToCase.tpl"}

{* pane to display / edit regular tags or tagsets for cases *}
{if $showTags}
<div id="casetags" class="crm-accordion-wrapper  crm-case-tags-block">
 <div class="crm-accordion-header">
  {ts}Case Tags{/ts}
 </div><!-- /.crm-accordion-header -->
 <div class="crm-accordion-body">
  {if $tags}
    <p class="crm-block crm-content-block crm-case-caseview-display-tags">
      &nbsp;&nbsp;
      {foreach from=$tags item='tag'}
        <span class="crm-tag-item" {if !empty($tag.color)}style="background-color: {$tag.color}; color: {$tag.color|colorContrast};"{/if}>
          {$tag.text}
        </span>
      {/foreach}
    </p>
  {/if}

   {foreach from=$tagSetTags item=displayTagset}
     <p class="crm-block crm-content-block crm-case-caseview-display-tagset">
       &nbsp;&nbsp;<strong>{$displayTagset.name}:</strong>
       {', '|implode:$displayTagset.items}
     </p>
   {/foreach}

   {if !$tags && !$tagSetTags }
     <div class="status">
       {ts}There are no tags currently assigned to this case.{/ts}
     </div>
   {/if}

  <div class="crm-submit-buttons">
    <a class="button case-miniform" href="#manageTagsDialog" data-key="{crmKey name='civicrm/case/ajax/processtags'}">
      {if $tags || $tagSetTags}{ts}Edit Tags{/ts}{else}{ts}Add Tags{/ts}{/if}
    </a>
  </div>

 </div><!-- /.crm-accordion-body -->
</div><!-- /.crm-accordion-wrapper -->

<div id="manageTagsDialog" class="hiddenElement">
  <div class="label">{$form.case_tag.label}</div>
  <div class="view-value"><div class="crm-select-container">{$form.case_tag.html}</div>
    <br/>
    <div style="text-align:left;">{include file="CRM/common/Tagset.tpl" tagsetType='case'}</div>
    <br/>
    <div class="clear"></div>
  </div>
</div>

{/if} {* end of tag block*}

{include file="CRM/Case/Form/ActivityTab.tpl"}

<div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
{/if} {* view related cases if end *}
</div>
{literal}
<style type="text/css">
  .crm-case-caseview-case_subject span.crm-editable {
    padding-right: 32px;
    position: relative;
  }
  .crm-case-caseview-case_subject span.crm-editable:before {
    position: absolute;
    font-family: 'FontAwesome';
    top: 0;
    right: 10px;
    content: "\f040";
    opacity: 0.7;
    color: #000;
    font-size: .92em;
  }
  .crm-case-caseview-case_subject span.crm-editable-editing {
    padding-right: 0;
  }
  .crm-case-caseview-case_subject span.crm-editable-editing form > input {
    min-width: 20em;
    padding: 3px;
  }
  .crm-case-caseview-case_subject span.crm-editable-editing:before {
    content: "";
  }
</style>
{/literal}
