<?php
require_once '../../../wp-blog-header.php';
require_once './tpaga.php';

$data = json_decode(file_get_contents('php://input'), true);
print_r($data);

$recieved_signature = $data['signature'];
$merchantToken = $data['merchant_token'];
$purchaseOrderId = $data['purchase_order_id'];
$purchaseId = $dat['purchase_id'];
$purchaseAmount = $data['purchase_amount'];
$paymentState = $data['purchase_state'];

$tpaga = new Tpaga;

// TODO: Merchant secret?
$merchant_secret = $tpaga->find_merchant_secret();
// {merchant_token} {purchase_amount} {purchase_order_id} {id} {state} {merchant.secret}
$message = $merchantToken . $purchaseAmount . $purchaseOrderId . $purchaseId . $paymentState . $merchant_secret;
$signature = hash('sha256', $message);

echo $signature;

if(isset($_REQUEST['response_code_pol'])){
  $polResponseCode = $_REQUEST['response_code_pol'];
} else {
  $polResponseCode = $_REQUEST['codigo_respuesta_pol'];
}

if (strtoupper($recieved_signature) == strtoupper($signature)) {
  $order = new WC_Order($purchaseOrderId);

  if($paymentState == 6 && $polResponseCode == 5){
    $order->update_status('failed', __('Transaccion fallida', 'woothemes'));
  } else if($paymentState == 6 && $polResponseCode == 4){
    $order->update_status('refunded', __('Transaccion rechazada', 'woothemes'));
  } else if($paymentState == 12 && $polResponseCode == 9994){
    $order->update_status('pending', __('Transaccion pendiente', 'woothemes'));
  } else if($paymentState == 4 && $polResponseCode == 1){
    $order->payment_complete();
  } else{
    $order->update_status('failed', __('Transaccion fallida', 'woothemes'));
  }
}
?>