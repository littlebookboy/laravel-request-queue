<?php

namespace LittleBookBoy\Request\Queue\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Jobs\RequestQueueJobs;

class RequestQueueMiddleware
{
    /* X_CORRELATION_ID string $requestId Http 表頭名稱，內容為請求識別 id 名稱 */
    const X_CORRELATION_ID = 'X-Correlation-ID';

    /* $uuid string 請求識別 id */
    protected $requestId;

    /**
     * 排入隊列工作等候執行、設定回應狀態，若需回呼則設表頭 id，狀態 202，其他則繼續執行
     *
     * @param  Request  $request
     * @param  Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        // 判斷是否傳入 callback parameters，若是則設定回應 202 Accepted
        if ($this->isNeedCallback($request)) {
            // 將請求工作分派到隊列中
            $this->dispatchRequest($request);
            // 覆寫回應為 202 Accepted
            return Response()->make('', 202);
        }

        // 處理回應內容，將請求下丟
        $response = $next($request);
        // 寫入回應表頭 Headers，記錄請求識別 id
        $response->headers->add([self::X_CORRELATION_ID => $this->requestId]);
        // 結束請求處理
        return $response;
    }

    /**
     * 判斷請求是否需要回呼回應
     *
     * @param $request
     * @return bool
     */
    private function isNeedCallback($request)
    {
        // 傳入參數
        $params = $request->toArray();
        // 是否同時接收到回呼 token 以及位址
        // 檢查鍵是否被設置
        $isGotCallbackParams = (isset($params['_callback_url']) && isset($params['_callback_token']));
        // 檢查鍵對應的內容是否為空
        $isGotCallbackParams &= ($isGotCallbackParams)
            ? ($params['_callback_url'] != '' && $params['_callback_token'] != '')
            : false;
        // 需要判斷的方法，可增加在此陣列
        $methods = [
            'patch',
            'put',
            'delete',
        ];
        // 轉換字串為小寫
        $method = strtolower($request->getMethod());
        // 僅 methods 中的方法走回呼機制
        $isMethodMatched = (in_array($method, $methods));
        // 判斷是否要回呼回應，當方法符合條件，回呼參數都有收到，則 return true
        return $isGotCallbackParams && $isMethodMatched;
    }

    /**
     * 將一個請求工作分派到隊列中
     */
    private function dispatchRequest($request)
    {
        // 派送工作到隊列，傳入 request 參數與路由
        dispatch(
            (new RequestQueueJobs(
                // 請求識別 id
                $request->headers->get(self::X_CORRELATION_ID),
                // 請求參數
                $request->toArray(),
                // 請求原始路由
                $request->path(),
                // 請求方法
                $request->method()
            ))
            // 指定存放的資料庫 driver
            ->onConnection('redis')
            // 設定隊列名稱，在 redis 中隊對應的 key 名稱為 queues:api:request
            ->onQueue('api:request')
        );
    }
}
