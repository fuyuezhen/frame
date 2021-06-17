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
class WSMessageFrontListener extends Listener
{
    /**
     * 事件名称
     *
     * @var string
     */
    protected $name = "ws.message.front";

    /**
     * 事件处理程序的方法
     *
     * @param swoStarServer $swoStarServer
     * @param SwooleServer $swooleServer
     * @param [type] $frame
     * @return void
     */
    public function handler(swoStarServer $swoStarServer = null, SwooleServer $swooleServer = null, $frame = null)
    {
        /**
         * 对于参数设置
         * {
         *  "method":"方法类型 privateChat:私聊 | serverBroadcast:广播",
         *  "msg":"发送的信息",
         *  "target":"发送的目标"
         *  }
         */
        $data = json_decode($frame->data, true);
        $this->{$data['method']}($swoStarServer, $swooleServer, $data, $frame->fd);
    }

    /**
     * 私聊：对所有服务器进行消息发送
     *
     * @param swoStarServer $swoStarServer
     * @param SwooleServer $swooleServer
     * @param [type] $data
     * @param [type] $fd
     * @return void
     */
    protected function privateChat(swoStarServer $swoStarServer, SwooleServer $swooleServer, $data, $fd)
    {
        // 1. 获取私聊用户ID (不是fd)
        $clientId = $data['clientId'];         
        // 'data' => [
        //     'fd'          => $fd,
        //     'name'        => "client" . $time . $sid, // 用户名
        //     'service_url' => $url,
        // ],
        // 2. 根据用户uid获取对应的服务器信息
        $clientIMServerInfoJson = $swoStarServer->getRedis()->hGet($this->app->make('config')->get('server.route.jwt.key'), $clientId);
        $clientIMServerInfo     = json_decode($clientIMServerInfoJson, true);

        // 3. 指定发送
        $clientIMServerUrl = explode(":", $clientIMServerInfo['service_url']);
        $clientFd          = $clientIMServerInfo['fd'];

        // 生成token
        // 因为这个是转发消息客户端，转发完信息后会自动断开连接，如果直接使用当前主客户端的token，那么在断开连接的时候会清楚redis的记录，这个是不行的
        $token = $this->getJwtToken(0, $clientIMServerInfo['service_url']); 

        // 发送
        $swoStarServer->send($clientIMServerUrl[0], $clientIMServerUrl[1], [
            'method' => 'forwarding',
            'msg'    => $data['msg'],
            'fd'     => $clientFd,
        ], [
            'sec-websocket-protocol' =>  $token
        ]);
    }

    /**
     * 获取Token
     *
     * @param [type] $uid 用户ID
     * @param [type] $url 连接的地址
     * @return void
     */
    protected function getJwtToken($uid, $url)
    {
        // iss：jwt签发者
        // aud：接受jwt的一方
        // sub：jwt所面向的用户
        // iat：签发时间
        // nbf：生效时间
        // exp：jwt的过期时间
        // jti：jwt的唯一身份标识，主要用来作为一次性token，从而回避重放攻击

        $key   = "swocloud";
        $time  = time();
        $token = [
            'iss' => "http://192.168.218.30", // 可选参数
            'aud' => "http://192.168.218.30", // 可选参数
            'iat' => $time, // 签发时间
            'nbf' => $time, // 生效时间
            'exp' => $time + 7200, // 过期时间
            'data' => [
                'uid'         => $uid,
                'name'        => "client_" . $time . "_" . $uid, // 用户名
                'service_url' => $url,
            ],
        ];
        return \Firebase\JWT\JWT::encode($token, $key);
    }


    /**
     * 广播：对所有服务器进行消息发送
     * 通过Route服务器对所有服务器进行广播（不选择当前服务器广播是因为，服务器自己需要执行相应的业务，压力会大，性能可能会受影响）
     *
     * @param swoStarServer $swoStarServer
     * @param SwooleServer $swooleServer
     * @param [type] $data
     * @param [type] $fd
     * @return void
     */
    protected function serverBroadcast(swoStarServer $swoStarServer, SwooleServer $swooleServer, $data, $fd)
    {
        $config = $this->app->make('config');
        /**
         * 发送给Route服务器的信息 =》 当前自己服务器的ip和port
         */
        $client = new \Swoole\Coroutine\Http\Client($config->get('server.route.server.host'), $config->get('server.route.server.port'));
        $ret = $client->upgrade("/"); // 升级为 WebSocket 连接。
        if ($ret) {
            $data = [
                'method'      => 'routeBroadcast',
                'msg'         => $data['msg'],
                'ip'          => $swoStarServer->getHost(),
                'port'        => $swoStarServer->getPort(),
            ];
            $client->push(json_encode($data));
        }
        $client->close();
    }
    
    /**
     * 接收Route服务器的广播信息
     *
     * @param swoStarServer $swoStarServer
     * @param SwooleServer $swooleServer
     * @param [type] $data
     * @param [type] $fd
     * @return void
     */
    protected function routeBroadcast(swoStarServer $swoStarServer, SwooleServer $swooleServer, $data, $fd)
    {
        $dataAck = [
            'method' => 'ack',
            'msg_id' => $data['msg_id'],
        ];
        $swooleServer->push($fd, json_encode($dataAck));
        // 接收之后可能有其他的业务
        // ....
        // 想所有连接方发送信息
        $swoStarServer->sendAll(json_encode($data));
    }

    /**
     * 转发信息给对应的客户端
     *
     * @param swoStarServer $swoStarServer
     * @param SwooleServer $swooleServer
     * @param [type] $data
     * @param [type] $fd
     * @return void
     */
    protected function forwarding(swoStarServer $swoStarServer, SwooleServer $swooleServer, $data, $fd)
    {
        $swooleServer->push($data['fd'], json_encode(['msg' => $data['msg']]));
    }
}
