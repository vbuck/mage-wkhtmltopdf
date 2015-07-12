<?php

/**
 * Module source abstract model.
 * 
 * PHP Version 5
 * 
 * @category  Class
 * @package   Vbuck_Wkhtmltopdf
 * @author    Rick Buczynski <me@rickbuczynski.com>
 * @copyright 2015 Rick Buczynski
 */

/**
 * Class declaration
 *
 * @category Class_Type_Model
 * @package  Vbuck_Wkhtmltopdf
 * @author   Rick Buczynski <me@rickbuczynski.com>
 */

class Vbuck_Wkhtmltopdf_Model_System_Config_Source_Abstract
{

    protected $_configPath = '';

    /**
     * Get all page size options.
     * 
     * @return array
     */
    public function getAllOptions()
    {
        $options = array();

        foreach ($this->toOptionArray() as $option) {
            $options[$option['value']] = $option['label'];
        }

        return $options;
    }

    /**
     * Get all page sizes as an option array.
     * 
     * @return array
     */
    public function toOptionArray()
    {
        $config     = Mage::getConfig()->getNode($this->_configPath);
        $options    = array();

        if ($config) {
            foreach ($config->children() as $child) {
                $value = $child->getName();

                if (isset($child->value)) {
                    $value = (string) $child->value;
                }

                $options[]  = array(
                    'value' => $value,
                    'label' => $value,
                );
            }
        }

        return $options;
    }

}