{*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.3                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2013                                |
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
{assign var="element_name" value=$element.element_custom_name}
{if $element.is_view eq 0}{* fix for CRM-3510 *}
    {if $element.help_pre}
        <tr>
            <td>&nbsp;</td>
            <td class="html-adjust description">{$element.help_pre}</td>
        </tr>
    {/if}
     {if $element.options_per_line != 0 }
        <tr>
            <td class="label">{$form.address.$blockId.$element_name.label}</td>
            <td class="html-adjust">
                {assign var="count" value="1"}
                <table class="form-layout-compressed" style="margin-top: -0.5em;">
                    <tr>
                        {* sort by fails for option per line. Added a variable to iterate through the element array*}
                        {assign var="index" value="1"}
                        {foreach name=outer key=key item=item from=$form.address.$blockId.$element_name}
                            {if $index < 10}
                                {assign var="index" value=`$index+1`}
                            {else}
                                <td class="labels font-light">{$form.address.$blockId.$element_name.$key.html}</td>
                                {if $count == $element.options_per_line}
                                    </tr>
                                    <tr>
                                    {assign var="count" value="1"}
                                {else}
                                    {assign var="count" value=`$count+1`}
                                {/if}
                            {/if}
                        {/foreach}
                        {if $element.html_type eq 'Radio'}
                            <td><span class="crm-clear-link">(<a href="#" title="unselect" onclick="unselectRadio('{$element_name}', '{$form.formName}'); return false;" >{ts}clear{/ts}</a>)</span></td>
                        {/if}
                    </tr>
                </table>
            </td>
        </tr>

        {if $element.help_post}
            <tr>
                <td>&nbsp;</td>
                <td class="description">{$element.help_post}<br />&nbsp;</td>
            </tr>
             {/if}
    {else}
        <tr>
            <td class="label">{$form.address.$blockId.$element_name.label}</td>
            <td class="html-adjust">
                {if $element.data_type neq 'Date'}
                    {$form.address.$blockId.$element_name.html}&nbsp;
                {elseif $element.skip_calendar NEQ true }
                    {include file="CRM/common/jcalendar.tpl" blockId=$blockId blockSection='address' elementName=$element_name}
                {/if}

                {if $element.html_type eq 'Radio'}
                    <span class="crm-clear-link">(<a href="#" title="unselect" onclick="unselectRadio('address[{$blockId}][{$element_name}]', '{$form.formName}'); return false;" >{ts}clear{/ts}</a>)</span>

                {elseif $element.data_type eq 'File'}
                    {if $element.element_value.data}
                        <span class="html-adjust"><br />
                            &nbsp;{ts}Attached File{/ts}: &nbsp;
                            {if $element.element_value.displayURL }
                                <a href="#" onclick="popUp('{$element.element_value.displayURL}'); return false;" ><img src="{$element.element_value.displayURL}" height = "100" width="100"></a>
                            {else}
                                <a href="{$element.element_value.fileURL}">{$element.element_value.fileName}</a>
                            {/if}
                            {if $element.element_value.deleteURL }
                                <br />
                            {$element.element_value.deleteURL}
                            {/if}
                        </span>
                    {/if}
                {elseif $element.html_type eq 'Autocomplete-Select'}
        {assign var="element_name" value="address[$blockId][$element_name]" }
                    {if $element.data_type eq 'ContactReference'}
                      {include file="CRM/Custom/Form/ContactReference.tpl"}
                    {else}
                      {include file="CRM/Custom/Form/AutoComplete.tpl"}
                    {/if}
                {/if}
            </td>
        </tr>

        {if $element.help_post}

<td>&nbsp;</td>
<td class="description">{$element.help_post}<br />&nbsp;</td>
</tr>
        {/if}
    {/if}
{/if}
