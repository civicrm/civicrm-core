{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
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
                            <td data-order="{$rel.start_date}">{$rel.start_date|crmDate}</td>
                            <td data-order="{$rel.end_date}">{$rel.end_date|crmDate}</td>
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
