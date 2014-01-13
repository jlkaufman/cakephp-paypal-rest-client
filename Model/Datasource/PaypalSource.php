<?php
/**
 *  Copyright (c) 2013, Justin Kaufman
 *  All rights reserved.
 *
 *  https://github.com/jlkaufman
 *
 *  Redistribution and use in source and binary forms, with or without
 *  modification, are permitted provided that the following conditions are met:
 *
 *  1. Redistributions of source code must retain the above copyright notice, this
 *     list of conditions and the following disclaimer.
 *  2. Redistributions in binary form must reproduce the above copyright notice,
 *     this list of conditions and the following disclaimer in the documentation
 *     and/or other materials provided with the distribution.
 *
 *  THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND
 *  ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
 *  WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
 *  DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR
 *  ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
 *  (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 *  LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
 *  ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 *  (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
 *  SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 *  The views and conclusions contained in the software and documentation are those
 *  of the authors and should not be interpreted as representing official policies,
 *  either expressed or implied, of the FreeBSD Project.
 */

App::uses('HttpSocket', 'Network/Http');
/**
 * PaypalSource class.
 *
 * @extends DataSource
 */
class PaypalSource extends DataSource
{
/**
 * description
 *
 * @var string
 * @access public
 */
	public $description = "Paypal REST client";



/**
 * environment
 *
 * (default value: '')
 *
 * @var string
 * @access private
 */
	private $_environment = '';




/**
 * user_name
 *
 * (default value: "")
 * SET IN database.php
 *
 * @var string
 * @access private
 */
	private $_user_name = "";




/**
 * password
 *
 * (default value: "")
 * SET IN database.php
 *
 * @var string
 * @access private
 */
	private $_password = "";




/**
 * Http
 *
 * (default value: null)
 * SET IN database.php
 *
 * @var mixed
 * @access private
 */
	private $Http = null;



/**
 * Payload we're sending to paypal
 *
 * (default value: null)
 *
 * @var mixed
 * @access private
 */
	private $_payload = null;





/**
 * Token we use to authenticate to paypal
 * @var [type]
 */
	private $_token = null;




/**
 * Endpoint we're using to talk to paypal
 * @var [type]
 */
	private $api_endpoint = null;




/**
 * Stores the response object
 * @var boolean
 */
	private $_response = false;



/**
 * __construct function.
 *
 * @access public
 * @param  mixed $config
 * @return void
 */
	public function __construct($config) {
		define('PAYPAL_TOKEN_EXPIRATION', 28800);
		parent::__construct($config);

		$this->Http      = new HttpSocket();

		$this->_user_name   = $this->config['username'];
		$this->_password    = $this->config['password'];
		$this->_environment = $this->config['environment'];

		if ("sandbox" === $this->_environment || "beta-sandbox" === $this->_environment) {
			$this->api_endpoint = "https://api." . $this->_environment . ".paypal.com/v1";
		} else {
			$this->api_endpoint = "https://api.paypal.com/v1";
		}

        		$this->_getToken();
	}



/**
* info function.
*
*	Returns info about this datasource
* @access public
* @return void
*/
	 public function info() {
		 return $this->description;
	 }




/**
 * Make a credit card payment
 * @param  array  $request data for the transaction
 */
	 public function creditCardPayment(array $request, stdClass $response, $type = null) {
	 	$this->_response = $response;
		$type = $this->_saleType($type);
		$request['credit_card']['billing_address'] = $request['billing_address'];
		$this->_payload = array(
			'intent' => $type,
			'payer' => array(
				'payment_method'      => 'credit_card',
				'funding_instruments' => array(
					array('credit_card' => $request['credit_card'])
				)
			),
			'transactions' => array($request['transaction'])
		);

		$this->_call('/payments/payment', 'post');

		return $this->_response;
	 }






/**
 * Creates a Paypal payment
 */
	 public function createPaypalPayment(array $request, stdClass $response, $type = null) {
	 	$this->_response = $response;
		$this->_payload = array(
			'intent' => $type,
			'redirect_urls' => array(
				'return_url' => $request['return_urls']['return_url'],
				'cancel_url' => $request['return_urls']['cancel_url']
			),
			'payer' => array(
				'payment_method'      => 'paypal'
			),
			'transactions' => array($request['transaction'])
		);

		$this->_call('/payments/payment', 'post');

		return $this->_response;
	 }





/**
 * Executes an approved Paypal payment
 * @param  [type] $request [description]
 * @return [type]       [description]
 */
	 public function executePaypalPayment(array $request, stdClass $response) {
	 	$this->_response = $response;
		$this->_payload = array(
			'payer_id' => $request['id']
		);

		$this->_call('/payments/payment/' . $request['payment_id'] . '/execute', 'post');

		return $this->_response;
	 }





/**
 * Capture an authorization
 */
	 public function captureAuthorization(array $request, stdClass $response) {
	 	$this->_response = $response;
		$this->_payload = array(
			'amount' => array(
				'currency' => $request['currency'],
				'total'    => $request['total']
			),
			'is_final_capture' => $request['is_final_capture']
		);

		$this->_call('/payments/authorization/' . $request['authorization_id'] . '/capture', 'post');

		$this->_response->type = 'capture';
		return $this->_response;
	 }





/**
 * Voids an authorization
 */
	 public function voidAuthorization(array $request, stdClass $response) {
	 	$this->_response = $response;
		$this->_call('/payments/authorization/' . $request['authorization_id'] . '/void', 'post');

		return $this->_response;
	 }







/**
 * Refunds a payment
 */
	 public function refundPayment(array $request, stdClass $response, $type = null) {
	 	$this->_response = $response;
		$type = $this->_refundType($type);

		if (isset($request['total']) && isset($request['currency'])) {
			$this->_payload = array(
				'amount' => array(
					'currency' => $request['currency'],
					'total'    => $request['total']
				),
			);
		} else {
			$this->_payload = array();
		}

		$this->_call('/payments/' . $type . '/' . $request['payment_id'] . '/refund', 'post');

		$this->_response->type = 'refund';

		return $this->_response;
	 }







/**
 * Stores a credit card in the CC vault provided by
 * Paypal
 * @param  array  $request Data to be stored
 * @return object
 */
	 public function storeCreditCard(array $request, stdClass $response) {
	 	$this->_response = $response;

		$this->_payload                    = $request['credit_card'];
		$this->_payload['payer_id']		   = $request['payer_id'];
		$this->_payload['billing_address'] = $request['billing_address'];

		$this->_call('/vault/credit-card/', 'post');

		$this->_response->type = 'store_credit_card';

		return $this->_response;
	 }






/**
 * Gets a credit card from the vault (used to check the status of a saved card mostly.)
 * @param  array  $request array containing the ID of the card
 * @return object
 */
	 public function getStoredCreditCardStatus(array $request, stdClass $response) {
	 	$this->_response = $response;

		$this->_payload = new stdClass();

		$this->_call('/vault/credit-card/' . $request['id'], 'get');

		$this->_response->type = 'get_credit_card';

		return $this->_response;
	 }






/**
 * Deletes a CC from the vault
 * @param  array  $request array containing the ID of the card
 * @return object
 */
	  public function deleteStoredCreditCard(array $request, stdClass $response) {
	  	$this->_response = $response;
		$this->_payload = new stdClass();

		$this->_call('/vault/credit-card/' . $request['id'], 'delete');

		$this->_response->type = 'delete_credit_card';

		return $this->_response;
	 }





/**
 * Performs the communication with the paypal api
 *
 * In case of error it returns false
 *
 * @param string $endpoint Endpoint we want to talk to
 * @param string $method   HTTP method we'll be using to communicate with Paypal
 * @return mixed array if success, false otherwise.
 */
	private function _call($endpoint, $method = null) {

		if ($this->_token === null) {
			$this->_getToken();
		}

		switch(strtolower($method)) {
			case 'get':
				$method = 'get';
				break;
			case 'delete':
				$method = 'delete';
				break;
            case 'post':
            default:
				$method = 'post';
				break;
		}


		$request = array(
			'header' => array(
				'Authorization' => 'Bearer ' . $this->_token,
				'Content-Type'  => 'application/json'
			)
		);
		if (!empty($this->_payload)) {
			$body = json_encode($this->_payload);
		} else {
			$body = json_encode(new stdClass());
		}


		$end_point = $this->api_endpoint . $endpoint;



		//call the web service
		$response = $this->Http->{$method}($end_point, $body, $request);

		if (!$response->isOk()) {
			$this->_handleError($response);
		} else {
			$this->_setResponse($response);
		}
	}





/**
 * Gets a token from paypal
 * Caches the result with an expiration that is 5 minutes less than the expiration of the token (8 hrs)
 */
	private function _getToken() {


		$cache_duration = '+ ' . (PAYPAL_TOKEN_EXPIRATION - 300) . ' Second';

		Cache::set(array('duration' => $cache_duration));
		$token = Cache::read('paypal_token');


		if ($token) {
			$this->_token = $token;
			return true;
		}

		$end_point = $this->api_endpoint . '/oauth2/token';


		$this->Http->configAuth('Basic', $this->_user_name, $this->_password);
		$request = array(
			'header' => array(
				'Content-Type'    => 'application/x-www-form-urlencoded',
				'Accept'          => 'application/json',
				'Accept-Language' => 'en_US'
			)
		);
		$body = array('grant_type' => 'client_credentials');

		//call the web service
		$response = $this->Http->post($end_point, $body, $request);
		if (!$response->isOk()) {
			$this->_handleError($response);
			return false;
		}

		$response = json_decode($response->body);

		$this->_token = $response->access_token;

		Cache::set(array('duration' => $cache_duration));
		Cache::write('paypal_token' , $this->_token);


		return true;
	}





/**
 * Handles errors returned by paypal
 *
 * @param  object $response Response object
 */
	private function _handleError(&$response) {
		$this->_response->status       = 'error';
		$this->_response->error        = json_decode($response->body);
		@$this->_response->error->code = $response->code;

		$this->_setResponse($response);
	}






/**
 * Build the response object
 */
	private function _setResponse(&$response) {
		$Response                        = json_decode($response->body);
		$this->_response->id             = @$Response->id;
		$this->_response->status         = @$Response->state;
		$this->_response->created        = date('Y-m-d H:i:s', strtotime(@$Response->create_time));
		$this->_response->modified       = date('Y-m-d H:i:s', strtotime(@$Response->update_time));
		$this->_response->payment_method = @$Response->payer->payment_method;
		$this->_response->type           = @$Response->intent;


		if ($this->_response->payment_method == 'credit_card') {

			$this->_response
				->payer
				->billing_address = @$Response->payer->funding_instruments[0]->credit_card->billing_address;

			unset($Response->payer->funding_instruments[0]->credit_card->billing_address);

			$this->_response
				->payer
				->credit_card     = @$Response->payer->funding_instruments[0]->credit_card;

		} elseif ($this->_response->payment_method == 'paypal') {

			$this->_response->payer->email = @$Response->payer->payer_info->email;
			$this->_response->payer->id    = @$Response->payer->payer_info->id;

			if (isset($Response->links)) {
				foreach ($Response->links as $link) {
					if ($link->rel == 'approval_url') {
						$this->_response->approval_url = $link->href;
					}
				}
			}

		}

		if (isset($Response->amount)) {
			$amount = $Response->amount;
		} else {
			$amount = @$Response->transactions[0]->amount;
		}

		$this->_response->transaction->amount      = $amount;
		$this->_response->transaction->description = @$Response->transactions[0]->description;

		if (isset($Response->transactions[0]->related_resources)) {
			foreach ($Response->transactions[0]->related_resources as $related_resource) {
				if (isset($related_resource->sale)) {
					if (isset($related_resource->sale->id)) {
						$this->_response
							->transaction
							->sale
							->id = $related_resource->sale->id;
					}

					if (isset($related_resource->sale->parent_payment)) {
						$this->_response
							->transaction
							->sale
							->parent_id = $related_resource->sale->parent_payment;
					}
				}


				if (isset($related_resource->authorization)) {
					if (isset($related_resource->authorization->id)) {
						$this->_response
							->transaction
							->authorization
							->id = $related_resource->authorization->id;
					}

					if (isset($related_resource->authorization->parent_payment)) {
						$this->_response
							->transaction
							->authorization
							->parent_id = $related_resource->authorization->parent_payment;
					}

					$this->_response->transaction->authorization->created = date(
						'Y-m-d H:i:s',
						strtotime($related_resource->authorization->create_time)
					);
					break;
				}
			}
		}
	}
/**
 * Types of sales we can do
 */
	private function _saleType($type) {
		switch ($type) {
			case 'authorize':
			case 'authorization':
				$type = 'authorize';
				break;
			default:
				$type = 'sale';
				break;
		}
		return $type;
	}
/**
 * Types of refunds we can do
 */
	private function _refundType($type) {
		switch ($type) {
			case 'capture':
				$type = 'capture';
				break;
			default:
				$type = 'sale';
				break;
		}
		return $type;
	}
}