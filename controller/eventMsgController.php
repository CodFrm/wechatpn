<?php
/**
 *============================
 * author:Farmer
 * time:2018/8/20 10:49
 * blog:blog.icodef.com
 * function:
 *============================
 */


namespace controller;


class eventMsgController extends baseMsgController {

    /**
     * 事件处理
     * @return mixed
     */
    protected function msgdeal() {
        // TODO: Implement msgdeal() method.
        $event = $this->post->Event->__toString();
        if (method_exists($this, $event)) {
            return call_user_func([$this, $event]);
        }
    }

    /**
     * 订阅
     * @return string
     */
    protected function subscribe() {
        return '垃圾';
    }

    /**
     * 取消订阅
     * @return string
     */
    protected function unsubscribe() {
        return '取消你妹';
    }
}