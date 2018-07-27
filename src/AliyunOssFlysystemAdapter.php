<?php
/**
 * @author Pan Wenbin <panwenbin@gmail.com>
 */

namespace panwenbin\laravel\aliyunoss;


use League\Flysystem\Adapter\AbstractAdapter;
use League\Flysystem\Adapter\Polyfill\StreamedTrait;
use League\Flysystem\AdapterInterface;
use League\Flysystem\Config;
use League\Flysystem\Util;
use OSS\Core\OssException;
use OSS\OssClient;
use ReflectionClass;

/**
 * Class AliyunOssFlysystemAdapter
 * @package panwenbin\laravel\aliyunoss
 *
 * 参考官方实现和apollopy实现
 * @author RobertYue19900425 <yueqiankun@163.com>
 * @author ApolloPY <ApolloPY@Gmail.com>
 */
class AliyunOssFlysystemAdapter extends AbstractAdapter
{
    use StreamedTrait;

    /**
     * @var \OSS\OssClient
     */
    protected $ossClient;

    /**
     * @var string
     */
    protected $bucket;

    /**
     * @var array
     */
    protected $options = [];

    /**
     * @var array
     */
    protected static $mappingOptions = [
        'mimetype' => OssClient::OSS_CONTENT_TYPE,
        'size' => OssClient::OSS_LENGTH,
    ];

    public function __construct(OssClient $ossClient, string $bucket, string $prefix = null, array $options = [])
    {
        $this->ossClient = $ossClient;
        $this->bucket = $bucket;
        $this->setPathPrefix($prefix);
        $this->options = array_merge($this->options, $options);
    }

    /**
     * Write a new file.
     *
     * @param string $path
     * @param string $contents
     * @param Config $config Config object
     *
     * @return array|false false on failure file meta data on success
     */
    public function write($path, $contents, Config $config)
    {
        $object = $this->applyPathPrefix($path);
        $options = $this->getOptionsFromConfig($config);

        if (!isset($options[OssClient::OSS_LENGTH])) {
            $options[OssClient::OSS_LENGTH] = Util::contentSize($contents);
        }
        if (!isset($options[OssClient::OSS_CONTENT_TYPE])) {
            $options[OssClient::OSS_CONTENT_TYPE] = Util::guessMimeType($path, $contents);
        }

        try {
            $this->ossClient->putObject($this->bucket, $object, $contents, $options);
        } catch (OssException $e) {
            return false;
        }

        $type = 'file';
        $result = compact('type', 'path');
        $result['mimetype'] = $options[OssClient::OSS_CONTENT_TYPE];

        return $result;
    }

    /**
     * Update a file.
     *
     * @param string $path
     * @param string $contents
     * @param Config $config Config object
     *
     * @return array|false false on failure file meta data on success
     */
    public function update($path, $contents, Config $config)
    {
        if (!$config->has('visibility') && !$config->has('ACL')) {
            $config->set('ACL', $this->getObjectACL($path));
        }

        return $this->write($path, $contents, $config);
    }

    /**
     * Rename a file.
     *
     * @param string $path
     * @param string $newpath
     *
     * @return bool
     */
    public function rename($path, $newpath)
    {
        if ($this->copy($path, $newpath) == false) {
            return false;
        }

        return $this->delete($path);
    }

    /**
     * Copy a file.
     *
     * @param string $path
     * @param string $newpath
     *
     * @return bool
     */
    public function copy($path, $newpath)
    {
        $object = $this->applyPathPrefix($path);
        $newobject = $this->applyPathPrefix($newpath);

        try {
            $this->ossClient->copyObject($this->bucket, $object, $this->bucket, $newobject);
        } catch (OssException $e) {
            return false;
        }

        return true;
    }

    /**
     * Delete a file.
     *
     * @param string $path
     *
     * @return bool
     */
    public function delete($path)
    {
        $object = $this->applyPathPrefix($path);

        try {
            $this->ossClient->deleteObject($this->bucket, $object);
        } catch (OssException $e) {
            return false;
        }

        return true;
    }

