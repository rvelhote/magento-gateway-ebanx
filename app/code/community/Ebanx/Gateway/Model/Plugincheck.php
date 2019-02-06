<?php

class Plugincheck extends Mage_Core_Model_Abstract
{
    /**
    * Retrieves the plugincheck list
    * @return array
    */
    public static function getPlugincheckList()
    {
        $list = array();
        $list['php'] = phpversion();
        $list['sql'] = self::getDBVersion();
        $list['plugins'] = self::getModules();
        $list['configs'] = self::getEbanxConfigs();
        return $list;
    }

    /**
    * Retrieves the installed community modules
    * @return array
    */
    public static function getModules()
    {
        $module_list = array();
        $modules = (array) Mage::getConfig()->getNode('modules')->children();
        foreach (array_keys($modules) as $module_name) {
            if ($modules[$module_name]->codePool == 'community') {
                $module_list[$module_name] = array(
                    'version' => (string)$modules[$module_name]->version,
                    'active'  => (string)$modules[$module_name]->active
                );
            }
        }
        return $module_list;
    }

    /**
    * Retrieves the DB version
    * @return mixed
    */
    public static function getDBVersion()
    {
        $resource = Mage::getSingleton('core/resource');
        $conn = $resource->getConnection('externaldb_read');
        return $conn->fetchCol('SELECT version() AS version')[0];
    }

    /**
    * Retrieves the EBANX module configs
    * @return array
    */
    private static function getEbanxConfigs()
    {
        $configs = Mage::getSingleton('ebanx/api')->getConfig();
        return array(
            'isSandbox'       => $configs->isSandbox,
            'baseCurrency'    => $configs->baseCurrency,
            'notificationUrl' => $configs->notificationUrl,
            'redirectUrl'     => $configs->redirectUrl,
            'taxesOnMerchant' => $configs->taxesOnMerchant,
        );
    }
}
