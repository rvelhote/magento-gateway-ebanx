<?php

class Ebanx_Gateway_Model_Mexico_Spei extends Ebanx_Gateway_Payment
{
    protected $_code = 'ebanx_spei';

    protected $_formBlockType = 'ebanx/form_spei';
    protected $_infoBlockType = 'ebanx/info_spei';

    /**
     * Ebanx_Gateway_Model_Mexico_Spei constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->gateway = $this->ebanx->spei();
    }

    /**
     * @param null $quote unused
     *
     * @return bool
     */
    public function isAvailable($quote = null)
    {
        return parent::isAvailable() && in_array($this->getCode(), explode(',', $this->configs['payment_methods_mexico']));
    }
}
