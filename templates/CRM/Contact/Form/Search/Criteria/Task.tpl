{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{if $showTask}
    <div id="task_block" class="form-item">
    <table class="form-layout">
         <tr>
            <td class="label">
                {$form.task_id.label nofilter}
            </td>
            <td>
                {$form.task_id.html nofilter}
            </td>
            <td class="label">
                {$form.task_status_id.label nofilter}
            </td>
            <td>
                {$form.task_status_id.html nofilter}
            </td>
        </tr>
      </table>
    </div>
{/if}
