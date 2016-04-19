{if !$printOnly}
{include file="CRM/Report/Form.tpl"}
{else}
<h1>{$reportTitle}</h1>
{if $statistics }
    {foreach from=$statistics.filters item=row}
        <h2>{$row.title} &nbsp:{$row.value}</h2>
    {/foreach}
{/if}

{*include the graph*}
{include file="CRM/Report/Form/Layout/Graph.tpl"}

{*modified table layout*}
{if (!$chartEnabled || !$chartSupported )&& $rows}
    {if $pager and $pager->_response and $pager->_response.numPages > 1}
        <br />
        <div class="report-pager">
            {include file="CRM/common/pager.tpl" noForm=0}
        </div>
    {/if}

    <table class={if !$printOnly}"report-layout"{else}"body"{/if}>
        <thead class="sticky">
        <tr>
            {foreach from=$columnHeaders item=header key=field}
                {assign var=class value=""}
                {if $header.type eq 1024 OR $header.type eq 1}
                {assign var=class value="class='reports-header-right'"}
                {else}
                    {assign var=class value="class='reports-header'"}
                {/if}
                {if !$skip}
                   {if $header.colspan}
                       <th {$header.class} colspan={$header.colspan}>{$header.title}</th>
                      {assign var=skip value=true}
                      {assign var=skipCount value=`$header.colspan`}
                      {assign var=skipMade  value=1}
                   {else}
                       <th {$class} {$header.class}>{$header.title}</th>
                   {assign var=skip value=false}
                   {/if}
                {else} {* for skip case *}
                   {assign var=skipMade value=`$skipMade+1`}
                   {if $skipMade >= $skipCount}{assign var=skip value=false}{/if}
                {/if}
            {/foreach}
        </tr>
        </thead>

        {assign var=rowCount  value=0}
        {foreach from=$rows item=row}
        {assign var=rowCount  value=`$rowCount+1`}
        {assign var=nextRow   value=`$rowCount+1`}
            <tr>
                {foreach from=$columnHeaders item=header key=field}
                    {assign var=fieldLink value=$field|cat:"_link"}
                    {assign var=fieldHover value=$field|cat:"_hover"}
                    <td {if $header.type eq 1024 OR $header.type eq 1} class="report-contents-right"{elseif $row.$field eq 'Subtotal'} class="report-label"{/if}>
                        {if $row.$fieldLink}
                            <a title="{$row.$fieldHover}" href="{$row.$fieldLink}">
                        {/if}

                        {if $row.$field eq 'Subtotal'}
                            {$row.$field}
                        {elseif $header.type & 4}
                            {if $header.group_by eq 'MONTH' or $header.group_by eq 'QUARTER'}
                                {$row.$field|crmDate:$config->dateformatPartial}
                            {elseif $header.group_by eq 'YEAR'}
                                {$row.$field|crmDate:$config->dateformatYear}
                            {else}
                                {$row.$field|truncate:10:''|crmDate}
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

        {if $printOnly and $rowCount % 7 eq 0 and $rows.$nextRow}

        </tbody>
        </table>
        <p>Response Codes: Y= Yes; N= No; U= Undecided; D= Declined to State</p>
        <p>Status Codes: NH= Not Home; MV= Moved; D= Deceased; WN= Wrong Number</p>
  {assign var=pageNum value= `$rowCount/7` }
        <p style="text-align: right;">Page {$pageNum|Ceil} of {$pageTotal}</p>
   <div class="page"></div>
        <h1>{$reportTitle}</h1>
        {if $statistics }
        {foreach from=$statistics.filters item=row}
            <h2>{$row.title} &nbsp:{$row.value}</h2>
        {/foreach}
        {/if}
        <table class={if !$printOnly}"report-layout"{else}"body"{/if}>
        <thead class="sticky">
        <tr>
            {foreach from=$columnHeaders item=header key=field}
                {assign var=class value=""}
                {if $header.type eq 1024 OR $header.type eq 1}
                {assign var=class value="class='reports-header-right'"}
                {else}
                    {assign var=class value="class='reports-header'"}
                {/if}
                {if !$skip}
                   {if $header.colspan}
                       <th {$header.class} colspan={$header.colspan}>{$header.title}</th>
                      {assign var=skip value=true}
                      {assign var=skipCount value=`$header.colspan`}
                      {assign var=skipMade  value=1}
                   {else}
                       <th {$class} {$header.class}>{$header.title}</th>
                   {assign var=skip value=false}
                   {/if}
                {else} {* for skip case *}
                   {assign var=skipMade value=`$skipMade+1`}
                   {if $skipMade >= $skipCount}{assign var=skip value=false}{/if}
                {/if}
            {/foreach}
        </tr>
        </thead>

        {/if}

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
    {if $printOnly}</tbody>{/if}
    </table>
    {if $printOnly}
    <p>Response Codes: Y= Yes; N= No; U= Undecided; D= Declined to State</p>
    <p>Status Codes: NH= Not Home; MV= Moved; D= Deceased; WN= Wrong Number</p>
    {assign var=pageNum value= `$rowCount/7` }
    <p style="text-align: right;">Page {$pageNum|Ceil} of {$pageTotal}</p>

    {/if}
{/if}

{* table layout ends *}
{if !$printOnly}
{*Statistics at the bottom of the page*}
{include file="CRM/Report/Form/Statistics.tpl" bottom=true}
{/if}
{include file="CRM/Report/Form/ErrorMessage.tpl"}
{/if}