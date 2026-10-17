<?php

namespace PlCommon\Library;

/**
 * 仅管理公共库命名空间的自动加载器
 *
 * @author 李煌<517306208@qq.com>
 * @date 2026-10-17
 */
class Autoloader
{
    /** @var bool 是否已在当前进程注册 */
    private static $registered = false;

    /**
     * 幂等注册加载器，保留宿主已有加载顺序
     *
     * @return void
     * @author 李煌<517306208@qq.com>
     * @date 2026-10-17
     */
    public static function register()
    {
        // 多入口重复引入时不重复追加加载器
        if (self::$registered) {
            return;
        }

        // 追加公共加载器，不替换或提前于宿主加载器
        spl_autoload_register([self::class, 'load'], true, false);
        self::$registered = true;
    }

    /**
     * 按明确的命名空间映射加载公共类
     *
     * @param string $class_name 待加载的完整类名
     * @return void
     * @author 李煌<517306208@qq.com>
     * @date 2026-10-17
     */
    public static function load($class_name)
    {
        // 大写命名空间映射到现有小写目录，兼容区分大小写的服务器
        $prefixes = [
            'PlCommon\\Modules\\' => 'modules',
            'PlCommon\\Services\\' => 'services',
            'PlCommon\\Models\\' => 'models',
            'PlCommon\\Library\\' => 'library',
        ];

        // 只处理公共库拥有的类名，其他命名空间留给原有加载器
        foreach ($prefixes as $prefix => $directory) {
            if (strncmp($class_name, $prefix, strlen($prefix)) !== 0) {
                continue;
            }

            // 限制相对类名字符，避免将异常类名转换成目录穿越路径
            $relative_class = substr($class_name, strlen($prefix));
            if (preg_match('/^[A-Za-z_][A-Za-z0-9_\\\\]*$/D', $relative_class) !== 1) {
                return;
            }

            // 只加载实际存在的文件，不因未找到类而干扰后续加载器
            $relative_path = str_replace('\\', '/', $relative_class);
            $file_path = sprintf('%s/%s/%s.php', dirname(__DIR__), $directory, $relative_path);
            if (is_file($file_path)) {
                require_once $file_path;
            }
            return;
        }
    }
}
