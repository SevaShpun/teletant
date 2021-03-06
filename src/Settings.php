<?php

namespace Askoldex\Teletant;


class Settings
{
    private $api_token = '';

    private $base_uri = 'https://api.telegram.org/';

    private $useHookReply = true;
    private $hookOnFirstRequest = true;

    private $proxy = '';

    public function __construct(string $api_token = null)
    {
        $this->setApiToken($api_token);
    }


    /**
     * @return string
     */
    public function getApiToken() : string
    {
        return $this->api_token;
    }

    /**
     * @param string $api_token
     * @return self
     */
    public function setApiToken(string $api_token) : self
    {
        $this->api_token = $api_token;
        return $this;
    }

    /**
     * @return string
     */
    public function getBaseUri(): string
    {
        return $this->base_uri;
    }

    /**
     * @param string $base_uri
     * @return self
     */
    public function setBaseUri(string $base_uri) : self
    {
        $this->base_uri = $base_uri;
        return $this;
    }

    /**
     * @return bool
     */
    public function isUseHookReply(): bool
    {
        return $this->useHookReply;
    }

    /**
     * @param bool $useHookReply
     * @return self
     */
    public function setUseHookReply(bool $useHookReply) : self
    {
        $this->useHookReply = $useHookReply;
        return $this;
    }

    /**
     * @return string
     */
    public function getProxy(): string
    {
        return $this->proxy;
    }

    /**
     * @param string $proxy
     * @return self
     */
    public function setProxy(string $proxy) : self
    {
        $this->proxy = $proxy;
        return $this;
    }

    /**
     * @return bool
     */
    public function isHookOnFirstRequest(): bool
    {
        return $this->hookOnFirstRequest;
    }

    /**
     * @param bool $hookOnFirstRequest
     */
    public function setHookOnFirstRequest(bool $hookOnFirstRequest)
    {
        $this->hookOnFirstRequest = $hookOnFirstRequest;
    }
}