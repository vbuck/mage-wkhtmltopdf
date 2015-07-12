<?php

/**
 * wkhtmltopdf service model.
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

class Vbuck_Wkhtmltopdf_Model_Service
{

    /* @var $_cookieFile string */
    private $_cookieFile;

    /* @var $_cookiePointer resource */
    private $_cookiePointer;

    /* @var $_lastError string */
    private $_lastError;

    /* @var $_lastOutput string */
    private $_lastOutput;
    
    /* @var $_pipes array */
    private $_pipes;

    /* @var $_process resource */
    private $_process;

    /* @var $_useCookies boolean */
    private $_useCookies = false;

    /* @var $_argumentSort array */
    protected $_argumentSort = array(
        'collate'                   => 'global',
        'no-collate'                => 'global',
        'cookie-jar'                => 'global',
        'copies'                    => 'global',
        'dpi'                       => 'global',
        'grayscale'                 => 'global',
        'image-dpi'                 => 'global',
        'image-quality'             => 'global',
        'lowquality'                => 'global',
        'margin-bottom'             => 'global',
        'margin-left'               => 'global',
        'margin-right'              => 'global',
        'margin-top'                => 'global',
        'orientation'               => 'global',
        'page-height'               => 'global',
        'page-size'                 => 'global',
        'page-width'                => 'global',
        'no-pdf-compression'        => 'global',
        'title'                     => 'global',
        'outline'                   => 'global',
        'no-outline'                => 'global',
        'outline-depth'             => 'global',
        'background'                => 'global',
        'no-background'             => 'global',
        'cookie'                    => 'global',
        'encoding'                  => 'global',
        'disable-external-links'    => 'global',
        'enable-external-links'     => 'global',
        'disable-internal-links'    => 'global',
        'enable-internal-links'     => 'global',
        'disable-javascript'        => 'global',
        'enable-javascript'         => 'global',
        'javascript-delay'          => 'global',
        'minimum-font-size'         => 'global',
        'page-offset'               => 'global',
        'print-media-type'          => 'global',
        'no-print-media-type'       => 'global',
        'disable-toc-back-links'    => 'global',
        'enable-toc-back-links'     => 'global',
        'viewport-size'             => 'global',
        'zoom'                      => 'global',
        'footer-center'             => 'global',
        'footer-font-name'          => 'global',
        'footer-font-size'          => 'global',
        'footer-left'               => 'global',
        'footer-line'               => 'global',
        'no-footer-line'            => 'global',
        'footer-right'              => 'global',
        'footer-spacing'            => 'global',
        'header-center'             => 'global',
        'header-font-name'          => 'global',
        'header-font-size'          => 'global',
        'header-left'               => 'global',
        'header-line'               => 'global',
        'no-header-line'            => 'global',
        'header-right'              => 'global',
        'header-spacing'            => 'global',
        'toc-header-text'           => 'toc',
        'toc-level-indentation'     => 'toc',
        'disable-toc-links'         => 'toc',
        'toc-text-size-shrink'      => 'toc',
    );

    /**
     * Attempt to close a cookie jar.
     * 
     * @return Vbuck_Wkhtmltopdf_Model_Service
     */
    private function _closeCookies()
    {
        if ($this->_cookiePointer && $this->_cookieFile) {
            @fclose($this->_cookiePointer);

            Mage::dispatchEvent(
                'whtmltopdf_service_cookie_close',
                array(
                    'object' => $this,
                    'cookie' => file_get_contents($this->_cookieFile)
                )
            );

            @unlink($this->_cookieFile);

            $this->_cookiePointer   = null;
            $this->_cookieFile      = null;
        }

        return $this;
    }

    /**
     * Write output and end the commanded process.
     * 
     * @param array &$descriptor The declared file descriptor.
     * 
     * @return void
     */
    private function _endProcess()
    {
        $tmp  = (string) stream_get_contents($this->_pipes[0]);
        $this->_lastOutput  = (string) stream_get_contents($this->_pipes[1]);
        $this->_lastError   = (string) stream_get_contents($this->_pipes[2]);

        if ($this->_lastError) {
            throw new Exception($this->_lastError);
        }

        foreach ($this->_pipes as $pipe) {
            fclose($pipe);
        }

        proc_close($this->_process);
    }

    /**
     * Get the current working directory.
     * 
     * @return null|string
     */
    private function _getCwd()
    {
        $cwd = dirname($this->_getPath());

        return in_array($cwd, array('', '.')) ? null : $cwd;
    }

    /**
     * Get the process descriptors.
     * 
     * @return array
     */
    private function _getDescriptorSpec()
    {
        return array(
            0 => array('pipe', 'r'),
            1 => array('pipe', 'w'),
            2 => array('pipe', 'r'), // no write to STDERR
        );
    }

    /**
     * Get runtime environment variables.
     * 
     * @return array
     */
    private function _getEnv()
    {
        return array();
    }

    /**
     * Get the path to wkhtmltopdf.
     * 
     * @return string
     */
    private function _getPath()
    {
        if ( !($path = Mage::getStoreConfig('pdf/service/path')) ) {
            $path = 'wkhtmltopdf';
        }

        return $path;
    }

    /**
     * Get the temporary storage path.
     * 
     * @return string
     */
    private function _getTmp()
    {
        if ( !($path = Mage::getStoreConfig('pdf/service/tmp')) ) {
            $path = '/tmp';
        }

        return $path;
    }

    /**
     * Attempt to open a cookie jar for storage.
     * 
     * @return Vbuck_Wkhtmltopdf_Model_Service
     */
    private function _openCookies()
    {
        if ($this->_useCookies) {
            // User-defined cookies
            if ($this->_cookieFile) {
                $path = $this->_cookieFile;
            } else {
                $path = dirname($this->_getTmp()) . DS . md5((string) microtime(true)) . '.txt';

                $this->_cookiePointer = @touch($path);
                @chmod($path, 0777);
            }

            $this->_cookieFile      = $path;
            $this->_cookiePointer   = @fopen($this->_cookieFile, 'r+');

            if (!$this->_cookiePointer) {
                throw new Exception('Could not open cookie jar for writing.');
            }

            fseek($this->_cookiePointer, 0, SEEK_END);
        }

        return $this;
    }

    /**
     * Prepare the given arguments for command line usage.
     * 
     * @param array $arguments The collection of arguments.
     * 
     * @return string
     */
    private function _prepareArguments($arguments)
    {
        $preparedArgs = array(
            'global'    => array(),
            'page'      => array(),
            'toc'       => array(),
        );

        if (is_string($arguments)) {
            return ' ' . $arguments;
        }

        foreach ($arguments as $key => $values) {
            $group = isset($this->_argumentSort[$key]) ? $this->_argumentSort[$key] : 'global';

            if (is_array($values)) {
                $preparedValues = array();

                foreach ($values as $value) {
                    $preparedValues[] = '--' . $key . ' ' . (string) $value;
                }

                $preparedArgs[$group][] = implode(' ', $preparedValues);
            } else if ($values === true) {
                $preparedArgs[$group][] = "--{$key}"; 
            } else {
                $preparedArgs[$group][] = '--' . $key . ' "' . addslashes((string) $values) . '"';
            }
        }

        $finalArgs = array();

        foreach ($preparedArgs as $group => $args) {
            if (!count($args)) {
                continue;
            }

            if ($group != 'global') {
                $finalArgs[] = $group;
            }

            $finalArgs = array_merge($finalArgs, $args);
        }

        return ( count($finalArgs) > 0 ? ' ' : '' ) . implode(' ', $finalArgs);
    }

    /**
     * Open a new process.
     * 
     * @param string $command The command to execute.
     * 
     * @return void
     */
    private function _startProcess($command)
    {
        $this->_process = proc_open(
            $command,
            $this->_getDescriptorSpec(),
            $this->_pipes,
            $this->_getCwd(),
            $this->_getEnv()
        );

        if ($this->_process === false) {
            throw new Exception('Process could not be opened.');
        }
    }

    /**
     * Pass a command to wkhtmltopdf.
     * 
     * @param string|array $arguments The arguments to pass.
     * @param string       $input     The input to pass as STDIN.
     * @param string       $routing   Optionally specify output control.
     * 
     * @return string
     */
    public function command($arguments, $input = null, $routing = '>&1')
    {
        if (isset($arguments['cookie'])) {
            $this->_useCookies = true;
        }

        $arguments  = $this->_prepareArguments($arguments);
        $command    = "{$this->_getPath()}{$arguments} - - {$routing}";

        $this->_openCookies();

        $this->_startProcess($command);
        
        $this->input($input);

        $this->_closeCookies();
        $this->_endProcess();

        return $this->_lastOutput;
    }

    /**
     * Pass input as STDIN to the open process.
     * 
     * @param string   $content The content to pass.
     * 
     * @return Vbuck_Wkhtmltopdf_Model_Service
     */
    public function input($content)
    {
        if (is_resource($this->_process) && $content) {
            fwrite($this->_pipes[0], $content);
            fclose($this->_pipes[0]);
        }

        return $this;
    }

    /**
     * Specify a cookie file for read/write operations.
     * 
     * @param string $path The path to the cookie file.
     * 
     * @return Vbuck_Wkhtmltopdf_Model_Service
     */
    public function useCookieFile($path)
    {
        if (!is_writable($path)) {
            throw new Exception('User-defined cookie file is not writeable.');
        }

        $this->_cookieFile = $path;
        $this->_useCookies = true;

        return $this;
    }

}