<?php

use Aws\Rds\RdsClient;
use Aws\Rds\Exception\RdsException;

function getInstanceByName(RdsClient $rdsClient, $instanceIdentifier)
{
    $result = [
        'found' => false,
        'instance' => null,
        'error' => 'unknown_error',
        'aws_error_code' => ''
    ];

    $response = null;

    try {
        $response = $rdsClient->describeDBInstances(array(
            'DBInstanceIdentifier' => $instanceIdentifier,
            'MaxRecords' => 100,
        ));

        $result['found'] = true;
    }
    catch (RdsException $exception) {
        if ($exception->getAwsErrorCode() === 'DBInstanceNotFound') {
            $result['error'] = 'not_found';
        }

        $result['aws_error_code'] = $exception->getAwsErrorCode();
    }
    catch (Exception $exception) {
        $result['error'] = 'unknown_error';
    }

    if ($result['found'] === true) {
        $instances = $response->get('DBInstances');

        if (count($instances) > 0) {
            $result['found'] = true;
            $result['instance'] = $instances[0];
        } else {
            $result['error'] = 'not_found';
        }
    }

    return $result;
}

function monitorInstanceStatus(RdsClient $rdsClient, $dbInstanceIdentifier, $expectedStatus)
{
    $maxTime = 1200;
    $currentTime = 0;
    $checkStepInterval = 10;

    $continueIterating = true;

    $checkInstanceResult = getInstanceByName($rdsClient, $dbInstanceIdentifier);

    while ($continueIterating === true && $currentTime < $maxTime) {
        if ($checkInstanceResult['found'] === false) {
            // instance could be completely not found - not create yet

            $currentTime += $checkStepInterval;
            yield "instance not found at $currentTime, waiting";
            sleep($checkStepInterval);

            $checkInstanceResult = getInstanceByName($rdsClient, $dbInstanceIdentifier);

            continue;
        } elseif ($checkInstanceResult['instance']['DBInstanceStatus'] != $expectedStatus) {
            // instance was found but status is not that we expect

            $currentTime += $checkStepInterval;
            yield "instance found, wrong status - '".$checkInstanceResult['instance']['DBInstanceStatus']."' at $currentTime, waiting...";
            sleep($checkStepInterval);

            $checkInstanceResult = getInstanceByName($rdsClient, $dbInstanceIdentifier);

            continue;
        } else {
            yield "instance is $expectedStatus";
            $continueIterating = false;

            break;
        }
    }

    yield "current time - $currentTime";
}

function createInstance(RdsClient $rdsClient, $newInstanceDetails)
{
    $result = [
        'created' => false,
        'status' => false,
        'error' => '',
        'aws_error_code' => '',
        'error_message' => '',
        'instance_data' => null,
    ];

    try {
        $result['instance_data'] = $rdsClient->createDBInstance($newInstanceDetails);
        $result['status'] = $result['created'] = true;
    }
    catch (RdsException $exception) {
        if ($exception->getAwsErrorCode() === 'DBInstanceAlreadyExists') {
            $result['created'] = false;
            $result['aws_error_code'] = 'DBInstanceAlreadyExists';
            $result['error_message'] = 'already_exists';
        } else {
            $result['created'] = false;
            $result['aws_error_code'] = $exception->getAwsErrorCode();
            $result['error_message'] = $exception->getMessage();
        }
    }
    catch (Exception $exception) {
        $result['created'] = false;
        $result['error_message'] = $exception->getMessage();
    }
    
    return $result;
}