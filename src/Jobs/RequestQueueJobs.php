<?php

namespace App\Jobs;

use Carbon\Carbon;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Log;

class RequestQueueJobs implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /* @var array $params 請求內容參數 */
    protected $params;

    /* @var array $params 請求導向路由 */
    protected $route;

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
    public function __construct($params, $route)
    {
        $this->params = $params;
        $this->route = $route;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        // 處理請求
        Log::info(Carbon::now());
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
}
