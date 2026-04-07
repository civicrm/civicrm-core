{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{if $context EQ 'Search'}
    {include file="CRM/common/pager.tpl" location="top"}
{/if}

{strip}
<table class="selector row-highlight">
  <thead class="sticky">
  <tr>
    {if $context eq 'Search' }
        <th scope="col" title="{ts escape='htmlattribute'}Select rows{/ts}">{$form.toggleSelect.html}</th>
    {/if}
    {foreach from=$columnHeaders item=header}
        <th scope="col">
        {if $header.sort}
          {assign var='key' value=$header.sort}
          {$sort->_response.$key.link}
        {else}
          {$header.name}
        {/if}
        </th>
    {/foreach}
  </tr>
  </thead>

  {counter start=0 skip=1 print=false}
  {foreach from=$rows item=row}
  <tr id="rowid{$row.contact_id}">
    {if $context eq 'Search'}
      {assign var=cbName value=$row.checkbox}
      <td>{$form.$cbName.html}</td>
    {/if}
    <td>{$row.contact_type}</td>
    <td><a href="{crmURL p='civicrm/contact/view' q="reset=1&cid=`$row.contact_id`&key=`$qfKey`&context=`$context`"}">{$row.sort_name}</a></td>
    <td {if ($row.email_on_hold eq 1) or ($row.contact_opt_out eq 1)}class='font-red'{/if}>{$row.email}</td>
    <td>{$row.mailing_name}</td>
    <td>{$row.mailing_subject}</td>
    <td>{$row.mailing_job_status}</td>
    <td class="crm-mailing-end_date">{$row.mailing_job_end_date|crmDate}</td>
    <td>{$row.action|replace:'xx':$row.contact_id}</td>
  </tr>
  {/foreach}
</table>
{/strip}



{if $context EQ 'Search'}
    {include file="CRM/common/pager.tpl" location="bottom"}
{/if}
