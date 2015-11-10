<?php

class m151109_165417_addIndexes extends \yii\mongodb\Migration
{
    public function up()
    {
        $this->createIndex('users', ['friends']);
        $this->createIndex('friend_requests', ['senderId', 'receiverId', 'status']);
    }

    public function down()
    {
        $this->dropIndex('users', ['friends']);
        $this->dropIndex('friend_requests', ['senderId', 'receiverId', 'status']);
    }
}
