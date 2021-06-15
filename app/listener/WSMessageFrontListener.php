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
        $this->{$data['method']}($swoStarServer, $swooleServer, $data['data'], $frame->fd);
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
        // 接收之后可能有其他的业务
        // ....
        // 想所有连接方发送信息
        var_dump($data);
        var_dump($data['msg']);
        $swoStarServer->sendAll($data['msg']);
    }

}
