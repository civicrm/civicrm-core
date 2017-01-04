{*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2017                                |
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
{if $action eq 8}
  {include file="CRM/PCP/Form/PCP/Delete.tpl"}
{else}
    <div id="pcp" class="crm-block crm-form-block crm-pcp-search-form-block">
        <h3>{ts}Find Campaign Pages{/ts}</h3>
        <table class="form-layout-compressed">
      <tr>
        <td>{$form.status_id.label}<br />{$form.status_id.html}</td>
        <td>{$form.page_type.label}<br />{$form.page_type.html}</td>
        <td>{$form.page_id.label}<br />{$form.page_id.html}</td>
        <td>{$form.event_id.label}<br />{$form.event_id.html}</td>
      </tr>
        </table>
        <div class="crm-submit-buttons">{$form.buttons.html}</div>
    </div>
{/if}
