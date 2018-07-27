<?php
/**
 * @author Pan Wenbin <panwenbin@gmail.com>
 */

namespace panwenbin\laravel\aliyunoss;


use Illuminate\Support\Facades\Storage;
use Illuminate\Support\ServiceProvider;
use League\Flysystem\Filesystem;
use OSS\OssClient;
use panwenbin\laravel\aliyunoss\plugins\FullUrl;
use panwenbin\laravel\aliyunoss\plugins\UploadFile;

class AliyunOssFlysystemServiceProvider extends ServiceProvider
{
    /**
     * Perform post-registration booting of services.
     *
     * @return void
     */
    public function register()
    {
        Storage::extend('oss', function ($app, $config) {
            $accessId = $config['access_id'];
            $accessKey = $config['access_key'];
            $endPoint = $config['endpoint'];
            $bucket = $config['bucket'];
            $prefix = null;
            if (isset($config['prefix'])) {
                $prefix = $config['prefix'];
            }
            $isCname = false;
            if (isset($config['is_cname'])) {
                $isCname = (bool)$config['is_cname'];
            }
            $client = new OssClient($accessId, $accessKey, $endPoint, $isCname);
            $adapter = new AliyunOssFlysystemAdapter($client, $bucket, $prefix);
            $filesystem = new Filesystem($adapter);
            $filesystem->addPlugin(new UploadFile());
            $filesystem->addPlugin(new FullUrl());

            return $filesystem;
        });
    }
}