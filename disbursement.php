<?php
function parseQueryResult() {
    $lines = file('./disbursement_query_result.csv');
    $applicationMap = [];
    $i = 0;
    foreach($lines as $line) {
        $i++;
        if($i == 1) continue;

        $line = trim($line);
        if(empty($line)) continue;
        list($id, $applicationId, $bankCode, $changeType, $channelFee) = explode(',', $line);
        $id = trim($id, ' "');
        $applicationId = trim($applicationId, ' "');
        $bankCode = trim($bankCode, ' "');
        $changeType = trim($changeType, ' "');
        $channelFee = trim($channelFee, ' "');
    
        $applicationMap[$applicationId] = [
            'id' => $id,
            'applicationId' => $applicationId,
            'bankCode' => $bankCode,
            'changeType' => $changeType,
            'channelFee' => $channelFee,    
        ];
    }
    return $applicationMap;
}
$qrMap = parseQueryResult();
$disbursementMap = parseBankDisbursement();
// var_dump(compareApplicationId($qrMap, $disbursementMap));die();
generateUpdateSQL($qrMap, $disbursementMap);



function compareApplicationId($qrMap, $disbursementMap) {
    $result = ['onlyQr' => [], 'onlyDis' => []];
    foreach($qrMap as $qrAppId => $qrItem) {
        foreach($disbursementMap as $disAppId => $disItem) {
            if($qrAppId == $disAppId) {
               unset($qrMap[$qrAppId]);
               unset($disbursementMap[$disAppId]);
            } 
        }
    }
    $result['onlyQr'] = $qrMap;
    $result['onlyDis'] = $disbursementMap;
    return $result;
}

function generateUpdateSQL($qrMap, $disbursementMap) {
    foreach($qrMap as $qrAppId => $qrItem) {
        foreach($disbursementMap as $disAppId => $disItem) {
            if($qrAppId == $disAppId) {
                //file_put_contents('/tmp/update_disbursement.sql', 
                //    "update sub_account_cash_turnover set bank_code='{$disItem['bankCode']}', channel_fee={$disItem['channelFee']} where id={$qrItem['id']}\n", FILE_APPEND);
                if($qrItem['bankCode'] == '' || 
                $qrItem['channelFee'] != $disItem['channelFee']) {
                    file_put_contents('/tmp/update_disbursement.sql', 
                    "update sub_account_cash_turnover set channel_fee={$disItem['channelFee']} where id={$qrItem['id']};\n", FILE_APPEND);
                    file_put_contents('/tmp/update_disbursement.sql', 
                    "update sub_account_cash_turnover set bank_code='{$disItem['bankCode']}' where application_id={$qrItem['applicationId']};\n", FILE_APPEND);
                    file_put_contents('/tmp/update_disbursement.sql', 
                    "update sub_account_pending_turnover set bank_code='{$disItem['bankCode']}', channel_fee={$disItem['channelFee']} where application_id={$qrItem['applicationId']};\n", FILE_APPEND);
                }
            }
        }
    }
}



function listFiles($dir) {
    $handler = opendir($dir);
    while (($filename = readdir($handler)) !== false) {
        if ($filename != "." && $filename != "..") {
                $files[] = $filename ;
        }
    }
    closedir($handler);
    return $files;
}

function parseBankDisbursement() {
    $files = listFiles('./out');
    $applicationMap = [];
    foreach($files as $file) {
        list($foo, $bankCode) = explode('_', $file);
        $lines = file("./out/{$file}");
        foreach($lines as $line) {
            $line = trim($line);
            list($applicationId, $channelFee) = explode("\t", $line);
            $applicationId = trim($applicationId, ' "');
            $channelFee = trim($channelFee, ' "');
        
            $applicationMap[$applicationId] = [
                'applicationId' => $applicationId,
                'bankCode' => $bankCode,
                'channelFee' => $channelFee,    
            ];
        }
    }
    return $applicationMap;
}