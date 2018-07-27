<?php

namespace panwenbin\laravel\aliyunoss\plugins;

use League\Flysystem\Plugin\AbstractPlugin;

/**
 * FullUrl class
 * 获取完全地址
 *
 * @author  ApolloPY <ApolloPY@Gmail.com>
 */
class FullUrl extends AbstractPlugin
{
    /**
     * Get the method name.
     *
     * @return string
     */
    public function getMethod()
    {
        return 'fullUrl';
    }

    /**
     * Handle.
     *
     * @param string $path
     * @param int $expires
     * @return string|false
     */
    public function handle($path, $expires = 0)
    {
        if (!method_exists($this->filesystem, 'getAdapter')) {
            return false;
        }

        if (!method_exists($this->filesystem->getAdapter(), 'fullUrl')) {
            return false;
        }

        return $this->filesystem->getAdapter()->fullUrl($path, $expires);
    }
}
