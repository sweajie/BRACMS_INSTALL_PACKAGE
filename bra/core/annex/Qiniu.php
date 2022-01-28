<?php
// +----------------------------------------------------------------------
// | 鸣鹤CMS [ New Better  ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2017 http://www.bracms.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( 您必须获取授权才能进行使用 )
// +----------------------------------------------------------------------
// | Author: new better <1620298436@qq.com>
// +----------------------------------------------------------------------
namespace Bra\core\annex;

use Bra\core\objects\BraAnnex;
use Bra\core\objects\BraCurl;
use Bra\core\objects\BraString;
use Qiniu\Auth;
use Qiniu\Config;
use Qiniu\Processing\PersistentFop;
use Qiniu\Rtc\AppClient;
use Qiniu\Storage\BucketManager;
use Qiniu\Storage\UploadManager;
/**
 * @property UploadManager uploadmgr
 */
class Qiniu extends AnnexEngine
{

    public $provider_id = 4;
    public $uploadmgr, $token, $bucket;
    public $get_token_api = '';

    public function __construct($config)
    {
        global $_W;
        $this->config = $config;
        $accessKey = $this->config['accesskey'];
        $secretKey = $this->config['secretkey'];
        $this->bucket = $this->config['bucket'];
        // 初始化签权对象
        if (!$_W['user']) {
            abort(403 ,'对不起 , 此功能需要登录');
        }
        $this->auth = new Auth($accessKey, $secretKey);
        $this->get_token_api = make_url('bra/annex/get_token');
    }

    public function get_token()
    {
        $this->token = $this->auth->uploadToken($this->config['bucket']);
        return $this->token;
    }

    public function get_manger()
    {
        $config = new Config();
        $this->uploadmgr = new UploadManager($config);
        return $this->uploadmgr;
    }

    public function put_file($file_name, $file_path)
    {
        if (!$this->token) {
            $this->get_token();
        }
        if (empty($this->uploadmgr)) {
            $this->get_manger();
        }

        list($ret, $err) = $this->uploadmgr->putFile($this->token, $file_name, $file_path);

        if ($err !== null) {
            $err = (array)$err;
            $err = (array)array_pop($err);
            $err = json_decode($err['body'], true);
            return $err;
        } else {
            return true;
        }
    }

    public function upload($file, $local_path = "")
    {
        //todo gen file name
        $file_name = $file['filename'];
        if (!$local_path) {
            $file_path = PUBLIC_ROOT . $file['url'];
        } else {
            $file_path = $local_path;
        }

        if (!file_exists($file_path)) {
            return bra_res(403, '本地文件不存在!' . $file_path);
        }

        if (!$this->token) {
            $this->get_token();
        }
        if (empty($this->uploadmgr)) {
            $this->get_manger();
        }

        list($ret, $err) = $this->uploadmgr->putFile($this->token, $file['url'], $file_path);

        $update = [];
        $update['provider_id'] = $this->provider_id;

        if ($err !== null) {
            $err = (array)$err;
            $err = (array)array_pop($err);
            $err = json_decode($err['body'], true);
            $err['ret'] = $ret;
            if ($err['error'] == "file exists") {
                D('annex')->bra_where($file['id'])->update($update);
                return bra_res(1, 'file exist');
            } else {
                return $err;
            }
        } else {
            D('annex')->bra_where($file['id'])->update($update);
            return bra_res(1, 'qiniu file uploaded!' , '' , $update);
        }
    }

    public function test()
    {
        if (!$this->token) {
            $this->get_token();
        }
        if (empty($this->uploadmgr)) {
            $this->get_manger();
        }
        $test_file = PUBLIC_ROOT . 'statics/images/logo.png';
        if (!is_file($test_file)) {
            $ret['code'] = 2;
            return bra_res(2, "测试文件不存在，请查看配置值");
        }
        $auth = $this->put_file(trim("bra_logo.jpg"), $test_file);

        if (!$auth) {
            return bra_res(2, "配置测试失败，请查看配置值" , $auth , $test_file);
        }

        $url = $this->config['url'] . '/bra_logo.jpg';

        $curl = new BraCurl();
        $response = $curl->test_url($url);
        if ($response->getStatusCode() != 200) {
            return bra_res(-1, '配置失败，七牛访问url错误');
        }

        $image = getimagesizefromstring($response->getBody());
        if (!empty($image) && BraString::str_found($image['mime'], 'image')) {
            return bra_res(1, '配置成功');
        } else {
            return bra_res(2, '配置失败，七牛访问url错误!');
        }
    }

