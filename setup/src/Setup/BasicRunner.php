<?php
namespace Civi\Setup;

class BasicRunner {

  /**
   * Execute the controller and display the output.
   *
   * Note: This is really just an example which handles input and output using
   * stock PHP variables and functions. Depending on the environment,
   * it may be easier to work directly with `getCtrl()->run(...)` which
   * handles inputs/outputs in a more abstract fashion.
   *
   * @param \Civi\Setup\UI\SetupController $ctrl
   *    A web controller.
   */
  public static function run($ctrl) {
    $method = $_SERVER['REQUEST_METHOD'];

    /** @var \Civi\Setup\UI\SetupResponse $response */
    $response = $ctrl->run($method, ($method === 'GET' ? $_GET : $_POST));

    self::send($ctrl, $response);
  }

  /**
   * @param \Civi\Setup\UI\SetupController $ctrl
   * @param \Civi\Setup\UI\SetupResponse $response
   */
  public static function send($ctrl, $response) {
    http_response_code($response->code);
    foreach ($response->headers as $k => $v) {
      header("$k: $v");
    }

    /** @var \Civi\Setup\Model $model */
    $model = \Civi\Setup::instance()->getModel();

    if ($response->isComplete) {
      echo $response->body;
    }
    else {
      $pageVars = [
        'pageAssets' => $response->assets,
        'pageTitle' => $response->title,
        'pageBody' => $response->body,
        'shortLangCode' => \CRM_Core_I18n_PseudoConstant::shortForLong($model->lang),
        'textDirection' => (\CRM_Core_I18n::isLanguageRTL($model->lang) ? 'rtl' : 'ltr'),
      ];

      echo $ctrl->render($ctrl->getResourcePath('page.tpl.php'), $pageVars);
    }
  }

}
