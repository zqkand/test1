<?php

//引入配置文件
require './wechat.cfg.php';

//定义一个wechat类，用来存储调用微信接口的方法
class Wechat
{

    //封装
    //public  公共的   都可以调用
    //protected  继承类可以调用
    //private　本类可以调用
    private $appid;
    private $appsecret;
    private $token;

    //实列化会触发一个方法
    public function __construct()
    {
        //初始化操作，赋值
        $this->appid = APPID;
        $this->appsecret = APPSECRET;
        $this->token = TOKEN;
        $this->textTpl = "<xml>
            <ToUserName><![CDATA[%s]]></ToUserName>
            <FromUserName><![CDATA[%s]]></FromUserName>
            <CreateTime>%s</CreateTime>
            <MsgType><![CDATA[%s]]></MsgType>
            <Content><![CDATA[%s]]></Content>
            <FuncFlag>0</FuncFlag>
            </xml>";
    }

    //校验验证方法
    public function valid()
    {
        $echoStr = $_GET["echostr"];

        //valid signature , option
        if ($this->checkSignature()) {
            echo $echoStr;
            exit;
        }
    }
    //消息管理方法
    //微信公众号和手机终端用户
    //进行接收和回复信息的方法
    public function responseMsg()
    {
        //get post data, May be due to the different environments
        $postStr = $GLOBALS["HTTP_RAW_POST_DATA"];
        //extract post data
        if (!empty($postStr)) {
            /* libxml_disable_entity_loader is to prevent XML eXternal Entity Injection,
              the best way is to check the validity of xml by yourself */
            libxml_disable_entity_loader(true);
            // file_put_contents('./data.xml',$postStr);
            $postObj = simplexml_load_string($postStr, 'SimpleXMLElement', LIBXML_NOCDATA);
            //判断接收到的消息使用特定的方法处理
            switch ($postObj->MsgType) {
                case 'text':
                    $this->doText($postObj);    //文本消息处理
                    break;
                case 'image':
                    $this->doImage($postObj);   //图片消息处理
                    break;
                case 'location':
                    $this->doLocation(); //地理位置消息处理
                    break;
                case 'event':
                    $this->doEvent();   //事件消息处理
                    break;
                default:
                    # code...
                    break;
            }

        }
    }
    //处理文本消息方法
    private function doText($postObj){
        $keyword = trim($postObj->Content);
        if (!empty($keyword)) {
            $msgType = "text";
            $contentStr = "Welcome to wechat world!";
            //接入自动回复机器人
            $url = 'http://api.qingyunke.com/api.php?key=free&appid=0&msg='.$keyword;
            $content = $this->request($url,false);
            $content = json_decode($content);
            $contentStr = $content->content;
            $contentStr = str_replace('{br}', "\r\n", $contentStr);
            //根据实际用户的需求，返回对应回应
            if($keyword == '你是谁'){
                $contentStr = '我是黑马3期的小秘书!';
            }
            $resultStr = sprintf($this->textTpl, $postObj->FromUserName, $postObj->ToUserName, time(), $msgType, $contentStr);
            // file_put_contents('./test.xml',$resultStr);
            echo $resultStr;
        }
    }
    //处理图片消息方法
    private function doImage($postObj){
        $PicUrl = $postObj->PicUrl;
        $resultStr = sprintf($this->textTpl, $postObj->FromUserName, $postObj->ToUserName, time(), 'text', $PicUrl);
        file_put_contents('./test.xml',$resultStr);
        echo $resultStr;
    }
    //校验签名方法
    private function checkSignature()
    {
        // you must define TOKEN by yourself
        if (!defined("TOKEN")) {
            throw new Exception('TOKEN is not defined!');
        }
        $signature = $_GET["signature"];
        $timestamp = $_GET["timestamp"];
        $nonce = $_GET["nonce"];
        $token = $this->token;
        $tmpArr = array($token, $timestamp, $nonce);
        // use SORT_STRING rule
        sort($tmpArr, SORT_STRING);
        $tmpStr = implode($tmpArr);
        $tmpStr = sha1($tmpStr);
        if ($tmpStr == $signature) {
            return true;
        } else {
            return false;
        }
    }

