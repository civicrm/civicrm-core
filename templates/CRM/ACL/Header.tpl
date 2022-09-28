{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{capture assign=docLink}{docURL page='user/initial-set-up/permissions-and-access-control/' text='Access Control Documentation'}{/capture}

<div class="help">
  <p>{ts 1=$docLink}ACLs allow you to control access to CiviCRM data. An ACL consists of an <strong>Operation</strong> (e.g. 'View' or 'Edit'), a <strong>set of data</strong> that the operation can be performed on (e.g. a group of contacts), and a <strong>Role</strong> that has permission to do this operation. Refer to the %1 for more info.{/ts}</p>
</div>

{php}
  $currentStep = $this->get_template_vars('step');
  $wizard = array(
    'style' => array(),
    'currentStepNumber' => $currentStep,
    'steps' => array(
      array(
        'title' => ts('Manage Roles'),
        'link' => CRM_Utils_System::url('civicrm/admin/options/acl_role', 'reset=1'),
      ),
      array(
        'title' => ts('Assign Users'),
        'link' => CRM_Utils_System::url('civicrm/acl/entityrole', 'reset=1'),
      ),
      array(
        'title' => ts('Manage ACLs'),
        'link' => CRM_Utils_System::url('civicrm/acl', 'reset=1'),
      ),
    ),
  );
  foreach ($wizard['steps'] as $num => &$step) {
    $step['step'] = $step['valid'] = $step['stepNumber'] = $num + 1;
    if ($step['stepNumber'] == $currentStep) {
      $step['link'] = NULL;
    }
  }
  $this->assign('wizard', $wizard);
{/php}

{include file="CRM/common/WizardHeader.tpl"}
