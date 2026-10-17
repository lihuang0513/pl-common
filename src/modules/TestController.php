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
    /** @var TestService 公共探测服务 */
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
        // 公共控制器只组装业务依赖，不启动另一套框架应用
        $this->test_service = $test_service === null ? new TestService() : $test_service;
    }

    /**
     * 提供稳定的公共调用入口，输出由宿主决定
     *
     * @return string
     * @author 李煌<517306208@qq.com>
     * @date 2026-10-17
     */
    public function indexAction()
    {
        // 按约定经过服务层与模型层获取数据库结果，公共库本身不输出响应
        return $this->test_service->getMessage();
    }
}
