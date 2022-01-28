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
namespace Bra\bra\pages;

use Bra\core\objects\BraAnnex;
use Bra\core\objects\BraFS;
use Bra\core\objects\BraModel;
use Bra\core\objects\BraPage;
use Bra\core\pages\BraController;
use Exception;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Storage;

class AnnexPage extends BraController {
    public $file_types = [
        'image' => 1,
        'video' => 2,
        'audio' => 3,
        'application' => 4,
        'other' => 5
    ];
    /**
     * @var $annex_m BraModel
     */
    private $annex_m;
    private $upload_tmp_path;
    private $save_path;
    private $ue_config;

    public function __construct (BraPage $bra_page) {
        global $_W;
        parent::__construct($bra_page);
        $this->annex_m = D('annex');
        $this->upload_tmp_path = storage_path(). DS . "upload_tmp" . DS;
        if (empty($_W['user'])) {
            end_resp(bra_res(200, '对不起,您无上传权限!'));
        }
        $this->save_path = 'annex' . DS . $_W['user']['id'];
    }

    public function bra_annex_chunk_upload() {

//check if request is GET and the requested chunk exists or not. this makes testChunks work
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            $temp_dir = $this->upload_tmp_path . $_GET['flowIdentifier'];
            $chunk_file = $temp_dir . '/' . $_GET['flowFilename'] . '.part' . $_GET['flowChunkNumber'];

            if (file_exists($chunk_file) && filesize($chunk_file) == $_GET['flowChunkSize']) {
                return header("HTTP/1.0 200 Ok");
            } else {
                return end_resp(bra_res(404 , '') , 'json' ,403);
            }
        }
// loop through files and move the chunks to a temporarily created directory
        try {
            if (!empty($_FILES)) foreach ($_FILES as $file) {
                if ($file['error'] != 0) {
                    continue;
                }

                // init the destination file (format <filename.ext>.part<#chunk>
                // the file is stored in a temporary directory
                $temp_dir = $this->upload_tmp_path . $_POST['flowIdentifier'];
                $dest_file = $temp_dir . '/' . $_POST['flowFilename'] . '.part' . $_POST['flowChunkNumber'];
                // create the temporary directory
                if (!is_dir($temp_dir)) {
                    mkdir($temp_dir, 0777, true);
                }
                // move the temporary file
                if (!move_uploaded_file($file['tmp_name'], $dest_file)) {
					return $this->page_data =  bra_res(500 , 'move_uploaded_file') ;
                } else {
                    // check if all the parts present, and create the final destination file
                    $res = $this->createFileFromChunks($temp_dir, $_POST['flowFilename'], $_POST['flowChunkSize'], $_POST['flowTotalSize']);
                    if(is_error($res)){
                        return $this->page_data =  $res ;
                    }else{
                        return $this->page_data = $this->process_annex_file($res['data'] ,  $_POST['flowFilename'] );
                    }
                }
            }
        } catch (Exception $e) {
            return $this->page_data = bra_res(500, $e->getMessage());
        }
    }


	private function process_annex_file ($path , $org_name , $is_private = false) {
		global $_W , $_GPC;
		$file = new UploadedFile($path , $org_name);
		$inst = $this->annex_m->bra_one(['md5' =>sha1_file($path) ]);
		if (!$inst) {
			$inst = [];
			$inst['user_id'] = $_W['user']['id'];
			$inst['filemime'] = $file->getMimeType();
			$filemime = explode('/', $inst['filemime']);
			$inst['file_type'] = $this->file_types[$filemime[0]] ?? '5';
			$inst['filesize'] = $file->getSize();
			$inst['create_at'] = date('Y-m-d H:i:s');
			$inst['site_id'] = $_W['site']['id'];
			// Get a file name
			if (isset($_REQUEST["filename"])) {
				$fileName = $_REQUEST["filename"];
			} elseif (!empty($_FILES)) {
				$fileName = $_FILES["file"]["name"];
			} else {
				$fileName = uniqid("file_");
			}
			$inst['filename'] = $fileName;
			$extend_name = "." . BraFS::file_ext($fileName);
			if (strpos(strtolower($extend_name), 'php') !== false) {
				return bra_res(500, 'system_error');
			}

			$save_path = $file->store($this->save_path);
			if (isset($_GPC['is_private']) && $_GPC['is_private']) {
			} else {
			}
			$inst['md5'] = sha1_file(PUBLIC_ROOT . $save_path);
			$shrink_res = BraAnnex::shrink_img(PUBLIC_ROOT . $save_path);
			// save to annex
			$inst['url'] = $save_path;
			$inst['provider_id'] = 1;
			$inst['id'] = $this->annex_m->insertGetId($inst);
		}
		//upload $this->save_path to cloud storage
		$storge = new BraAnnex($inst['id']);
		$res = $storge->upload();
		if (isset($shrink_res)) {
			$inst['shrink_res'] = $shrink_res;
		}
		if (!is_error($res)) {
			$inst['code'] = 1;
			$inst['res'] = $res;
			$inst['msg'] = '上传成功!';

			return $inst;
		} else {
			$inst['code'] = 500;
			$inst['res'] = $res;
			$inst['msg'] = $res['msg'];

			return $inst;
		}
	}

    public function bra_annex_upload () {
        global $_W, $_GPC;
        $files = request()->file();
        if(!$files){
            request()->files->add($_W['_bra_uploaded_files']);
//            $files =  $_W['_bra_uploaded_files'];error: 0
//name: "0ed00a385343fbf2b3f31298b07eca8064388f3e.jpg"
//size: 475646
//tmp_name: "/tmp/sfyJ4tTDd"
//type: "image/jpeg"
            foreach($_W['_bra_uploaded_files'] as $f){
                $files[] = new UploadedFile($f['tmp_name'], $f['name'] ,  $f['type'],   $f['error']);
            }

//            return $this->page_data = bra_res(500  , '没有文件' , $files ,$_W['_bra_uploaded_files']);
        }
        // 获取表单上传文件
//        if(empty($files)){
//            $files = $_GPC['files'];
//        }
        try {
            foreach ($files as $file) {
                $path = $file->path();
                $mime= $file->getMimeType();
                $file_size = $file->getSize();
                $fileHash = sha1_file($path);
                $inst = (array)$this->annex_m->bra_where(['md5' => $fileHash])->first();
                if (!$inst) {
                    $inst = [];
                    $inst['md5'] = $fileHash;
                    $inst['user_id'] = $_W['user']['id'];
                    $inst['filemime'] = $mime;
                    $filemime = explode('/', $inst['filemime']);
                    $inst['file_type'] = $this->file_types[$filemime[0]] ?? '5';
                    $inst['filesize'] = $file_size;
                    $inst['create_at'] = date('Y-m-d H:i:s');
                    $inst['site_id'] = $_W['site']['id'];
                    // Get a file name
                    if (isset($_REQUEST["filename"])) {
                        $fileName = $_REQUEST["filename"];
                    } elseif (!empty($_FILES)) {
                        $fileName = $_FILES["file"]["name"];
                    } else {
                        $fileName = uniqid("file_");
                    }
                    $inst['filename'] = $fileName;
                    $extend_name = "." . BraFS::file_ext($fileName);
                    if (strpos(strtolower($extend_name), 'php') !== false) {
                        return bra_res(500, 'system_error');
                    }
                    if (isset($_GPC['is_private']) && $_GPC['is_private']) {
                        $save_path = $file->store($this->save_path);
                    } else {
                        $save_path = $file->store($this->save_path);
                    }
                    $shrink_res = BraAnnex::shrink_img(PUBLIC_ROOT . $save_path, (bool)$_GPC['keep_clear']);
                    // save to annex
                    $inst['url'] = $save_path;
                    $inst['provider_id'] = 1;
                    $inst['id'] = $this->annex_m->insertGetId($inst);
                }
                //upload $this->save_path to cloud storage
                $storge = new BraAnnex($inst['id']);
                $res = $storge->upload();
                if (isset($shrink_res)) {
                    $inst['shrink_res'] = $shrink_res;
                }
                if (!is_error($res)) {
                    $inst['code'] = 1;
                    $inst['res'] = $res;
                    $inst['msg'] = '上传成功!';

                    A($inst);
                    return $this->page_data = $inst;
                } else {
                    $inst['code'] = 500;
                    $inst['res'] = $res;
                    $inst['msg'] = $res['msg'];

                    A($inst);
                    return $this->page_data = $inst;
                }
            }
        } catch (Exception $e) {
            return $this->page_data = bra_res(500, $e->getMessage());
        }

        return $this->page_data = bra_res(500  , '没有获取到文件!' , '' , app()->request->all());
    }

    public function save_annex() {

    }
    public function createFileFromChunks ($temp_dir, $fileName, $chunkSize, $totalSize) {
        // count all the parts of this file
        $total_files = 0;
        foreach (scandir($temp_dir) as $file) {
            if (stripos($file, $fileName) !== false) {
                $total_files++;
            }
        }
        // check that all the parts are present
        // the size of the last part is between chunkSize and 2*$chunkSize
        if ($total_files * $chunkSize >= ($totalSize - $chunkSize + 1)) {
            // create the final destination file
            if (($fp = fopen($this->upload_tmp_path . $fileName, 'w')) !== false) {
                for ($i = 1; $i <= $total_files; $i++) {
                    fwrite($fp, file_get_contents($temp_dir . '/' . $fileName . '.part' . $i));
                }
                fclose($fp);
            } else {
                return bra_res(500, '对不起创建文件失败');
            }
            // rename the temporary directory (to avoid access from other
            // concurrent chunks uploads) and than delete it
            if (false && rename($temp_dir, $temp_dir . '_UNUSED')) {
                //	$this->rrmdir($temp_dir . '_UNUSED');
            } else {
                //	$this->rrmdir($temp_dir);
            }

            return bra_res(1, '上传完成', '', $this->upload_tmp_path . $fileName);
        } else {
            return bra_res(500, 'File is still uploading ' , '' , $temp_dir);
        }
    }

    public function bra_annex_ueditor ($query) {
        $action = $query['action'];
        switch ($action) {
            case "config" :
                $result = $this->config();
                break;
            case "uploadimage" :
            case "uploadscrawl" :
            case 'uploadvideo':
                /* 上传文件 */
            case 'uploadfile':
                $result = $this->bra_annex_upload();
                $annex = new BraAnnex($result['id']);
                $data = $annex->annex;
                $data['state'] = "SUCCESS";
                $data['url'] = $annex->get_url();

                $result = $data;
                break;
            case "upload_avatar" :
                $result = $this->avatar_upload();
                break;
            /* 列出图片 */
            case 'listimage':
                $result = $this->listimage();
                break;
            /* 列出文件 */
            case 'listfile':
                $result = $this->listfile();
                break;
            /* 抓取远程文件 */
            case 'catchimage':
                $result = $this->catchimage();
                break;
            default:
                $result = json_encode(array('state' => '请求地址出错'));
                break;
        }

        return $this->page_data = $result;
    }


    private function config () {
        global $_W;
        $config_str = preg_replace("/\/\*[\s\S]+?\*\//", "", file_get_contents(PUBLIC_ROOT . "statics/packs/ueditor/php/config.json"));
        //图片前缀
        $storge_engine = BraAnnex::get_default();
        $prefix = $storge_engine->get_prefix();
        $config_str = str_replace("{imageUrlPrefix}", $prefix, $config_str);
        $CONFIG = $this->ue_config = json_decode($config_str, true);

        return $CONFIG;
    }
}
