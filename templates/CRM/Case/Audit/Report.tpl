{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
<html xmlns="http://www.w3.org/1999/xhtml" lang="{$config->lcMessages|truncate:2:"":true}" xml:lang="{$config->lcMessages|truncate:2:"":true}">
<head>
  <title>{$pageTitle}</title>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
  <base href="{crmURL p="" a=1}" /><!--[if IE]></base><![endif]-->
  <style media="screen, print">@import url({$config->userFrameworkResourceURL}css/print.css);</style>
</head>

<body>
<div id="crm-container" class="crm-container">
<h1>{$pageTitle}</h1>
<div id="report-date">{$reportDate}</div>
{if $case}
  <h2>{ts}Case Summary{/ts}</h2>
  <table class="report-layout">
    <tr>
      <th class="reports-header">{ts}Client{/ts}</th>
      <th class="reports-header">{ts}Case Type{/ts}</th>
      <th class="reports-header">{ts}Status{/ts}</th>
      <th class="reports-header">{ts}Start Date{/ts}</th>
      <th class="reports-header">{ts}Case ID{/ts}</th>
    </tr>
    <tr>
      <td class="crm-case-report-clientName">{$case.clientName}</td>
      <td class="crm-case-report-caseType">{$case.caseType}</td>
      <td class="crm-case-report-status">{$case.status}</td>
      <td class="crm-case-report-start_date">{$case.start_date}</td>
      <td class="crm-case-report-{$caseId}">{$caseId}</td>
    </tr>
  </table>
{/if}

{if $caseRelationships}
  <h2>{ts}Case Roles{/ts}</h2>
  <table class ="report-layout">
    <tr>
      <th class="reports-header">{ts}Case Role{/ts}</th>
      <th class="reports-header">{ts}Name{/ts}</th>
      <th class="reports-header">{ts}Phone{/ts}</th>
      <th class="reports-header">{ts}Email{/ts}</th>
    </tr>

    {foreach from=$caseRelationships item=row key=relId}
      <tr>
        <td class="crm-case-report-caserelationships-relation">{$row.relation}</td>
        <td class="crm-case-report-caserelationships-name">{$row.name}</td>
        <td class="crm-case-report-caserelationships-phone">{$row.phone}</td>
        <td class="crm-case-report-caserelationships-email">{$row.email}</td>
      </tr>
    {/foreach}
    {foreach from=$caseRoles item=relName key=relTypeID}
      {if $relTypeID neq 'client'}
        <tr>
          <td>{$relName}</td>
          <td>{ts}(not assigned){/ts}</td>
          <td></td>
          <td></td>
        </tr>
      {else}
        <tr>
          <td class="crm-case-report-caseroles-role">{$relName.role}</td>
          <td class="crm-case-report-caseroles-sort_name">{$relName.sort_name}</td>
          <td class="crm-case-report-caseroles-phone">{$relName.phone}</td>
          <td class="crm-case-report-caseroles-email">{$relName.email}</td>
        </tr>
      {/if}
    {/foreach}
  </table>
  <br />
{/if}
{if $otherRelationships}
    <table  class ="report-layout">
         <tr>
        <th class="reports-header">{ts}Client Relationship{/ts}</th>
        <th class="reports-header">{ts}Name{/ts}</th>
        <th class="reports-header">{ts}Phone{/ts}</th>
        <th class="reports-header">{ts}Email{/ts}</th>
      </tr>
        {foreach from=$otherRelationships item=row key=relId}
        <tr>
            <td class="crm-case-report-otherrelationships-relation">{$row.relation}</td>
            <td class="crm-case-report-otherrelationships-name">{$row.name}</td>
            <td class="crm-case-report-otherrelationships-phone">{$row.phone}</td>
            <td class="crm-case-report-otherrelationships-email">{$row.email}</td>
        </tr>
        {/foreach}
    </table>
    <br />
{/if}

{if $globalRelationships}
    <table class ="report-layout">
         <tr>
         <th class="reports-header">{$globalGroupInfo.title}</th>
          <th class="reports-header">{ts}Phone{/ts}</th>
         <th class="reports-header">{ts}Email{/ts}</th>
      </tr>
        {foreach from=$globalRelationships item=row key=relId}
        <tr>
            <td class="crm-case-report-globalrelationships-sort_name">{$row.sort_name}</td>
            <td class="crm-case-report-globalrelationships-phone">{$row.phone}</td>
            <td class="crm-case-report-globalrelationships-email">{$row.email}</td>
        </tr>
      {/foreach}
    </table>
{/if}

{if $caseCustomFields}
  {foreach from=$caseCustomFields item=group}
    <h2>{$group.title}</h2>
      <table class ="report-layout">
        {foreach from=$group.values item=row}
          <tr>
            <th class="label">{$row.label}</td>
            <td class="crm-case-report-custom-field">{$row.value}</td>
          </tr>
        {/foreach}
    </table>
  {/foreach}
{/if}

{if $activities}
  <h2>{ts}Case Activities{/ts}</h2>
  {foreach from=$activities item=activity key=key}
    <table  class ="report-layout">
      {foreach from=$activity item=field}
        {* TODO: Using an unmunged field in the css class would have always been problematic? Since it sometimes has spaces. *}
        <tr class="crm-case-report-activity-{$field.name}">
          <th scope="row" class="label">{$field.label|escape}</th>
          {if $field.name eq 'Activity Type' or $field.name eq 'Status'}
            <td class="bold">{$field.value|escape}</td>
          {* TODO: See note in CRM/Case/XMLProcessor/Report.php: Subject is already escaped in the php file so that's why it's not escaped here, but should that be reversed? *}
          {elseif $field.name eq 'Details' or $field.name eq 'Subject'}
            <td>{$field.value}</td>
          {else}
            <td>{$field.value|escape}</td>
          {/if}
        </tr>
      {/foreach}
    </table>
    <br />
  {/foreach}
{/if}
</div>
</body>
</html>