    /**
     * Delete a directory.
     *
     * @param string $dirname
     *
     * @return bool
     * @throws OssException
     */
    public function deleteDir($dirname)
    {
        $list = $this->listContents($dirname, true);

        $objects = [];
        foreach ($list as $val) {
            if ($val['type'] === 'file') {
                $objects[] = $this->applyPathPrefix($val['path']);
            } else {
                $objects[] = $this->applyPathPrefix($val['path']) . '/';
            }
        }

        try {
            $this->ossClient->deleteObjects($this->bucket, $objects);
        } catch (OssException $e) {
            return false;
        }

        return true;
    }

    /**
     * Create a directory.
     *
     * @param string $dirname directory name
     * @param Config $config
     *
     * @return array|false
     */
    public function createDir($dirname, Config $config)
    {
        $object = $this->applyPathPrefix($dirname);
        $options = $this->getOptionsFromConfig($config);

        try {
            $this->ossClient->createObjectDir($this->bucket, $object, $options);
        } catch (OssException $e) {
            return false;
        }

        return ['path' => $dirname, 'type' => 'dir'];
    }

    /**
     * Set the visibility for a file.
     *
     * @param string $path
     * @param string $visibility
     *
     * @return array|false file meta data
     */
    public function setVisibility($path, $visibility)
    {
        $object = $this->applyPathPrefix($path);
        $acl = ($visibility === AdapterInterface::VISIBILITY_PUBLIC) ? 'public-read' : 'private';

        try {
            $this->ossClient->putObjectAcl($this->bucket, $object, $acl);
        } catch (OssException $e) {
            return false;
        }

        return compact('visibility');
    }

    /**
     * Check whether a file exists.
     *
     * @param string $path
     *
     * @return array|bool|null
     */
    public function has($path)
    {
        $object = $this->applyPathPrefix($path);

        return $this->ossClient->doesObjectExist($this->bucket, $object);
    }

    /**
     * Read a file.
     *
     * @param string $path
     *
     * @return array|false
     */
    public function read($path)
    {
        $object = $this->applyPathPrefix($path);

        try {
            $contents = $this->ossClient->getObject($this->bucket, $object);
        } catch (OssException $e) {
            return false;
        }

        return compact('contents', 'path');
    }

    /**
     * List contents of a directory.
     *
     * @param string $directory
     * @param bool $recursive
     *
     * @return array
     * @throws OssException
     */
    public function listContents($directory = '', $recursive = false)
    {
        $directory = rtrim($this->applyPathPrefix($directory), '\\/');
        if ($directory) {
            $directory .= '/';
        }

        $bucket = $this->bucket;
        $delimiter = '/';
        $nextMarker = '';
        $maxkeys = 1000;

        $options = [
            'delimiter' => $delimiter,
            'prefix' => $directory,
            'max-keys' => $maxkeys,
            'marker' => $nextMarker,
        ];

        $listObjectInfo = $this->ossClient->listObjects($bucket, $options);

        $objectList = $listObjectInfo->getObjectList(); // 文件列表
        $prefixList = $listObjectInfo->getPrefixList(); // 目录列表

        $result = [];
        foreach ($objectList as $objectInfo) {
            if ($objectInfo->getSize() === 0 && $directory === $objectInfo->getKey()) {
                $result[] = [
                    'type' => 'dir',
                    'path' => $this->removePathPrefix(rtrim($objectInfo->getKey(), '/')),
                    'timestamp' => strtotime($objectInfo->getLastModified()),
                ];
                continue;
            }
            $result[] = [
                'type' => 'file',
                'path' => $this->removePathPrefix($objectInfo->getKey()),
                'timestamp' => strtotime($objectInfo->getLastModified()),
                'size' => $objectInfo->getSize(),
            ];
        }
        foreach ($prefixList as $prefixInfo) {
            if ($recursive) {
                $next = $this->listContents($this->removePathPrefix($prefixInfo->getPrefix()), $recursive);
                $result = array_merge($result, $next);
            } else {
                $result[] = [
                    'type' => 'dir',
                    'path' => $this->removePathPrefix(rtrim($prefixInfo->getPrefix(), '/')),
                    'timestamp' => 0,
                ];
            }
        }

        return $result;
    }

