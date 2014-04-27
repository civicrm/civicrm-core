{*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.5                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2014                                |
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
{* Related Contacts/Memberships block within View Membership *}
<div class="view-content">
    <div class="crm-block crm-content-block">
    {include file="CRM/common/jsortable.tpl" useAjax=0}
            <div id="related-contacts-memberships">
                <h3>{ts}Related Contacts/Memberships{/ts}</h3>
                {strip}
                    <table id="related_contact" class="display">
                        <thead>
                        <tr>
                            <th>{ts}Relationship{/ts}</th>
                            <th>{ts}Relationship Start{/ts}</th>
                            <th>{ts}Relationship End{/ts}</th>
                            <th>{ts}Name{/ts}</th>
                            <th></th> {* Job title or other information complementing Relationship *}
                            <th>{ts}Membership Status{/ts}</th>
                            <th id="nosort">{$related_text}</th>
                        </tr>
                        </thead>
                        {foreach from=$related item=rel}
                        <tr id="rel_{$rel.id}" class="{cycle values="odd-row,even-row"} row-relationship {if $rel.membership_id}row-highlight{/if}">
                            <td>
                                <a href="{crmURL p='civicrm/contact/view/rel' q="action=view&reset=1&selectedChild=rel&cid=`$rel.cid`&id=`$rel.id`"}">{$rel.relation}</a>
                            </td>
                            <td>{$rel.start_date|crmDate}</td>
                            <td>{$rel.end_date|crmDate}</td>
                            <td class="bold">
                                <a href="{crmURL p='civicrm/contact/view' q="action=view&reset=1&cid=`$rel.cid`"}">{$rel.name}</a>
                            </td>
                            <td>{$rel.comment}</td>
                            <td class="bold">{$rel.status}</td>
                            <td class="nowrap">{$rel.action|replace:'xx':$rel.mid}</td>
                            </tr>
                        {/foreach}
                    </table>
                {/strip}
            </div>
    </div>
</div>
