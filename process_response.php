<?php
require_once '../../../wp-blog-header.php';
require_once './tpaga.php';
get_header('shop');

global $woocommerce;

$data = json_decode(file_get_contents('php://input'), true);

$response_body = wp_remote_retrieve_body( $data );

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
// merchant_token purchase_amount purchase_order_id id state merchant.secret
$message = $merchantToken . $purchaseAmount . $purchaseOrderId . $purchaseId . $paymentState . $merchantSecret;
$signature = hash('sha256', $message);

if ($recievedSignature == $signature) {
  $order = new WC_Order($purchaseOrderId);
  // Test the code to know if the transaction went through or not.
  if ($paymentState == 'paid' && $responseMessage == 'Successful payment process!'){
    $order->add_order_note( __( 'Tpaga payment completed.', 'tpaga' ) );
    $order->payment_complete();
  } else{
    wc_add_notice( $responseMessage, 'error');
    $order->add_order_note('Error ' . $responseMessage);
  }
}

if ($recievedSignature == $signature) {
?>
  <center>
    <table style="width: 42%; margin-top: 100px;">
      <tr align="center">
        <th colspan="2">DATOS DE LA COMPRA</th>
      </tr>
      <tr align="right">
        <td>Estado de la transacci&oacute;n</td>
        <td><?php echo $paymentState; ?></td>
      </tr>
      <tr align="right">
        <td>ID de la transacci&oacute;n</td>
        <td><?php echo $purchaseOrderId; ?></td>
      </tr>   
      <tr align="right">
        <td>Referencia de la venta</td>
        <td><?php echo $purchaseId; ?></td>
      </tr>   
      <tr align="right">
        <td>Referencia de la transacci&oacute;n</td>
        <td><?php echo $purchaseOrderId; ?></td>
      </tr> 
      <tr align="right">
        <td>Valor total</td>
        <td>$<?php echo $purchaseAmount; ?> </td>
      </tr>
      <tr align="right">
        <td>Moneda</td>
        <td><?php echo $currency; ?></td>
      </tr>
      <tr align="right">
        <td>Descripción</td>
        <td><?php echo $description; ?></td>
      </tr>
      <tr align="right">
        <td>Entidad</td>
        <td><?php echo $paymentMethod; ?></td>
      </tr>
      <tr align="right">
        <td>Entidad</td>
        <td><?php echo $installments; ?></td>
      </tr>
    </table>
    <p/>
    <h1><?php echo "HOLA QUE HACE" ?></h1>
  </center>
<?php
} else {
  echo '<h1><center>La petici&oacute;n es incorrecta! Hay un error en la firma digital.</center></h1>';
}

get_footer('shop');
?>