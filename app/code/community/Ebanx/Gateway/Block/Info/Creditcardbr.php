<?php

class Ebanx_Gateway_Block_Info_Creditcardbr extends Ebanx_Gateway_Block_Info_Abstract
{
    /**
     * @return Ebanx_Gateway_Block_Info_Creditcardbr
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('ebanx/info/creditcard_br.phtml');
        if ($this->isAdmin()) {
            $this->setTemplate('ebanx/info/default.phtml');
        }
    }
}
