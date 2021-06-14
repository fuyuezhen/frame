<?php
namespace app\listener;

use Firebase\JWT\JWT;
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
        info($token);
        
        if (empty($token) || (!$this->check($server, $token, $request->fd))) {
            $response->end();
            return false;
        }
        
        // websocket握手连接算法验证
        $this->handShake($request, $response);
    }

    /**
     * 对连接的用户进行权限校验，如果通过就存入redis中
     *
     * @param WebSocketServer $server
     * @param [type] $token
     * @param [type] $fd
     * @return void
     */
    protected function check(WebSocketServer $server, $token, $fd)
    {
        try {
            $config = $this->app->make('config');
            $key    = $config->get("server.route.jwt.key");
            // 对jwt的token进行解析，返回jwt对象
            // 'data' => [
            //     'uid'  => $uid,
            //     'name'  => "client" . $time . $sid, // 用户名
            //     'service_url' => $url,
            // ],
            $jwt = JWT::encode($token, $key, $config->get('server.route.jwt.alg'));

            // 从jwt中获取信息
            $userInfo = $jwt->data;
            var_dump($jwt);
            var_dump($userInfo);
            // 然后绑定路由的关系
            $url = $userInfo->service_url;
            $server->getRedis()->hset($key, $userInfo->uid, \json_encode([
                'fd'   => $fd,
                'name' => $userInfo->name,
            ]));
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * websocket握手连接算法验证
     *
     * @param Request $request
     * @param Response $response
     * @return void
     */
    protected function handShake(Request $request = null, Response $response = null)
    {
        // print_r( $request->header );
        // if (如果不满足我某些自定义的需求条件，那么返回end输出，返回false，握手失败) {
        //    $response->end();
        //     return false;
        // }

        // websocket握手连接算法验证
        $secWebSocketKey = $request->header['sec-websocket-key'];
        $patten = '#^[+/0-9A-Za-z]{21}[AQgw]==$#';
        if (0 === preg_match($patten, $secWebSocketKey) || 16 !== strlen(base64_decode($secWebSocketKey))) {
            $response->end();
            return false;
        }
        // echo $request->header['sec-websocket-key'];
        $key = base64_encode(
            sha1(
                $request->header['sec-websocket-key'] . '258EAFA5-E914-47DA-95CA-C5AB0DC85B11',
                true
            )
        );

        $headers = [
            'Upgrade' => 'websocket',
            'Connection' => 'Upgrade',
            'Sec-WebSocket-Accept' => $key,
            'Sec-WebSocket-Version' => '13',
        ];

        // WebSocket connection to 'ws://127.0.0.1:9502/'
        // failed: Error during WebSocket handshake:
        // Response must not include 'Sec-WebSocket-Protocol' header if not present in request: websocket
        if (isset($request->header['sec-websocket-protocol'])) {
            $headers['Sec-WebSocket-Protocol'] = $request->header['sec-websocket-protocol'];
        }

        foreach ($headers as $key => $val) {
            $response->header($key, $val);
        }

        $response->status(101);
        $response->end();
    }
}
