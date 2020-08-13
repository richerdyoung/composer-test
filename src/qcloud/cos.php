<?php

namespace tpcms\qcloud;

use tpcms\libs\Helper;
use Qcloud\Cos\Client;



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
        $this->helper = new Helper();
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
            $DataList = $result['Buckets'][0]['Bucket'];
            foreach ($DataList as $key => $value) {
                $DataList[$key]['CreationDate'] = date('Y-m-d H:i:s', strtotime($value['CreationDate']));
            }
            return $DataList;

        } catch (\Exception $e) {
            //请求失败
            echo($e->getMessage());
        }
    }


     /**
     * 查看bucket以及权限
     */
    public function  buketDetail($bucket_name){
        $cosClient = $this->getClient();
        try {
            $result = $cosClient->getBucketAcl(array(
                'Bucket' => $bucket_name //格式：BucketName-APPID
            ))->toArray();
            $DataMes = [
                'Owner'=>$result['Owner'],
                'GrantsList'=>$result['Grants'][0]['Grant'],
                'Location'=>$result['Location'],
            ];        
            return $DataMes;
        } catch (\Exception $e) {
            //请求失败
            echo($e->getMessage());
        }
    }    
    
    

    /**
     * 文件列表
     */
    public function fileList($Prefix,$Marker,$BucketName,$MaxKeys){

        $cosClient = $this->getClient();

        try {

            $TxcosResult = $cosClient->listObjects(array(
                'Bucket' => $BucketName, 
                'Delimiter' => '/',
                'EncodingType' => 'url',
                'Marker' => $Marker,      //上次列出对象的断点
                'Prefix' => $Prefix,    //列出对象的前缀
                'MaxKeys' => $MaxKeys
            ))->toArray();

            $TxcosDomainUrl = $TxcosResult['Location'];
            $TxcosMaxKeys = $TxcosResult['MaxKeys'];
            $TxcosIsTruncated = $TxcosResult['IsTruncated'];
            $TxcosMarker = $TxcosResult['Marker'];
            $TxcosNextMarker = '';
            if($TxcosIsTruncated == 1){
                $TxcosNextMarker = $TxcosResult['NextMarker'];
            }
            $TxcosPrefix = $TxcosResult['Prefix'];
            $BucketNameList = [
                [
                    'PrefixPath'=>'',
                    'PrefixName'=>$BucketName, 
                    'PrefixStr'=>'/',
                ],
            ];
            $LastStr = substr($TxcosPrefix, -1 );
            $DataPrefixList = [];
            if(!empty($TxcosPrefix) &&  $LastStr ==='/'){
                $PrefixMes = explode('/',$TxcosPrefix);
                array_pop($PrefixMes);//剔除最后一个空元素
                $PrefixPath = '';
                for ($i=0; $i < count($PrefixMes); $i++) { 
                    # code...
                    $PrefixPath .= $PrefixMes[$i].'/';
                    $DataPrefixList[]= [
                        'PrefixPath'=>$PrefixPath,
                        'PrefixName'=>$PrefixMes[$i],
                        'PrefixStr'=>'/',
                    ];
                }
                array_walk($BucketNameList,function($item) use (&$DataPrefixList) {
                    array_unshift($DataPrefixList, $item);
                });
            }else{
                $DataPrefixList = $BucketNameList;
            }
            
            //文件夹
            $FolderList=[];
            if(isset($TxcosResult['CommonPrefixes'])   && !empty($TxcosResult['CommonPrefixes']) ){
                $CommonPrefixes = $TxcosResult['CommonPrefixes'];
                foreach ($CommonPrefixes as $key => $value) {
                    # code...
                    $FolderList[] = [
                        'FileType'=>2,
                        'FileKeyName'=>$value['Prefix'],
                        'NewFileKeyName'=>$value['Prefix'],
                        'FileSize'=>'--',
                        'StorgeType'=>'--',
                        'LastUpdateTime'=>'--',
                    ];
                }
            }
            //文件
            $FileList=[];
            if(isset($TxcosResult['Contents'])   && !empty($TxcosResult['Contents']) ){
                $Contents = $TxcosResult['Contents'];
                foreach ($Contents as $key => $value) {
                    # code...
                    $FileList[] = [
                        'FileType'=>1,
                        'FileKeyName'=>$value['Key'],
                        'NewFileKeyName'=>$value['Key'],
                        'FileSize'=>$this->helper->GetFileSize($value['Size']),
                        'StorgeType'=>$value['StorageClass'],
                        'LastUpdateTime'=>date('Y-m-d H:i:s', strtotime($value['LastModified'])),
                    ];
                }
            }
            
            $DataList  = array_merge($FolderList,$FileList);
            $result = [
                'TxcosDomainUrl'=>$TxcosDomainUrl,
                'TxcosIsTruncated'=>$TxcosIsTruncated,
                'TxcosMaxKeys'=>$TxcosMaxKeys,
                'TxcosMarker'=>$TxcosMarker,
                'TxcosNextMarker'=>$TxcosNextMarker,
                'TxcosPrefix'=>$TxcosPrefix,
                'DataPrefixList'=>$DataPrefixList,
                'DataList'=>$DataList,
                'TxcosResult'=>$TxcosResult,
                
            ];

            return $result;

        } catch (\Exception $e) {
            //请求失败
            echo($e->getMessage());
        }

       
    }


    
    /**
     * 文件上传
     */
    public function fileUpload($BucketName,$file_data,$path){

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

        $cosClient = $this->getClient();   
       
        try {
            $result = $cosClient->putObject([ 
                'Bucket' => $BucketName,
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
     * 下载文件
     */
    public function fileDownload($BucketName,$KeyName,$SaveAs){
        $cosClient = $this->getClient();
        try {
            $result = $cosClient->getObject(array(
                'Bucket' => $BucketName, 
                'Key' => $KeyName,
                'SaveAs' => $SaveAs,
                /*
                'Range' => 'bytes=0-10',
                'ResponseCacheControl' => 'string',
                'ResponseContentDisposition' => 'string',
                'ResponseContentEncoding' => 'string',
                'ResponseContentLanguage' => 'string',
                'ResponseContentType' => 'string',
                'ResponseExpires' => 'string',
                */
            ))->toArray();
            
            // 请求成功
            return $result;

        } catch (\Exception $e) {
            // 请求失败
            echo($e);
        }
    
    }



    /**
     * 删除文件
     */
    public function fileDel($BucketName,$KeyName){
        $cosClient = $this->getClient();
        try {
            $result = $cosClient->deleteObject(array(
                'Bucket' => $BucketName, //格式：BucketName-APPID
                'Key' => $KeyName,
            ))->toArray();


            // 请求成功
            return $result;
        } catch (\Exception $e) {
            // 请求失败
            echo($e);
        }
    
    }




}





