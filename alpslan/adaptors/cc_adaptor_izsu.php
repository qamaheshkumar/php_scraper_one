<?php

// To collect data from izsu.com
// file name : cc_adaptor_izsu.php


class CC_ADAPTOR_IZSU {
   
    public function scrapeFiles(){
        $arrFinalValue = array();
        $timezone = date_default_timezone_get();
        date_default_timezone_set('Europe/Istanbul');
        $strScraperTime = date('Ymd H:i:s', strtotime('now'));
        $strFileContent = file_get_contents('https://www.izsu.gov.tr/SuKesintileri/suKesintileriGetirJS');
        $arrFileContent = json_decode($strFileContent, true);
        foreach ($arrFileContent as $key => $arrEachRowValue) {
            $strStartDate = $strStartTime = $strEndDate = $strEndTime = '';
            if(preg_match('#^(.*)\s+saat\s+(\d+:\d+)\s+tarihinde#', $arrEachRowValue['KesintiSuresi'], $arrDateTime)){
                if(isset($arrDateTime[1]) && trim($arrDateTime[1])){
                    $strStartDate = $strEndDate = trim($arrDateTime[1]);
                }
                if(isset($arrDateTime[2]) && trim($arrDateTime[2])){
                    $strStartTime = $strEndTime = trim($arrDateTime[2]);
                }
            }
            if(preg_match('#^(.*?)\s+saat\s+(\d+:\d+).*?(\d+:\d+).*?kesintisi#', $arrEachRowValue['KesintiSuresi'], $arrDateTime)){
                if(isset($arrDateTime[1]) && trim($arrDateTime[1])){
                    $strStartDate = $strEndDate = trim($arrDateTime[1]);
                }
                if(isset($arrDateTime[2]) && trim($arrDateTime[2])){
                    $strStartTime = trim($arrDateTime[2]);
                }
                if(isset($arrDateTime[3]) && trim($arrDateTime[3])){
                    $strEndTime = trim($arrDateTime[3]);
                }
            }
            $arrFile['SOURCE_INSTITUTION'] = 'izsu';
            $arrFile['TAGS'] = 'Cut/Shortage, Water';
            $arrFile['PROVINCE'] = "İZMİR";
            $arrFile['DISTRICT'] = "'".$arrEachRowValue['IlceAdi']."'";
            $arrFile['POSTAL_CODE'] = $arrEachRowValue['Mahalleler'];
            $arrFile['STREET'] = '';
            $arrFile['VENUE'] = '';
            $arrFile['COUNTRY'] = 'TURKEY';
            $arrFile['RAW_ADDRESS'] = $arrEachRowValue['Mahalleler'].', '.$arrEachRowValue['IlceAdi'].', İZMİR';
            $arrFile['RAW_START'] = $arrEachRowValue['KesintiSuresi'];
            $arrFile['START_DATE'] = $strStartDate;
            $arrFile['START_TIME'] = $strStartTime;
            $arrFile['FINISH_DATE'] = $strEndDate;
            $arrFile['FINISH_TIME'] = $strEndTime;
            $arrFile['DESCRIPTION'] = str_replace("\n", '', $arrEachRowValue['Aciklama']);
            $arrFile['LINK'] = 'https://www.izsu.gov.tr/tr/SuKesintileri/SuKesintisiBilgileri/22';
            $arrFile['IMAGE_PATH'] = '';
            $arrFile['IMAGE_LINK'] = '';
            $arrFile['COORDINATE'] = '';
            $arrFile['SCRAPE_DATE'] = $strScraperTime;
            if(date('Ymd', strtotime($strStartDate)) >= date('Ymd')){
                $arrFinalValue[] = $arrFile;
            }
        }
        date_default_timezone_set($timezone);
        return $arrFinalValue;
    }
    
    public function createFileInCsv($arrFinalValues) {
        if(count($arrFinalValues) == 0){
            trigger_error("\n Result In Csv Format -- Empty result returned \n ");
        }
        $timezone = date_default_timezone_get();
        date_default_timezone_set('Europe/Istanbul');
        $strScraperDate = date('dmY', strtotime('now'));
        $strScraperTime = date('His', strtotime('now'));
        $strFinalResultFilePath = "\\csvfiles\\";
        $strCsvFileName = str_replace(array('{date}', '{time}'),
                    array($strScraperDate, $strScraperTime), 'cc_adaptor_izsu_{date}{time}.csv');
        if (!file_exists($strFinalResultFilePath)) {
            mkdir($strFinalResultFilePath, 777, true);
        }
        $fileOpenAndWrite = fopen($strFinalResultFilePath.$strCsvFileName, 'w');
        print 'Saved File Path => '.$strFinalResultFilePath.$strCsvFileName."\n";
        $blnHeaderValue = FALSE;
        foreach ($arrFinalValues as $intKeyCount => $arrEachRowResult) {
            if($intKeyCount == 0 && $blnHeaderValue == FALSE){
                fputcsv($fileOpenAndWrite, array_keys($arrEachRowResult), ';');
                $blnHeaderValue = TRUE;
            }
            fputcsv($fileOpenAndWrite, $arrEachRowResult, ';');
        }
        fclose($fileOpenAndWrite);
    }
}


$objadaptorIzsu = new CC_ADAPTOR_IZSU();
$arrFinalValues = $objadaptorIzsu->scrapeFiles();
$objadaptorIzsu->createFileInCsv($arrFinalValues);

