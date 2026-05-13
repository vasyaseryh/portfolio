<?php

namespace app\controllers;

use Yii;
use app\models\Recall;
use app\models\RecallSearch;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use app\models\School;

/**
 * RecallController implements the CRUD actions for Recall model.
 */
class RecallController extends MainController
{

    /**
     * Creates a new Recall model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return string|\yii\web\Response
     */
    public function actionAdd($id)
    {
        $model = new Recall();

        if ($this->request->isPost) {
            if ($model->load($this->request->post())) {
                $model->school_id = $id;
                $model->user_id = Yii::$app->user->identity->id;
                $model->save();
                return $this->redirect(['school/view', 'id' => $id]);
            }
        } else {
            $model->loadDefaultValues();
        }

        return $this->render('create', [
            'model' => $model, 'recall_model' => $this->findModel($id)
        ]);
    }

    protected function findModel($id)
    {
        if (($model = School::findOne(['id' => $id])) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }


}
