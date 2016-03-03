<?php
/*
Plugin Name: Tpaga - WooCommerce Gateway
Plugin URI: http://www.tpaga.co/
Description: Extends WooCommerce by Adding the Tpaga Web Checkout.
Version: 1.0
Author: Tpaga
Author URI: http://www.tpaga.co/
*/
add_action('plugins_loaded', 'woocommerce_tpaga_gateway', 0);
function woocommerce_tpaga_gateway() {
  if(!class_exists('WC_Payment_Gateway')) return;

  class Tpaga extends WC_Payment_Gateway {

    /**
     * Constructor de la pasarela de pago
     *
     * @access public
     * @return void
     */
    public function __construct(){
      $this->id         = 'tpaga';
      $this->icon         = apply_filters('woocomerce_tpagalatam_icon', plugins_url('/img/logotpaga.png', __FILE__));
      $this->has_fields     = false;
      $this->method_title     = 'Tpaga';
      $this->method_description = 'Integración de Woocommerce con Tpaga Web checkout';
      
      $this->init_form_fields();
      $this->init_settings();
      
      $this->title = $this->settings['title'];
      $this->merchant_token = $this->settings['merchant_token'];
      $this->public_api_key = $this->settings['public_api_key'];
      $this->gateway_url = $this->settings['gateway_url'];
      $this->merchant_secret = $this->settings['merchant_secret'];
      $this->test = $this->settings['test'];
      $this->response_page = $this->settings['response_page'];
      $this->confirmation_page = $this->settings['confirmation_page'];

      if (version_compare(WOOCOMMERCE_VERSION, '2.0.0', '>=' )) {
                add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( &$this, 'process_admin_options' ) );
             } else {
                add_action( 'woocommerce_update_options_payment_gateways', array( &$this, 'process_admin_options' ) );
            }
      add_action('woocommerce_receipt_tpaga', array(&$this, 'receipt_page'));
    }
    
    /**
     * Funcion que define los campos que iran en el formulario en la configuracion
     * de la pasarela de tpaga Latam
     *
     * @access public
     * @return void
     */
    function init_form_fields() {
      $this->form_fields = array(
        'enabled' => array(
                    'title' => __('Habilitar/Deshabilitar', 'tpaga'),
                    'type' => 'checkbox',
                    'label' => __('Habilitar pago por medio de Tpaga Web Checkout', 'tpaga'),
                    'default' => 'no'),
                'title' => array(
                    'title' => __('Título', 'tpaga'),
                    'type'=> 'text',
                    'description' => __('Título que el usuario verá durante checkout.', 'tpaga'),
                    'default' => __('Tpaga WebCheckout', 'tpaga')),
                'merchant_token' => array(
                    'title' => __('Merchant Token', 'tpaga'),
                    'type' => 'text',
                    'description' => __('Identificador único de merchant en Tpaga.', 'tpaga')),
                'public_api_key' => array(
                    'title' => __('Public API Key', 'tpaga'),
                    'type' => 'text',
                    'description' => __('Llave que sirve para encriptar la comunicación con tpaga Latam.', 'tpaga')),
                'merchant_secret' => array(
                    'title' => __('Secreto', 'tpaga'),
                    'type' => 'text',
                    'description' => __('Llave que sirve para encriptar la comunicación con tpaga Latam.', 'tpaga')),
                'gateway_url' => array(
                    'title' => __('Gateway URL', 'tpaga'),
                    'type' => 'text',
                    'description' => __('URL de la pasarela de pago tpaga Latam.', 'tpaga')),
        'test' => array(
                    'title' => __('Transacciones en modo de prueba', 'tpaga'),
                    'type' => 'checkbox',
                    'label' => __('Habilita las transacciones en modo de prueba.', 'tpaga'),
                    'default' => 'no'),
                'response_page' => array(
                    'title' => __('Página de respuesta'),
                    'type' => 'text',
                    'description' => __('URL de la página mostrada después de finalizar el pago. No olvide cambiar su dominio.', 'tpaga'),
          'default' => __('http://su.dominio.com/wp-content/plugins/woocommerce-tpaga-latam-2.0/response.php', 'tpaga')),
                'confirmation_page' => array(
                    'title' => __('Página de confirmación'),
                    'type' => 'text',
                    'description' => __('URL de la página que recibe la respuesta definitiva sobre los pagos. No olvide cambiar su dominio.', 'tpaga'),
          'default' => __('http://su.dominio.com/wp-content/plugins/woocommerce-tpaga-latam-2.0/confirmation.php', 'tpaga'))
      );
    }
    
    /**
    * Muestra el fomrulario en el admin con los campos de configuracion del gateway tpaga Latam
    */
    public function admin_options() {
      echo '<h3>'.__('Tpaga Web Checkout', 'tpaga').'</h3>';
      echo '<table class="form-table">';
      $this -> generate_settings_html();
      echo '</table>';
    }
    
    /**
     * Atiende el evento de checkout y genera la pagina con el formularion de pago.
     * Solo para la versiones anteriores a la 2.1.0 de WC
     *
     * @access public
     * @return void
     */
    function receipt_page($order){
      echo '<p>'.__('Gracias por su pedido, para continuar con el pago haga click el botón.', 'tpaga').'</p>';
      echo $this -> generate_tpaga_form($order);
    }
    
    /**
     * Construye un arreglo con todos los parametros que seran enviados al gateway de tpaga
     *
     * @access public
     * @return void
     */
    public function get_params_post( $order_id ){
      global $woocommerce;
      $order = new WC_Order( $order_id );
      $currency = get_woocommerce_currency();
      $amount = number_format(($order -> get_total()),2,'.','');
      $signature = hash('sha256', $this -> merchant_token . round($amount, 0) . $order -> id . $this -> merchant_secret);
      $description = "";
      $products = $order->get_items();
      foreach($products as $product) {
        $description .= $product['name'] . ',';
      }
      $tax = number_format(($order -> get_total_tax()),2,'.','');
      $taxReturnBase = number_format(($amount - $tax),2,'.','');
      if ($tax == 0) $taxReturnBase = 0;
      
      $test = 0;
      if($this->test == 'yes') $test = 1;
      
      $parameters_args = array(
        'merchant_token' => $this->merchant_token,
        'purchase_order_id' => $order -> id,
        'purchase_description' => trim($description, ','),
        'purchase_amount' => round($amount, 0),
        'purchase_tax' => $tax,
        'purchase_signature' => $signature,
        'purchase_currency' => $currency,
        'customer_firstname' => $order->billing_first_name,
        'customer_lastname' => $order->billing_last_name,
        'customer_phone' => $order->billing_phone,
        'customer_cellphone' => $order->billing_phone,
        'customer_email' => $order->billing_email,

        'address_street' => $order->shipping_address_1,
        'address_city' => $order->shipping_city,
        'address_country' => $order->shipping_country,
        'address_state' => $order->billing_state,
        'address_postal_code' => $order->billing_postcode,
      );
      return $parameters_args;
    }
        
    /**
     * Metodo que genera el formulario con los datos de pago
     *
     * @access public
     * @return void
     */
    public function generate_tpaga_form( $order_id ){     
      $parameters_args = $this->get_params_post( $order_id );
      
      $tpaga_args_array = array();
      foreach( $parameters_args as $key => $value ){
        $tpaga_args_array[] = $key . '=' . $value;
      }
      $params_post = implode('&', $tpaga_args_array);

      $tpaga_args_array = array();
      foreach( $parameters_args as $key => $value ){
        $tpaga_args_array[] = "<input type='hidden' name='purchase[$key]' value='$value'/>";
      }
      return '<form action="'.$this->gateway_url.'" method="post" id="tpaga_form">' . implode('', $tpaga_args_array) 
        . '<input type="submit" id="submit_tpaga" value="' .__('Pagar', 'tpaga').'" /></form>';
    }
    
    /**
     * Procesa el pago 
     *
     * @access public
     * @return void
     */
    function process_payment( $order_id ) {
      global $woocommerce;
      $order = new WC_Order( $order_id );
      $woocommerce->cart->empty_cart();

      if (version_compare(WOOCOMMERCE_VERSION, '2.0.19', '<=' )) {
        return array('result' => 'success', 'redirect' => add_query_arg('order',
          $order->id, add_query_arg('key', $order->order_key, get_permalink(get_option('woocommerce_pay_page_id'))))
        );
      } else {
      
        $parameters_args = $this->get_params_post( $order_id );
        
        $tpaga_args_array = array();
        foreach($parameters_args as $key => $value){
          $tpaga_args_array[] = $key . '=' . $value;
        }
        $params_post = implode('&', $tpaga_args_array);
      
        return array(
          'result' => 'success',
          'redirect' =>  $order->get_checkout_payment_url( true )
        );
      }
    }
    
    /**
     * Retorna la configuracion del api key
     */
    function find_merchant_secret() {
      return $this->settings['merchant_secret'];
    }
  }

  /**
   * Ambas funciones son utilizadas para notifcar a WC la existencia de Tpaga
   */
  function add_tpaga( $methods ) {
    $methods[] = 'Tpaga';
    return $methods;
  }
  add_filter('woocommerce_payment_gateways', 'add_tpaga' );
}