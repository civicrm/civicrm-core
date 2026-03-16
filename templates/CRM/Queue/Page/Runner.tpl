{* For debugging try uncommenting the following line and then including this template footer.tpl
{assign var=queueRunnerData value=['buttons'=>['retry'=>TRUE,'skip'=>TRUE]]}
*}
<div class="crm-block crm-form-block crm-queue-runner-form-block panel" id=bootstrap-theme>
  <div id="crm-queue-runner-progress" >
    <div class="progress">
      <div class="progress-bar progress-bar-striped active" role="progressbar" aria-valuenow="{{ $ctrl.progress }}" aria-valuemin="0" aria-valuemax="100" style="width:0;">
        <span class="sr-only"></span>
      </div>
    </div>
  </div>
  <div id="crm-queue-runner-desc" class="panel-body bg-primary crm-flex-box crm-flex-justify-between crm-flex-align-center" >
    <div id="crm-queue-runner-title">{ts}Beginning first step{/ts}</div>
    <div id="crm-queue-runner-buttonset" class="crm-flex-box" style="flex: 0 0 max-content;">
      {if $queueRunnerData['buttons']['retry']}
      <button class="btn btn-primary" id="crm-queue-runner-retry"><i class="crm-i fa-backward-step"></i>{ts}Retry{/ts}</button>
      {/if}
      {if $queueRunnerData['buttons']['skip']}
      <button class="btn btn-warning" id="crm-queue-runner-skip"><i class="crm-i fa-fast-forward"></i>{ts}Skip{/ts}</button>
      {/if}
    </div>
  </div>
  <div id="crm-queue-runner-crash-text" class=panel-body>
    <p>{ts}The Retry button will retry the step that failed. Sometimes temporary factors may have affected the process on the first run and retrying may work. Try this first.{/ts}</p>
    <p>{ts}The Skip button will skip the step that failed and proceed with other steps. This could leave something broken, especially if the steps that follow depended on the success of the crashed step. Use this as last resort.{/ts}</p>
    <p>{ts}If you are running the update in production, you may want to roll back to your backup while you figure this out. You can use the CiviCRM chat site to search/ask for help.{/ts}<p>
    <ul style="list-style: disc;">
      <li><a href=https://chat.civicrm.org/civicrm/channels/town-square target=_blank >chat.civicrm.org</a></li>
      <li><a href=https://docs.civicrm.org/sysadmin/en/latest/troubleshooting/ target=_blank >{ts}Admin troubleshooting page{/ts}</a></li>
    </ul>
  </div>
  <div id="crm-queue-runner-message"></div>
</div>

{literal}
<script type="text/javascript">

CRM.$(function($) {
  // Note: Queue API provides "#remaining tasks" but not "#completed tasks" or "#total tasks".
  // To compute a %complete, we manually track #completed. This only works nicely if we
  // assume that the queue began with a fixed #tasks.

  var queueRunnerData = {/literal}{$queueRunnerData|@json}{literal};

  const buttonSet = document.getElementById('crm-queue-runner-buttonset'),
    progressBar =  document.querySelector('#crm-queue-runner-progress .progress-bar'),
    setProgress = (pct) => {
      progressBar.style.width = pct;
      progressBar.setAttribute('aria-valuenow',pct);
      progressBar.firstElementChild.textContent = pct;
    };
    // For debugging, uncomment the next line:
    // window.setProgress = setProgress;

  var displayResponseData = function(data, textStatus, jqXHR) {
    if (data.redirect_url) {
      window.location.href = data.redirect_url;
      return;
    }

    setProgress(100 * queueRunnerData.completed / (queueRunnerData.completed + queueRunnerData.numberOfItems) + '%');

    if (data.is_error) {
      buttonSet.style.display = '';
      $("#crm-queue-runner-crash-text").show();
      if (queueRunnerData.isEnded) {
        $('#crm-queue-runner-skip').button('disable');
      }
      $('#crm-queue-runner-title').text('Error: ' + data.last_task_title);
    } else if (!data.is_continue && queueRunnerData.numberOfItems == 0) {
      $('#crm-queue-runner-title').text('Done');
    } else {
      $('#crm-queue-runner-title').text('Executed: ' + data.last_task_title);
    }

    if (data.exception) {
      $('#crm-queue-runner-message').html('');
      $('<div></div>').html(data.exception).prependTo('#crm-queue-runner-message');
    }

  };

  var handleError = function(jqXHR, textStatus, errorThrown) {
    // Do this regardless of whether the response was well-formed
    buttonSet.style.display = '';
    $("#crm-queue-runner-crash-text").show();

    var data = $.parseJSON(jqXHR.responseText)
    if (data) {
      displayResponseData(data);
    }
  };

  var handleSuccess = function(data, textStatus, jqXHR) {
    if (!data.is_error) {
      queueRunnerData.completed++;
    }
    if ('numberOfItems' in data && data.numberOfItems !== null) {
      queueRunnerData.numberOfItems = parseInt(data.numberOfItems);
    }

    displayResponseData(data);

    // FIXME re-consider merits of is_continue in the corner-case of executing last step
    if (data.is_continue) {
      window.setTimeout(runNext, 50);
    } else if (!data.is_continue && queueRunnerData.numberOfItems == 0 && !queueRunnerData.isEnded) {
      queueRunnerData.isEnded = true;
      window.setTimeout(runNext, 50);
    }
  };

  // Dequeue and execute the next item
  var runNext = function() {
    $.ajax({
      type: 'POST',
      url: (queueRunnerData.isEnded ? queueRunnerData.onEndAjax : queueRunnerData.runNextAjax),
      data: {
        qrid: queueRunnerData.qrid
      },
      dataType: 'json',
      beforeSend: function(jqXHR, settings) {
          buttonSet.style.display = 'none';
          $("#crm-queue-runner-crash-text").hide();
      },
      error: handleError,
      success: handleSuccess
    });
  }

  var retryNext = function() {
    $('#crm-queue-runner-message').html('');
    runNext();
  }

  // Dequeue and the next item, then move on to runNext for the subsequent items
  var skipNext = function() {
    $.ajax({
      type: 'POST',
      url: queueRunnerData.skipNextAjax,
      data: {
        qrid: queueRunnerData.qrid
      },
      dataType: 'json',
      beforeSend: function(jqXHR, settings) {
        $('#crm-queue-runner-message').html('');
        buttonSet.style.display = 'none';
        $("#crm-queue-runner-crash-text").hide();
      },
      error: handleError,
      success: handleSuccess
    });
  }

  // Set up the UI

  setProgress('0%');

  if (queueRunnerData.buttons.retry == 1) {
  $("#crm-queue-runner-retry").click(retryNext);
  } else {
    $("#crm-queue-runner-retry").remove();
  }
  if (queueRunnerData.buttons.skip == 1) {
  $("#crm-queue-runner-skip").click(skipNext);
  } else {
    $("#crm-queue-runner-skip").remove();
  }
  $("#crm-queue-runner-buttonset").buttonset();
  $("#crm-queue-runner-buttonset").hide();
  $("#crm-queue-runner-crash-text").hide();
  window.setTimeout(runNext, 50);
});
</script>{/literal}
