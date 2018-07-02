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
<html xmlns="http://www.w3.org/1999/xhtml" lang="{$config->lcMessages|truncate:2:"":true}" xml:lang="{$config->lcMessages|truncate:2:"":true}">
<head>
  <title>{$pageTitle}</title>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
  <base href="{crmURL p="" a=1}" /><!--[if IE]></base><![endif]-->
  <style type="text/css" media="screen, print">@import url({$config->userFrameworkResourceURL}css/print.css);</style>
</head>

<body>
<div id="crm-container" class="crm-container">
<h1>{$pageTitle}</h1>
<div id="report-date">{$reportDate}</div>
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

<h2>{ts}Case Activities{/ts}</h2>
{foreach from=$activities item=activity key=key}
  <table  class ="report-layout">
       {foreach from=$activity item=field name=fieldloop}
           <tr class="crm-case-report-activity-{$field.label}">
             <th scope="row" class="label">{$field.label|escape}</th>
             {if $field.label eq 'Activity Type' or $field.label eq 'Status'}
                <td class="bold">{$field.value|escape}</td>
             {elseif $field.label eq 'Details' or $field.label eq 'Subject'}
                <td>{$field.value}</td>
             {else}
                <td>{$field.value|escape}</td>
             {/if}
           </tr>
       {/foreach}
  </table>
  <br />
{/foreach}
</div>
</body>
</html>





