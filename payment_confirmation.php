<?php
require_once '../../../wp-blog-header.php';
require_once './tpaga.php';
get_header('shop');

$recievedSignature = $_REQUEST['signature'];
$merchantToken = $_REQUEST['merchant_token'];
$purchaseOrderId =  $_REQUEST['purchase_order_id'];
$purchaseId =  $_REQUEST['purchase_id'];
$purchaseAmount =  $_REQUEST['purchase_amount'];
$paymentState =  $_REQUEST['purchase_state'];
$responseMessage =  $_REQUEST['payment_message'];
$paymentMethod =  $_REQUEST['payment_method'];
$currency =  $_REQUEST['purchase_currency'];
$description =  $_REQUEST['purchase_description'];
$installments =  $_REQUEST['installments'];

$tpaga = new Tpaga;

$merchantSecret = $tpaga->find_merchant_secret();
$message = $merchantToken . $purchaseAmount . $purchaseOrderId . $purchaseId . $paymentState . $merchantSecret;
$signature = hash('sha256', $message);

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
        <td>Descripción</td>/
        <td><?php echo $description; ?></td>
      </tr>
      <tr align="right">
        <td>Metodo de pago</td>
        <td><?php echo $paymentMethod; ?></td>
      </tr>
      <tr align="right">
        <td>N&uacute;mero de cuotas</td>
        <td><?php echo $installments; ?></td>
      </tr>
    </table>
    <p/>
    <h1><?php echo "Su pago ha sido procesado satisfactoriamente. Gracias por su compra." ?></h1>
  </center>
<?php
} else {
  echo '<h1><center>La petici&oacute;n es incorrecta! Hay un error en la firma digital.</center></h1>';
}

get_footer('shop');
?>