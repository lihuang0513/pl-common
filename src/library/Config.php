<?php

namespace CompanyCommon\Library;

use InvalidArgumentException;
use RuntimeException;

/**
 * 按需读取公共库的独立资源配置
 *
 * @author 李煌<517306208@qq.com>
 * @date 2026-10-17
 */
class Config
{
    /** @var string|null 显式指定的配置文件 */
    private $config_path;

    /** @var array|null 当前实例已加载的配置 */
    private $values;

    /**
     * 保存配置路径，不读取文件或初始化资源
     *
     * @param string|null $config_path 独立配置文件路径
     * @return void
     * @author 李煌<517306208@qq.com>
     * @date 2026-10-17
     */
    public function __construct($config_path = null)
    {
        // 延迟到首次获取配置时才读取文件
        $this->config_path = $config_path;
    }

    /**
     * 获取指定资源组下的一份连接配置
     *
     * @param string $section 资源组名称
     * @param string $name 连接名称
     * @return array
     * @author 李煌<517306208@qq.com>
     * @date 2026-10-17
     */
    public function get($section, $name = 'default')
    {
        // 首次资源访问才合并默认配置与部署环境配置
        if ($this->values === null) {
            $this->load();
        }

        // 不允许未知资源隐式回落到其他数据库或 Redis
        $group = isset($this->values[$section]) ? $this->values[$section] : [];
        if (!is_array($group) || !isset($group[$name]) || !is_array($group[$name])) {
            throw new InvalidArgumentException('公共资源配置不存在。');
        }
        return $group[$name];
    }

    /**
     * 从独立文件读取配置，不访问宿主框架容器
     *
     * @return void
     * @author 李煌<517306208@qq.com>
     * @date 2026-10-17
     */
    private function load()
    {
        // 默认配置只包含安全占位值，不建立外部连接
        $config_root = sprintf('%s/config', dirname(__DIR__, 2));
        $defaults = require sprintf('%s/defaults.php', $config_root);
        $config_path = $this->config_path;
        if ($config_path === null) {
            $environment_path = getenv('PL_COMMON_CONFIG');
            $config_path = empty($environment_path) ? null : $environment_path;
        }

        // 显式配置缺失时及时报错；未显式配置则允许在无资源场景仅加载类
        $explicit_path = $config_path !== null;
        if (!$explicit_path) {
            $config_path = sprintf('%s/local.php', $config_root);
        }
        if (!is_file($config_path) || !is_readable($config_path)) {
            if ($explicit_path) {
                throw new RuntimeException('公共库配置文件不可读。');
            }
            $this->values = $defaults;
            return;
        }

        // 配置必须返回数组，避免错误配置导致不可预测的合并行为
        $overrides = require $config_path;
        if (!is_array($overrides)) {
            throw new RuntimeException('公共库配置文件必须返回数组。');
        }
        $this->values = array_replace_recursive($defaults, $overrides);
    }
}
