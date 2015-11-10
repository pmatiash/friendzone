<?php

namespace app\controllers;

class SiteController extends \yii\web\Controller
{
    public $layout = 'api';

    public function actionIndex()
    {
        return $this->render('index');
    }

    public function actionUsers()
    {
        return $this->render('users', [
            'users' => \app\models\User::getCollection()
                ->find()
                ->limit(200)
        ]);
    }

}
