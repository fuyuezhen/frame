<?php
namespace app\http\controller;

class Index
{
    public function index()
    {
        return file_get_contents(dirname(__DIR__) . "/view/index/index.html");
    }
    public function test()
    {
        return file_get_contents(dirname(__DIR__) . "/view/index/test.html");
    }
}