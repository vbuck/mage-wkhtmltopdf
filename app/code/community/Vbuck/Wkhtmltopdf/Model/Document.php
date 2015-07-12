<?php

/**
 * DOM document intermediate model.
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

class Vbuck_Wkhtmltopdf_Model_Document
    extends DOMDocument
{

    public function __construct($version = '1.0', $encoding = 'UTF-8')
    {
        parent::__construct($version, $encoding);
    }

}