<?php
$eventManager = \Bitrix\Main\EventManager::getInstance();
$eventManager->addEventHandler("iblock", "OnAfterIBlockElementAdd", "OnAfterIBlockElementAddHandler");
$eventManager->addEventHandler("iblock", "OnAfterIBlockElementUpdate", "OnAfterIBlockElementUpdateHandler");

include_once __DIR__ . '/functions.php';



use Bitrix\Highloadblock as HL;

$eventManager->addEventHandler("iblock", "OnBeforeIBlockElementAdd", "checkAddNews");
$eventManager->addEventHandler("iblock", "OnBeforeIBlockElementUpdate", "checkUpdateNews");
$eventManager->addEventHandler("search", "BeforeIndex", "BeforeIndexHandler");

function BeforeIndexHandler($arFields)
{
	if(!CModule::IncludeModule("iblock")) {
        return $arFields;
	}
    if($arFields["MODULE_ID"] == "iblock")
    {
		$db_props = CIBlockElement::GetProperty(
			$arFields["PARAM2"],
			$arFields["ITEM_ID"],
			array("sort" => "asc"),
			Array("CODE"=>"DATE")
		);
		if($ar_props = $db_props->Fetch()) {
			if(!empty($ar_props['VALUE'])) {
				//$arFields['LAST_MODIFIED'] = $ar_props['VALUE'];
				//$arFields['DATE_FROM'] = $ar_props['VALUE'];
			}
			//var_dump($ar_props);
			//die();
		}
    }
    return $arFields;
}

function checkAddNews(&$arFields){

    CModule::IncludeModule("main");
    CModule::IncludeModule('highloadblock');
    if($arFields['IBLOCK_ID'] == 1) {
		$arFields['PROPERTY_VALUES'][7] = $arFields['ACTIVE_FROM'];
        $detail_text = $arFields['DETAIL_TEXT'];
        $hlbl = 3;
        $hlblock = HL\HighloadBlockTable::getById($hlbl)->fetch();

        $entity = HL\HighloadBlockTable::compileEntity($hlblock);
        $entity_data_class = $entity->getDataClass();

        $rsData = $entity_data_class::getList(array(
            "select" => array("*"),
        ));

        while($arData = $rsData->Fetch()){
            $ban_word[$arData['ID']] = $arData['UF_NAME'];
            $ban_decs[$arData['ID']] = $arData;
        }

        $longStringLower = strtolower($detail_text);
        $found = false;
        foreach ($ban_word as $key => $property) {
            $propertyLower = strtolower($property);
            $decsLower = strtolower($ban_decs[$key]['UF_DECS_NAME']);
            if (strpos($longStringLower, $propertyLower) !== false) {
                if (strpos($longStringLower, $decsLower) == false) {
                    $found = $key;
                    break;
                }
            }
        }
        if ($found) {
            $ar = Array(
                "MESSAGE" => "В описании статьи id:".$arFields['ID']." найдены запрещенные слова ".$ban_decs[$found]['UF_NAME']." без пометки ".$ban_decs[$found]['UF_DECS_NAME'],
                "TAG" => "IM_CONVERT",
                "ENABLE_CLOSE" => "Y",
                "NOTIFY_TYPE" => "E",
            );
            $ID = CAdminNotify::Add($ar);
        }
    }
}

function checkUpdateNews(&$arFields){

    CModule::IncludeModule("main");
    CModule::IncludeModule('highloadblock');
    if($arFields['IBLOCK_ID'] == 1) {
		$val = array_values($arFields['PROPERTY_VALUES'][7]);
		if(empty($val[0]['VALUE'])) {
			$arFields['PROPERTY_VALUES'][7] = $arFields['ACTIVE_FROM'];
		} else {
			if(empty($arFields['ACTIVE_FROM'])) {
				$arFields['ACTIVE_FROM'] = $val[0]['VALUE'];
			}
		}
        $detail_text = $arFields['DETAIL_TEXT'];
        $hlbl = 3;
        $hlblock = HL\HighloadBlockTable::getById($hlbl)->fetch();

        $entity = HL\HighloadBlockTable::compileEntity($hlblock);
        $entity_data_class = $entity->getDataClass();

        $rsData = $entity_data_class::getList(array(
            "select" => array("*"),
        ));

        while($arData = $rsData->Fetch()){
            $ban_word[$arData['ID']] = $arData['UF_NAME'];
            $ban_decs[$arData['ID']] = $arData;
        }

        $longStringLower = mb_strtolower($detail_text, 'UTF-8');
        $found = false;
        foreach ($ban_word as $key => $property) {
            $propertyLower = mb_strtolower($property, 'UTF-8');
            $pattern = "/\b" . preg_quote($propertyLower, '/') . "\b/iu";
            $decsLower = strtolower($ban_decs[$key]['UF_DECS_NAME']);

            if (strpos($longStringLower, $propertyLower) !== false and preg_match($pattern, $longStringLower)) {
                $decsLower = str_replace('"', '', $decsLower);
                if (strpos($longStringLower, $decsLower) == false) {
                    $found = $key;
                    break;
                }
            }
        }
        if ($found) {

            $hlbl = 9;
            $hlblock = HL\HighloadBlockTable::getById($hlbl)->fetch();

            $entity = HL\HighloadBlockTable::compileEntity($hlblock);
            $entity_data_class = $entity->getDataClass();

            $rsData = $entity_data_class::getList(array(
                "select" => array("*"),
                "filter" => array("UF_ID_ELEMENT" => $arFields['ID']),
            ));
            if($arData = $rsData->Fetch()){
                $arElement = $arData;
            }

            if(empty($arElement)) {
                $link = "<a href='/bitrix/admin/iblock_element_edit.php?IBLOCK_ID=1&type=content&ID=" . $arFields['ID'] . "&lang=ru'>" . $arFields['ID'] . "</a>";
                $data = array(
                    "UF_ID_ELEMENT" => $arFields['ID'],
                    "UF_NAME_MAIN" => $ban_decs[$found]['UF_NAME'],
                    "UF_DECS_NAME" => $ban_decs[$found]['UF_DECS_NAME'],
                    "UF_ID_TEXT" => $link
                );
                $result = $entity_data_class::add($data);
            } else {
                $link = "<a href='/bitrix/admin/iblock_element_edit.php?IBLOCK_ID=1&type=content&ID=" . $arFields['ID'] . "&lang=ru'>" . $arFields['ID'] . "</a>";
                $data = array(
                    "UF_ID_ELEMENT" => $arFields['ID'],
                    "UF_NAME_MAIN" => $ban_decs[$found]['UF_NAME'],
                    "UF_DECS_NAME" => $ban_decs[$found]['UF_DECS_NAME'],
                    "UF_ID_TEXT" => $link
                );

                $result = $entity_data_class::update($arElement['ID'], $data);
            }
        } else {
            $hlbl = 9;
            $hlblock = HL\HighloadBlockTable::getById($hlbl)->fetch();

            $entity = HL\HighloadBlockTable::compileEntity($hlblock);
            $entity_data_class = $entity->getDataClass();

            $rsData = $entity_data_class::getList(array(
                "select" => array("*"),
                "filter" => array("UF_ID_ELEMENT" => $arFields['ID']),
            ));
            if($arData = $rsData->Fetch()){
                $arElement = $arData;
            }

            if(!empty($arElement)) {
               $entity_data_class::Delete($arElement['ID']);
            }
        }
    }
}

