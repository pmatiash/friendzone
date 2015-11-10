<?php

namespace tests\api\controllers;

class ApiControllerCest
{
    /**
     * @var \app\models\User
     */
    private $sender;

    /**
     * @var \app\models\User
     */
    private $receiver;

    private function isUsersExists()
    {
        return $this->sender instanceof \app\models\User && $this->receiver instanceof \app\models\User;
    }

    public function setUp()
    {
        // insert test users to db
        $this->sender = (new \app\models\User())
            ->setName('testUserSender');
        $this->sender->save();

        $this->receiver = (new \app\models\User())
            ->setName('testUserReceiver');
        $this->receiver->save();
    }

    public function addFriend(\ApiTester $tester)
    {
        if (!$this->isUsersExists()) {
            throw new \Exception('Tested users were not set up');
        }

        $apiRoute = 'friend/' . $this->sender->getId(true);

        // correct response
        $tester->sendPOST($apiRoute, [
            'receiverId' => $this->receiver->getId(true)
        ]);
        $tester->seeResponseCodeIs(200);
        $tester->seeResponseIsJson();
        $tester->seeResponseContains('{"result":"ok"}');

        // duplicate request
        $tester->sendPOST($apiRoute, [
            'receiverId' => $this->receiver->getId(true)
        ]);
        $tester->seeResponseCodeIs(400);
        $tester->seeResponseIsJson();
        $tester->seeResponseContains('{"result":"error","message":');

        // reverted duplicate request
        $tester->sendPOST('friend/' . $this->receiver->getId(true), [
            'receiverId' => $this->sender->getId(true)
        ]);
        $tester->seeResponseCodeIs(400);
        $tester->seeResponseIsJson();
        $tester->seeResponseContains('{"result":"error","message":');

        // empty params
        $tester->sendPOST($apiRoute, []);
        $tester->seeResponseCodeIs(400);
        $tester->seeResponseIsJson();
        $tester->seeResponseContains(('{"result":"error","message":'));

        // missed required param
        $tester->sendPOST($apiRoute, [
            'senderId' => ''
        ]);
        $tester->seeResponseCodeIs(400);
        $tester->seeResponseIsJson();
        $tester->seeResponseContains(('{"result":"error","message":'));

        // user is not exists
        $tester->sendPOST($apiRoute, [
            'senderId' => '12345',
            'receiverId' => '12345'
        ]);
        $tester->seeResponseCodeIs(400);
        $tester->seeResponseIsJson();
        $tester->seeResponseContains(('{"result":"error","message":'));

        // incorrect format of param
        $tester->sendPOST($apiRoute, [
            'receiverId' => ['senderId' => $this->receiver->getId(true)]
        ]);
        $tester->seeResponseCodeIs(400);
        $tester->seeResponseIsJson();
        $tester->seeResponseContains(('{"result":"error","message":'));
    }

    public function getFriendRequests(\ApiTester $tester)
    {
        if (!$this->isUsersExists()) {
            throw new \Exception('Tested users were not set up');
        }

        $apiRoute = 'friends/' . $this->sender->getId(true);

        // correct response
        $tester->sendGET($apiRoute);
        $tester->seeResponseCodeIs(200);
        $tester->seeResponseIsJson();
        $tester->seeResponseContains('{"result":"ok","requests":[{"sender":"' . $this->sender->getId(true)
            .'","receiver":"' . $this->receiver->getId(true) .'","status":0}');

        // incorrect route
        $tester->sendGET('friends');
        $tester->seeResponseCodeIs(404);
        $tester->seeResponseIsJson();
        $tester->seeResponseContains('{"result":"error","message":');

        // user is not exists
        $tester->sendGET('friends/12345');
        $tester->seeResponseCodeIs(400);
        $tester->seeResponseIsJson();
        $tester->seeResponseContains('{"result":"error","message":');
    }

