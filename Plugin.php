<?php
/**
 * 执念付费阅读
 * 
 * @package ZhinianPay
 * @author zhinianblog
 * @version 1.2.0
 * @link https://zhinianblog.com
 */
class ZhinianPay_Plugin implements Typecho_Plugin_Interface
{
    /**
     * 激活插件方法,如果激活失败,直接抛出异常
     * 
     * @access public
     * @return void
     * @throws Typecho_Plugin_Exception
     */
    public static function activate()
    {
        if (!Typecho_Db_Adapter_Mysql::isAvailable() && !Typecho_Db_Adapter_Pdo_Mysql::isAvailable()) {
            throw new Typecho_Plugin_Exception(_t('没有找到任何可用的 Mysql 适配器'));
        }
        
        // Typecho_Plugin::factory('admin/write-post.php')->bottom = array('ZhinianPay_Plugin', 'render');
        Typecho_Plugin::factory('Widget_Abstract_Contents')->contentEx = array('ZhinianPay_Plugin', 'ZhinianPayhide');
		Typecho_Plugin::factory('Widget_Abstract_Contents')->excerptEx = array('ZhinianPay_Plugin', 'ZhinianPayhide');
        Typecho_Plugin::factory('Widget_Archive')->header = array('ZhinianPay_Plugin', 'header');
        Typecho_Plugin::factory('Widget_Archive')->footer = array('ZhinianPay_Plugin', 'footer');
    }
    
    /**
     * 禁用插件方法,如果禁用失败,直接抛出异常
     * 
     * @static
     * @access public
     * @return void
     * @throws Typecho_Plugin_Exception
     */
    public static function deactivate()
    {
    }
    
    /**
     * 插件实现方法
     * 
     * @access public
     * @return void
     */
    public static function render($post)
    {
        $jsUrl = Helper::options()->pluginUrl . '/ZhinianPay/js/ZhinianPay.js';
        $loadJquery = Typecho_Widget::widget('Widget_Options')->plugin('ZhinianPay');
        $result = '<script type="text/javascript" src="'. $jsUrl .'"></script>';
        if($loadJquery == '01') {
            $result = $result.'<script src="//cdn.staticfile.org/jquery/3.5.1/jquery.min.js"></script>';
        }
        echo $result;
    }
    
