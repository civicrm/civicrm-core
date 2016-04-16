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

<h3>{ts 1=$contact_type}Matching Rule for %1 Contacts{/ts}</h3>
<div class="crm-block crm-form-block crm-dedupe-rules-form-block">
    <div class="help">
        {ts}Configure up to five fields to evaluate when searching for 'suspected' duplicate contact records.{/ts} {help id="id-rules"}
    </div>
    <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="top"}</div>
  <table class="form-layout">
     <tr class="crm-dedupe-rules-form-block-title">
        <td class="label">{$form.title.label}</td>
        <td>
            {$form.title.html}
            <div class="description">
                {ts}Enter descriptive name for this matching rule.{/ts}
            </div>
        </td>
    </tr>
    <tr class="crm-dedupe-rules-form-block-used">
        <td class="label">{$form.used.label}</td>
        <td>{$form.used.html} {help id="id-rule-used"}</td>
     </tr>
     <tr class="crm-dedupe-rules-form-block-is_reserved">
        <td class="label">{$form.is_reserved.label}</td>
        <td>{$form.is_reserved.html}
          {if !$isReserved}
            <br />
            <span class="description">{ts}WARNING: Once a rule is marked as reserved it can not be deleted and the fields and weights can not be modified.{/ts}</span>
          {/if}
        </td>
     </tr>
     <tr class="crm-dedupe-rules-form-block-fields">
        <td></td>
        <td>
          <table class="form-layout-compressed">
            {* Hide fields and document match criteria for optimized reserved rules. *}
            {if $ruleName EQ 'IndividualSupervised' OR $ruleName EQ 'IndividualUnsupervised' OR $ruleName EQ 'IndividualGeneral'}
            <tr>
                <td>
                  <div class="status message">
                    {ts}This reserved rule is pre-configured with matching fields to optimize dedupe scanning performance. It matches on:{/ts}
                    <ul>
                      {if $ruleName EQ 'IndividualUnsupervised'}
                        <li>{ts}Email only{/ts}</li>
                      {elseif $ruleName EQ 'IndividualSupervised'}
                        <li>{ts}Email{/ts}</li>
                        <li>{ts}First Name{/ts}</li>
                        <li>{ts}Last Name{/ts}</li>
                      {elseif $ruleName EQ 'IndividualGeneral'}
                        <li>{ts}First Name{/ts}</li>
                        <li>{ts}Last Name{/ts}</li>
                        <li>{ts}Middle Name (if present){/ts}</li>
                        <li>{ts}Suffix (if present){/ts}</li>
                        <li>{ts}Street Address (if present){/ts}</li>
                        <li>{ts}Birth Date (if present){/ts}</li>
                      {/if}
                    </ul>
                  </div>
                </td>
            </tr>
            {else}
              {if $isReserved}
                  <tr>
                      <td>
                        <div class="status message">
                          {ts}Note: You cannot edit fields for a reserved rule.{/ts}
                        </div>
                      </td>
                  </tr>
              {/if}
              <tr class="columnheader"><td>{ts}Field{/ts}</td><td>{ts}Length{/ts}</td><td>{ts}Weight{/ts}</td></tr>
                {section name=count loop=5}
                  {capture assign=where}where_{$smarty.section.count.index}{/capture}
                  {capture assign=length}length_{$smarty.section.count.index}{/capture}
                  {capture assign=weight}weight_{$smarty.section.count.index}{/capture}
                  <tr class="{cycle values="odd-row,even-row"}">
                      <td>{$form.$where.html}</td>
                      <td>{$form.$length.html}</td>
                      <td>{$form.$weight.html}</td>
                  </tr>
                {/section}
              <tr class="columnheader"><td colspan="2">{$form.threshold.label}</td>
                <td>{$form.threshold.html}</td>
              </tr>
            {/if}
          </table>
        </td>
    </tr>
  </table>
  <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
</div>
