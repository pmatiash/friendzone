<?php

namespace app\commands;

/**
 * Filling users collection with random data
 *
 * Class FillUsersController
 * @package app\commands
 */
class FillUsersController extends \yii\console\Controller
{
    const FRIENDS_COUNT_MIN = 5;
    const FRIENDS_COUNT_MAX = 10;

    public function actionIndex($limit = 1000)
    {
        echo "Starting filling users...\n";

        $progressOld = null;
        $userIds = [];

        // fill users
        for ($i = 0; $i < $limit; $i++) {

            $this->showProgress($i, $limit, $progressOld);

            $id = \app\models\User::getCollection()->insert([
                'name' => 'user_' . $i,
                'friends' => []
            ]);

            $userIds[] = $id;
        }

        echo "Starting filling relations...\n";
        // fill relations
        $users = \app\models\User::findAll([
            '_id' => ['$in' => $userIds]
        ]);

        $progressOld = null;
        $usersCount = count($users);
        $statuses = \app\models\FriendRequest::getAllStatuses();

        foreach ($users as $key => $user) {

            $this->showProgress($key, $usersCount, $progressOld);

            // set random users as friend
            $friendKeys = array_rand($userIds, rand(self::FRIENDS_COUNT_MIN, self::FRIENDS_COUNT_MAX));

            foreach ($friendKeys as $friendKey) {

                if ($user->getId() === $users[$friendKey]->getId()) continue;

                $status = array_rand($statuses);

                (new \app\models\FriendRequest($user, $users[$friendKey]))
                    ->setStatus($statuses[$status])
                    ->save(false);
            }
        }

        echo "Successfully completed!\n";
    }

    private function showProgress($currentStep, $limit, &$prevStep)
    {
        $progress = (int)($currentStep / $limit * 100);

        if ($prevStep !== $progress) {
            $prevStep = $progress;
            echo "Progress: $progress %\n";
        }
    }

}
