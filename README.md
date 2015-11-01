# Vbuck_Wkhtmltopdf

A wkhtmltopdf adapter module for Magento.

## Objective

To replace the Zend_Pdf "suite" of tools for generating sales-related PDF
documents in Magento. Also, to provide a very convenient method for quickly
generating PDF documents from HTML.

## Installation

Requires wkhtmltopdf binary for your system:

 > http://wkhtmltopdf.org/downloads.html

Example installation on CentOS 6:

```
yum install xorg-x11-fonts-75dpi
rpm -Uvh http://download.gna.org/wkhtmltopdf/0.12/0.12.2.1/wkhtmltox-0.12.2.1_linux-centos6-amd64.rpm
```

Then, deploy the Magento extension with modman:

```
cd /path/to/project/.modman
modman clone https://github.com/vbuck/mage-wkhtmltopdf.git
cd ../ && modman deploy Vbuck_Wkhthmltopdf
```

## Usage

To render directly to PDF content:

```php
/* @var $adapter Vbuck_Wkhtmltopdf_Model_Adapter */
$adapter = Mage::getModel('pdf/adapter');
$adapter->setContent('<h1>#realmagento</h1>')
    ->render();

$pdfContent = $adapter->getOutput();
```

Or save directly to file:

```php
/* @var $adapter Vbuck_Wkhtmltopdf_Model_Adapter */
$adapter = Mage::getModel('pdf/adapter');
$file    = $adapter
    ->setContent('<h1>#realmagento</h1>')
    ->toFile('test.pdf');

# Saved to media/pdf/test.pdf
```

## Notes

See `config.xml` for default adapter options, and to tune the location of your
wkhtmltopdf binary. Many things still could be built out (eg: `system.xml`,
cookie functionality tested, concept of pages, etc.), but out of the gate it
gives you enough power to get away from `Zend_Pdf`.

## License

The MIT License (MIT)

Copyright (c) 2015 Rick Buczynski.

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in
all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
THE SOFTWARE.