function OnAfterIBlockElementAddHandler(&$arFields)
{
	if(isset($arFields['PROPERTY_VALUES'][2]) && !empty($arFields['PROPERTY_VALUES'][2])){
		$sirname = reset($arFields['PROPERTY_VALUES'][2])['VALUE'];
		if(!empty($sirname)){
			$firstLetter = mb_ord(mb_substr($sirname, 0, 1));
	
			$purifiedFirstLetter = false;
			if($firstLetter >= 65 && $firstLetter <= 90 || $firstLetter >= 1040 && $firstLetter <= 1071){
				$purifiedFirstLetter = mb_substr($sirname, 0, 1);
			}
	
			CIBlockElement::SetPropertyValuesEx(
				$arFields['ID'], false, [
					'SIRNAME_FIRST_LETTER' => $purifiedFirstLetter,
				]
			);
		}
	}
}
function OnAfterIBlockElementUpdateHandler(&$arFields)
{
    if(isset($arFields['PROPERTY_VALUES'][2]) && !empty($arFields['PROPERTY_VALUES'][2])){
        $sirname = reset($arFields['PROPERTY_VALUES'][2])['VALUE'];
        if(!empty($sirname)){
            $firstLetter = mb_ord(mb_substr($sirname, 0, 1));

            $purifiedFirstLetter = false;
            if($firstLetter >= 65 && $firstLetter <= 90 || $firstLetter >= 1040 && $firstLetter <= 1071){
                $purifiedFirstLetter = mb_substr($sirname, 0, 1);
            }

            CIBlockElement::SetPropertyValuesEx(
                $arFields['ID'], false, [
                    'SIRNAME_FIRST_LETTER' => $purifiedFirstLetter,
                ]
            );
        }
    }
}

function var_dumper($arr){
    echo "<pre>";
    print_r($arr);
    echo "</pre>";
}

function getSectionById($iblockId = 0, $id = ''){
    \CModule::IncludeModule("iblock");

    if($iblockId) {
        $filter['IBLOCK_ID'] = $iblockId;
        $filter['ID'] = $id;
        $rsSect = \CIBlockSection::GetList([], $filter, false, ['ID', 'NAME', 'SECTION_PAGE_URL']);

        return $rsSect->GetNext();
    }

    return false;
}

function getSectionByCode($iblockId = 0, $code = ''){
    \CModule::IncludeModule("iblock");

    if($iblockId) {
        $filter['IBLOCK_ID'] = $iblockId;
        $filter['CODE'] = $code;
        $rsSect = \CIBlockSection::GetList([], $filter, false, ['ID', 'NAME', 'CNT']);

        return $rsSect->GetNext();
    }

    return false;
}

function getRandomSection($iblockId = 0, $filter = []){
    \CModule::IncludeModule("iblock");

    $result = [];

    $filter['IBLOCK_ID'] = $iblockId;
    $rsSect = \CIBlockSection::GetList([], $filter, false, ['ID', 'NAME','SECTION_PAGE_URL']);
    while ($section = $rsSect->GetNext()){
        $result[] = $section;
    }

    $randomKey = array_rand($result);

    return $result[$randomKey];
}

function getSectionList($iblockId = 0, $filter = []){
    \CModule::IncludeModule("iblock");

    $result = [];

    $filter['IBLOCK_ID'] = $iblockId;
    $rsSect = \CIBlockSection::GetList([], $filter, false, ['ID', 'NAME','SECTION_PAGE_URL']);
    while ($section = $rsSect->GetNext()){
        $result[$section['ID']] = $section;
    }

    return $result;
}