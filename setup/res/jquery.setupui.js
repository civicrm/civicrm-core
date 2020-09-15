/**
 * This is a jQuery plugin which adds some helpers for the setup UI.
 */
(function($){
  /**
   * Enable or disable an error message.
   *
   * <p id="my-error-message">The world is one fire.</p>
   * Ex: $('#my-error-message').toggleError(false)
   *
   * @param bool isError
   */
  $.fn.toggleError = function (isError) {
    this.toggleClass('install-validate-ok', !isError)
      .toggleClass('install-validate-bad', isError)
      .toggleClass('error', isError);

    var errors = $('.install-validate-bad');
    $('#install_button').prop('disabled', errors.length > 0);
    return this;
  };

  /**
   * Ex: $('.watch-these').useValidator(function(){
   *   $('#some-error-message').toggleError(booleanExpression);
   * })
   * @param cb
   */
  $.fn.useValidator = function(cb) {
    cb();
    this.on('change', cb);
    return this;
  };
})(jQuery);