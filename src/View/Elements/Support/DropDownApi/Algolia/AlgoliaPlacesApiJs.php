<?php

namespace Simplon\Form\View\Elements\Support\DropDownApi\Algolia;

use Simplon\Form\View\Elements\Support\DropDownApi\DropDownApiJsInterface;
use Simplon\Form\View\Elements\Support\DropDownApi\DropDownApiResponseDataInterface;

/**
 * @package Simplon\Form\View\Elements\Support\DropDownApi\Algolia
 * @see https://community.algolia.com/places/rest.html
 */
class AlgoliaPlacesApiJs implements DropDownApiJsInterface
{
    const TYPE_CITY = 'city';
    const TYPE_COUNTRY = 'country';
    const TYPE_ADDRESS = 'address';
    const TYPE_BUSSTOP = 'busStop';
    const TYPE_TRAINSTATION = 'trainStation';
    const TYPE_TOWNHALL = 'townhall';
    const TYPE_AIRPORT = 'airport';

    /**
     * @var string
     */
    private $apiKey;
    /**
     * @var string
     */
    private $appId;
    /**
     * @var string
     */
    private $language = 'en';
    /**
     * @var array
     */
    private $countries = [];
    /**
     * @var string
     */
    private $type;
    /**
     * @var DropDownApiResponseDataInterface
     */
    private $onResponseHandler;

    /**
     * @return string
     */
    public function getApiKey(): string
    {
        return $this->apiKey;
    }

    /**
     * @param string $apiKey
     *
     * @return AlgoliaPlacesApiJs
     */
    public function setApiKey(string $apiKey): AlgoliaPlacesApiJs
    {
        $this->apiKey = $apiKey;

        return $this;
    }

    /**
     * @return string
     */
    public function getAppId(): string
    {
        return $this->appId;
    }

    /**
     * @param string $appId
     *
     * @return AlgoliaPlacesApiJs
     */
    public function setAppId(string $appId): AlgoliaPlacesApiJs
    {
        $this->appId = $appId;

        return $this;
    }

    /**
     * @return string
     */
    public function getLanguage(): string
    {
        return $this->language;
    }

    /**
     * @param string $language
     *
     * @return AlgoliaPlacesApiJs
     */
    public function setLanguage(string $language): AlgoliaPlacesApiJs
    {
        $this->language = $language;

        return $this;
    }

    /**
     * @return null|string
     */
    public function getType(): ?string
    {
        return $this->type;
    }

    /**
     * @param string $type
     *
     * @return AlgoliaPlacesApiJs
     */
    public function setType(string $type): AlgoliaPlacesApiJs
    {
        $this->type = $type;

        return $this;
    }

    /**
     * @return array|null
     */
    public function getCountries(): ?array
    {
        return $this->countries;
    }

    /**
     * @param string $countryCode
     *
     * @return AlgoliaPlacesApiJs
     */
    public function addCountries(string $countryCode)
    {
        $this->countries[] = $countryCode;

        return $this;
    }

    /**
     * @param array $countries
     *
     * @return AlgoliaPlacesApiJs
     */
    public function setCountries(array $countries)
    {
        $this->countries = $countries;

        return $this;
    }

    /**
     * @return string
     */
    public function getMethod(): string
    {
        return 'POST';
    }

    /**
     * @return string
     */
    public function getUrl(): string
    {
        return 'https://places-dsn.algolia.net/1/places/query';
    }

    /**
     * @return null|string
     */
    public function renderBeforeXHRJsString(): ?string
    {
        if ($this->apiKey && $this->appId)
        {
            return "xhr.setRequestHeader('X-Algolia-API-Key', " . $this->getApiKey() . "); xhr.setRequestHeader ('X-Algolia-Application-Id', " . $this->getAppId() . ")";
        }

        return null;
    }

    /**
     * @return string
     */
    public function renderBeforeSendJsString(): string
    {
        return
            "settings.data = JSON.stringify({"
            . "query: settings.urlData.query.replace(/str\./, 'straße')," // updated for German address handling
            . "language: \"" . $this->getLanguage() . "\","
            . "type: \"" . $this->getType() . "\","
            . "countries: " . json_encode($this->getCountries())
            . "})";
    }

    /**
     * @return DropDownApiResponseDataInterface
     */
    public function getOnResponse(): DropDownApiResponseDataInterface
    {
        if (!$this->onResponseHandler)
        {
            $this->onResponseHandler = new AlgoliaPlacesApiResponseData();
        }

        return $this->onResponseHandler;
    }

    /**
     * @param DropDownApiResponseDataInterface $onResponseHandler
     *
     * @return AlgoliaPlacesApiJs
     */
    public function setOnResponseHandler(DropDownApiResponseDataInterface $onResponseHandler): AlgoliaPlacesApiJs
    {
        $this->onResponseHandler = $onResponseHandler;

        return $this;
    }
}