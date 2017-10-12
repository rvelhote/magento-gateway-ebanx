<?php
require_once Mage::getBaseDir('lib') . '/Ebanx/vendor/autoload.php';

use Ebanx\Benjamin\Models\Bank;
use Ebanx\Benjamin\Models\Country;
use Ebanx\Benjamin\Models\Person;
use Ebanx\Benjamin\Services\Exchange;

class Ebanx_Gateway_Helper_Data extends Mage_Core_Helper_Abstract
{
	const URL_PRINT_LIVE = 'https://print.ebanx.com/';
	const URL_PRINT_SANDBOX = 'https://sandbox.ebanx.com/print/';

	private $order;

	public function getEbanxUrl()
	{
		return $this->isSandboxMode() ? self::URL_PRINT_SANDBOX : self::URL_PRINT_LIVE;
	}

	public function isSandboxMode()
	{
		return $this->getMode() === Ebanx_Gateway_Model_Source_Mode::SANDBOX;
	}

	public function getMode()
	{
		return Mage::getStoreConfig('payment/ebanx_settings/mode');
	}

	public function getSandboxIntegrationKey()
	{
		return Mage::getStoreConfig('payment/ebanx_settings/integration_key_' . Ebanx_Gateway_Model_Source_Mode::SANDBOX);
	}

	public function getLiveIntegrationKey()
	{
		return Mage::getStoreConfig('payment/ebanx_settings/integration_key_' . Ebanx_Gateway_Model_Source_Mode::LIVE);
	}

	public function areKeysFilled()
	{
		$keys = $this->getIntegrationKey();
		$publicKeys = $this->getPublicIntegrationKey();
		return !empty($keys) && !empty($publicKeys);
	}

	public function getIntegrationKey()
	{
		return Mage::getStoreConfig('payment/ebanx_settings/integration_key_' . $this->getMode());
	}

	public function getPublicIntegrationKey()
	{
		return Mage::getStoreConfig('payment/ebanx_settings/integration_key_public_' . $this->getMode());
	}

	public function getDueDate($date = null, $format = 'YYYY-MM-dd HH:mm:ss')
	{
		$date = !is_null($date) ? $date : Mage::getModel('core/date')->timestamp();
		$dueDate = new Zend_Date($date);

		return $dueDate->addDay($this->getDueDateDays())->get($format);
	}

	public function getDueDateDays()
	{
		return Mage::getStoreConfig('payment/ebanx_settings/due_date_days');
	}

	public function getMaxInstalments()
	{
		return Mage::getStoreConfig('payment/ebanx_settings/max_instalments');
	}

	public function getMinInstalmentValue()
	{
		return Mage::getStoreConfig('payment/ebanx_settings/min_instalment_value');
	}

	public function getInterestRate()
	{
		return Mage::getStoreConfig('payment/ebanx_settings/interest_rate');
	}

	public function saveCreditCardAllowed()
	{
		return Mage::getStoreConfig('payment/ebanx_settings/save_card_data');
	}

	public function transformTefToBankName($bankCode)
	{
		$banks = array(
			'itau' => Bank::ITAU,
			'bradesco' => Bank::BRADESCO,
			'bancodobrasil' => Bank::BANCO_DO_BRASIL,
			'banrisul' => Bank::BANRISUL
		);

		return $banks[strtolower($bankCode)];
	}

	public function hasDocumentFieldAlreadyForMethod($methodCode)
	{
		$fields = $this->getDocumentFieldsRequiredForMethod($methodCode);

		if (empty($fields)) {
			return true;
		}

		foreach ($fields as $field) {
			$documentFieldName = Mage::getStoreConfig('payment/ebanx_settings/' . $field);
			if ($documentFieldName) {
				if (!Mage::getSingleton('customer/session')->isLoggedIn() || Mage::getSingleton('checkout/session')->getQuote()->getCheckoutMethod() === 'register') {
					return true;
				}

				$customerId = Mage::getSingleton('customer/session')->getCustomer()->getId();
				$customer = Mage::getModel('customer/customer')->load($customerId);

				$customerHasSavedAddress = $customer->getDefaultShipping();
				$customerHasSavedDocument = $customer->getData($documentFieldName);
				if ($customerHasSavedAddress && $customerHasSavedDocument) {
					return true;
				}
			}
		}

		return false;
	}

	public function getLabelForComplianceField($code)
	{
		switch ($code) {
			case 'ebanx_boleto':
			case 'ebanx_tef':
			case 'ebanx_wallet':
			case 'ebanx_cc_br':
				return $this->getBrazilianDocumentLabel();

			case 'ebanx_sencillito':
			case 'ebanx_servipag':
				return $this->__('RUT Document');

			case 'ebanx_baloto':
			case 'ebanx_pse':
				return $this->__('DNI Document');

			default:
				return $this->__('Document Number');
		}
	}

