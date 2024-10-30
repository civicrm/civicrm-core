{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{if (!$chartEnabled || !$chartSupported )&& $rows}
    {if $pager and $pager->_response and $pager->_response.numPages > 1}
        <div class="report-pager">
            {include file="CRM/common/pager.tpl" location="top"}
        </div>
    {/if}
    <div class="crm-report-overlay form-layout-compressed">
    <table class="crm-table-group-summary form-layout-compressed">
        {capture assign="tableHeader"}
        <tr>
            {foreach from=$columnHeaders item=header key=field}
                {if !$skip}
                   {if $header.colspan}
                      <td colspan={$header.colspan}>{$header.title}</td>
                      {assign var=skip value=true}
                      {assign var=skipCount value=$header.colspan}
                      {assign var=skipMade  value=1}
                   {else}
                      <td>{$header.title}</td>
                       {assign var=skip value=false}
                   {/if}
                {else} {* for skip case *}
                   {assign var=skipMade value=$skipMade+1}
                   {if $skipMade >= $skipCount}{assign var=skip value=false}{/if}
                {/if}
            {/foreach}
        </tr>
        {/capture}

        {if !$sections} {* section headers and sticky headers aren't playing nice yet *}
          <thead>
            <tr>
                {$tableHeader}
            </tr>
          </thead>
        {/if}

        {* pre-compile section header here, rather than doing it every time under foreach *}
        {capture assign=sectionHeaderTemplate}
            {assign var=columnCount value=$columnHeaders|@count}
            {assign var=l value=$smarty.ldelim}
            {assign var=r value=$smarty.rdelim}
            {foreach from=$sections item=section key=column name=sections}
                {counter assign="h"}
                {$l}isValueChange value=$row.{$column} key="{$column}" assign=isValueChanged{$r}
                {$l}if $isValueChanged{$r}

                    {$l}if $sections.{$column}.type & 4{$r}
                        {$l}assign var=printValue value=$row.{$column}|crmDate{$r}
                    {$l}elseif $sections.{$column}.type eq 1024{$r}
                        {$l}assign var=printValue value=$row.{$column}|crmMoney{$r}
                    {$l}else{$r}
                        {$l}assign var=printValue value=$row.{$column}{$r}
                    {$l}/if{$r}

                    <tr><th colspan="{$columnCount}">
                        <h{$h}>{$section.title}: {$l}$printValue|default:"<em>none</em>"{$r}
                            ({$l}sectionTotal key=$row.{$column} depth={$smarty.foreach.sections.index}{$r})
                        </h{$h}>
                    </th></tr>
                    {if $smarty.foreach.sections.last}
                        <tr>{$l}$tableHeader{$r}</tr>
                    {/if}
                {$l}/if{$r}
            {/foreach}
        {/capture}

        {foreach from=$rows item=row key=rowid}
           {eval var=$sectionHeaderTemplate}
            <tr  class="{$row.class}" id="crm-report_{$rowid}">
                {foreach from=$columnHeaders item=header key=field}
                    {assign var=fieldLink value=$field|cat:"_link"}
                    {assign var=fieldHover value=$field|cat:"_hover"}
                    {assign var=fieldClass value=$field|cat:"_class"}
                    <td class="crm-report-{$field}{if $header.type eq 1024 OR $header.type eq 1} report-contents-right{elseif $row.$field eq 'Subtotal'} report-label{/if}">
                        {if $row.$fieldLink}
                            <a title="{$row.$fieldHover|escape}" href="{$row.$fieldLink}" {$row.$fieldClass}>
                        {/if}

                        {if is_array($row.$field)}
                            {foreach from=$row.$field item=fieldrow key=fieldid}
                                <div class="crm-report-{$field}-row-{$fieldid}">{$fieldrow}</div>
                            {/foreach}
                        {elseif $row.$field eq 'Subtotal'}
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
                            <span class="nowrap">{$row.$field|crmMoney}</span>
                        {else}
                            {$row.$field}
                        {/if}

                        {if $row.$fieldLink}</a>{/if}
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
                            {$grandStat.$field|crmMoney}
                        {else}
                            {$grandStat.$field}
                        {/if}
                    </td>
                {/foreach}
            </tr>
            {* /foreach*}
        {/if}
    </table>
    </div>
    {if $pager and $pager->_response and $pager->_response.numPages > 1}
        <div class="report-pager">
            {include file="CRM/common/pager.tpl" location="bottom"}
        </div>
    {/if}
{/if}