    /**
     * 文章替换
     * 
     * @access public
     * @param string $content 文章正文
     * @param Widget_Abstract_Contents $opt 
     */
    public static function ZhinianPayhide($content, $post) {
        
        $options = Typecho_Widget::widget('Widget_Options');
		$option = $options->plugin('ZhinianPay');
        $cid = $post->cid;
        $str = $cid;
        $new = '';
        if ($str[strlen($str) - 1] != '0') {
            for ($i=0; $i<strlen($str); ++$i) {
                $new .= chr(ord('a') + intval($str[$i]) - 1);
            }
        }
        
        $db = Typecho_Db::get();
		$query= $db->select()->from('table.contents')->where('cid = ?', $cid ); 
		$row = $db->fetchRow($query);
		if($row['authorId'] != Typecho_Cookie::get('__typecho_uid')) {
		    $cookietime = $option->teepay_cookietime==""?1:$option->teepay_cookietime;
		    $cookieName =  'ZhinianPayCookie'.$new;
		    if(!isset($_COOKIE[$cookieName])) {
				$randomCode = md5(uniqid(microtime(true),true));
				setcookie($cookieName, $randomCode, time()+3600*24*$cookietime);
			}
			$bussId = $_COOKIE[$cookieName];
		}
		else {
		    $content = preg_replace('/{ZhinianPay[^}]*}/', '', $content);
            $content = preg_replace('/{\/ZhinianPay}/', '', $content);
            return $content;
		}
		
		$zhiniantempContent = $content;
        $zhiniantempContent = preg_replace('/{ZhinianPay[^}]*}/', 'ZhinianPayStart', $zhiniantempContent);
        $zhiniantempContent = preg_replace('/{\/ZhinianPay}/', 'ZhinianPayEnd', $zhiniantempContent);
        
        $start = 'ZhinianPayStart';
        $end = 'ZhinianPayEnd';
        
        $hideContent = substr($zhiniantempContent, strlen($start)+strpos($zhiniantempContent, $start),(strlen($zhiniantempContent) - strpos($zhiniantempContent, $end))*(-1));
		
		$start = 'money=';
        $end = '}';
        $input = $content;
        $money = substr($input, strlen($start)+strpos($input, $start), strpos($input, '}', strpos($input, 'money='))-(strlen($start)+strpos($input, $start)));
		
		$qqNum = $option->qqNum;
		$alipay = $option->alipay;
		$wxpay = $option->wxpay;
		$qqpay = $option->qqpay;
		$cardId = $option->cardId;
		
		$appId = $option->appId;
        $mchId = $option->mchId;
        $mchKey = $option->mchKey;
        
		
		$dangmianfuAppid = $option->ffyd_zhifubaodangmianfu_appid;
        $alipay_appid = $option->alipay_appid;
        $app_private_key = $option->app_private_key;
        $alipay_public_key = $option->alipay_public_key;
		
        $returnUrl = '//'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
        
        $yizhif_interfUrl = $option->ffyd_yizhifu_interfUrl;
        $yizhifu_pid = $option->ffyd_yizhifu_pid;
        $yizhifu_miyao = $option->ffyd_yizhifu_miyao;
        
        $mazhifu_interfUrl = $option->ffyd_mazhifu_interfUrl;
        $mazhifu_pid = $option->ffyd_mazhifu_pid;
        $mazhifu_miyao = $option->ffyd_mazhifu_miyao;
        
        
        $form = '<form style="display:none;" target="_blank" action="https://dy.zhinianboke.com/pay/zhifu/ZhiFu001/init" method="post" id="subscribe_form"><input type="hidden" name="qqNum" value="'.$qqNum.'"><input type="hidden" name="alipay" value="'.$alipay.'"><input type="hidden" name="wxpay" value="'.$wxpay.'"><input type="hidden" name="qqpay" value="'.$qqpay.'"><input type="hidden" name="appId" value="'.$appId.'"><input type="hidden" name="mchId" value="'.$mchId.'"><input type="hidden" name="mchKey" value="'.$mchKey.'"><input type="hidden" id="ZhinianPay_cardId" name="cardId" value="'.$cardId.'"><input type="hidden" id="ZhinianPay_cookietime" value="'.$cookietime.'"><input type="hidden" name="orderName" value="文章付费阅读"><input type="hidden" id="ZhinianPay_cookieName" value="'.$cookieName.'"><input type="hidden" id="ZhinianPay_bussId" name="bussId" value="'.$bussId.'"><input type="hidden" name="orderDes" value="文章付费阅读"><input type="hidden" name="dangmianfuAppid" value="'.$dangmianfuAppid.'"><input type="hidden" name="alipayAppid" value="'.$alipay_appid.'"><input type="hidden" name="alipayAppPrivateKey" value="'.$app_private_key.'"><input type="hidden" name="alipayPublicKey" value="'.$alipay_public_key.'"><input type="hidden" id="ZhinianPay_orderFee" name="orderFee" value="'.$money.'"><input type="hidden" name="returnUrl" value="'.$returnUrl.'"><input type="hidden" name="interfUrl" value="'.$yizhif_interfUrl.'"><input type="hidden" name="pid" value="'.$yizhifu_pid.'"><input type="hidden" name="miyao" value="'.$yizhifu_miyao.'"><input type="hidden" name="mazhifuInterfUrl" value="'.$mazhifu_interfUrl.'"><input type="hidden" name="mazhifuPid" value="'.$mazhifu_pid.'"><input type="hidden" name="mazhifuMiyao" value="'.$mazhifu_miyao.'"><input type="submit" value="" id="submit"></form>';
        
        $replaceEnd = '<div class="zhinianpay_content" style="display: none;">'.$hideContent.'</div>';
        $replaceEnd = $replaceEnd . '<span id="zhinian_hide">此处内容作者设置了 <i id="zhinian_hide__button">付费'.$money . ' 元(点击此处支付，付费后请刷新界面) </i>可见，付费后 '. $cookietime . ' 天内有效</span>'.$form;
        $content = preg_replace('/{ZhinianPay[^}]*}([\s\S]*?){\/ZhinianPay}/', $replaceEnd, $content);
		return $content;
    }
    
    
    /**
     * 在主题中直接调用
     *
     * @access public
     * @return int
     * @throws
     */
    public static function getTeePay(){
    }
    
