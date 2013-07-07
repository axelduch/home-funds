<?php

class ApiController extends CController {
    /**
     * Key which has to be in HTTP USERNAME and PASSWORD headers
     * 
     */
    const APP_ID = 'ASCCPE';

    private $format = 'json';

    public $defaultAction = 'index';

    public function init() {

    }

    public function filters() {
    }

    /**
     * Fetches and outputs a list for a given model
     * Based on GET method
     * Output format: JSON
     * Terminates the execution
     */
    public function actionList($ModelClass) {
        $action = $this->getAction()->getId();
        if (ApiHelper::ableToRetrieveModel($ModelClass, $action)) {
            $model = $ModelClass::model()->findAll();
        } else {
            $error = new stdClass();
            $error->msg = sprintf('Mode list is not implemented for model "%s" and action "%s"', $ModelClass, $action);
            $this->_sendResponse(501, CJSON::encode($error));
        }
        $this->_sendResponse(200, CJSON::encode($model));
    }
    /**
     * Fetches a single model data and outputs the result
     * Based on GET method
     * Output format: JSON
     * Terminates the execution
     */
    public function actionView($ModelClass, $id) {
        $action = $this->getAction()->getId();
        if (ApiHelper::ableToRetrieveModel($ModelClass, $action)) {
            $model = $ModelClass::model()->find((int) $id);
        } else {
            $error = new stdClass();
            $error->msg = sprintf('Mode view is not implemented for model "%s" and action "%s"', $ModelClass, $action);
            $this->_sendResponse(501, CJSON::encode($error));
            Yii::app()->end();
        }
        if ($model === null) {
            $error = new stdClass();
            $error->msg = sprintf('Model "%s" with id "%s" was not found', $ModelClass, (int) $id);
            $this->_sendResponse(404, CJSON::encode($error));
        }
        $this->_sendResponse(200, CJSON::encode($model));
    }

    public function actionCreate($ModelClass) {
        Yii::trace('Entering ApiController action create');
        $action = $this->getAction()->getId();
        if (ApiHelper::ableToRetrieveModel($ModelClass, $action)) {
            $model = new $ModelClass;
        } else {
            $error = new stdClass();
            $error->msg = sprintf('Mode create is not implemented for model "%s" and action "%s"', $ModelClass, $action);
            $this->_sendResponse(501, CJSON::encode($error));
        }
        
        $input = Yii::app()->request->rawBody;
        $post = CJSON::decode($input, true);
        if (is_array($post) && !empty($post)) {
            foreach ($post as $prop => $value) {
                if ($model->hasAttribute($prop)) {
                    $model->$prop = $value;
                } else {
                    $error = new stdClass();
                    $error->msg = sprintf('Unallowed parameter "%s" for model "%s"', $prop, $ModelClass);
                    $this->_sendResponse(500, CJSON::encode($error));
                }
            }
            if ($model->save()) {
                $this->_sendResponse(200, CJSON::encode(
                    $ModelClass::model()->findByPk($model->id))
                );
            } else {
                $errors = new stdClass();
                $errors->msg = sprintf('Could not create model "%s"', $ModelClass);
                $errors->errors = $model->errors;
                $this->_sendResponse(500, CJSON::encode($model->errors));
            }
        }
    }

