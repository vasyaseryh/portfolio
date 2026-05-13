<?php

use yii\helpers\Html;

/** @var yii\web\View $this */
/** @var app\models\School $model */

$this->title = 'Добавление учебного заведения';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="school-create">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
