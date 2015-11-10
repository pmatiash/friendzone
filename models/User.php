<?php

namespace app\models;

/**
 * Class User
 * @package app\models
 */
class User extends \yii\mongodb\ActiveRecord
{

    public static function collectionName()
    {
        return 'users';
    }

    public function attributes()
    {
        return [
            '_id',
            'name',
            'friends'
        ];
    }

    public function init()
    {
        parent::init();

        if (!$this->friends) {
            $this->friends = [];
        }
    }
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    public function getId($asString = false)
    {
        if ($asString) {
            return (string)$this->_id;
        }

        return $this->_id;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getFriendRequests()
    {
        return \app\models\FriendRequest::getCollection()->find([
            '$or' => [
                ['senderId' => $this->getId()],
                ['receiverId' => $this->getId()]
            ]
        ]);
    }

    public function getFriends()
    {
        return $this->hasMany($this->className(), ['_id' => 'friends']);
    }

    public function getFriendsRecursive($level = 0, &$friends = [])
    {
        if (!$friends) {
            $friends = $this->getFriends()->all();
        }

        foreach ($friends as $key => $friend) {
            $result[$key] = $friend->getAttributes();
            $_friends = $friend->getFriends()->all();

            if ($level) {
                $result[$key]['friends'] = $this->getFriendsRecursive($level-1, $_friends);
            }
        }

       return isset($result) ? $result : [];
    }

    public function getFriendsId()
    {
        return $this->friends;
    }

    public function setFriend(\app\models\User $user)
    {
        $friends = $this->friends;
        $result = in_array($user->getId(), $friends);

        if (!$result) {
            array_push($friends, $user->getId());
            $this->friends = $friends;
        }

        return $this;
    }

    public function unsetFriend(\app\models\User $user)
    {
        $friends = $this->friends;
        $result = array_search($user->getId(), $friends);

        if ($result || $result === 0) {
            unset($friends[$result]);
            $this->friends = $friends;
        }

        return $this;
    }

}
