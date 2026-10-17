<?php

namespace CompanyCommon\Modules;

use CompanyCommon\Services\TestService;

/**
 * 供各宿主直接实例化的公共验证入口，不依赖框架控制器
 *
 * @author 李煌<517306208@qq.com>
 * @date 2026-10-17
 */
class TestController
{
    /** @var TestService 当前控制器复用的公共验证服务 */
    private $test_service;

    /**
     * 保存服务实例，不操作数据库或 Redis
     *
     * @param TestService|null $test_service 可选的公共探测服务
     * @return void
     * @author 李煌<517306208@qq.com>
     * @date 2026-10-17
     */
    public function __construct(TestService $test_service = null)
    {
        // 构造时只初始化一次默认服务；传入实例时直接复用，不连接外部资源
        $this->test_service = $test_service === null ? new TestService() : $test_service;
    }

    /**
     * 提供稳定的公共调用入口，输出由宿主决定
     *
     * @return array
     * @author 李煌<517306208@qq.com>
     * @date 2026-10-17
     */
    public function indexAction()
    {
        // 经服务层和模型层汇总两个数据库与 Redis 的真实操作结果
        return $this->test_service->getResults();
    }
}
