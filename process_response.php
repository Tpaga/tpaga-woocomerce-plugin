<?php
require_once '../../../wp-blog-header.php';
require_once './tpaga.php';

$data = json_decode(file_get_contents('php://input'), true);
print_r($data);

$recievedSignature = $data['signature'];
$merchantToken = $data['merchant_token'];
$purchaseOrderId = $data['purchase_order_id'];
$purchaseId = $data['purchase_id'];
$purchaseAmount = $data['purchase_amount'];
$paymentState = $data['purchase_state'];
$responseMessage = $data['payment_message'];

$tpaga = new Tpaga;

$merchantSecret = $tpaga->find_merchant_secret();
// {merchant_token} {purchase_amount} {purchase_order_id} {id} {state} {merchant.secret}
$message = $merchantToken . $purchaseAmount . $purchaseOrderId . $purchaseId . $paymentState . $merchantSecret;
$signature = hash('sha256', $message);

if ($recievedSignature == $signature) {
  $order = new WC_Order($purchaseOrderId);

  if ($paymentState == 'paid' && $responseMessage == 'Successful payment process!'){
    $order->payment_complete();
  } else if($paymentState == 'failed' && $responseMessage == 'Failed payment process!'){
    $order->update_status('failed', __('Transaccion rechazada', 'woothemes'));
  } else{
    $order->update_status('failed', __('Transaccion fallida', 'woothemes'));
  }
}
?>