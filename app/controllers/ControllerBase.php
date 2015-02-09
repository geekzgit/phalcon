<?php

use Phalcon\Mvc\Controller;

class ControllerBase extends Controller {

    /**
     * 判断请求是否上传了文件
     * @params string $name $_FILES[$name]
     */
    protected function hasFile($name) {
        if ($this->request->hasFiles()) {
            $files = $this->request->getUploadedFiles();
            foreach ($files as $file) {
                if ($file->getKey() == $name) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * 获取上传的文件
     * @params string $name $_FILES[$name]
     *
     * @return boolean | Phalcon\Http\Request\File
     */
    protected function getUploadedFile($name) {
        if ($this->request->hasFiles()) {
            $files = $this->request->getUploadedFiles();
            foreach ($files as $file) {
                if ($file->getKey() == $name) {
                    return $file;
                }
            }
        }
        return false;
    }
}
