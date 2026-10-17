(function () {

  const appendWrappedControl = (container) => {
    const wrapper = document.createElement('li');
    wrapper.className = 'civi-riverlea-user-controls';
    container.append(wrapper);

    const controls = [
      'civi-riverlea-font-size-control',
      'civi-riverlea-dark-mode-control',
    ];

    // if and when the control is defined, add an instance to the menu
    // (the definitions will be loaded based on admin setting)
    controls.forEach((control) => {
      customElements.whenDefined(control).then(() => wrapper.append(document.createElement(control)));
    });
  };

  document.addEventListener('DOMContentLoaded', () => {
    const menu = document.getElementById('civicrm-menu');
    if (menu) {
      appendWrappedControl(menu);
      return;
    }

    const observer = new MutationObserver(() => {
      const menu = document.getElementById('civicrm-menu');
      if (menu) {
        appendWrappedControl(menu);

        observer.disconnect();
      }
    });
    observer.observe(document.body, {childList: true, subtree: true});
  });

})();
