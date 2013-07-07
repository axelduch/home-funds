<?php
class ApiHelper {
    /**
     * Tells whether a model can be used in ApiController or not
     * @return boolean
     */
    public static function ableToRetrieveModel($modelName, $action) {
        $can = false;
        $apiModels = Yii::app()->params['apiModels'];
        if (in_array(strtolower($modelName), array_keys($apiModels))) {
            if (class_exists($modelName)) {
                $reflectObj = new ReflectionClass($modelName);
                if ($reflectObj->isSubclassOf('CActiveRecord')
                    && in_array($action, $apiModels[$modelName])) {
                    $can = true;
                }
            }
        }
        return $can;
    }
}
