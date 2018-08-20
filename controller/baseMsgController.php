<?php
/**
 *============================
 * author:Farmer
 * time:2018/8/20 10:39
 * blog:blog.icodef.com
 * function:
 *============================
 */


namespace controller;


abstract class baseMsgController {

    /**
     * @var \SimpleXMLElement
     */
    protected $post;

    public function __construct($post) {
        $this->post = $post;
        if (!$this->verify()) {
            return;
        }
        $msg = $this->msgdeal();
        if (is_array($msg)) {
            echo $this->reply($msg);
        } else if (is_string($msg)) {
            echo $this->replyText($msg);
        }
    }

    /**
     * 验证数据是否正确
     * @return bool
     */
    protected function verify(): bool {
        if (!(isset($_GET['openid']) == $this->post->FromUserName->__toString())) {
            return false;
        }
        if (abs($_GET['timestamp'] - $this->post->CreateTime) > 5) {
            return false;
        }
        return true;
    }

    /**
     * 消息处理
     * @return mixed
     */
    abstract protected function msgdeal();

    /**
     * 回复xml文本
     * @param array $array
     * @return string
     */
    public function reply(array $array, bool $out = false): string {
        $array = $this->replyDefault($array);
        $text = '<xml>';
        foreach ($array as $key => $value) {
            if (is_string($value)) {
                $text .= "<$key><![CDATA[$value]]></$key>";
            } else {
                $text .= "<$key>$value</$key>";
            }
        }
        $text .= '</xml>';
        if ($out) {
            echo $text;
        }
        return $text;
    }

    /**
     * 构造回复信息的一些默认值
     * @param array $array
     * @return array
     */
    protected function replyDefault(array $array): array {
        if (!isset($array['ToUserName'])) {
            $array['ToUserName'] = $this->post->FromUserName->__toString();
        }
        if (!isset($array['FromUserName'])) {
            $array['FromUserName'] = $this->post->ToUserName->__toString();
        }
        if (!isset($array['CreateTime'])) {
            $array['CreateTime'] = time();
        }
        return $array;
    }

    /**
     * 回复文本信息
     * @param string $text
     * @return string
     */
    public function replyText(string $text): string {
        return $this->reply(['MsgType' => 'text', 'Content' => $text]);
    }
}