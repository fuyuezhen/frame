<?php
namespace swostar\event;

/**
 * 事件监听父类
 */
abstract class Listener
{
    /**
     * 事件名称你
     *
     * @var string
     */
    protected $name = "interface";

    /**
     * 入口实例
     *
     * @var [type]
     */
    protected $app;

    /**
     * 初始化
     *
     * @param [type] $app
     */
    public function __construct($app) {
        $this->app = $app;
    }

    /**
     * 事件处理程序的方法
     *
     * @return void
     */
    public abstract function handler();

    /**
     * 获取事件名称
     *
     * @return void
     */
    public function getName()
    {
        return $this->name;
    }
}