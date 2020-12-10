{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
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
     {icon icon="fa-info-circle"}{/icon}
        {ts}There are no records selected for Print.{/ts}
   </div>
{/if}
