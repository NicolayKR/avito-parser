<?php
require_once($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_before.php");
require_once($_SERVER["DOCUMENT_ROOT"] . "/avitoImport/vendor/autoload.php");
\Bitrix\Main\Loader::includeModule('im');
\Bitrix\Main\Loader::includeModule('blog');
\Bitrix\Main\Loader::includeModule('crm');
\Bitrix\Main\Loader::includeModule('highloadblock');
\Bitrix\Main\Loader::includeModule('tasks');
\Bitrix\Main\Loader::includeModule('iblock');
use Spatie\ArrayToXml\ArrayToXml;

//Формируем массив брендов и фото для шин.
$arFilter = Array('IBLOCK_ID'=>16);
$arSelect = Array('ID','IBLOCK_ID','NAME','PICTURE','IBLOCK_SECTION_ID');
$resParent = CIBlockSection::GetList(
    Array(),
    $arFilter,
    true,
    $arSelect,
    false
);
while($ob = $resParent->GetNext()){
    $arFields = $ob;
    $arResulParent[$arFields['ID']] = $arFields;
}
foreach($arResulParent as $index => $itemParent) {
    $arResulParent[$index]['PICTURE'] = 'https://autotapki77.ru/'.CFile::GetPath($itemParent['PICTURE']);
    $resCurr = CIBlockSection::GetNavChain(
        $itemParent['IBLOCK_ID'],
        $itemParent['IBLOCK_SECTION_ID']
    );
    if ($itemParent['IBLOCK_SECTION_ID']) {
        if ($ob = $resCurr->GetNext()) {
            ;
            $arFields = $ob['NAME'];
        }
        $arResulParent[$index]['ROOT_NAME'] = $arFields;
    }
}

//
$arFilter= Array("IBLOCK_ID" => 16, '!=PROPERTY_AVITO_IMPORT' => false);
$arSelect = Array("ID", "IBLOCK_SECTION_ID","IBLOCK_ID", "PROPERTY_AVITO_IMPORT", "PROPERTY_SHIRINA_PROFILYA","PROPERTY_VYSOTA_PROFILYA","PROPERTY_POSADOCHNYY_DIAMETR", "PROPERTY_SEZONNOST","PROPERTY_INDEKS_NAGRUZKI", "PROPERTY_INDEKS_SKOROSTI", "PROPERTY_PROIZVODITEL", "PROPERTY_MODEL_AVTOSHINY", "PROPERTY_MODEL_AVTOSHINY","NAME", "PROPERTY_SHIPY", "PROPERTY_KONSTRUKTSIYA_AVTOSHINY","PROPERTY_MINIMUM_PRICE");
$res= CIBlockElement::GetList(
    Array(),
    $arFilter,
    false,
    false,
    $arSelect
);
while($ob = $res->GetNext()){;
    $arFields = $ob;
    $arResult[$arFields['ID']] = $arFields;
}

$xml = new DOMDocument( "1.0", "UTF-8" );
$xml_root= $xml->createElement( "Ads" );
$xml_root->setAttribute( "formatVersion", 3 );
$xml_root->setAttribute( "target","Avito.ru");
foreach ($arResult as $index => $item){
    $xml_wrapper_item = $xml->createElement( "Ad" );
    $itemID = $xml->createElement( "Id", $item['ID']);
    $itemName = $xml->createElement( "Title", $item['NAME']);
    $itemCategory = $xml->createElement( "Category", 'Запчасти и аксессуары');
    $CategoryType = $xml->createElement( "TypeId", '10-048');
    $itemPhone = $xml->createElement( "ContactPhone", '8 499 384-12-14');
    $itemCity = $xml->createElement( "City", 'Москва');
    $itemType = $xml->createElement( "AdType", 'Товар приобретен на продажу');
    $itemSalesmanName = $xml->createElement( "ManagerName", 'Сергей');
    $itemAdress = $xml->createElement( "Address", 'Москва, Днепропетровский пр., 7');
    $descriptionText = 'У нас вы можете приобрести '.$item['NAME'].'. Шины абсолютно новые отличного качества напрямую от производителя! Бесплатная доставка при покупке от 4шт. Отправляем в регионы транспортной компанией. Цена указана за 1шт. По всем вопросам звоните, всегда рады помочь с выбором!';
    $itemDescription = $xml->createElement( "Description", $descriptionText);
    $itemCondition = $xml->createElement( "Condition", 'Новое');
    if (array_key_exists($item["IBLOCK_SECTION_ID"], $arResulParent)) {
        $itemBrand = $xml->createElement( "Brand", $arResulParent[$item["IBLOCK_SECTION_ID"]]['ROOT_NAME']);
        $xml_wrapper_item->appendChild($itemBrand);
        $itemImages = $xml->createElement( "Images");
        $itemImagesItem = $xml->createElement( "Image");
        $itemImagesItem->setAttribute( "url", $arResulParent[$item["IBLOCK_SECTION_ID"]]['PICTURE']);
        $itemImages->appendChild($itemImagesItem);
        $xml_wrapper_item->appendChild($itemImages);
    }
    $itemDiameter = $xml->createElement( "RimDiameter", $item["PROPERTY_POSADOCHNYY_DIAMETR_VALUE"]);
    if($item["PROPERTY_SEZONNOST_VALUE"] == 'Зимняя'){
        $season = 'Зимние';
    }
    elseif($item["PROPERTY_SEZONNOST_VALUE"] == 'Летняя'){
        $season = 'Летние';
    }
    elseif($item["PROPERTY_SEZONNOST_VALUE"] == 'Всесезонная'){
        $season = 'Всесезонные';
    }
    if($season == 'Зимние'){
        $seasonText = $season." ".mb_strtolower((string)$item["PROPERTY_SHIPY_VALUE"]);
    }
    else{
        $seasonText = $season;
    }
    $itemSeason = $xml->createElement( "TireType", $seasonText);
    $itemWidth = $xml->createElement( "TireSectionWidth", $item["PROPERTY_SHIRINA_PROFILYA_VALUE"]);
    $itemAspectRatio = $xml->createElement( "TireAspectRatio", $item["PROPERTY_VYSOTA_PROFILYA_VALUE"]);
    if($item["PROPERTY_KONSTRUKTSIYA_AVTOSHINY_VALUE"]){
        $itemRunFlat = $xml->createElement( "RunFlat", 'Да');
        $xml_wrapper_item->appendChild($itemRunFlat);
    }
    $itemModel = $xml->createElement( "Model", $item["NAME"]);
    $itemSpeedIndex = $xml->createElement( "SpeedIndex", $item["PROPERTY_INDEKS_SKOROSTI_VALUE"]);
    $itemLoadIndex = $xml->createElement( "LoadIndex", $item["PROPERTY_INDEKS_NAGRUZKI_VALUE"]);
    $itemPrice = $xml->createElement( "Price",$item["PROPERTY_MINIMUM_PRICE_VALUE"]);
    $itemListingFee = $xml->createElement( "ListingFee", 'PackageSingle');

    $xml_wrapper_item->appendChild($itemID);
    $xml_wrapper_item->appendChild($itemName);
    $xml_wrapper_item->appendChild($itemPhone);
//    $itemCategory->appendChild($CategoryType);
    $xml_wrapper_item->appendChild($itemCategory);
    $xml_wrapper_item->appendChild($CategoryType);
    $xml_wrapper_item->appendChild($itemType);
    $xml_wrapper_item->appendChild($itemSalesmanName);
    $xml_wrapper_item->appendChild($itemCity);
    $xml_wrapper_item->appendChild($itemAdress);
    $xml_wrapper_item->appendChild($itemDescription);
    $xml_wrapper_item->appendChild($itemCondition);
    $xml_wrapper_item->appendChild($itemDiameter);
    $xml_wrapper_item->appendChild($itemSeason);
    $xml_wrapper_item->appendChild($itemWidth);
    $xml_wrapper_item->appendChild($itemAspectRatio);
    $xml_wrapper_item->appendChild($itemModel);
    $xml_wrapper_item->appendChild($itemSpeedIndex);
    $xml_wrapper_item->appendChild($itemLoadIndex);
    $xml_wrapper_item->appendChild($itemPrice);
    $xml_wrapper_item->appendChild($itemListingFee);
    $xml_root->appendChild($xml_wrapper_item);
}
//Диски
//Формируем массив брендов и фото для дисков.
$arFilter = Array('IBLOCK_ID'=>19);
$arSelect = Array('ID','IBLOCK_ID','NAME','PICTURE','IBLOCK_SECTION_ID');
$resParent = CIBlockSection::GetList(
    Array(),
    $arFilter,
    true,
    $arSelect,
    false
);
while($ob = $resParent->GetNext()){
    $arFields = $ob;
    $arResulParentDisk[$arFields['ID']] = $arFields;
}
foreach($arResulParentDisk as $index => $itemParent) {
    $arResulParentDisk[$index]['PICTURE'] = 'https://autotapki77.ru/'.CFile::GetPath($itemParent['PICTURE']);
    $resCurr = CIBlockSection::GetNavChain(
        $itemParent['IBLOCK_ID'],
        $itemParent['IBLOCK_SECTION_ID']
    );
    if ($itemParent['IBLOCK_SECTION_ID']) {
        if ($ob = $resCurr->GetNext()) {
            ;
            $arFields = $ob['NAME'];
        }
        $arResulParentDisk[$index]['ROOT_NAME'] = $arFields;
    }
}
//
$arFilter= Array("IBLOCK_ID" => 19, '!=PROPERTY_AVITO_IMPORT' => false);
$arSelect = Array("ID", "IBLOCK_SECTION_ID","IBLOCK_ID","NAME", "PROPERTY_MINIMUM_PRICE", "PROPERTY_POSADOCHNYY_DIAMETR_DISKA","PROPERTY_WHEEL_TYPE","PROPERTY_SHIRINA_DISKA", "PROPERTY_COUNT_OTVERSTIY", "PROPERTY_MEZHBOLTOVOE_RASSTOYANIE", "PROPERTY_VYLET_DISKA" );
$res= CIBlockElement::GetList(
    Array(),
    $arFilter,
    false,
    false,
    $arSelect
);
while($ob = $res->GetNext()){;
    $arFields = $ob;
    $arResultDisk[$arFields['ID']] = $arFields;
}
//print_r('<pre>');
//print_r($arResultDisk);
//print_r('</pre>');
foreach ($arResultDisk as $index => $item){
    $xml_wrapper_item = $xml->createElement( "Ad" );
    $itemID = $xml->createElement( "Id", $item['ID']);
    $itemName = $xml->createElement( "Title", $item['NAME']);
    $itemCategory = $xml->createElement( "Category", 'Запчасти и аксессуары');
    $CategoryType = $xml->createElement( "TypeId", '10-046');
    $itemPhone = $xml->createElement( "ContactPhone", '8 499 384-12-14');
    $itemCity = $xml->createElement( "City", 'Москва');
    $itemType = $xml->createElement( "AdType", 'Товар приобретен на продажу');
    $itemSalesmanName = $xml->createElement( "ManagerName", 'Сергей');
    $itemAdress = $xml->createElement( "Address", 'Москва, Днепропетровский пр., 7');
    $descriptionText = 'В наличии Диск Литой Yamato Segun Minamoto-no Eritomo 19/8.5J 5x120 ET45/74.10. Диски абсолютно новые отличного качества напрямую от производителя! Бесплатная доставка при покупке от 4шт.Отправляем в регионы транспортной компанией.Цена указана за 1шт.По всем вопросам звоните, всегда рады помочь с выбором!';
    $itemDescription = $xml->createElement( "Description", $descriptionText);
    $itemCondition = $xml->createElement( "Condition", 'Новое');
    if (array_key_exists($item["IBLOCK_SECTION_ID"], $arResulParentDisk)) {
        $itemBrand = $xml->createElement( "Brand", $arResulParentDisk[$item["IBLOCK_SECTION_ID"]]['ROOT_NAME']);
        $xml_wrapper_item->appendChild($itemBrand);
        $itemImages = $xml->createElement( "Images");
        $itemImagesItem = $xml->createElement( "Image");
        $itemImagesItem->setAttribute( "url", $arResulParentDisk[$item["IBLOCK_SECTION_ID"]]['PICTURE']);
        $itemImages->appendChild($itemImagesItem);
        $xml_wrapper_item->appendChild($itemImages);
    }
    $itemModel = $xml->createElement( "Model", $item["NAME"]);
    $itemPrice = $xml->createElement( "Price",$item["PROPERTY_MINIMUM_PRICE_VALUE"]);
    $itemRimDiameter = $xml->createElement( "RimDiameter",$item["PROPERTY_POSADOCHNYY_DIAMETR_DISKA_VALUE"]);
    $itemRimType = $xml->createElement( "RimType",$item["PROPERTY_WHEEL_TYPE_VALUE"]);
    $itemRimWidth = $xml->createElement( "RimWidth",$item["PROPERTY_SHIRINA_DISKA_VALUE"]);
    $itemRimBolts = $xml->createElement( "RimBolts",$item["PROPERTY_COUNT_OTVERSTIY_VALUE"]);
    $itemRimBoltsDiameter = $xml->createElement( "RimBoltsDiameter",$item["PROPERTY_MEZHBOLTOVOE_RASSTOYANIE_VALUE"]);
    $itemRimOffset = $xml->createElement( "RimOffset",$item["PROPERTY_VYLET_DISKA_VALUE"]);
    $itemListingFee = $xml->createElement( "ListingFee", 'PackageSingle');

    $xml_wrapper_item->appendChild($itemID);
    $xml_wrapper_item->appendChild($itemName);
    $xml_wrapper_item->appendChild($itemPhone);
//    $itemCategory->appendChild($CategoryType);
    $xml_wrapper_item->appendChild($itemCategory);
    $xml_wrapper_item->appendChild($CategoryType);
    $xml_wrapper_item->appendChild($itemType);
    $xml_wrapper_item->appendChild($itemSalesmanName);
    $xml_wrapper_item->appendChild($itemCity);
    $xml_wrapper_item->appendChild($itemAdress);
    $xml_wrapper_item->appendChild($itemDescription);
    $xml_wrapper_item->appendChild($itemCondition);
    $xml_wrapper_item->appendChild($itemModel);
    $xml_wrapper_item->appendChild($itemPrice );
    $xml_wrapper_item->appendChild($itemRimDiameter);
    $xml_wrapper_item->appendChild($itemRimType);
    $xml_wrapper_item->appendChild($itemRimWidth);
    $xml_wrapper_item->appendChild($itemRimBolts);
    $xml_wrapper_item->appendChild($itemRimBoltsDiameter);
    $xml_wrapper_item->appendChild($itemRimOffset);
    $xml_wrapper_item->appendChild($itemListingFee);
    $xml_root->appendChild($xml_wrapper_item);
}
$xml->appendChild($xml_root);
$result = $xml->saveXML();
$filapath = $_SERVER["DOCUMENT_ROOT"]."/avitoImport/importXML.xml";
file_put_contents($filapath, $result);
//Под вопросом
//<DateBegin>2015-12-24</DateBegin>
//<DateBegin>2017-04-06T21:58:00+03:00</DateBegin>
//<DateEnd>2079-08-28</DateEnd>
//<DateEnd>2018-05-09T10:29:00+03:00</DateEnd>


require_once($_SERVER["DOCUMENT_ROOT"] . BX_ROOT . "/modules/main/include/epilog_after.php");