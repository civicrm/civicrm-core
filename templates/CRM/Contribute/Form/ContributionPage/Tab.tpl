{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
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
                            <li><a class="crm-contribution-test" href="{crmURL p='civicrm/contribute/transact' q="reset=1&action=preview&id=`$contributionPageID`" fe='true'}">{ts}Online Contribution (Test-drive){/ts}</a></li>
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
