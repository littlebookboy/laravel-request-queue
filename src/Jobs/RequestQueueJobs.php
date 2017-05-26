<?php

namespace App\Jobs;

use Exception;
use GuzzleHttp\Client as GuzzleHttpClient;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class RequestQueueJobs implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /* $uuid string 請求識別 id */
    protected $requestId;

    /* @var array $params 請求內容參數 */
    protected $params;

    /* @var array $params 請求導向路由 */
    protected $route;

    /* @var string $requestMethod 請求方法 */
    protected $requestMethod;

    /* @var string $callbackUrl 回呼位址 */
    protected $callbackUrl;

    /* @var string $callbackToken 回呼認證 token */
    protected $callbackToken;

    /**
     * 任務最大嘗試次數
     *
     * @var int
     */
    public $tries = 1;

    /**
     * 任務運行的超時時間
     *
     * @var int
     */
    public $timeout = 30;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(
        $requestId,
        $params,
        $route,
        $requestMethod
    ) {
        // 請求識別 id
        $this->requestId = $requestId;
        // 請求參數
        $this->params = $params;
        // 路由實體
        $this->route = $route;
        // 請求方法
        $this->requestMethod = $requestMethod;
        // callback 位址
        $this->callbackUrl = $params['_callback_url'];
        // callback 認證
        $this->callbackToken = $params['_callback_token'];
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        // 請求實際網址
        $requestUrl = config('request-queue.base_url') . '/' . $this->route;
        // 初始化 callback client
        $client = new GuzzleHttpClient();
        // 移除 callback 參數
        $this->unsetCallbackParams();
        // 發送實際請求
        $client->request($this->requestMethod, $requestUrl, $this->params);
        // callback 表頭
        $headers = ['X-Correlation-ID' => $this->requestId];
        // 發出 callback
        $client->request('POST', $this->callbackUrl, ['headers' => $headers]);
    }

    /**
     * 要處理的失敗任務。
     *
     * @param Exception $exception
     * @return void
     */
    public function failed(Exception $exception)
    {
        // 給用戶發送失敗通知，等等...
    }

    /**
     * 移除 callback 參數
     */
    public function unsetCallbackParams()
    {
        // 回呼位址
        unset($this->params['_callback_url']);
        // 回呼認證
        unset($this->params['_callback_token']);
    }
}
