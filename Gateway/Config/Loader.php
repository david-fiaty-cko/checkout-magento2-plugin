<?php

namespace CheckoutCom\Magento2\Gateway\Config;

use Magento\Framework\Module\Dir;

class Loader
{
    const CONFIGURATION_FILE_NAME = 'config.xml';
    const APM_FILE_NAME = 'apm.xml';
    const KEY_MODULE_NAME = 'CheckoutCom_Magento2';
    const KEY_MODULE_ID = 'checkoutcom_magento2';
    const KEY_PAYMENT = 'payment';
    const KEY_SETTINGS = 'settings';
    const KEY_CONFIG = 'checkoutcom_configuration';

    /**
     * @var Dir
     */
    protected $moduleDirReader;

    /**
     * @var Parser
     */
    protected $xmlParser;

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var EncryptorInterface
     */
    protected $encryptor;

    /**
     * @var Reader
     */
    protected $directoryReader;

    /**
     * @var Array
     */
    protected $xmlData;

    /**
     * Loader constructor
     */
    public function __construct(
        \Magento\Framework\Module\Dir\Reader $moduleDirReader,
        \Magento\Framework\Xml\Parser $xmlParser,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Encryption\EncryptorInterface $encryptor,
        \Magento\Framework\Module\Dir\Reader $directoryReader
    ) {
        $this->moduleDirReader = $moduleDirReader;
        $this->xmlParser = $xmlParser;
        $this->scopeConfig = $scopeConfig;
        $this->storeManager = $storeManager;
        $this->encryptor = $encryptor;
    	$this->directoryReader = $directoryReader;

        $this->data = $this->loadConfig();
    }

    protected function loadConfig() {
        try {
            // Prepare the output array
            $output = [];

            // Load the xml data
            $this->xmlData = $this->loadXmlData();
    
            // Build the config data array
            foreach ($this->xmlData['config'] as $parent => $child) {
                foreach ($child as $group => $arr) {
                    // Loop through values for the payment method
                    foreach ($arr as $key => $val) {
                        if (!$this->isHidden($key)) {
                            // Get the field  value in db
                            $path = $parent . '/' . $group . '/' . $key;
                            $value = $this->scopeConfig->getValue(
                                $path,
                                \Magento\Store\Model\ScopeInterface::SCOPE_STORE
                            );

                            // Process encrypted fields
                            if ($this->isEncrypted($key)) {
                                $value = $this->encryptor->decrypt($value);
                            } 

                            // Add the final value to the config array
                            $output[$parent][$group][$key] = $value;
                        }
                    }
                }
            }

            // Load the APM list
            $output['settings']['checkoutcom_configuration']['apm_list'] = $this->loadApmList();

            return $output;
        }
        catch(\Exception $e) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __(
                    "The module configuration file can't be loaded" . " - "
                    . $e->getMessage()
                )
            );          
        }
    }

    protected function loadApmList() {
        // Build the APM array
        $output = [];
        foreach ($this->xmlData['apm'] as $row) {
            $output[] = [
                'value' => $row['id'],
                'label' => __($row['title'])
            ];
        }

        return $output;
    }

    protected function getFilePath($fileName) {
        return $this->moduleDirReader->getModuleDir(
            Dir::MODULE_ETC_DIR,
            self::KEY_MODULE_NAME
        ) . '/' . $fileName;
    }

    protected function loadXmlData() {
        // Prepare the output array
        $output = [];

        // Load config.xml
        $output['config'] = $this->xmlParser
        ->load($this->getFilePath(self::CONFIGURATION_FILE_NAME))
        ->xmlToArray()['config']['_value']['default'];

        // Load apm.xml
        $output['apm'] = $this->xmlParser
        ->load($this->getFilePath(self::APM_FILE_NAME))
        ->xmlToArray()['config']['_value']['item'];

        return $output;
    }

    protected function isHidden($field) {
        $hiddenFields = explode(
            ',',
            $this->scopeConfig->getValue(
                'settings/checkoutcom_configuration/fields_hidden',
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE
            )
        );

        return in_array($field, $hiddenFields);
    }

    protected function isEncrypted($field) {
        return in_array(
            $field,
            explode(
                ',',
                $this->scopeConfig->getValue(
                    'settings/checkoutcom_configuration/fields_encrypted',
                    \Magento\Store\Model\ScopeInterface::SCOPE_STORE
                )
            )
        );
    }

    public function getValue($key, $methodId = null) {
        // Prepare the path
        $path = ($methodId) 
        ? 'payment/' . $methodId  . '/' .  $key
        : 'settings/checkoutcom_configuration/' . $key;

        // Get field value in database
        $value = $this->scopeConfig->getValue(
            $path,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );

        // Return a decrypted value for encrypted fields
        if ($this->isEncrypted($key)) {
            return $this->encryptor->decrypt($value);
        }

        // Return a normal value
        return $value;
    }
}
