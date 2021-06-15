<?php
namespace app\listener;

use Firebase\JWT\JWT;
use swostar\event\Listener;
use swostar\server\websocket\WebSocketServer as swoStarServer;
use swostar\WebSocket\Server as SwooleServer;

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
     * @param swoStarServer $server
     * @param Request $request
     * @param Response $response
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
     * @param WebSocketServer $swoStarServer
     * @param SwooleServer $swooleServer
     * @param [type] $fd
     * @return void
     */
    protected function cancel(WebSocketServer $swoStarServer = null, SwooleServer $swooleServer = null, $fd = null)
    {
        
    }
}
