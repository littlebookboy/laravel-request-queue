## Queue

說明

提供一個簡單實現將 request 派送到隊列，並使用 Redis (或 [MySQL](https://laravel.com/docs/master/queues)) 
資料庫儲存隊列工作。隊列工作的監聽則透過 supervisord，來輔助背景監聽。

## 安裝

使用 composer 安裝套件 
```
composer require littlebookboy/laravel-request-queue
```

註冊服務提供者
```
LittleBookBoy\Request\Queue\RequestQueueServiceProvider::class,
```

發佈
```
php artisan vendor:publish
```

## MySQL
```
php artisan queue:table

php artisan migrate
```

## Redis

兩種資料庫擇一使用，本專案預設使用 Redis

### 本機環境

安裝 Redis Server，在此以 Mac 為例，可以參考官網的 
[installing-redis-on-mac-os-x](http://jasdeep.ca/2012/05/installing-redis-on-mac-os-x/) 安裝
```
brew update && brew install redis
```

啟用 Redis Server
```
redis-server
```

背景執行可用
```
redis-server --daemonize yes
```

確認啟動了 Redis Server，使用此指令來列出目前關於 redis-server 常駐的命令
```
ps aux | grep redis-server
```

啟動 CLI，會看到預設的 redis connection 是 127.0.0.1:6379
```
redis-cli

127.0.0.1:6379> 
```

### 專案環境

因為我是用 MAMP 環境，所以必須讓他的 PHP 支援 Redis，可以參考這個說明去安裝 
[panxianhai/php-redis-mamp](https://github.com/panxianhai/php-redis-mamp)，
裡面也有直接放好該有的 redis.so，不用自己去 [php.net](http://php.net/) 手動編譯出來。
修改 php.ini，若是 MAMP Pro，打開 php.ini 可以在 ```File > Edit Template > PHP > PHP 7.0.8 php.ini``` 找到。
```
[redis]
extension="redis.so"
```

重啟 MAMP 後打開 phpinfo 看，有出現 redis 表示 MAMP 中的 PHP 庫已支援 Redis，
而 Laravel 已經有一套很完整的機制可以讓你快速連結到你本機的 Redis，在這個專案中，
我使用的是 [predis/predis](https://packagist.org/packages/predis/predis) 這個套件。

## Example

> 假設現在你有專案預設的 User model 與 users 資料表，裡面有一個 id 為 1 的使用者資料；
> 
> 若記錄緝捕追到相同的請求識別 id ```X-Correlation-ID``` 時，會回應 409 告訴請求方，表示識別 id 發生衝突。

若是透過 AJAX 發送請求，要加入 Header 告訴系統我是用 AJAX 傳遞資料，不然測試時，
會遇到 request 參數收不到的情形，請[參考](https://imququ.com/post/four-ways-to-post-data-in-http.html)
```
Content-Type: x-www-form-urlencoded
```

### 接收請求路由

隊列工作啟動條件目前設置為 patch, put, delete 三種請求會進行請求回應的處理流程

```
// patch, put, delete
Route::resource('user', 'UserController', ['only' => ['store', 'update', 'destroy']]);
```

### 請求 callback 參數

> 請求時傳入包含 ```_callback_url``` 與 ```_callback_token``` 兩個參數，系統會回應 202，
> 表示請求已接受，並把實際請求派送到隊列中，進行處理，並在處理後呼叫 ```_callback_url``` 這個位址。
> 
> 若是沒有傳送```_callback_url``` 與 ```_callback_token``` 時，系統會直接處理該請求，並回應請求方，這種作法請求方需等候回應。

callback 路由，將原始請求的請求識別 id 回應到 callback 位址
```
Route::post('callback_receiver', function (\Illuminate\Http\Request $request) {
    // 驗證 token
    ...
    
    // log the callback header
    Log::info(collect($request->header())->toJson());
});
```

測試用控制器，處理 202 之後的隊列工作
```
<?php

namespace App\Http\Controllers;

use App\User;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function update()
    {
        $user = User::find(1);
        $user->name = str_random(20);
        $user->email = str_random(20) . '@littlebookboy.dev';
        $user->password = Hash::make(str_random(8));
        $user->remember_token = Hash::make(str_random(100));
        $user->update();
    }
}
```

設定隊列監聽
```
/path/to/bin/php /path/to/project/artisan queue:work connection --queue=job:name
```

本例設定
```
/path/to/bin/php /path/to/project/artisan queue:work redis --queue=api:request
```

### Config

可設定 ```driver``` 與 ```base_url```

|name|description|
|---|---|
|driver|隊列使用的資料庫|
|base_url|處理實際請求時，使用的專案域名|

### Fail Jobs

失敗的訊息可用官方的資料表
```
php artisan queue:failed-table

php artisan migrate
```

請注意，隊列監聽中，當有程式變動需要重啟監聽才會生效。

處理請求可在 ```app/Jobs/RequestQueueJobs.php``` 中的 ```handle``` 進行邏輯處理。
