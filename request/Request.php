<?php
namespace woodlsy\request;

abstract class Request
{
    protected $apiParams;

    abstract public function getApiParams();

    abstract public function getApiMethodName();
}