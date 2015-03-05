<?php

namespace Simplon\Form;

use Simplon\Form\Elements\CoreElementInterface;
use Simplon\Form\Interfaces\ArrayElementInterface;

class Form
{
    /**
     * @var array
     */
    private $requestData = [];

    /**
     * @var array
     */
    private $elementValues = [];

    /**
     * @var string
     */
    private $id = 'simplon-form';

    /**
     * @var string
     */
    private $url = '';

    /**
     * @var string
     */
    private $method = 'POST';

    /**
     * @var string
     */
    private $acceptCharset = 'utf-8';

    /**
     * @var bool
     */
    private $useCsrf = true;

    /**
     * @var string
     */
    private $csrfName;

    /**
     * @var string
     */
    private $csrfValue;

    /**
     * @var bool
     */
    private $isValidCsrf = false;

    /**
     * @var null|bool
     */
    private $isValid = null;

    /**
     * @var CoreElementInterface[]
     */
    private $elements = [];

    /**
     * @var string
     */
    private $generalErrorMessage = '<strong>Validation failed.</strong> Have a look at the error notes below.';

    /**
     * @var array
     */
    private $assetFiles = [];

    /**
     * @var array
     */
    private $assetInlines = [];

    /**
     * @var string
     */
    private $urlRootAssets;

    /**
     * @var array
     */
    private $attributes = [];

    /**
     * @param array $requestData
     */
    public function __construct($requestData = [])
    {
        // start session for csrf
        if (!session_id())
        {
            session_start();
        }

        // set data
        $this->setRequestData($requestData);

        // set base assets
        $this->addAssetFiles([
            'default.css',
        ]);
    }

    /**
     * @param string $key
     * @param string $val
     *
     * @return static
     */
    public function addAttribute($key, $val)
    {
        $this->attributes[$key] = $val;

        return $this;
    }

    /**
     * @param array $attrs
     *
     * @return static
     */
    public function setAttributes(array $attrs)
    {
        $this->attributes = $attrs;

        return $this;
    }

    /**
     * @return array
     */
    public function getAttributes()
    {
        return $this->attributes;
    }

    /**
     * @param boolean $useCsrf
     *
     * @return Form
     */
    public function setUseCsrf($useCsrf)
    {
        $this->useCsrf = $useCsrf === true;

        return $this;
    }

    /**
     * @param string $urlRootAssets
     *
     * @return Form
     */
    public function setUrlRootAssets($urlRootAssets)
    {
        $this->urlRootAssets = rtrim($urlRootAssets, '/');

        return $this;
    }

    /**
     * @param string $generalErrorMessage
     *
     * @return Form
     */
    public function setGeneralErrorMessage($generalErrorMessage)
    {
        $this->generalErrorMessage = $generalErrorMessage;

        return $this;
    }

    /**
     * @return string
     */
    public function getGeneralErrorMessage()
    {
        return (string)$this->generalErrorMessage;
    }

    /**
     * @return string
     */
    public function renderGeneralErrorMessage()
    {
        $template = '<div class="alert alert-danger">:value</div>';

        return (string)str_replace(':value', $this->generalErrorMessage, $template);
    }

    /**
     * @param $id
     *
     * @return Form
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @param $url
     *
     * @return Form
     */
    public function setUrl($url)
    {
        $this->url = $url;

        return $this;
    }

    /**
     * @param $charset
     *
     * @return Form
     */
    public function setCharset($charset)
    {
        $this->acceptCharset = $charset;

        return $this;
    }

    /**
     * @param $method
     *
     * @return Form
     */
    public function setMethod($method)
    {
        $this->method = $method;

        return $this;
    }

    /**
     * @return array|CoreElementInterface[]
     */
    public function getElements()
    {
        return $this->elements;
    }

    /**
     * @param CoreElementInterface $element
     *
     * @return Form
     */
    public function addElement(CoreElementInterface $element)
    {
        // element knows how to read/set its request value
        $element->setPostValueByRequestData($this->getRequestData());

        // run element setup to initiate assets and whatnot
        $element->setup();

        // handle assets
        $this->addAssetFiles($element->getAssetFiles());
        $this->addAssetInlines($element->getAssetInlines());

        // collect elements
        $this->elements[] = $element;

        return $this;
    }

    /**
     * @param CoreElementInterface[] $elements
     *
     * @return Form
     */
    public function setElements(array $elements)
    {
        foreach ($elements as $element)
        {
            $this->addElement($element);
        }

        return $this;
    }

