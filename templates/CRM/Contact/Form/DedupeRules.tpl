{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
<h3>{ts 1=$contactTypes.$contact_type}Matching Rule for %1 Contacts{/ts}</h3>
<div class="crm-block crm-form-block crm-dedupe-rules-form-block">
    <div class="help">
        {ts}Configure up to five fields to evaluate when searching for 'suspected' duplicate contact records.{/ts} {help id="configure-rules"}
    </div>
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
        <td class="label">{ts}Usage{/ts}</td>
        <td>
          <div>
            <p><strong>{ts}Currently set to: {/ts}<span class='js-dedupe-rules-current'></span></strong></p>
            <p class='js-dedupe-rules-desc'></p>
            <p><button class='crm-button js-dedupe-rules-change' type='button' {if NOT $canChangeUsage} disabled title='{ts escape='htmlattribute' 1=$ruleUsed}To change the usage for this rule, please configure another rule as %1{/ts}'{/if}>{ts}Change usage{/ts}</button></p>
          </div>
        </td>
     </tr>
     <tr class="crm-dedupe-rules-form-block-is_reserved">
        <td class="label">{$form.is_reserved.label}</td>
        <td>{$form.is_reserved.html}
          {if empty($isReserved)}
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
            {if !empty($ruleName) and ($ruleName EQ 'IndividualSupervised' OR $ruleName EQ 'IndividualUnsupervised' OR $ruleName EQ 'IndividualGeneral')}
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
              {if !empty($isReserved)}
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

<div class='dedupe-rules-dialog' data-title='{ts escape='htmlattribute'}Change usage{/ts}' data-button-close='{ts escape='htmlattribute'}Close{/ts}' data-button-update='{ts escape='htmlattribute'}Update{/ts}' hidden>
  <p>{ts}CiviCRM includes three types of dedupe rule. <strong>You can only configure one 'Unsupervised' and one 'Supervised' rule for each contact type, but you can configure any number of additional 'General' rules to provide other criteria to scan for possible duplicates.</strong>{/ts}</p>
  <p>{ts}Selecting 'Unsupervised' or 'Supervised' will convert the previously configured rule of that type to 'General'.{/ts}</p>
  <div>
    <label>
      <input type="radio" name="usedDialog" value="Unsupervised">
      <p><strong class='dedupe-rules-dialog-title'>{ts}Unsupervised{/ts}</strong></p>
      <p class='dedupe-rules-dialog-desc'>{ts}The 'Unsupervised' rule for each contact type is automatically used when new contacts are created through online registrations including Events, Membership, Contributions and Profile pages. They are also selected by default when you Import contacts. They are generally configured with a narrow definition of what constitutes a duplicate.{/ts}</p>
    </label>
  </div>
  <div>
    <label>
      <input type="radio" name="usedDialog" value="Supervised">
      <p><strong class='dedupe-rules-dialog-title'>{ts}Supervised{/ts}</strong></p>
      <p class='dedupe-rules-dialog-desc'>{ts}The 'Supervised' rule for each contact type is automatically used to check for possible duplicates when contacts are added or edited via the user interface. Supervised Rules should be configured with a broader definition of what constitutes a duplicate.{/ts}</p>
    </label>
  </div>
  <div>
    <label>
      <input type="radio" name="usedDialog" value="General">
      <p><strong class='dedupe-rules-dialog-title'>{ts}General{/ts}</strong></p>
      <p class='dedupe-rules-dialog-desc'>{ts}You can configure any number of 'General' rules, to provide other criteria to scan for possible duplicates.{/ts}</p>
    </label>
  </div>
</div>
