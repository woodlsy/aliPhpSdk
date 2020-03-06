<?php
namespace woodlsy\request;

use woodlsy\httpClient\HttpCurl;

class Client
{
    public $appkey;

    public $secretKey;

    public $gatewayUrl = "http://gw.api.taobao.com/router/rest";

    public $format = "xml";

    protected $signMethod = "md5";

    protected $apiVersion = "2.0";

    public function execute(Request $request)
    {
        $result = new \stdClass();
        //组装系统参数
        $sysParams = [];
        $sysParams["app_key"] = $this->appkey;
        $sysParams["v"] = $this->apiVersion;
        $sysParams["format"] = $this->format;
        $sysParams["sign_method"] = $this->signMethod;
        $sysParams["method"] = $request->getApiMethodName();
        $sysParams["timestamp"] = date("Y-m-d H:i:s");

        //获取业务参数
        $apiParams = $request->getApiParams();

        //系统参数放入GET请求串
        $requestUrl = $this->gatewayUrl."?";

        //签名
        $sysParams["sign"] = $this->generateSign(array_merge($apiParams, $sysParams));

        foreach ($sysParams as $sysParamKey => $sysParamValue)
        {
            // if(strcmp($sysParamKey,"timestamp") != 0)
                $requestUrl .= "$sysParamKey=" . urlencode($sysParamValue) . "&";
        }

        /*$fileFields = array();
        foreach ($apiParams as $key => $value) {
            if(is_array($value) && array_key_exists('type',$value) && array_key_exists('content',$value) ){
                $value['name'] = $key;
                $fileFields[$key] = $value;
                unset($apiParams[$key]);
            }
        }*/

        $requestUrl = substr($requestUrl, 0, -1);

        //发起HTTP请求
        try
        {
            $resp = (new HttpCurl())->setUrl($requestUrl)->setData($apiParams)->post();
        }catch (\Exception $e)
        {
            $result->code = $e->getCode();
            $result->msg = $e->getMessage();
            return $result;
        }

        unset($apiParams);

        //解析TOP返回结果
        $respWellFormed = false;
        if ("json" == $this->format)
        {
            $respObject = json_decode($resp);
            if (null !== $respObject)
            {
                $respWellFormed = true;
                foreach ($respObject as $propKey => $propValue)
                {
                    $respObject = $propValue;
                }
            }
        }
        else if("xml" == $this->format)
        {
            $respObject = @simplexml_load_string($resp);
            if (false !== $respObject)
            {
                $respWellFormed = true;
            }
        } else {
            $result->code = -1;
            $result->msg = "定义错误的数据类型";
            return $result;
        }

        //返回的HTTP文本不是标准JSON或者XML，记下错误日志
        if (false === $respWellFormed)
        {
            // TODO 记日志
//            $this->logCommunicationError($sysParams["method"],$requestUrl,"HTTP_RESPONSE_NOT_WELL_FORMED",$resp);
            $result->code = 0;
            $result->msg = "HTTP_RESPONSE_NOT_WELL_FORMED";
            return $result;
        }

        //如果TOP返回了错误码，记录到业务错误日志中
        if (isset($respObject->code))
        {
            // TODO 记日志
//            $logger = new TopLogger;
//            $logger->conf["log_file"] = rtrim(TOP_SDK_WORK_DIR, '\\/') . '/' . "logs/top_biz_err_" . $this->appkey . "_" . date("Y-m-d") . ".log";
//            $logger->log(array(
//                date("Y-m-d H:i:s"),
//                $resp
//            ));
        }
        return $respObject;

    }

    protected function generateSign($params)
    {
        ksort($params);

        $stringToBeSigned = $this->secretKey;
        foreach ($params as $k => $v)
        {
            if(!is_array($v) && "@" != substr($v, 0, 1))
            {
                $stringToBeSigned .= "$k$v";
            }
        }
        unset($k, $v);
        $stringToBeSigned .= $this->secretKey;

        return strtoupper(md5($stringToBeSigned));
    }
}