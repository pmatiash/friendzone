<?php
/**
 * Created by PhpStorm.
 * User: Ltm
 * Date: 05.11.2015
 * Time: 15:29
 */

namespace app\controllers;

use Swagger\Annotations as SWG;

class ApiController extends \yii\rest\Controller
{
    /**
     * @var \yii\web\Response
     */
    private $response;

    /**
     * @var \yii\web\Request
     */
    private $request;

    public $modelClass = 'app\models\User';

    const ERROR_CODE_OK = 200;
    const ERROR_CODE_ERROR = 400;
    const ERROR_CODE_FORBID = 503;

    public function init()
    {
        parent::init();
        $handler = new \app\components\ApiErrorHandler();
        \Yii::$app->set('errorHandler', $handler);
        $handler->register();
    }

    public function behaviors()
    {
        $behaviors = parent::behaviors();
        $behaviors['contentNegotiator']['formats'] = ['application/json' => \yii\web\Response::FORMAT_JSON];
        return $behaviors;
    }

    /**
     * @inheritdoc
     */
    protected function verbs()
    {
        return [
            'set-friend-request' => ['POST'],
            'get-friend-requests' => ['GET'],
            'update-friend-request' => ['PATCH'],
            'get-friend-list' => ['GET'],
            'get-friends-friends' => ['GET'],
        ];
    }

    public function beforeAction($action)
    {
        if (parent::beforeAction($action)) {
            $this->response = \Yii::$app->response;
            $this->request = \Yii::$app->request;
            $this->response->statusCode = self::ERROR_CODE_OK;

            return true;
        }

        return true;
    }

    /**
     * @SWG\Post(
     *     path="/api/friend/{id}",
     *     summary="Add friend to the current user",
     *     tags={"Friend Request"},
     *     description="",
     *     operationId="findPetsByTags",
     *     consumes={"application/json"},
     *     produces={"application/json"},
     *     @SWG\Parameter(
     *         name="id",
     *         in="path",
     *         description="Current userId",
     *         required=true,
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="receiverId",
     *         in="formData",
     *         description="Friend Id",
     *         required=true,
     *         type="string"
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="Successful operation"
     *     ),
     *     @SWG\Response(
     *         response="400",
     *         description="Bad request",
     *     )
     * )
     */
    public function actionSetFriendRequest($id)
    {
        $receiverId = $this->request->post('receiverId');

        if (!$id || !$receiverId) {
            throw new \yii\web\HttpException(self::ERROR_CODE_ERROR, 'Missed required parameter');
        }

        $sender = \app\models\User::findOne(['_id' => $id]);

        if (!$sender) {
            throw new \yii\web\HttpException(self::ERROR_CODE_ERROR, 'User is not found');
        }

        $receiver = \app\models\User::findOne(['_id' => $receiverId]);

        if (!$receiver) {
            throw new \yii\web\HttpException(self::ERROR_CODE_ERROR, 'User is not found');
        }

        $request = new \app\models\FriendRequest($sender, $receiver);

        if ($request->validate()) {
            $request->save(false);

        } else {
            $responseMessage = '';
            foreach ($request->getFirstErrors() as $field => $errorMessage) {
                $responseMessage .= $errorMessage;
            }

            throw new \yii\web\HttpException(self::ERROR_CODE_ERROR, $responseMessage);
        }

        return ['result' => 'ok'];
    }


    /**
     * @SWG\Get(
     *     path="/api/friends/{id}",
     *     summary="Show all friend requests for the current user",
     *     tags={"Friend Request"},
     *     description="",
     *     operationId="findPetsByTags",
     *     consumes={"application/json"},
     *     produces={"application/json"},
     *     @SWG\Parameter(
     *         name="id",
     *         in="path",
     *         description="Current userId",
     *         required=true,
     *         type="string",
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="Successful operation"
     *     ),
     *     @SWG\Response(
     *         response="400",
     *         description="Bad request",
     *     )
     * )
     */
    public function actionGetFriendRequests($id)
    {
        if (!$id) {
            throw new \yii\web\HttpException(self::ERROR_CODE_ERROR, 'Missed required parameter Id');
        }

        $user = \app\models\User::findOne($id);

        if (!$user) {
            throw new \yii\web\HttpException(self::ERROR_CODE_ERROR, 'User is not found');
        }

        $requests = [];
        foreach ($user->getFriendRequests() as $request) {

            $requests[] = [
                'sender' => (string)$request['senderId'],
                'receiver' => (string)$request['receiverId'],
                'status' => $request['status']
            ];
        }

        return ['result' => 'ok', 'requests' => $requests];
    }

