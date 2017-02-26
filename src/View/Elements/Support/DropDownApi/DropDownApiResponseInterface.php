<?php

namespace Simplon\Form\View\Elements\Support\DropDownApi;

/**
 * @package Simplon\Form\View\Elements\Support\DropDownApi
 */
interface DropDownApiResponseInterface
{
    /**
     * @return string
     */
    public function getResultItemsKey(): string;

    /**
     * @return string
     */
    public function getLabel(): string;

    /**
     * @return string
     */
    public function getRemoteId(): string;
}