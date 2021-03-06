<?php
/*
Plugin Name: Tpaga Web Checkout para WooCommerce
Plugin URI: http://www.tpaga.co/
Description: Agrega Tpaga WebCheckout como metodo de pago.
Version: 1.02
Author: Tpaga
Author URI: http://www.tpaga.co/
GitHub Plugin URI: https://github.com/Tpaga/tpaga-woocomerce-plugin
License: Apache License 2.0.  License URI: http://www.apache.org/licenses/LICENSE-2.0 */

add_action('plugins_loaded', 'woocommerce_tpaga_gateway', 0);

// Hook in. Adds a custom field to the Checkout form
add_filter( 'woocommerce_checkout_fields' , 'custom_override_checkout_fields' );
/* Display field value on the order edit page */
add_action( 'woocommerce_admin_order_data_after_billing_address', 'my_custom_checkout_field_display_admin_order_meta', 10, 1 );
/* Update the order meta with field value  */
add_action( 'woocommerce_checkout_update_order_meta', 'my_custom_checkout_field_update_order_meta' );


// Our hooked in function - $fields is passed via the filter
function custom_override_checkout_fields( $fields ) {
     $fields['billing']['billing_customer_dni'] = array(
        'label'     => __('Documento de identidad', 'woocommerce'),
        'placeholder'   => _x('Documento de identidad', 'placeholder', 'woocommerce'),
        'required'  => true,
        'class'     => array('form-row-wide'),
        'clear'     => true,
        'type' => 'number'
     );

     return $fields;
}


function my_custom_checkout_field_display_admin_order_meta($order){
    echo '<p><strong>'.__('Documento de identidad').':</strong> ' . get_post_meta( $order->id, '_billing_customer_dni', true ) . '</p>';
}


function my_custom_checkout_field_update_order_meta( $order_id ) {
    if ( ! empty( $_POST['customer_dni'] ) ) {
        update_post_meta( $order_id, '_customer_dni', sanitize_text_field( $_POST['customer_dni'] ) );
    }
}


