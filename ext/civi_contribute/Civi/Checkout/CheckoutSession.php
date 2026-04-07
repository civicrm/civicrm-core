<?php

namespace Civi\Checkout;

use Civi\Api4\Contribution;
use Civi\Api4\Payment;

use CRM_Contribute_ExtensionUtil as E;

/**
 * CheckoutSession represents state of a user going through checkout.
 *
 * It mainly functions to store ephemeral data like:
 * - payment processor session IDs
 * - onward urls - where user should be sent e.g. for success/failure/cancel
 */
class CheckoutSession {

  public const STATUS_SUCCESS = 'success';
  public const STATUS_PENDING = 'pending';
  public const STATUS_CANCEL = 'cancel';
  public const STATUS_FAIL = 'fail';

  protected int $contributionId;

  protected string $checkoutOption;

  /**
   * @var string[]
   * Store for urls that may be used for onward journey. Expect them
   * to be keyed by STATUS
   */
  protected array $urls = [];

  /**
   * @var string[]
   * Store for messages that may be used for onward journey. Expect them
   * to be keyed by STATUS
   */
  protected array $messages = [];

  /**
   * @var string
   * Title for the checkout session - may be shown on landing pages
   */
  protected string $title;

  /**
   * @var string
   * Current status of the session. Expect to be one
   * of the STATUS_ constants above
   */
  protected string $status = self::STATUS_PENDING;

  /**
   * @var mixed[]
   * Other parameters required by the Payment Processor
   * Might include things like payment_intent_id or
   * card details (in PCI compliant context!)
   */
  protected array $checkoutParams = [];

  /**
   * @var bool
   * Are we in test mode?
   */
  protected bool $testMode;

  /**
   * @var float
   * Total amount
   */
  protected float $totalAmount;

  /**
   * @var float
   * Fee amount
   * @see createPayment
   */
  protected float $feeAmount = 0;

  /**
   * The trxn_id to record on the financial_trxn (payment) record
   *
   * @var string
   */
  protected string $transactionId = '';

  /**
   * The order_reference to record on the financial_trxn (payment) record
   *
   * @var string
   */
  protected string $orderReference = '';

  /**
   * @var array
   *
   * Keys that will be set on the Afform response.
   *
   * Keys should be strings, values may be strings or JSON serializable
   * objects (like array of strings)
   *
   * This could be a standard key supported by the afForm
   * controller (like `redirect`) or a custom key
   * that is used by the Payment Processor on the client
   * side (like `checkout_session_id` in Stripe Embedded Checkout)
   */
  protected array $responseItems = [];

  /**
   * @param int $contributionId
   * @param string $checkoutOption
   *
   * @throws \CRM_Core_Exception
   */
  public function __construct(int $contributionId, string $checkoutOption) {
    $this->contributionId = $contributionId;
    $this->checkoutOption = $checkoutOption;

    // fetch values from the contribution that should not change during the session
    // note: do *not* fetch things like status upfront -- that may change
    $contribution = \Civi\Api4\Contribution::get(FALSE)
      ->addWhere('id', '=', $this->contributionId)
      ->addSelect('is_test', 'total_amount')
      ->execute()
      ->single();

    $this->totalAmount = $contribution['total_amount'];
    $this->testMode = $contribution['is_test'];

    // set default messages
    // NOTE: we use "payment" instead of checkout
    // as checkout is weird terminology in a donation
    // context
    $this->title = E::ts('CiviCRM payment');

    $this->messages = [
      self::STATUS_CANCEL => E::ts('Payment cancelled.'),
      self::STATUS_FAIL => E::ts('Payment failed'),
      self::STATUS_SUCCESS => E::ts('Payment complete'),
      self::STATUS_PENDING => E::ts('Confirming payment status'),
    ];
  }

  public function getContributionId(): int {
    return $this->contributionId;
  }

  public function getTotalAmount(): float {
    return $this->totalAmount;
  }

  public function isTestMode(): bool {
    return $this->testMode;
  }

  protected function getCheckoutOption(): CheckoutOptionInterface {
    return \Civi::service('civi.checkout')->getOption($this->checkoutOption);
  }

  /**
   * @param array $params
   *
   * @return $this
   */
  public function setCheckoutParams(array $params): CheckoutSession {
    foreach ($params as $key => $value) {
      $this->setCheckoutParam($key, $value);
    }
    return $this;
  }

  /**
   * @param string $key
   * @param mixed $value
   *
   * @return $this
   */
  public function setCheckoutParam(string $key, $value): CheckoutSession {
    $this->checkoutParams[$key] = $value;
    return $this;
  }

  /**
   * @return mixed[]
   */
  public function getCheckoutParams(): array {
    return $this->checkoutParams;
  }

