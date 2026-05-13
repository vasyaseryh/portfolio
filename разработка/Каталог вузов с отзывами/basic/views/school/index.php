<?php

use app\models\School;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\grid\ActionColumn;
use yii\grid\GridView;

/** @var yii\web\View $this */
/** @var app\models\SchoolSearch $searchModel */
/** @var yii\data\ActiveDataProvider $dataProvider */

$this->title = 'Учебные заведения';
?>
<div class="school-index">

    <h1><?= Html::encode($this->title) ?></h1>

    <?php if(Yii::$app->user->can('admin')){?>
        <p><?= Html::a('Добавить учебное заведение', ['create'], ['class' => 'btn btn-success']) ?></p>
    <?php }?>




    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel' => $searchModel,
        'emptyText' => 'Ничего не найдено',
 
        'columns' => [
            'id',
            'title',
            'direction',
            'address',
            [
                'class' => 'yii\grid\ActionColumn',
                'template' => '{view} {update} {delete}', // Определяем доступные действия
                'visibleButtons' => [
                    'update' => Yii::$app->user->can('admin'), // Проверка прав на обновление
                    'delete' => Yii::$app->user->can('admin'), // Проверка прав на удаление
                ],
            ],
            
        ],
        
    ]); ?>


</div>
