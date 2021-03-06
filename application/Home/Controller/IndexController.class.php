<?php
namespace Home\Controller;
use Common\Controller\HomeBaseController;
class IndexController extends HomeBaseController {
    private $db_Articles,$db_Article_category,$db_Tag,$db_Article_tag;
    public function __construct(){
        parent::__construct();
        $this->db_Tag = D ('Tag');
        $this->db_Articles = D ('Articles');
        $this->db_Article_tag = D ('Article_tag');
        $this->db_Article_category = D ('Article_category');

    }
    public function index(){
        //默认读取全部
        if(!isset($_GET['tagid']) &&  !isset($_GET['caid']) ){
            $show_array['a_state'] = array('eq',C('ARTICLE_NORMAL'));
            $count = $this->db_Articles->where($show_array)->count();
            $Page       =  new \Think\Page($count,5);
            $show       =  $Page->show();// 分页显示输出
            $this->assign('page',$show);// 赋值分页输出
            $article_list = $this->db_Articles->where($show_array)->order(array('a_id'=>'desc'))
                ->limit($Page->firstRow.','.$Page->listRows)
                ->select();
        }
        //标签目录
        $tag_id = I('get.tagid');
        if(!empty($tag_id) && isset($tag_id) ){
            $tag_info = $this->db_Tag->getByHt_id($tag_id);
            if(empty($tag_info)){
                $this->error("无法获取原标签",U('Home/Index/index'),'',1);
            }
            $where['at.at_tag_id'] = $tag_id;
            $where['a.a_state'] = array('eq',C('ARTICLE_NORMAL'));
            $count = $this->db_Article_tag
                ->alias('at')
                ->join('__ARTICLES__ a ON at.at_article_id = a.a_id')
                ->where($where)
                ->count();
            $Page       =  new \Think\Page($count,5);
            $show       =  $Page->show();// 分页显示输出
            $this->assign('page',$show);// 赋值分页输出
            $article_list = $this->db_Article_tag
                ->alias('at')
                ->join('__ARTICLES__ a ON at.at_article_id = a.a_id')
                ->where($where)
                ->order('a.a_add_time desc')
                ->limit($Page->firstRow.','.$Page->listRows)
                ->select();
            $this->assign("tag_info",$tag_info);
        }
        //分类目录
        $ac_id = I('get.caid');
        if(!empty($ac_id) && isset($ac_id) ){
            $category_info = $this->db_Article_category->getByAc_id($ac_id);
            if(empty($category_info)){
                $this->error("无法获取原分类",U('Home/Index/index'),'',1);
            }
            $where['ac.ac_id'] = $ac_id;
            $where['a.a_state'] = array('eq',C('ARTICLE_NORMAL'));
            $count = $this->db_Article_category
                ->alias('ac')
                ->join('__ARTICLES__ a ON ac.ac_id = a.a_category_id')
                ->where($where)
                ->count();
            $Page       =  new \Think\Page($count,5);
            $show       =  $Page->show();// 分页显示输出
            $this->assign('page',$show);// 赋值分页输出
            $article_list = $this->db_Article_category
                ->alias('ac')
                ->join('__ARTICLES__ a ON ac.ac_id = a.a_category_id')
                ->where($where)
                ->order('a.a_add_time desc')
                ->limit($Page->firstRow.','.$Page->listRows)
                ->select();
            $this->assign("category_info",$category_info);
        }

        if(!empty($article_list) && is_array($article_list)){
            foreach ($article_list as $k => $v){
                //分类
                $ac_array['ac_id'] = array('eq',$v['a_category_id']);
                $category_info = $this->db_Article_category->where($ac_array)->find();
                $article_list[$k]['category_info'] =  $category_info;
                //标签
                $tag_array['at_article_id'] = $v['a_id'];
                $tag_id_list = $this->db_Article_tag->where($tag_array)->select();
                $tag_id_str = '';
                foreach ($tag_id_list as $key => $val){
                    $tag_id_str .= $val['at_tag_id'].',';
                }
                $tag_id_str =  rtrim($tag_id_str, ',');

                $tag_list_array['ht_id'] = array('in',$tag_id_str);
                $tag_list = $this->db_Tag->where($tag_list_array)->select();
                $article_list[$k]['tag_info'] =  $tag_list;

                //暂时都随机产生图片1-10张图片/randimg文件夹下
                $list_img_src = rand(1,10) . ".png";
                $article_list[$k]['list_img_src'] =  $list_img_src;

            }
        }

        $this->assign("article_list",$article_list);

        $this->display();
    }
    public function article(){
        $tag_id_str = '';
        $a_id = I('get.id');
        if(empty($a_id)){
            $this->error("无法获取原文章",U('Home/Index/index'),'',1);
        }
        $article_info = $this->db_Articles->getByA_id($a_id);
        if(empty($article_info)){
            $this->error("无法获取原文章",U('Home/Index/index'),'',1);
        }
        //标签
        $tag_id_list = $this->db_Article_tag->where(array('at_article_id'=>$a_id))->select();
        if(!empty($tag_id_list) && is_array($tag_id_list)){
            $tag_id_str = '';
            foreach ($tag_id_list as $key => $val){
                $tag_id_str .= $val['at_tag_id'].',';
            }
            $tag_id_str =  rtrim($tag_id_str, ',');
        }
        $tag_list_array['ht_id'] = array('in',$tag_id_str);
        $tag_list = $this->db_Tag->where($tag_list_array)->select();
        //分类
        $category_list = $this->db_Article_category->where(array('ac_id'=>$article_info['a_category_id']))->select();
        $article_info['tag_list'] = $tag_list;
        $article_info['category_list'] = $category_list;

        //上一篇
        $prev_array['a_id'] = array('lt',$a_id);
        $prev_a=$this->db_Articles->where($prev_array)->order('a_id desc')->limit('1')->find();
        $this->assign('prev_a',$prev_a);
        //下一篇
        $next_array['a_id'] = array('gt',$a_id);
        $next_a=$this->db_Articles->where($next_array)->order('a_id asc')->limit('1')->find();
        $this->assign('next_a',$next_a);

        $this->assign("article_info",$article_info);
        //系统随机推荐文章，现行规则，根据数据库id随机读取，后面在做修改
        /*no1 rand()*`a_id`**//*no2 思路: php读取出所有id数组，php数组随机产生一组id字符串，mysql用in去查*/
        $rand_list = $this->db_Articles->order('rand()*`a_id`')->limit('6')->select();
        if(!empty($rand_list) && is_array($rand_list)){
            foreach ($rand_list as $k => $v) {
                //分类
                $ac_array['ac_id'] = array('eq', $v['a_category_id']);
                $category_info = $this->db_Article_category->where($ac_array)->find();
                $rand_list[$k]['category_info'] = $category_info;
            }
        }

        $this->assign("rand_list",$rand_list);

        $this->display();
    }
    
