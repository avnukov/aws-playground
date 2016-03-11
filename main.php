<?php
require_once('vendor/autoload.php');

require_once('classes.php');
require_once('functions.php');
require_once('config.php');

ini_set('error_reporting', E_ALL);
ini_set('display_errors', true);
ini_set('max_execution_time', 1500);

use Aws\Rds\RdsClient;

// define vars
$stages = [
    'list-instances' => true,
    'create-new-instance' => false,
    'check-new-instance' => false,
];

$defaultRegion = 'us-east-1';
$defaultZone = 'us-east-1a';

$newInstanceDetails = array(
    'DBName' => 'web02',
    // DBInstanceIdentifier is required
    'DBInstanceIdentifier' => 'testapp01-maria03-3',
    'AllocatedStorage' => 5,
    // DBInstanceClass is required
    'DBInstanceClass' => 'db.t2.micro',
    // Engine is required
    'Engine' => 'mariadb',
    'MasterUsername' => 'coroot',
    'MasterUserPassword' => 'EightCharactersPassword123',
    'DBSecurityGroups' => [],
    'VpcSecurityGroupIds' => [], //!todo need to add your security group
    'AvailabilityZone' => $defaultZone, // 'eu-central-1a',
    'DBSubnetGroupName' => 'default',
    // 'PreferredMaintenanceWindow' => 'string',
    // 'DBParameterGroupName' => 'string',
    'BackupRetentionPeriod' => 0,
    // 'PreferredBackupWindow' => 'string',
    'Port' => 3306,
    'MultiAZ' => false,
    // 'EngineVersion' => 'string',
    'AutoMinorVersionUpgrade' => false,
    // 'LicenseModel' => 'string',
    // 'Iops' => integer,
    // 'OptionGroupName' => 'string',
    // 'CharacterSetName' => 'string',
    'PubliclyAccessible' => true,
    'Tags' => [],
    // 'DBClusterIdentifier' => 'string',
    'StorageType' => 'standard',
    // 'TdeCredentialArn' => 'string',
    // 'TdeCredentialPassword' => 'string',
    'StorageEncrypted' => false,
    // 'KmsKeyId' => 'string',
    'CopyTagsToSnapshot' => false,
);


$dummyProfiler = new dummyProfiler();

$rdsClient = new RdsClient([
    'version' => 'latest',
    'region'  => $defaultRegion,
    'credentials' => [
        'key'    => $config['aws']['key'],
        'secret' => $config['aws']['secret'],
    ],
]);

echo '<pre>';

if (isset($stages['list-instances']) && $stages['list-instances'] === true) {
    $dummyProfiler->addOrUpdate('retrieve_instances_list');
    $result = $rdsClient->describeDBInstances(array(
        // 'DBInstanceIdentifier' => 'string',
        'MaxRecords' => 100,
    ));
    $instances = $result->get('DBInstances');
    print_r($instances);

    $dummyProfiler->addOrUpdate('retrieve_instances_list');
}

if (isset($stages['list-instances']) && $stages['list-instances'] === true) {
    $dummyProfiler->addOrUpdate('retrieve_instances_by_identifier');

    $instanceDetails = getInstanceByName($rdsClient, 'testapp01-maria02');
    print_r($instanceDetails);

    // print_r($result->get('DBInstances'));
    $dummyProfiler->addOrUpdate('retrieve_instances_by_identifier');
}

if (isset($stages['create-new-instance']) && $stages['create-new-instance'] === true) {
    $dummyProfiler->addOrUpdate('create_new_instance');

    $newInstanceStatus = createInstance($rdsClient, $newInstanceDetails);
    var_dump($newInstanceStatus);

    $dummyProfiler->addOrUpdate('create_new_instance');

    if ($newInstanceStatus['status'] === true && isset($stages['check-new-instance']) && $stages['check-new-instance'] === true) {

        echo "=========================================\n";
        foreach (monitorInstanceStatus($rdsClient, $newInstanceDetails['DBInstanceIdentifier'], 'available') as $status) {
            echo $status . "\n";
        }
        echo "=========================================\n";
    }
}

print_r($dummyProfiler->getElements());

