<?php
/**
 * Created by PhpStorm.
 * User: tongyifan
 * Date: 18-12-28
 * Time: 下午8:04
 */

class API_Response
{
    var $status;
    var $msg;
    var $timestamp;
    var $data;

    public function __construct($data, $status = 0, $msg = '')
    {
        $this->data = $data;
        $this->status = $status;
        $this->msg = $msg;
        $this->timestamp = time();
    }

    /**
     * @return int
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @param int $status
     */
    public function setStatus($status)
    {
        $this->status = $status;
    }

    /**
     * @return string
     */
    public function getMsg()
    {
        return $this->msg;
    }

    /**
     * @param string $msg
     */
    public function setMsg($msg)
    {
        $this->msg = $msg;
    }

    /**
     * @return int
     */
    public function getTimestamp()
    {
        return $this->timestamp;
    }

    /**
     * @param int $timestamp
     */
    public function setTimestamp($timestamp)
    {
        $this->timestamp = $timestamp;
    }

    /**
     * @return mixed
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @param mixed $data
     */
    public function setData($data)
    {
        $this->data = $data;
    }
}