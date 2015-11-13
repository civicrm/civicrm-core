{*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2015                                |
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
{* TabHeader.tpl provides a tabbed interface well as title for current step *}
<div class="crm-actions-ribbon crm-contribpage-tab-actions-ribbon">
   <ul id="actions">
      <li><div id="crm-contribpage-links-wrapper">
            {crmButton id="crm-contribpage-links-link" href="#" icon="bars"}{ts}Contribution Links{/ts}{/crmButton}
              <div class="ac_results" id="crm-contribpage-links-list">
                 <div class="crm-contribpage-links-list-inner">
                   <ul>
                            <li><a class="crm-contribpage-contribution" href="{crmURL p='civicrm/contribute/add' q="reset=1&action=add&context=standalone"}">{ts}New Contribution{/ts}</a></li>
                            <li><a class="crm-contribution-test" href="{crmURL p='civicrm/contribute/transact' q="reset=1&action=preview&id=`$contributionPageID`"}">{ts}Online Contribution (Test-drive){/ts}</a></li>
                            <li><a class="crm-contribution-live" href="{crmURL p='civicrm/contribute/transact' q="reset=1&id=`$contributionPageID`" fe='true'}" target="_blank">{ts}Online Contribution (Live){/ts}</a></li>
                </ul>
                 </div>
            </div>
        </div></li>
        <div>
              {help id="id-configure-contrib-pages"}
        </div>
  </ul>
  <div class="clear"></div>
</div>
{include file="CRM/common/TabHeader.tpl"}

{literal}
<script>

cj('body').click(function() {
  cj('#crm-contribpage-links-list').hide();
});

cj('#crm-contribpage-links-link').click(function(event) {
  cj('#crm-contribpage-links-list').toggle();
  event.stopPropagation();
  return false;
});

</script>
{/literal}
