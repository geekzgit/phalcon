<?php

class SystemController extends PageBaseController
{
    /**
     * 向数据库导入sql
     */
    public function importSqlAction() {
        if ($this->hasFile('file1')) {
            // Print the real file names and sizes
            $file = $this->getUploadedFile('file1');
            echo $file->getName(), " ", $file->getSize(), "\n";
        }
        echo 2;
    }

    public function testAction() {
        echo 3;
    }
}
