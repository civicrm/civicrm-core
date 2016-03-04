<?php

/*
 * This file is part of the civicrm-cxn-rpc package.
 *
 * Copyright (c) CiviCRM LLC <info@civicrm.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this package.
 */

namespace Civi\Cxn\Rpc;

use Civi\Cxn\Rpc\Exception\InvalidCertException;

/**
 * Interface CertificateValidatorInterface
 * @package Civi\Cxn\Rpc
 *
 * A certificate validator determines whether a certificate is fully valid -- for whatever
 * "valid" means in the system policy (eg signed by a trusted CA, not expired, not revoked).
 */
interface CertificateValidatorInterface {

  /**
   * Determine whether an X.509 certificate is currently valid.
   *
   * @param string $certPem
   *   PEM-encoded certificate.
   * @throws InvalidCertException
   *   Invalid certificates are reported as exceptions.
   */
  public function validateCert($certPem);

}