    public function form($field)
    {
        global $_W;
        $region = $this->config['region'] ? $this->config['region'] : 'z0';
        $default_value = $field->default_value;
        $token = $this->token;
        if (!$token) {
            $token = $this->get_token();
        }
        if (is_array($default_value)) {
            $file_ids = $default_value;
        } else {
            $file_ids = array_filter(explode(",", $default_value));
        }
        $form_name = $field->form_name;
        $form_str = '';
        foreach ($file_ids as $file_id) {
            $annex = new BraAnnex($file_id);
            if ($annex->annex) {
                $file = $annex->annex['old_data'];
                $src = $annex->get_url();

                if ($file['file_type'] == 1) {
                    $form_str .= "<li class='layui-upload-img weui-uploader__file'>
<img src='{$src}' bra-mini='view_image' alt='{$file['filename']}' class='layui-upload-img'>
<input type='hidden' value='{$file_id}' name='$form_name'>
<i class='icon close' onclick='remove_parent(this , \".layui-upload-img\")'>x</i>
</li>";
                } else {
                    $form_str .= "<li class='layui-upload-img weui-uploader__file'>
附件
<input type='hidden' value='{$file_id}' name='$form_name'>
<i class='icon close' onclick='remove_parent(this , \".layui-upload-img\")'>x</i>
</li>";
                }
            }
        }
        //生产七牛表单
        $form_str .= <<<EOF
        <div class="weui-cell is-paddingless">
                <div class="weui-cell__bd">
                    <div class="weui-uploader">
                        <div class="weui-uploader__bd">
                            <ul class="weui-uploader__files"   id="$field->field_name">

                            </ul>
                            <div class="weui-uploader__input-box" id="{$field->field_name}-b_btn">
                                <div id="{$field->field_name}_btn" class="uploader" style="width: 100%;height: 100%;"><i class="layui-icon  layui-icon-picture"></i></div>
                            </div>
                            <div class="weui-uploader__input-box"id="{$field->field_name}-v_btn" >

                                <div id="{$field->field_name}_btn_v" class="uploader" style="width: 100%;height: 100%;"><i class="layui-icon layui-icon-video"></i></div>
                            </div>

                            <a id="{$field->field_name}_btn_ss" style="display: none" class="button  is-success">开始上传</a>
                        </div>
                    </div>
                </div>
            </div>

EOF;
        $_W->bra_scripts[] = <<<EOF
        <style>
        .icon.close{
			left: 0;
			line-height: 1em;
			position: absolute;
			background: aliceblue;
			height: 10px;
			width: 10px;
        }
        #totalBar{
            float: left;
            width: 100%;
            height: 30px;
            position: relative;
            border: 1px solid;
        }
        #totalBar #totalBarColor{
            width: 100%;
            border: 0px;
            background-color:rgba(232,152,39,0.8);
            height: 28px;
        }
        #totalBar .speed{
            width: 100%;
            color: #bebdba;
            text-align: center;
            line-height: 30px;
        }
        .weui-uploader__file .layui-icon{
            margin: auto;
            display: block;
            line-height: 77px;
            font-size: 45px;
            background: #f3f3f3;
            text-align: center;
        }
        .weui-uploader__file{
            background: #fafafa;
            position: relative;
        }
        .weui-uploader__file img{
            width: 100%;
            height: 100%;
            object-fit: cover;
            position: absolute;
            left: 0;
            top: 0;
        }

        .weui-uploader__file .delete{
        position: absolute;
        right: 0;
        }
</style>
<script>
        function remove_parent(obj , selector) {
            $(obj).parents(selector).remove();
        }
        var API_QINIU_TOKEN = "$this->get_token_api";
        require(['jquery' , 'plupload' , 'bra_upload', 'qiniu'] , function($ , plupload , bra_upload , qiniu) {
            $(function() {
                    var token = "$token";
                    var config = {
                      useCdnDomain: true,
                      disableStatisticsReport: false,
                      retryCount: 6,
                      region: qiniu.region.$region ,
                      container : "{$field->field_name}" ,
                      browse_button :['{$field->field_name}_btn','{$field->field_name}_btn_v']  ,
                      user_id : "{$_W['user']['id']}" ,
                      real_form_name : "{$field->form_name}" ,
                      default_value : "$default_value",
                      force_cam : "{$field->force_cam}"
                    };
                    var putExtra = {
                      fname: "",
                      params: {
                      },
                      mimeType: null
                    };
                    console.log(config.default_value);
                    bra_upload.init_bra_upload( token, putExtra, config);
            });
        });