    /**
     * @return array
     */
    public function getElementValues()
    {
        if (empty($this->elementValues))
        {
            $elements = $this->getElements();

            foreach ($elements as $element)
            {
                if ($element instanceof ArrayElementInterface)
                {
                    $this->elementValues = $element->getElementValues($this->elementValues);
                }
                else
                {
                    $this->elementValues[$element->getId()] = $element->getValue();
                }
            }

        }

        return $this->elementValues;
    }

    /**
     * @return bool
     */
    public function isValid()
    {
        // handle csrf
        $this->handleCsrf();

        if ($this->isSubmitted() && $this->isValid === null)
        {
            // iterate through all elements
            foreach ($this->getElements() as $element)
            {
                if ($element->isValid() === false)
                {
                    $this->isValid = false;
                }
            }

            $this->isValid = $this->isValid !== false;
        }

        return $this->isValid;
    }

    /**
     * @param $template
     *
     * @return string
     */
    public function addFormAndAssetsTags($template)
    {
        $form = "<form {{attributes}}>\n\n{{internalFields}}\n\n{{template}}\n\n{{css}}\n\n{{js}}</form>";

        // set internal fields
        $internalFields = [
            '<input type="hidden" name="hide-' . $this->getId() . '" value="1">',
            '<input type="hidden" name="hide-' . $this->getCsrfName() . '" value="' . $this->getCsrfValue() . '">',
        ];

        // set attributes
        $attributes = [
            'role'           => 'form',
            'id'             => $this->getId(),
            'action'         => $this->getUrl(),
            'method'         => $this->getMethod(),
            'accept-charset' => $this->getCharset(),
            'enctype'        => 'multipart/form-data',
            'class'          => 'simplon-form',
        ];

        // merge attributes with outter ones
        $attributes = array_merge($attributes, $this->getAttributes());

        // set values
        $renderedAttributes = [];

        foreach ($attributes as $key => $value)
        {
            $renderedAttributes[] = $key . '="' . $value . '"';
        }

        // render attributes
        $form = str_replace('{{attributes}}', join(' ', $renderedAttributes), $form);

        // render internal fields
        $form = str_replace('{{internalFields}}', join("\n", $internalFields), $form);

        // render css asset
        $form = str_replace('{{css}}', $this->renderHeaderAssets(), $form);

        // render js asset
        $form = str_replace('{{js}}', $this->renderBodyAssets(), $form);

        // render template
        $form = str_replace('{{template}}', $template, $form);

        return $form;
    }

    /**
     * @return string
     */
    public function renderHeaderAssets()
    {
        return $this->renderAssets(['css'], '<link rel="stylesheet" href="{{url}}">');
    }

    /**
     * @return string
     */
    public function renderBodyAssets()
    {
        // render includes
        $content = $this->renderAssets(['js'], '<script src="{{url}}" type="text/javascript"></script>');

        // domready + inline scripts
        $domReadyFunction = "var DOMReady = function(a,b,c){b=document,c='addEventListener';b[c]?b[c]('DOMContentLoaded',a):window.attachEvent('onload',a)};";
        $domReadyCallback = "DOMReady(function () {\n" . join(";\n", $this->getAssetInlines()) . ";\n});";
        $content .= "\n\n<script type=\"text/javascript\">\n\n// SIMPLON FORM - INLINE HANDLINGS\n\n{$domReadyFunction}\n\n{$domReadyCallback}\n\n</script>\n\n";

        return $content;
    }

    /**
     * @return array
     */
    protected function getRequestData()
    {
        return (array)$this->requestData;
    }

    /**
     * @return bool
     */
    private function hasRequestData()
    {
        return empty($this->requestData) === false;
    }

    /**
     * @param array $requestData
     *
     * @return Form
     */
    private function setRequestData(array $requestData)
    {
        $this->requestData = $requestData;

        return $this;
    }

    /**
     * @return string
     */
    private function getId()
    {
        return (string)$this->id;
    }

    /**
     * @return string
     */
    private function getUrl()
    {
        return (string)$this->url;
    }

    /**
     * @return string
     */
    private function getCharset()
    {
        return (string)$this->acceptCharset;
    }

    /**
     * @return string
     */
    private function getMethod()
    {
        return (string)$this->method;
    }

