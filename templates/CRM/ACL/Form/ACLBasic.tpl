{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{* this template is used for adding/editing ACL  *}
<div class="form-item">
<fieldset><legend>{if $action eq 1}{ts}New ACL{/ts}{elseif $action eq 2}{ts}Edit ACL{/ts}{else}{ts}Delete ACL{/ts}{/if}</legend>

{if $action eq 8}
  <div class="messages status no-popup">
    <dl>
      <dt><div class="icon inform-icon"></div></dt>
      <dd>
        {ts}WARNING: Delete will remove this permission from the specified ACL Role.{/ts} {ts}Do you want to continue?{/ts}
      </dd>
    </dl>
  </div>
{else}
  <dl>
    <dt>{$form.entity_id.label}</dt><dd>{$form.entity_id.html}</dd>
    <dt>&nbsp;</dt><dd class="description">{ts}Select a Role to assign (grant) this permission to. Select the special role 'Everyone' if you want to grant this permission to ALL users. 'Anyone' includes anonymous (i.e. not logged in) users.{/ts}</dd>
  </dl>
  <dl>
    <dt>{$form.object_table.label}</dt>
<dd>
<table>
<tr><td>
{$form.object_table.html}
</td></tr>
</table>
</dd>
  </dl>
{/if}
  <dl>
    <dt></dt><dd>{$form.buttons.html}</dd>
  </dl>
</fieldset>
</div>