    //封装一个请求方法
    public function request($url, $https = true, $method = 'get', $data = null)
    {
        //1.初始化
        $ch = curl_init($url);
        //2.设置curl
        //返回数据不输出
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        //满足https
        if ($https === true) {
            //绕过ssl验证
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        }
        //满足post
        if ($method === 'post') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        }
        //3.发送请求
        $content = curl_exec($ch);
        //4.关闭资源
        curl_close($ch);
        return $content;
    }

    //获取access_token
    public function getAccessToken()
    {
        //1.url
        $url = 'https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=' . $this->appid . '&secret=' . $this->appsecret;
        //2.请求方式
        //3.发送请求
        $content = $this->request($url);
        //4.处理返回值
        //转json为obj
        $content = json_decode($content);
        echo $content->access_token;
    }

    //获取access_token　保存到文件
    public function getAccessTokenByFile()
    {
        $fileName = './access_token';
        //先取文件如果没有文件或者过期就从网络取
        //判断文件存在，并没有超过7200s
        if (file_exists($fileName) && (time() - filemtime($fileName)) < 7200) {
            $access_token = file_get_contents($fileName);
        } else {
            //1.url
            $url = 'https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=' . $this->appid . '&secret=' . $this->appsecret;
            //2.请求方式
            //3.发送请求
            $content = $this->request($url);
            //4.处理返回值
            //转json为obj
            $content = json_decode($content);
            $access_token = $content->access_token;
            //把取到的数据存储起来
            file_put_contents($fileName, $access_token);
        }
        echo $access_token;
    }

    //获取access_token 保存到memcache或者redis
    public function getAccessTokenByMem()
    {
        //取memcache里看有没有数据
        $mem = new Memcache();
        $mem->connect('127.0.0.1', 11211);
        $access_token = $mem->get('accessToken');
        if ($access_token === false) {
            //1.url
            $url = 'https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=' . $this->appid . '&secret=' . $this->appsecret;
            //2.请求方式
            //3.发送请求
            $content = $this->request($url);
            //4.处理返回值
            //转json为obj
            $content = json_decode($content);
            $access_token = $content->access_token;
            //网络取到之后，再存储到memcache里
            $mem->set('accessToken', $access_token, 0, 7000);
        }
        return $access_token;
    }

    //获取ticket
    public function getTicket($scene_id, $tmp = true, $expire_seconds = 604800)
    {
        //1.url地址
        $url = 'https://api.weixin.qq.com/cgi-bin/qrcode/create?access_token=' . $this->getAccessTokenByMem();
        //2.请求方法
        //判断临时还是永久的
        if ($tmp === true) {
            $data = '{"expire_seconds": ' . $expire_seconds . ', "action_name": "QR_SCENE", "action_info": {"scene": {"scene_id": ' . $scene_id . '}}}';
        } else {
            $data = '{"action_name": "QR_LIMIT_SCENE", "action_info": {"scene": {"scene_id": ' . $scene_id . '}}}';
        }
        //3.发送请求
        $content = $this->request($url, true, 'post', $data);
        //4.处理返回值
        //json转对象
        $content = json_decode($content);
        echo $content->ticket;
    }

    //换取二维码
    public function getQRCode()
    {
        $ticket = 'gQH-8DwAAAAAAAAAAS5odHRwOi8vd2VpeGluLnFxLmNvbS9xLzAyQWRKQU1abzVkQjMxT2FGTGhvMWIAAgQK7_VYAwSAOgkA';
        //1.url
        $url = 'https://mp.weixin.qq.com/cgi-bin/showqrcode?ticket=' . $ticket;
        //2.请求方式
        //3.发送请求
        $content = $this->request($url);
        echo file_put_contents('./qrcode.jpg', $content);
        // header('Content-Type:image/jpg');
        // echo $content;
    }

    //创建菜单
    public function createMenu()
    {
        //1.url
        $url = 'https://api.weixin.qq.com/cgi-bin/menu/create?access_token=' . $this->getAccessTokenByMem();
        //2.请求方式
        $data = '{
              "button":[
              {
                   "type":"click",
                   "name":"今日资讯",
                   "key":"news"
               },
               {
                    "name":"黑马3期",
                    "sub_button":[
                    {
                        "type":"view",
                        "name":"百度",
                        "url":"http://www.baidu.com/"
                     },
                     {
                        "type":"view",
                        "name":"H5",
                        "url":"http://panteng.me/demos/whb/"
                     },
                     {
                        "name": "发送位置",
                        "type": "location_select",
                        "key": "rselfmenu_2_0"
                     }]
                }]
             }';
        //3.发送请求
        $content = $this->request($url, true, 'post', $data);
        //4.处理返回值
        //json转obj
        $content = json_decode($content);
        // {"errcode":0,"errmsg":"ok"}
        if ($content->errmsg == 'ok') {
            echo '创建成功!';
        } else {
            echo '创建失败!' . '<br />';
            echo '失败原因:' . $content->errcode;
        }
    }

    //查询菜单
    public function showMenu()
    {
        //1.url
        $url = 'https://api.weixin.qq.com/cgi-bin/menu/get?access_token=' . $this->getAccessTokenByMem();
        //2.请求方式
        //3.发送请求
        $content = $this->request($url);
        var_dump($content);
    }

    //删除菜单
    public function delMenu()
    {
        //1.url
        $url = 'https://api.weixin.qq.com/cgi-bin/menu/delete?access_token=' . $this->getAccessTokenByMem();
        //2.请求方式
        //3.发送请求
        $content = $this->request($url);
        //json转obj
        $content = json_decode($content);
        // {"errcode":0,"errmsg":"ok"}
        if ($content->errmsg == 'ok') {
            echo '删除成功!';
        } else {
            echo '删除失败!' . '<br />';
            echo '失败原因:' . $content->errcode;
        }
    }

    //获取用户openID
    public function getUserList()
    {
        //1.url
        $url = 'https://api.weixin.qq.com/cgi-bin/user/get?access_token=' . $this->getAccessTokenByMem();
        //2.判断请求
        //3.发送请求
        $content = $this->request($url);
        //4.处理返回值
        //json转obj
        $content = json_decode($content);
        // var_dump($content);
        echo '关注数:' . $content->total . '<br />';
        echo '拉数:' . $content->count . '<br />';
        foreach ($content->data->openid as $key => $value) {
            echo ($key + 1) . '###' . $value . '<br />';
        }
    }

    //通过openID列表获取用户基本信息
    public function getUserInfo()
    {
        $openid = 'openid=oo8Dmw5O52MaTIAlE7xSXrvvXEx0';
        //1.url
        $url = 'https://api.weixin.qq.com/cgi-bin/user/info?access_token=' . $this->getAccessTokenByMem() . '&openid=' . $openid . '&lang=zh_CN';
        //2.判断请求方式
        //3.发送请求
        $content = $this->request($url);
        //4.处理返回值
        //json转obj
        $content = json_decode($content);
        // var_dump($content);
        echo '昵称:' . $content->nickname . '<br />';
        echo '性别:' . $content->sex . '<br />';
        echo '省份:' . $content->province . '<br />';
        echo '<img src="' . $content->headimgurl . '" style="width:100px;" />';
    }

    //通过mediaID获取素材
    public function getMediaFile()
    {
        $media_id = 'CNTAqFMPMID4sS3n7CS4J-Sz6Bgl0NSjlL0RnpXD6LsfU2-8sTUX0ihK6bFEVsED';
        //1.url
        $url = 'https://api.weixin.qq.com/cgi-bin/media/get?access_token=' . $this->getAccessTokenByMem() . '&media_id=' . $media_id;
        //2.判断请求
        //3.发送请求
        $content = $this->request($url);
        //4.处理返回值
        //返回的是素材文件
        //存储图片
        //通过curl获取到的header头信息获取到文件类型
        echo file_put_contents('./girl.jpg', $content);
    }

}
