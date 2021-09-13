<?php


namespace App\Services\PolicyDataService;


use App\Models\Plain\LicensePlateInfo;
use Carbon\Carbon;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

class LicensePlateInfoService
{
    private $url;
    private $pass;
    private $user;
    private $organization;
    private $client;

    private const REPORT_TYPE = 'report_individual';

    public function __construct() {
        $this->url = env('AVTOCOD_URL');
        $this->user = env('AVTOCOD_USER');
        $this->pass = env('AVTOCOD_PASSWORD');
        $this->organization = env('AVTOCOD_ORG_DOMAIN');
        $this->client = new Client();
    }

    /**
     * Генерация токена для получения данных через сервис АВТОКОД
     *
     * @return string
     */
    public function generateToken() : string {
        $user = $this->user . '@' . $this->organization;
        $now = Carbon::now()->setTimezone('UTC');
        $stamp = $now->getTimestamp();
        $age = 3600; // 1 hour
        $passHash = base64_encode(md5($this->pass, true));
        $saltedHash = base64_encode(md5($stamp . ':' . $age . ':' . $passHash, true));
        return 'AR-REST ' . base64_encode($user . ':' . $stamp . ':' . $age . ':' . $saltedHash);
    }

    /**
     * Получение информации по гос.номеру транспортного средства
     * @param string $number
     * @return string
     */
    public function getLicensePlateInfo(string $number) : string {
        try {
            $token = $this->generateToken();

            // Generate report
            $url = $this->url . 'user/reports/' . self::REPORT_TYPE . '/_make';
            $data = [
                'queryType' => 'GRZ',
                'query' => $number
            ];
            Log::channel('avtocod')->info('Request: ' . json_encode($data) . PHP_EOL);

            $result = $this->client->post($url, [
                'json' => $data,
                'headers' => ['Authorization' => $token]
            ]);

            if (!$result) {
                Log::channel('avtocod')->error('Unable to perform request' . PHP_EOL);
                throw new \Exception('Unable to perform request');
            }
            Log::channel('avtocod')->info('Response: ' . $result->getBody() . PHP_EOL);
            $result = json_decode($result->getBody());

            if (!isset($result->data) || !count($result->data) || !isset($result->data[0]->uid)) {
                throw new \Exception('Data is empty');
            }

            $uid = $result->data[0]->uid;

            // Get generated report data
            $url = $this->url . 'user/reports/' . $uid . '?_content=true&_detailed=true';
            Log::channel('avtocod')->info('Request: ' . $url . PHP_EOL);
            $tries = 0;
            do {
                if ($tries > 0) {
                    sleep(5);
                }
                $result = $this->client->get($url, [
                    'headers' => ['Authorization' => $token]
                ]);

                if (!$result) {
                    Log::channel('avtocod')->error('Unable to perform request' . PHP_EOL);
                    throw new \Exception('Unable to perform request');
                }
                Log::channel('avtocod')->info('Response: ' . $result->getBody() . PHP_EOL);
                $result = json_decode($result->getBody());

                if (!isset($result->data) || !count($result->data)) {
                    throw new \Exception('Data is empty');
                }

                if ($result->data[0]->progress_error === 2) {
                    throw new \Exception('Report error');
                }
                $tries++;
            } while ($result->data[0]->progress_wait > 0 && $tries < 12);

            if (!isset($result->data[0]->content)) {
                throw new \Exception('Content is empty');
            }

            $info = new LicensePlateInfo($result->data[0]->content);

            return json_encode(
                (object)[
                    'status' => 'ok',
                    'response' => $info
                ]
            );
        } catch (\Exception $ex) {
            Log::channel('avtocod')->error($ex->getMessage());
            abort(404, $ex->getMessage());
        }
        return json_encode(
            (object)[
                'status' => 'error'
            ]
        );
    }
}
