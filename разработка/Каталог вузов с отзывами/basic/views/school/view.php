<?php

use yii\helpers\Html;
use yii\widgets\DetailView;

/** @var yii\web\View $this */
/** @var app\models\School $model */

$this->title = $model->title;
$this->params['breadcrumbs'][] = $this->title;
\yii\web\YiiAsset::register($this);
?>
<div class="school-view">

    <h1><?= Html::encode($this->title) ?></h1>

    <p>
        <?= Yii::$app->user->can('admin')?Html::a('Обновить', ['update', 'id' => $model->id], ['class' => 'btn btn-primary']):'' ?>
        <?= Yii::$app->user->can('admin')?Html::a('Удалить', ['delete', 'id' => $model->id], [
            'class' => 'btn btn-danger',
            'data' => [
                'confirm' => 'Are you sure you want to delete this item?',
                'method' => 'post',
            ],
        ]):'' ?>
         <?= Html::a('Добавить отзыв', ['recall/add', 'id' => $model->id], ['class' => 'btn btn-success']) ?>
    </p>

    <br>
    <?=  Html::img('@web/' . $model->image, ['alt' => 'Изображение', 'style' => 'width: 700px; height: auto;'])?>
    <br>
    <br>
    <br>
    <?= DetailView::widget([
        'model' => $model,
        'attributes' => [
            'text:ntext',
            'direction',
            'address',
        ],
    ]) ?>

<?php if($model->recall){?>
    <div class="recall-index">

        <h2>Отзывы</h2>
    
        <div class="recalls-list">
            <?php foreach ($model->recall as $recall): ?>
       
                <div class="recall-item">
                    <h2 class="recall-title"><?= Html::encode($recall->title) ?></h2>
                    <p class="recall-text"><?= Html::encode($recall->text) ?></p>
                    <p class="recall-text">Отзыв написал: <?= Html::encode($recall->user->username) ?></p>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
<?php }?>
</div>
