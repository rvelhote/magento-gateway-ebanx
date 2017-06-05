<?php
require_once Mage::getBaseDir('lib') . '/Ebanx/vendor/autoload.php';

use Ebanx\Benjamin\Models\Country;

class Ebanx_Gateway_Helper_Data extends Mage_Core_Helper_Abstract
{
    const URL_PRINT_LIVE    = 'https://ebanx.com/print/';
    const URL_PRINT_SANDBOX = 'https://sandbox.ebanx.com/print/';

    public function getEbanxUrl()
	{
        return $this->isSandboxMode() ? self::URL_PRINT_SANDBOX : self::URL_PRINT_LIVE;
    }

    public function getMode()
	{
        return Mage::getStoreConfig('payment/ebanx_settings/mode');
    }

    public function isSandboxMode()
	{
        return $this->getMode() === Ebanx_Gateway_Model_Source_Mode::SANDBOX;
    }

    public function getIntegrationKey()
	{
        return Mage::getStoreConfig('payment/ebanx_settings/integration_key_' . $this->getMode());
    }

    public function getSandboxIntegrationKey()
	{
        return Mage::getStoreConfig('payment/ebanx_settings/integration_key_' . Ebanx_Gateway_Model_Source_Mode::SANDBOX);
    }

    public function getLiveIntegrationKey()
	{
        return Mage::getStoreConfig('payment/ebanx_settings/integration_key_' . Ebanx_Gateway_Model_Source_Mode::LIVE);
    }

    public function getDueDateDays()
	{
        return Mage::getStoreConfig('payment/ebanx_settings/due_date_days');
    }

    public function getDueDate()
	{
        $dueDate = new Zend_Date(Mage::getModel('core/date')->timestamp());
        return $dueDate->addDay($this->getDueDateDays())->get('YYYY-MM-dd HH:mm:ss');
    }

	public function transformCountryCodeToName($countryCode)
	{
        if (!$countryCode) {
            return false;
        }
        
		$countries = [
			'cl' => Country::CHILE,
			'br' => Country::BRAZIL,
			'co' => Country::COLOMBIA,
			'mx' => Country::MEXICO,
			'pe' => Country::PERU,
		];

		return $countries[strtolower($countryCode)];
	}
}
