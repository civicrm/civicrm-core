<!-- FIXME: CSS conventions and polish -->
<div class="crm-block crm-form-block crm-queue-runner-form-block">
  <div id="crm-queue-runner-progress"></div>
  <div id="crm-queue-runner-desc">
    <div id="crm-queue-runner-buttonset" style="right:20px;position:absolute;">
      <button id="crm-queue-runner-retry">Retry</button>
      <button id="crm-queue-runner-skip">Skip</button>
    </div>
    <div>[<span id="crm-queue-runner-title"></span>]</div>
  </div>
  <div id="crm-queue-runner-message"></div>
</div>

{literal}
<script type="text/javascript">

cj(function() {
  // Note: Queue API provides "#remaining tasks" but not "#completed tasks" or "#total tasks".
  // To compute a %complete, we manually track #completed. This only works nicely if we
  // assume that the queue began with a fixed #tasks.
  
  var queueRunnerData = {/literal}{$queueRunnerData|@json}{literal};

  var displayResponseData = function(data, textStatus, jqXHR) {
    if (data.redirect_url) {
      window.location = data.redirect_url;
      return;
    }
    
    var pct = 100 * queueRunnerData.completed / (queueRunnerData.completed + queueRunnerData.numberOfItems);
    cj("#crm-queue-runner-progress").progressbar({ value: pct });
    
    if (data.is_error) {
      cj("#crm-queue-runner-buttonset").show();
      if (queueRunnerData.isEnded) {
        cj('#crm-queue-runner-skip').button('disable');
      }
      cj('#crm-queue-runner-title').text('Error: ' + data.last_task_title);
    } else if (!data.is_continue && queueRunnerData.numberOfItems == 0) {
      cj('#crm-queue-runner-title').text('Done');
    } else {
      cj('#crm-queue-runner-title').text('Executed: ' + data.last_task_title);
    }
    
    if (data.exception) {
      cj('#crm-queue-runner-message').html('');
      cj('<div></div>').html(data.exception).prependTo('#crm-queue-runner-message');
    }
    
  };
  
  var handleError = function(jqXHR, textStatus, errorThrown) {
    // Do this regardless of whether the response was well-formed
    cj("#crm-queue-runner-buttonset").show();
    
    var data = cj.parseJSON(jqXHR.responseText)
    if (data) {
      displayResponseData(data);
    }
  };
  
  var handleSuccess = function(data, textStatus, jqXHR) {
    if (!data.is_error) {
      queueRunnerData.completed++;
    }
    if (data.numberOfItems) {
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
    cj.ajax({
      type: 'POST',
      url: (queueRunnerData.isEnded ? queueRunnerData.onEndAjax : queueRunnerData.runNextAjax),
      data: {
        qrid: queueRunnerData.qrid
      },
      dataType: 'json',
      beforeSend: function(jqXHR, settings) {
          cj("#crm-queue-runner-buttonset").hide();
      },
      error: handleError,
      success: handleSuccess
    });
  }
  
  var retryNext = function() {
    cj('#crm-queue-runner-message').html('');
    runNext();
  }
  
  // Dequeue and the next item, then move on to runNext for the subsequent items
  var skipNext = function() {
    cj.ajax({
      type: 'POST',
      url: queueRunnerData.skipNextAjax,
      data: {
        qrid: queueRunnerData.qrid
      },
      dataType: 'json',
      beforeSend: function(jqXHR, settings) {
        cj('#crm-queue-runner-message').html('');
        cj("#crm-queue-runner-buttonset").hide();
      },
      error: handleError,
      success: handleSuccess
    });
  }
  
  // Set up the UI
  
  cj("#crm-queue-runner-progress").progressbar({ value: 0 });
  if (queueRunnerData.buttons.retry == 1) {
  cj("#crm-queue-runner-retry").button({
    text: false,
    icons: {primary: 'ui-icon-refresh'}
  }).click(retryNext);
  } else {
    cj("#crm-queue-runner-retry").remove();
  }
  if (queueRunnerData.buttons.skip == 1) {
  cj("#crm-queue-runner-skip").button({
    text: false,
    icons: {primary: 'ui-icon-seek-next'}
  }).click(skipNext);
  } else {
    cj("#crm-queue-runner-skip").remove();
  }
  cj("#crm-queue-runner-buttonset").buttonset();
  cj("#crm-queue-runner-buttonset").hide();
  window.setTimeout(runNext, 50);
});

</script>
{/literal}