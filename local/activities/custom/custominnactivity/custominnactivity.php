<?php if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

use App\Classes\Dadata;
use Bitrix\Bizproc\Activity\BaseActivity;
use Bitrix\Bizproc\FieldType;
use Bitrix\Main\ErrorCollection;
use Bitrix\Main\Localization\Loc;
use Bitrix\Bizproc\Activity\PropertiesDialog;
use Dadata\DadataClient;

class CBPCustomInnActivity extends BaseActivity
{
    
    /**
     * @see parent::_construct()
     * @param $name string Activity name
     */
    public function __construct($name)
    {
        parent::__construct($name);

        $this->arProperties = [
            'Inn' => '',

            // return
            'Text' => null,
        ];

        $this->SetPropertiesTypes([
            'Text' => ['Type' => FieldType::STRING],
        ]);
    }

    /**
     * Return activity file path
     * @return string
     */
    protected static function getFileName(): string
    {
        return __FILE__;
    }

    /**
     * @return ErrorCollection
     */
    protected function internalExecute(): ErrorCollection
    {
        $errors = parent::internalExecute();

        $token = "c3e1c953415de33555e4324f96d90f4b827a6256";
        $secret = "f8ee060dd7a96d20200cc4992b5ddda2934b3dc4";

        $companyName = 'Компания не найдена!';


        // Вариант подключения DADATA через composer - не работает composer на хостинге от OTUS
        /*try {
            $dadata = new DadataClient($token, $secret);
            $response = $dadata->findById("party", $this->Inn);

            if (!empty($response) && is_array($response)) {

                $firstItem = current($response);

                if ($firstItem && isset($firstItem['value'])) {
                    $companyName = $firstItem['value'];
                }

            }
        } catch (\Exception $e) {
            $this->log('Dadata API Error: ' . $e->getMessage());
        }*/

        try {
            $dadata = new Dadata($token, $secret);
            $dadata->init();

            $fields = array("query" => $this->Inn, "count" => 1);
            $response = $dadata->findById("party", $fields);

            if (!empty($response['suggestions']) && is_array($response['suggestions'])) {

                $firstItem = current($response['suggestions']);

                if ($firstItem && isset($firstItem['value'])) {
                    $companyName = $firstItem['value'];
                }

            }
        } catch (\Exception $e) {
            $this->log('Dadata API Error: ' . $e->getMessage());
        }

        $this->preparedProperties['Text'] = $companyName;
        $this->log('Результат поиска: ' . $this->preparedProperties['Text']);

        return $errors;
    }

    /**
     * @param PropertiesDialog|null $dialog
     * @return array[]
     */
    public static function getPropertiesDialogMap(?PropertiesDialog $dialog = null): array
    {
        $map = [
            'Inn' => [
                'Name' => 'ИНН компании',
                'FieldName' => 'inn',
                'Type' => FieldType::STRING,
                'Required' => true,
                'Options' => [],
            ],
        ];
        return $map;
    }




}