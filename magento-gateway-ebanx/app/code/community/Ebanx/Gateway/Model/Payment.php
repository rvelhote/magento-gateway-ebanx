<?php

abstract class Ebanx_Gateway_Model_Payment extends Mage_Payment_Model_Method_Abstract
{
	static protected $redirect_url;
	protected $gateway;
	protected $payment;
	protected $ebanx;
	protected $adapter;
	protected $data;
	protected $result;
	protected $customer;
	protected $paymentData;
	protected $_isGateway = true;
	protected $_canUseFormMultishipping = false;
	protected $_isInitializeNeeded = true;
	protected $_canRefund = true;

	public function __construct()
	{
		parent::__construct();

		$this->ebanx = Mage::getSingleton('ebanx/api')->ebanx();
		$this->adapter = Mage::getModel('ebanx/adapters_paymentAdapter');
		$this->helper = Mage::helper('ebanx');
	}

	public function initialize($paymentAction, $stateObject)
	{
		try {
			parent::initialize($paymentAction, $stateObject);

			$this->payment = $this->getInfoInstance();
			$this->order = $this->payment->getOrder();
			$this->customer = Mage::getModel('customer/customer')->load($this->order->getCustomerId());

			$this->setupData();

			$this->transformPaymentData();

			$this->processPayment();

			$this->persistPayment();
		} catch (Exception $e) {
			Mage::throwException($e->getMessage());
		}
	}

	public function setupData()
	{
		// Create payment data
		$id = $this->payment->getOrder()->getIncrementId();
		$time = time();
		$merchantPaymentCode = "$id-$time";

		$this->data = new Varien_Object();
		$this->data->setMerchantPaymentCode($merchantPaymentCode)
			->setDueDate($this->helper->getDueDate())
			->setEbanxMethod($this->getCode())
			->setStoreCurrency(Mage::app()->getStore()->getCurrentCurrencyCode())
			->setAmountTotal($this->order->getGrandTotal())
			->setPerson($this->customer)
			->setItems($this->order->getAllVisibleItems())
			->setRemoteIp($this->order->getRemoteIp())
			->setBillingAddress($this->order->getBillingAddress())
			->setPayment($this->payment)
			->setOrder($this->order);
	}

	public function transformPaymentData()
	{
		$this->paymentData = $this->adapter->transform($this->data);
	}

	public function processPayment()
	{
		$res = $this->gateway->create($this->paymentData);

		$this->helper->log($res, $this->getCode() . '.log');

		if ($res['status'] !== 'SUCCESS') {
			// TODO: Make an error handler
			Mage::throwException($res['status_code'] . ' - ' . $res['status_message']);
		}

		// Set the URL for redirect
		if (!empty($res['redirect_url'])) {
			self::$redirect_url = $res['redirect_url'];
		} else {
			self::$redirect_url = Mage::getUrl('checkout/onepage/success');
		}

		$this->result = $res;
	}

	public function persistPayment()
	{
		$this->payment
			->setEbanxPaymentHash($this->result['payment']['hash'])
			->setEbanxEnvironment($this->helper->getMode());

		$this->customer
			->setEbanxCustomerDocument($this->helper->getDocumentNumber())
			->save();
	}

	public function getOrderPlaceRedirectUrl()
	{
		return self::$redirect_url;
	}

	public function canUseForCountry($country)
	{
		$countryName = $this->helper->transformCountryCodeToName($country);

		return $this->gateway->isAvailableForCountry($countryName);
	}
}
