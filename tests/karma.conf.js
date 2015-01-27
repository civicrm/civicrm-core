module.exports = function(config) {
  config.set({
    autoWatch: true,
    basePath: '..',
    browsers: ['Chrome'],
    exclude: [
    ],
    files: [
      'bower_components/jquery/dist/jquery.min.js',
      'bower_components/jquery-ui/jquery-ui.min.js',
      'packages/backbone/lodash.compat.min.js',
      'packages/jquery/plugins/select2/select2.min.js',
      'packages/jquery/plugins/jquery.blockUI.js',
      'packages/jquery/plugins/jquery.validate.js',
      'js/Common.js',
      'bower_components/angular/angular.js',
      'bower_components/angular-file-upload/angular-file-upload.js',
      'bower_components/angular-jquery-dialog-service/dialog-service.js',
      'bower_components/angular-route/angular-route.js',
      'bower_components/angular-mocks/angular-mocks.js',
      'bower_components/angular-ui-sortable/sortable.js',
      'bower_components/angular-ui-utils/ui-utils.js',
      'bower_components/angular-unsavedChanges/dist/unsavedChanges.js',
      'tests/karma/modules.js',
      'js/crm.ajax.js',
      'js/angular-*.js',
      'tests/karma/lib/*.js',
      'tests/karma/**/*.js',
    ],
    frameworks: ['jasmine'],
    logLevel: config.LOG_INFO,
    port: 9876,
    reporters: ['progress'],
    junitReporter: {
      outputFile: 'tests/output/karma.xml',
      suite: ''
    },
    singleRun: false
  });
};
