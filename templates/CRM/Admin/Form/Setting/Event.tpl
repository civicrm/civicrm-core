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
{capture assign=docLink}{docURL page="CiviEvent Cart Checkout" text="CiviEvent Cart Checkout" resource="wiki"}{/capture}
<div class="crm-block crm-form-block">
<div id="help">
    {ts 1=$docLink}These settings are used to configure properties for the CiviEvent component. Please read the %1 documentation, and make sure you understand it before modifying default values.{/ts}
</div>
<div class="crm-block crm-form-block>
<div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="top"}</div>
      <table class="form-layout-compressed">
        <tr class="crm-mail-form-block-enable_cart">
            <td class="label">{$form.enable_cart.label}</td><td>{$form.enable_cart.html}<br />
            <span class="description">{ts}Check to enable the Event Cart checkout workflow.{/ts}</span></td>
        </tr>
      </table>
<div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
<div class="spacer"></div>
</div>
