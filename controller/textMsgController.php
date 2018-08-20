<?php
/**
 *============================
 * author:Farmer
 * time:2018/8/20 10:32
 * blog:blog.icodef.com
 * function:
 *============================
 */


namespace controller;


use lib\db;

class textMsgController extends baseMsgController {

    protected function msgdeal() {
        // TODO: Implement msgdeal() method.
        if (!isset($this->post->Content)) return;
        $content = $this->post->Content->__toString();

        return $this->post->Content->__toString();
    }

}