    public function updateFriendStatus(\ApiTester $tester)
    {
        if (!$this->isUsersExists()) {
            throw new \Exception('Tested users were not set up');
        }

        $apiRoute = 'friend/' . $this->receiver->getId(true);

        $statuses = [
            \app\models\FriendRequest::STATUS_APPROVED,
            \app\models\FriendRequest::STATUS_CANCELED
        ];

        foreach ($statuses as $status) {
            // correct response status Approved
            $tester->sendPATCH($apiRoute, [
                'friendId' => $this->sender->getId(true),
                'status' => $status,
            ]);

            $tester->seeResponseCodeIs(200);
            $tester->seeResponseIsJson();
            $tester->seeResponseContains('{"result":"ok"}');
        }

        // empty params
        $tester->sendPATCH($apiRoute, []);
        $tester->seeResponseCodeIs(400);
        $tester->seeResponseIsJson();
        $tester->seeResponseContains('{"result":"error","message":');

        // incorrect status code
        $tester->sendPATCH($apiRoute, [
            'friendId' => $this->sender->getId(true),
            'status' => 3,
        ]);
        $tester->seeResponseCodeIs(400);
        $tester->seeResponseIsJson();
        $tester->seeResponseContains('{"result":"error","message":');

        // user is not exist
        $tester->sendPATCH($apiRoute, [
            'friendId' => '12345',
            'status' => 0,
        ]);
        $tester->seeResponseCodeIs(400);
        $tester->seeResponseIsJson();
        $tester->seeResponseContains('{"result":"error","message":');

        // incorrect format
        $tester->sendPATCH($apiRoute, [
            'friendId' => ['friendId' => $this->sender->getId(true)],
        ]);
        $tester->seeResponseCodeIs(400);
        $tester->seeResponseIsJson();
        $tester->seeResponseContains('{"result":"error","message":');

        // revert request
        $tester->sendPATCH('friend/' . $this->sender->getId(true), [
            'friendId' => $this->receiver->getId(true),
            'status' => ['status' => 1],
        ]);
        $tester->seeResponseCodeIs(400);
        $tester->seeResponseIsJson();
        $tester->seeResponseContains('{"result":"error","message":');
    }

    public function getRequestsByStatus(\ApiTester $tester)
    {
        if (!$this->isUsersExists()) {
            throw new \Exception('Tested users were not set up');
        }

        $statuses = [
            \app\models\FriendRequest::STATUS_APPROVED,
        ];

        foreach ($statuses as $status) {
            $apiRoute = sprintf('friends/%s/%d', $this->sender->getId(true), $status);

            $tester->sendGET($apiRoute);
            $tester->seeResponseCodeIs(200);
            $tester->seeResponseIsJson();
            $tester->seeResponseContains('{"result":"ok"');
        }

        // incorrect user id
        $tester->sendGET('friends/12345/0');
        $tester->seeResponseCodeIs(400);
        $tester->seeResponseIsJson();
        $tester->seeResponseContains('{"result":"error","message":');

        // incorrect status
        $tester->sendGET(sprintf('friends/%s/123', $this->sender->getId(true)));
        $tester->seeResponseCodeIs(400);
        $tester->seeResponseIsJson();
        $tester->seeResponseContains('{"result":"error","message":');

        // forbid status
        $tester->sendGET(sprintf('friends/%s/0', $this->sender->getId(true)));
        $tester->seeResponseCodeIs(503);
        $tester->seeResponseIsJson();
        $tester->seeResponseContains('{"result":"error","message":');
    }

    public function getFriendsFriends()
    {

    }

    public function tearDown()
    {
        if ($this->isUsersExists()) {
            \app\models\FriendRequest::deleteAll([
                '$or' => [[
                    'senderId' => [
                        '$in' => [
                            $this->sender->getId(),
                            $this->receiver->getId()
                        ]
                    ],
                    'receiverId' => [
                        '$in' => [
                            $this->sender->getId(),
                            $this->receiver->getId()
                        ]
                    ]
                ]]
            ]);

            \app\models\User::deleteAll([
                '_id' => [
                    '$in' => [
                        $this->sender->getId(),
                        $this->receiver->getId()
                    ]
                ]
            ]);
        }
    }

}