#CakePHP Paypal REST Client

#### Installation

1. Clone the repository into the `app/Plugins/PaypalSource` directory  

```
	cd app/Plugins  
	git clone https://github.com/jlkaufman/CakePHP-Paypal-REST-Client.git PaypalSource
```

#### Configuration

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

### Usage

#### Including the Paypal Model in your controller
* Add `PaypalSource.Paypal` to your $uses array.  
	*E.g.:* `public $uses = array('PaypalSource.Paypal');`  
	
	You can now call the model with `$this->Paypal->method();` from your controller.  
	

#### Credit Card Payment

Credit card payments are easy to create. We can do one of two things; we can create a sale (a final payment), or we can authorize an amount that we will capture later.

To create a creditcard payment, call `$this->Paypal->creditCardPayment($data, $type)` 

* $data will contain all the information we're going to send to Paypal
	Here's an example:	

```
$data = array(
	'credit_card' => array(
		"number"       => "4417119669820331",
		"type"         => "visa",
		"expire_month" => 1,
		"expire_year"  => 2018,
		"cvv2"         => "874",
		"first_name"   => "Joe",
		"last_name"    => "Shopper"
	),
	'billing_address' => array(
		"line1"        => "52 N Main ST",
		"line2"        => "Apt. 211",
		"city"         => "Johnstown",
		"country_code" => "CA",
		"postal_code"  => "H0H 0H0",
		"state"        => "Quebec"
	),
	'transaction' => array(
		"amount" => array(
			"total"    => "7.47",
			"currency" => "USD",
			"details"  => array(
				"subtotal" => "7.41",
				"tax"      => "0.03",
				"shipping" => "0.03"
			)
		),
		"description" => "This is the payment transaction description."
	)
);
	
```	
* $type can either be `authorization` or `sale`  
	`sale`: A final sale, and will complete the transaction  
	`authorization`: Authorize the card for the amount specified. We will have to capture the payment later.

##### Example response

```
{
   "id": "PAY-30J08441N2038343CKLJHKEA",
   "status": "approved",
   "created": "2014-01-12 10:57:20",
   "modified": "2014-01-12 10:57:29",
   "payment_method": "credit_card",
   "type": "authorize",
   "payer": {
      "billing_address": {
         "line1": "52 N Main ST",
         "line2": "Apt. 211",
         "city": "Johnstown",
         "state": "Quebec",
         "postal_code": "H0H 0H0",
         "country_code": "CA"
      },
      "credit_card": {
         "type": "visa",
         "number": "xxxxxxxxxxxx0331",
         "expire_month": "1",
         "expire_year": "2018",
         "first_name": "Joe",
         "last_name": "Shopper"
      },
      "id": "",
      "email": ""
   },
   "approval_url": "",
   "transaction": {
      "amount": {
         "total": "7.47",
         "currency": "USD",
         "details": {
            "subtotal": "7.41",
            "tax": "0.03",
            "shipping": "0.03"
         }
      },
      "description": "This is the payment transaction description.",
      "sale": {
         "id": "",
         "parent_id": ""
      },
      "authorization": {
         "id": "10V50318J8770814T",
         "created": "2014-01-12 10:57:20",
         "parent_id": "PAY-30J08441N2038343CKLJHKEA"
      }
   },
   "error": {
      "code": false
   }
}
```

#### Capture an Authorization
To capture an authorization one must create the [Authorization](#credit-card-payment) and get the Authorization ID from the response.
	

