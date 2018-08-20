<?php
/**
 *============================
 * author:Farmer
 * time:2018/8/20 10:13
 * blog:blog.icodef.com
 * function:核心
 *============================
 */
require_once 'lib/functions.php';

class core {

    public $config;

    protected $post;

    public function __construct() {
        $this->config = require_once 'config.php';
        spl_autoload_register([$this, 'autoload']);
    }

    /**
     * 自动加载
     * @param $class
     */
    public function autoload($class) {
        $class = str_replace('\\', '/', $class);
        require_once $class . '.php';
    }

    /**
     * 开始运行,对一些数据进行处理
     */
    public function run() {
        if (!isset_r($_GET, 'signature,timestamp,nonce')) {
            die('error request');
        }
        $array = array($this->config ['token'], $_GET['timestamp'], $_GET['nonce']);
        sort($array, SORT_STRING);
        $str = implode($array);
        $sign = sha1($str);
        if ($sign == $_GET['signature']) {
            //校验成功,开始处理接收内容
            if (isset($_GET['echostr'])) {
                die($_GET['echostr']);
            }
            $this->post = file_get_contents('php://input');
            if ($this->post == '') {
                return;
            }
            $this->start();
        }
    }

    /**
     * 开始对接,解析接收到的xml
     */
    protected function start() {
        $this->post = simplexml_load_string($this->post);
        if (!isset($this->post->MsgType)) {
            die('error');
        }
        //初始化数据库链接
        \lib\db::init($this->config['db']['host'], $this->config['db']['user'],
            $this->config['db']['pwd'], $this->config['db']['db'],
            $this->config['db']['prefix']
        );

        $class = 'controller\\' . $this->post->MsgType->__toString() . 'MsgController';
        new $class($this->post);
    }
}