</script>

EOF;

        return $form_str;
    }

    public function get_prefix()
    {
        return $this->config['url'];
    }

    public function convert_amr($filePath, $mediaid)
    {
        global $_GPC;
        //数据处理队列名称,不设置代表不使用私有队列，使用公有队列。
        $pipeline = trim($_GPC['pipeline']);
        //通过添加'|saveas'参数，指定处理后的文件保存的bucket和key
        //不指定默认保存在当前空间，bucket为目标空间，后一个参数为转码之后文件名
        $savekey = \Qiniu\base64_urlSafeEncode($this->bucket . ':' . $mediaid . '.mp3');
        //设置转码参数
        $fops = "avthumb/mp3/ab/320k/ar/44100/acodec/libmp3lame";
        $fops = $fops . '|saveas/' . $savekey;
        if (!empty($pipeline)) {  //使用私有队列
            $policy = array(
                'persistentOps' => $fops,
                'persistentPipeline' => $pipeline
            );
        } else {                  //使用公有队列
            $policy = array(
                'persistentOps' => $fops
            );
        }

        //指定上传转码命令
        $uptoken = $this->auth->uploadToken($this->bucket, null, 3600, $policy);
        $key = $mediaid . '.amr'; //七牛云中保存的amr文件名
        $uploadMgr = new UploadManager();

        //上传文件并转码$filePath为本地文件路径
        list($ret, $err) = $uploadMgr->putFile($uptoken, $key, $filePath);
        if ($err !== null) {
            return false;
        } else {
            //此时七牛云中同一段音频文件有amr和MP3两个格式的两个文件同时存在
            $bucketMgr = new BucketManager($this->auth);
            //为节省空间,删除amr格式文件
            $bucketMgr->delete($this->bucket, $key);
            return $this->get_prefix() . '/' . str_replace(".amr", '.mp3', $ret['key']);
        }
    }

    public function convert_video($file_id)
    {
        $file = D('file')->find(['id' => $file_id]);
        $prefix = substr($file['url'], 0, strrpos($file['url'], '.'));
        if ($file['convert'] == 2) {
            $pre = new PersistentFop($this->auth, $this->config);
            $ret = $pre->status($file['convert_pid']);
            $res = $ret[0];
            if ($res['code'] == 0) {
                //update playurl
                $update['play_url'] = $res['items'][0]['key'];
                $update['convert'] = 1;
                D('file')->bra_where($file['id'])->update($update);
                $ret['code'] = 1;
                $ret['msg'] = "转码已经完成!";
            } elseif ($res['code'] == 2) {
                $ret['code'] = 1;
                $ret['msg'] = "转码正在进行中!!";
            } else {
                $update['error'] = $file['error'] + 1;
                $update['convert'] = 0;
                $update['play_url'] = '';
                $update['convert_pid'] = '';
                $ret['code'] = $res['code'];
                $ret['msg'] = $res['desc'];
                D('file')->bra_where($file_id)->update($update);
            }
            return $ret;
        }

        if ($file['convert'] == 1) {
            $ret['code'] = 1;
            $ret['msg'] = '转码已经完成!!';
            return $ret;
        }

        if ($file['convert'] == 0) {
            $fops = 'avthumb/mp4/s/640x360/vb/1.25m/autoscale/1';
            $saveas_key = $this->urlsafe_b64encode("{$this->bucket}:$prefix" . '640x360' . '.mp4');
            $fops .= '|saveas/' . $saveas_key;
            $pre = new PersistentFop($this->auth, $this->config);
            $res = $pre->execute($this->bucket, $file['url'], $fops);
            if ($res[0]) {
                $update['convert_pid'] = $res[0];
                $update['convert'] = 2;
                D('file')->bra_where($file_id)->update($update);

                $ret['code'] = 1;
                $ret['msg'] = '操作成功!';
                return $ret;
            } else {
                //inc error count
                $update['error'] = $file['error'] + 1;
                D('annex')->bra_where($file_id)->update($update);//->update($update, ['id' => $file_id]);
                $ret['code'] = 2;
                $ret['msg'] = $file['file_id'] . '对不起,转码出错了!!';
                return $ret;
            }
        }
    }


}
