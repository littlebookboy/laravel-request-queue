<?php

namespace LittleBookBoy\Request\Queue;

use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;
use LittleBookBoy\Request\Queue\Middleware\RequestQueueMiddleware;
use LittleBookBoy\Request\Recorder\Middleware\RequestRecorderMiddleware;

class RequestQueueServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot(Router $router)
    {
        // 設定發佈
        $this->publisher();
        // 啟用請求回應記錄器中介層
        $router->pushMiddlewareToGroup('api', RequestRecorderMiddleware::class);
        // 啟用請求隊列工作推送中介層
        $router->pushMiddlewareToGroup('api', RequestQueueMiddleware::class);
        // config 指定的隊列驅動
        $driver = config('request-queue.driver');
        // 設定隊列資料庫驅動
        config(['queue.default' => $driver]);
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
    }

    /**
     * 設定發佈路徑
     */
    private function publisher()
    {
        // only accept publish request in command line
        if ($this->app->runningInConsole())
        {
            // 自訂記錄器配置檔
            $this->publishes([
                __DIR__ . '/resources/config/request-queue.php' => config_path('request-queue.php')
            ]);
        }
    }
}
