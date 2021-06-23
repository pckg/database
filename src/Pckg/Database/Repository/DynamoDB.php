<?php

namespace Pckg\Database\Repository;

use Aws\Credentials\Credentials;
use Aws\DynamoDb\Marshaler;
use Aws\Resource\Aws;
use Aws\S3\S3Client;
use Aws\Sdk;
use GuzzleHttp\Promise\Create;
use Pckg\Database\Entity;
use Pckg\Database\Helper\Cache;
use Pckg\Database\Query;
use Pckg\Database\Record;
use Pckg\Database\Repository;

/**
 * Class DynamoDB
 *
 * @package Pckg\Database\Repository
 */
class DynamoDB extends Custom
{
    use Failable;

    protected $localCache = [];

    /**
     * @var array|mixed
     */
    protected $config = [];

    public function __construct($config)
    {
        $this->config = $config;
    }

    protected function getDynamoDB()
    {
        $config = only($this->config, [
                'version',
                'region',
            ]) + [
                'credentials' => function () {
                    return Create::promiseFor(
                        new Credentials($this->config['api_key'], $this->config['api_secret'])
                    );
                }
            ];

        $sdk = new Sdk($config);

        return [$sdk->createDynamoDb(), new Marshaler()];
    }

    /**
     * @param Entity $entity
     *
     * @return null
     */
    public function one(Entity $entity)
    {
        $query = $entity->getQuery();

        $keyData = [];
        foreach ($query->getWhere()->getChildren() as $where) {
            if ($where === '`id` = ?') {
                $keyData['id'] = $query->getBinds('where')[0];
            } else if ($where === '`uuid` = ?') {
                $keyData['uuid'] = $query->getBinds('where')[0];
            } else {
                throw new \Exception('Invalid DynamoDB condition');
            }
        }

        if (!$keyData) {
            throw new \Exception('Key data is requested');
        }

        [$dynamoDb, $marshaler] = $this->getDynamoDB();

        $marshalJsonKey = $marshaler->marshalJson(json_encode($keyData));

        // get existing item
        $params = [
            'TableName' => $entity->getTable(),
            'Key' => $marshalJsonKey,
        ];
        $item = $dynamoDb->getItem($params);

        $realItem = collect($item['Item'])->map(function ($item) {
            return $item[array_keys($item)[0]];
        });

        return $entity->getRecord($realItem->all());
    }

    /**
     * @param Entity $entity
     *
     * @return array
     */
    public function all(Entity $entity)
    {
        throw new \Exception('Fetching all items is not supported');

        [$dynamoDb, $marshaler] = $this->getDynamoDB();

        $keyData = [
            'uuid' => '4f22a329-0744-4d4e-b837-5850533830e5',
        ];
        $data = $keyData + [
                'data' => 'this is data'
            ];
        /*
        // this works :)
        $marshalJsonItem = $marshaler->marshalJson(json_encode($data));

        $params = [
            'TableName' => $entity->getTable(),
            'Item' => $marshalJsonItem,
        ];

        try {
            $result = $dynamoDb->putItem($params);
            ddd('added?', $result, $params);

        } catch (DynamoDbException $e) {
            echo "Unable to add item:\n";
            echo $e->getMessage() . "\n";
            ddd('retry?', $params);
        }*/
    }
}
