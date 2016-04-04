<?php
require_once '../../../wp-blog-header.php';
require_once './tpaga.php';

$data = json_decode(file_get_contents('php://input'), true);

$recievedSignature = $data['signature'];
$merchantToken = $data['merchant_token'];
$purchaseOrderId = $data['purchase_order_id'];
$purchaseId = $data['purchase_id'];
$purchaseAmount = $data['purchase_amount'];
$paymentState = $data['purchase_state'];
$responseMessage = $data['payment_message'];
$paymentMethod = $data['payment_method'];
$currency = $data['purchase_currency'];
$description = $data['purchase_description'];
$installments = $data['installments'];

$tpaga = new Tpaga;

$merchantSecret = $tpaga->find_merchant_secret();

$message = $merchantToken . $purchaseAmount . $purchaseOrderId . $purchaseId . $paymentState . $merchantSecret;
$signature = hash('sha256', $message);

if ($recievedSignature == $signature) {
  $order = new WC_Order($purchaseOrderId);
  // Test the code to know if the transaction went through or not.
  if ($paymentState == 'paid'){
    $order->add_order_note( __( 'Pago satisfactorio por medio de.', 'tpaga' ) );
    $order->payment_complete();
    echo $responseMessage;
  } else{
    wc_add_notice( $responseMessage, 'error');
    $order->add_order_note('Error mientras se procesaba el pago, con estado: Payment ' . $responseMessage);
    $order->update_status('failed', __('Transaccion fallida. ', 'woothemes'));
    echo $responseMessage;
  }
}
