<?php
namespace app\listener;

use Firebase\JWT\JWT;
use swostar\event\Listener;
use swostar\server\websocket\Connections;
use swostar\server\websocket\WebSocketServer as swoStarServer;
use Swoole\WebSocket\Server as SwooleServer;

/**
 * 开始监听
 */
class WSCloseListener extends Listener
{
    /**
     * 事件名称
     *
     * @var string
     */
    protected $name = "ws.close";

    /**
     * 事件处理程序的方法
     *
     * @param swoStarServer $swoStarServer
     * @param SwooleServer $swooleServer
     * @param [type] $fd
     * @return void
     */
    public function handler(swoStarServer $swoStarServer = null, SwooleServer $swooleServer = null, $fd = null)
    {
        // 注销用户的登录
        if ($this->app->make('config')->get('server.ws.is_handshake')) {
            $this->cancel($swoStarServer, $swooleServer, $fd);
        }
    }

    /**
     * 注销用户的登录
     *
     * @param swoStarServer $swoStarServer
     * @param SwooleServer $swooleServer
     * @param [type] $fd
     * @return void
     */
    protected function cancel(swoStarServer $swoStarServer = null, SwooleServer $swooleServer = null, $fd = null)
    {
        $request = Connections::get($fd)['request'];
        $token   = $request->header['sec-websocket-protocol'];
        $config  = $this->app->make('config');
        $key     = $config->get("server.route.jwt.key");
        // 对jwt的token进行解析，返回jwt对象
        // 'data' => [
        //     'uid'  => $uid,
        //     'name'  => "client" . $time . $sid, // 用户名
        //     'service_url' => $url,
        // ],
        $jwt = JWT::decode($token, $key, $config->get('server.route.jwt.alg'));
        // 从jwt中获取信息
        $userInfo = $jwt->data;
        $swoStarServer->getRedis()->hdel($key, $userInfo->uid);
        info("触发移除" . $userInfo->uid);
    }
}
