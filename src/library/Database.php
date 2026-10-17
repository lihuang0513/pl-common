<?php

namespace CompanyCommon\Library;

use PDO;
use PDOException;
use RuntimeException;

/**
 * 不依赖 Phalcon 的独立惰性数据库连接工厂
 *
 * @author 李煌<517306208@qq.com>
 * @date 2026-10-17
 */
class Database
{
    /** @var Config 公共库独立配置 */
    private $config;

    /** @var PDO[] 当前工厂已打开的连接 */
    private $connections = [];

    /**
     * 保存配置对象，不建立数据库连接
     *
     * @param Config|null $config 可选的独立配置对象
     * @return void
     * @author 李煌<517306208@qq.com>
     * @date 2026-10-17
     */
    public function __construct(Config $config = null)
    {
        // 使用公共配置对象，避免读取或修改宿主 DI 的 db 服务
        $this->config = $config === null ? new Config() : $config;
    }

    /**
     * 第一次使用时建立 PDO 连接，同一工厂内按名称复用
     *
     * @param string $name 数据库连接名称
     * @return PDO
     * @author 李煌<517306208@qq.com>
     * @date 2026-10-17
     */
    public function getConnection($name = 'default')
    {
        // 复用已经建立的独立连接，避免同一业务重复建连
        if (isset($this->connections[$name])) {
            return $this->connections[$name];
        }

        // 仅在查询需要连接时读取配置并检查可用驱动
        $settings = $this->config->get('database', $name);
        if (empty($settings['dsn']) || !is_string($settings['dsn'])) {
            throw new RuntimeException('公共数据库尚未配置。');
        }
        if (!class_exists('PDO', false)) {
            throw new RuntimeException('公共数据库需要 PDO 扩展。');
        }
        $timeout = isset($settings['timeout']) ? max(1, (int) $settings['timeout']) : 3;

        // 使用非持久连接、异常模式和真实预处理，保持资源与宿主事务隔离
        try {
            $connection = new PDO(
                $settings['dsn'],
                isset($settings['username']) ? $settings['username'] : '',
                isset($settings['password']) ? $settings['password'] : '',
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                    PDO::ATTR_PERSISTENT => false,
                    PDO::ATTR_TIMEOUT => $timeout,
                ]
            );
        } catch (PDOException $exception) {
            // 将连接失败交给调用方处理，公共错误消息不包含连接凭据
            throw new RuntimeException('公共数据库连接失败。', 0, $exception);
        }
        $this->connections[$name] = $connection;
        return $connection;
    }
}
