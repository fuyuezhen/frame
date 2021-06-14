<?php
namespace app\listener;

use swostar\event\Listener;
use swostar\server\websocket\WebSocketServer;
use Swoole\Http\Request;
use Swoole\Http\Response;

/**
 * 开始监听
 */
class HandsHakeListener extends Listener
{
    /**
     * 事件名称
     *
     * @var string
     */
    protected $name = "ws.hand";

    /**
     * 事件处理程序的方法
     *
     * @param WebSocketServer $server
     * @param Request $request
     * @param Response $response
     * @return void
     */
    public function handler(WebSocketServer $server = null, Request $request = null, Response $response = null)
    {
        // 权限校验
        // 没有携带token直接结束
        $token = $request->header['sec-websocket-protocol'];
        if (empty($token) || ($this->check($server, $token, $request->fd))) {
            $response->end();
            return false;
        }
        $response->end($token);
    }
}
