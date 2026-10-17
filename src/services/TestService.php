<?php

namespace CompanyCommon\Services;

use RuntimeException;
use CompanyCommon\Models\TestModel;

/**
 * 校验数据库探测结果并提供稳定的业务返回值
 *
 * @author 李煌<517306208@qq.com>
 * @date 2026-10-17
 */
class TestService
{
    /** @var TestModel 公共探测模型 */
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
        // 构建惰性依赖链，实例化服务不会建立连接
        $this->test_model = $test_model === null ? new TestModel() : $test_model;
    }

    /**
     * 校验查询已进入指定数据库后返回探测文本
     *
     * @return string
     * @author 李煌<517306208@qq.com>
     * @date 2026-10-17
     */
    public function getMessage()
    {
        // 通过模型执行真实数据库查询，不能用本地默认值伪造成功
        $row = $this->test_model->fetchProbe();

        // 同时要求选中了数据库且返回约定文本，避免无数据库连接被误判成功
        if (empty($row) || empty($row['database_name']) || !isset($row['message']) || $row['message'] !== 'test') {
            throw new RuntimeException('公共数据库探测结果不符合预期。');
        }
        return $row['message'];
    }
}
