<?php
namespace swostar\server\websocket;

/**
 * 建立的连接
 */
class Connections
{
    private static $connections = [];

    /**
     * 初始化
     *
     * @param [type] $fd
     * @param [type] $request
     * @return void
     */
    public static function init($fd, $request)
    {
        self::$connections[$fd]['path']    = $request->server['path_info'];
        self::$connections[$fd]['request'] = $request;
    }

    /**
     * 获取连接所对应的request信息
     * @param [type] $fd
     * @return void
     */
    public static function get($fd = null) {
        if (!isset(self::$connections[$fd])) {
            return null;
        }
        return self::$connections[$fd] ?? null;
    }

    /**
     * 删除连接所对应的path信息
     * @param [type] $fd
     * @return void
     */
    public static function del($fd = null){
        if (!isset(self::$connections[$fd])) {
            return false;
        }
        unset(self::$connections[$fd]);
        return true;
    }
}