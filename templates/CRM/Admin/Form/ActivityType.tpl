{*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.4                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2013                                |
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
{* this template is used for adding/editing/deleting activity type  *}
<div class="form-item">
<fieldset><legend>{if $action eq 1}{ts}New Activity Type{/ts}{elseif $action eq 2}{ts}Edit Activity Type{/ts}{else}{ts}Delete Activity Type{/ts}{/if}</legend>

   {if $action eq 8}
      <div class="messages status no-popup">
        <dl>
          <dt><div class="icon inform-icon"></div></dt>
          <dd>
          {ts}WARNING: Deleting this option will result in the loss of all activity type records which use the option.{/ts} {ts}This may mean the loss of a substantial amount of data, and the action cannot be undone.{/ts} {ts}Do you want to continue?{/ts}
          </dd>
       </dl>
      </div>
     {else}
      <dl>
       <dt>{$form.name.label}</dt><dd>{$form.name.html}</dd>
      <dt>{$form.description.label}</dt><dd>{$form.description.html}</dd>
        <dt>{$form.is_active.label}</dt><dd>{$form.is_active.html}</dd>
      </dl>
     {/if}
    <dl>
      <dt></dt><dd>{$form.buttons.html}</dd>
    </dl>
</fieldset>
</div>
