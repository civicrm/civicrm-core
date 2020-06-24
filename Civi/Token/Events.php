<?php

namespace Civi\Token;

class Events {
  /**
   * @see \Civi\Token\Event\TokenRegisterEvent
   * @deprecated - You may simply use the event name directly. dev/core#1744
   */
  const TOKEN_REGISTER = 'civi.token.list';

  /**
   * @see \Civi\Token\Event\TokenValueEvent
   * @deprecated - You may simply use the event name directly. dev/core#1744
   */
  const TOKEN_EVALUATE = 'civi.token.eval';

  /**
   * @see \Civi\Token\Event\TokenRenderEvent
   * @deprecated - You may simply use the event name directly. dev/core#1744
   */
  const TOKEN_RENDER = 'civi.token.render';

}
