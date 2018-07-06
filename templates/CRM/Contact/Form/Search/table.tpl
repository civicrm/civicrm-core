{*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2018                                |
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
{* template for search builder *}
<div id="map-field">
{strip}
  {section start=1 name=blocks loop=$blockCount}
    {assign var="x" value=$smarty.section.blocks.index}
    <div class="crm-search-block">
      <h3>{if $x eq 1}{ts}Include contacts where{/ts}{else}{ts}Also include contacts where{/ts}{/if}</h3>
      <table>
        {section name=cols loop=$columnCount[$x]}
          {assign var="i" value=$smarty.section.cols.index}
          <tr>
            <td class="form-item even-row">
              {$form.mapper[$x][$i].html}
              {$form.operator[$x][$i].html|crmAddClass:'required'}&nbsp;&nbsp;
              <span class="crm-search-value" id="crm_search_value_{$x}_{$i}">
                {$form.value[$x][$i].html|crmAddClass:'required'}
              </span>
              {if $i gt 0 or $x gt 1}
                &nbsp;<a href="#" class="crm-reset-builder-row crm-hover-button" title="{ts}Remove this row{/ts}"><i class="crm-i fa-times"></i></a>
              {/if}
            </td>
          </tr>
        {/section}

        <tr class="crm-search-builder-add-row">
          <td class="form-item even-row underline-effect">
            {$form.addMore[$x].html}
          </td>
        </tr>
      </table>
    </div>
  {/section}
  <h3 class="crm-search-builder-add-block underline-effect">{$form.addBlock.html}</h3>
{/strip}
</div>
