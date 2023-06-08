<?php

namespace Aoding9\BaiduAip;

use Illuminate\Support\ServiceProvider;

class BaiduAipServiceProvider extends ServiceProvider {
    public function boot() {
        // 将配置文件发布到/config目录下
        $this->publishes([
                             __DIR__ . '/config/baiduAip.php' => config_path('baiduAip.php'),
                         ]);
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
    
        // 注册单例到容器，把config传进去
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