    public function sendMsg(){
        /*$appkey = "23476581";*/
        $secret = "42cbc25c574852e80b017f802e4338cf";
        import('Org.Alidayu.TopClient');
        import('Org.Alidayu.ResultSet');
        import('Org.Alidayu.RequestCheckUtil');
        import('Org.Alidayu.TopLogger');
        import('Org.Alidayu.AlibabaAliqinFcSmsNumSendRequest');
        //将需要的类引入，并且将文件名改为原文件名.class.php的形式
        $c = new \TopClient;
        $c->appkey = $appkey;
        $c->secretKey = $secret;
        $req = new \AlibabaAliqinFcSmsNumSendRequest;
        $req->setExtend("1");//确定发给的是哪个用户，参数为用户id
        $req->setSmsType("normal");
        /*
           进入阿里大鱼的管理中心找到短信签名管理，输入已存在签名的名称，这里是身份验证。
        */
        $req->setSmsFreeSignName("HSBLOG博客");
        $req->setSmsParam("{\"verify\":\"999999\"}");
        //这里设定的是发送的短信内容：验证码${code}，您正在进行${product}身份验证，打死不要告诉别人哦！”
        $req->setRecNum("18853211461");//参数为用户的手机号码
        $req->setSmsTemplateCode("SMS_17160001");
        $resp = $c->execute($req);
        var_dump($resp);


    }
    
}