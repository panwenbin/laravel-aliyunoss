<?php
/**
 * @author Pan Wenbin <panwenbin@gmail.com>
 */

namespace panwenbin\laravel\aliyunoss\plugins;

use League\Flysystem\Plugin\AbstractPlugin;

/**
 * Class getUrl
 * @package panwenbin\laravel\aliyunoss\plugins
 */
class GetUrl extends AbstractPlugin
{
    /**
     * Get the method name.
     *
     * @return string
     */
    public function getMethod()
    {
        return 'getUrl';
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

        if (!method_exists($this->filesystem->getAdapter(), 'getUrl')) {
            return false;
        }

        return $this->filesystem->getAdapter()->getUrl($path, $expires);
    }
}
