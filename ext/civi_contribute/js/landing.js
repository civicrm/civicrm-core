(function (api4) {

  document.addEventListener('DOMContentLoaded', () => {
    // note: token may be updated on subsequent requests
    let recheckCount = 0;

    const setMessage = (message, showLoader) => {
      document.querySelector('#checkoutLanding .crm-checkout-message').innerText = message;
      document.querySelector('#checkoutLanding .crm-loading-spinner').style.display = showLoader ? null : 'none';
    };

    const recheckStatus = (token) => CRM.api4('Contribution', 'continueCheckout', {
      token: token
    })
    .then((response) => handleResponse(response));

    const handleResponse = (response) => {
      if (!response.status || !response.message) {
        setMessage(ts('Error checking payment status'), false);
      }

      if (response.redirect) {
        window.location = response.redirect;
        return;
      }

      if (response.status === 'pending') {
        if (recheckCount > 3) {
          setMessage(ts('Unable to get status'), false);
        }
        else {
          setMessage(response.message, true);
          recheckCount += 1;
          setTimeout(() => recheckStatus(response.token), 3000);
        }
      }
      else {
        // otherwise, hide loader
        setMessage(response.message, false);
      }
    };

    handleResponse(CRM.vars.checkout.landingPageInitialCheck);
  });
})(CRM.api4);