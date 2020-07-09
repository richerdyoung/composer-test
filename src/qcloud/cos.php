<?php
/**
 * 腾讯云oss服务
 * 依然范儿特西
 */

namespace tpcms\qcloud;

use Qcloud\Cos\Client;
use think\Log;

class Cos
{
   /**
     * @var array
     */
    protected $config;

     /**
     * @var array
     */
    protected $client;

    public function __construct(array $config = []){
        $this->config = $config;
    }

    
    /**
     * @return string
     */
    public function getBucket()
    {
        return $this->config['bucket'];
    }

    
    /**
     * @return string
     */
    public function getRegion()
    {
        return $this->config['region'] ?? '';
    }

    /**
     * @return string
     */
    public function getAppId(){
        return $this->config['credentials']['appId'] ?? null;
    }

      /**
     * @return string
     */
    public function getSecretId(){
        return $this->config['credentials']['secretId'] ?? null;
    }


    /**
     * @return string
     */
    public function getSecretKey(){
        return $this->config['credentials']['secretKey'] ?? null;
    }

     /**
     * @return array
     */
    public function getClient(){
        $cosClient = new Client([
            'region' => $this->getRegion(),
            'credentials'=>[
                'appId'     => $this->getAppId(),
                'secretId'    => $this->getSecretId(),
                'secretKey' => $this->getSecretKey(),
            ]
        ]);
        return $cosClient;
    }
    


     /**
     * bucket列表
     */
    public function  buketList(){
        $cosClient = $this->getClient();
        try {
            //请求成功
            $result = $cosClient->listBuckets()->toArray();
            return $result;
        } catch (\Exception $e) {
            
            //请求失败
            echo($e->getMessage());
        }

    }

     /**
     * 查看bucket以及权限
     */
    public function  buketDetail($Name){
        $cosClient = $this->getClient();
        try {
            $result = $cosClient->getBucketAcl(array(
                'Bucket' => $Name //格式：BucketName-APPID
            ))->toArray();
            return $result;
        } catch (\Exception $e) {
            
            //请求失败
            echo($e->getMessage());
        }

        
    }    
    
    /**
     * 创建bucket
     */
    public function  buketCreat(){
        $cosClient = $this->getClient();
        try {
            $result = $cosClient->createBucket(array(
                'Bucket' => $Name //格式：BucketName-APPID
            ))->toArray();
            return $result;
        } catch (\Exception $e) {
            //请求失败
            echo($e->getMessage());
        }
    }




     /**
     * 删除bucket
     */
    public function  buketDel(){

      $cosClient = $this->getClient();

        try {
            $result = $cosClient->deleteBucket(array(
                'Bucket' => $Name //格式：BucketName-APPID
            ))->toArray();
            return $result;
        } catch (\Exception $e) {
            //请求失败
            echo($e->getMessage());
        }


       
    }




    
    /**
     * 文件上传
     */
    public function fileUpload($file_data,$path){

        $result_data = [
            'file_ext'=>'',
            'file_path'=>'',
            'file_view'=>'',
            'file_name'=>'出错了',
        ];

        //例如/users/local/myfile.txt
        $filePath = $file_data['tmp_name'];
        // 上传文件后缀
        $file_ext = substr($file_data['name'],strrpos($file_data['name'],'.')+1);
        //文件名称
        $file_name = md5(microtime()).'.'.$file_ext;
        // 文件名称
        $object = $path.'/'.$file_name;   
        $cosClient = new Client([
            'region' => $this->getRegion,
            'credentials'=>[
                'appId'     => $this->getAppId,
                'secretId'    => $this->getSecretId,
                'secretKey' => $this->getSecretKey
            ]
        ]);     
        //bucket的命名规则为{name}-{appid} ，此处填写的存储桶名称必须为此格式
        $bucket = $this->getBucket;
        try {
            $result = $cosClient->putObject([ 
                'Bucket' => $bucket,
                'Key' => $object,
                'Body' => fopen($filePath, 'rb')
            ]);
            $result_data = [
                'file_ext'=>$file_ext,
                'file_path'=>'/'.$result['Key'],
                'file_view'=>$result['Location'],
                'file_name'=>$file_name,
            ];
            
        } catch (\Exception $e) {
            $result_data = [
                'file_ext'=>$file_ext,
                'file_path'=>'',
                'file_view'=>'',
                'file_name'=>$file_name,
            ];
           
        }
        return $result_data;
   
    }


    /**
     * 文件列表
     */
    public function fileList(){

    }

    /**
     * 删除文件
     */
    public function fileDel(){

    }




}





