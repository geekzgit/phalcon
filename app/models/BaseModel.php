<?php

use Phalcon\Db\RawValue;

class BaseModel extends \Phalcon\Mvc\Model
{
    /**
     * 有默认值的属性
     */
    protected $defaultAttrs = [];

    public function initialize()
    {
        // 忽略某些列
        //$this->skipAttributes($this->skipAttrs);

    }

    public function beforeCreate()
    {
        $this->created_at = date('Y-m-d H:i:s');
    }

    public function beforeSave()
    {
        $this->updated_at = date('Y-m-d H:i:s');
        foreach ($this->defaultAttrs as $field) {
            if (! isset($this->$field)) {
                $this->$field = new RawValue('default');
            }
        }
    }

}
