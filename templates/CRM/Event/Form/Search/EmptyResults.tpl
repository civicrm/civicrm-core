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
{* No matches for submitted search request. *}
<div class="messages status no-popup">
  <div class="icon inform-icon"></div> &nbsp;
    {if $qill}{ts}No matches found for:{/ts}
        {include file="CRM/common/displaySearchCriteria.tpl"}
    {else}
        {ts}None found.{/ts}
    {/if}
  <br />
    {ts}Suggestions:{/ts}
    <ul>
        <li>{ts}if you are searching by participant name, check your spelling{/ts}</li>
        <li>{ts}try a different spelling or use fewer letters{/ts}</li>
        <li>{ts}if you are searching within a date range, try a wider range of values{/ts}</li>
        <li>{ts}make sure you have enough privileges in the access control system{/ts}</li>
    </ul>
 </div>
