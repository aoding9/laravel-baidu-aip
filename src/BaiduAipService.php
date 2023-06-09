<?php
/**
 * @Desc
 * @User yangyang
 * @Date 2023/5/4 10:44
 */

namespace Aoding9\BaiduAip;


class BaiduAipService {
    public $config;
    public  $appId;
    public  $apiKey;
    public  $secretKey;
    protected $guzzleOptions = [];
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
    
    /**
     * @Desc 获取或初始化aipFace实例
     * @return AipFace
     * @Date 2023/6/7 10:37
     */
    public function aipFace() {
        return $this->aipFace ?? $this->aipFace = new AipFace($this->getAppId(), $this->getApiKey(), $this->getSecretKey());
    }

    public function getConfig($key){
        return $this->config[$key]??null;
    }
}
