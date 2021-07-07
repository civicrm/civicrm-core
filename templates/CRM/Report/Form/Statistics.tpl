{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{if !empty($top)}
  {if !empty($printOnly)}
    <h1>{$reportTitle}</h1>
    <div id="report-date">{if !empty($reportDate)}{$reportDate}{/if}</div>
  {/if}
  {if !empty($statistics)}
    <table class="report-layout statistics-table">
      {if !empty($statistics.groups)}
        {foreach from=$statistics.groups item=row}
          <tr>
            <th class="statistics" scope="row">{$row.title}</th>
            <td>{$row.value|escape}</td>
          </tr>
        {/foreach}
      {/if}
      {if !empty($statistics.filters)}
        {foreach from=$statistics.filters item=row}
          <tr>
            <th class="statistics" scope="row">{$row.title}</th>
            <td>{$row.value|escape}</td>
          </tr>
        {/foreach}
      {/if}
    </table>
  {/if}
{/if}

{if !empty($bottom) and !empty($rows) and !empty($statistics)}
  <table class="report-layout">
    {if !empty($statistics.counts)}
      {foreach from=$statistics.counts item=row}
        <tr>
          <th class="statistics" scope="row">{$row.title}</th>
          <td>
            {if !empty($row.type) and $row.type eq 1024}
              {$row.value|crmMoney|escape}
            {elseif !empty($row.type) and $row.type eq 2}
              {$row.value|escape}
            {else}
               {$row.value|crmNumberFormat|escape}
            {/if}

          </td>
        </tr>
      {/foreach}
    {/if}
  </table>
{/if}
