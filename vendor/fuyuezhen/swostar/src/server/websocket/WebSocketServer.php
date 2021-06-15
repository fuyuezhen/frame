<?php
namespace swostar\server\websocket;

use swostar\server\Server;
use swostar\server\http\HttpServer;
use Swoole\WebSocket\Server as SwooleServer;
use Swoole\Http\Request;
use Swoole\Http\Response;

/**
 * websocket服务类
 */
class WebSocketServer extends HttpServer
{
    
    /**
     * 创建服务
     *
     * @return void
     */
    protected function createServer(){
        $this->swooleServer = new SwooleServer($this->host, $this->port);
        // $this->swooleServer = new SwooleServer("127.0.0.1", $this->port);

        info("启动WebSocket监听：" . $this->host . ":" . $this->port);
    }

    /**
     * 设置子类回调事件
     * @return void
     */
    protected function setSubEvent()
    {
        $event = [
            'open'    => 'onOpen',
            'message' => 'onMessage',
            'close'   => 'onClose',
        ];

        // 是否自定义handshake 握手的过程
        (!$this->app->make('config')->get('server.ws.is_handshake')) ?: $event['handshake'] = 'onHandShake';
        // 是否开启http服务
        (!$this->app->make('config')->get('server.ws.enable_http')) ?: $event['request'] = 'onRequest';

        $this->event['sub'] = $event;
    }

    /**
     * 初始化设置
     * @return void
     */
    protected function initSetting()
    {
        $config        = app('config');
        $this->tcpable = $config->get('server.ws.tcpable');
        $this->host    = $config->get('server.ws.host');
        $this->port    = $config->get('server.ws.port');
        $this->confing = $config->get('server.ws.swoole');
    }

    /**
     * WebSocket 建立连接后进行握手。WebSocket 服务器会自动进行 handshake 握手的过程，如果用户希望自己进行握手处理，可以设置 onHandShake 事件回调函数。
     * 注意：设置 onHandShake 回调函数后不会再触发 onOpen 事件，需要应用代码自行处理，可以使用 $server->defer 调用 onOpen 逻辑
     * 
     * 这里是自己定义进行握手的处理，来校验用户的token
     * 
     * @param Request $request
     * @param Response $response
     * @return void
     */
    public function onHandShake(Request $swooleRequest, Response $swooleResponse)
    {
        // 触发握手处理的事件，处理token，传入对应的参数，用户请求信息和响应信息都传入。
        $this->app->make('event')->trigger('ws.hand', [$this, $swooleRequest, $swooleResponse]);

        // 设置 onHandShake 回调函数后不会再触发 onOpen 事件，需要应用代码自行处理，所以在此处调用 onOpen 逻辑
        $this->onOpen($this->swooleServer, $swooleRequest);
    }

    /**
     * 当 WebSocket 客户端与服务器建立连接并完成握手后会回调此函数。
     *
     * @param SwooleServer $server
     * @param $request 是一个 HTTP 请求对象，包含了客户端发来的握手请求信息
     * @return void
     */
    public function onOpen(SwooleServer $server, $request) {
        $this->controller("open", $request->server['path_info'], [$server, $request]);
        
        Connections::init($request->fd, $request);
    }
    
    /**
     * 当服务器收到来自客户端的数据帧时会回调此函数。
     *
     * @param SwooleServer $server
     * @param $frame 是 Swoole\WebSocket\Frame 对象，包含了客户端发来的数据帧信息
     * @return void
     */
    public function onMessage(SwooleServer $server, $frame) {
        // 消息回复事件
        $this->app->make('event')->trigger('ws.message.front', [$this, $server, $frame]);
        
        $this->controller("message", (Connections::get($frame->fd)['path']), [$server, $frame]);
    }
    
    /**
     * TCP 客户端连接关闭后，在 Worker 进程中回调此函数。
     *
     * @param SwooleServer $server
     * @param integer $fd           连接的文件描述符，相当于请求连接的ID
     * @return void
     */
    public function onClose($server, int $fd, int $reactorId) {
        if (!empty(Connections::get($fd))) {

            $this->controller("close", (Connections::get($fd)['path']), [$server, $fd, $reactorId]);

            $this->app->make('event')->trigger('ws.close', [$this, $server, $fd]);

            Connections::del($fd);
        }
    }

    /**
     * 分发大对应控制器执行事项
     *
     * @param [type] $method
     * @param [type] $path
     * @param [type] $param
     * @return void
     */
    protected function controller($method, $path, $param)
    {
        app("route")->setFlag('web_socket')->setMethod($method)->match($path, $param);
    }

    /**
     * 向所有连接方发送信息
     *
     * @param [type] $msg
     * @return void
     */
    public function sendAll($msg)
    {
        \Swoole\Coroutine::sleep(1);// 此处延迟发送，等待route客户端关闭后在通知，否则有可能会push失败。

        // $connections 遍历所有websocket连接用户的fd，给所有用户推送
        foreach ($this->swooleServer->connections as $fd) {
            // 需要先判断是否是正确的websocket连接，否则有可能会push失败
            if ($this->swooleServer->exist($fd)) {
                $this->swooleServer->push($fd, $msg);
            }
        }
    }
}