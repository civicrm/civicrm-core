{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{if $top}
  {if $printOnly}
    <h1>{$reportTitle}</h1>
    <div id="report-date">{$reportDate}</div>
  {/if}
  {if $statistics}
    <table class="report-layout statistics-table">
      {foreach from=$statistics.groups item=row}
        <tr>
          <th class="statistics" scope="row">{$row.title}</th>
          <td>{$row.value|escape}</td>
        </tr>
      {/foreach}
      {foreach from=$statistics.filters item=row}
        <tr>
          <th class="statistics" scope="row">{$row.title}</th>
          <td>{$row.value|escape}</td>
        </tr>
      {/foreach}
    </table>
  {/if}
{/if}

{if $bottom and $rows and $statistics}
  <table class="report-layout">
    {foreach from=$statistics.counts item=row}
      <tr>
        <th class="statistics" scope="row">{$row.title}</th>
        <td>
          {if $row.type eq 1024}
            {$row.value|crmMoney|escape}
          {elseif $row.type eq 2}
            {$row.value|escape}
          {else}
            {$row.value|crmNumberFormat|escape}
          {/if}

        </td>
      </tr>
    {/foreach}
  </table>
{/if}