    /**
     * @SWG\Patch(
     *     path="/api/friend/{id}",
     *     summary="Change status for the friend request: approve or cancel friend request",
     *     tags={"Friend Request"},
     *     description="",
     *     operationId="findPetsByTags",
     *     consumes={"application/json"},
     *     produces={"application/json"},
     *     @SWG\Parameter(
     *         name="id",
     *         in="path",
     *         description="Current userId",
     *         required=true,
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="friendId",
     *         in="formData",
     *         description="Friend Id",
     *         required=true,
     *         type="string"
     *     ),
     *     @SWG\Parameter(
     *         name="status",
     *         in="formData",
     *         description="Request Status",
     *         required=true,
     *         type="integer",
     *         default=1,
     *         enum={1, 2}
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="Successful operation"
     *     ),
     *     @SWG\Response(
     *         response="400",
     *         description="Bad request",
     *     )
     * )
     */
    public function actionUpdateFriendRequest($id)
    {
        if (!$id) {
            throw new \yii\web\HttpException(self::ERROR_CODE_ERROR, 'Missed required parameter Id');
        }

        $friendId = $this->request->post('friendId');

        if (!$friendId) {
            throw new \yii\web\HttpException(self::ERROR_CODE_ERROR, 'Missed required parameter friendId');
        }

        $request = \app\models\FriendRequest::findOne([
            'senderId' => new \MongoId($friendId),
            'receiverId' => new \MongoId($id)
        ]);

        if (!$request) {
            throw new \yii\web\HttpException(self::ERROR_CODE_ERROR, 'Request is not exists');
        }

        $status = $this->request->post('status');

        if (!$status) {
            throw new \yii\web\HttpException(self::ERROR_CODE_ERROR, 'Missed required parameter status');
        }

        $request->setStatus($status);

        if (!$request->save()) {
            throw new \yii\web\HttpException(self::ERROR_CODE_ERROR, $request->getFirstErrorString());
        }

        return ['result' => 'ok'];
    }

    /**
     * @SWG\Get(
     *     path="/api/friends/{id}/{status}",
     *     summary="Show all friend requests filtered by special status",
     *     tags={"Friend Request"},
     *     description="",
     *     consumes={"application/json"},
     *     produces={"application/json"},
     *     @SWG\Parameter(
     *         name="id",
     *         in="path",
     *         description="Current userId",
     *         required=true,
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="status",
     *         in="path",
     *         description="Request status",
     *         required=true,
     *         type="integer",
     *         default=1,
     *         enum={1}
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="Successful operation"
     *     ),
     *     @SWG\Response(
     *         response="400",
     *         description="Bad request",
     *     )
     * )
     */
    public function actionGetFriendList($id, $status = null)
    {
        if (!$id) {
            throw new \yii\web\HttpException(self::ERROR_CODE_ERROR, 'Missed required parameter Id');
        }

        $user = \app\models\User::findOne($id);

        if (!$user) {
            throw new \yii\web\HttpException(self::ERROR_CODE_ERROR, 'User is not found');
        }

        if (is_null($status)) {
            throw new \yii\web\HttpException(self::ERROR_CODE_ERROR, 'Missed required parameter status');
        }

        if (in_array($status, \app\models\FriendRequest::getAllStatuses()) === false) {
            throw new \yii\web\HttpException(self::ERROR_CODE_ERROR, 'Status is out of range');
        }

        if ($status != \app\models\FriendRequest::STATUS_APPROVED) {
            throw new \yii\web\HttpException(self::ERROR_CODE_FORBID, 'Operations is not allowed');
        }

        $friends = [];
        foreach ($user->getFriends()->all() as $key => $friend) {
            $friends[$key] = $friend->getAttributes();
            $friends[$key]['_id'] = $friend->getId(true);
        }

        return ['result' => 'ok', 'friends' => $friends];
    }

    /**
     * @SWG\Get(
     *     path="/api/friends/{id}/friends/{level}",
     *     summary="Show all friends of friends to according recursively level",
     *     tags={"Friend Request"},
     *     description="",
     *     consumes={"application/json"},
     *     produces={"application/json"},
     *     @SWG\Parameter(
     *         name="id",
     *         in="path",
     *         description="Current userId",
     *         required=true,
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="level",
     *         in="path",
     *         description="Request status",
     *         required=true,
     *         type="integer",
     *         default=2,
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="Successful operation"
     *     ),
     *     @SWG\Response(
     *         response="400",
     *         description="Bad request",
     *     )
     * )
     */
    public function actionGetFriendsFriends($id, $level = 0)
    {
        if (!$id) {
            throw new \yii\web\HttpException(self::ERROR_CODE_ERROR, 'Missed required parameter Id');
        }

        $user = \app\models\User::findOne($id);

        if (!$user) {
            throw new \yii\web\HttpException(self::ERROR_CODE_ERROR, 'User is not found');
        }

        return ['result' => 'ok', 'friends' => $user->getFriendsRecursive($level)];
    }

}