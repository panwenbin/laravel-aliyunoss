<?php
/**
 * @author Pan Wenbin <panwenbin@gmail.com>
 */

namespace panwenbin\laravel\aliyunoss\plugins;

use League\Flysystem\Plugin\AbstractPlugin;

/**
 * Class getTemporaryUrl
 * @package panwenbin\laravel\aliyunoss\plugins
 */
class GetTemporaryUrl extends AbstractPlugin
{
    /**
     * Get the method name.
     *
     * @return string
     */
    public function getMethod()
    {
        return 'getTemporaryUrl';
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

        if (!method_exists($this->filesystem->getAdapter(), 'getTemporaryUrl')) {
            return false;
        }

        return $this->filesystem->getAdapter()->getTemporaryUrl($path, $expires);
    }
}
