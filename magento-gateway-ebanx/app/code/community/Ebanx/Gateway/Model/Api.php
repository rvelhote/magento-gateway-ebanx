<?php
require_once Mage::getBaseDir('lib') . '/Ebanx/vendor/autoload.php';

use Ebanx\Benjamin\Models\Configs\Config;
use Ebanx\Benjamin\Models\Configs\CreditCardConfig;

class Ebanx_Gateway_Model_Api
{
    protected $benjamin;

    public function __construct()
	{
        $config = new Config(array(
            'integrationKey'        => Mage::helper('ebanx')->getLiveIntegrationKey(),
            'sandboxIntegrationKey' => Mage::helper('ebanx')->getSandboxIntegrationKey(),
            'isSandbox'             => Mage::helper('ebanx')->isSandboxMode(),
            'baseCurrency'          => Mage::app()->getStore()->getCurrentCurrencyCode(),
            'notificationUrl'       => Mage::getBaseUrl(),
            'redirectUrl'           => Mage::getBaseUrl(),
        ));
        // Mage::log($config, null, 'benjamin-config.log', true);
        // $creditCardConfig = new CreditCardConfig();
        // $creditCardConfig->addInterest(1,0.2);
        // $this->benjamin = EBANX($config, $creditCardConfig);

        $this->benjamin = EBANX($config);
    }

	public function ebanx()
	{
		return $this->benjamin;
	}
}
