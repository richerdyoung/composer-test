<?php

namespace tpcms\libs;

class Helper{

    
    //获取文件的大小
    public  function GetFileSize($filesize){
        if($filesize >= '1073741824'){
            $filesize_name = round($filesize/1073741824,2).' GB';
        }elseif($filesize >= '1048576'){
            $filesize_name = round($filesize/1048576,2).' MB';
        }elseif($filesize >= '1024'){
            $filesize_name = round($filesize/1024,2).' KB';
        }else{
            $filesize_name = $filesize.'B'; 
        }
        return $filesize_name;

    }











}

?>