    public function actionImport($ModelClass) {
        $action = $this->getAction()->getId();
        if (!ApiHelper::ableToRetrieveModel($ModelClass, $action)) {
            $error = new stdClass();
            $error->msg = 'You are not allowed to import';
            $error->debug = $model;
            $this->_sendResponse(403, CJSON::encode($error));
        }
        
        $input = Yii::app()->request->rawBody;
        $post = CJSON::decode($input, true);
        if (is_array($post) && !empty($post)) {
            if (!isset($post['data']) || !is_array($post['data'])) {
                $error = new stdClass();
                $error->msg = 'Expected key "data" to be defined and to be of type array';
                $this->_sendResponse(400, CJSON::encode($error));
            }
            
            // Start importing
            
            // Track some pieces of information
            $createdCount = 0;
            $errors = array();
            $debug = array();
            
            foreach ($post['data'] as $modelData) {
                $model = new $ModelClass;
                $errorForCurrent = FALSE;
                foreach ($modelData as $prop => $value) {
                    if ($model->hasAttribute($prop)) {
                        $model->$prop = $value;
                    } else {                        
                        $error = new stdClass();
                        $error->msg = sprintf('Unallowed parameter "%s" for model "%s"', $prop, $ModelClass);
                        $errors[] = $error;
                        unset($error);
                        // forget about current model
                        continue;
                    }
                }
                if (!$errorForCurrent) {
                    $debug[] = $model;
                    if (!$model->save()) {
                        $error = new stdClass();
                        $error->msg = 'Could not save model...';
                        $errors[] = $error;
                    }                     
                } else {
                    ++$createdCount;
                }    
            }
            
            // Build information if a failure occurred for one or more models
            if (isset($errors[0])) {
                
            }
            
            $resp = array (
                'created_count' => $createdCount,
                'errors' => $errors,
                'debug' => $debug
            );
            
            // Send information
            $this->_sendResponse(isset($errors[0]) ? 400 : 200, CJSON::encode($resp));
        }
        
        $error = new stdClass();
        $error->msg = 'Cannot import empty data set!';
        $error->debug = gettype($post);
        $this->_sendResponse(400, CJSON::encode($error));
    }
    
    public function actionUpdate() {

    }

    public function actionDelete($ModelClass, $id) {
        $action = $this->getAction()->getId();
        
        if (ApiHelper::ableToRetrieveModel($ModelClass, $action)) {
            $model = $ModelClass::model()->findByPk((int) $id);
        } else {
            $error = new stdClass();
            $error->msg = 'You are not allowed to delete this';
            $this->_sendResponse(403, CJSON::encode($error));
        }
        
        if ($model->delete()) {
            $this->_sendResponse(200, true);
        }
        
        $this->_sendResponse(200, CJSON::encode('success'));
    }
    
    /**
     * This method outputs a given response
     * And terminates the execution of current script
     */
    private function _sendResponse($status = 200, $body = '', $content_type = 'application/json') {
        // Make sure no output buffering occurs
        while (ob_get_level()) {
            ob_end_clean();
        }
        // set the status
        $status_header = 'HTTP/1.1 ' . $status . ' ' . $this->_getStatusCodeMessage($status);
        header($status_header);
        // and the content type
        header('Content-type: ' . $content_type);
        // pages with body are easy
        if ($body !== '') {
            // send the body
            echo $body;
        }
        // we need to create the body if none is passed
        else {
            // create some body messages
            $message = '';

            // this is purely optional, but makes the pages a little nicer to read
            // for your users.  Since you won't likely send a lot of different status codes,
            // this also shouldn't be too ponderous to maintain
            switch($status) {
                case 401 :
                    $message = 'You must be authorized to view this page.';
                    break;
                case 404 :
                    $message = 'The requested URL ' . $_SERVER['REQUEST_URI'] . ' was not found.';
                    break;
                case 500 :
                    $message = 'The server encountered an error processing your request.';
                    break;
                case 501 :
                    $message = 'The requested method is not implemented.';
                    break;
            }

            // servers don't always have a signature turned on
            // (this is an apache directive "ServerSignature On")
            $signature = ($_SERVER['SERVER_SIGNATURE'] === '') ? $_SERVER['SERVER_SOFTWARE'] . ' Server at ' . $_SERVER['SERVER_NAME'] . ' Port ' . $_SERVER['SERVER_PORT'] : $_SERVER['SERVER_SIGNATURE'];
            var_dump($status);
            $stdObject = new stdClass();
            $stdObject->msg = $message;
            echo CJSON::encode($stdObject);
        }
        Yii::app()->end();
    }

    private function _getStatusCodeMessage($status) {
        // these could be stored in a .ini file and loaded
        // via parse_ini_file()... however, this will suffice
        // for an example
        $codes = Array(200 => 'OK', 400 => 'Bad Request', 401 => 'Unauthorized', 402 => 'Payment Required', 403 => 'Forbidden', 404 => 'Not Found', 500 => 'Internal Server Error', 501 => 'Not Implemented', );
        return (isset($codes[$status])) ? $codes[$status] : '';
    }

}
