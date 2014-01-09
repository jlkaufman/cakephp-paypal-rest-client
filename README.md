#CakePHP Paypal REST Client

## Installation

1. Clone the repo into the `app/Plugins/PaypalSource` directory

`cd app/Plugins && git clone https://github.com/jlkaufman/CakePHP-Paypal-REST-Client.git PaypalSource`

## Configuration

In `database.php` add the following block and fill it out accordingly:

```
// Paypal
	public $Paypal = array(
		'datasource'     => 'PaypalSource.PaypalSource',
		'environment'    => 'sandbox', // Production: 'production'   SandBox: 'sandbox'
		'username'       => '',
		'password'       => '',
		'receiver_email' => ''
	);
```