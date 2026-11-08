<?php

namespace App\Services;

use App\Models\BiometricDevice;
use Exception;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;

class BiometricDeviceService
{
    /**
     * Create a ZKTeco instance with a short timeout (3s) to avoid Apache request hangs
     */
    protected function makeZk(string $ip, int $port = 4370)
    {
        if (!class_exists('\Fsuuaas\Zkteco\Lib\ZKTeco')) {
            throw new Exception("ZKTeco library (fsuuaas/zkteco) is not installed.");
        }
        $zkClass = '\Fsuuaas\Zkteco\Lib\ZKTeco';
        $zk = new $zkClass($ip, $port);
        // Override default 60s timeout with 3s for web requests
        if (isset($zk->_zkclient) && is_resource($zk->_zkclient)) {
            socket_set_option($zk->_zkclient, SOL_SOCKET, SO_RCVTIMEO, ['sec' => 3, 'usec' => 0]);
            socket_set_option($zk->_zkclient, SOL_SOCKET, SO_SNDTIMEO, ['sec' => 3, 'usec' => 0]);
        }
        return $zk;
    }
    /**
     * Check if the device is ZKTeco based on port, model, or network response
     */
    public function isZkDevice(BiometricDevice $device): bool
    {
        $port = intval($device->port);
        $model = strtolower($device->model ?? '');
        $name = strtolower($device->name ?? '');

        if ($port === 4370) {
            return true;
        }

        if (str_contains($model, 'zk') || str_contains($model, 'k50') || str_contains($model, 'k40') || 
            str_contains($model, 'mb') || str_contains($model, 'uface') || str_contains($model, 'in01') ||
            str_contains($name, 'zk') || str_contains($name, 'k50')) {
            return true;
        }

        return false;
    }

    protected function getClient(BiometricDevice $device)
    {
        try {
            $password = Crypt::decryptString($device->password);
        } catch (Exception $e) {
            $password = $device->password;
        }

        return new Client([
            'base_uri' => "http://{$device->ip_address}:{$device->port}/",
            'timeout' => 10,
            'auth' => [$device->username, $password, 'digest'],
            'headers' => [
                'Content-Type' => 'application/xml',
                'Accept' => 'application/xml',
            ],
        ]);
    }

    /**
     * Connect (Test Connection)
     */
    public function connect(BiometricDevice $device): bool
    {
        if ($this->isZkDevice($device)) {
            try {
                $zk = $this->makeZk($device->ip_address, intval($device->port ?? 4370));
                if ($zk->connect()) {
                    $zk->disconnect();
                    return true;
                }
            } catch (Exception $e) {
                Log::error("ZKTeco Connect Failed for {$device->ip_address}: " . $e->getMessage());
            }
            return false;
        }

        try {
            $client = $this->getClient($device);
            $response = $client->get('ISAPI/System/deviceInfo');

            return $response->getStatusCode() === 200;
        } catch (Exception $e) {
            Log::error("Hikvision Connect Failed for {$device->ip_address}: " . $e->getMessage());

            return false;
        }
    }

