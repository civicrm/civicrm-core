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
  {if !empty($printOnly)}
    <h1>{$reportTitle}</h1>
    <div id="report-date">{if !empty($reportDate)}{$reportDate}{/if}</div>
  {/if}
  {if !empty($statistics)}
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

{if $bottom and !empty($rows) and !empty($statistics)}
  <table class="report-layout">
    {if $statistics.counts}
      {foreach from=$statistics.counts item=row}
        <tr>
          <th class="statistics" scope="row">{$row.title}</th>
          <td>
            {if array_key_exists('type', $row) && $row.type eq 1024}
              {$row.value|crmMoney|escape}
            {elseif array_key_exists('type', $row) && $row.type eq 2}
              {$row.value|purify}
            {else}
               {$row.value|crmNumberFormat|escape}
            {/if}

          </td>
        </tr>
      {/foreach}
    {/if}
  </table>
{/if}
