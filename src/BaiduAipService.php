<?php
/**
 * @Desc
 * @User yangyang
 * @Date 2023/5/4 10:44
 */

namespace Aoding9\BaiduAip;


class BaiduAipService {
    public array $config;
    public ?string $appId;
    public ?string $apiKey;
    public ?string $secretKey;
    protected array $guzzleOptions = [];
    protected $aipFace;

    public function __construct($config = []) {
        $this->config = $config;
        // dd($this);
    }

    protected function getAppId(): ?string {
        return $this->appId ?? $this->appId = $this->getConfig('app_id');
    }

    protected function getApiKey(): ?string {
        return $this->apiKey ?? $this->apiKey = $this->getConfig('api_key');
    }

    protected function getSecretKey(): ?string {
        return $this->secretKey ?? $this->secretKey = $this->getConfig('secret_key');
    }

    public function aipFace() {
        return $this->aipFace ?? $this->aipFace = new AipFace($this->getAppId(), $this->getApiKey(), $this->getSecretKey());
    }

    public function getConfig($key){
        return $this->config[$key]??null;
    }
}
