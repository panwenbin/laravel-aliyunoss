## 阿里云OSS的Laravel Service Provider封装
> 官方的`aliyun-oss-php-sdk-flysystem`和`aliyun-oss-php-sdk-laravel`各种缺点还不怎么更新  
第三方的也是版本滞后，基本没有兼容到PHPSDK 2.3的，所以参考这些后重新封装了一个。  
欢迎提issue或者pull request。

## 版本
此封装依赖直接写到了Laravel5.5及以上，支持服务自动发现。

## 安装
`composer require panwenbin/laravel-aliyunoss`

## 配置
```
// config/filesystems.php
'default' => env('FILESYSTEM_DRIVER', 'oss'), // 缺省驱动改为oss
...
'disks' => [
    ...
    // 增加oss配置段
    'oss' => [
        'driver'     => 'oss',
        'access_id'  => env('OSS_ACCESS_ID','your id'),
        'access_key' => env('OSS_ACCESS_KEY','your key'),
        'bucket'     => env('OSS_BUCKET','your bucket'),
        'endpoint'   => env('OSS_ENDPOINT','your endpoint'),
        'prefix'     => env('OSS_PREFIX', ''), // optional
        'is_cname'   => env('OSS_IS_CNAME', ''), // optional
    ],
]
```

## 使用
[Laravel 5.5 Doc#FileSystem](https://laravel.com/docs/5.5/filesystem)

## 插件
```
Storage::disk('oss')->uploadFile($md5_path, '/local_fle_path/1.png', ['mimetype' => 'image/png','filename' => 'filename_by_down.png']);
Storage::disk('oss')->fullUrl($path); // 永久地址
Storage::disk('oss')->fullUrl($path, 3600); // 临时地址
```