<?php

namespace CompanyCommon\Models;

use PDO;
use Redis;
use RuntimeException;
use CompanyCommon\Library\Database;
use CompanyCommon\Library\RedisClient;

/**
 * 封装两库最新记录查询和本地 Redis 测试键操作
 *
 * @author 李煌<517306208@qq.com>
 * @date 2026-10-17
 */
class TestModel
{
    /** @var Database 模型内复用的命名数据库工厂 */
    private $database;

    /** @var RedisClient 模型内复用的 Redis 工厂 */
    private $redis_client;

    /**
     * 初始化可复用的数据资源工厂，不提前建立连接
     *
     * @param Database|null $database 可选的公共数据库工厂
     * @param RedisClient|null $redis_client 可选的公共 Redis 工厂
     * @return void
     * @author 李煌<517306208@qq.com>
     * @date 2026-10-17
     */
    public function __construct(Database $database = null, RedisClient $redis_client = null)
    {
        // 默认工厂只在构造时创建一次；工厂自身不读取配置或连接资源
        $this->database = $database === null ? new Database() : $database;
        $this->redis_client = $redis_client === null ? new RedisClient() : $redis_client;
    }

    /**
     * 从本地评论库查询主键最大的完整评论记录
     *
     * @return array
     * @author 李煌<517306208@qq.com>
     * @date 2026-10-17
     */
    public function fetchLatestComment()
    {
        // 按名称取得评论库连接，查询固定表并绑定返回条数
        $connection = $this->database->getConnection('pinglun');
        $statement = $connection->prepare('SELECT * FROM comment ORDER BY id DESC LIMIT :row_limit');
        $statement->bindValue(':row_limit', 1, PDO::PARAM_INT);
        $statement->execute();
        $row = $statement->fetch();
        $statement->closeCursor();

        // 返回连接实际选中的库名及完整记录，空表以 null 表示
        $database_name = $connection->query('SELECT DATABASE()')->fetchColumn();
        return [
            'connection' => 'pinglun',
            'database' => $database_name,
            'table' => 'comment',
            'row' => $row === false ? null : $row,
        ];
    }

    /**
     * 使用另一数据库连接查询日志表主键最大的完整记录
     *
     * @return array
     * @author 李煌<517306208@qq.com>
     * @date 2026-10-17
     */
    public function fetchLatestPlapiLog()
    {
        // 日志查询使用独立的命名连接，不复用评论库连接或切换它的数据库
        $connection = $this->database->getConnection('plapi_log');
        $statement = $connection->prepare('SELECT * FROM plapi_tmp_log_2024 ORDER BY id DESC LIMIT :row_limit');
        $statement->bindValue(':row_limit', 1, PDO::PARAM_INT);
        $statement->execute();
        $row = $statement->fetch();
        $statement->closeCursor();

        // 输出实际库名，便于验证两库连接没有发生混用
        $database_name = $connection->query('SELECT DATABASE()')->fetchColumn();
        return [
            'connection' => 'plapi_log',
            'database' => $database_name,
            'table' => 'plapi_tmp_log_2024',
            'row' => $row === false ? null : $row,
        ];
    }

    /**
     * 在本地 Redis 写入原始键 test=111 并读取校验
     *
     * @return array
     * @author 李煌<517306208@qq.com>
     * @date 2026-10-17
     */
    public function writeAndReadRedis()
    {
        // 本次验证必须操作用户指定的原始键，配置有前缀时拒绝写入其他键
        $connection = $this->redis_client->getConnection('local');
        $key_prefix = $connection->getOption(Redis::OPT_PREFIX);
        if ($key_prefix !== '' && $key_prefix !== null && $key_prefix !== false) {
            throw new RuntimeException('本地 Redis 验证连接必须使用空键前缀。');
        }

        // 按用户要求写入固定测试值，不修改其他缓存键
        if (!$connection->set('test', '111')) {
            throw new RuntimeException('本地 Redis 测试键写入失败。');
        }

        // 读回实际存储值，只有一致时才返回成功结果
        $value = $connection->get('test');
        if ($value !== '111') {
            throw new RuntimeException('本地 Redis 测试键读回不一致。');
        }
        return ['key' => 'test', 'value' => $value];
    }
}
