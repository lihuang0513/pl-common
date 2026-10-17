<?php

namespace PlCommon\Services;

use PlCommon\Models\TestModel;

/**
 * 编排多数据库查询与本地 Redis 验证并汇总结果
 *
 * @author 李煌<517306208@qq.com>
 * @date 2026-10-17
 */
class TestService
{
    /** @var TestModel 当前服务复用的公共验证模型 */
    private $test_model;

    /**
     * 保存探测模型，不执行查询
     *
     * @param TestModel|null $test_model 可选的公共探测模型
     * @return void
     * @author 李煌<517306208@qq.com>
     * @date 2026-10-17
     */
    public function __construct(TestModel $test_model = null)
    {
        // 默认模型在构造时创建一次，后续调用复用该模型，不在此处查询资源
        $this->test_model = $test_model === null ? new TestModel() : $test_model;
    }

    /**
     * 在同一次调用中读取两库最新行并验证本地 Redis 写入
     *
     * @return array
     * @author 李煌<517306208@qq.com>
     * @date 2026-10-17
     */
    public function getResults()
    {
        // 从本地评论库取得主键最大的完整评论记录
        $pinglun = $this->test_model->fetchLatestComment();

        // 使用另一命名连接查询日志库，验证同一请求内的多数据库隔离
        $plapi_log = $this->test_model->fetchLatestPlapiLog();

        // 两库读取成功后，执行用户指定的本地 Redis 写入并读回核验
        $redis = $this->test_model->writeAndReadRedis();

        // 汇总真实查询结果，由宿主决定如何输出 HTTP 或 CLI 响应
        return [
            'status' => 'success',
            'pinglun' => $pinglun,
            'plapi_log' => $plapi_log,
            'redis' => $redis,
        ];
    }
}