    /**
     * 输出头部css
     * 
     * @access public
     * @param unknown $header
     * @return unknown
     */
    public static function header() {
        $cssUrl = Helper::options()->pluginUrl . '/ZhinianPay/css/ZhinianPay.css';
        echo '<link rel="stylesheet" type="text/css" href="' . $cssUrl . '" />';
    }
    
    /**
     * 输出底部js
     * 
     * @access public
     * @param unknown $header
     * @return unknown
     */
    public static function footer() {
        $jsUrl = Helper::options()->pluginUrl . '/ZhinianPay/js/ZhinianPay.js';
        $loadJquery = Typecho_Widget::widget('Widget_Options')->plugin('ZhinianPay');
        $result = '<script type="text/javascript" src="'. $jsUrl .'"></script>';
        if($loadJquery->loadJquery == '01') {
            echo '<script src="//cdn.staticfile.org/jquery/3.5.1/jquery.min.js"></script>';
        }
        echo $result;
        
    }
    
    /**
     * 获取插件配置面板
     * 
     * @access public
     * @param Typecho_Widget_Helper_Form $form 配置面板
     * @return void
     */
    public static function config(Typecho_Widget_Helper_Form $form)
    {
        
        $div=new Typecho_Widget_Helper_Layout();
		$div->html('<small>		
			<h6>基础功能</h6>
			<span><p>第一步：配置下方各项参数；</p></span>
			<span><p>PS：如果想实时到账，可以配置支付宝appid，应用私钥，支付宝公钥即可</p></span>
		</small>');
		$div->render();
		$loadJquery = new Typecho_Widget_Helper_Form_Element_Select(
            'loadJquery',
            array(
                '01' => '是',
                '' => '否',
            ),
            '',
            '是否加载Jquery',
            '默认一般是否，如果插件开启后，付费功能无法使用，可选择选择是之后进行尝试'
        );
        $alipay = new Typecho_Widget_Helper_Form_Element_Select(
            'alipay',
            array(
                '01' => '是',
                '' => '否',
            ),
            '',
            '请选择是否开启支付宝支付',
            ''
        );
        $wxpay = new Typecho_Widget_Helper_Form_Element_Select(
            'wxpay',
            array(
                '01' => '是',
                '' => '否',
            ),
            '',
            '请选择是否开启微信支付',
            ''
        );
        
        $qqpay = new Typecho_Widget_Helper_Form_Element_Select(
            'qqpay',
            array(
                '01' => '是',
                '' => '否',
            ),
            '',
            '请选择是否开启QQ支付',
            ''
        );
        $form->addInput($loadJquery);
        $form->addInput($alipay);
        $form->addInput($wxpay);
        $form->addInput($qqpay);
        
        $host = new Typecho_Widget_Helper_Form_Element_Text('cardId', NULL, 'zhinianblog',
        _t('授权码'), _t('请填写在 https://dy.zhinianboke.com 申请的授权码'));
        $form->addInput($host->addRule('required', _t('必须填写授权码')));
        
        $qqNum = new Typecho_Widget_Helper_Form_Element_Text('qqNum', NULL, '',
        _t('qq号码'), _t('请填写在qq号码，支付界面跳转到qq使用，供客户点击qq咨询'));
        $form->addInput($qqNum);
        
        
        //配置信息
		$teepay_cookietime = new Typecho_Widget_Helper_Form_Element_Text('teepay_cookietime', array('value'), 1, _t('免登录Cookie保存时间(天)'), _t('付费后多长时间内可以重复阅读，默认为1天。'));
        $form->addInput($teepay_cookietime);
        
        // alipay配置
		$ffyd_zhifubaodangmianfu_appid = new Typecho_Widget_Helper_Form_Element_Text('ffyd_zhifubaodangmianfu_appid', array('value'), "", _t('支付宝当面付appid'), _t('支付宝当面付的appid号。'));
        $form->addInput($ffyd_zhifubaodangmianfu_appid);
		
		$alipay_appid = new Typecho_Widget_Helper_Form_Element_Text('alipay_appid', array('value'), "", _t('支付宝应用appid'), _t('支付宝应用的appid号。'));
        $form->addInput($alipay_appid);
        
		$app_private_key = new Typecho_Widget_Helper_Form_Element_Textarea('app_private_key', array('value'), "", _t('支付宝应用私钥'), _t('应用私钥，不是支付宝私钥。'));
        $form->addInput($app_private_key);
        
		$alipay_public_key = new Typecho_Widget_Helper_Form_Element_Textarea('alipay_public_key', array('value'), "", _t('支付宝公钥'), _t('在支付宝生成的公钥。'));
        $form->addInput($alipay_public_key);
        
        // 微信支付配置
		$appId = new Typecho_Widget_Helper_Form_Element_Text('appId', array('value'), "", _t('微信公众号appid'), _t('微信公众号appid。'));
        $form->addInput($appId);
        
		$mchId = new Typecho_Widget_Helper_Form_Element_Textarea('mchId', array('value'), "", _t('微信商户号'), _t('微信商户号mchId。'));
        $form->addInput($mchId);
        
		$mchKey = new Typecho_Widget_Helper_Form_Element_Textarea('mchKey', array('value'), "", _t('微信商户密钥'), _t('微信商户密钥mchKey。'));
        $form->addInput($mchKey);
        
        // 易支付配置
        $ffyd_yizhifu_interfUrl = new Typecho_Widget_Helper_Form_Element_Text('ffyd_yizhifu_interfUrl', array('value'), "", _t('易支付API接口支付地址'), _t('介绍：填写对应易支付网站中的API接口支付地址,注意后面有 mapi.php <br>
		例如：https://suyan.qqdsw8.cn/mapi.php'));
        $form->addInput($ffyd_yizhifu_interfUrl);
        
        $ffyd_yizhifu_pid = new Typecho_Widget_Helper_Form_Element_Text(
    		'ffyd_yizhifu_pid',
    		array('value'),
    		"",
    		_t('易支付商户ID'),
    		_t('介绍：申请地址如下 <a href="https://suyan.qqdsw8.cn/user/reg.php" target="_BLANK">https://suyan.qqdsw8.cn/user/reg.php</a> <br/>
		<span style="color:red;">本站不对该地址资金结算做保证，只是提供一个渠道</span>')
    	);
    	$form->addInput($ffyd_yizhifu_pid);
    	
    	$ffyd_yizhifu_miyao = new Typecho_Widget_Helper_Form_Element_Text(
    		'ffyd_yizhifu_miyao',
    		NULL,
    		NULL,
    		_t('易支付商户密钥'),
    		_t('介绍：申请的商户秘钥 <br/>')
    	);
    	$form->addInput($ffyd_yizhifu_miyao);
    	
    	// 码支付
    	$ffyd_mazhifu_interfUrl = new Typecho_Widget_Helper_Form_Element_Text(
    		'ffyd_mazhifu_interfUrl',
    		NULL,
    		NULL,
    		'码支付API接口支付地址',
    		'介绍：填写对应码支付网站中的支付请求支付地址,注意后面可能有 submit.php <br>
    		例如：https://pay.ococn.cn/submit.php'
    	);
    	$form->addInput($ffyd_mazhifu_interfUrl);
    	
    	$ffyd_mazhifu_pid = new Typecho_Widget_Helper_Form_Element_Text(
    		'ffyd_mazhifu_pid',
    		NULL,
    		NULL,
    		'码支付商户ID',
    		'介绍：申请地址如下 <a href="https://pay.ococn.cn/User/Login.php?invite_user=199451637" target="_BLANK">https://pay.ococn.cn/User/Login.php?invite_user=199451637</a> <br/>
    		<span style="color:red;">本站不对该地址资金结算做保证，只是提供一个渠道</span>'
    	);
    	$form->addInput($ffyd_mazhifu_pid);
    	
    	$ffyd_mazhifu_miyao = new Typecho_Widget_Helper_Form_Element_Text(
    		'ffyd_mazhifu_miyao',
    		NULL,
    		NULL,
    		'码支付商户密钥',
    		'介绍：申请的商户秘钥 <br/>'
    	);
    	$form->addInput($ffyd_mazhifu_miyao);
    }
    
    /**
     * 个人用户的配置面板
     * 
     * @access public
     * @param Typecho_Widget_Helper_Form $form
     * @return void
     */
    public static function personalConfig(Typecho_Widget_Helper_Form $form){}
}
