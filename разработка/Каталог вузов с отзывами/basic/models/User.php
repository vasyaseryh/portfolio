<?php

namespace app\models;

use Yii;
use yii\db\ActiveRecord;
use yii\web\IdentityInterface;
use app\models\Recall;

class User extends ActiveRecord implements IdentityInterface
{
    const STATUS_ACTIVE = 1;
    const STATUS_INACTIVE = 0;

    public $password; // Для хранения пароля при регистрации

    public static function tableName()
    {
        return 'user'; // Укажите имя вашей таблицы
    }

    public function rules()
    {
        return [
            [['username', 'email', 'password'], 'required', 'message' => 'Поле должно быть заполнено'],
            [['email'], 'email', 'message' => 'email должен быть правильным'],
            ['username', 'string', 'max' => 255],
            ['password', 'string', 'min' => 6],
            ['username', 'unique', 'targetClass' => self::class, 'message' => 'Этот логин уже занят.'],
            ['email', 'unique', 'targetClass' => self::class, 'message' => 'Этот email уже занят.'],
            ['status', 'in', 'range' => [self::STATUS_ACTIVE, self::STATUS_INACTIVE]],
        ];
    }

    public function attributeLabels()
    {
        return [
            'username' => 'ФИО',
            'password' => 'Пароль',
        ];
    }

    public function beforeSave($insert)
    {
        if (parent::beforeSave($insert)) {
            if ($this->isNewRecord) {
                // Хеширование пароля перед сохранением
                $this->password_hash = Yii::$app->security->generatePasswordHash($this->password);
                $this->auth_key = Yii::$app->security->generateRandomString();
                $this->created_at = time();
                $this->updated_at = time();
            }
            return true;
        }
        return false;
    }

    // Реализация интерфейса IdentityInterface
    public static function findIdentity($id)
    {
        return static::findOne($id);
    }

    public static function findIdentityByAccessToken($token, $type = null)
    {
        // Реализуйте логику для поиска пользователя по токену
    }

    public function getId()
    {
        return $this->id;
    }

    public function getAuthKey()
    {
        return $this->auth_key;
    }

    public function validateAuthKey($authKey)
    {
        return $this->getAuthKey() === $authKey;
    }

    public function validatePassword($password)
    {
        return Yii::$app->security->validatePassword($password, $this->password_hash);
    }

    // Добавляем метод findByUsername
    public static function findByUsername($username)
    {
        return static::findOne(['username' => $username]);
    }

}