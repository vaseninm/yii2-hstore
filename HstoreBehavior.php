<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace vaseninm\behaviors;

use yii\base\InvalidCallException;
use yii\behaviors\AttributeBehavior;
use yii\db\BaseActiveRecord;


class HstoreBehavior extends AttributeBehavior
{
    public $attribute = [];


    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();

        if (empty($this->attributes)) {
            $this->attributes = [
                BaseActiveRecord::EVENT_AFTER_VALIDATE => [$this->attribute],
                BaseActiveRecord::EVENT_AFTER_FIND => [$this->attribute],
            ];
        }
    }

    /**
     * @inheritdoc
     */
    protected function getValue($event)
    {
        switch ($event->name) {
            case BaseActiveRecord::EVENT_AFTER_VALIDATE:
                return $this->hstoreEncode($this->owner->{$this->attribute});
            case BaseActiveRecord::EVENT_AFTER_FIND:
                return $this->hstoreDecode($this->owner->{$this->attribute});
            default:
                throw new InvalidCallException('Invalid get value call');
        }
    }

    protected function hstoreEncode($array) {
        if (empty($array))
            return null;

        $string = '';

        foreach ($array as $k => $v) {
            if ($v !== null)
                $string .= "\"{$this->quoteValue($k)}\"=>\"{$this->quoteValue($v)}\",";
            else
                $string .= "\"{$this->quoteValue($k)}\"=>NULL,";
        }

        return $string;
    }

    protected function hstoreDecode($raw) {
        $raw = preg_replace('/([$])/u', "\\\\$1", $raw);
        $unescapedHStore = array();
        eval('$unescapedHStore = array(' . $raw . ');');
        return $unescapedHStore;
    }

    protected function quoteValue($string) {
        return addslashes($string);
    }
}
