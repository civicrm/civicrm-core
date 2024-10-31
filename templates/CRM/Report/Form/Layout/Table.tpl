{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{if !$rows}
  <p>{ts}None found.{/ts}</p>
{else}
    {if $pager and $pager->_response and $pager->_response.numPages > 1}
        <div class="report-pager">
            {include file="CRM/common/pager.tpl" location="top"}
        </div>
    {/if}
    <table class="report-layout display">
        {capture assign="tableHeader"}
            {foreach from=$columnHeaders item=header key=field}
                {assign var=class value=""}
                {if $header.type eq 1024 OR $header.type eq 1 OR $header.type eq 512}
                {assign var=class value="class='reports-header-right'"}
                {else}
                    {assign var=class value="class='reports-header'"}
                {/if}
                {if !$skip}
                   {if $header.colspan}
                       <th colspan={$header.colspan}>{$header.title|escape}</th>
                      {assign var=skip value=true}
                      {assign var=skipCount value=$header.colspan}
                      {assign var=skipMade  value=1}
                   {else}
                       <th {$class}>{$header.title|escape}</th>
                   {assign var=skip value=false}
                   {/if}
                {else} {* for skip case *}
                   {assign var=skipMade value=$skipMade+1}
                   {if $skipMade >= $skipCount}{assign var=skip value=false}{/if}
                {/if}
            {/foreach}
        {/capture}

        {if !$sections} {* section headers and sticky headers aren't playing nice yet *}
            <thead class="sticky">
            <tr>
              {$tableHeader nofilter}
            </tr>
        </thead>
        {/if}

        {* pre-compile section header here, rather than doing it every time under foreach *}
        {capture assign=sectionHeaderTemplate}
            {assign var=columnCount value=$columnHeaders|@count}
            {assign var=pageBroke value=0}
        {/capture}

        {foreach from=$rows item=row key=rowid}
          {foreach from=$sections item=section key=column name=sections}
            {counter start=1 assign="h"}
            {assign var=sectionColumn value=$sections.$column}
            {isValueChange value=$row.$column key=$column assign=isValueChanged}
              {if $isValueChanged}

              {if $sectionColumn.type & 4}
              {assign var=printValue value=$row.$column|crmDate}
              {elseif $sectionColumn.type eq 1024}
              {assign var=printValue value=$row.$column|crmMoney}
              {else}
              {assign var=printValue value=$row.$column}
              {/if}
              {if $rowid neq 0}
              {if $section.pageBreak}
                  {if $pageBroke >= $h or $pageBroke == 0}
                </table>
                <div class="page-break"></div>
                <table class="report-layout display">
                  {/if}
                  {assign var=pageBroke value=$h}
              {/if}
              {/if}
            <tr class="crm-report-sectionHeader crm-report-sectionHeader-{$h}"><th colspan="{$columnCount}">

                <h{$h}>{$section.title|escape}: {$printValue|default:"<em>none</em>"}
                  ({sectionTotal key=$row.$column depth=$smarty.foreach.sections.index totals=$sectionTotals})
                </h{$h}>
              </th></tr>
              {if $smarty.foreach.sections.last}
                <tr class="crm-report-sectionCols">{$tableHeader}</tr>
              {/if}
              {/if}
          {/foreach}
          <tr
            class="{cycle values="odd-row,even-row"} {if array_key_exists('class', $row)}{$row.class}{/if} crm-report"
            id="crm-report_{$rowid}"
          >
              {foreach from=$columnHeaders item=header key=field}
                  {assign var=fieldLink value=$field|cat:"_link"}
                  {assign var=fieldHover value=$field|cat:"_hover"}
                  {assign var=fieldClass value=$field|cat:"_class"}
                  <td class="crm-report-{$field}{if $header.type eq 1024 OR $header.type eq 1 OR $header.type eq 512} report-contents-right{elseif array_key_exists($field, $row) && $row.$field eq 'Subtotal'} report-label{/if}">
                      {if array_key_exists($fieldLink, $row) && $row.$fieldLink}
                          <a href="{$row.$fieldLink}"
                             {if array_key_exists($fieldHover, $row)}title="{$row.$fieldHover|escape}"{/if}
                             {if array_key_exists($fieldClass, $row)}class="{$row.$fieldClass}"{/if}
                          >
                      {/if}

                      {if array_key_exists($field, $row) && is_array($row.$field)}
                          {foreach from=$row.$field item=fieldrow key=fieldid}
                              <div class="crm-report-{$field}-row-{$fieldid}">{$fieldrow}</div>
                          {/foreach}
                      {elseif array_key_exists($field, $row) && $row.$field eq 'Subtotal'}
                          {$row.$field}
                      {elseif $header.type & 4 OR $header.type & 256}
                          {if $header.group_by eq 'MONTH' or $header.group_by eq 'QUARTER'}
                              {$row.$field|crmDate:$config->dateformatPartial}
                          {elseif $header.group_by eq 'YEAR'}
                              {$row.$field|crmDate:$config->dateformatYear}
                          {else}
                              {if $header.type == 4}
                                 {$row.$field|truncate:10:''|crmDate}
                              {else}
                                 {$row.$field|crmDate}
                              {/if}
                          {/if}
                      {elseif $header.type eq 1024}
                          {if $currencyColumn}
                              <span class="nowrap">{$row.$field|crmMoney:$row.$currencyColumn}</span>
                          {else}
                              <span class="nowrap">{$row.$field|crmMoney}</span>
                         {/if}
                      {elseif array_key_exists($field, $row)}
                          {$row.$field|smarty:nodefaults|purify}
                      {/if}

                      {if array_key_exists($fieldLink, $row) && $row.$fieldLink}</a>{/if}
                  </td>
              {/foreach}
          </tr>
        {/foreach}

        {if $grandStat}
            {* foreach from=$grandStat item=row*}
            <tr class="total-row">
                {foreach from=$columnHeaders item=header key=field}
                    <td class="report-label">
                        {if $header.type eq 1024}
                            {if $currencyColumn}
                                {$grandStat.$field|crmMoney:$row.$currencyColumn}
                            {else}
                                {$grandStat.$field|crmMoney}
                            {/if}
                        {elseif array_key_exists($field, $grandStat)}
                            {$grandStat.$field}
                        {/if}
                    </td>
                {/foreach}
            </tr>
            {* /foreach*}
        {/if}
    </table>
    {if $pager and $pager->_response and $pager->_response.numPages > 1}
        <div class="report-pager">
            {include file="CRM/common/pager.tpl" location="bottom"}
        </div>
    {/if}
{/if}
