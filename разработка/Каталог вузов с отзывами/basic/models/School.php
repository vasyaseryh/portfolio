<?php

namespace app\models;
use app\models\Recall;

use Yii;

/**
 * This is the model class for table "school".
 *
 * @property int $id
 * @property int $recall_id
 * @property string $title
 * @property string $text
 * @property int $directions_id
 * @property string $address
 * @property string $image_url
 */
class School extends \yii\db\ActiveRecord
{


    
    
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'school';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['title', 'text', 'address', 'direction'], 'required'],
            [['text'], 'string'],
            [['direction'], 'string'],
            [['title'], 'string', 'max' => 20],
            [['address'], 'string', 'max' => 100],
            [['image'], 'file', 'extensions' => 'png, jpg'],
        
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'title' => 'Наименование',
            'text' => 'Описание',
            'direction' => 'Направления',
            'address' => 'Адрес',
            'image' => 'Image Url',
        ];
    }

    public function getRecall()
    {
        return $this->hasMany(Recall::class, ['school_id' => 'id']);
    }

}
