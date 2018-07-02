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
<p>

{if $rows}
<div class="form-item crm-block crm-form-block crm-mailing-form-block">
     <span class="element-right">{$form.buttons.html}</span>
</div>
<div class="spacer"></div>
<br />
<p>
<table>
  <tr class="columnheader">
    <th>{ts}Name{/ts}</th>
    <th>{ts}Email{/ts}</th>
    <th>{ts}Mailing Name{/ts}</th>
    <th>{ts}Mailing Subject{/ts}</th>
    <th>{ts}Mailing Status{/ts}</th>
    <th>{ts}Completed Date{/ts}</th>
  </tr>
{foreach from=$rows item=row}
    <tr class="{cycle values="odd-row,even-row"} crm-mailing">
      <td class='crm-mailing-sort_name'>{$row.sort_name}</td>
      <td {if ($row.email_on_hold eq 1) or ($row.contact_opt_out eq 1)}class='font-red'{/if}>{$row.email}</td>
      <td class='crm-mailing-mailing_name'>{$row.mailing_name}</td>
      <td class='crm-mailing-mailing_subject'>{$row.mailing_subject}</td>
      <td class='crm-mailing-mailing_job_status'>{$row.mailing_job_status}</td>
      <td class="crm-mailing-end_date">{$row.mailing_job_end_date|crmDate}</td>
    </tr>
{/foreach}
</table>

<div class="form-item">
     <span class="element-right">{$form.buttons.html}</span>
</div>

{else}
   <div class="messages status no-popup">
     <div class="icon inform-icon"/>
        {ts}There are no records selected for Print.{/ts}
   </div>
{/if}
