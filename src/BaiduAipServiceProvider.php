<?php

namespace Yang\BaiduAip;

use Illuminate\Support\ServiceProvider;

class BaiduAipServiceProvider extends ServiceProvider {
    public function boot() {
        // 发布文件
        // $this->publishes([
        //                      \dirname(__DIR__).'/migrations/' => database_path('migrations'),
        //                  ], 'migrations');

        // if ($this->app->runningInConsole()) {
        //     $this->loadMigrationsFrom(\dirname(__DIR__).'/migrations/');
        // }
    }

    public function register() {
        // 合并配置文件
        $this->mergeConfigFrom(__DIR__ . '/config/baiduAip.php', 'baiduAip');

        // 单例注入容器
        $this->app->singleton(BaiduAipService::class, function($app) {
            return new BaiduAipService(config('baiduAip'));
        });

        // 注册别名
        $this->app->alias(BaiduAipService::class, 'baiduAip');
    }
    
    /**
     * @Desc 延迟注册
     * @return string[]
     * @Date 2023/6/6 10:30
     */
    public function provides()
    {
        return [BaiduAipService::class, 'baiduAip'];
    }
}