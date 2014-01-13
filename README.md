#CakePHP Paypal REST Client

### Table of Contents
1. [Installation](#installation)
2. [Configuration](#configuration)
3. Usage
	- [Including the Paypal Model in your controller](#including-the-paypal-model-in-your-controller) 
	- [Notes about response returned](#notes-about-response-returned)
	- [Credit Card Payment](#credit-card-payment)
	- [Capture an Authorization](#capture-an-authorization)
	- [Void an Authorization](#void-an-authorization)
	- [Refund a sale](#refund-a-sale)

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
	
#### Notes about response returned
All the methods in the Paypal class will return an instance of StdClass. In the documentation we show the object in JSON form, simply for readability. You can access each member of the object using arrow notation. 

#### Credit Card Payment

Credit card payments are easy to create. We can do one of two things; we can create a sale (a final payment), or we can authorize an amount that we will capture later.

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

$response = $this->Paypal->creditCardPayment($data, $type);
	
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
To capture an authorization one must create the [Authorization](#credit-card-payment) (Credit Card Payment with $type set to `authorization`) and get the Authorization ID from the response.

Here's an example of capturing an authorization:

```
$capture_data = array(
	'authorization_id' => '10V50318J8770814T',
	'currency'         => 'USD',
	'total'            => '7.47',
	'is_final_capture' => true
);

$response = $this->Paypal->captureAuthorization($data);
```
* `authorization_id` is stored in the response object returned by `Paypal::creditCardPayment()` in `$response->transaction->authorization->id`

##### Example Capture Response

```
{
   "id": "2E448764JU789501Y",
   "status": "completed",
   "created": "2014-01-12 10:57:30",
   "modified": "2014-01-12 10:57:42",
   "payment_method": null,
   "type": "capture",
   "payer": {
      "billing_address": {
         "line1": "",
         "line2": "",
         "city": "",
         "country_code": "",
         "postal_code": "",
         "state": ""
      },
      "credit_card": {
         "number": "",
         "type": "",
         "expire_month": "",
         "expire_year": "",
         "first_name": "",
         "last_name": ""
      },
      "id": "",
      "email": ""
   },
   "approval_url": "",
   "transaction": {
      "amount": {
         "total": "7.47",
         "currency": "USD"
      },
      "description": null,
      "sale": {
         "id": "",
         "parent_id": ""
      },
      "authorization": {
         "id": "",
         "created": ""
      }
   },
   "error": {
      "code": false
   }
}
```

#### Void an Authorization
There are all sorts of reasons one would void an authorization (client canceled the transaction, some other reason that would mean you shoudln't be charging them, whatever).

To void an authorization one must create the [Authorization](#credit-card-payment) (Credit Card Payment with $type set to `authorization`) and get the Authorization ID from the response.

Here's an example of voiding an authorization:

```
$data = array(
	'authorization_id' => '2073151243457584H'
);

$response = $this->Paypal->voidAuthorization($data);
```
* `authorization_id` is stored in the response object returned by `Paypal::creditCardPayment()` in `$response->transaction->authorization->id`

##### Example Response
```
{
   "id": "2073151243457584H",
   "status": "voided",
   "created": "2014-01-12 14:36:48",
   "modified": "2014-01-12 14:37:00",
   "payment_method": null,
   "type": null,
   "payer": {
      "billing_address": {
         "line1": "",
         "line2": "",
         "city": "",
         "country_code": "",
         "postal_code": "",
         "state": ""
      },
      "credit_card": {
         "number": "",
         "type": "",
         "expire_month": "",
         "expire_year": "",
         "first_name": "",
         "last_name": ""
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
      "description": null,
      "sale": {
         "id": "",
         "parent_id": ""
      },
      "authorization": {
         "id": "",
         "created": ""
      }
   },
   "error": {
      "code": false
   }
}
```

#### Refund a sale

Sometimes it's necessary to refund a transaction.
To refund a sale one must first create the [Sale](#credit-card-payment) (Credit Card Payment with $type set to `sale`) and get the Sale ID from the response.

```
$data = array(
	'payment_id' => '3XX41928KR179661L',
	'currency'   => 'USD',
	'total'      => '7.47'
);

$response = $this->Paypal->refundPayment($data);
```
* `payment_id` is stored in the response object returned by `Paypal::creditCardPayment()` in `$response->transaction->sale->id`

##### Example Response
```
{
   "id": "7FN74449PP796325P",
   "status": "completed",
   "created": "2014-01-13 16:31:24",
   "modified": "2014-01-13 16:31:24",
   "payment_method": null,
   "type": "refund",
   "payer": {
      "billing_address": {
         "line1": "",
         "line2": "",
         "city": "",
         "country_code": "",
         "postal_code": "",
         "state": ""
      },
      "credit_card": {
         "number": "",
         "type": "",
         "expire_month": "",
         "expire_year": "",
         "first_name": "",
         "last_name": ""
      },
      "id": "",
      "email": ""
   },
   "approval_url": "",
   "transaction": {
      "amount": {
         "total": "7.47",
         "currency": "USD"
      },
      "description": null,
      "sale": {
         "id": "",
         "parent_id": ""
      },
      "authorization": {
         "id": "",
         "created": ""
      }
   },
   "error": {
      "code": false
   }
}
```