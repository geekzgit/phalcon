<?php
/**
 * 自定义异常
 */
class SystemException extends exception {

    /**
     * 快捷抛出异常,默认异常代码50000
     */
    public static function error($err, $code = 50000) {
        throw new static($err, $code);
    }

}
