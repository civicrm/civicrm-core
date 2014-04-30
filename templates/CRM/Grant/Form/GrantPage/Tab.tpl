{*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.2                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2012                                |
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
<div class="crm-actions-ribbon crm-grantpage-tab-actions-ribbon">
   <ul id="actions">
    	<li><div id="crm-grantpage-links-wrapper">
    	      <a id="crm-grantpage-links-link" class="button">
                <span><div class="icon dropdown-icon"></div>{ts}Grant Application Links{/ts}</span>
              </a>
    	        <div class="ac_results" id="crm-grantpage-links-list">
    	      	   <div class="crm-grantpage-links-list-inner">
    	      	   	<ul>
                            <li><a class="crm-grantpage-grant" href="{crmURL p='civicrm/grant/add' q="reset=1&action=add&context=standalone"}">{ts}New Grant{/ts}</a></li>
                            <li><a class="crm-grant-live" href="{crmURL p='civicrm/grant/transact' q="reset=1&id=`$grantApplicationPageID`" fe='true'}" target="_blank">{ts}Grant Application Page (Live){/ts}</a></li>
    		        </ul>
    	           </div>
    	      </div>
        </div></li>
        <div>
              {help id="id-configure-grant-pages"}
        </div>
  </ul>
  <div class="clear"></div>
</div>
{include file="CRM/common/TabHeader.tpl"}

{literal}
<script>

cj('body').click(function() {
	cj('#crm-grantpage-links-list').hide();
	});

cj('#crm-grantpage-links-link').click(function(event) {
	cj('#crm-grantpage-links-list').toggle();
	event.stopPropagation();
	});

</script>
{/literal}