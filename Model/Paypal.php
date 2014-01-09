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
App::uses('AppModel', 'Model');

class Paypal extends AppModel
{
/**
 * We don't use a DB table
 * @var boolean
 */
	public $useTable = false;


/**
 * Default datasource for this model
 * @var string
 */
	public $useDbConfig = 'Paypal';

/**
 * Template of Request array (what we send to the datasource)
 * @var array
 */
	public $requestTemplate = array(
		'credit_card' => array(
			'number'       => '',
			'type'         => '',
			'expire_month' => '',
			'expire_year'  => '',
			'cvv2'         => '',
			'first_name'   => '',
			'last_name'    => ''
		),
		'billing_address' => array(
			'line1'        => '',
			'line2'        => '', //optional
			'city'         => '',
			'country_code' => '',
			'postal_code'  => '',
			'state'        => ''
		),
		'transaction' => array(
			'amount' => array(
				'total'    => '',
				'currency' => '',
				'details'  => array(
					'subtotal' => '',
					'tax'      => '',
					'shipping' => ''
				)
			),
			'description' => ''
		),
		'return_urls' => array( // We must set these for Paypal payments, they are required fields
			'cancel_url' => '',
			'return_url' => ''
		)
	);

/**
 * Template of the Response Object returned by the datasource
 * @var array
 */
	public $responseTemplate = array(
		'id'             => '',
		'status'         => '',
		'created'        => '',
		'modified'       => '',
		'payment_method' => '',
		'type'           => '',
		'payer'          => array(
			'billing_address' => array( // Set  when we're processing a credit card
				'line1'        => '',
				'line2'        => '',
				'city'         => '',
				'country_code' => '',
				'postal_code'  => '',
				'state'        => ''
			),
			'credit_card' => array( // Set  when we're processing a credit card
				'number'       => '',
				'type'         => '',
				'expire_month' => '',
				'expire_year'  => '',
				'first_name'   => '',
				'last_name'    => ''
			),
			'id' => '', // Set when we're processing a paypal payment
			'email' => '' // Same
		),
		'approval_url' => '', // If we're using Paypal payments, this will be the URL that
							  // we forward users to to confirm the payment
		'transaction'    => array(
			'amount' => array(
				'total'    => '',
				'currency' => '',
				'details'  => array(
					'subtotal' => '',
					'tax'      => '',
					'shipping' => ''
				)
			),
			'description' => '',
			'sale' => array( // Set when we're processing a 'Sale'
				'id' => '',
				'parent_id' => ''
			),
			'authorization' => array( // Set when we're authorizing
				'id' => '',
				'created' => ''
			)
		),
		'error' => array( // Set when we encounter an error
			'code' => false
		)
	);
/**
 * Construct
 *
 */
	public function __construct($id = false, $table = null, $ds = null) {
		parent::__construct($id, $table, $ds);
		$this->_getDatasource();
	}

/**
 * __get() - Overload the model
 * @param  string $name property we're tying to get
 * @return mixed
 */
	public function __get($name) {
		if ($name == 'response') {
			return $this->_arrayToObject($this->responseTemplate);
		}
		if ($name == 'request') {
			$request = new ArrayObject($this->requestTemplate);
			return $request->getArrayCopy();
		}
		parent::__get($name);
	}


/**
 * Create a credit card payment
 *
 * @param  array  $data Array of data to send
 * @param  string $type authorize or sale
 * @return object PaymentResponse
 */
	public function creditCardPayment($data, $type = null) {
		$request = array_merge($this->request, $data);

		return $this->Gateway->creditCardPayment($request, $this->response, $type);
	}

/**
 * Creates a Paypal payment
 * @param  array  $data Array of data to send
 * @param  string $type authorize or sale
 * @return object PaymentResponse
 */
	public function createPaypalPayment($data, $type = null) {
		$this->_getDatasource('Paypal');
		$request = array_merge($this->request, $data);
		return $this->Gateway->createPaypalPayment($request, $this->response, $type);
	}

/**
 * Executes a paypal payment after we've received the token
 * @param  [type] $data [description]
 * @return [type]       [description]
 */
	public function executePaypalPayment($data) {
		$this->_getDatasource('Paypal');
		$request = array_merge($this->request, $data);
		return $this->Gateway->executePaypalPayment($request, $this->response);
	}

/**
 * Captures an authorized payment
 * @param  array  $data
 * @return object
 */
	public function captureAuthorization($data) {
		$request = array_merge($this->request, $data);
		return $this->Gateway->captureAuthorization($request, $this->response);
	}

/**
 * Voids an authorization
 * @param  array  $data
 * @return object
 */
	public function voidAuthorization($data) {
		$request = array_merge($this->request, $data);
		return $this->Gateway->voidAuthorization($request, $this->response);
	}

/**
 * Refunds a payment
 * @param  array  $data
 * @param  string $type capture or sale
 * @return object
 */
	public function refundPayment($data, $type = null) {
		$request = array_merge($this->request, $data);
		return $this->Gateway->refundPayment($request, $this->response, $type);
	}

/**
 * Stores a CC in the gateway's vault
 * @param  array  $data
 * @return object
 */
	public function storeCreditCard($data) {
		$request = array_merge($this->request, $data);
		return $this->Gateway->storeCreditCard($request, $this->response);
	}

/**
 * Gets the status of a stored credit card
 * @param  array  $data
 * @return object
 */
	public function getStoredCreditCardStatus($data) {
		$request = array_merge($this->request, $data);
		return $this->Gateway->getStoredCreditCardStatus($request, $this->response);
	}

/**
 * Deletes a stored CC from the vault
 * @param  array  $data
 * @return object
 */
	public function deleteStoredCreditCard($data) {
		$request = array_merge($this->request, $data);
		return $this->Gateway->deleteStoredCreditCard($request, $this->response);
	}

/**
 * Grabs a datasource and sets it to $this->Gateway
 * @param  string $datasource name of the datasource config
 * @return void
 */
	private function _getDatasource($datasource = null) {
		$this->Gateway = $this->getDataSource($datasource);
	}




/**
 * Converts an array into an object
 *
 * @param  array $array Array to be converted
 * @return stdClass
 */
	private function _arrayToObject($array) {
		return json_decode(json_encode($array));
	}
}