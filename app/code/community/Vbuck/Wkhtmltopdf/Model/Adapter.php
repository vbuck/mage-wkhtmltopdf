<?php

/**
 * A wkhtmltopdf command line adapter model.
 * 
 * PHP Version 5
 *
 * Supported header/footer variables:
 * 
 * [page]       Replaced by the number of the pages currently being printed
 * [frompage]   Replaced by the number of the first page to be printed
 * [topage]     Replaced by the number of the last page to be printed
 * [webpage]    Replaced by the URL of the page being printed
 * [section]    Replaced by the name of the current section
 * [subsection] Replaced by the name of the current subsection
 * [date]       Replaced by the current date in system local format
 * [isodate]    Replaced by the current date in ISO 8601 extended format
 * [time]       Replaced by the current time in system local format
 * [title]      Replaced by the title of the of the current page object
 * [doctitle]   Replaced by the title of the output document
 * [sitepage]   Replaced by the number of the page in the current site being converted
 * [sitepages]  Replaced by the number of pages in the current site being converted
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

class Vbuck_Wkhtmltopdf_Model_Adapter
    extends Varien_Object
{

    const ORIENTATION_LANDSCAPE = 'Landscape';
    const ORIENTATION_PORTRAIT  = 'Portrait';

    /* @var $_assets array */
    protected $_assets          = array();

    /* @var $_cookies array */
    protected $_cookies         = array();

    /* @var $_document Vbuck_Wkhtmltopdf_Model_Document */
    protected $_document;

    /* @var $_errors array */
    protected $_errors          = array();

    /* @var $_options array */
    protected $_options         = array();

    /* @var $_output string */
    protected $_output          = '';

    /* @var $_pdfFilePath string */
    protected $_pdfFilePath;

    /* @var $_service Vbuck_Wkhtmltopdf_Model_Service */
    protected $_service;

    /**
     * Local constructor.
     * 
     * @return void
     */
    protected function _construct()
    {
        $this->_service = Mage::getModel('pdf/service');

        $this->_prepareDefaults();
    }

    /**
     * Add an error to the data store.
     * 
     * @param string|Exception $error The error details.
     *
     * @return Vbuck_Wkhtmltopdf_Model_Adapter
     */
    protected function _addError($error)
    {
        if ($error instanceof Exception) {
            $error = $error->getMessage();
        }

        $this->_errors[] = $error;

        return $this;
    }

    /**
     * Post-render handler.
     * 
     * @return Vbuck_Wkhtmltopdf_Model_Adapter
     */
    protected function _afterRender()
    {
        return $this;
    }

    /**
     * Pre-render handler.
     * 
     * @return Vbuck_Wkhtmltopdf_Model_Adapter
     */
    protected function _beforeRender()
    {
        return $this;
    }

    /**
     * Embed assets onto the document.
     * 
     * @return Vbuck_Wkhtmltopdf_Model_Adapter
     */
    protected function _embedAssets()
    {
        $head = current( ($this->_document->getElementsByTagName('head')) );

        foreach ($this->_assets as $asset) {
            $node = $this->_document->createDocumentFragment();

            $node->appendXml($asset);

            $head->appendChild($node);
        }
    }

    /**
     * Determine whether a font is supported.
     * 
     * @param string $font The font name to check.
     * 
     * @return boolean
     */
    protected function _isFontSupported($font)
    {
        $fonts = array_values( (Mage::getSingleton('pdf/system_config_source_font')->getAllOptions()) );

        return in_array($font, $fonts);
    }

    /**
     * Determine whether the orientation is supported.
     * 
     * @param string $orientation The orientation to check.
     * 
     * @return boolean
     */
    protected function _isOrientationSupported($orientation)
    {
        $orientations = array_values( (Mage::getSingleton('pdf/system_config_source_orientation')->getAllOptions()) );

        return in_array($orientation, $fonts);
    }

    /**
     * Determine whether a page size is supported.
     * 
     * @param string $size The size to check.
     * 
     * @return boolean
     */
    protected function _isPageSizeSupported($size)
    {
        $sizes = array_values( (Mage::getSingleton('pdf/system_config_source_pagesize')->getAllOptions()) );

        return in_array($size, $sizes);
    }

    /**
     * Prepare the cookie arguments.
     * 
     * @return Vbuck_Wkhtmltopdf_Model_Adapter
     */
    protected function _mergeCookies()
    {
        $values = array();

        foreach ($this->_cookies as $key => $value) {
            $values[] = '"' . addslashes($key) . '" "' . addslashes($value) . '"';
        }

        $this->setOption('cookie', $values);

        return $this;
    }

    /**
     * Prepare default adapter settings.
     * 
     * @return Vbuck_Wkhtmltopdf_Model_Adapter
     */
    protected function _prepareDefaults()
    {
        $this->setEncoding()
            ->setDocumentFooterFont()
            ->setDocumentHeaderFontSize()
            ->setDocumentFooterFont()
            ->setDocumentFooterFontSize()
            ->setMargin()
            ->setPageSize()
            ->setOutlineDepth()
            ->setUseSmartShrinking()
            ->setZoom()
            ->setTocHeaderText()
            ->setTocLevelIndentation()
            ->setTocTextSizeFactor()
            ->setUseToc()
            ->setViewportSize()
            ;

        return $this;
    }

    /**
     * Prepare the underlying DOM document.
     * 
     * @return Vbuck_Wkhtmltopdf_Model_Adapter
     */
    protected function _prepareDocument()
    {
        if (!$this->_document) {
            $className          = Mage::getConfig()->getModelClassName('pdf/document');
            $this->_document    = new $className(null, $this->getOption('encoding'));
        }

        return $this;
    }

    /**
     * PDF render implementation.
     * 
     * @return Vbuck_Wkhtmltopdf_Model_Adapter
     */
    protected function _render()
    {
        @$this->_document->loadHTML($this->getContent());

        $this->_embedAssets();

        $input = $this->_document->saveHTML();

        $this->_output = $this->_service
            ->command($this->_options, $input);

        return $this;
    }

    /**
     * Validate adapter settings.
     * 
     * @return Vbuck_Wkhtmltopdf_Model_Adapter
     */
    protected function _validateOptions()
    {
        return $this;
    }

    /**
     * Add a cookie to the request.
     * 
     * @param string $key   The cookie name.
     * @param string $value The cookie value.
     */
    public function addCookie($key, $value)
    {
        $this->_cookies[$key] = $value;

        return $this;
    }

    /**
     * Dynamically include a local asset.
     *
     * Supported Types:
     *  - js
     *  - js_css
     *  - skin_js
     *  - skin_css
     * 
     * @param string $type The item type.
     * @param string $name The file name.
     */
    public function addItem($type, $name)
    {
        $assetType  = null;
        $dirType    = null;
        $contents   = null;

        switch ($type) {
            case 'js' :
                $assetType  = 'JavaScript';
                $dirType    = 'base';
                break;
            case 'js_css' :
                $assetType  = 'Stylesheet';
                $dirType    = 'base';
                break;
            case 'skin_js' :
                $assetType  = 'JavaScript';
                $dirType    = 'skin';
                break;
            case 'skin_css' :
                $assetType  = 'Stylesheet';
                $dirType    = 'skin';

        }

        if ($dirType == 'skin') {
            $path = Mage::getDesign()->getSkinBaseDir() . DS . str_replace('/', DS, $name);
        } else {
            $path = Mage::getBaseDir($dirType). DS . 'js' . DS . $name;
        }

        if (is_readable($path)) {
            $contents = @file_get_contents($path);
        }

        if (!$contents) {
            $this->_addError(sprintf('File not loaded: "%s"', $path));
        } else {
            call_user_func(array($this, "add{$assetType}"), $contents);
        }

        return $this;
    }

    /**
     * Add raw JavaScript to the document.
     * 
     * @param string $script The JavaScript code to execute in the document.
     *
     * @return Vbuck_Wkhtmltopdf_Model_Adapter
     */
    public function addJavaScript($script)
    {
        // Dirty-check for presence of container
        if (strcasecmp(substr($script, 0, 7), '<script') !== 0) {
            $script = '<script type="text/javascript">' . $script . '</script>';
        }

        $this->_assets[] = $script;

        return $this;
    }

    /**
     * Add raw CSS to the document.
     * 
     * @param string $css The CSS code to execute in the document.
     *
     * @return Vbuck_Wkhtmltopdf_Model_Adapter
     */
    public function addStylesheet($css)
    {
        // Dirty-check for presence of container
        if (strcasecmp(substr($css, 0, 6), '<style') !== 0) {
            $css = '<style type="text/css">' . $css . '</style>';
        }

        $this->_assets[] = $css;

        return $this;
    }

    /**
     * Clear all queued assets.
     * 
     * @return Vbuck_Wkhtmltopdf_Model_Adapter
     */
    public function clearAssets()
    {
        $this->_assets = array();

        return $this;
    }

    /**
     * Clear all queued cookies.
     * 
     * @return Vbuck_Wkhtmltopdf_Model_Adapter
     */
    public function clearCookies()
    {
        $this->_cookies = array();

        return $this;
    }

    /**
     * Get the errors collection.
     * 
     * @return array
     */
    public function getErrors()
    {
        return $this->_errors;
    }

    /**
     * Get a process option value.
     * 
     * @param string $key The option key.
     * 
     * @return string|null
     */
    public function getOption($key)
    {
        if (isset($this->_options[$key])) {
            return $this->_options[$key];
        }

        return null;
    }

    /**
     * Get the collection of process options.
     * 
     * @return array
     */
    public function getOptions()
    {
        return $this->_options;
    }

    /**
     * Get the rendered output.
     * 
     * @return string|null
     */
    public function getOutput()
    {
        return $this->_output;
    }

    /**
     * Get the wkhtmltopdf version.
     * 
     * @return string|null
     */
    public function getVersion()
    {
        try {
            preg_match('/\d+(?:\.\d+)+/', $this->_service->command('-V', null, '2>&1'), $matches);

            return $matches[0];
        } catch (Exception $error) { }

        return null;
    }

    /**
     * Determine whether the last run process has errors.
     * 
     * @return boolean
     */
    public function hasErrors()
    {
        return count($this->_errors) > 0;
    }

    /**
     * Remove a queued cookie.
     * 
     * @param string $key The cookie name.
     * 
     * @return Vbuck_Wkhtmltopdf_Model_Adapter
     */
    public function removeCookie($key)
    {
        if (isset($this->_cookies[$key])) {
            unset($this->_cookies[$key]);
        }

        return $this;
    }

    /**
     * Render the PDF document.
     * 
     * @return string|null
     */
    final public function render()
    {
        Mage::dispatchEvent('pdf_adapter_render_start', array('adapter' => $this));

        $this->_validateOptions();

        if (!empty($this->_cookies)) {
            $this->_mergeCookies();
        }

        try {
            $this->_beforeRender();

            $this->_prepareDocument();

            $this->_render();
            $this->_afterRender();

            $output = $this->getOutput();
        } catch (Exception $error) {
            $output = null;

            $this->_addError($error);
        }

        Mage::dispatchEvent(
            'pdf_adapter_render_end', 
            array(
                'adapter' => $this,
                'content' => $output,
            )
        );

        return $output;
    }

    /**
     * Remove a process option.
     * 
     * @param string $key The option key.
     * 
     * @return Vbuck_Wkhtmltopdf_Model_Adapter
     */
    public function removeOption($key)
    {
        if (isset($this->_options[$key])) {
            unset($this->_options[$key]);
        }

        return $this;
    }

    /**
     * Set the background print flag.
     * 
     * @param boolean $flag
     *
     * @return Vbuck_Wkhtmltopdf_Model_Adapter
     */
    public function setAllowBackround($flag = true)
    {
        if ($flag === true) {
            $this->removeOption('no-background')
                ->setOption('background', true);
        } else {
            $this->removeOption('background')
                ->setOption('no-background', true);
        }

        return $this;
    }

    /**
     * Set the external link flag.
     * 
     * @param boolean $flag
     *
     * @return Vbuck_Wkhtmltopdf_Model_Adapter
     */
    public function setAllowExternalLinks($flag = true)
    {
        if ($flag === true) {
            $this->removeOption('disable-external-links')
                ->setOption('enable-external-links', true);
        } else {
            $this->removeOption('enable-external-links')
                ->setOption('disable-external-links', true);
        }

        return $this;
    }

    /**
     * Set the form field conversion flag.
     * 
     * @param boolean $flag
     *
     * @return Vbuck_Wkhtmltopdf_Model_Adapter
     */
    public function setAllowFormFields($flag = true)
    {
        if ($flag === true) {
            $this->removeOption('disable-forms')
                ->setOption('enable-forms', true);
        } else {
            $this->removeOption('enable-forms')
                ->setOption('disable-forms', true);
        }

        return $this;
    }

    /**
     * Set the image processing flag.
     * 
     * @param boolean $flag
     *
     * @return Vbuck_Wkhtmltopdf_Model_Adapter
     */
    public function setAllowImages($flag = true)
    {
        if ($flag === true) {
            $this->removeOption('no-images')
                ->setOption('images', true);
        } else {
            $this->removeOption('images')
                ->setOption('no-images', true);
        }

        return $this;
    }

    /**
     * Set the internal link flag.
     * 
     * @param boolean $flag
     *
     * @return Vbuck_Wkhtmltopdf_Model_Adapter
     */
    public function setAllowInternalLinks($flag = true)
    {
        if ($flag === true) {
            $this->removeOption('disable-internal-links')
                ->setOption('enable-internal-links', true);
        } else {
            $this->removeOption('enable-internal-links')
                ->setOption('disable-internal-links', true);
        }

        return $this;
    }

    /**
     * Set the JavaScript processing flag.
     * 
     * @param boolean $flag
     *
     * @return Vbuck_Wkhtmltopdf_Model_Adapter
     */
    public function setAllowJavaScript($flag = true)
    {
        if ($flag === true) {
            $this->removeOption('disable-javascript')
                ->setOption('enable-javascript', true);
        } else {
            $this->removeOption('enable-javascript')
                ->setOption('disable-javascript', true);
        }

        return $this;
    }

    /**
     * Set the document DPI.
     * 
     * @param int $value The DPI value.
     */
    public function setDocumentDpi(int $value)
    {
        $this->setOption('dpi', $value);

        return $this;
    }

    /**
     * Set the document encoding.
     * 
     * @param string $value The value type.
     *
     * @return Vbuck_Wkhtmltopdf_Model_Adapter
     */
    public function setDocumentEncoding($value = null)
    {
        if (is_null($value)) {
            $value = Mage::getStoreConfig('pdf/adapter/encoding');
        }

        $this->setOption('encoding', $value);

        return $this;
    }

    /**
     * Set the document footer content.
     * 
     * @param string $contentLeft   The left content.
     * @param string $contentCenter The center content.
     * @param string $contentRight  The right content.
     */
    public function setDocumentFooter($contentLeft = '', $contentCenter = '', $contentRight = '')
    {
        $this->setOption('footer-left', $contentLeft)
            ->setOption('footer-center', $contentCenter)
            ->setOption('footer-right', $contentRight);

        return $this;
    }

    /**
     * Set the document footer font.
     * 
     * @param string $font The font name.
     *
     * @return Vbuck_Wkhtmltopdf_Model_Adapter
     */
    public function setDocumentFooterFont($font = null)
    {
        if (!$this->_isFontSupported($value)) {
            $this->_addError(sprintf('Font "%s" not supported, using default.', $value));

            $value = Mage::getStoreConfig('pdf/adapter/footer_font');
        }

        $this->setOption('footer-font-name', $value);

        return $this;
    }

    /**
     * Set the document footer font size.
     * 
     * @param int $size The font size in points.
     *
     * @return Vbuck_Wkhtmltopdf_Model_Adapter
     */
    public function setDocumentFooterFontSize(int $size)
    {
        if (!$size) {
            $size = (int) Mage::getStoreConfig('pdf/adapter/footer_font_size');
        }

        $this->setOption('footer-font-size', $size);

        return $this;
    }

    /**
     * Set the document footer spacing value.
     * 
     * @param float $value The spacing amount in mm.
     *
     * @return Vbuck_Wkhtmltopdf_Model_Adapter
     */
    public function setDocumentFooterSpacing(float $value)
    {
        $this->setOption('footer-spacing', $value);

        return $this;
    }

    /**
     * Set the document header content.
     * 
     * @param string $contentLeft   The left content.
     * @param string $contentCenter The center content.
     * @param string $contentRight  The right content.
     */
    public function setDocumentHeader($contentLeft = '', $contentCenter = '', $contentRight = '')
    {
        $this->setOption('header-left', $contentLeft)
            ->setOption('header-center', $contentCenter)
            ->setOption('header-right', $contentRight);

        return $this;
    }

    /**
     * Set the document header font.
     * 
     * @param string $font The font name.
     *
     * @return Vbuck_Wkhtmltopdf_Model_Adapter
     */
    public function setDocumentHeaderFont($font = null)
    {
        if (!$this->_isFontSupported($value)) {
            $this->_addError(sprintf('Font ""%s"" not supported, using default.', $value));

            $value = Mage::getStoreConfig('pdf/adapter/header_font');
        }

        $this->setOption('header-font-name', $value);

        return $this;
    }

    /**
     * Set the document header font size.
     * 
     * @param int $size The font size in points.
     *
     * @return Vbuck_Wkhtmltopdf_Model_Adapter
     */
    public function setDocumentHeaderFontSize(int $size)
    {
        if (!$size) {
            $size = (int) Mage::getStoreConfig('pdf/adapter/header_font_size');
        }

        $this->setOption('header-font-size', $size);

        return $this;
    }

    /**
     * Set the document header spacing value.
     * 
     * @param float $value The spacing amount in mm.
     *
     * @return Vbuck_Wkhtmltopdf_Model_Adapter
     */
    public function setDocumentHeaderSpacing(float $value)
    {
        $this->setOption('header-spacing', $value);

        return $this;
    }

    /**
     * Set the document title.
     * 
     * @param string $value The document title.
     *
     * @return Vbuck_Wkhtmltopdf_Model_Adapter
     */
    public function setDocumentTitle($value)
    {
        $this->setOption('title', $value);

        return $this;
    }

    /**
     * Explicitly set the image DPI.
     * 
     * @param int $value The DPI value.
     *
     * @return Vbuck_Wkhtmltopdf_Model_Adapter
     */
    public function setImageDpi(int $value)
    {
        if (!$value) {
            $value = (int) Mage::getStoreConfig('pdf/adapter/image_dpi');
        }

        $this->setOption('image-dpi', $value);

        return $this;
    }

    /**
     * Explicitly set the image quality.
     * 
     * @param int $value The quality value from 1-100.
     *
     * @return Vbuck_Wkhtmltopdf_Model_Adapter
     */
    public function setImageQuality(int $value)
    {
        if (!$value) {
            $value = (int) Mage::getStoreConfig('pdf/adapter/image_quality');
        }

        $this->setOption('image-quality', $value);

        return $this;
    }

    /**
     * Set the grayscale rendering flag.
     * 
     * @param boolean $flag
     *
     * @return Vbuck_Wkhtmltopdf_Model_Adapter
     */
    public function setIsGrayscale($flag = true)
    {
        if ($flag === true) {
            $this->setOption('grayscale', true);
        } else {
            $this->removeOption('grayscale');
        }

        return $this;
    }

    /**
     * Set the landscape orientation flag.
     * 
     * @param boolean $flag
     *
     * @return Vbuck_Wkhtmltopdf_Model_Adapter
     */
    public function setIsLandscape($flag = true)
    {
        if (!$flag) {
            $this->setIsPortrait(true);
        } else {
            $this->setOrientation(self::ORIENTATION_LANDSCAPE);
        }

        return $this;
    }

    /**
     * Set the portrait orientation flag.
     * 
     * @param boolean $flag
     *
     * @return Vbuck_Wkhtmltopdf_Model_Adapter
     */
    public function setIsPortrait($flag = true)
    {
        if (!$flag) {
            $this->setIsLandscape(true);
        } else {
            $this->setOrientation(self::ORIENTATION_PORTRAIT);
        }
        
        return $this;
    }

    /**
     * Set the post-render JavaScript execution delay.
     * 
     * @param int $value The delay value.
     *
     * @return Vbuck_Wkhtmltopdf_Model_Adapter
     */
    public function setJavaScriptDelay(int $value)
    {
        if (is_null($value)) {
            $value = Mage::getStoreConfig('pdf/adapter/javascript_delay');
        }

        $this->setOption('javascript-delay', $value);

        return $this;
    }

    /**
     * Set the low quality mode flag.
     * 
     * @param boolean $flag
     *
     * @return Vbuck_Wkhtmltopdf_Model_Adapter
     */
    public function setLowQualityMode($flag = true)
    {
        if ($flag === true) {
            $this->setOption('lowquality', true);
        } else {
            $this->removeOption('lowquality');
        }

        return $this;
    }

    /**
     * Set the page margins.
     * 
     * @param string $top    The top margin value.
     * @param string $right  The right margin value.
     * @param string $bottom The bottom margin value.
     * @param string $left   The left margin value.
     *
     * @return Vbuck_Wkhtmltopdf_Model_Adapter
     */
    public function setMargin($top = null, $right = null, $bottom = null, $left = null)
    {
        $this->setMarginTop($top)
            ->setMarginRight($right)
            ->setMarginBottom($bottom)
            ->setMarginLeft($left);

        return $this;
    }

    /**
     * Set the page margin bottom amount.
     * 
     * @param string $bottom The bottom margin value.
     *
     * @return Vbuck_Wkhtmltopdf_Model_Adapter
     */
    public function setMarginBottom($value = null)
    {
        if (is_null($value)) {
            $value = Mage::getStoreConfig('pdf/adapter/margin_bottom');
        }

        if (strlen($value) > 0) {
            $this->setOption('margin-bottom', $value);
        }

        return $this;
    }

    /**
     * Set the page margin left amount.
     * 
     * @param string $value The left margin value.
     *
     * @return Vbuck_Wkhtmltopdf_Model_Adapter
     */
    public function setMarginLeft($value = null)
    {
        if (is_null($value)) {
            $value = Mage::getStoreConfig('pdf/adapter/margin_left');
        }

        if (strlen($value) > 0) {
            $this->setOption('margin-left', $value);
        }

        return $this;
    }

    /**
     * Set the page margin right amount.
     * 
     * @param string $value The right margin value.
     *
     * @return Vbuck_Wkhtmltopdf_Model_Adapter
     */
    public function setMarginRight($value = null)
    {
        if (is_null($value)) {
            $value = Mage::getStoreConfig('pdf/adapter/margin_right');
        }

        if (strlen($value) > 0) {
            $this->setOption('margin-right', $value);
        }

        return $this;
    }

    /**
     * Set the page margin top amount.
     * 
     * @param string $value The top margin value.
     *
     * @return Vbuck_Wkhtmltopdf_Model_Adapter
     */
    public function setMarginTop($value = null)
    {
        if (is_null($value)) {
            $value = Mage::getStoreConfig('pdf/adapter/margin_top');
        }

        if (strlen($value) > 0) {
            $this->setOption('margin-top', $value);
        }

        return $this;
    }

    /**
     * Set the CSS media-type value.
     * 
     * @param string $value The type value.
     *
     * @return Vbuck_Wkhtmltopdf_Model_Adapter
     */
    public function setMediaType($value)
    {
        if ($value == 'print') {
            $this->removeOption('no-print-media-type');
        } else if ($value == 'screen') {
            $this->setOption('no-print-media-type', true);
        } else {
            $this->_addError(sprintf('Media type "%s" not supported.', $value));
        }

        return $this;
    }

    /**
     * Set the minimum document font size.
     * 
     * @param int $value The font size in points.
     *
     * @return Vbuck_Wkhtmltopdf_Model_Adapter
     */
    public function setMinFontSize(int $value)
    {
        $this->setOption('minimum-font-size', $value);

        return $this;
    }

    /**
     * Set the number of document copies to render.
     * 
     * @param integer $value The number of copies.
     *
     * @return Vbuck_Wkhtmltopdf_Model_Adapter
     */
    public function setNumOfCopies($value = 0)
    {
        $this->setOption('copies', $value);

        return $this;
    }

    /**
     * Set a process option.
     * 
     * @param string $key   The option key.
     * @param mixed  $value The option value.
     *
     * @return Vbuck_Wkhtmltopdf_Model_Adapter
     */
    public function setOption($key, $value)
    {
        $this->_options[$key] = $value;

        return $this;
    }

    /**
     * Set the page orientation.
     * 
     * @param string $value The orientation (landscape|portrait).
     *
     * @return Vbuck_Wkhtmltopdf_Model_Adapter
     */
    public function setOrientation($value)
    {
        if (!$this->_isOrientationSupported($value)) {
            $value = Mage::getStoreConfig('pdf/adapter/orientation');

            $this->_addError(sprintf('orientation "%s" not supported, using default.', $value));
        }

        $this->setOption('orientation', $value);

        return $this;
    }

    /**
     * Set the document outline depth.
     * 
     * @param int|null $value The depth value.
     *
     * @return Vbuck_Wkhtmltopdf_Model_Adapter
     */
    public function setOutlineDepth(int $value = null)
    {
        if (is_null($vaue)) {
            $value = (int) Mage::getStoreConfig('pdf/adapter/outline_depth');
        }

        if ($value === 0) {
            $this->setUseOutline(false);
        } else {
            $this->setUseOutline(true);
        }

        $this->setOption('outline-depth', $value);

        return $this;
    }

    /**
     * Explicitly set the page height.
     * 
     * @param string $value The page height value.
     *
     * @return Vbuck_Wkhtmltopdf_Model_Adapter
     */
    public function setPageHeight($value)
    {
        if (!$this->_isPageSizeSupported('Custom')) {
            return $this->_addError('Custom page sizing is not allowed.');
        }

        $this->setPageSize('Custom')
            ->setOption('page-height', $value);

        return $this;
    }

    /**
     * Set the page numbering offset.
     * 
     * @param int $value The offset value.
     *
     * @return Vbuck_Wkhtmltopdf_Model_Adapter
     */
    public function setPageOffset(int $value)
    {
        $this->setOption('page-offset', $value);

        return $this;
    }

    /**
     * Set the page size.
     * 
     * @param string $value The page size code.
     *
     * @return Vbuck_Wkhtmltopdf_Model_Adapter
     */
    public function setPageSize($value)
    {
        if (!$this->_isPageSizeSupported($value)) {
            $this->_addError(sprintf('Page size "%s" not supported, using default.', $value));

            $value = Mage::getStoreConfig('pdf/adapter/page_size');
        }

        if ($value != 'Custom') {
            $this->removeOption('page-height')
                ->removeOption('page-width');
        }

        $this->setOption('page-size', $value);

        return $this;
    }

    /**
     * Explicitly set the page width.
     * 
     * @param string $value The page width value.
     *
     * @return Vbuck_Wkhtmltopdf_Model_Adapter
     */
    public function setPageWidth($value)
    {
        if (!$this->_isPageSizeSupported('Custom')) {
            return $this->_addError('Custom page sizing is not allowed.');
        }

        $this->setPageSize('Custom')
            ->setOption('page-width', $value);

        return $this;
    }

    /**
     * Set the table of contents heading text.
     * 
     * @param string $value The heading text.
     *
     * @return Vbuck_Wkhtmltopdf_Model_Adapter
     */
    public function setTocHeaderText($value = null)
    {
        if (is_null($value)) {
            $value = Mage::getStoreConfig('pdf/adapter/toc_header_text');
        }

        $this->setOption('toc-header-text', $value);

        return $this;
    }

    /**
     * Set the table of contents indentation amount.
     * 
     * @param string $value The indentation amount.
     *
     * @return Vbuck_Wkhtmltopdf_Model_Adapter
     */
    public function setTocLevelIndentation($value = null)
    {
        if (is_null($value)) {
            $value = Mage::getStoreConfig('pdf/adapter/toc_level_indentation');
        }

        $this->setOption('toc-level-indentation', $value);

        return $this;
    }

    /**
     * Set the table of contents text size factor.
     * 
     * @param float|null $value The text size factor.
     *
     * @return Vbuck_Wkhtmltopdf_Model_Adapter
     */
    public function setTocTextSizeFactor(float $value = null)
    {
        if (is_null($value)) {
            $value = Mage::getStoreConfig('pdf/adapter/toc_text_size_factor');
        }

        $this->setOption('toc-text-size-shrink', $value);

        return $this;
    }

    /**
     * Set the page collation flag.
     * 
     * @param boolean $flag
     *
     * @return Vbuck_Wkhtmltopdf_Model_Adapter
     */
    public function setUseCollation($flag = true)
    {
        if ($flag === true) {
            $this->removeOption('no-collate')
                ->setOption('collate', true);
        } else {
            $this->removeOption('collate')
                ->setOption('no-collate', true);
        }

        return $this;
    }

    /**
     * Set the PDF compression flag.
     * 
     * @param boolean $flag
     *
     * @return Vbuck_Wkhtmltopdf_Model_Adapter
     */
    public function setUseCompression($flag = true)
    {
        if ($flag === true) {
            $this->removeOption('no-pdf-compression');
        } else {
            $this->setOption('no-pdf-compression', true);
        }

        return $this;
    }

    /**
     * Set the footer line flag.
     * 
     * @param boolean $flag
     *
     * @return Vbuck_Wkhtmltopdf_Model_Adapter
     */
    public function setUseFooterLine($flag = true)
    {
        if ($flag === true) {
            $this->removeOption('no-footer-line')
                ->setOption('footer-line', true);
        } else {
            $this->removeOption('footer-line')
                ->setOption('no-footer-line', true);
        }

        return $this;
    }

    /**
     * Set the header line flag.
     * 
     * @param boolean $flag
     *
     * @return Vbuck_Wkhtmltopdf_Model_Adapter
     */
    public function setUseHeaderLine($flag = true)
    {
        if ($flag === true) {
            $this->removeOption('no-header-line')
                ->setOption('header-line', true);
        } else {
            $this->removeOption('header-line')
                ->setOption('no-header-line', true);
        }

        return $this;
    }

    /**
     * Set the document outline flag.
     * 
     * @param boolean $flag
     *
     * @return Vbuck_Wkhtmltopdf_Model_Adapter
     */
    public function setUseOutline($flag = true)
    {
        if ($flag === true) {
            $this->removeOption('no-outline')
                ->setOption('outline', true);
        } else {
            $this->removeOption('outline')
                ->setOption('no-outline', true);
        }

        return $this;
    }

    /**
     * Set the smart shrinking flag.
     * 
     * @param boolean $flag
     *
     * @return Vbuck_Wkhtmltopdf_Model_Adapter
     */
    public function setUseSmartShrinking($flag = null)
    {
        if (is_null($flag)) {
            $flag = Mage::getStoreConfigFlag('pdf/adapter/smart_shrinking');
        }

        if ($flag === true) {
            $this->removeOption('disable-smart-shrinking')
                ->setOption('enable-smart-shrinking', true);
        } else {
            $this->removeOption('enable-smart-shrinking')
                ->setOption('disable-smart-shrinking', true);
        }

        return $this;
    }

    /**
     * Set the table of contents generation flag.
     * 
     * @param boolean $flag
     *
     * @return Vbuck_Wkhtmltopdf_Model_Adapter
     */
    public function setUseToc($flag = null)
    {
        if (is_null($flag)) {
            $flag = Mage::getStoreConfigFlag('pdf/adapter/use_toc');
        }

        if ($flag !== true) {
            $this->removeOption('toc-header-text')
                ->removeOption('toc-level-indentation')
                ->removeOption('disable-toc-back-links')
                ->removeOption('toc-text-size-shrink');
        }

        return $this;
    }

    /**
     * Set the table of contents back-linking flag.
     * 
     * @param boolean $flag
     *
     * @return Vbuck_Wkhtmltopdf_Model_Adapter
     */
    public function setUseTocBackLinks($flag = true)
    {
        if (is_null($flag)) {
            $flag = Mage::getStoreConfigFlag('pdf/adapter/toc_back_links');
        }

        if ($flag === true) {
            $this->removeOption('disable-toc-back-links')
                ->setOption('enable-toc-back-links', true);
        } else {
            $this->removeOption('enable-toc-back-links')
                ->setOption('disable-toc-back-links', true);
        }

        return $this;
    }

    /**
     * Set the table of contents linking flag.
     * 
     * @param boolean $flag
     *
     * @return Vbuck_Wkhtmltopdf_Model_Adapter
     */
    public function setUseTocLinks($flag = true)
    {
        if (is_null($flag)) {
            $flag = Mage::getStoreConfigFlag('pdf/adapter/toc_links');
        }

        if ($flag === true) {
            $this->removeOption('disable-toc-back-links');
        } else {
            $this->setOption('disable-toc-back-links', true);
        }

        return $this;
    }

    /**
     * Explicitly the viewport size.
     * 
     * @param string $value The viewport in WxH format.
     *
     * @return Vbuck_Wkhtmltopdf_Model_Adapter
     */
    public function setViewportSize($value = null)
    {
        if (is_null($value)) {
            $value = Mage::getStoreConfig('pdf/adapter/viewport_size');
        }

        $this->setOption('viewport-size', $value);

        return $this;
    }

    /**
     * Set the page zoom factor.
     * 
     * @param float $value The zoom factor value.
     *
     * @return Vbuck_Wkhtmltopdf_Model_Adapter
     */
    public function setZoom(float $value = null)
    {
        if (is_null($value)) {
            $value = Mage::getStoreConfig('pdf/adapter/zoom');
        }

        $this->setOption('zoom', $value);

        return $this;
    }

    /**
     * Render and save the PDF to a file.
     * 
     * @return string
     */
    public function toFile($fileName = null, $regenerate = false)
    {
        if (!$this->_pdfFilePath || $regenerate) {
            $this->render();

            if ( !($output = $this->getOutput()) ) {
                return null;
            }

            $file       = new Varien_Io_File();
            $basePath   = Mage::getBaseDir() . DS . Mage::getStoreConfig('pdf/adapter/pdf_storage');
            $filePath   = $basePath . DS . ($fileName ? $fileName : ( md5( (string) microtime(true) ) . '.pdf') );

            $file->setAllowCreateFolders(true)
                ->open(array('path' => $basePath));

            if (!is_writable($basePath)) {
                $this->_addError(sprintf('Directory "%s" is not writeable.', $basePath));

                return null;
            }

            $file->streamOpen($filePath, 'w', 0755);

            $file->streamWrite($output);

            $file->streamClose();

            $this->_pdfFilePath = $filePath;
        }

        return $this->_pdfFilePath;
    }

}