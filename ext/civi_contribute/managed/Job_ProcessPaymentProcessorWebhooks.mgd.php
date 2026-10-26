<?php

return [
  [
    'name' => 'Process PaymentProcessor Webhooks',
    'entity' => 'Job',
    'cleanup' => 'always',
    'update' => 'unmodified',
    // Sites running the mjwshared extension already have this job, under the
    // same name - adopt its existing civicrm_job row (and the data/config an
    // admin may have customized on it) rather than creating a second one.
    // mjwshared's own job keeps running unmodified for as long as it's
    // installed; this declaration takes over the row via the reconciler's
    // module-migration handling, repointing api_entity/api_action here.
    'replaces' => ['module' => 'mjwshared'],
    'params' => [
      'version' => 4,
      'values' => [
        'name' => 'Process PaymentProcessor Webhooks',
        'description' => ts('Process incomplete payment processor webhooks'),
        'run_frequency' => 'Always',
        'api_entity' => 'PaymentprocessorWebhook',
        'api_action' => 'process',
        'parameters' => "version=4\ncheckPermissions=0\ndeleteOld=-3 month",
      ],
      'match' => [
        'name',
      ],
    ],
  ],
];
