<?php
namespace app\web_socket\controller;

class Index
{
    /**
     * 当 WebSocket 客户端与服务器建立连接并完成握手后会回调此函数。
     */
    public function open($ws, $request) {
        $ws->push($request->fd, "hello, welcome\n");
    }
    
    /**
     * 当服务器收到来自客户端的数据帧时会回调此函数。
     */
    public function message($ws, $frame) {
        info("收到客户端 {$frame->fd}:{$frame->data},opcode:{$frame->opcode},fin:{$frame->finish}\n");
        $ws->push($frame->fd, "this is server");
    }
    
    /**
     * TCP 客户端连接关闭后，在 Worker 进程中回调此函数。
     */
    public function close($ws, int $fd, int $reactorId) {
        info("用户 {$fd} 断开连接");
    }
}