function woocommerce_tpaga_gateway() {
  // Verifica que woocomerce este instalado
  if(!class_exists('WC_Payment_Gateway')) return;

  // Clase Tpaga que hereda funcionalidad de Woocommerce
  class Tpaga extends WC_Payment_Gateway {

    // Configuracion del botón de pago
    public function __construct(){

      // ID global para este metodo de pago
      $this->id = 'tpaga';

      // Icono que será mostrado al momento de escoger medio de pagos
      $this->icon = apply_filters('woocomerce_tpaga_icon', plugins_url('/img/tpaga-logo.png', __FILE__));

      // Bool. Puede ser configurada con true si se esta haciendo una integración directa.
      // este no es nuestro caso, ya el proceso se terminará por medio de un tercero $this->has_fields     = false;
      $this->method_title     = 'Tpaga';
      $this->method_description = 'Integración de Woocommerce con Tpaga Web checkout';

      // Define la configuracion que luego serán cargadas con init_settings()
      $this->init_form_fields();

      // Luego que init_settings() es llamado, es posible guardar la configuracion en variables
      // e.j: $this->get_option( 'title' );
      $this->init_settings();

      // Convertimos settings en variables que podemos utilizar
      $this->title = $this->get_option( 'title' );
      $this->merchant_token = $this->get_option( 'merchant_token' );
      $this->environment_url = '';
      $this->merchant_secret = $this->get_option( 'merchant_secret' );
      $this->test = $this->get_option( 'test' );
      $this->autocomplete = $this->get_option( 'autocomplete' );

      // Guarda las opciones administrativas de acuerdo a la version de WC
      // 'process_admin_options' es un metodo de WC
      if (version_compare(WOOCOMMERCE_VERSION, '2.0.0', '>=' )) {
        add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( &$this, 'process_admin_options' ) );
      } else {
        add_action( 'woocommerce_update_options_payment_gateways', array( &$this, 'process_admin_options' ) );
      }

      add_action( 'woocommerce_receipt_tpaga', array(&$this, 'receipt_page') );
    }

    // Formulario de configuración Tpaga WebCheckout
    function init_form_fields() {
      $this->form_fields = array(
          'enabled' => array(
          'title' => __('Habilitar/Deshabilitar', 'tpaga'),
          'type' => 'checkbox',
          'label' => __('Habilitar pago por medio de Tpaga Web Checkout', 'tpaga'),
          'default' => 'no'
        ),
        'title' => array(
          'title' => __('Título', 'tpaga'),
          'type'=> 'text',
          'description' => __('Título que el usuario verá durante checkout.', 'tpaga'),
          'default' => __('Tpaga WebCheckout', 'tpaga')
        ),
        'merchant_token' => array(
          'title' => __('Merchant Token', 'tpaga'),
          'type' => 'text',
          'description' => __('Identificador único de merchant en Tpaga.', 'tpaga')
        ),
        'merchant_secret' => array(
          'title' => __('Secreto', 'tpaga'),
          'type' => 'text',
          'description' => __('Llave que sirve para encriptar la comunicación con Tpaga WebCheckout.', 'tpaga')
        ),
        'test' => array(
          'title' => __('Transacciones en modo de prueba', 'tpaga'),
          'type' => 'checkbox',
          'label' => __('Habilita las transacciones en modo de prueba.', 'tpaga'),
          'default' => 'no'
        ),
        'autocomplete' => array(
          'title' => __('Autocompletar órdenes', 'tpaga'),
          'type' => 'checkbox',
          'label' => __('Habilita el autocompletado de la orden luego de recibir el pago.', 'tpaga'),
          'default' => 'no'
        )
      );
    }

    // Crea el formulario de administrador
    public function admin_options() {
      echo '<h3>'.__('Tpaga Web Checkout', 'tpaga').'</h3>';
      echo '<table class="form-table">';
      $this -> generate_settings_html();
      echo '</table>';
    }

    // Muestra el botón de pago, que luego redirecciona a TW
    function receipt_page( $order ){
      echo '<p>'.__('Gracias por su pedido, Hagá click en PAGAR para proceder con el pago.', 'tpaga').'</p>';
      echo $this -> generate_tpaga_form($order);
    }

    // Configura los datos que serán luego renderizados como un formulario
    public function get_params_post( $orderId ){
      $order = new WC_Order( $orderId );

      $currency = get_woocommerce_currency();
      $amount = number_format( ($order -> get_total() ), 1, '.', '');

      // Calcula la firma digital
      $signature = hash('sha256', $this -> merchant_token . $amount . $order -> id . $this -> merchant_secret);
      $description = "";

      // Obtiene los items del carrito de compras
      $products = $order->get_items();
      foreach($products as $product) {
        $description .= $product['name'] . ',';
      }

      $tax = number_format(($order -> get_total_tax()),2,'.','');
      $taxReturnBase = number_format(($amount - $tax),2,'.','');
      if ($tax == 0) $taxReturnBase = 0;

      // Campos que convertirán los datos del botón de pago
      $parametersArgs = array(
        'merchant_token' => $this->merchant_token,
        'purchase_order_id' => $order -> id,
        'purchase_description' => trim($description, ','),
        'purchase_amount' => $amount,
        'purchase_tax' => $tax,
        'purchase_signature' => $signature,
        'purchase_currency' => $currency,
        'customer_firstname' => $order->billing_first_name,
        'customer_lastname' => $order->billing_last_name,
        'customer_phone' => $order->billing_phone,
        'customer_email' => $order->billing_email,
        'address_street' => $order->billing_address_1,
        'address_city' => $order->billing_city,
        'address_country' => $order->shipping_country,
        'address_state' => $order->billing_state
      );

      return $parametersArgs;
    }

    // Genera el formulario "Botón" de pago
    public function generate_tpaga_form( $orderId ){
      $parametersArgs = $this->get_params_post( $orderId );

      $tpagaArgs = array();
      foreach( $parametersArgs as $key => $value ){
        $tpagaArgs[] = $key . '=' . $value;
      }

      $tpagaArgs = array();
      foreach( $parametersArgs as $key => $value ){
        $tpagaArgs[] = "<input type='hidden' name='purchase[$key]' value='$value'/>";
      }

      // Url de Tpaga dependiendo del ambiente en el que nos encontremos
      $environment = ( $this->test == "yes" ) ? 'TRUE' : 'FALSE';
      $this->environment_url = ( "FALSE" == $environment )
                           ? 'https://webcheckout.tpaga.co/checkout'
                           : 'https://staging.webcheckout.tpaga.co/checkout';

      // Escribimos en el navegador nuestro botón
      return '<form action="'.$this->environment_url.'" method="post" id="tpaga_form">' . implode('', $tpagaArgs)
        . '<input type="submit" id="submit_tpaga" value="' .__('Pagar', 'tpaga').'" /></form>';
    }

    // Procesa el pago e informa a WC del mismo.
    function process_payment( $orderId ) {
      global $woocommerce;
      $order = new WC_Order( $orderId );

      // Paso importantisímo ya que vacia el carrito
      $woocommerce->cart->empty_cart();

      // Informa a WC que el proceso de pago inicio
      if (version_compare(WOOCOMMERCE_VERSION, '2.0.19', '<=' )) {
        return array('result' => 'success',
                     'redirect' => add_query_arg('order',
                     $order->id,
                     add_query_arg( 'key', $order->order_key, get_permalink( get_option('woocommerce_pay_page_id'))))
                    );
      } else {

        $parametersArgs = $this->get_params_post( $orderId );

        $tpagaArgs = array();
        foreach($parametersArgs as $key => $value){
          $tpagaArgs[] = $key . '=' . $value;
        }

        return array(
          'result' => 'success',
          'redirect' =>  $order->get_checkout_payment_url( true )
        );
      }
    }

    // Retorna el secreto del vendedor actual
    function find_merchant_secret() {
      return $this->settings['merchant_secret'];
    }

    // Autocompleta la orden si el admin seleccionó dicha opción
    function autocomplete($order) {
      if( $this->autocomplete == 'yes' ){
        $order->update_status('completed');
      }
    }
  }

  // Notifica a WC la integración con tpaga
  function add_tpaga( $methods ) {
    $methods[] = 'Tpaga';
    return $methods;
  }
  add_filter('woocommerce_payment_gateways', 'add_tpaga' );
}
