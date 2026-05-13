<?php

use yii\helpers\Html;

/** @var yii\web\View $this */
/** @var app\models\Recall $model */

$this->title = 'Добавление отзыва';
$this->params['breadcrumbs'][] = ['label' => $recall_model->title, 'url' => ['school/view', 'id' => $recall_model->id]];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="recall-create">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