    /**
     * @param int $length
     *
     * @return string
     */
    private function createRandomToken($length = 12)
    {
        $randomString = '';
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';

        // generate token
        for ($i = 0; $i < $length; $i++)
        {
            $randomString .= $characters[rand(0, strlen($characters) - 1)];
        }

        return $randomString;
    }

    /**
     * @return string
     */
    private function getCsrfSessionKey()
    {
        return 'CSRF_'.$this->getId();
    }

    /**
     * @return void
     */
    private function handleCsrf()
    {
        // validate csrf
        $this->validateCsrfTokens();

        // render tokens
        $this->renderCsrfTokens();

        // cache as session
        $_SESSION[$this->getCsrfSessionKey()] = [
            'name'  => $this->getCsrfName(),
            'value' => $this->getCsrfValue(),
        ];
    }

    /**
     * @return bool
     */
    private function validateCsrfTokens()
    {
        if ($this->getUseCsrf() === false)
        {
            $this->isValidCsrf = true;

            return true;
        }

        // --------------------------------------

        $process =
            $this->hasRequestData() === true
            && isset($_SESSION[$this->getCsrfSessionKey()])
            && isset($_SESSION[$this->getCsrfSessionKey()]['name'])
            && isset($_SESSION[$this->getCsrfSessionKey()]['value'])
            && isset($this->requestData['hide-' . $_SESSION[$this->getCsrfSessionKey()]['name']]);

        if ($process)
        {
            // test value
            if ($_SESSION[$this->getCsrfSessionKey()]['value'] === $this->requestData['hide-' . $_SESSION[$this->getCsrfSessionKey()]['name']])
            {
                $this->isValidCsrf = true;
            }
        }

        return true;
    }

    /**
     * @return void
     */
    private function renderCsrfTokens()
    {
        $this
            ->setCsrfName($this->createRandomToken(32))
            ->setCsrfValue($this->createRandomToken(32));
    }

    /**
     * @param string $csrfName
     *
     * @return Form
     */
    private function setCsrfName($csrfName)
    {
        $this->csrfName = $csrfName;

        return $this;
    }

    /**
     * @return string
     */
    private function getCsrfName()
    {
        return $this->csrfName;
    }

    /**
     * @param string $csrfValue
     *
     * @return Form
     */
    private function setCsrfValue($csrfValue)
    {
        $this->csrfValue = $csrfValue;

        return $this;
    }

    /**
     * @return string
     */
    private function getCsrfValue()
    {
        return $this->csrfValue;
    }

    /**
     * @return bool
     */
    private function isSubmitted()
    {
        return
            $this->hasRequestData() === true // any request data at all?
            && isset($this->requestData['hide-' . $this->getId()]) // has this form been submitted?
            && $this->isValidCsrf === true; // csrf must match
    }

    /**
     * @param array $assetFiles
     *
     * @return $this
     */
    private function addAssetFiles(array $assetFiles)
    {
        foreach ($assetFiles as $file)
        {
            if (in_array($file, $this->assetFiles) === false)
            {
                $this->assetFiles[] = $file;
            }
        }

        return $this;
    }

    /**
     * @return array
     */
    private function getAssetFiles()
    {
        return $this->assetFiles;
    }

    /**
     * @param array $assetInlines
     *
     * @return Form
     */
    private function addAssetInlines(array $assetInlines)
    {
        foreach ($assetInlines as $inline)
        {
            $this->assetInlines[] = $inline;
        }

        return $this;
    }

    /**
     * @return array
     */
    private function getAssetInlines()
    {
        return $this->assetInlines;
    }

    /**
     * @param array $types
     * @param $pattern
     *
     * @return string
     */
    private function renderAssets(array $types, $pattern)
    {
        $assets = [];

        foreach ($this->getAssetFiles() as $file)
        {
            $parts = explode('.', $file);
            $fileExtension = strtolower(array_pop($parts));

            if (in_array($fileExtension, $types) === true)
            {
                $url = $this->getUrlRootAssets() . '/simplon-form/' . trim($file, '/');
                $assets[] = str_replace('{{url}}', $url, $pattern);
            }
        }

        return join("\n\n", $assets);
    }

    /**
     * @return string
     */
    private function getUrlRootAssets()
    {
        return $this->urlRootAssets;
    }

    /**
     * @return boolean
     */
    private function getUseCsrf()
    {
        return $this->useCsrf;
    }
}