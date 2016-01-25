<?php
namespace app\models;

use \yii\data\ActiveDataProvider;
use \yii\vertica\ActiveRecord;

class Vertica extends ActiveRecord
{
    public $email;
    public $password;
    public $username;

    public static function tableName()
    {
        return 'vertica';
    }

    /**
     * @return array validation rules for model attributes.
     */
    public function rules()
    {
        return [
            [ ['username', 'password', 'email'], 'safe' ],
            [ ['username', 'password', 'email'], 'required' ]
        ];
    }



    public function search($params)
    {
        $query = Vertica::find();
        $dataProvider = new ActiveDataProvider([
            'query' => $query,
        ]);

        if (!($this->load($params) && $this->validate())) {
            return $dataProvider;
        }

        $query->andFilterWhere(['like', 'username', $this->username]);
        $query->andFilterWhere(['like', 'email', $this->email]);

        return $dataProvider;
    }
}