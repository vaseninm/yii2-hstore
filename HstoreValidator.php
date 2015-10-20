<?php
/**
 * Created by PhpStorm.
 * User: mvasenin
 * Date: 19.10.15
 * Time: 17:44
 */

namespace vaseninm\behaviors;


use yii\base\Model;
use yii\validators\FilterValidator;
use yii\validators\Validator;
use yii\base\InvalidConfigException;


class HstoreValidator extends Validator
{

    /**
     * @var array|Validator definition of the validation rule, which should be used on array values.
     * It should be specified in the same format as at [[yii\base\Model::rules()]], except it should not
     * contain attribute list as the first element.
     * For example:
     *
     * ~~~
     * ['integer']
     * ['match', 'pattern' => '/[a-z]/is']
     * ~~~
     *
     * Please refer to [[yii\base\Model::rules()]] for more details.
     */
    public $rule;
    /**
     * @var boolean whether to use error message composed by validator declared via [[rule]] if its validation fails.
     * If enabled, error message specified for this validator itself will appear only if attribute value is not an array.
     * If disabled, own error message value will be used always.
     */
    public $allowMessageFromRule = true;
    public $key = [];

    /**
     * @var Validator validator instance.
     */
    private $_validator;

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();

        if ($this->key === null) {
            throw new InvalidConfigException('Key is required');
        }

        if ($this->message === null) {
            $this->message = \Yii::t('yii', '{attribute} is invalid.');
        }

        if (is_string($this->key)) {
            $this->key = [ $this->key ];
        }
    }

    /**
     * Returns the validator declared in [[rule]].
     * @param Model|null $model model in which context validator should be created.
     * @return Validator the declared validator.
     */
    private function getValidator($model = null)
    {
        if ($this->_validator === null) {
            $this->_validator = $this->createEmbeddedValidator($model);
        }


        return $this->_validator;
    }

    /**
     * Creates validator object based on the validation rule specified in [[rule]].
     * @param Model|null $model model in which context validator should be created.
     * @throws \yii\base\InvalidConfigException
     * @return Validator validator instance
     */
    private function createEmbeddedValidator($model)
    {
        $rule = $this->rule;
        if ($rule instanceof Validator) {
            return $rule;
        } elseif (is_array($rule) && isset($rule[0])) { // validator type
            if (!is_object($model)) {
                $model = new Model(); // mock up context model
            }
            return Validator::createValidator($rule[0], $model, $this->attributes, array_slice($rule, 1));
        } else {
            throw new InvalidConfigException('Invalid validation rule: a rule must be an array specifying validator type.');
        }
    }

    /**
     * @inheritdoc
     */
    public function validateAttribute($model, $attribute)
    {
        $value = $model->$attribute;
        $validator = $this->getValidator();
        if ($validator instanceof FilterValidator && is_array($value)) {
            $filteredValue = [];

            foreach ($this->key as $key) {
                if (!$validator->skipOnArray || !is_array($value[$key])) {
                    $filteredValue[$key] = call_user_func($validator->filter, $value[$key]);
                }
            }

            $model->$attribute = $filteredValue;
        } else {
            foreach ($this->key as $key) {
                $result = $this->getValidator($model)->validateValue($model->{$attribute}[$key]);

                if (!empty($result)) {
                    $this->addError($model, [$attribute, $key], $result[0], $result[1]);
                }
            }


        }
    }

    public function addError($model, $attribute, $message, $params = [])
    {
        $value = $model->{$attribute[0]}[$attribute[1]];
        $params['attribute'] = $model->getAttributeLabel($attribute[0]);
        $params['value'] = is_array($value) ? 'array()' : $value;
        $model->addError($attribute[0] . "[{$attribute[1]}]", \Yii::$app->getI18n()->format($message, $params, \Yii::$app->language));
    }
}