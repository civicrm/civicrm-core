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
<div class="form-item">
<fieldset>
<legend>{ts}Sent SMS Message{/ts}</legend>
<dl>
<dt>{ts}Date Sent{/ts}</dt><dd>{$sentDate|crmDate}</dd>
<dt>{ts}From{/ts}</dt><dd>{if $fromName}{$fromName}{else}{ts}(display name not available){/ts}{/if}</dd>
<dt>{ts}To{/ts}</dt><dd>{$toName}</dd>
<dt>{ts}Message{/ts}</dt><dd>{$message}</dd>
<dt>&nbsp;</dt>
  <dd class="crm-submit-buttons">
    <a class="button cancel" href="{crmURL p='civicrm/contact/view/activity' q="history=1&show=1"}">{ts}Done{/ts}</a>
  </dd>
</dl>
</fieldset>
</div>
