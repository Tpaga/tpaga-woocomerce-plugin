## Tpaga Web Checkout para WooCommerce

[![Code Climate](https://codeclimate.com/github/Tpaga/tpaga-woocomerce-plugin/badges/gpa.svg)](https://codeclimate.com/github/Tpaga/tpaga-woocomerce-plugin)	[![Issue Count](https://codeclimate.com/github/Tpaga/tpaga-woocomerce-plugin/badges/issue_count.svg)](https://codeclimate.com/github/Tpaga/tpaga-woocomerce-plugin)	[![Test Coverage](https://codeclimate.com/github/Tpaga/tpaga-woocomerce-plugin/badges/coverage.svg)](https://codeclimate.com/github/Tpaga/tpaga-woocomerce-plugin/coverage)

Tpaga WebCheckout es un servicio de Tpaga con el cual sus clientes podrán realizar pagos de manera agíl y sencilla. Este plugin es una implimentación de Tpaga WebCheckout para Wordpress para ser utilizado en conjunto con Woocommerce (Nos encontramos en Beta).

### Requerimientos mínimos

 - Wordpress 4.4.2 (Probado 4.5)
 - WooCommerce 2.0 ó posterior

### Integración del plugin con Wordpress
Debido a que este plugin es una extensión de [Woocomerce](https://www.woothemes.com/woocommerce/), es necesario tener dicho plugin instalado en su instancia de Wordpress.

1. Descargue el zip de este repositorio.
2. Visite `/wp-admin/plugin-install.php` y cargue el archivo .zip en el formulario.
3. Seleccione Tpaga en la ficha de checkout ubicada en  `WooCommerce > Settings > Checkout > Tpaga`
4. En el formulario deberá activar a Tpaga como medio de pago.
5. Los campos `Merchant Token` y `Secret` son generados al registrase en Tpaga WebCheckout
6. Si desea hacer transacciones de prueba seleccione el botón `Transacciones en modo de prueba`.

Con esta breve configuración usted esta listo para hacer cobros utilizando Tpaga WebCheckout.

> **NOTA :** Si el titulo de la página de respuesta es `Page Not Found` revise la configuración de los permalinks. [wordpress is giving me 404 page not found](http://stackoverflow.com/questions/5182534/wordpress-is-giving-me-404-page-not-found-for-all-pages-except-the-homepage#5182651)  
