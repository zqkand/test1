<?php
//引入配置文件
require './wechat.cfg.php';
//定义一个wechat类，用来存储调用微信接口的方法
class Wechat{
  //封装
  //public  公共的   都可以调用
  //protected  继承类可以调用
  //private　本类可以调用
  private $appid;
  private $appsecret;
  //实列化会触发一个方法
  public function __construct(){
    //初始化操作，赋值
    $this->appid = APPID;
    $this->appsecret = APPSECRET;
  }
  //封装一个请求方法
  public function request($url,$https=true,$method='get',$data=null){
    //1.初始化
    $ch = curl_init($url);
    //2.设置curl
    //返回数据不输出
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    //满足https
    if($https === true){
      //绕过ssl验证
      curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
      curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    }
    //满足post
    if($method === 'post'){
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
  public function getAccessToken(){
    //1.url
    $url = 'https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid='.$this->appid.'&secret='.$this->appsecret;
    //2.请求方式
    //3.发送请求
    $content = $this->request($url);
    //4.处理返回值
    //转json为obj
    $content = json_decode($content);
    echo $content->access_token;
  }
  //获取access_token　保存到文件
  public function getAccessTokenByFile(){
    $fileName = './access_token';
    //先取文件如果没有文件或者过期就从网络取
    //判断文件存在，并没有超过7200s
    if(file_exists($fileName) && (time() - filemtime($fileName)) < 7200){
      $access_token = file_get_contents($fileName);
    }else{
    //1.url
    $url = 'https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid='.$this->appid.'&secret='.$this->appsecret;
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
  public function getAccessTokenByMem(){
    //取memcache里看有没有数据
    $mem = new Memcache();
    $mem->connect('127.0.0.1', 11211);
    $access_token = $mem->get('accessToken');
    if($access_token === false){
      //1.url
      $url = 'https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid='.$this->appid.'&secret='.$this->appsecret;
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
  public function getTicket($scene_id,$tmp=true,$expire_seconds=604800){
    //1.url地址
    $url = 'https://api.weixin.qq.com/cgi-bin/qrcode/create?access_token='.$this->getAccessTokenByMem();
    //2.请求方法
    //判断临时还是永久的
    if($tmp === true){
      $data = '{"expire_seconds": '.$expire_seconds.', "action_name": "QR_SCENE", "action_info": {"scene": {"scene_id": '.$scene_id.'}}}';
    }else{
      $data = '{"action_name": "QR_LIMIT_SCENE", "action_info": {"scene": {"scene_id": '.$scene_id.'}}}';
    }
    //3.发送请求
    $content = $this->request($url,true,'post',$data);
    //4.处理返回值
    //json转对象
    $content = json_decode($content);
    echo $content->ticket;
  }
  //换取二维码
  public function getQRCode(){
    $ticket = 'gQH-8DwAAAAAAAAAAS5odHRwOi8vd2VpeGluLnFxLmNvbS9xLzAyQWRKQU1abzVkQjMxT2FGTGhvMWIAAgQK7_VYAwSAOgkA';
    //1.url
    $url = 'https://mp.weixin.qq.com/cgi-bin/showqrcode?ticket='.$ticket;
    //2.请求方式
    //3.发送请求
    $content = $this->request($url);
    echo file_put_contents('./qrcode.jpg', $content);
    // header('Content-Type:image/jpg');
    // echo $content;
  }
  //创建菜单
  public function createMenu(){
    //1.url
    $url = 'https://api.weixin.qq.com/cgi-bin/menu/create?access_token='.$this->getAccessTokenByMem();
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
    $content = $this->request($url,true,'post',$data);
    //4.处理返回值
    //json转obj
    $content = json_decode($content);
    // {"errcode":0,"errmsg":"ok"}
    if($content->errmsg == 'ok'){
      echo '创建成功!';
    }else{
      echo '创建失败!'.'<br />';
      echo '失败原因:'.$content->errcode;
    }
  }
  //查询菜单
  public function showMenu(){
    //1.url
    $url = 'https://api.weixin.qq.com/cgi-bin/menu/get?access_token='.$this->getAccessTokenByMem();
    //2.请求方式
    //3.发送请求
    $content = $this->request($url);
    var_dump($content);
  }
  //删除菜单
  public function delMenu(){
    //1.url
    $url = 'https://api.weixin.qq.com/cgi-bin/menu/delete?access_token='.$this->getAccessTokenByMem();
    //2.请求方式
    //3.发送请求
    $content = $this->request($url);
    //json转obj
    $content = json_decode($content);
    // {"errcode":0,"errmsg":"ok"}
    if($content->errmsg == 'ok'){
      echo '删除成功!';
    }else{
      echo '删除失败!'.'<br />';
      echo '失败原因:'.$content->errcode;
    }
  }
  public function getUserList(){
      $url = 'https://api.weixin.qq.com/cgi-bin/user/get?access_token='.$this->getAccessTokenByMem();
      //判断请求
      //发送请求
      $content = $this->request($url);
      //json 转化为obj
      $content = json_decode($content);
      //

      //处理返回数据,
      echo "关注数: $content->total <br/>";
      echo "拉取数: $content->count <br/>";
      foreach($content->data->openid as $key => $value){
          echo ($key+1).'###'.$value.'<br/>';
      }
  }
  //获取用户基本信息
    public function getUserInfo(){
      $url = 'https://api.weixin.qq.com/cgi-bin/user/info?access_token='.$this->getAccessTokenByMem().'&openid=oo8Dmw5O52MaTIAlE7xSXrvvXEx0&lang=zh_CN';

      //判断请求方式 get
        //发送请求
        $content = $this->request($url);
        //处理返回值
        //json 转对象
        $content = json_decode($content);
        //dump($content);

        echo "昵称: $content->nickname <br/>";
        echo "性别: $content->sex <br/>";
        echo "省份: $content->province<br/>";
        echo "头像<br/><img src='$content->headimgurl'>";
    }

    //通过mediaID获取素材
    public function getMediaFile(){
        $media_id = '';
        $url = ''.$this->getAccessTokenByMem().'&media_id='.$media_id;
        //判断请求
        //发送请求
        $content = $this->request($url);
        //返回的是素材文件
        //存储文件
        //通过curl获取到的头信息,获取文件类型
        file_put_contents('./girl.jpg',$content);
    }
}