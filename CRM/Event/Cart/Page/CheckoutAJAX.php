<?php
class CRM_Event_Cart_Page_CheckoutAJAX {
  function add_participant_to_cart() {
    require 'CRM/Core/Transaction.php';
    $transaction = new CRM_Core_Transaction();
    $cart_id     = $_GET['cart_id'];
    $event_id    = $_GET['event_id'];

    $cart = CRM_Event_Cart_BAO_Cart::find_by_id($_GET['cart_id']);

    //XXX security
    $participant = CRM_Event_Cart_BAO_MerParticipant::create(array(
        'cart_id' => $cart->id,
        'contact_id' => CRM_Event_Cart_Form_Cart::find_or_create_contact(),
        'event_id' => $event_id,
      ));
    $participant->save();

    $form = new CRM_Core_Form();
    $pform = new CRM_Event_Cart_Form_MerParticipant($participant);
    $pform->appendQuickForm($form);

    $renderer = $form->getRenderer();

    $config = CRM_Core_Config::singleton();
    $templateDir = $config->templateDir;
    if (is_array($templateDir)) {
      $templateDir = array_pop($templateDir);
    }
    $requiredTemplate = file_get_contents($templateDir . '/CRM/Form/label.tpl');
    $renderer->setRequiredTemplate($requiredTemplate);

    $form->accept($renderer);
    $template = CRM_Core_Smarty::singleton();
    $template->assign('form', $renderer->toArray());
    $template->assign('participant', $participant);
    $output = $template->fetch("CRM/Event/Cart/Form/Checkout/Participant.tpl");
    $transaction->commit();
    echo $output;
    CRM_Utils_System::civiExit();
  }

  function remove_participant_from_cart() {
    $participant = CRM_Event_Cart_BAO_MerParticipant::get_by_id($_GET['id']);
    $participant->delete();

    CRM_Utils_System::civiExit();
  }
}

