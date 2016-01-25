<?php
/**
 * Created by PhpStorm.
 * User: maksim
 * Date: 25.01.16
 * Time: 22:51
 */

namespace app\commands;

use Yii;
use yii\console\Controller;

class VerticaController extends Controller
{
    /**
     * Сколько ждать секунд передследующей попыткой загрузить данные в БД
     */
    const WAIT = 1;



    /**
     * ActionIndex
     * @param string $file_name
     */
    public function actionIndex( $file_name = '' )
    {
        $user = get_current_user();
        $this->log( "Обработка файла $file_name пользователем $user" );

        $this->checkFile( $file_name );

        $result = false;

        for( $i = 1; $i <= 3; $i++ ){

            $this->log( "Попытка $i загрузить данные в БД" );

            $result = $this->loadData( $file_name );

            if( $result ){
                break;
            }else{
                sleep( $this::WAIT );
            }
        }

        $result_message = $result ? "Файл $file_name успешно обработан" : "Ошибка. Файл $file_name не был загружен";

        $this->log( $result_message );
    }


    /**
     * Загрузить данные из указанного файла в базу
     * @param string $file_name
     * @return bool
     * @throws \Exception
     * @throws \yii\db\Exception
     */
    function loadData( $file_name ){

        $this->log( "Загрузка данных в БД из файла $file_name" );


        /**
         * @var \yii\db\Connection $vertica
         */
        $vertica = Yii::$app->vertica;
        $transaction = $vertica->beginTransaction();

        $sql = "COPY vertica FROM $file_name PARSER fcsvparser()";

        try {
            $vertica->createCommand( $sql )->execute();
            $transaction->commit();
            $result = true;
        } catch( \Exception $e ) {

            $transaction->rollBack();
            $this->log( sprintf("Ошибка обращения к БД. Файл %s:%s, сообщение: %s, стек вызова: %s",
                    $e->getFile(),
                    $e->getLine(),
                    $e->getMessage(),
                    $e->getTraceAsString()
                )
            );
            throw $e;
        }


        if( $result ){
            $this->log( "Данные успешно загружены" );
        }else{
            $this->log( "Ошибка загрузки данных в БД" );
        }

        return $result;
    }


    /**
     * Проверить имя файла переданное как параметр команде
     * @param string $file_name
     */
    function checkFile( $file_name ){
        $this->log( "Проверка указанного файла" );

        //Пустая строка
        if( strlen( $file_name ) == 0 ){
            die ( "\nОшибка: Не указано имя файла. Укажите имя файла, как параметр команды (через пробел). " .
                "Например, /var/www/vertica/data/load.csv\n\n" );
        }

        //Нет такого файла
        if( ! file_exists( $file_name ) ){
            die( "\nОшибка: Указанный файл не существует. Укажите корректное имя файла, как параметр команды (через пробел). " .
                "Например, /var/www/vertica/data/load.csv\n\n" );
        }

        //Нет доступа на чтение к файлу
        if( ! is_readable( $file_name ) ){
            $user = get_current_user();
            die( "\nОшибка: Указанный файл не доступен для чтения пользователю $user. " .
                "Установите корректное права на указанный файл $file_name\n\n" );
        }

        $this->log( "Файл $file_name подходит" );
    }


    /**
     * Вывести сообщение о работе скрипта
     * @param $message
     */
    function log( $message ){
        echo "\n$message\n";
    }
}