	public function getLabelForComplianceFieldByCountry($countryCode)
	{
		switch (strtolower($countryCode)) {
			case 'br':
				return $this->getBrazilianDocumentLabel();
			case 'cl':
				return $this->__('RUT Document');
			case 'co':
				return $this->__('DNI Document');
			default:
				return $this->__('Document Number');
		}
	}

	public function getBrazilianDocumentLabel()
	{
		$label = array();
		$taxes = explode(',', Mage::getStoreConfig('payment/ebanx_settings/brazil_taxes'));

		return strtoupper(implode(' / ', $taxes));
	}

	public function getDocumentNumber($order, $data)
	{
		$this->order = $order;
		$countryCode = $this->getCustomerData()['country_id'];
		$country = $this->transformCountryCodeToName($countryCode);
		$methodCode = $data->getEbanxMethod();

		switch ($country) {
			case Country::BRAZIL:
				return $this->getBrazilianDocumentNumber($methodCode);
			case Country::CHILE:
				return $this->getChileanDocumentNumber($methodCode);
			case Country::COLOMBIA:
				return $this->getColombianDocumentNumber($methodCode);
			default:
				return null;
		}
	}

	public function getCustomerData()
	{
		$checkoutData = Mage::getSingleton('checkout/session')->getQuote()->getBillingAddress()->getData();

		$customerAddressData = array_key_exists('customer_address_id', $checkoutData) && !is_null($checkoutData['customer_address_id'])
			? Mage::getModel('customer/address')->load($checkoutData['customer_address_id'])->getCustomer()->getData()
			: $checkoutData;

		$customerSessionData = Mage::getSingleton('customer/session')->getCustomer()->getData();

		$customerParams = Mage::app()->getRequest()->getParams();

		$data = array_merge(
			$checkoutData,
			$customerAddressData,
			$customerSessionData,
			$customerParams
		);

		return $data;
	}

	public function transformCountryCodeToName($countryCode)
	{
		if (!$countryCode) {
			return false;
		}

		$countries = array(
			'cl' => Country::CHILE,
			'br' => Country::BRAZIL,
			'co' => Country::COLOMBIA,
			'mx' => Country::MEXICO,
			'pe' => Country::PERU,
		);

		$countryIndex = strtolower($countryCode);
		if (!array_key_exists($countryIndex, $countries)) {
			return null;
		}

		return $countries[$countryIndex];
	}

	public function getBrazilianDocumentNumber($methodCode)
	{
		$customer = $this->getCustomerData();

		if (array_key_exists('ebanx-document', $customer) && isset($customer['ebanx-document'][$methodCode])) {
			return $customer['ebanx-document'][$methodCode];
		}

		if ($cpfField = Mage::getStoreConfig('payment/ebanx_settings/cpf_field')) {
			if ($cpfField === 'taxvat') {
				return $this->order->getCustomerTaxvat();
			}

			if ($customer[$cpfField]) {
				return $customer[$cpfField];
			}
		}

		if ($cnpjField = Mage::getStoreConfig('payment/ebanx_settings/cnpj_field')) {
			if ($cnpjField === 'taxvat') {
				return $this->order->getCustomerTaxvat();
			}

			if ($customer[$cnpjField]) {
				return $customer[$cnpjField];
			}
		}


		return $customer['ebanx-document'][$methodCode];
	}

	public function getChileanDocumentNumber($methodCode)
	{
		$customer = $this->getCustomerData();

		if ($rutField = Mage::getStoreConfig('payment/ebanx_settings/rut_field')) {
			if ($rutField === 'taxvat') {
				return $this->order->getCustomerTaxvat();
			}

			if ($customer[$rutField]) {
				return $customer[$rutField];
			}
		}

		return $customer['ebanx-document'][$methodCode];
	}

	public function getColombianDocumentNumber($methodCode)
	{
		$customer = $this->getCustomerData();

		if ($dniField = Mage::getStoreConfig('payment/ebanx_settings/dni_field')) {
			if ($dniField === 'taxvat') {
				return $this->order->getCustomerTaxvat();
			}

			if ($customer[$dniField]) {
				return $customer[$dniField];
			}
		}

		return $customer['ebanx-document'][$methodCode];
	}

	public function getPersonType($document)
	{
		$document = str_replace(array('.', '-', '/'), '', $document);

		if ($this->getCustomerData()['country_id'] !== 'BR' || strlen($document) < 14) {
			return Person::TYPE_PERSONAL;
		}

		return Person::TYPE_BUSINESS;
	}

