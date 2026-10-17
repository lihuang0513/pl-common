<?php

namespace CompanyCommon\Models;

use CompanyCommon\Library\Database;

/**
 * 使用真实数据库查询验证公共库的数据访问链路
 *
 * @author 李煌<517306208@qq.com>
 * @date 2026-10-17
 */
class TestModel
{
    /** @var Database|null 首次查询时才创建的公共数据库工厂 */
    private $database;

    /**
     * 保存数据库工厂，不提前连接数据库
     *
     * @param Database|null $database 可选的公共数据库工厂
     * @return void
     * @author 李煌<517306208@qq.com>
     * @date 2026-10-17
     */
    public function __construct(Database $database = null)
    {
        // 只保存显式传入的工厂，构造模型不自动加载或创建数据库依赖
        $this->database = $database;
    }

    /**
     * 从数据库获取探测结果与实际选中的数据库名称
     *
     * @return array|false
     * @author 李煌<517306208@qq.com>
     * @date 2026-10-17
     */
    public function fetchProbe()
    {
        // 实际执行查询时才加载并创建数据库工厂
        if ($this->database === null) {
            $this->database = new Database();
        }

        // 获取惰性连接并执行绑定参数查询，不读取或写入业务表
        $connection = $this->database->getConnection();
        $statement = $connection->prepare('SELECT :message AS message, DATABASE() AS database_name');
        $statement->execute(['message' => 'test']);

        // 返回数据库实际结果，供服务层判断连接链路是否完整
        $row = $statement->fetch();
        $statement->closeCursor();
        return $row;
    }
}
