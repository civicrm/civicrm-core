{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{* this template is used for confirmation of delete for a file  *}
<fieldset><legend>{ts}Delete Attached File{/ts}</legend>
    <div class="status">
      <dl>
        <dt>{icon icon="fa-info-circle"}{/icon}</dt>
        <dd>
          {ts}WARNING: Are you sure you want to delete the attached file?{/ts}
        </dd>
      </dl>
    </div>

<dl><dt></dt><dd>{$form.buttons.html}</dd></dl>
</fieldset>
