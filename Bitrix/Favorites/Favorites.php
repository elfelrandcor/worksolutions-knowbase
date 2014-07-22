<?php
/**
 * @author Smotrov Dmitriy <dsxack@gmail.com>
 */

namespace WS\Bitrix\Favorites;


use CModule;
use CIBlockElement;
use CCurrencyRates;
use WS\Bitrix\ArrayStorage\ArrayStorageFactory;

class Favorites {
    private $storage;

    public function __construct($name) {
        $this->storage = ArrayStorageFactory::make($name);
    }

    public function add($id) {
        $this->storage->add($id);

        return $this;
    }

    public function delete($id) {
        $this->storage->delete($id);

        return $this;
    }

    public function get() {
        return $this->storage->get();
    }


    public function sum($iblockId, $priceType, $currency){
        if (!CModule::IncludeModule('iblock')) {
            throw new \Exception('Модуль `Инфоблоки` не установлен');
        }
        if (CModule::IncludeModule('catalog')) {
            throw new \Exception('Модуль `Каталог` не установлен');
        }

        if (!$elements = $this->get()) {
            return 0;
        }

        $sum = 0;
        $elementsResult = CIBlockElement::GetList(
            array(
                'SORT'=>'ASC',
                'NAME'=>'ASC'
            ),
            array(
                'IBLOCK_ID'=>$iblockId,
                'ID' => $elements,
                'ACTIVE'=>'Y'
            ),
            false,
            false,
            array(
                "ID",
                "NAME",
                "CATALOG_GROUP_" . $priceType,
                "DETAIL_PAGE_URL",
                "DETAIL_PICTURE",
                "PROPERTY_ARTICLE"
            )
        );
        while ($arElement = $elementsResult->GetNext()){
            $price = round($arElement["CATALOG_PRICE_" . $priceType]);
            $catalogCurrency = $arElement["CATALOG_CURRENCY_" . $priceType];

            $sum += CCurrencyRates::ConvertCurrency($price, $catalogCurrency, $currency);
        }

        return $sum;
    }

    public function formattedSum($iblockId, $priceType, $currency){
        $sum = $this->sum($iblockId, $priceType, $currency);

        return FormatCurrency($sum, $currency);
    }
} 