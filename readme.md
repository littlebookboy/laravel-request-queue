## Queue

提供一個簡單實現將 request 派送到隊列，並使用 Redis (或 [MySQL](https://laravel.com/docs/master/queues)) 資料庫儲存隊列工作。隊列工作的監聽則透過 supervidord，來輔助背景監聽。


## 資料表

MySQL
```
php artisan queue:table

php artisan migrate
```

Redis

安裝 Redis Server，在此以 Mac 為例，可以參考官網的 [installing-redis-on-mac-os-x](http://jasdeep.ca/2012/05/installing-redis-on-mac-os-x/) 安裝
```
brew update && brew install redis
```

因為我是用 MAMP 環境，所以必須讓他的 PHP 支援 Redis，可以參考這個說明去安裝[panxianhai/php-redis-mamp](https://github.com/panxianhai/php-redis-mamp)，裡面也有直接放好該有的 redis.so，不用自己去 [php.net](http://php.net/) 手動編譯出來。修改 php.ini，若是 MAMP Pro，打開 php.ini 可以在 ```File > Edit Template > PHP > PHP 7.0.8 php.ini``` 找到
```
[redis]
extension="redis.so"
```

重啟 MAMP 後打開 phpinfo 看，有出現 redis 表示 MAMP 中的 PHP 庫已支援 Redis，而 Laravel 已經有一套很完整的機制可以讓你快速連結到你本機的 Redis，在這個專案中，我使用的是 [predis/predis](https://packagist.org/packages/predis/predis) 這個套件。


## 使用


註冊服務提供者
```
LittleBookBoy\Request\Queue\RequestQueueServiceProvider::class,
```

路由範例，本例僅針對 patch, put, delete 三種請求進行處理
```
Route::resource('user', 'UserController', ['only' => ['store', 'update', 'destroy']]);
```

請求要加入 Header 告訴系統我是用 AJAX 傳遞資料，不然測試時會遇到 request 參數收不到的情形，請[參考](https://imququ.com/post/four-ways-to-post-data-in-http.html)
```
Content-Type: x-www-form-urlencoded
```

發佈 config 檔
```
php artisan vendor:publish
```

## Example

路由
```
// patch, put, delete
Route::resource('user', 'UserController', ['only' => ['store', 'update', 'destroy']]);
```

控制器
```
public function update($id)
{
    // 處理請求
    $user = new User();
    $user->name = str_random(20);
    $user->save();
}
```

本例沒有設定時間戳，所以先把 ```User``` Model 裡面加 ```public $timestamps = false;```，並把 fillable，與 hidden 拿掉。
Content-Type: x-www-form-urlencoded  hidden 拿掉
