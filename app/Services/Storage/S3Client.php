<?php


namespace App\Services\Storage;

/**
 * Клиент Selectel S3 для облачного хранилища
 * Class S3Client
 * @package App\Services\Storage
 */
class S3Client {

    private $client;
    private $bucket;
    public const LOG_CHANNEL = 's3-storage';

    public function __construct() {
        $this->client = new \Aws\S3\S3Client([
            'version' => 'latest',
            'region' => env('SELECTEL_DEFAULT_REGION'),
            'use_path_style_endpoint' => true,
            'credentials' => [
                'key' => env('SELECTEL_ACCESS_KEY_ID'),
                'secret' => env('SELECTEL_SECRET_ACCESS_KEY')
            ],
            'endpoint' => env('SELECTEL_URL')
        ]);
        $this->bucket = env('SELECTEL_BUCKET');
    }

    /**
     * Получение объекта из хранилища
     * @param string $path
     * @param string|null $savePath
     * @return bool|mixed
     */
    public function getObject(string $path, string $savePath = null) {
        try {
            if (!$path) {
                throw new \Exception('Object name is empty');
            }
            if (!$this->doesObjectExist($path)) {
                throw new \Exception('Object does not exist');
            }
            $data = $this->client->getObject(array_merge(
                [
                    'Bucket' => $this->bucket,
                    'Key' => $path
                ],
                $savePath ? ['SaveAs' => $savePath] : [] // сохранение по указанному пути
            ));
            \Log::channel(self::LOG_CHANNEL)->info('Getting object success: ' . $path);
            return $savePath ? true : $data->get('Body');
        } catch (\Exception $ex) {
            \Log::channel(self::LOG_CHANNEL)->error('Get object ' . $path . ' error: ' . PHP_EOL .
                $ex->getMessage() . PHP_EOL . $ex->getTraceAsString());
        }
        return false;
    }

    /**
     * Сохранение объекта в хранилище
     * @param string $path
     * @param string $data Данные файла, либо локальный путь, если указан параметр $localPath
     * @param bool $localPath
     * @return bool
     */
    public function putObject(string $path, string $data, bool $localPath = false): bool {
        try {
            if (!$path) {
                throw new \Exception('Object name is empty');
            }
            $this->client->putObject([
                'Bucket' => $this->bucket,
                'Key' => $path,
                // SourceFile - путь к файлу на диске
                // Body - данные файла
                ($localPath ? 'SourceFile' : 'Body') => $data
            ]);
            \Log::channel(self::LOG_CHANNEL)->info('Putting object success: ' . $path);
            return true;
        } catch (\Exception $ex) {
            \Log::channel(self::LOG_CHANNEL)->error('Put object ' . $path . ' error: ' . PHP_EOL .
                $ex->getMessage() . PHP_EOL . $ex->getTraceAsString());
        }
        return false;
    }

    /**
     * Получение метаданных объекта
     * @param string $path
     * @return ?\Aws\Result
     */
    public function headObject(string $path): ?\Aws\Result {
        try {
            if (!$path) {
                throw new \Exception('Object name is empty');
            }
            return $this->client->headObject([
                'Bucket' => $this->bucket,
                'Key' => $path
            ]);
        } catch (\Exception $ex) {
            \Log::channel(self::LOG_CHANNEL)->error('Head object error: ' . PHP_EOL .
                $ex->getMessage() . PHP_EOL . $ex->getTraceAsString());
            return null;
        }
    }

    /**
     * Проверка наличия объекта
     * @param string $path
     * @return bool
     */
    public function doesObjectExist(string $path): bool {
        return $this->client->doesObjectExist($this->bucket, $path);
    }

    /**
     * Получение списка объектов
     * @param string $path
     * @return \Aws\Result|null
     */
    public function listObjects(string $path): ?\Aws\Result {
        try {
            if (!$path) {
                throw new \Exception('Object name is empty');
            }
            return $this->client->listObjectsV2([
                'Bucket' => $this->bucket,
                'Prefix' => $path,
                'Delimeter' => '/'
            ]);
        } catch (\Exception $ex) {
            \Log::channel(self::LOG_CHANNEL)->error('List objects at path ' . $path . ' error: ' . PHP_EOL .
                $ex->getMessage() . PHP_EOL . $ex->getTraceAsString());
            return null;
        }
    }
}
