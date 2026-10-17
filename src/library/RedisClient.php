<?php

namespace PlCommon\Library;

use Redis;
use RuntimeException;

/**
 * 按实际使用创建独立的 Redis 连接
 *
 * @author 李煌<517306208@qq.com>
 * @date 2026-10-17
 */
class RedisClient
{
    /** @var Config 当前工厂复用的独立配置对象 */
    private $config;

    /** @var Redis[] 当前工厂已打开的连接 */
    private $connections = [];

    /**
     * 保存配置对象，未使用 Redis 时不要求安装扩展
     *
     * @param Config|null $config 可选的独立配置对象
     * @return void
     * @author 李煌<517306208@qq.com>
     * @date 2026-10-17
     */
    public function __construct(Config $config = null)
    {
        // 默认配置对象创建一次；Config 构造方法不会读取文件或连接资源
        $this->config = $config === null ? new Config() : $config;
    }

    /**
     * 首次使用时完成连接、认证和数据库选择
     *
     * @param string $name Redis 连接名称
     * @return Redis
     * @author 李煌<517306208@qq.com>
     * @date 2026-10-17
     */
    public function getConnection($name = 'default')
    {
        // 同一工厂只复用自己的连接，不复用或修改宿主 Redis
        if (isset($this->connections[$name])) {
            return $this->connections[$name];
        }

        // 实际调用时才要求配置及扩展，普通公共方法不受影响
        $settings = $this->config->get('redis', $name);
        if (empty($settings['host'])) {
            throw new RuntimeException('公共 Redis 尚未配置。');
        }
        if (!class_exists('Redis', false)) {
            throw new RuntimeException('公共 Redis 需要 phpredis 扩展。');
        }

        // 有界超时建立非持久连接，避免连接故障长期阻塞调用方
        $connection = new Redis();
        $port = isset($settings['port']) ? (int) $settings['port'] : 6379;
        $timeout = isset($settings['timeout']) ? max(0.1, (float) $settings['timeout']) : 1.0;
        if (!$connection->connect($settings['host'], $port, $timeout)) {
            throw new RuntimeException('公共 Redis 连接失败。');
        }

        // 限制读取等待时间，再按独立配置认证和选库
        $read_timeout = isset($settings['read_timeout']) ? max(0.1, (float) $settings['read_timeout']) : 1.0;
        $connection->setOption(Redis::OPT_READ_TIMEOUT, $read_timeout);
        if (!empty($settings['password']) && !$connection->auth($settings['password'])) {
            throw new RuntimeException('公共 Redis 认证失败。');
        }
        $database = isset($settings['database']) ? (int) $settings['database'] : 0;
        if (!$connection->select($database)) {
            throw new RuntimeException('公共 Redis 选择数据库失败。');
        }

        // 给公共缓存键添加独立前缀，避免与宿主键重名
        $prefix = isset($settings['prefix']) ? $settings['prefix'] : 'pl-common:';
        $connection->setOption(Redis::OPT_PREFIX, $prefix);
        $this->connections[$name] = $connection;
        return $connection;
    }
}
