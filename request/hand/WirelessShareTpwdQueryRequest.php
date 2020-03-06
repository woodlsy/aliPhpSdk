<?php
namespace woodlsy\aliPhpSdk\request\hand;

use woodlsy\aliPhpSdk\request\Request;

class WirelessShareTpwdQueryRequest extends Request
{
    private $passwordContent;

    public function getApiMethodName()
    {
        return "taobao.wireless.share.tpwd.query";
    }


    public function setPasswordContent(string $content)
    {
        $this->passwordContent = $content;
        $this->apiParams['password_content'] = $content;
    }

    public function getApiParams()
    {
        return $this->getApiParams();
    }
}