  /**
   * @param string $key
   *
   * @return mixed|null
   */
  public function getCheckoutParam(string $key) {
    return $this->checkoutParams[$key] ?? NULL;
  }

  /**
   * @param string $status
   *
   * @return $this
   */
  public function setStatus(string $status): CheckoutSession {
    $this->status = $status;
    return $this;
  }

  /**
   * @return string
   */
  public function getStatus(): string {
    return $this->status;
  }

  /**
   * @param string $url
   *
   * @return $this
   */
  public function setSuccessUrl(string $url): CheckoutSession {
    $this->urls['success'] = $url;
    return $this;
  }

  /**
   * @param string $message
   *
   * @return $this
   */
  public function setSuccessMessage(string $message): CheckoutSession {
    $this->messages['success'] = $message;
    return $this;
  }

  /**
   * @return string
   */
  public function getSuccessUrl(): string {
    return $this->urls['success'] ?? '';
  }

  /**
   * @param string $url
   *
   * @return $this
   */
  public function setFailUrl(string $url): CheckoutSession {
    $this->urls['fail'] = $url;
    return $this;
  }

  /**
   * @return string
   */
  public function getFailUrl(): string {
    return $this->urls['fail'] ?? $this->urls['cancel'] ?? '';
  }

  /**
   * @param string $url
   *
   * @return $this
   */
  public function setCancelUrl(string $url): CheckoutSession {
    $this->urls['cancel'] = $url;
    return $this;
  }

  /**
   * @return string
   */
  public function getCancelUrl(): string {
    return $this->urls['cancel'] ?? $this->urls['fail'] ?? '';
  }

  /**
   * @param string $url
   *
   * @return $this
   */
  public function setPendingUrl(string $url): CheckoutSession {
    $this->urls[self::STATUS_PENDING] = $url;
    return $this;
  }

  /**
   * @return string
   */
  public function getPendingUrl(): string {
    return $this->urls[self::STATUS_PENDING] ?? '';
  }

  /**
   * Get URL to take user to based on the current status
   */
  public function getNextUrl(): string {
    $status = $this->getStatus();

    return $this->urls[$status] ?? FALSE;
  }

  /**
   * Get status message to show to user
   */
  public function getStatusMessage(): string {
    $status = $this->getStatus();
    return $this->messages[$status] ?? E::ts('Unrecognised payment status: %1', [1 => $status]);
  }

  /**
   * Set title (for landing page)
   */
  public function setTitle(string $title): CheckoutSession {
    $this->title = $title;
    return $this;
  }

  /**
   * Get title (for landing page)
   */
  public function getTitle(): string {
    return $this->title;
  }

  /**
   * @return string
   */
  public function getLandingUrl(): string {
    return (string) \Civi::url('civicrm/checkout/continue')
      ->setQuery(['session' => $this->tokenise()])
      ->setPreferFormat('absolute')
      ->setScheme('current');
  }

  /**
   * @param string $key
   * @param mixed $value
   *
   * @return $this
   */
  public function setResponseItem(string $key, $value): CheckoutSession {
    $this->responseItems[$key] = $value;
    return $this;
  }

  /**
   * @return array
   */
  public function getResponseItems(): array {
    $responseItems = $this->responseItems;

    // if redirect / message aren't set explicitly, then provide
    // defaults to override any normal Afform redirect/message
    $redirect = $this->getNextUrl() ?? FALSE;
    $responseItems['redirect'] ??= $redirect;

    $message = $this->getStatusMessage() ?? FALSE;
    $responseItems['message'] ??= $message;

    return $responseItems;
  }

  /**
   * @return array
   */
  public function toArray(): array {
    return [
      'contribution_id' => $this->contributionId,
      'checkout_option' => $this->checkoutOption,
      'urls' => $this->urls,
      'messages' => $this->messages,
      'title' => $this->title,
      'checkout_params' => $this->getCheckoutParams(),
      'status' => $this->getStatus(),
    ];
  }

  /**
   * @return string
   */
  public function tokenise(): string {
    // 20 minutes
    $expires = \CRM_Utils_Time::time() + (20 * 60);
    $payload = [
      'session' => $this->toArray(),
      'exp' => $expires,
    ];
    return \Civi::service('crypto.jwt')->encode($payload, ['SIGN', 'WEAK_SIGN']);
  }