	public function errorLog($data)
	{
		$this->log($data, 'ebanx_error');
	}

	public function log($data, $filename = 'ebanx', $extension = '.log')
	{
		$isLogEnabled = Mage::getStoreConfig('payment/ebanx_settings/debug_log') === '1';

		if (!$isLogEnabled) return;

		Mage::log($data, null, $filename . $extension, true);
	}

	/**
	 * Splits address in street name, house number and addition
	 *
	 * @param  string $address Address to be split
	 * @return array
	 */
	public function split_street($address)
	{
		$result = preg_match('/^([^,\-\/\#0-9]*)\s*[,\-\/\#]?\s*([0-9]+)\s*[,\-\/]?\s*([^,\-\/]*)(\s*[,\-\/]?\s*)([^,\-\/]*)$/', $address, $matches);
		if ($result === false) {
			throw new \RuntimeException(sprintf('Problems trying to parse address: \'%s\'', $address));
		}
		if ($result === 0) {
			return array(
				'streetName' => $address,
				'houseNumber' => 'S/N',
				'additionToAddress' => ''
			);
		}
		$street_name = $matches[1];
		$house_number = $matches[2];
		$addition_to_address = $matches[3] . $matches[4] . $matches[5];
		if (empty($street_name)) {
			$street_name = $matches[3];
			$addition_to_address = $matches[5];
		}
		return array(
			'streetName' => $street_name,
			'houseNumber' => $house_number ?: 'S/N',
			'additionToAddress' => $addition_to_address
		);
	}

	public function getVoucherUrlByHash($hash, $format = 'basic')
	{
		$res = $this->getPaymentByHash($hash);

		if ($res['status'] !== 'SUCCESS') {
			return;
		}

		$payment = $res['payment'];

		switch ($payment['payment_type_code']) {
			case 'boleto':
				$url = $payment['boleto_url'];
				break;
			case 'pagoefectivo':
				$url = $payment['cip_url'];
				break;
			case 'oxxo':
				$url = $payment['oxxo_url'];
				break;
			case 'baloto':
				$url = $payment['baloto_url'];
				break;
		}

		return "{$url}&format={$format}";
	}

	public function getPaymentByHash($hash)
	{
		$ebanx = Mage::getSingleton('ebanx/api')->ebanx();

		return $ebanx->paymentInfo()->findByHash($hash);
	}

	public function getLocalAmountWithTax($currency, $value)
	{
		$ebanx = Mage::getSingleton('ebanx/api')->ebanx();

		return $ebanx->exchange()->siteToLocalWithTax($currency, $value);
  }

	public function hasToShowInlineIcon()
	{
		return Mage::getStoreConfig('payment/ebanx_settings/payment_methods_visualization');
	}

	public function isEbanxMethod($code) {
		$ebanxMethods = array(
			'ebanx_cc_br',
			'ebanx_boleto',
			'ebanx_tef',
			'ebanx_wallet',
			'ebanx_sencillito',
			'ebanx_servipag',
			'ebanx_webpay',
			'ebanx_multicaja',
			'ebanx_pse',
			'ebanx_baloto',
			'ebanx_cc_mx',
			'ebanx_dc_mx',
			'ebanx_oxxo',
			'ebanx_safetypay',
			'ebanx_pagoefectivo'
		);
		return in_array($code, $ebanxMethods);
	}

	/**
	 * @param $methodCode
	 * @return mixed
	 */
	public function getDocumentFieldsRequiredForMethod($methodCode)
	{
		$methodsToFields = [
			// Brazil
			'ebanx_boleto'       => ['cpf_field', 'cnpj_field'],
			'ebanx_tef'          => ['cpf_field', 'cnpj_field'],
			'ebanx_wallet'       => ['cpf_field', 'cnpj_field'],
			'ebanx_cc_br'        => ['cpf_field', 'cnpj_field'],
			// Chile
			'ebanx_sencillito'   => ['rut_field'],
			'ebanx_servipag'     => ['rut_field'],
			'ebanx_webpay'       => ['rut_field'],
			'ebanx_multicaja'    => ['rut_field'],
			// Colombia
			'ebanx_baloto'       => ['dni_field'],
			'ebanx_pse'          => ['dni_field'],
			// Mexico
			'ebanx_oxxo'         => [],
			'ebanx_cc_mx'        => [],
			'ebanx_dc_mx'        => [],
			// Peru
			'ebanx_pagoefectivo' => [],
			'ebanx_safetypay'    => []
		];

		return $methodsToFields[$methodCode];
	}
}
