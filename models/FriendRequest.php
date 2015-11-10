<?php
/**
 * Created by PhpStorm.
 * User: Ltm
 * Date: 05.11.2015
 * Time: 10:10
 */

namespace app\models;


class FriendRequest extends \yii\mongodb\ActiveRecord
{
    private $sender;
    private $receiver;
    private $oldStatus = null;

    const STATUS_WAITED = 0;
    const STATUS_APPROVED = 1;
    const STATUS_CANCELED = 2;

    const SCENARIO_CREATE = 'create';

    public function __construct($sender = null, $receiver = null)
    {
        if ($sender) {
            $this->sender = $sender;
            $this->setSenderId($sender->getId());
        }

        if ($receiver) {
            $this->receiver = $receiver;
            $this->setReceiverId($receiver->getId());
        }
    }

    public static function collectionName()
    {
        return 'friend_requests';
    }

    public function attributes()
    {
        return [
            '_id',
            'senderId',
            'receiverId',
            'status'
        ];
    }

    public function rules()
    {
        return [
            [['senderId', 'receiverId'], 'required'],
            ['senderId', 'unique',
                'targetAttribute' => ['senderId', 'receiverId'],
                'message' => 'Friend request already has been sent',
                'on' => self::SCENARIO_CREATE
            ],
            ['senderId', 'unique',
                'targetAttribute' => ['receiverId' => 'senderId'],
                'message' => 'Friend request already has been sent',
                'on' => self::SCENARIO_CREATE
            ],
            ['status', 'in', 'range' => [self::STATUS_APPROVED, self::STATUS_WAITED, self::STATUS_CANCELED]],
            ['status', 'default', 'value' => self::STATUS_WAITED]
        ];
    }

    public function beforeValidate()
    {
        if ($this->isNewRecord) {
            $this->setScenario(self::SCENARIO_CREATE);
        }

        return parent::beforeValidate();
    }

    public function beforeSave($insert)
    {
        if (parent::beforeSave($insert)) {

            if ($this->isAttributeChanged('status')) {
                $this->oldStatus = is_null($this->getOldAttribute('status')) ? $this->getAttribute('status') : $this->getOldAttribute('status');
            }

            return true;
        }

        return false;
    }
    public function afterSave($insert, $changedAttributes)
    {
        parent::afterSave($insert, $changedAttributes);

        if (!is_null($this->oldStatus)) {

            if ($this->isApproved()) {
                $this->setFriend();

            } elseif ($this->isCanceled()) {
                $this->unsetFriend();
            }
        }
    }

    public function setSenderId($senderId)
    {
        if (!$senderId instanceof \MongoId) {
            $senderId = new \MongoId($senderId);
        }

        $this->senderId = $senderId;

        return $this;
    }

    public function setReceiverId($receiverId)
    {
        if (!$receiverId instanceof \MongoId) {
            $receiverId = new \MongoId($receiverId);
        }

        $this->receiverId = $receiverId;

        return $this;
    }

    public function setApproved()
    {
        $this->status = self::STATUS_APPROVED;

        return $this;
    }

    public function setCanceled()
    {
        $this->status = self::STATUS_CANCELED;

        return $this;
    }

    public function isApproved()
    {
        return $this->status === self::STATUS_APPROVED;
    }

    public function isCanceled()
    {
        return $this->status === self::STATUS_CANCELED;
    }

    public function getSender()
    {
        if (!$this->sender) {
            $this->sender = $this->hasOne(\app\models\User::className(), ['_id' => 'senderId'])->one();
        }

        return $this->sender;
    }

    public function getReceiver()
    {
        if (!$this->receiver) {
            $this->receiver = $this->hasOne(\app\models\User::className(), ['_id' => 'receiverId'])->one();
        }

        return $this->receiver;
    }

    private function setFriend()
    {
        $this->getSender()
            ->setFriend($this->getReceiver())
            ->save();

        $this->getReceiver()
            ->setFriend($this->getSender())
            ->save();
    }

    private function unsetFriend()
    {
        $this->getSender()
            ->unsetFriend($this->getReceiver())
            ->save();

        $this->getReceiver()
            ->unsetFriend($this->getSender())
            ->save();
    }

    public function getFirstErrorString()
    {
        $errors = $this->getFirstErrors();

        $errorMessage = '';

        foreach ($errors as $field => $message) {
            $errorMessage .= $message;
        }

        return $errorMessage;
    }

    public function setStatus($status)
    {
        $this->status = (int)$status;
        return $this;
    }

    public static function getAllStatuses()
    {
        return [
            static::STATUS_APPROVED,
            static::STATUS_CANCELED,
            static::STATUS_WAITED
        ];
    }

}