    /**
     * Get all the meta data of a file or directory.
     *
     * @param string $path
     *
     * @return array|false
     */
    public function getMetadata($path)
    {
        $object = $this->applyPathPrefix($path);

        try {
            $result = $this->ossClient->getObjectMeta($this->bucket, $object);
        } catch (OssException $e) {
            return false;
        }

        return [
            'type' => 'file',
            'dirname' => Util::dirname($path),
            'path' => $path,
            'timestamp' => strtotime($result['last-modified']),
            'mimetype' => $result['content-type'],
            'size' => $result['content-length'],
        ];
    }

    /**
     * Get the size of a file.
     *
     * @param string $path
     *
     * @return array|false
     */
    public function getSize($path)
    {
        return $this->getMetadata($path);
    }

    /**
     * Get the mimetype of a file.
     *
     * @param string $path
     *
     * @return array|false
     */
    public function getMimetype($path)
    {
        return $this->getMetadata($path);
    }

    /**
     * Get the last modified time of a file as a timestamp.
     *
     * @param string $path
     *
     * @return array|false
     */
    public function getTimestamp($path)
    {
        return $this->getMetadata($path);
    }

    /**
     * Get the visibility of a file.
     *
     * @param string $path
     *
     * @return array|false
     */
    public function getVisibility($path)
    {
        $bucket = $this->bucket;
        $object = $this->applyPathPrefix($path);
        try {
            $res['visibility'] = $this->ossClient->getObjectAcl($bucket, $object);
        } catch (OssException $e) {
            return false;
        }

        return $res;
    }

    /**
     * 获取options
     *
     * @param Config $config
     */
    protected function getOptionsFromConfig(Config $config)
    {
        $options = $this->options;
        foreach (static::$mappingOptions as $option => $ossOption) {
            if ($config->has($option)) {
                $options[$ossOption] = $config->get($option);
            }
        }
    }

    /**
     * 上传本地文件(插件方法)
     * @param $path
     * @param $localFilePath
     * @param Config $config
     * @return array|bool
     */
    public function uploadFile($path, $localFilePath, Config $config)
    {
        $object = $this->applyPathPrefix($path);
        $options = $this->getOptionsFromConfig($config);

        $options[OssClient::OSS_CHECK_MD5] = true;

        if (!isset($options[OssClient::OSS_CONTENT_TYPE])) {
            $options[OssClient::OSS_CONTENT_TYPE] = Util::guessMimeType($path, '');
        }

        try {
            $this->ossClient->uploadFile($this->bucket, $object, $localFilePath, $options);
        } catch (OssException $e) {
            return false;
        }

        $type = 'file';
        $result = compact('type', 'path');
        $result['mimetype'] = $options[OssClient::OSS_CONTENT_TYPE];

        return $result;
    }

    /**
     * 获取完整地址(插件方法)
     * @param $path
     * @param int $expires
     * @return null|string
     * @throws \ReflectionException
     */
    public function fullUrl($path, $expires = 0)
    {
        $object = $this->applyPathPrefix($path);

        if ($expires) {
            try {
                $url = $this->ossClient->signUrl($this->bucket, $object, $expires);
            } catch (OssException $e) {
                return null;
            }
        } else {
            $class = new ReflectionClass(get_class($this->ossClient));
            $method = $class->getMethod("generateHostname");
            $method->setAccessible(true);
            $hostname = $method->invoke($this->ossClient, $this->bucket);
            $url = 'http://' . $hostname . '/' . $object;
        }

        return $url;
    }
}