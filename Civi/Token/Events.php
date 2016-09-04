<?php

namespace Civi\Token;

class Events {
  /**
   * Create a list of supported tokens.
   *
   * @see \Civi\Token\Event\TokenRegisterEvent
   */
  const TOKEN_REGISTER = 'civi.token.list';

  /**
   * Create a list of supported tokens.
   *
   * @see \Civi\Token\Event\TokenValueEvent
   */
  const TOKEN_EVALUATE = 'civi.token.eval';

  /**
   * Perform post-processing on a rendered message.
   *
   * WARNING: It is difficult to develop robust,
   * secure code using this stage. However, we need
   * to support it during a transitional period
   * while the token logic is reorganized.
   *
   * @see \Civi\Token\Event\TokenRenderEvent
   */
  const TOKEN_RENDER = 'civi.token.render';

}
