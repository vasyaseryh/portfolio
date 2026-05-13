<?php

namespace app\models;
use app\models\User;
use Yii;

/**
 * This is the model class for table "recall".
 *
 * @property int $id
 * @property string $title
 * @property string $text
 * @property int $school_id
 * @property int $user_id
 */
class Recall extends \yii\db\ActiveRecord
{


    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'recall';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['title', 'text', 'school_id', 'user_id'], 'required'],
            [['text'], 'string'],
            [['school_id', 'user_id'], 'integer'],
            [['title'], 'string', 'max' => 30],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'title' => 'Заголовок отзыва',
            'text' => 'Текст',
            'school_id' => 'School ID',
            'user_id' => 'User ID',
        ];
    }

    public function getUser()
    {
        return $this->hasOne(User::class, ['id' => 'user_id']);
    }

}
