<?php

namespace Civi\OAuth;

trait CiviConnectUrlTrait {

  /**
   * @var string|null
   */
  protected $urlCiviConnect;

  public function getCiviConnectUrl(): ?string {
    return $this->urlCiviConnect;
  }

  public function setCiviConnectUrl(?string $urlCiviConnect): void {
    $this->urlCiviConnect = $urlCiviConnect;
  }

}
