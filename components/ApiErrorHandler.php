<?php
/**
 * Created by PhpStorm.
 * User: Ltm
 * Date: 07.11.2015
 * Time: 16:13
 */

namespace app\components;

class ApiErrorHandler extends \yii\web\ErrorHandler
{

    /**
     * @inheridoc
     */
    protected function renderException($exception)
    {
        if (\Yii::$app->has('response')) {
            $response = \Yii::$app->getResponse();

        } else {
            $response = new \yii\web\Response();
        }

        $response->format = \yii\web\Response::FORMAT_JSON;
        $response->data = [
            'result' => 'error',
            'message' => $exception->getMessage()
        ];

        if (isset($exception->statusCode)) {
            $response->setStatusCode($exception->statusCode);

        } else {
            $response->setStatusCode(400);
        }

        $response->send();
    }

}