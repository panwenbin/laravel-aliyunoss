<?php

namespace panwenbin\laravel\aliyunoss\plugins;

use League\Flysystem\Config;
use League\Flysystem\Plugin\AbstractPlugin;

/**
 * PutFile class
 * 上传本地文件.
 *
 * @author  ApolloPY <ApolloPY@Gmail.com>
 */
class UploadFile extends AbstractPlugin
{
    /**
     * Get the method name.
     *
     * @return string
     */
    public function getMethod()
    {
        return 'uploadFile';
    }

    /**
     * Handle.
     *
     * @param string $path
     * @param string $localFilePath
     * @param array $config
     * @return bool
     */
    public function handle($path, $localFilePath, array $config = [])
    {
        if (!method_exists($this->filesystem, 'getAdapter')) {
            return false;
        }

        if (!method_exists($this->filesystem->getAdapter(), 'uploadFile')) {
            return false;
        }

        $config = new Config($config);
        if (method_exists($this->filesystem, 'getConfig')) {
            $config->setFallback($this->filesystem->getConfig());
        }

        return (bool)$this->filesystem->getAdapter()->uploadFile($path, $localFilePath, $config);
    }
}
