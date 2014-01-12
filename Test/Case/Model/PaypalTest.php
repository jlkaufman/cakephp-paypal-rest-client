<?php
App::uses('PaypalSource.Paypal', 'Model');

/**
 * PaypalSource.Paypal Test Case
 *
 */
class PaypalTest extends CakeTestCase {

/**
 * setUp method
 *
 * @return void
 */
	public function setUp() {
		parent::setUp();
		$this->Paypal = ClassRegistry::init('PaypalSource.Paypal');
	}

/**
 * tearDown method
 *
 * @return void
 */
	public function tearDown() {
		unset($this->Paypal);

		parent::tearDown();
	}

	public function testCreditCardPayment() {

	}

}