  /**
   * @param string $sessionToken
   *
   * @return \Civi\AfformPayment\CheckoutSession
   * @throws \CRM_Core_Exception
   */
  public static function restoreFromToken(string $sessionToken): CheckoutSession {
    $payload = \Civi::service('crypto.jwt')->decode($sessionToken, ['SIGN', 'WEAK_SIGN']);

    $data = $payload['session'];

    $session = new CheckoutSession($data->contribution_id, $data->checkout_option);
    $session->setCheckoutParams((array) $data->checkout_params);
    $session->urls = (array) $data->urls;
    $session->messages = (array) $data->messages;
    $session->title = $data->title;

    // TODO: we should probably recheck the session status after restoring from the token
    // because the token status may be out of date - for example if:
    // - user submits to continue a pending session, checkout is finalised server side
    // - user submits duplicate request with the same old token, according to which the
    // session is still pending
    $session->status = $data->status;

    return $session;
  }

  /**
   * @return $this
   */
  public function startCheckout(): CheckoutSession {
    $this->getCheckoutOption()->startCheckout($this);
    return $this;
  }

  /**
   * @return $this
   */
  public function continueCheckout(): CheckoutSession {
    $this->getCheckoutOption()->continueCheckout($this);
    return $this;
  }

  /**
   * @return $this
   * @throws \CRM_Core_Exception
   * @throws \Civi\API\Exception\UnauthorizedException
   */
  public function pending(): CheckoutSession {
    $this->status = self::STATUS_PENDING;
    Contribution::update(FALSE)
      ->addWhere('id', '=', $this->contributionId)
      ->addWhere('contribution_status_id:name', '!=', 'Pending')
      ->addValue('contribution_status_id:name', 'Pending')
      ->execute();
    return $this;
  }

  /**
   * @return $this
   * @throws \CRM_Core_Exception
   * @throws \Civi\API\Exception\UnauthorizedException
   */
  public function fail(): CheckoutSession {
    $this->status = self::STATUS_FAIL;
    Contribution::update(FALSE)
      ->addWhere('id', '=', $this->contributionId)
      ->addWhere('contribution_status_id:name', '!=', 'Failed')
      ->addValue('contribution_status_id:name', 'Failed')
      ->execute();
    return $this;
  }

  /**
   * @return $this
   * @throws \CRM_Core_Exception
   * @throws \Civi\API\Exception\UnauthorizedException
   */
  public function cancel(): CheckoutSession {
    $this->status = self::STATUS_CANCEL;
    Contribution::update(FALSE)
      ->addWhere('id', '=', $this->contributionId)
      ->addWhere('contribution_status_id:name', '!=', 'Cancelled')
      ->addValue('contribution_status_id:name', 'Cancelled')
      ->execute();
    return $this;
  }

  /**
   * @return $this
   */
  public function success(): CheckoutSession {
    $this->status = self::STATUS_SUCCESS;
    // TODO: the checkout is successful, but we are probably still
    // waiting for the payment to clear
    // ideally we would be able to update the Contribution record
    // to indicate that payment is expected but in transit
    return $this;
  }

  public function getFeeAmount(): float {
    return $this->feeAmount;
  }

  /**
   * @param float $feeAmount
   *
   * @return $this
   */
  public function setFeeAmount(float $feeAmount): CheckoutSession {
    $this->feeAmount = $feeAmount;
    return $this;
  }

  public function getTransactionId(): string {
    return $this->transactionId;
  }

  /**
   * @param string $transactionId
   *
   * @return $this
   */
  public function setTransactionId(string $transactionId): CheckoutSession {
    $this->transactionId = $transactionId;
    return $this;
  }

  public function getOrderReference(): string {
    return $this->orderReference;
  }

  /**
   * @param string $orderReference
   *
   * @return $this
   */
  public function setOrderReference(string $orderReference): CheckoutSession {
    $this->orderReference = $orderReference;
    return $this;
  }

  /**
   * @return int
   *   Returns the ID of the FinancialTrxn (Payment) that was created
   *
   * @throws \CRM_Core_Exception
   * @throws \Civi\API\Exception\UnauthorizedException
   */
  public function createPayment(): int {
    if (method_exists($this->getCheckoutOption(), 'createPayment')) {
      return $this->getCheckoutOption()->createPayment($this);
    }
    else {
      return Payment::create(FALSE)
        ->addValue('contribution_id', $this->getContributionId())
        ->addValue('total_amount', $this->totalAmount)
        ->addValue('fee_amount', $this->feeAmount)
        ->addValue('payment_processor_id', $this->getCheckoutOption()->getPaymentProcessorId())
        ->addValue('trxn_date', date('Ymd H:i:s'))
        ->addValue('trxn_id', $this->transactionId)
        ->addValue('order_reference', $this->orderReference)
        ->addValue('payment_instrument_id:name', $this->getCheckoutOption()->getPaymentMethod())
        ->execute()->first()['id'];
    }
  }

}
