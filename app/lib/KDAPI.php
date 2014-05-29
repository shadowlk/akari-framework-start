<?php
Class KDAPI{
    const VERSION = "1.1";
    const BASEURL = "http://kdays.cn/api_v2/";

    public $appkey;
    public $secret;
    protected static $k;
    
    /**
     * 单例
     * 
     * @return KDAPI
     */
    public static function getInstance(){
        if(!isset(self::$k)){
            self::$k = new self();
        }
        
        return self::$k;
    }
    
    /**
     * 构造函数
     */
    protected function __construct(){
        $config = C("KDAPI");
        if(!empty($config)){
            $this->setAppkey($config['appkey'], $config['secret']);
        }
    }
    
    /**
     * 设置Appkey
     * 
     * @param string $appkey APPKEY
     * @param string $secret 秘密key
     * @todo 设置中有config的kdapi有设定，构造时会自动调用
     */
    public function setAppkey($appkey, $secret){
        $this->appkey = $appkey;
        $this->secret = $secret;
    }
    
    /**
     * url请求
     * 
     * @param string $url URL地址
     * @param string $data 数据数组
     * @return boolean|mixed
     */
    public function doReq($url, $data){
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_URL, $url); 
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_setopt($ch, CURLOPT_USERAGENT, "KDays APIHelper/1.0");
        
        if(!empty($data)){
            curl_setopt($ch, CURLOPT_POST, TRUE);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data, '', '&'));
        }
        
        $data = curl_exec($ch);
        curl_close($ch);
        
        if(!$data){
            return FALSE;
        }        
        return json_decode($data);
    }
    
    /**
     * 运行API的请求
     * 
     * @param string $action
     * @param array $param
     * @return mixed
     */
    public function run($action, $param = array()){
        $url = KDAPI::BASEURL.$action."?appkey=".$this->appkey;
        if(array_key_exists("sig", $param)){
            $url .= "&".$this->createSig($param['sig']);
            unset($param['sig']);
        }
        
        foreach($param as $key => $value){
            if(substr($key, 0, 2) == "G."){
                $url .= "&".substr($key, 2)."=".urlencode($value);
                unset($param[$key]);
            }
        }
        
        return $this->doReq($url, $param);
    }
    
    /**
     * 返回签名
     * 
     * @param string $text 数据文字
     * @return string
     */
    public function createSig($text = 'KD'){
        $sigTime = TIMESTAMP;
        $sig = md5($text."_".$this->secret."_".$sigTime);
        
        return "sig=$sig&sig_time=$sigTime";
    }
    
    
    /********** 以下是操作 ***********/
    
    /**
     * 获得Access Token
     * 
     * @param string $username 用户名
     * @param string $password MD5后的密码
     * @param number $expire 有效期
     * @return Ambigous <mixed, boolean>
     */
    public function getAccessToken($username, $password, $expire = 864000){
        return $this->run("authorize/access_token", array(
        	"G.username" => base64_encode($username),
            "G.password" => md5($password),
            "G.lifetime" => $expire,
            "sig" => "token"
        ));
    }
    
    /**
     * 根据Token获得有效期和用户名
     * 
     * @param string $token accesstoken
     * @return Ambigous <mixed, boolean>
     */
    public function getTokenInfo($token){
        return $this->run("authorize/token_info", Array(
        	"G.token" => $token
        ));
    }
    
    /**
     * 添加动态
     * 
     * @param mixed $uid 用户uid(也可以传递access token)
     * @param string $type 类型
     * @param string $descrip 动态的说明
     * @param string $value1 参数1
     * @param string $value2 参数2
     * @return Ambigous <mixed, boolean>
     * @todo 在7天内，相同内容的动态不允许添加。 具体可以检查返回的insert 为true插入成功 false代表已经存在了
     */
    public function addFeed($uid, $type, $descrip, $value1, $value2){
        $pk = "G.uid";
        if(!is_numeric($uid))   $pk = "G.token";
        
        return $this->run("account/add_feed", Array(
            "$pk" => $uid,
            "set_type" => $type,
            "descrip" => $descrip,
            "value1" => $value1,
            "value2" => $value2
        ));
    }
    
    /**
     * 检查用户是否存在
     * 
     * @param string $username 用户名
     * @param string $password 不为空时会检查密码是否正确。传递为加密后的
     * @todo 此外会返回uid，groupid和regdate
     */
    public function checkUserExist($username, $password = ""){
        return $this->run("account/user_exist", Array(
        	"username" => $username,
            "pwd" => $password
        ));
    }
    
    /**
     * 根据uid或者email获得用户名
     * 
     * @param string $value 值
     * @param string $type 取的类型(uid或email)
     * @param boolean $base64Encode 返回的用户名是否base64
     */
    public function convertUserName($value, $type, $base64Encode = false){
        return $this->run("account/convert", Array(
        	"value" => $value,
            "get_type" => $type,
            "encode" => $base64Encode ? 1 : 0
        ));
    }
    
    /**
     * 更新用户签名
     * 
     * @param string $token access token
     * @param string $honor 签名(不能大于60)
     */
    public function updateHonor($token, $honor){
        return $this->run("account/update_honor", Array(
        	"str" => $honor,
            "G.token" => $token
        ));
    }
    
    /**
     * 成就解锁
     * 
     * @param int $uid 用户uid
     * @param string $name 解锁的成就名
     * @todo 成就名是在成就系统里设定好的id，而不是直接传个名字过去
     */
    public function activeAchievement($uid, $name){
        return $this->run("account/active_achievement", Array(
        	"uid" => $uid,
            "name" => $name
        ));
    }
    
    /**
     * 获得用户论坛相关资料<br />
     * 其它应用资料需要通过专门的API，如萝莉有自己的API调用
     * 
     * @param string $token token
     * @param array $reqParam 额外请求的数据(见todo)
     * @return Ambigous <mixed, boolean>
     * @todo 默认返回username用户名 uid用户UID email地址 password密码<br /><br />
     * reqParam可额外请求 regdate注册时间 gender性别 oicqQQ号码 bday生日<br />
     * groupid论坛权限组别id  lover恋人  newpm新短信数目  honor签名
     * postnum发帖数  digests精华数  rvrc威望  money金钱  lastvisit上次访问  lastpost上次发帖
     * onlinetime在线时间  monoltime月在线  monthpost月发帖 onlineip在线ip
     * at_num新@的数目  re_num新回复/引用的数目
     */
    public function getUserInfo($token, $reqParam = array()){
        return $this->run("account/user_info", Array(
        	"G.token" => $token,
            "gets" => implode(",", $reqParam)
        ));
    }
    
    /**
     * 添加单条通知
     * 
     * @param mixed $uid 用户uid，也可以传送token到这个参数
     * @param string $type 类型（比如BOOK_UP之类的 用来识别到底是什么通知）
     * @param string $value 检索用参数，可以写id之类的，用来加强说明的体验。（多的放额外）
     * @param string $descrip 用来显示的说明
     * @param array $extra 额外
     * @param boolean $forceInsert 是否允许重复？ true时为强制插入，不进行检查
     * @todo 如果没有设置强制插入，更新数据时返回的do为update，反之为insert
     */
    public function addSignleNotify($uid, $type, $value, $descrip, $extra = array(), $forceInsert = false){
        $pk = "G.uid";
        if(!is_numeric($uid))   $pk = "G.token";
        
        return $this->run("notify/add", Array(
        	"$pk" => $uid,
            "G.type" => $type,
            "descrip" => $descrip,
            "value" => $value,
            "extra" => $extra,
            "force_insert" => $forceInsert ? 1 : 0
        ));
    }
    
    /**
     * 添加群体通知，对多个用户同时注册同一条通知<br />
     * 如用于书籍的更新等
     * 
     * @param array $uids 要添加通知的uid（和单条不同，不允许使用token传入）
     * @param string $type 类型（比如BOOK_UP之类的 用来识别到底是什么通知）
     * @param unknown $value 检索用参数，可以写id之类的，用来加强说明的体验。（多的放额外）
     * @param string $descrip 用来显示的说明
     * @param array $extra 额外
     * @param boolean $forceInsert 是否允许重复？ true时为强制插入，不进行检查
     * @return Ambigous <mixed, boolean>
     * @todo 返回的是个数组 有update和insert2个key，分别代表更新的和新插入的通知列表
     */
    public function addGroupNotify($uids, $type, $value, $descrip, $extra = array(), $forceInsert = false){
        return $this->run("notify/register", Array(
                "uids" => implode(",", $uids),
                "G.type" => $type,
                "descrip" => $descrip,
                "value" => $value,
                "extra" => $extra,
                "force_insert" => $forceInsert ? 1 : 0
        ));
    }
    
    /**
     * 获得通知列表
     * 
     * @param string $token accesstoken
     * @param number $time 显示这时间后的通知，为0时默认为前15天的通知显示
     * @param array $appids 应用id过滤
     * @param string $type 类型
     * @param boolean $ifnew 是否只显示未读
     * @return Ambigous <mixed, boolean>
     */
    public function getNotifyList($token, $time = 0, $appids = array(), $type = "", $ifnew = true){
        return $this->run("notify/list", Array(
        	"G.token" => $token,
            "f_time" => $time,
            "f_type" => $type,
            "f_appid" => implode(",", $appids),
            "ifnew" => $ifnew ? 1 : 0
        ));
    }
    
    /**
     * 设定通知为已读
     * 
     * @param string $token accesstoken
     * @param array $notifyId 通知id
     */
    public function setreadNotify($token, $notifyId){
        return $this->run("notify/setread", Array(
        	"G.token" => $token,
            "id" => implode(",", $notifyId)
        ));
    }
    
    /**
     * 创建订单
     * 
     * @param int $uid 用户uid
     * @param string $orderId 用户自己的订单id
     * @param string $subject 购买名称
     * @param string $type 类型(F论坛)
     * @param int $price 价格
     * @param int $exPrice 虚拟货币时用来计算传送的价值
     * @param string $recvName 购物者
     * @param string $recvAddr 地址
     * @return Ambigous <mixed, boolean>
     */
    public function createOrder($uid, $orderId, $subject, $type, $price, $exPrice, $recvName, $recvAddr = 'IN_APP'){
        return $this->run("pay/create", Array(
        	"from_order_id" => $orderId,
            "recv_name" => $recvName,
            "subject" => $subject,
            "recv_addr" => $recvAddr,
            "price" => $price,
            "ex_price" => $exPrice,
            "uid" => $uid,
            "type" => $type
        ));
    }
    
    /**
     * 查询订单信息
     * @param string $orderId 系统生成的订单id
     */
    public function getOrderInfo($orderId){
        return $this->run("pay/info", Array(
        	"order_id" => $orderId
        ));
    }
    
    /**
     * 发送论坛短信
     * 
     * @param string $token accesstoken
     * @param int $toUser 发给哪个uid(可以传递用户名)
     * @param string $title 标题
     * @param string $content 内容
     */
    public function sendMessage($token, $toUser, $title, $content){
        $pk = "to_uid";
        if(!is_numeric($toUser))    $pk = "to_user";
        
        return $this->run("message/create", array(
        	"title" => $title,
            "content" => $content,
            "$pk" => $toUser
        ));
    }
    
    /**
     * Key-Value操作
     * 
     * @param string $key 键名
     * @param string $value 值(false时为取值)
     * @param string $token access-token  -1时为这个应用下全局
     * @param int $lifetime 有效时间 -1为一直生效
     * @return Ambigous <mixed, boolean>
     */
    public function key2value($key, $value = false, $token = -1, $lifetime = -1){
        $reqArr = Array("key" => $key, "token" => $token, "lifetime" => $lifetime);
        
        if($value !== false){
            $reqArr['value'] = $value;
        }
        
        if($token == -1){
            $reqArr['sig'] = "create_".$key;
        }
        
        return $this->run("key2value", $reqArr);
    }
}