    /**
     * Test Connection for Controller
     */
    public function testConnection(BiometricDevice $device): array
    {
        try {
            if ($this->isZkDevice($device)) {
                if (!extension_loaded('sockets')) {
                    return ['success' => false, 'message' => 'PHP sockets extension is not enabled. Please enable extension=sockets in php.ini and restart Apache.'];
                }

                $zk = $this->makeZk($device->ip_address, intval($device->port ?? 4370));
                if ($zk->connect()) {
                    $serialRaw = $zk->serialNumber();
                    $nameRaw = $zk->deviceName();
                    $zk->disconnect();

                    $serial = trim(str_replace(['~SerialNumber=', "\0"], '', is_string($serialRaw) ? $serialRaw : ''));
                    $name = trim(str_replace(['~DeviceName=', "\0"], '', is_string($nameRaw) ? $nameRaw : ''));

                    $msg = 'Connected Successfully to ZKTeco ' . ($name ?: ($device->model ?: 'Machine')) . ($serial ? " (S/N: {$serial})" : '');
                    return ['success' => true, 'message' => $msg];
                }

                return [
                    'success' => false,
                    'message' => "ZKTeco Connection Failed — Device not responding at {$device->ip_address}:{$device->port}. Check: 1) Device is ON and connected to network. 2) PC and device are on same network (192.168.1.x). 3) Port 4370 is not blocked by Windows Firewall."
                ];
            }

            if ($this->connect($device)) {
                return ['success' => true, 'message' => 'Connection Successful (Hikvision ISAPI)'];
            }

            return ['success' => false, 'message' => 'Connection Failed (Check Credentials/Network)'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Connection Error: ' . $e->getMessage()];
        }
    }

    /**
     * Get Attendance Logs
     */
    public function getAttendanceLogs(BiometricDevice $device): array
    {
        if ($this->isZkDevice($device)) {
            try {
                $zk = $this->makeZk($device->ip_address, intval($device->port ?? 4370));
                if ($zk->connect()) {
                    $attendance = $zk->getAttendance();
                    $zk->disconnect();

                    $logs = [];
                    foreach ($attendance as $att) {
                        $logs[] = [
                            'id' => (string) ($att['id'] ?? $att['uid'] ?? '0'),
                            'timestamp' => (string) ($att['timestamp'] ?? ''),
                            'state' => intval($att['state'] ?? 1),
                            'uid' => (string) ($att['uid'] ?? 0),
                        ];
                    }

                    Log::info("ZKTeco Log Pull Success for {$device->ip_address}: " . count($logs) . " logs.");
                    return $logs;
                }
            } catch (Exception $e) {
                Log::error("ZKTeco Log Pull Failed for {$device->ip_address}: " . $e->getMessage());
            }
        }

        try {
            $logs = $this->getAttendanceLogsJson($device);
            if (!empty($logs)) {
                return $logs;
            }
            return $this->getAttendanceLogsXml($device);
        } catch (Exception $e) {
            Log::error('Hikvision Log Pull Failed: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Sync Device Time
     */
    public function syncTime(BiometricDevice $device): bool
    {
        if ($this->isZkDevice($device)) {
            try {
                $zk = $this->makeZk($device->ip_address, intval($device->port ?? 4370));
                if ($zk->connect()) {
                    $zk->setTime(date('Y-m-d H:i:s'));
                    $zk->disconnect();
                    return true;
                }
            } catch (Exception $e) {
                Log::error("ZKTeco Time Sync Failed for {$device->ip_address}: " . $e->getMessage());
                return false;
            }
        }

        try {
            $client = $this->getClient($device);
            $time = date('Y-m-d\TH:i:s');
            $xml = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>
                    <Time>
                        <timeMode>manual</timeMode>
                        <localTime>$time</localTime>
                        <timeZone>CST-5:00:00</timeZone>
                    </Time>";

            $client->put('ISAPI/System/time', ['body' => $xml]);

            return true;
        } catch (Exception $e) {
            Log::error('Hikvision Time Sync Failed: ' . $e->getMessage());

            return false;
        }
    }

    /**
     * Add/Update user on device
     */
    public function addUserToDevice(BiometricDevice $device, string $userId, string $name, string $password = ''): bool
    {
        if ($this->isZkDevice($device)) {
            try {
                $zk = $this->makeZk($device->ip_address, intval($device->port ?? 4370));
                if ($zk->connect()) {
                    $uid = intval($userId) > 0 ? intval($userId) : 1;
                    $zk->setUser($uid, $userId, $name, $password, 0, 0);
                    $zk->disconnect();
                    Log::info("User {$userId} ({$name}) pushed to ZKTeco device {$device->ip_address}");
                    return true;
                }
            } catch (Exception $e) {
                Log::error("ZKTeco Add User Failed for {$userId}: " . $e->getMessage());
                return false;
            }
        }

        try {
            $client = new Client([
                'base_uri' => "http://{$device->ip_address}:{$device->port}/",
                'timeout' => 10,
                'auth' => [$device->username, $this->getDecryptedPassword($device), 'digest'],
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ],
            ]);

            $userData = [
                'UserInfo' => [
                    'employeeNo' => $userId,
                    'name' => $name,
                    'userType' => 'normal',
                    'Valid' => [
                        'enable' => true,
                        'beginTime' => '2020-01-01T00:00:00',
                        'endTime' => '2037-12-31T23:59:59',
                    ],
                    'doorRight' => '1',
                    'RightPlan' => [
                        [
                            'doorNo' => 1,
                            'planTemplateNo' => '1',
                        ],
                    ],
                ],
            ];

            $response = $client->post('ISAPI/AccessControl/UserInfo/Record?format=json', [
                'json' => $userData,
                'http_errors' => false,
            ]);

            $statusCode = $response->getStatusCode();
            $body = (string) $response->getBody();

            if ($statusCode === 409 || $statusCode === 400 || strpos($body, 'userExisted') !== false || strpos($body, 'exist') !== false) {
                $response = $client->put('ISAPI/AccessControl/UserInfo/Modify?format=json', [
                    'json' => $userData,
                    'http_errors' => false,
                ]);
                $statusCode = $response->getStatusCode();
                $body = (string) $response->getBody();
            }

            if ($statusCode === 200 || $statusCode === 201) {
                return true;
            }

            return $this->addUserToDeviceXml($device, $userId, $name, $password);

        } catch (Exception $e) {
            return $this->addUserToDeviceXml($device, $userId, $name, $password);
        }
    }

    /**
     * Get Attendance Logs via Hikvision JSON API
     */
    protected function getAttendanceLogsJson(BiometricDevice $device): array
    {
        try {
            $client = new Client([
                'base_uri' => "http://{$device->ip_address}:{$device->port}/",
                'timeout' => 30,
                'auth' => [$device->username, $this->getDecryptedPassword($device), 'digest'],
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ],
            ]);

            $allLogs = [];
            $searchPosition = 0;
            $hasMore = true;

            $startTime = date('Y-m-d\TH:i:s', strtotime('-30 days'));
            $endTime = date('Y-m-d\TH:i:s');

            while ($hasMore) {
                $searchData = [
                    'AcsEventCond' => [
                        'searchID' => '1',
                        'searchResultPosition' => $searchPosition,
                        'maxResults' => 100,
                        'major' => 0,
                        'minor' => 0,
                        'startTime' => $startTime,
                        'endTime' => $endTime,
                    ],
                ];

                $response = $client->post('ISAPI/AccessControl/AcsEvent?format=json', [
                    'json' => $searchData,
                    'http_errors' => false,
                ]);

                $statusCode = $response->getStatusCode();
                $body = (string) $response->getBody();

                if ($statusCode !== 200) {
                    $hasMore = false;
                    continue;
                }

                $data = json_decode($body, true);
                $batchCount = 0;

                if (isset($data['AcsEvent']['InfoList'])) {
                    foreach ($data['AcsEvent']['InfoList'] as $info) {
                        $allLogs[] = [
                            'id' => (string) ($info['employeeNoString'] ?? $info['employeeNo'] ?? '0'),
                            'timestamp' => (string) ($info['time'] ?? ''),
                            'state' => 1,
                            'uid' => (string) ($info['serialNo'] ?? 0),
                        ];
                        $batchCount++;
                    }
                }

                if ($batchCount < 100) {
                    $hasMore = false;
                } else {
                    $searchPosition += 100;
                }

                if ($searchPosition > 10000) {
                    $hasMore = false;
                }
            }

            return $allLogs;

        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * Get Attendance Logs via Hikvision XML API
     */
    protected function getAttendanceLogsXml(BiometricDevice $device): array
    {
        try {
            $client = $this->getClient($device);

            $allLogs = [];
            $searchPosition = 0;
            $hasMore = true;

            $startTime = date('Y-m-d\TH:i:s', strtotime('-30 days'));
            $endTime = date('Y-m-d\TH:i:s');

            while ($hasMore) {
                $xmlQuery = '<?xml version="1.0" encoding="utf-8"?>
                            <AcsEventCond version="2.0">
                                <searchID>1</searchID>
                                <searchResultPosition>'.$searchPosition.'</searchResultPosition>
                                <maxResults>100</maxResults>
                                <major>0</major>
                                <minor>0</minor>
                                <startTime>'.$startTime.'</startTime>
                                <endTime>'.$endTime.'</endTime>
                            </AcsEventCond>';

                $response = $client->post('ISAPI/AccessControl/AcsEvent', [
                    'body' => $xmlQuery,
                    'http_errors' => false,
                ]);

                $statusCode = $response->getStatusCode();
                $xmlString = (string) $response->getBody();

                if ($statusCode !== 200) {
                    $hasMore = false;
                    continue;
                }

                $xml = simplexml_load_string($xmlString);
                if ($xml === false) {
                    $hasMore = false;
                    continue;
                }

                $batchCount = 0;

                if (isset($xml->InfoList) && isset($xml->InfoList->Info)) {
                    foreach ($xml->InfoList->Info as $info) {
                        $allLogs[] = [
                            'id' => (string) ($info->employeeNoString ?? $info->employeeNo ?? '0'),
                            'timestamp' => (string) ($info->time->time ?? $info->time),
                            'state' => 1,
                            'uid' => (string) ($info->serialNo ?? 0),
                        ];
                        $batchCount++;
                    }
                }

                if ($batchCount < 100) {
                    $hasMore = false;
                } else {
                    $searchPosition += 100;
                }

                if ($searchPosition > 10000) {
                    $hasMore = false;
                }
            }

            return $allLogs;

        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * Add/Update user on device via Hikvision XML
     */
    protected function addUserToDeviceXml(BiometricDevice $device, string $userId, string $name, string $password = ''): bool
    {
        try {
            $client = $this->getClient($device);

            $xmlBody = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>
            <UserInfo version=\"2.0\" xmlns=\"http://www.hikvision.com/ver20/XMLSchema\">
                <employeeNo>{$userId}</employeeNo>
                <name>{$name}</name>
                <userType>normal</userType>
                <closeDelayEnabled>false</closeDelayEnabled>
                <Valid>
                    <enable>true</enable>
                    <beginTime>2020-01-01T00:00:00</beginTime>
                    <endTime>2037-12-31T23:59:59</endTime>
                    <timeType>local</timeType>
                </Valid>
                <doorRight>1</doorRight>
                <RightPlan>
                    <doorNo>1</doorNo>
                    <planTemplateNo>1</planTemplateNo>
                </RightPlan>
            </UserInfo>";

            try {
                $response = $client->post('ISAPI/AccessControl/UserInfo/Record', [
                    'body' => $xmlBody,
                    'http_errors' => false,
                ]);
                $statusCode = $response->getStatusCode();
            } catch (Exception $ex) {
                $statusCode = 500;
            }

            if ($statusCode !== 200 && $statusCode !== 201) {
                $response = $client->put('ISAPI/AccessControl/UserInfo/Modify', [
                    'body' => $xmlBody,
                    'http_errors' => false,
                ]);
                $statusCode = $response->getStatusCode();
            }

            return ($statusCode === 200 || $statusCode === 201);

        } catch (Exception $e) {
            return false;
        }
    }

    protected function getDecryptedPassword(BiometricDevice $device): string
    {
        try {
            return Crypt::decryptString($device->password);
        } catch (Exception $e) {
            return $device->password;
        }
    }

    public function clearLogs(BiometricDevice $device): bool
    {
        if ($this->isZkDevice($device)) {
            try {
                $zk = $this->makeZk($device->ip_address, intval($device->port ?? 4370));
                if ($zk->connect()) {
                    $zk->clearAttendance();
                    $zk->disconnect();
                    return true;
                }
            } catch (Exception $e) {
                Log::error("ZKTeco Clear Logs Failed: " . $e->getMessage());
                return false;
            }
        }
        return false;
    }

    public function syncShifts(BiometricDevice $device): array
    {
        return $this->syncTime($device)
            ? ['success' => true, 'message' => 'Device Time Synced. Shifts handled by Server.']
            : ['success' => false, 'message' => 'Connection Failed.'];
    }
}
