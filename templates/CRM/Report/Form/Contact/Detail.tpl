{*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2016                                |
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
{* this div is being used to apply special css *}
    {if !$section }
    <div class="crm-block crm-form-block crm-report-field-form-block">
        {include file="CRM/Report/Form/Fields.tpl"}
    </div>
    {/if}

<div class="crm-block crm-content-block crm-report-form-block">
{include file="CRM/Report/Form/Actions.tpl"}
{if !$section }
{include file="CRM/Report/Form/Statistics.tpl" top=true}
{/if}
    {if $rows}
        <div class="report-pager">
            {include file="CRM/common/pager.tpl" location="top"}
        </div>

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

        {foreach from=$rows item=row}
                  <table class="report-layout crm-report_contact_civireport">
                        {eval var=$sectionHeaderTemplate}
                            <tr>
                                {foreach from=$columnHeaders item=header key=field}
                                    {if !$skip}
                                        {if $header.colspan}
                                            <th colspan={$header.colspan}>{$header.title}</th>
                                            {assign var=skip value=true}
                                            {assign var=skipCount value=`$header.colspan`}
                                            {assign var=skipMade  value=1}
                                        {else}
                                            <th>{$header.title}</th>
                                            {assign var=skip value=false}
                                        {/if}
                                    {else} {* for skip case *}
                                        {assign var=skipMade value=`$skipMade+1`}
                                        {if $skipMade >= $skipCount}{assign var=skip value=false}{/if}
                                    {/if}
                                {/foreach}
                            </tr>
                            <tr class="group-row crm-report">
                                {foreach from=$columnHeaders item=header key=field}
                                    {assign var=fieldLink value=$field|cat:"_link"}
                                    {assign var=fieldHover value=$field|cat:"_hover"}
                                    <td  class="report-contents crm-report_{$field}">
                                        {if $row.$fieldLink}<a title="{$row.$fieldHover}" href="{$row.$fieldLink}">{/if}

                                        {if $row.$field eq 'Subtotal'}
                                            {$row.$field}
                                        {elseif $header.type eq 12 || $header.type eq 4}
                                            {if $header.group_by eq 'MONTH' or $header.group_by eq 'QUARTER'}
                                                {$row.$field|crmDate:$config->dateformatPartial}
                                            {elseif $header.group_by eq 'YEAR'}
                                                {$row.$field|crmDate:$config->dateformatYear}
                                            {else}
                                                {$row.$field|truncate:10:''|crmDate}
                                            {/if}
                                        {elseif $header.type eq 1024}
                                            {$row.$field|crmMoney}
                                        {else}
                                            {$row.$field}
                                        {/if}

                                        {if $row.contactID} {/if}

                                        {if $row.$fieldLink}</a>{/if}
                                    </td>
                                {/foreach}
                            </tr>
                        </table>

                        {if $columnHeadersComponent}
                            {assign var=componentContactId value=$row.contactID}
                            {foreach from=$columnHeadersComponent item=pheader key=component}
                                {if $componentRows.$componentContactId.$component}
                                    <h3>{$component|replace:'_civireport':''|upper}</h3>
                          <table class="report-layout crm-report_{$component}">
                              {*add space before headers*}
                            <tr>
                                {foreach from=$pheader item=header}
                              <th>{$header.title}</th>
                                {/foreach}
                            </tr>

                              {foreach from=$componentRows.$componentContactId.$component item=row key=rowid}
                            <tr class="{cycle values="odd-row,even-row"} crm-report" id="crm-report_{$rowid}">
                                {foreach from=$columnHeadersComponent.$component item=header key=field}
                              {assign var=fieldLink value=$field|cat:"_link"}
                                                {assign var=fieldHover value=$field|cat:"_hover"}
                              <td class="report-contents crm-report_{$field}">
                                  {if $row.$fieldLink}
                                <a title="{$row.$fieldHover} "href="{$row.$fieldLink}">
                                  {/if}

                                  {if $row.$field eq 'Sub Total'}
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
                                {$row.$field|crmMoney}
                                  {else}
                                {$row.$field}
                                  {/if}

                                  {if $row.$fieldLink}</a>{/if}
                              </td>
                                {/foreach}
                            </tr>
                              {/foreach}
                          </table>
                            {/if}
                            {/foreach}
                        {/if}
        {/foreach}

  <div class="report-pager">
            {include file="CRM/common/pager.tpl"}
        </div>
        <br />
        {if $grandStat}
            <table class="report-layout">
                <tr>
                    {foreach from=$columnHeaders item=header key=field}
                        <td>
                            <strong>
                                {if $header.type eq 1024}
                                    {$grandStat.$field|crmMoney}
                                {else}
                                    {$grandStat.$field}
                                {/if}
                            </strong>
                        </td>
                    {/foreach}
                </tr>
            </table>
        {/if}

        {if !$section }
            {*Statistics at the bottom of the page*}
            {include file="CRM/Report/Form/Statistics.tpl" bottom=true}
        {/if}
    {/if}
    {include file="CRM/Report/Form/ErrorMessage.tpl"}
</div>
